<?php
namespace App\Http\Controllers;
use App\Models\{Seance, AnneeScolaire, Centre, Filiere, Matiere, MatiereCentreAnnee};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresenceController extends Controller {
    public function index(Request $request, $centreId = null) {
        $user = Auth::user();
        if (!$user->estAdmin()) $centreId = $user->centre_id;
        if ($centreId && !$user->estAdmin() && $user->centre_id != $centreId) abort(403);
        $centres         = $user->estAdmin() ? Centre::all() : collect();
        $anneesScolaires = AnneeScolaire::orderByDesc('date_debut')->get();
        $filieres        = Filiere::with('filiereOptions.niveaux')->orderBy('nom')->get();
        $query = Seance::with(['matiere.niveau.filiereOption.filiere','salle.centre','professeur','options','presences'])->where('statut','terminee');
        if ($centreId) $query->whereHas('salle',fn($q)=>$q->where('centre_id',$centreId));
        if ($aid = $request->get('annee_id'))  $query->where('annee_scolaire_id',$aid);
        if ($dd  = $request->get('date_debut')) $query->whereDate('debut','>=',$dd);
        if ($df  = $request->get('date_fin'))   $query->whereDate('debut','<=',$df);
        if ($fid = $request->get('filiere_id')) $query->whereHas('matiere',fn($q)=>$q->where('filiere_id',$fid));
        if ($mid = $request->get('matiere_id')) $query->where('matiere_id',$mid);
        $seances  = $query->orderByDesc('debut')->paginate(25)->withQueryString();
        $centre   = $centreId ? Centre::find($centreId) : null;
        // Matières pour le filtre (visibles uniquement si une filière est sélectionnée)
        $matieres = $request->get('filiere_id')
            ? Matiere::where('filiere_id', $request->get('filiere_id'))->where('archive', false)->orderBy('nom')->get()
            : collect();
        return view('presences.index', compact('seances','centres','anneesScolaires','filieres','matieres','centre','centreId'));
    }

    public function fiche(Seance $seance)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $seance->salle?->centre_id != $user->centre_id) abort(403);

        $seance->load([
            'matiere.niveau.filiereOption.filiere',
            'salle.centre',
            'professeur',
            'options.filiereOption.filiere',
            'options.niveau',
            'presences' => fn($q) => $q->with(['inscription.etudiant', 'sortiesTemporaires'])->orderBy('heure_entree'),
        ]);

        // ── Stats HP professeur / matière (TPE n'a pas de professeur) ───────
        $profSeancesTerminees = $seance->professeur_id
            ? Seance::where('professeur_id', $seance->professeur_id)
                ->where('matiere_id', $seance->matiere_id)
                ->where('statut', 'terminee')
                ->where('annee_scolaire_id', $seance->annee_scolaire_id)
                ->get()
            : collect();

        $profHpFait    = round($profSeancesTerminees->where('type', 'HP')->sum('duree_heures'), 1);
        $profTpeFait   = 0; // Les TPE ne sont pas comptabilisés sur le professeur
        $profNbSeances = $profSeancesTerminees->where('type', 'HP')->count();

        $hpInitial  = $seance->matiere->hp_initial ?? 0;
        $tpeInitial = $seance->matiere->tpe_initial ?? 0;

        $profHpRestant  = max(0, round($hpInitial - $profHpFait, 1));
        $profTpeRestant = 0; // non pertinent : les TPE ne comptent pas pour le professeur

        // Durée de la séance actuelle incluse
        $dureeActuelle = round($seance->duree_heures, 1);
        $estDerniereHP  = $seance->type === 'HP' && ($profHpFait + $dureeActuelle) >= $hpInitial && $hpInitial > 0;
        $estDerniereTPE = false; // TPE pas comptabilisé sur le professeur

        // Quota centre/matière/année (vases communicants)
        $centreId = $seance->salle->centre_id ?? null;
        $quota    = $centreId ? MatiereCentreAnnee::where('matiere_id', $seance->matiere_id)
            ->where('centre_id', $centreId)
            ->where('annee_scolaire_id', $seance->annee_scolaire_id)
            ->first() : null;

        // ── KPIs présence ────────────────────────────────────────────────────
        $nbPresents     = $seance->presences->where('statut', 'present')->count();
        $nbAbsents      = $seance->presences->where('statut', 'absent')->count();
        $nbInsuffisants = $seance->presences->where('statut', 'presence_insuffisante')->count();
        $totalInscrits  = $seance->options->sum(fn($o) => $o->inscriptions()->where('statut', 'actif')->count());

        $dureeEffectiveMinutes = $seance->calculerDureeEffective();

        return view('presences.fiche', compact(
            'seance', 'nbPresents', 'nbAbsents', 'nbInsuffisants', 'totalInscrits',
            'profSeancesTerminees', 'profHpFait', 'profTpeFait', 'profNbSeances',
            'hpInitial', 'tpeInitial', 'profHpRestant', 'profTpeRestant',
            'quota', 'estDerniereHP', 'estDerniereTPE', 'dureeActuelle',
            'dureeEffectiveMinutes'
        ));
    }

    public function exportPDF(Seance $seance) {
        $user = Auth::user();
        if (!$user->estAdmin() && $seance->salle?->centre_id != $user->centre_id) abort(403);
        $seance->load(['matiere.niveau.filiereOption.filiere','salle.centre','professeur','options.filiereOption','options.niveau','presences.inscription.etudiant','presences.inscription.option.niveau','presences.sortiesTemporaires']);
        $nbPresents    = $seance->presences->where('statut','present')->count();
        $nbAbsents     = $seance->presences->where('statut','absent')->count();
        $nbInsuffisants= $seance->presences->where('statut','presence_insuffisante')->count();
        $totalInscrits = $seance->options->sum(fn($o)=>$o->inscriptions()->where('statut','actif')->count());
        return response(view('presences.fiche_pdf',compact('seance','nbPresents','nbAbsents','nbInsuffisants','totalInscrits'))->render())->header('Content-Type','text/html');
    }

    public function annees() {
        if (!Auth::user()->estAdmin()) abort(403);
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        return view('presences.annees', compact('annees'));
    }

    public function storeAnnee(Request $request) {
        if (!Auth::user()->estAdmin()) abort(403);
        $data = $request->validate(['libelle'=>'required|string|max:20|unique:annees_scolaires,libelle','date_debut'=>'required|date','date_fin'=>'required|date|after:date_debut','active'=>'boolean']);
        if (!empty($data['active'])) AnneeScolaire::where('active',true)->update(['active'=>false]);
        AnneeScolaire::create($data);
        return back()->with('succes',"Année \"{$data['libelle']}\" créée.");
    }

    public function activerAnnee(AnneeScolaire $annee) {
        if (!Auth::user()->estAdmin()) abort(403);
        AnneeScolaire::where('active',true)->update(['active'=>false]);
        $annee->update(['active'=>true]);
        return back()->with('succes',"Année {$annee->libelle} activée.");
    }
}
