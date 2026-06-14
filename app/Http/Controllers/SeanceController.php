<?php
namespace App\Http\Controllers;

use App\Models\{Seance, Matiere, Salle, Option, MatiereCentreAnnee, Centre, User, Presence, AnneeScolaire, Inscription};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SeanceController extends Controller
{
    // ── Auto-mise à jour des statuts selon l'heure réelle ───────────────────
    private function syncStatuts(int $centreId): void
    {
        $now = now();

        // planifiee → en_cours (si le cours a commencé et n'est pas encore fini)
        Seance::whereHas('salle', fn($q) => $q->where('centre_id', $centreId))
            ->where('statut', 'planifiee')
            ->where('debut', '<=', $now)
            ->where('fin', '>', $now)
            ->update(['statut' => 'en_cours']);

        // planifiee ou en_cours → terminee (si l'heure de fin est passée)
        $overdue = Seance::with(['options.inscriptions', 'matiere'])
            ->whereHas('salle', fn($q) => $q->where('centre_id', $centreId))
            ->whereIn('statut', ['planifiee', 'en_cours'])
            ->where('fin', '<=', $now)
            ->get();

        foreach ($overdue as $seance) {
            $seance->update(['statut' => 'terminee']);

            // Vases communicants si prof n'a pas badgé (hors compositions)
            if (!$seance->heure_scan_professeur && $seance->type === 'HP' && !$seance->est_composition) {
                $h = (int) ceil($seance->duree_heures);
                foreach ($seance->options as $opt) {
                    $q = MatiereCentreAnnee::firstOrCreate(
                        ['matiere_id' => $seance->matiere_id, 'centre_id' => $opt->centre_id, 'annee_scolaire_id' => $seance->annee_scolaire_id],
                        ['hp_restant' => $seance->matiere->hp_initial, 'tpe_dynamique' => $seance->matiere->tpe_initial]
                    );
                    $q->appliquerVasesCommunicants($h);
                }
                $this->planifierRattrapage($seance);
            }

            // Absents automatiques
            foreach ($seance->options as $opt) {
                foreach ($opt->inscriptions()->where('statut', 'actif')->get() as $insc) {
                    Presence::firstOrCreate(
                        ['seance_id' => $seance->id, 'inscription_id' => $insc->id],
                        ['statut' => 'absent']
                    );
                }
            }
        }
    }

    // ── Planifie automatiquement une séance de rattrapage HP ─────────────────
    // Cherche le même créneau horaire la semaine suivante (puis +1…+8)
    // dans la même salle et attribue les mêmes groupes au même professeur.
    private function planifierRattrapage(Seance $manquee): void
    {
        $manquee->loadMissing('options');
        $dureeH     = (int) ceil($manquee->duree_heures);
        $optionIds  = $manquee->options->pluck('id')->all();

        for ($semaine = 1; $semaine <= 8; $semaine++) {
            $candidat = $manquee->debut->copy()->addWeeks($semaine);
            $fin      = $candidat->copy()->addHours($dureeH);

            $conflit = Seance::where('salle_id', $manquee->salle_id)
                ->whereIn('statut', ['planifiee', 'en_cours'])
                ->where(fn($q) => $q
                    ->whereBetween('debut', [$candidat, $fin->copy()->subMinute()])
                    ->orWhereBetween('fin',  [$candidat->copy()->addMinute(), $fin])
                    ->orWhere(fn($q2) => $q2->where('debut', '<=', $candidat)->where('fin', '>=', $fin))
                )->exists();

            if (!$conflit) {
                $rattrapage = Seance::create([
                    'matiere_id'        => $manquee->matiere_id,
                    'salle_id'          => $manquee->salle_id,
                    'professeur_id'     => $manquee->professeur_id,
                    'annee_scolaire_id' => $manquee->annee_scolaire_id,
                    'debut'             => $candidat,
                    'fin'               => $fin,
                    'type'              => 'HP',
                    'statut'            => 'planifiee',
                    'is_inter_centre'   => $manquee->is_inter_centre,
                    'est_composition'   => false,
                ]);
                $rattrapage->options()->sync($optionIds);
                return;
            }
        }
        // Si aucun créneau libre dans les 8 prochaines semaines, pas de rattrapage auto.
        // Les heures restent visibles via hp_restant dans le suivi des matières.
    }

    public function index(Request $request, $centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $centre = Centre::findOrFail($centreId);
        $annee  = AnneeScolaire::courante();

        // Sync automatique des statuts avant affichage
        $this->syncStatuts($centreId);

        // Filtres
        $salleId   = $request->get('salle_id');
        $matiereId = $request->get('matiere_id');
        $profId    = $request->get('prof_id');
        $statut    = $request->get('statut');

        $filtreActif = $salleId || $matiereId || $profId || $statut;
        $date        = $request->get('date', today()->toDateString());

        $query = Seance::with(['matiere', 'salle', 'professeur', 'options'])
            ->whereHas('salle', fn($q) => $q->where('centre_id', $centreId));

        if ($filtreActif) {
            if ($salleId)   $query->where('salle_id', $salleId);
            if ($matiereId) $query->where('matiere_id', $matiereId);
            if ($profId)    $query->where('professeur_id', $profId);
            if ($statut)    $query->where('statut', $statut);

            // Filtre matière seul → toute l'année scolaire (du plus récent au plus ancien)
            if ($matiereId && !$salleId && !$profId && !$statut && $annee) {
                $query->where('annee_scolaire_id', $annee->id);
                $seances = $query->orderByDesc('debut')->get();
            } else {
                // Autres filtres → fenêtre 15 jours passés + 60 jours futurs
                $query->whereBetween('debut', [
                    now()->subDays(15)->startOfDay(),
                    now()->addDays(60)->endOfDay(),
                ]);
                $seances = $query->orderBy('debut')->get();
            }
        } else {
            $seances = $query->whereDate('debut', $date)->orderBy('debut')->get();
        }

        $matieres = Matiere::where('archive', false)->orderBy('nom')->get();
        $salles   = Salle::where('centre_id', $centreId)->orderBy('nom')->get();
        $options  = $annee
            ? Option::where('centre_id', $centreId)->where('annee_scolaire_id', $annee->id)
                    ->with('filiereOption', 'niveau')->get()
            : collect();
        $profs = User::where('role', 'ROLE_PROFESSEUR')->where('centre_id', $centreId)->orderBy('name')->get();

        return view('seances.index', compact(
            'seances', 'matieres', 'salles', 'options', 'profs',
            'centreId', 'centre', 'date', 'annee',
            'filtreActif', 'salleId', 'matiereId', 'profId', 'statut'
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
            'duree_heures'  => 'required|in:3,4',
            'type'          => 'required|in:HP,TPE',
            'option_ids'    => 'required|array|min:1',
            'option_ids.*'  => 'exists:options,id',
        ]);

        $salle = Salle::findOrFail($data['salle_id']);
        if (!$user->estAdmin() && $salle->centre_id != $user->centre_id) abort(403);

        $annee = AnneeScolaire::courante();

        // Règle HP avant TPE : les HP doivent être entièrement planifiés avant tout TPE
        if ($data['type'] === 'TPE') {
            $matiere    = Matiere::findOrFail($data['matiere_id']);
            $cid        = $salle->centre_id;
            $hpFait     = Seance::where('matiere_id', $matiere->id)
                ->where('annee_scolaire_id', $annee?->id)
                ->where('statut', 'terminee')->where('type', 'HP')->where('est_composition', false)
                ->whereHas('salle', fn($q) => $q->where('centre_id', $cid))
                ->get()->sum('duree_heures');
            $hpPlanifie = Seance::where('matiere_id', $matiere->id)
                ->where('annee_scolaire_id', $annee?->id)
                ->whereIn('statut', ['planifiee', 'en_cours'])->where('type', 'HP')->where('est_composition', false)
                ->whereHas('salle', fn($q) => $q->where('centre_id', $cid))
                ->get()->sum('duree_heures');
            $hpCouvert  = round($hpFait + $hpPlanifie, 1);
            if ($hpCouvert < $matiere->hp_initial) {
                return back()->withErrors(['type' =>
                    "HP incomplets : {$hpCouvert}h couvertes sur {$matiere->hp_initial}h. "
                    ."Planifiez toutes les séances HP avant de créer des TPE."
                ])->withInput();
            }
        }

        $debut = Carbon::parse($data['debut']);
        $fin   = $debut->copy()->addHours((int) $data['duree_heures']);

        // Vérification capacité
        $total = Option::whereIn('id', $data['option_ids'])
            ->withCount(['inscriptions as nb' => fn($q) => $q->where('statut', 'actif')])
            ->get()->sum('nb');
        if ($total > $salle->capacite) {
            return back()->withErrors(['capacite' => "Capacité insuffisante : {$total} inscrits pour {$salle->capacite} places."])->withInput();
        }

        // Conflit de salle
        $conflit = Seance::where('salle_id', $salle->id)
            ->whereIn('statut', ['planifiee', 'en_cours'])
            ->where(fn($q) => $q
                ->whereBetween('debut', [$debut, $fin->copy()->subMinute()])
                ->orWhereBetween('fin',  [$debut->copy()->addMinute(), $fin])
                ->orWhere(fn($q2) => $q2->where('debut', '<=', $debut)->where('fin', '>=', $fin))
            )->exists();
        if ($conflit) {
            return back()->withErrors(['conflit' => "Conflit : la salle {$salle->nom} est déjà réservée sur ce créneau."])->withInput();
        }

        $seance = Seance::create([
            'matiere_id'        => $data['matiere_id'],
            'salle_id'          => $salle->id,
            'professeur_id'     => $data['professeur_id'],
            'annee_scolaire_id' => $annee?->id,
            'debut'             => $debut,
            'fin'               => $fin,
            'type'              => $data['type'],
            'statut'            => 'planifiee',
            'is_inter_centre'   => Option::whereIn('id', $data['option_ids'])->pluck('centre_id')->unique()->count() > 1,
        ]);
        $seance->options()->sync($data['option_ids']);

        return back()->with('succes', 'Séance de ' . $data['duree_heures'] . 'h créée le ' . $debut->format('d/m/Y à H:i') . '.');
    }

    public function demarrer(Seance $seance)
    {
        if ($seance->statut === 'terminee') return back()->withErrors(['statut' => 'Séance déjà terminée.']);
        $seance->update(['statut' => 'en_cours', 'heure_scan_professeur' => now()]);
        return back()->with('succes', 'Séance démarrée — badge professeur enregistré.');
    }

    public function terminer(Seance $seance)
    {
        $seance->update(['statut' => 'terminee']);

        if (!$seance->heure_scan_professeur && $seance->type === 'HP' && !$seance->est_composition) {
            $h = (int) ceil($seance->duree_heures);
            foreach ($seance->options as $opt) {
                $q = MatiereCentreAnnee::firstOrCreate(
                    ['matiere_id' => $seance->matiere_id, 'centre_id' => $opt->centre_id, 'annee_scolaire_id' => $seance->annee_scolaire_id],
                    ['hp_restant' => $seance->matiere->hp_initial, 'tpe_dynamique' => $seance->matiere->tpe_initial]
                );
                $q->appliquerVasesCommunicants($h);
            }
            $this->planifierRattrapage($seance);
        }

        foreach ($seance->options as $opt) {
            foreach ($opt->inscriptions()->where('statut', 'actif')->get() as $insc) {
                Presence::firstOrCreate(
                    ['seance_id' => $seance->id, 'inscription_id' => $insc->id],
                    ['statut' => 'absent']
                );
            }
        }
        return back()->with('succes', $seance->est_composition ? 'Composition clôturée. Absences enregistrées.' : 'Séance clôturée. Absences enregistrées.');
    }

    public function pause(Request $request, Seance $seance)
    {
        // Pause fixe : 30 minutes
        $DUREE = 30;

        // Pas de pause si séance non en cours
        if ($seance->statut !== 'en_cours') {
            return back()->withErrors(['pause' => 'La séance n\'est pas en cours.']);
        }

        // Pause déjà active ?
        if ($seance->heure_fin_pause && now()->lt($seance->heure_fin_pause)) {
            return back()->withErrors(['pause' => 'Une pause est déjà en cours jusqu\'à ' . $seance->heure_fin_pause->format('H:i') . '.']);
        }

        $debutH = $seance->debut->hour * 60 + $seance->debut->minute;
        $nowMin  = now()->hour * 60 + now()->minute;

        // Soir (≥ 17h30) → pas de pause
        if ($debutH >= 17 * 60 + 30) {
            return back()->withErrors(['pause' => 'Pas de pause autorisée pour les séances du soir (≥ 17h30).']);
        }

        // Master (niveau contient M1, M2, Master) → pas de pause
        $estMaster = $seance->options()->whereHas('niveau', fn($q) =>
            $q->where('libelle', 'like', '%Master%')
              ->orWhere('libelle', 'like', '%M1%')
              ->orWhere('libelle', 'like', '%M2%')
        )->exists();
        if ($estMaster) {
            return back()->withErrors(['pause' => 'Pas de pause autorisée pour les groupes Master.']);
        }

        // Fenêtre autorisée : 10h00–11h00 OU 15h00–16h00
        $fenetresMatin   = [$nowMin >= 10 * 60 && $nowMin < 11 * 60];
        $fenetresApm     = [$nowMin >= 15 * 60 && $nowMin < 16 * 60];
        if (!$fenetresMatin[0] && !$fenetresApm[0]) {
            return back()->withErrors(['pause' =>
                'Pause non autorisée à ' . now()->format('H:i') .
                '. Fenêtres autorisées : 10h00–11h00 ou 15h00–16h00.'
            ]);
        }

        $fin = now()->addMinutes($DUREE);
        $seance->update(['heure_debut_pause' => now(), 'heure_fin_pause' => $fin]);
        return back()->with('succes', "Pause de {$DUREE} min déclarée — reprise à " . $fin->format('H:i') . '.');
    }
}
