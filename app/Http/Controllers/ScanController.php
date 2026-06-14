<?php
namespace App\Http\Controllers;

use App\Models\{Seance, Salle, Presence, SortieTemporaire, Etudiant, Centre, Inscription, AnneeScolaire, Option};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScanController extends Controller
{
    public function index($centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $centre  = Centre::findOrFail($centreId);
        $salles  = Salle::where('centre_id', $centreId)->orderBy('nom')->get();
        $annee   = AnneeScolaire::courante();

        $groupes = $annee
            ? Option::where('centre_id', $centreId)->where('annee_scolaire_id', $annee->id)->orderBy('nom')->get()
            : collect();

        return view('scan.index', compact('salles', 'centre', 'centreId', 'groupes', 'annee'));
    }

    // ── API : liste des étudiants du centre (annuaire de test) ──────────────
    public function etudiants($centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $annee = AnneeScolaire::courante();
        if (!$annee) return response()->json(['etudiants' => []]);

        $rows = DB::table('etudiants as e')
            ->join('inscriptions as i', 'i.etudiant_id', '=', 'e.id')
            ->join('options as o', 'o.id', '=', 'i.option_id')
            ->where('o.centre_id', $centreId)
            ->where('i.annee_scolaire_id', $annee->id)
            ->where('i.statut', 'actif')
            ->orderBy('o.nom')
            ->orderBy('e.nom')
            ->select('e.id', 'e.badge_uid', 'e.nom', 'e.prenom', 'e.matricule',
                     'o.id as option_id', 'o.nom as groupe')
            ->get();

        return response()->json(['etudiants' => $rows]);
    }

    // ── API : séance en cours / à venir dans une salle ───────────────────────
    public function seanceCourante($salleId)
    {
        $salle = Salle::findOrFail($salleId);
        $user  = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $salle->centre_id) abort(403);

        $seance = Seance::with(['matiere', 'professeur', 'options'])
            ->where('salle_id', $salleId)
            ->whereIn('statut', ['en_cours', 'planifiee'])
            ->where('debut', '<=', now()->addMinutes(30))
            ->where('fin', '>=', now())
            ->first();

        if (!$seance) return response()->json(['seance' => null]);

        $pauseActive = $seance->heure_fin_pause && now()->lt($seance->heure_fin_pause);

        return response()->json([
            'seance' => [
                'id'           => $seance->id,
                'statut'       => $seance->statut,
                'type'         => $seance->type,
                'debut'        => $seance->debut->format('H:i'),
                'fin'          => $seance->fin->format('H:i'),
                'matiere_code' => $seance->matiere->code,
                'matiere_nom'  => $seance->matiere->nom,
                'professeur'   => $seance->professeur->name,
                'pause_active' => $pauseActive,
                'pause_fin'    => $pauseActive ? $seance->heure_fin_pause->format('H:i') : null,
                'groupes'      => $seance->options->map(fn($o) => ['id' => $o->id, 'nom' => $o->nom]),
            ],
        ]);
    }

    // ── Traitement du badge scanné ───────────────────────────────────────────
    public function scanner(Request $request)
    {
        $data = $request->validate([
            'badge_uid' => 'required|string',
            'salle_id'  => 'required|exists:salles,id',
            'mode'      => 'required|in:entree,sortie',
        ]);

        $salle    = Salle::findOrFail($data['salle_id']);
        $etudiant = Etudiant::where('badge_uid', $data['badge_uid'])->first();

        if (!$etudiant) {
            return response()->json([
                'autorise' => false,
                'statut'   => 'badge_inconnu',
                'message'  => 'Badge non reconnu dans le système.',
                'couleur'  => 'rouge',
            ]);
        }

        $annee = AnneeScolaire::courante();
        $inscription = $annee
            ? $etudiant->inscriptions()->where('annee_scolaire_id', $annee->id)->where('statut', 'actif')->first()
            : null;

        if (!$inscription) {
            return response()->json([
                'autorise' => false,
                'statut'   => 'non_inscrit',
                'message'  => "{$etudiant->nom} {$etudiant->prenom} n'a pas d'inscription active cette année.",
                'couleur'  => 'rouge',
            ]);
        }

        // L'étudiant appartient-il à ce centre ?
        $optionCentreId = DB::table('options')->where('id', $inscription->option_id)->value('centre_id');
        if ($optionCentreId != $salle->centre_id) {
            return response()->json([
                'autorise' => false,
                'statut'   => 'mauvais_centre',
                'message'  => "{$etudiant->nom} {$etudiant->prenom} n'est pas inscrit dans ce centre.",
                'couleur'  => 'rouge',
            ]);
        }

        // Séance en cours ou débutant sous 30 min
        $seance = Seance::where('salle_id', $salle->id)
            ->whereIn('statut', ['en_cours', 'planifiee'])
            ->where('debut', '<=', now()->addMinutes(30))
            ->where('fin', '>=', now())
            ->first();

        // Blocage pause prof
        if ($seance && $data['mode'] === 'entree'
            && $seance->heure_fin_pause
            && now()->lt($seance->heure_fin_pause)) {
            return response()->json([
                'autorise' => false,
                'statut'   => 'pause_prof',
                'message'  => 'Cours en pause. Reprise à ' . $seance->heure_fin_pause->format('H:i') . '.',
                'couleur'  => 'orange',
            ]);
        }

        return $data['mode'] === 'sortie'
            ? $this->traiterSortie($inscription, $seance, $etudiant)
            : $this->traiterEntree($inscription, $seance, $etudiant);
    }

    private function traiterEntree(Inscription $inscription, ?Seance $seance, Etudiant $etudiant)
    {
        $nom = "{$etudiant->prenom} {$etudiant->nom}";

        // Pas de séance → accès libre
        if (!$seance) {
            return response()->json([
                'autorise' => true,
                'statut'   => 'acces_libre',
                'message'  => "Accès libre autorisé — {$nom}",
                'couleur'  => 'vert',
            ]);
        }

        // L'étudiant est-il dans un groupe lié à cette séance ?
        $inscritDansSeance = $seance->options()->where('options.id', $inscription->option_id)->exists();
        if (!$inscritDansSeance) {
            $groupes = $seance->options()->pluck('nom')->join(', ');
            return response()->json([
                'autorise' => false,
                'statut'   => 'non_inscrit_seance',
                'message'  => "Séance réservée aux groupes : {$groupes}. {$nom} n'est pas concerné.",
                'couleur'  => 'rouge',
            ]);
        }

        $presence = Presence::where('seance_id', $seance->id)
            ->where('inscription_id', $inscription->id)
            ->first();

        if ($presence) {
            $sortie = SortieTemporaire::where('presence_id', $presence->id)
                ->whereNull('heure_rentree')
                ->latest()
                ->first();

            if ($sortie) {
                $min = (int) $sortie->heure_sortie->diffInMinutes(now());
                if ($min > 15) {
                    return response()->json([
                        'autorise' => false,
                        'statut'   => 'rentree_refusee',
                        'message'  => "Réentrée refusée — {$nom} absent depuis {$min} min (max 15 min).",
                        'couleur'  => 'rouge',
                    ]);
                }
                $sortie->update(['heure_rentree' => now(), 'duree_minutes' => $min]);
                return response()->json([
                    'autorise' => true,
                    'statut'   => 'rentree_ok',
                    'message'  => "Réentrée autorisée — {$nom} (absent {$min} min).",
                    'couleur'  => 'vert',
                ]);
            }

            return response()->json([
                'autorise' => true,
                'statut'   => 'deja_present',
                'message'  => "{$nom} est déjà enregistré présent.",
                'couleur'  => 'orange',
            ]);
        }

        Presence::create([
            'seance_id'      => $seance->id,
            'inscription_id' => $inscription->id,
            'statut'         => 'present',
            'heure_entree'   => now(),
        ]);

        return response()->json([
            'autorise' => true,
            'statut'   => 'entree_ok',
            'message'  => "Accès autorisé — {$nom}",
            'couleur'  => 'vert',
        ]);
    }

    private function traiterSortie(Inscription $inscription, ?Seance $seance, Etudiant $etudiant)
    {
        $nom = "{$etudiant->prenom} {$etudiant->nom}";

        if (!$seance) {
            return response()->json([
                'autorise' => true,
                'statut'   => 'sortie_ok',
                'message'  => "Sortie enregistrée — {$nom}.",
                'couleur'  => 'vert',
            ]);
        }

        $presence = Presence::where('seance_id', $seance->id)
            ->where('inscription_id', $inscription->id)
            ->first();

        if (!$presence) {
            return response()->json([
                'autorise' => false,
                'statut'   => 'erreur',
                'message'  => "Aucune entrée enregistrée pour {$nom} dans cette séance.",
                'couleur'  => 'orange',
            ]);
        }

        $minAvant = (int) now()->diffInMinutes($seance->fin, false);

        if ($minAvant > 10) {
            SortieTemporaire::create(['presence_id' => $presence->id, 'heure_sortie' => now()]);
            return response()->json([
                'autorise' => true,
                'statut'   => 'sortie_temporaire',
                'message'  => "Sortie temporaire — {$nom}. Retour obligatoire sous 15 min.",
                'couleur'  => 'orange',
            ]);
        }

        $statut = $minAvant > 0 ? 'sortie_anticipee_toleree' : 'present';
        $presence->update(['statut' => $statut, 'heure_sortie_definitive' => now()]);

        return response()->json([
            'autorise' => true,
            'statut'   => 'sortie_ok',
            'message'  => "Sortie enregistrée — {$nom}.",
            'couleur'  => 'vert',
        ]);
    }
}
