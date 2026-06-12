<?php
namespace App\Http\Controllers;

use App\Models\{Centre, Etudiant, Seance, Presence, Filiere};
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // ── Dashboard Directeur — vue consolidée toute l'école ─────────
    public function directeur()
    {
        $user = Auth::user();
        if (!$user->estAdmin()) {
            return redirect()->route('dashboard.centre', $user->centre_id);
        }

        $centres = Centre::all();

        // Statistiques globales
        $stats = [
            'nb_centres'            => $centres->count(),
            'total_etudiants'       => Etudiant::where('statut', 'actif')->count(),
            'seances_aujourd_hui'   => Seance::whereDate('debut', today())->count(),
            'total_interpellations' => 0, // sera calculé ci-dessous

            // Répartition par filière
            'par_filiere' => Etudiant::where('statut', 'actif')
                ->join('options', 'etudiants.option_id', '=', 'options.id')
                ->join('filieres', 'options.filiere_id', '=', 'filieres.id')
                ->selectRaw('filieres.nom as filiere, count(*) as nb')
                ->groupBy('filieres.nom')
                ->orderByDesc('nb')
                ->pluck('nb', 'filiere')
                ->toArray(),

            // Activité récente (7 jours)
            'seances_recentes' => Seance::with(['matiere', 'salle.centre'])
                ->where('debut', '>=', now()->subDays(7))
                ->orderByDesc('debut')
                ->limit(6)
                ->get(),
        ];

        // Statistiques par centre
        $centresStats = [];
        foreach ($centres as $c) {
            $nbEtudiants = Etudiant::whereHas('option', fn($q) => $q->where('centre_id', $c->id))
                ->where('statut', 'actif')->count();

            $seancesAujourdhui = Seance::whereHas('salle', fn($q) => $q->where('centre_id', $c->id))
                ->whereDate('debut', today())->count();

            $enCours = Seance::whereHas('salle', fn($q) => $q->where('centre_id', $c->id))
                ->where('statut', 'en_cours')->count();

            // Interpellations : étudiants avec plus de 25% d'absences
            $interpellations = Presence::whereHas('seance.salle', fn($q) => $q->where('centre_id', $c->id))
                ->where('statut', 'absent')
                ->distinct('etudiant_id')
                ->count('etudiant_id');

            // Taux d'assiduité global du centre
            $totalPresences = Presence::whereHas('seance.salle', fn($q) => $q->where('centre_id', $c->id))->count();
            $presentsOk     = Presence::whereHas('seance.salle', fn($q) => $q->where('centre_id', $c->id))
                ->where('statut', '!=', 'absent')->count();
            $taux = $totalPresences > 0 ? round($presentsOk / $totalPresences * 100) : 100;

            $stats['total_interpellations'] += $interpellations;

            $centresStats[] = [
                'centre'             => $c,
                'nb_etudiants'       => $nbEtudiants,
                'seances_aujourd_hui'=> $seancesAujourdhui,
                'en_cours'           => $enCours,
                'interpellations'    => $interpellations,
                'taux_assiduite'     => $taux,
            ];
        }

        return view('dashboard.directeur', compact('centres', 'stats', 'centresStats'));
    }

    // ── Dashboard Centre — vue cloisonnée ───────────────────────────
    public function centre($centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) {
            abort(403, 'Accès refusé : vous ne pouvez accéder qu\'à votre propre centre.');
        }

        $centre = Centre::findOrFail($centreId);

        $nbEtudiants = Etudiant::whereHas('option', fn($q) => $q->where('centre_id', $centreId))
            ->where('statut', 'actif')->count();

        $seancesAujourdhui = Seance::whereHas('salle', fn($q) => $q->where('centre_id', $centreId))
            ->whereDate('debut', today())
            ->with(['matiere', 'salle', 'professeur'])
            ->orderBy('debut')
            ->get();

        $seancesEnCours = $seancesAujourdhui->where('statut', 'en_cours')->count();

        $nbInterpellations = Presence::whereHas('seance.salle', fn($q) => $q->where('centre_id', $centreId))
            ->where('statut', 'absent')
            ->distinct('etudiant_id')
            ->count('etudiant_id');

        return view('dashboard.centre', compact(
            'centre', 'nbEtudiants', 'seancesAujourdhui',
            'seancesEnCours', 'nbInterpellations', 'centreId'
        ));
    }
}
