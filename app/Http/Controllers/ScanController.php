<?php
namespace App\Http\Controllers;

use App\Models\{Seance, Salle, Presence, SortieTemporaire};
use App\Models\Etudiant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScanController extends Controller
{
    // Page du simulateur de scan
    public function index($centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $salles  = Salle::where('centre_id', $centreId)->get();
        $centre  = \App\Models\Centre::findOrFail($centreId);
        $historique = [];

        return view('scan.index', compact('salles','centre','centreId','historique'));
    }

    // POST /scan/badge — traiter un scan
    public function scanner(Request $request)
    {
        $data = $request->validate([
            'badge_uid' => 'required|string',
            'salle_id'  => 'required|exists:salles,id',
            'mode'      => 'required|in:entree,sortie',
        ]);

        $salle    = Salle::findOrFail($data['salle_id']);
        $etudiant = Etudiant::where('badge_uid', $data['badge_uid'])->first();
        $maintenant = now();

        if (!$etudiant) {
            return response()->json([
                'autorise' => false,
                'statut'   => 'badge_inconnu',
                'message'  => 'Badge non reconnu dans le système.',
                'couleur'  => 'rouge',
            ]);
        }

        if ($etudiant->statut !== 'actif') {
            return response()->json([
                'autorise' => false,
                'statut'   => 'etudiant_inactif',
                'message'  => "Étudiant {$etudiant->statut}. Accès refusé.",
                'couleur'  => 'rouge',
            ]);
        }

        // Trouver la séance active dans cette salle
        $seance = Seance::where('salle_id', $salle->id)
            ->where('statut', 'en_cours')
            ->whereTime('debut', '<=', $maintenant)
            ->whereTime('fin', '>=', $maintenant)
            ->first();

        // Autorisation 30 min avant si séance planifiée
        if (!$seance) {
            $seance = Seance::where('salle_id', $salle->id)
                ->where('statut', 'planifiee')
                ->where('debut', '<=', $maintenant->copy()->addMinutes(30))
                ->where('fin', '>=', $maintenant)
                ->first();
        }

        // LABO : accès strictement verrouillé (RG-058)
        if ($salle->estLabo() && !$seance) {
            return response()->json([
                'autorise' => false,
                'statut'   => 'labo_verrouille',
                'message'  => 'Laboratoire verrouillé. Aucun cours en ce moment.',
                'couleur'  => 'rouge',
            ]);
        }

        // Pause prof en cours (RG-060)
        if ($seance && $seance->heure_fin_pause && now()->lt($seance->heure_fin_pause) && $data['mode'] === 'entree') {
            return response()->json([
                'autorise' => false,
                'statut'   => 'pause_prof',
                'message'  => 'Pause enseignant en cours. Reprise à ' . $seance->heure_fin_pause->format('H:i') . '.',
                'couleur'  => 'orange',
            ]);
        }

        // MODE SORTIE
        if ($data['mode'] === 'sortie') {
            return $this->traiterSortie($etudiant, $seance, $salle);
        }

        // MODE ENTRÉE
        return $this->traiterEntree($etudiant, $seance, $salle);
    }

    private function traiterEntree($etudiant, $seance, $salle)
    {
        if (!$seance) {
            // Salle banalisée sans séance : accès libre (RG-057)
            if (!$salle->estLabo()) {
                return response()->json([
                    'autorise' => true,
                    'statut'   => 'acces_libre',
                    'message'  => "Aucun cours. Accès autorisé avec badge valide ({$etudiant->nom} {$etudiant->prenom}).",
                    'couleur'  => 'vert',
                ]);
            }
        }

        // Vérifier que l'étudiant est inscrit à cette séance
        $inscrit = $seance->options()->whereHas('etudiants', fn($q) => $q->where('etudiants.id', $etudiant->id))->exists();

        if (!$inscrit) {
            return response()->json([
                'autorise' => false,
                'statut'   => 'non_inscrit',
                'message'  => "Non inscrit à ce cours ({$etudiant->nom} {$etudiant->prenom}).",
                'couleur'  => 'rouge',
            ]);
        }

        // Vérifier sortie temporaire (RG-062 : règle 15 min)
        $presence = Presence::where('seance_id', $seance->id)->where('etudiant_id', $etudiant->id)->first();
        if ($presence) {
            $derniereSortie = SortieTemporaire::where('presence_id', $presence->id)
                ->whereNull('heure_rentree')->latest()->first();

            if ($derniereSortie) {
                $minutesAbsent = $derniereSortie->heure_sortie->diffInMinutes(now());
                if ($minutesAbsent > 15) {
                    return response()->json([
                        'autorise' => false,
                        'statut'   => 'rentree_refusee',
                        'message'  => "Réentrée refusée : absent depuis {$minutesAbsent} min (max 15 min).",
                        'couleur'  => 'rouge',
                        'nom'      => "{$etudiant->nom} {$etudiant->prenom}",
                    ]);
                }
                // Fermer la sortie temporaire
                $derniereSortie->update(['heure_rentree' => now()]);
                return response()->json([
                    'autorise' => true,
                    'statut'   => 'rentree_ok',
                    'message'  => "Réentrée autorisée ({$minutesAbsent} min d'absence). {$etudiant->nom} {$etudiant->prenom}",
                    'couleur'  => 'vert',
                ]);
            }
        }

        // Créer ou récupérer la présence
        Presence::firstOrCreate(
            ['seance_id' => $seance->id, 'etudiant_id' => $etudiant->id],
            ['statut' => 'present', 'heure_entree' => now()]
        );

        return response()->json([
            'autorise' => true,
            'statut'   => 'entree_ok',
            'message'  => "Accès autorisé. {$etudiant->nom} {$etudiant->prenom}",
            'couleur'  => 'vert',
        ]);
    }

    private function traiterSortie($etudiant, $seance, $salle)
    {
        if (!$seance) {
            return response()->json([
                'autorise' => true,
                'statut'   => 'sortie_ok',
                'message'  => "Sortie enregistrée. {$etudiant->nom} {$etudiant->prenom}",
                'couleur'  => 'vert',
            ]);
        }

        $presence = Presence::where('seance_id', $seance->id)->where('etudiant_id', $etudiant->id)->first();
        if (!$presence) {
            return response()->json([
                'autorise' => false,
                'statut'   => 'erreur',
                'message'  => 'Aucune entrée enregistrée pour cet étudiant.',
                'couleur'  => 'orange',
            ]);
        }

        // Vérifier tolérance sortie anticipée (RG-066 : 10 min)
        $finSeance    = \Carbon\Carbon::parse($seance->fin);
        $minutesAvant = now()->diffInMinutes($finSeance, false);
        $statut       = 'sortie_ok';
        $message      = "Sortie enregistrée. {$etudiant->nom} {$etudiant->prenom}";

        if ($minutesAvant > 10) {
            // Sortie temporaire (RG-061)
            SortieTemporaire::create([
                'presence_id' => $presence->id,
                'heure_sortie'=> now(),
            ]);
            $statut  = 'sortie_temporaire';
            $message = "Sortie temporaire. {$etudiant->nom} — max 15 min pour revenir.";
        } elseif ($minutesAvant > 0) {
            $presence->update(['statut' => 'sortie_anticipee_toleree', 'heure_sortie_definitive' => now()]);
            $statut  = 'sortie_anticipee_toleree';
            $message = "Sortie anticipée tolérée (< 10 min). {$etudiant->nom} {$etudiant->prenom}";
        } else {
            $presence->update(['heure_sortie_definitive' => now()]);
        }

        return response()->json([
            'autorise' => true,
            'statut'   => $statut,
            'message'  => $message,
            'couleur'  => $minutesAvant > 10 ? 'orange' : 'vert',
        ]);
    }
}
