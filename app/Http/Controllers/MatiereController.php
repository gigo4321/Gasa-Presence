<?php
namespace App\Http\Controllers;

use App\Models\{Filiere, MatiereCentre, Centre};
use Illuminate\Support\Facades\Auth;

class MatiereController extends Controller
{
    public function index($centreId)
    {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $centre   = Centre::findOrFail($centreId);

        // Toutes les filières avec options → niveaux → matières + quotas du centre
        $filieres = Filiere::with([
            'formationOptions.niveaux.matieres.quotasCentres' => fn($q) => $q->where('centre_id', $centreId),
        ])->orderBy('nom')->get();

        return view('matieres.index', compact('filieres', 'centre', 'centreId'));
    }
}
