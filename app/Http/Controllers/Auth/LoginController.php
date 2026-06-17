<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class LoginController extends Controller {
    public function showLoginForm() {
        if (Auth::check()) return $this->redirectSelonRole(Auth::user());
        return view('auth.login');
    }
    public function login(Request $request) {
        $credentials = $request->validate(['email'=>'required|email','password'=>'required']);
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectSelonRole(Auth::user());
        }
        return back()->withErrors(['email'=>'Identifiants incorrects.'])->onlyInput('email');
    }
    public function logout(Request $request) {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect('/');
    }
    private function redirectSelonRole($user) {
        if ($user->estAdmin()) return redirect()->route('dashboard.directeur');
        if ($user->estProfesseur()) return redirect()->route('seances.index', ['centreId' => $user->centre_id, 'prof_id' => $user->id]);
        return redirect()->route('dashboard.centre', $user->centre_id);
    }
}
