<?php
namespace App\Http\Controllers;
use App\Models\{Filiere, Centre, AnneeScolaire};
use Illuminate\Support\Facades\Auth;
class MatiereController extends Controller {
    public function index($centreId) {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);
        $centre   = Centre::findOrFail($centreId);
        $annee    = AnneeScolaire::courante();
        $filieres = Filiere::with([
            'filiereOptions' => fn($q) => $q->with([
                'niveaux' => fn($q2) => $q2->with([
                    'matieres' => fn($q3) => $q3->with([
                        'quotas' => fn($q4) => $q4->where('centre_id',$centreId)->when($annee, fn($q5)=>$q5->where('annee_scolaire_id',$annee->id))
                    ])->orderBy('semestre')->orderBy('nom')
                ])->orderBy('ordre')
            ])->orderBy('nom')
        ])->orderBy('nom')->get();
        return view('matieres.index', compact('filieres','centre','centreId','annee'));
    }
}
