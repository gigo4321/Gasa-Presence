<?php
namespace App\Http\Controllers;

use App\Models\{Seance, Matiere, Salle, Option, MatiereCentre, Centre, User, Presence};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeanceController extends Controller
{
    public function index(Request $request, $centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $date   = $request->get('date', today()->toDateString());
        $centre = Centre::findOrFail($centreId);

        $seances = Seance::with(['matiere','salle','professeur','options'])
            ->whereHas('salle', fn($q) => $q->where('centre_id', $centreId))
            ->whereDate('debut', $date)
            ->orderBy('debut')
            ->get();

        $matieres = Matiere::orderBy('nom')->get();
        $salles   = Salle::where('centre_id', $centreId)->orderBy('nom')->get();
        $options  = Option::where('centre_id', $centreId)->with('filiere')->orderBy('nom')->get();

        // ── FILTRAGE DES PROFESSEURS PAR CENTRE (clé du changement) ──
        // Seuls les profs rattachés à CE centre apparaissent dans le planning
        // email_verified_at non null = actif
        $profs = User::where('role', 'ROLE_PROFESSEUR')
            ->where('centre_id', $centreId)
            ->whereNotNull('email_verified_at') // actifs uniquement
            ->orderBy('name')
            ->get();

        return view('seances.index', compact(
            'seances','matieres','salles','options','profs','centreId','centre','date'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'matiere_id'    => 'required|exists:matieres,id',
            'salle_id'      => 'required|exists:salles,id',
            'professeur_id' => 'required|exists:users,id',
            'debut'         => 'required|date',
            'fin'           => 'required|date|after:debut',
            'type'          => 'required|in:HP,TPE',
            'option_ids'    => 'required|array|min:1',
            'option_ids.*'  => 'exists:options,id',
        ]);

        $salle = Salle::findOrFail($data['salle_id']);
        if (!$user->estAdmin() && $salle->centre_id != $user->centre_id) abort(403);

        // Vérifier que le prof appartient bien au centre de la salle
        $prof = User::findOrFail($data['professeur_id']);
        if (!$user->estAdmin() && $prof->centre_id != $salle->centre_id) {
            return back()->withErrors([
                'professeur_id' => 'Ce professeur n\'est pas rattaché à ce centre.'
            ])->withInput();
        }

        // Vérification capacité (RG-040)
        $totalEtudiants = Option::whereIn('id', $data['option_ids'])
            ->withCount(['etudiants as nb_actifs' => fn($q) => $q->where('statut','actif')])
            ->get()->sum('nb_actifs');

        if ($totalEtudiants > $salle->capacite) {
            return back()->withErrors([
                'capacite' => "Capacité insuffisante : {$totalEtudiants} étudiants pour {$salle->capacite} places dans \"{$salle->nom}\"."
            ])->withInput();
        }

        $centreIds     = Option::whereIn('id', $data['option_ids'])->pluck('centre_id')->unique();
        $isInterCentre = $centreIds->count() > 1;

        $seance = Seance::create([
            'matiere_id'     => $data['matiere_id'],
            'salle_id'       => $data['salle_id'],
            'professeur_id'  => $data['professeur_id'],
            'debut'          => $data['debut'],
            'fin'            => $data['fin'],
            'type'           => $data['type'],
            'statut'         => 'planifiee',
            'is_inter_centre'=> $isInterCentre,
        ]);

        $seance->options()->sync($data['option_ids']);

        return back()->with('succes', 'Séance créée' . ($isInterCentre ? ' (inter-centres).' : '.'));
    }

    public function demarrer(Seance $seance)
    {
        if ($seance->statut !== 'planifiee') return back()->withErrors(['err' => 'Séance déjà démarrée.']);
        $seance->update(['statut' => 'en_cours', 'heure_scan_professeur' => now()]);
        return back()->with('succes', 'Séance démarrée.');
    }

    public function terminer(Seance $seance)
    {
        if (!in_array($seance->statut, ['en_cours','planifiee'])) return back()->withErrors(['err' => 'Impossible.']);
        $seance->update(['statut' => 'terminee']);

        // Vases communicants si prof absent (RG-030)
        if (!$seance->heure_scan_professeur && $seance->type === 'HP') {
            $h = (int) ceil($seance->duree_heures);
            foreach ($seance->options as $opt) {
                $q = MatiereCentre::firstOrCreate(
                    ['matiere_id' => $seance->matiere_id, 'centre_id' => $opt->centre_id],
                    ['hp_restant' => $seance->matiere->hp_initial, 'tpe_dynamique' => $seance->matiere->tpe_initial]
                );
                $q->appliquerVasesCommunicants($h);
            }
        }

        // Absents automatiques
        foreach ($seance->options as $opt) {
            foreach ($opt->etudiants()->where('statut','actif')->get() as $e) {
                Presence::firstOrCreate(
                    ['seance_id' => $seance->id, 'etudiant_id' => $e->id],
                    ['statut' => 'absent']
                );
            }
        }

        return back()->with('succes', 'Séance clôturée. Absences enregistrées.');
    }

    public function pause(Request $request, Seance $seance)
    {
        $data = $request->validate(['duree_minutes' => 'required|integer|min:1|max:60']);
        $fin  = now()->addMinutes($data['duree_minutes']);
        $seance->update(['heure_debut_pause' => now(), 'heure_fin_pause' => $fin]);
        return back()->with('succes', "Pause jusqu'à " . $fin->format('H:i') . '.');
    }
}
