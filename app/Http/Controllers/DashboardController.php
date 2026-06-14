<?php
namespace App\Http\Controllers;
use App\Models\{Centre, Inscription, Seance, Presence, AnneeScolaire};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller {
    public function directeur() {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->estAdmin()) return redirect()->route('dashboard.centre', $user->centre_id);
        $annee   = AnneeScolaire::courante();
        $centres = Centre::all();
        $stats   = [
            'nb_centres'            => $centres->count(),
            'total_etudiants'       => $annee ? Inscription::where('annee_scolaire_id',$annee->id)->where('statut','actif')->count() : 0,
            'seances_aujourd_hui'   => Seance::whereDate('debut',today())->count(),
            'total_interpellations' => 0,
            'annee'                 => $annee,
            'par_filiere'           => [], // Initialisé pour éviter l'erreur
            'seances_recentes'      => Seance::with(['matiere', 'salle.centre'])->latest()->take(5)->get(),
        ];

        $centresStats = [];
        foreach ($centres as $c) {
            $inscrits  = $annee ? Inscription::whereHas('option', fn($q)=>$q->where('centre_id',$c->id)->where('annee_scolaire_id',$annee->id))->where('statut','actif')->count() : 0;
            $seances   = Seance::whereHas('salle',fn($q)=>$q->where('centre_id',$c->id))->whereDate('debut',today())->count();
            $enCours   = Seance::whereHas('salle',fn($q)=>$q->where('centre_id',$c->id))->where('statut','en_cours')->count();
            $interp    = 0;
            $stats['total_interpellations'] += $interp;
            $centresStats[] = [
                'centre' => $c,
                'nb_etudiants' => $inscrits, // Corrigé pour correspondre à la vue
                'seances_aujourd_hui' => $seances,
                'en_cours' => $enCours,
                'interpellations' => $interp,
                'taux_assiduite' => 100
            ];
        }
        return view('dashboard.directeur', compact('centres','stats','centresStats','annee'));
    }

    public function centre(Request $request, string $centreId) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $centre  = Centre::findOrFail($centreId);

        // Récupérer toutes les années pour le dropdown
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        // Gérer l'année sélectionnée ou l'année courante par défaut
        $anneeId = $request->get('annee_id');
        $annee = $anneeId ? AnneeScolaire::find($anneeId) : AnneeScolaire::courante();

        $nbInscrits = $annee ? Inscription::whereHas('option', fn($q)=>$q->where('centre_id',$centreId)->where('annee_scolaire_id',$annee->id))->where('statut','actif')->count() : 0;

        $seancesAujourdhui = Seance::whereHas('salle',fn($q)=>$q->where('centre_id',$centreId))->whereDate('debut',today())->with(['matiere','salle','professeur'])->orderBy('debut')->get();
        $seancesEnCours    = $seancesAujourdhui->where('statut','en_cours')->count();
        return view('dashboard.centre', compact('centre','nbInscrits','seancesAujourdhui','seancesEnCours','centreId','annee', 'annees'));
    }
}
