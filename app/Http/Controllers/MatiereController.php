<?php
namespace App\Http\Controllers;
use App\Models\{Filiere, Centre, AnneeScolaire, Seance, MatiereCentreAnnee};
use Illuminate\Support\Facades\Auth;

class MatiereController extends Controller {

    public function index($centreId) {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);
        $centre = Centre::findOrFail($centreId);
        $annee  = AnneeScolaire::courante();

        // Recalcule les quotas depuis l'historique réel des séances avant affichage,
        // pour corriger tout écart entre les valeurs stockées et les sessions effectivement terminées.
        if ($annee) {
            $this->recalculerQuotas((int)$centreId, (int)$annee->id);
        }

        $filieres = Filiere::with([
            'filiereOptions' => fn($q) => $q->with([
                'niveaux' => fn($q2) => $q2->with([
                    'matieres' => fn($q3) => $q3->with([
                        'quotas' => fn($q4) => $q4
                            ->where('centre_id', $centreId)
                            ->when($annee, fn($q5) => $q5->where('annee_scolaire_id', $annee->id))
                    ])->orderBy('semestre')->orderBy('nom')
                ])->orderBy('ordre')
            ])->orderBy('nom')
        ])->orderBy('nom')->get();

        return view('matieres.index', compact('filieres', 'centre', 'centreId', 'annee'));
    }

    /**
     * Recalcule hp_restant et tpe_dynamique pour toutes les matières de ce centre/année
     * depuis l'historique des séances HP terminées, puis enregistre les résultats.
     *
     * hp_restant  = hp_initial  − Σ(heures des séances avec scan professeur)
     * tpe_dynamique = tpe_initial − Σ(heures des séances sans scan professeur / absences)
     */
    private function recalculerQuotas(int $centreId, int $anneeId): void
    {
        $seances = Seance::with(['matiere', 'options'])
            ->where('statut', 'terminee')
            ->where('type', 'HP')
            ->where('annee_scolaire_id', $anneeId)
            ->where('est_composition', false)
            ->whereHas('options', fn($q) => $q->where('centre_id', $centreId))
            ->get();

        foreach ($seances->groupBy('matiere_id') as $matiereId => $groupe) {
            $matiere  = $groupe->first()->matiere;
            $hpFait   = round($groupe->filter(fn($s) => $s->heure_scan_professeur !== null)->sum('duree_heures'), 1);
            $hpAbsent = round($groupe->filter(fn($s) => $s->heure_scan_professeur === null)->sum('duree_heures'), 1);

            MatiereCentreAnnee::updateOrCreate(
                ['matiere_id' => $matiereId, 'centre_id' => $centreId, 'annee_scolaire_id' => $anneeId],
                [
                    'hp_restant'    => max(0, $matiere->hp_initial  - $hpFait),
                    'tpe_dynamique' => max(0, $matiere->tpe_initial - $hpAbsent),
                ]
            );
        }
    }
}
