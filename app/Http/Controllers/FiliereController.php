<?php
namespace App\Http\Controllers;
use App\Models\{Filiere, FiliereOption, Niveau, Matiere};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class FiliereController extends Controller {
    public function index() {
        if (!Auth::user()->estAdmin()) abort(403);
        $filieres = Filiere::with('filiereOptions.niveaux.matieres')->orderBy('nom')->get();

        // Récupérer les matières existantes pour servir de modèles
        $templatesMatiere = Matiere::select('nom', 'code', 'hp_initial', 'tpe_initial')
            ->distinct()
            ->orderBy('nom')
            ->get();

        return view('filieres.index', compact('filieres', 'templatesMatiere'));
    }

    public function store(Request $request) {
        if (!Auth::user()->estAdmin()) abort(403);
        $data = $request->validate(['nom'=>'required|string|max:100','code'=>'required|string|max:20|unique:filieres,code']);
        Filiere::create($data);
        return back()->with('succes',"Filière \"{$data['nom']}\" créée.");
    }

    public function update(Request $request, Filiere $filiere) {
        if (!Auth::user()->estAdmin()) abort(403);
        $filiere->update($request->validate(['nom'=>'required|string|max:100','code'=>'required|string|max:20|unique:filieres,code,'.$filiere->id]));
        return back()->with('succes','Filière renommée.');
    }

    public function archive(Filiere $filiere) {
        if (!Auth::user()->estAdmin()) abort(403);
        $filiere->update(['archive' => !$filiere->archive]);
        return back()->with('succes', $filiere->archive ? 'Filière archivée.' : 'Filière réactivée.');
    }

    public function destroy(Filiere $filiere) {
        if (!Auth::user()->estAdmin()) abort(403);
        if (!$filiere->canDelete()) return back()->withErrors(['delete'=>'Impossible : des matières existent dans cette filière.']);
        $filiere->delete();
        return back()->with('succes','Filière supprimée.');
    }

    // Options
    public function storeOption(Request $request, Filiere $filiere) {
        if (!Auth::user()->estAdmin()) abort(403);
        $data = $request->validate(['nom'=>'required|string|max:100','code'=>'required|string|max:20']);
        $filiere->filiereOptions()->create($data);
        return back()->with('succes',"Option \"{$data['nom']}\" ajoutée.");
    }

    public function updateOption(Request $request, FiliereOption $filiereOption) {
        if (!Auth::user()->estAdmin()) abort(403);
        $filiereOption->update($request->validate(['nom'=>'required|string|max:100','code'=>'required|string|max:20']));
        return back()->with('succes','Option mise à jour.');
    }

    public function archiveOption(FiliereOption $filiereOption) {
        if (!Auth::user()->estAdmin()) abort(403);
        $filiereOption->update(['archive'=>!$filiereOption->archive]);
        return back()->with('succes', $filiereOption->archive ? 'Option archivée.' : 'Option réactivée.');
    }

    // Niveaux
    public function storeNiveau(Request $request, FiliereOption $filiereOption) {
        if (!Auth::user()->estAdmin()) abort(403);
        $data  = $request->validate(['libelle'=>'required|string|max:30','code'=>'required|string|max:10']);
        $ordre = $filiereOption->niveaux()->withTrashed()->max('ordre') + 1;
        $filiereOption->niveaux()->create(array_merge($data, ['ordre'=>$ordre]));
        return back()->with('succes',"Niveau \"{$data['libelle']}\" ajouté.");
    }

    public function updateNiveau(Request $request, Niveau $niveau) {
        if (!Auth::user()->estAdmin()) abort(403);
        $niveau->update($request->validate(['libelle'=>'required|string|max:30','code'=>'required|string|max:10']));
        return back()->with('succes','Niveau mis à jour.');
    }

    public function archiveNiveau(Niveau $niveau) {
        if (!Auth::user()->estAdmin()) abort(403);
        $niveau->update(['archive'=>!$niveau->archive]);
        return back()->with('succes', $niveau->archive ? 'Niveau archivé.' : 'Niveau réactivé.');
    }

    // Matières
    public function storeMatiere(Request $request, Niveau $niveau) {
        if (!Auth::user()->estAdmin()) abort(403);
        $data = $request->validate([
            'nom'        => 'required|string|max:150',
            'code'       => [
                'required',
                'string',
                'max:20',
                Rule::unique('matieres')->where(function ($query) use ($niveau) {
                    return $query->where('filiere_id', $niveau->filiereOption->filiere_id)
                                 ->where('niveau_id', $niveau->id);
                }),
            ],
            'semestre'   => 'required|in:1,2',
            'hp_initial' => 'required|integer|min:1|max:500',
            'tpe_initial'=> 'required|integer|min:0|max:500'
        ]);
        Matiere::create(array_merge($data,['filiere_id'=>$niveau->filiereOption->filiere_id,'niveau_id'=>$niveau->id]));
        return back()->with('succes',"Matière \"{$data['nom']}\" créée.");
    }

    public function updateMatiere(Request $request, Matiere $matiere) {
        if (!Auth::user()->estAdmin()) abort(403);
        $data = $request->validate([
            'nom'        => 'required|string|max:150',
            'code'       => [
                'required',
                'string',
                'max:20',
                Rule::unique('matieres')->where(function ($query) use ($matiere) {
                    return $query->where('filiere_id', $matiere->filiere_id)
                                 ->where('niveau_id', $matiere->niveau_id);
                })->ignore($matiere->id),
            ],
            'semestre'   => 'required|in:1,2',
            'hp_initial' => 'required|integer|min:1|max:500',
            'tpe_initial'=> 'required|integer|min:0|max:500'
        ]);
        $matiere->update($data);
        return back()->with('succes','Matière mise à jour.');
    }

    public function archiveMatiere(Matiere $matiere) {
        if (!Auth::user()->estAdmin()) abort(403);
        $matiere->update(['archive'=>!$matiere->archive]);
        return back()->with('succes', $matiere->archive ? 'Matière archivée.' : 'Matière réactivée.');
    }

    public function destroyMatiere(Matiere $matiere) {
        if (!Auth::user()->estAdmin()) abort(403);
        if (!$matiere->canDelete()) return back()->withErrors(['delete'=>'Impossible : des séances existent pour cette matière.']);
        $matiere->forceDelete();
        return back()->with('succes','Matière supprimée.');
    }
}
