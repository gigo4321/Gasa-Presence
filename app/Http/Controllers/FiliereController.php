<?php
namespace App\Http\Controllers;

use App\Models\{Filiere, FiliereOption, Niveau, Matiere};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FiliereController extends Controller
{
    // ── Page principale : liste des filières avec leurs options et niveaux ──
    public function index()
    {
        if (!Auth::user()->estAdmin()) abort(403);

        $filieres = Filiere::with([
            'filiereOptions.niveaux.matieres'
        ])->orderBy('nom')->get();

        return view('filieres.index', compact('filieres'));
    }

    // ── Créer une filière ──────────────────────────────────────────────────
    public function store(Request $request)
    {
        if (!Auth::user()->estAdmin()) abort(403);

        $data = $request->validate([
            'nom'  => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:filieres,code',
        ]);

        Filiere::create($data);
        return back()->with('succes', "Filière \"{$data['nom']}\" créée.");
    }

    // ── Modifier une filière ───────────────────────────────────────────────
    public function update(Request $request, Filiere $filiere)
    {
        if (!Auth::user()->estAdmin()) abort(403);

        $data = $request->validate([
            'nom'  => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:filieres,code,' . $filiere->id,
        ]);

        $filiere->update($data);
        return back()->with('succes', 'Filière modifiée.');
    }

    // ── Créer une option dans une filière (SIL, Réseau…) ──────────────────
    public function storeOption(Request $request, Filiere $filiere)
    {
        if (!Auth::user()->estAdmin()) abort(403);

        $data = $request->validate([
            'nom'  => 'required|string|max:100',
            'code' => 'required|string|max:20',
        ]);

        $filiere->filiereOptions()->create($data);
        return back()->with('succes', "Option \"{$data['nom']}\" ajoutée à {$filiere->nom}.");
    }

    // ── Créer un niveau dans une option (L1, M2…) ─────────────────────────
    public function storeNiveau(Request $request, FiliereOption $filiereOption)
    {
        if (!Auth::user()->estAdmin()) abort(403);

        $data = $request->validate([
            'libelle' => 'required|string|max:30',
            'code'    => 'required|string|max:10',
        ]);

        // Ordre auto : dernier niveau + 1
        $ordre = $filiereOption->niveaux()->max('ordre') + 1;

        $filiereOption->niveaux()->create([
            'libelle' => $data['libelle'],
            'code'    => $data['code'],
            'ordre'   => $ordre,
        ]);

        return back()->with('succes', "Niveau \"{$data['libelle']}\" ajouté.");
    }

    // ── Créer une matière dans un niveau ──────────────────────────────────
    public function storeMatiere(Request $request, Niveau $niveau)
    {
        if (!Auth::user()->estAdmin()) abort(403);

        $data = $request->validate([
            'nom'         => 'required|string|max:150',
            'code'        => 'required|string|max:20|unique:matieres,code',
            'semestre'    => 'required|in:1,2',
            'hp_initial'  => 'required|integer|min:1|max:500',
            'tpe_initial' => 'required|integer|min:0|max:500',
        ]);

        Matiere::create([
            'nom'         => $data['nom'],
            'code'        => $data['code'],
            'semestre'    => $data['semestre'],
            'hp_initial'  => $data['hp_initial'],
            'tpe_initial' => $data['tpe_initial'],
            'filiere_id'  => $niveau->filiereOption->filiere_id,
            'niveau_id'   => $niveau->id,
        ]);

        return back()->with('succes', "Matière \"{$data['nom']}\" ajoutée.");
    }

    // ── Modifier une matière ───────────────────────────────────────────────
    public function updateMatiere(Request $request, Matiere $matiere)
    {
        if (!Auth::user()->estAdmin()) abort(403);

        $data = $request->validate([
            'nom'         => 'required|string|max:150',
            'code'        => 'required|string|max:20|unique:matieres,code,' . $matiere->id,
            'semestre'    => 'required|in:1,2',
            'hp_initial'  => 'required|integer|min:1|max:500',
            'tpe_initial' => 'required|integer|min:0|max:500',
        ]);

        $matiere->update($data);
        return back()->with('succes', 'Matière modifiée.');
    }
}
