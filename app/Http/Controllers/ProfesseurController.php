<?php
namespace App\Http\Controllers;
use App\Models\{User, Centre, Matiere};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash};

class ProfesseurController extends Controller {
    public function index($centreId) {
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);
        $centre      = Centre::findOrFail($centreId);
        $professeurs = User::where('role','ROLE_PROFESSEUR')->where('centre_id',$centreId)->with('matieres')->orderBy('name')->get();
        $matieres    = Matiere::where('archive',false)->orderBy('nom')->get();
        return view('professeurs.index', compact('professeurs','centre','centreId','matieres'));
    }

    public function store(Request $request) {
        $user = Auth::user();
        $data = $request->validate(['name'=>'required|string|max:100','prenom'=>'required|string|max:100','email'=>'required|email|unique:users,email','telephone'=>'required|string|max:20','badge_uid'=>'nullable|string|max:50|unique:users,badge_uid','centre_id'=>'required|exists:centres,id','password'=>'required|string|min:6','matiere_ids'=>'nullable|array','matiere_ids.*'=>'exists:matieres,id']);
        if (!$user->estAdmin() && $data['centre_id'] != $user->centre_id) abort(403);
        $prof = User::create(['name'=>trim($data['name'].' '.$data['prenom']),'email'=>$data['email'],'telephone'=>$data['telephone'],'badge_uid'=>$data['badge_uid']??null,'password'=>Hash::make($data['password']),'role'=>'ROLE_PROFESSEUR','centre_id'=>$data['centre_id'],'email_verified_at'=>now()]);
        if (!empty($data['matiere_ids'])) $prof->matieres()->sync($data['matiere_ids']);
        return back()->with('succes','Professeur '.$prof->name.' ajouté.');
    }

    public function update(Request $request, User $professeur) {
        $user = Auth::user();
        if (!$user->estAdmin() && $professeur->centre_id != $user->centre_id) abort(403);
        $data = $request->validate(['name'=>'required|string|max:100','email'=>'required|email|unique:users,email,'.$professeur->id,'telephone'=>'required|string|max:20','badge_uid'=>'nullable|string|max:50|unique:users,badge_uid,'.$professeur->id,'matiere_ids'=>'nullable|array','matiere_ids.*'=>'exists:matieres,id']);
        $professeur->update($data);
        $professeur->matieres()->sync($data['matiere_ids']??[]);
        return back()->with('succes','Professeur modifié.');
    }

    public function toggle(User $professeur) {
        $user = Auth::user();
        if (!$user->estAdmin() && $professeur->centre_id != $user->centre_id) abort(403);
        $professeur->update(['email_verified_at'=>$professeur->email_verified_at?null:now()]);
        return back()->with('succes', $professeur->fresh()->estActif() ? $professeur->name.' activé.' : $professeur->name.' désactivé.');
    }
}
