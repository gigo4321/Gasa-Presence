<?php
namespace App\Http\Controllers;
use App\Models\{Etudiant, Inscription, Option, AnneeScolaire, Centre, Niveau};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EtudiantController extends Controller
{
    // Liste des étudiants du centre pour l'année active
    public function index(Request $request, $centreId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $annee  = $request->get('annee_id')
            ? AnneeScolaire::findOrFail($request->get('annee_id'))
            : AnneeScolaire::courante();

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $centre = Centre::findOrFail($centreId);

        $query = Inscription::with(['etudiant','option.filiereOption.filiere','option.niveau','anneeScolaire'])
            ->whereHas('option', fn($q) => $q->where('centre_id',$centreId)
                ->when($annee, fn($q2) => $q2->where('annee_scolaire_id',$annee->id)));

        if ($search = $request->get('q')) {
            $query->whereHas('etudiant', fn($q) => $q
                ->where('nom','like',"%{$search}%")
                ->orWhere('prenom','like',"%{$search}%")
                ->orWhere('matricule','like',"%{$search}%")
            );
        }

        if ($statut   = $request->get('statut'))    $query->where('statut', $statut);
        if ($optionId = $request->get('option_id')) $query->where('option_id', $optionId);

        $inscriptions  = $query->orderBy('created_at','desc')->paginate(20)->withQueryString();
        $options       = $annee ? Option::where('centre_id',$centreId)->where('annee_scolaire_id',$annee->id)->with('filiereOption','niveau')->get() : collect();
        $optionActive  = $optionId ? $options->firstWhere('id', $optionId) : null;

        return view('etudiants.index', compact('inscriptions','options','optionActive','centre','centreId','annee','annees'));
    }

    // Créer profil + inscription en même temps
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data = $request->validate([
            'matricule'       => 'required|string|unique:etudiants,matricule',
            'nom'             => 'required|string|max:100',
            'prenom'          => 'required|string|max:100',
            'email'           => 'required|email|unique:etudiants,email',
            'telephone'       => 'nullable|string|max:20',
            'badge_uid'       => 'nullable|string|unique:etudiants,badge_uid',
            'date_naissance'  => 'nullable|date',
            'option_id'       => 'required|exists:options,id',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
        ]);

        $option = Option::findOrFail($data['option_id']);
        if (!$user->estAdmin() && $option->centre_id != $user->centre_id) abort(403);

        $etudiant = Etudiant::create([
            'matricule'      => strtoupper($data['matricule']),
            'nom'            => strtoupper($data['nom']),
            'prenom'         => ucfirst(strtolower($data['prenom'])),
            'email'          => $data['email'],
            'telephone'      => $data['telephone'] ?? null,
            'badge_uid'      => $data['badge_uid'] ?? null,
            'date_naissance' => $data['date_naissance'] ?? null,
        ]);

        Inscription::create([
            'etudiant_id'      => $etudiant->id,
            'option_id'        => $data['option_id'],
            'annee_scolaire_id'=> $data['annee_scolaire_id'],
            'statut'           => 'actif',
            'date_inscription' => today(),
        ]);

        return back()->with('succes', "Étudiant {$etudiant->nom} {$etudiant->prenom} inscrit avec succès.");
    }

    // Modifier le profil permanent de l'étudiant
    public function update(Request $request, Etudiant $etudiant)
    {
        $user = Auth::user();
        $data = $request->validate([
            'nom'           => 'required|string|max:100',
            'prenom'        => 'required|string|max:100',
            'email'         => 'required|email|unique:etudiants,email,'.$etudiant->id,
            'telephone'     => 'nullable|string|max:20',
            'badge_uid'     => 'nullable|string|unique:etudiants,badge_uid,'.$etudiant->id,
            'date_naissance'=> 'nullable|date',
        ]);
        $etudiant->update($data);
        return back()->with('succes','Profil mis à jour.');
    }

    // Changer le statut d'une inscription
    public function updateInscription(Request $request, Inscription $inscription)
    {
        $data = $request->validate(['statut'=>'required|in:actif,suspendu,diplome,abandonne']);
        $inscription->update($data);
        return back()->with('succes','Statut mis à jour.');
    }

    // Réinscrire un étudiant pour l'année suivante (monte de niveau auto)
    public function reinscrire(Request $request, Inscription $inscription)
    {
        $user = Auth::user();
        $data = $request->validate([
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
            'option_id'         => 'required|exists:options,id',
            'notes'             => 'nullable|string',
        ]);

        // Vérifier qu'il n'est pas déjà inscrit cette année
        $dejaInscrit = Inscription::where('etudiant_id', $inscription->etudiant_id)
            ->where('annee_scolaire_id', $data['annee_scolaire_id'])->exists();

        if ($dejaInscrit) {
            return back()->withErrors(['reinscription'=>'Cet étudiant est déjà inscrit pour cette année scolaire.']);
        }

        Inscription::create([
            'etudiant_id'       => $inscription->etudiant_id,
            'option_id'         => $data['option_id'],
            'annee_scolaire_id' => $data['annee_scolaire_id'],
            'statut'            => 'actif',
            'date_inscription'  => today(),
            'notes'             => $data['notes'] ?? null,
        ]);

        return back()->with('succes', 'Réinscription effectuée avec succès.');
    }

    // Import CSV
    public function import(Request $request, $centreId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);
        $request->validate(['fichier'=>'required|file|mimes:csv,txt|max:2048','option_id'=>'required|exists:options,id','annee_scolaire_id'=>'required|exists:annees_scolaires,id']);

        $option = Option::findOrFail($request->option_id);
        if (!$user->estAdmin() && $option->centre_id != $user->centre_id) abort(403);

        $lignes   = array_map('str_getcsv', file($request->file('fichier')->getRealPath()));
        $entetes  = array_map('trim', array_shift($lignes));
        $importes = 0; $erreurs = [];

        foreach ($lignes as $i => $ligne) {
            if (count($ligne) < 4) continue;
            $row       = array_combine($entetes, $ligne);
            $matricule = trim($row['matricule'] ?? '');
            $nom       = trim($row['nom']       ?? '');
            $prenom    = trim($row['prenom']    ?? '');
            $email     = trim($row['email']     ?? '');
            $badge     = trim($row['badge_uid'] ?? '') ?: null;
            $tel       = trim($row['telephone'] ?? '') ?: null;

            if (!$matricule || !$nom || !$email) { $erreurs[] = "Ligne ".($i+2)." : champs obligatoires manquants."; continue; }

            // Créer ou trouver l'étudiant par matricule
            $etudiant = Etudiant::firstOrCreate(
                ['matricule' => strtoupper($matricule)],
                ['nom'=>strtoupper($nom),'prenom'=>ucfirst(strtolower($prenom)),'email'=>$email,'telephone'=>$tel,'badge_uid'=>$badge]
            );

            // Créer l'inscription si elle n'existe pas déjà
            $existe = Inscription::where('etudiant_id',$etudiant->id)->where('annee_scolaire_id',$request->annee_scolaire_id)->exists();
            if ($existe) { $erreurs[] = "Ligne ".($i+2)." : {$matricule} déjà inscrit."; continue; }

            Inscription::create(['etudiant_id'=>$etudiant->id,'option_id'=>$option->id,'annee_scolaire_id'=>$request->annee_scolaire_id,'statut'=>'actif','date_inscription'=>today()]);
            $importes++;
        }

        $msg = "{$importes} inscription(s) importée(s).";
        if ($erreurs) $msg .= ' '.count($erreurs).' ignorée(s).';
        return back()->with('succes',$msg)->with('import_erreurs',$erreurs);
    }

    // Modèle CSV
    public function modeleCSV() {
        $csv = "matricule,nom,prenom,email,telephone,badge_uid\nGASA-001,AHOUANSOU,Kossivi,k.ahouansou@gasa.bj,+229 97000001,ETU-001\n";
        return response($csv,200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="modele_etudiants.csv"']);
    }

    // Reconduire toute une promotion (tous les actifs d'une option → option suivante)
    public function reconduirePromotion(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'option_source_id'  => 'required|exists:options,id',
            'option_cible_id'   => 'required|exists:options,id',
            'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
        ]);

        $inscriptions = Inscription::where('option_id',$data['option_source_id'])->where('statut','actif')->get();
        $reconduits = 0;
        foreach ($inscriptions as $insc) {
            $existe = Inscription::where('etudiant_id',$insc->etudiant_id)->where('annee_scolaire_id',$data['annee_scolaire_id'])->exists();
            if (!$existe) {
                Inscription::create(['etudiant_id'=>$insc->etudiant_id,'option_id'=>$data['option_cible_id'],'annee_scolaire_id'=>$data['annee_scolaire_id'],'statut'=>'actif','date_inscription'=>today()]);
                $reconduits++;
            }
        }
        return back()->with('succes',"{$reconduits} étudiant(s) reconduit(s) vers la nouvelle option.");
    }
}
