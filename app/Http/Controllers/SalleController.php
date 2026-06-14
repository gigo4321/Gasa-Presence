<?php
namespace App\Http\Controllers;

use App\Models\{Salle, Equipement, Centre};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalleController extends Controller
{
    public function index(Request $request, $centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $centre = Centre::findOrFail($centreId);
        $salles = Salle::where('centre_id', $centreId)
            ->withCount('equipements')
            ->with('equipements')
            ->orderBy('nom')
            ->get();

        return view('salles.index', compact('centre', 'salles', 'centreId'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'centre_id' => 'required|exists:centres,id',
            'nom'       => 'required|string|max:80',
            'type'      => 'required|string|max:80',
            'capacite'  => 'required|integer|min:1',
        ]);

        if (!$user->estAdmin() && $user->centre_id != $data['centre_id']) abort(403);

        $exists = Salle::where('nom', $data['nom'])->where('centre_id', $data['centre_id'])->exists();
        if ($exists) {
            return back()->withErrors(['nom' => "Une salle avec ce nom existe déjà dans ce centre."])->withInput();
        }

        Salle::create($data);
        return back()->with('succes', "Salle « {$data['nom']} » créée.");
    }

    public function update(Request $request, Salle $salle)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $salle->centre_id) abort(403);

        $data = $request->validate([
            'nom'      => 'required|string|max:80',
            'type'     => 'required|string|max:80',
            'capacite' => 'required|integer|min:1',
        ]);

        $exists = Salle::where('nom', $data['nom'])
            ->where('centre_id', $salle->centre_id)
            ->where('id', '!=', $salle->id)
            ->exists();
        if ($exists) {
            return back()->withErrors(['nom' => "Une autre salle porte déjà ce nom dans ce centre."])->withInput();
        }

        $salle->update($data);
        return back()->with('succes', "Salle « {$salle->nom} » mise à jour.");
    }

    public function destroy(Salle $salle)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $salle->centre_id) abort(403);

        if ($salle->seances()->exists()) {
            return back()->withErrors(['salle' => "Impossible de supprimer : des séances utilisent cette salle."]);
        }

        $nom = $salle->nom;
        $salle->delete();
        return back()->with('succes', "Salle « {$nom} » supprimée.");
    }

    // ── Équipements ────────────────────────────────────────────────────────────

    public function storeEquipement(Request $request, Salle $salle)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $salle->centre_id) abort(403);

        $data = $request->validate([
            'nom'           => 'required|string|max:150',
            'type_materiel' => 'nullable|string|max:100',
            'numero_serie'  => 'nullable|string|max:100',
            'etat'          => 'required|in:bon,defectueux,hors_service,en_maintenance',
            'quantite'      => 'required|integer|min:1',
        ]);

        $salle->equipements()->create($data);
        return back()->with('succes', "Équipement « {$data['nom']} » ajouté à {$salle->nom}.");
    }

    public function updateEquipement(Request $request, Equipement $equipement)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $equipement->salle->centre_id) abort(403);

        $data = $request->validate([
            'nom'           => 'required|string|max:150',
            'type_materiel' => 'nullable|string|max:100',
            'numero_serie'  => 'nullable|string|max:100',
            'etat'          => 'required|in:bon,defectueux,hors_service,en_maintenance',
            'quantite'      => 'required|integer|min:1',
        ]);

        $equipement->update($data);
        return back()->with('succes', "Équipement « {$equipement->nom} » mis à jour.");
    }

    public function destroyEquipement(Equipement $equipement)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $equipement->salle->centre_id) abort(403);

        $nom = $equipement->nom;
        $equipement->delete();
        return back()->with('succes', "Équipement « {$nom} » supprimé.");
    }
}
