<?php
namespace App\Http\Controllers;

use App\Models\{Seance, Matiere, Salle, Option, MatiereCentreAnnee, Centre, User, AnneeScolaire, Filiere};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlanningController extends Controller
{
    public function apercu(Request $request, $centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $centre = Centre::findOrFail($centreId);
        $annee  = AnneeScolaire::courante();

        if (!$annee) {
            return view('planning.apercu', [
                'centre'       => $centre,
                'centreId'     => $centreId,
                'annee'        => null,
                'matieres'     => collect(),
                'salles'       => collect(),
                'profs'        => collect(),
                'groupes'      => collect(),
                'autresOptions'=> collect(),
            ]);
        }

        // Matières liées aux groupes actifs du centre
        $optionIds = Option::where('centre_id', $centreId)
            ->where('annee_scolaire_id', $annee->id)
            ->pluck('id');

        $matiereIds = DB::table('option_seance')
            ->join('seances', 'seances.id', '=', 'option_seance.seance_id')
            ->whereIn('option_seance.option_id', $optionIds)
            ->pluck('seances.matiere_id')
            ->merge(
                DB::table('matieres')
                    ->join('niveaux', 'niveaux.id', '=', 'matieres.niveau_id')
                    ->join('filiere_options', 'filiere_options.id', '=', 'niveaux.filiere_option_id')
                    ->join('options', function ($j) use ($centreId, $annee) {
                        $j->on('options.filiere_option_id', '=', 'filiere_options.id')
                          ->where('options.centre_id', $centreId)
                          ->where('options.annee_scolaire_id', $annee->id);
                    })
                    ->where('matieres.archive', false)
                    ->pluck('matieres.id')
            )
            ->unique()
            ->values();

        $matieres = Matiere::whereIn('id', $matiereIds)
            ->where('archive', false)
            ->with(['filiere', 'niveau.filiereOption'])
            ->orderBy('semestre')->orderBy('nom')
            ->get()
            ->map(function ($m) use ($centreId, $annee) {
                $seancesTerminees = Seance::where('matiere_id', $m->id)
                    ->where('annee_scolaire_id', $annee->id)
                    ->where('statut', 'terminee')
                    ->where('est_composition', false)
                    ->whereHas('salle', fn($q) => $q->where('centre_id', $centreId))
                    ->get();

                $m->hp_fait     = round($seancesTerminees->where('type', 'HP')->sum('duree_heures'), 1);
                $m->tpe_fait    = round($seancesTerminees->where('type', 'TPE')->sum('duree_heures'), 1);
                $m->hp_restant  = max(0, round($m->hp_initial  - $m->hp_fait,  1));
                $m->tpe_restant = max(0, round($m->tpe_initial - $m->tpe_fait, 1));
                $m->nb_seances  = $seancesTerminees->count();

                $m->hp_planifie = round(
                    Seance::where('matiere_id', $m->id)->where('annee_scolaire_id', $annee->id)
                        ->whereIn('statut', ['planifiee', 'en_cours'])->where('type', 'HP')
                        ->where('est_composition', false)
                        ->whereHas('salle', fn($q) => $q->where('centre_id', $centreId))
                        ->get()->sum('duree_heures'), 1);

                $m->tpe_planifie = round(
                    Seance::where('matiere_id', $m->id)->where('annee_scolaire_id', $annee->id)
                        ->whereIn('statut', ['planifiee', 'en_cours'])->where('type', 'TPE')
                        ->where('est_composition', false)
                        ->whereHas('salle', fn($q) => $q->where('centre_id', $centreId))
                        ->get()->sum('duree_heures'), 1);

                $m->a_composition = Seance::where('matiere_id', $m->id)
                    ->where('annee_scolaire_id', $annee->id)
                    ->where('est_composition', true)
                    ->exists();

                return $m;
            });

        $salles        = Salle::where('centre_id', $centreId)->orderBy('nom')->get();
        $profs         = User::where('role', 'ROLE_PROFESSEUR')->where('centre_id', $centreId)->orderBy('name')->get();
        $groupes       = Option::where('centre_id', $centreId)->where('annee_scolaire_id', $annee->id)->orderBy('nom')->get();
        $autresOptions = Option::where('annee_scolaire_id', $annee->id)
            ->where('centre_id', '!=', $centreId)
            ->with('centre')
            ->orderBy('nom')
            ->get();

        // Filières représentées dans les matières de ce centre (pour les filtres)
        $filiereIds = $matieres->pluck('filiere_id')->unique()->filter();
        $filieres   = Filiere::whereIn('id', $filiereIds)->orderBy('nom')->get();

        return view('planning.apercu', compact(
            'centre', 'centreId', 'annee', 'matieres', 'salles', 'profs', 'groupes', 'autresOptions', 'filieres'
        ));
    }

    // ── Génération séances récurrentes par matière ──────────────────────────────
    public function generer(Request $request, $centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $data = $request->validate([
            'matiere_id'       => 'required|exists:matieres,id',
            'professeur_id'    => 'required|exists:users,id',
            'salle_id'         => 'required|exists:salles,id',
            'option_ids'       => 'required|array|min:1',
            'option_ids.*'     => 'exists:options,id',
            'type'             => 'required|in:HP,TPE',
            'date_debut'       => 'required|date',
            'heure_debut'      => 'required|date_format:H:i',
            'duree_heures'     => 'required|in:3,4',
            'jour_semaine'     => 'required|integer|min:1|max:7',
            'nb_semaines'      => 'required|integer|min:1|max:52',
        ]);

        $annee = AnneeScolaire::courante();
        if (!$annee) return back()->withErrors(['annee' => 'Aucune année scolaire active.']);

        $salle = Salle::findOrFail($data['salle_id']);
        if ($salle->centre_id != $centreId) abort(403);

        // Règle HP avant TPE
        if ($data['type'] === 'TPE') {
            $matiere    = Matiere::findOrFail($data['matiere_id']);
            $hpFait     = Seance::where('matiere_id', $matiere->id)->where('annee_scolaire_id', $annee->id)
                ->where('statut', 'terminee')->where('type', 'HP')->where('est_composition', false)
                ->whereHas('salle', fn($q) => $q->where('centre_id', $centreId))->get()->sum('duree_heures');
            $hpPlanifie = Seance::where('matiere_id', $matiere->id)->where('annee_scolaire_id', $annee->id)
                ->whereIn('statut', ['planifiee', 'en_cours'])->where('type', 'HP')->where('est_composition', false)
                ->whereHas('salle', fn($q) => $q->where('centre_id', $centreId))->get()->sum('duree_heures');
            $hpCouvert = round($hpFait + $hpPlanifie, 1);
            if ($hpCouvert < $matiere->hp_initial) {
                return back()->withErrors(['type' =>
                    "HP incomplets : {$hpCouvert}h/{$matiere->hp_initial}h planifiées. Terminez les HP avant les TPE."
                ]);
            }
        }

        $dateBase  = Carbon::parse($data['date_debut']);
        $jourCible = (int) $data['jour_semaine'];
        $dureeH    = (int) $data['duree_heures'];

        $dateStart = $dateBase->copy()->startOfWeek()->addDays($jourCible - 1);
        if ($dateStart->lt($dateBase)) $dateStart->addWeek();

        $crees = $skipped = 0;

        for ($i = 0; $i < $data['nb_semaines']; $i++) {
            $debut = Carbon::parse($dateStart->format('Y-m-d') . ' ' . $data['heure_debut']);
            $fin   = $debut->copy()->addHours($dureeH);

            $conflit = Seance::where('salle_id', $salle->id)
                ->where(fn($q) => $q
                    ->whereBetween('debut', [$debut, $fin->copy()->subMinute()])
                    ->orWhereBetween('fin',  [$debut->copy()->addMinute(), $fin])
                    ->orWhere(fn($q2) => $q2->where('debut', '<=', $debut)->where('fin', '>=', $fin))
                )
                ->whereIn('statut', ['planifiee', 'en_cours'])
                ->exists();

            if ($conflit) {
                $skipped++;
            } else {
                $seance = Seance::create([
                    'matiere_id'        => $data['matiere_id'],
                    'salle_id'          => $salle->id,
                    'professeur_id'     => $data['professeur_id'],
                    'annee_scolaire_id' => $annee->id,
                    'debut'             => $debut,
                    'fin'               => $fin,
                    'type'              => $data['type'],
                    'statut'            => 'planifiee',
                    'is_inter_centre'   => false,
                    'est_composition'   => false,
                ]);
                $seance->options()->sync($data['option_ids']);
                $crees++;
            }

            $dateStart->addWeek();
        }

        $msg = "{$crees} séance(s) créée(s)";
        if ($skipped) $msg .= ", {$skipped} ignorée(s) (conflit de salle)";

        return back()->with('succes', $msg . '.');
    }

    // ── Génération planning mi-semestre ─────────────────────────────────────────
    public function genererMiSemestre(Request $request, $centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $data = $request->validate([
            'matiere_ids'        => 'required|array|min:1',
            'matiere_ids.*'      => 'exists:matieres,id',
            'option_ids'         => 'required|array|min:1',
            'option_ids.*'       => 'exists:options,id',
            'option_ids_compo'   => 'nullable|array',
            'option_ids_compo.*' => 'exists:options,id',
            'salle_cours_id'     => 'required|exists:salles,id',
            'salle_compo_id'     => 'required|exists:salles,id',
            'date_debut'         => 'required|date',
            'jours_cours'        => 'required|array|min:1',
            'jours_cours.*'      => 'integer|min:1|max:7',
            'jour_compo'         => 'required|integer|min:1|max:7',
            'heure_debut_cours'  => 'required|date_format:H:i',
            'duree_cours'        => 'required|in:3,4',
            'heure_debut_compo'  => 'required|date_format:H:i',
            'duree_compo'        => 'required|in:3,4',
            'surveillant_id'     => 'required|exists:users,id',
        ]);

        $annee = AnneeScolaire::courante();
        if (!$annee) return back()->withErrors(['annee' => 'Aucune année scolaire active.']);

        $salleCours = Salle::findOrFail($data['salle_cours_id']);
        $salleCompo = Salle::findOrFail($data['salle_compo_id']);
        if ($salleCours->centre_id != $centreId || $salleCompo->centre_id != $centreId) abort(403);

        $joursCours  = array_map('intval', $data['jours_cours']);
        $jourCompo   = (int) $data['jour_compo'];
        $dureeCoursH = (int) $data['duree_cours'];
        $dureeCompoH = (int) $data['duree_compo'];

        $optionIdsCompo = array_values(array_unique(array_merge(
            $data['option_ids'],
            $data['option_ids_compo'] ?? []
        )));
        $isInterCentre = Option::whereIn('id', $optionIdsCompo)->pluck('centre_id')->unique()->count() > 1;

        // ── Construire la file tournante des matières ──────────────────────────
        $pool = []; // ['matiere_id', 'matiere', 'prof_id', 'remaining']

        foreach ($data['matiere_ids'] as $matiereId) {
            $matiere = Matiere::findOrFail($matiereId);
            $profId  = DB::table('matiere_professeur')
                ->where('matiere_id', $matiereId)
                ->first()?->user_id ?? $data['surveillant_id'];

            $hpFait = Seance::where('matiere_id', $matiereId)
                ->where('annee_scolaire_id', $annee->id)
                ->where('statut', 'terminee')
                ->where('type', 'HP')
                ->where('est_composition', false)
                ->whereHas('salle', fn($q) => $q->where('centre_id', $centreId))
                ->get()->sum('duree_heures');

            $hpPlanifie = Seance::where('matiere_id', $matiereId)
                ->where('annee_scolaire_id', $annee->id)
                ->whereIn('statut', ['planifiee', 'en_cours'])
                ->where('type', 'HP')
                ->where('est_composition', false)
                ->whereHas('salle', fn($q) => $q->where('centre_id', $centreId))
                ->get()->sum('duree_heures');

            $hpRestant = max(0, $matiere->hp_initial - $hpFait - $hpPlanifie);
            $nbSeances = $hpRestant > 0 ? (int) ceil($hpRestant / $dureeCoursH) : 0;

            if ($nbSeances > 0) {
                $pool[] = [
                    'matiere_id' => (int) $matiereId,
                    'matiere'    => $matiere,
                    'prof_id'    => $profId,
                    'remaining'  => $nbSeances,
                ];
            }
        }

        // ── Algorithme rotatif jour par jour ──────────────────────────────────
        // Chaque jour de cours : séance HP pour la prochaine matière en rotation
        // Dès qu'une matière finit ses HP → file d'attente composition
        // Jour de compo : programmer la prochaine composition en attente
        // => La salle de cours reste disponible pour d'autres groupes les jours de compo
        // => La salle de compo est dédiée, aucune séance HP ne l'utilise

        $pendingCompos = []; // matières prêtes pour leur composition
        $poolIdx       = 0;
        $dateCurr      = Carbon::parse($data['date_debut'])->startOfDay();
        $creesCours    = 0;
        $creesCompo    = 0;
        $skipped       = 0;
        $maxIter       = 500;
        $iter          = 0;

        while ((!empty($pool) || !empty($pendingCompos)) && $iter++ < $maxIter) {
            $jourISO     = $dateCurr->dayOfWeekIso; // 1=Lundi … 7=Dimanche
            $estJourCours = in_array($jourISO, $joursCours);
            $estJourCompo = ($jourISO === $jourCompo);

            // ── Priorité : composition si le jour y est dédié et une compo attend ──
            if ($estJourCompo && !empty($pendingCompos)) {
                $item  = $pendingCompos[0];
                $debut = Carbon::parse($dateCurr->format('Y-m-d') . ' ' . $data['heure_debut_compo']);
                $fin   = $debut->copy()->addHours($dureeCompoH);

                $conflit = $this->aConflit($salleCompo->id, $debut, $fin);

                if (!$conflit) {
                    $seanceC = Seance::create([
                        'matiere_id'        => $item['matiere_id'],
                        'salle_id'          => $salleCompo->id,
                        'professeur_id'     => $data['surveillant_id'],
                        'annee_scolaire_id' => $annee->id,
                        'debut'             => $debut,
                        'fin'               => $fin,
                        'type'              => 'HP',
                        'statut'            => 'planifiee',
                        'is_inter_centre'   => $isInterCentre,
                        'est_composition'   => true,
                    ]);
                    $seanceC->options()->sync($optionIdsCompo);
                    $creesCompo++;
                    array_shift($pendingCompos);
                }
                // Si conflit, on réessaie au prochain jour de compo → on continue
            }

            // ── Cours HP si c'est un jour de cours ET le groupe n'a pas composition ce jour ──
            // (si jour_compo == un des jours_cours et compo programmée ce jour, on saute le cours)
            $compoProgCeJour = $estJourCompo && !empty($pendingCompos);
            if ($estJourCours && !$compoProgCeJour && !empty($pool)) {
                // Rotation : on essaie chaque matière à partir de poolIdx
                $tentatives = count($pool);
                $scheduled  = false;

                for ($t = 0; $t < $tentatives; $t++) {
                    $idx  = $poolIdx % count($pool);
                    $item = &$pool[$idx];

                    if ($item['remaining'] > 0) {
                        $debut = Carbon::parse($dateCurr->format('Y-m-d') . ' ' . $data['heure_debut_cours']);
                        $fin   = $debut->copy()->addHours($dureeCoursH);

                        if (!$this->aConflit($salleCours->id, $debut, $fin)) {
                            $seance = Seance::create([
                                'matiere_id'        => $item['matiere_id'],
                                'salle_id'          => $salleCours->id,
                                'professeur_id'     => $item['prof_id'],
                                'annee_scolaire_id' => $annee->id,
                                'debut'             => $debut,
                                'fin'               => $fin,
                                'type'              => 'HP',
                                'statut'            => 'planifiee',
                                'is_inter_centre'   => false,
                                'est_composition'   => false,
                            ]);
                            $seance->options()->sync($data['option_ids']);
                            $creesCours++;
                            $item['remaining']--;
                            $scheduled = true;

                            if ($item['remaining'] === 0) {
                                $pendingCompos[] = $item;
                                array_splice($pool, $idx, 1);
                                if (!empty($pool)) {
                                    $poolIdx = $idx % count($pool);
                                }
                            } else {
                                $poolIdx = ($idx + 1) % max(1, count($pool));
                            }
                            break;
                        }
                    }

                    $poolIdx = ($idx + 1) % max(1, count($pool));
                    $skipped++;
                }
            }

            $dateCurr->addDay();
        }

        $msg = "{$creesCours} cours HP + {$creesCompo} composition(s) créés";
        if ($skipped) $msg .= " ({$skipped} créneau(x) ignoré(s) pour conflit)";
        if (!empty($pendingCompos)) {
            $msg .= ". ⚠ " . count($pendingCompos) . " composition(s) non planifiée(s) (augmentez la plage de dates)";
        }
        if (!empty($pool)) {
            $rest = array_sum(array_column($pool, 'remaining'));
            $msg .= ". ⚠ {$rest} séance(s) HP non planifiée(s) (augmentez la plage de dates)";
        }

        return back()->with('succes', $msg . '.');
    }

    // ── Utilitaire conflit de salle ──────────────────────────────────────────────
    private function aConflit(int $salleId, Carbon $debut, Carbon $fin): bool
    {
        return Seance::where('salle_id', $salleId)
            ->whereIn('statut', ['planifiee', 'en_cours'])
            ->where(fn($q) => $q
                ->whereBetween('debut', [$debut, $fin->copy()->subMinute()])
                ->orWhereBetween('fin',  [$debut->copy()->addMinute(), $fin])
                ->orWhere(fn($q2) => $q2->where('debut', '<=', $debut)->where('fin', '>=', $fin))
            )->exists();
    }
}
