<?php
namespace App\Http\Controllers;
use App\Models\{Option, AnneeScolaire, Centre, FiliereOption, Niveau, Inscription};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OptionController extends Controller {
    public function index(Request $request, $centreId) {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);
        $centre = Centre::findOrFail($centreId);
        $annee  = $request->get('annee_id') ? AnneeScolaire::findOrFail($request->get('annee_id')) : AnneeScolaire::courante();
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $options = Option::where('centre_id',$centreId)
            ->when($annee, fn($q) => $q->where('annee_scolaire_id',$annee->id))
            ->with(['filiereOption.filiere','niveau','inscriptions'])
            ->get();
        $filiereOptions = FiliereOption::with('filiere','niveaux')->where('archive',false)->get();
        $anneesScolaires = $annees;
        return view('etudiants.options', compact('options','centre','centreId','annee','annees','filiereOptions','anneesScolaires'));
    }

    public function store(Request $request, $centreId) {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);
        $data = $request->validate([
            'nom'                => 'required|string|max:100',
            'filiere_option_id'  => 'required|exists:filiere_options,id',
            'niveau_id'          => 'required|exists:niveaux,id',
            'annee_scolaire_id'  => 'required|exists:annees_scolaires,id',
        ]);
        Option::create(array_merge($data, ['centre_id'=>$centreId]));
        return back()->with('succes',"Groupe \"{$data['nom']}\" créé.");
    }

    // Reconduire tous les étudiants actifs d'un groupe vers un nouveau groupe
    public function reconduire(Request $request, Option $option) {
        $data = $request->validate([
            'option_cible_id'    => 'required|exists:options,id',
            'annee_scolaire_id'  => 'required|exists:annees_scolaires,id',
        ]);
        $inscriptions = $option->inscriptions()->where('statut','actif')->get();
        $nb = 0;
        foreach ($inscriptions as $insc) {
            if (!Inscription::where('etudiant_id',$insc->etudiant_id)->where('annee_scolaire_id',$data['annee_scolaire_id'])->exists()) {
                Inscription::create(['etudiant_id'=>$insc->etudiant_id,'option_id'=>$data['option_cible_id'],'annee_scolaire_id'=>$data['annee_scolaire_id'],'statut'=>'actif','date_inscription'=>today()]);
                $nb++;
            }
        }
        return back()->with('succes',"{$nb} étudiant(s) reconduit(s).");
    }
}
