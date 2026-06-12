<?php
namespace App\Http\Controllers;

use App\Models\{Etudiant, Option};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EtudiantController extends Controller
{
    public function index(Request $request, $centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) {
            abort(403);
        }

        $query = Etudiant::with(['option.filiere','option.centre'])
            ->whereHas('option', fn($q) => $q->where('centre_id', $centreId));

        if ($search = $request->get('q')) {
            $query->where(fn($q) => $q
                ->where('nom','like',"%{$search}%")
                ->orWhere('prenom','like',"%{$search}%")
                ->orWhere('matricule','like',"%{$search}%")
            );
        }

        $etudiants = $query->orderBy('nom')->paginate(20)->withQueryString();
        $options   = Option::where('centre_id', $centreId)->with('filiere')->get();

        return view('etudiants.index', compact('etudiants','options','centreId'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'nom'       => 'required|string|max:100',
            'prenom'    => 'required|string|max:100',
            'email'     => 'required|email|unique:etudiants,email',
            'matricule' => 'required|string|unique:etudiants,matricule',
            'option_id' => 'required|exists:options,id',
            'badge_uid' => 'nullable|string|unique:etudiants,badge_uid',
        ]);

        $option = Option::findOrFail($data['option_id']);
        if (!$user->estAdmin() && $option->centre_id != $user->centre_id) abort(403);

        Etudiant::create($data);
        return back()->with('succes', 'Étudiant ajouté avec succès.');
    }

    public function update(Request $request, Etudiant $etudiant)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $etudiant->centre?->id != $user->centre_id) abort(403);

        $data = $request->validate([
            'nom'      => 'required|string|max:100',
            'prenom'   => 'required|string|max:100',
            'statut'   => 'required|in:actif,suspendu,diplome',
            'badge_uid'=> 'nullable|string|unique:etudiants,badge_uid,'.$etudiant->id,
        ]);

        $etudiant->update($data);
        return back()->with('succes', 'Étudiant modifié avec succès.');
    }
}
