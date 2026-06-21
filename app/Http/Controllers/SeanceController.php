<?php
namespace App\Http\Controllers;

use App\Models\{Seance, Matiere, Salle, Option, MatiereCentreAnnee, Centre, User, Presence, AnneeScolaire, Inscription, ContestationHoraire};
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

            // TPE : clôture automatique, aucune validation professeur requise
            if ($seance->type === 'TPE' && !$seance->cloture_validee_at) {
                $seance->update(['cloture_validee_at' => now()]);
            }

            if ($seance->type === 'HP' && !$seance->est_composition) {
                $h = (int) ceil($seance->duree_heures);
                foreach ($seance->options as $opt) {
                    $q = MatiereCentreAnnee::firstOrCreate(
                        ['matiere_id' => $seance->matiere_id, 'centre_id' => $opt->centre_id, 'annee_scolaire_id' => $seance->annee_scolaire_id],
                        ['hp_restant' => $seance->matiere->hp_initial, 'tpe_dynamique' => $seance->matiere->tpe_initial]
                    );
                    if ($seance->heure_scan_professeur) {
                        $q->hp_restant = max(0, $q->hp_restant - $h);
                        $q->save();
                    } else {
                        $q->appliquerVasesCommunicants($h);
                    }
                }
                if (!$seance->heure_scan_professeur) {
                    $this->planifierRattrapage($seance);
                }
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
            ->withCount(['presences as nb_presents_auto' => fn($q) => $q->where('statut', 'present')])
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
        // Filtre de la page : seulement le centre courant
        $profs    = User::where('role', 'ROLE_PROFESSEUR')->where('centre_id', $centreId)->orderBy('name')->get();
        // Formulaire : tous les centres (séances inter-centres)
        $toutesOptions = $annee
            ? Option::where('annee_scolaire_id', $annee->id)
                    ->with('filiereOption', 'niveau', 'centre')
                    ->orderBy('centre_id')->orderBy('nom')->get()
            : collect();
        $tousProfs = User::where('role', 'ROLE_PROFESSEUR')
            ->with('centre')->orderBy('name')->get();

        return view('seances.index', compact(
            'seances', 'matieres', 'salles', 'profs', 'toutesOptions', 'tousProfs',
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
            'professeur_id' => 'nullable|exists:users,id',
            'debut'         => 'required|date',
            'duree_heures'  => 'required|in:3,4',
            'type'          => 'required|in:HP,TPE',
            'option_ids'    => 'required|array|min:1',
            'option_ids.*'  => 'exists:options,id',
        ]);

        $salle = Salle::findOrFail($data['salle_id']);
        if (!$user->estAdmin() && $salle->centre_id != $user->centre_id) abort(403);

        $annee = AnneeScolaire::courante();

        // HP requiert un professeur ; TPE n'en a pas (travail autonome des étudiants)
        if ($data['type'] === 'HP' && empty($data['professeur_id'])) {
            return back()->withErrors(['professeur_id' => 'Un professeur est requis pour une séance HP.'])->withInput();
        }

        // Règle HP avant TPE : vérifier pour CHAQUE centre des groupes sélectionnés
        if ($data['type'] === 'TPE') {
            $centresIds = Option::whereIn('id', $data['option_ids'])->pluck('centre_id')->unique();
            foreach ($centresIds as $cid) {
                $mca = MatiereCentreAnnee::where('matiere_id', $data['matiere_id'])
                    ->where('centre_id', $cid)
                    ->where('annee_scolaire_id', $annee?->id)
                    ->first();
                $hpRestant = $mca?->hp_restant ?? Matiere::findOrFail($data['matiere_id'])->hp_initial;
                if ($hpRestant > 0) {
                    $nomCentre = Centre::find($cid)?->nom ?? "Centre #{$cid}";
                    return back()->withErrors(['type' =>
                        "HP incomplets pour {$nomCentre} : {$hpRestant}h encore dues. "
                        ."Toutes les séances HP doivent être terminées avant de créer des TPE."
                    ])->withInput();
                }
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
            'professeur_id'     => $data['professeur_id'] ?? null,
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
        if ($seance->statut === 'terminee') {
            return back()->withErrors(['statut' => 'Séance déjà terminée.']);
        }

        $seance->update(['statut' => 'terminee']);

        if ($seance->type === 'TPE' && !$seance->cloture_validee_at) {
            $seance->update(['cloture_validee_at' => now()]);
        }

        if ($seance->type === 'HP' && !$seance->est_composition) {
            $h = (int) ceil($seance->duree_heures);
            foreach ($seance->options as $opt) {
                $q = MatiereCentreAnnee::firstOrCreate(
                    ['matiere_id' => $seance->matiere_id, 'centre_id' => $opt->centre_id, 'annee_scolaire_id' => $seance->annee_scolaire_id],
                    ['hp_restant' => $seance->matiere->hp_initial, 'tpe_dynamique' => $seance->matiere->tpe_initial]
                );
                if ($seance->heure_scan_professeur) {
                    $q->hp_restant = max(0, $q->hp_restant - $h);
                    $q->save();
                } else {
                    $q->appliquerVasesCommunicants($h);
                }
            }
            if (!$seance->heure_scan_professeur) {
                $this->planifierRattrapage($seance);
            }
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
        $DUREE = 30;

        if ($seance->statut !== 'en_cours') {
            return back()->withErrors(['pause' => 'La séance n\'est pas en cours.']);
        }

        // Une seule pause active à la fois
        if ($seance->heure_fin_pause && now()->lt($seance->heure_fin_pause)) {
            return back()->withErrors(['pause' => 'Une pause est déjà en cours jusqu\'à ' . $seance->heure_fin_pause->format('H:i') . '.']);
        }

        // La pause doit être déclenchée dans la plage horaire de la séance
        if (now()->lt($seance->debut) || now()->gte($seance->fin)) {
            return back()->withErrors(['pause' =>
                'La pause ne peut être déclenchée qu\'entre '
                . $seance->debut->format('H:i') . ' et ' . $seance->fin->format('H:i') . '.'
            ]);
        }

        $fin = now()->addMinutes($DUREE);
        $seance->update([
            'heure_debut_pause'     => now(),
            'heure_fin_pause'       => $fin,
            'durees_pauses_minutes' => ($seance->durees_pauses_minutes ?? 0) + $DUREE,
        ]);
        return back()->with('succes', "Pause de {$DUREE} min déclarée — reprise à " . $fin->format('H:i') . '.');
    }

    // ── Clôture de séance : validation par le professeur ────────────────────
    public function cloturer(Request $request, Seance $seance)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // Seul le professeur de la séance ou l'admin peut valider la clôture.
        // Le responsable de centre n'a pas ce droit : c'est le professeur qui atteste à l'administration.
        if (!$user->estAdmin() && $seance->professeur_id !== $user->id) {
            abort(403);
        }

        if ($seance->statut !== 'terminee') {
            return back()->withErrors(['cloture' => 'La séance doit être terminée avant de valider la clôture.']);
        }
        if ($seance->cloture_validee_at) {
            return back()->withErrors(['cloture' => 'Cette séance a déjà été clôturée.']);
        }

        $rules = [
            'nb_presents'        => 'required|integer|min:0',
            'souhaite_contester' => 'nullable|boolean',
        ];

        // Si le professeur a coché la case de contestation, on ajoute les règles de validation
        if ($request->boolean('souhaite_contester')) {
            $rules['duree_contestee_minutes'] = 'required|integer|min:0';
            $rules['motif']                   = 'required|string|min:10|max:1000';
        }

        $data = $request->validate($rules);

        $seance->update([
            'nb_presents_valide'  => $data['nb_presents'],
            'cloture_validee_at'  => now(),
            'cloture_validee_par' => $user->id,
        ]);

        if ($request->boolean('souhaite_contester')) {
            ContestationHoraire::updateOrCreate(
                ['seance_id' => $seance->id, 'professeur_id' => $user->id],
                [
                    'duree_calculee_minutes'  => $seance->calculerDureeEffective(),
                    'duree_contestee_minutes' => $data['duree_contestee_minutes'],
                    'motif'                   => $data['motif'],
                    'statut'                  => 'en_attente',
                ]
            );
        }

        $msg = 'Clôture validée — ' . $data['nb_presents'] . ' présent(s) confirmé(s).';
        if ($request->boolean('souhaite_contester')) {
            $msg .= ' Votre contestation horaire a également été transmise.';
        }

        return back()->with('succes', $msg);
    }
}
