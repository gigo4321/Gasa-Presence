<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\{DashboardController,EtudiantController,OptionController,ProfesseurController,SeanceController,ScanController,MatiereController,FiliereController,PresenceController,SalleController,PlanningController};
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));
Route::get('/login',  [LoginController::class,'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class,'login'])->name('login.post');
Route::post('/logout',[LoginController::class,'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    // ── Dashboards ──────────────────────────────────────────────────────────
    Route::get('/dashboard/directeur',          [DashboardController::class,'directeur'])->name('dashboard.directeur');
    Route::get('/dashboard/centre/{centreId}',  [DashboardController::class,'centre'])->name('dashboard.centre');

    // ── Référentiel (Directeur) ─────────────────────────────────────────────
    Route::get('/filieres',                                          [FiliereController::class,'index'])->name('filieres.index');
    Route::post('/filieres',                                         [FiliereController::class,'store'])->name('filieres.store');
    Route::put('/filieres/{filiere}',                                [FiliereController::class,'update'])->name('filieres.update');
    Route::post('/filieres/{filiere}/archive',                       [FiliereController::class,'archive'])->name('filieres.archive');
    Route::delete('/filieres/{filiere}',                             [FiliereController::class,'destroy'])->name('filieres.destroy');

    Route::post('/filieres/{filiere}/options',                       [FiliereController::class,'storeOption'])->name('filieres.options.store');
    Route::put('/filiere-options/{filiereOption}',                   [FiliereController::class,'updateOption'])->name('filieres.options.update');
    Route::post('/filiere-options/{filiereOption}/archive',          [FiliereController::class,'archiveOption'])->name('filieres.options.archive');

    Route::post('/filiere-options/{filiereOption}/niveaux',          [FiliereController::class,'storeNiveau'])->name('filieres.niveaux.store');
    Route::put('/niveaux/{niveau}',                                  [FiliereController::class,'updateNiveau'])->name('filieres.niveaux.update');
    Route::post('/niveaux/{niveau}/archive',                         [FiliereController::class,'archiveNiveau'])->name('filieres.niveaux.archive');

    Route::post('/niveaux/{niveau}/matieres',                        [FiliereController::class,'storeMatiere'])->name('filieres.matieres.store');
    Route::put('/matieres/{matiere}',                                [FiliereController::class,'updateMatiere'])->name('filieres.matieres.update');
    Route::post('/matieres/{matiere}/archive',                       [FiliereController::class,'archiveMatiere'])->name('filieres.matieres.archive');
    Route::delete('/matieres/{matiere}',                             [FiliereController::class,'destroyMatiere'])->name('filieres.matieres.destroy');

    // ── Matières par centre ──────────────────────────────────────────────────
    Route::get('/centre/{centreId}/matieres',   [MatiereController::class,'index'])->name('matieres.index');

    // ── Groupes (Options) par centre ────────────────────────────────────────
    Route::get('/centre/{centreId}/groupes',    [OptionController::class,'index'])->name('options.index');
    Route::post('/centre/{centreId}/groupes',   [OptionController::class,'store'])->name('options.store');
    Route::post('/options/{option}/reconduire', [OptionController::class,'reconduire'])->name('options.reconduire');

    // ── Étudiants ───────────────────────────────────────────────────────────
    Route::get('/centre/{centreId}/etudiants',           [EtudiantController::class,'index'])->name('etudiants.index');
    Route::post('/etudiants',                            [EtudiantController::class,'store'])->name('etudiants.store');
    Route::put('/etudiants/{etudiant}',                  [EtudiantController::class,'update'])->name('etudiants.update');
    Route::put('/inscriptions/{inscription}',            [EtudiantController::class,'updateInscription'])->name('inscriptions.update');
    Route::post('/inscriptions/{inscription}/reinscrire',[EtudiantController::class,'reinscrire'])->name('inscriptions.reinscrire');
    Route::post('/centre/{centreId}/etudiants/import',   [EtudiantController::class,'import'])->name('etudiants.import');
    Route::get('/etudiants/modele-csv',                  [EtudiantController::class,'modeleCSV'])->name('etudiants.modele');

    // ── Professeurs ─────────────────────────────────────────────────────────
    Route::get('/centre/{centreId}/professeurs',          [ProfesseurController::class,'index'])->name('professeurs.index');
    Route::post('/professeurs',                           [ProfesseurController::class,'store'])->name('professeurs.store');
    Route::put('/professeurs/{professeur}',               [ProfesseurController::class,'update'])->name('professeurs.update');
    Route::post('/professeurs/{professeur}/toggle',       [ProfesseurController::class,'toggle'])->name('professeurs.toggle');

    // ── Salles & Équipements ────────────────────────────────────────────────
    Route::get('/centre/{centreId}/salles',                  [SalleController::class,'index'])->name('salles.index');
    Route::post('/centre/{centreId}/salles',                 [SalleController::class,'store'])->name('salles.store');
    Route::put('/salles/{salle}',                            [SalleController::class,'update'])->name('salles.update');
    Route::delete('/salles/{salle}',                         [SalleController::class,'destroy'])->name('salles.destroy');

    Route::post('/salles/{salle}/equipements',               [SalleController::class,'storeEquipement'])->name('salles.equipements.store');
    Route::put('/equipements/{equipement}',                  [SalleController::class,'updateEquipement'])->name('equipements.update');
    Route::delete('/equipements/{equipement}',               [SalleController::class,'destroyEquipement'])->name('equipements.destroy');

    // ── Planning (génération automatique) ───────────────────────────────────
    Route::get('/centre/{centreId}/planning',             [PlanningController::class,'apercu'])->name('planning.apercu');
    Route::post('/centre/{centreId}/planning/generer',              [PlanningController::class,'generer'])->name('planning.generer');
    Route::post('/centre/{centreId}/planning/generer-mi-semestre', [PlanningController::class,'genererMiSemestre'])->name('planning.generer-mi-semestre');

    // ── Séances ─────────────────────────────────────────────────────────────
    Route::get('/centre/{centreId}/seances',              [SeanceController::class,'index'])->name('seances.index');
    Route::post('/seances',                               [SeanceController::class,'store'])->name('seances.store');
    Route::post('/seances/{seance}/demarrer',             [SeanceController::class,'demarrer'])->name('seances.demarrer');
    Route::post('/seances/{seance}/terminer',             [SeanceController::class,'terminer'])->name('seances.terminer');
    Route::post('/seances/{seance}/pause',                [SeanceController::class,'pause'])->name('seances.pause');

    // ── Scan ─────────────────────────────────────────────────────────────────
    Route::get('/centre/{centreId}/scan',                 [ScanController::class,'index'])->name('scan.index');
    Route::get('/centre/{centreId}/scan/etudiants',       [ScanController::class,'etudiants'])->name('scan.etudiants');
    Route::get('/salles/{salleId}/seance-courante',       [ScanController::class,'seanceCourante'])->name('scan.seance-courante');
    Route::post('/scan/badge',                            [ScanController::class,'scanner'])->name('scan.badge');

    // ── Présences ────────────────────────────────────────────────────────────
    Route::get('/presences',                              [PresenceController::class,'index'])->name('presences.index');
    Route::get('/centre/{centreId}/presences',            [PresenceController::class,'index'])->name('presences.centre');
    Route::get('/presences/seance/{seance}',              [PresenceController::class,'fiche'])->name('presences.fiche');
    Route::get('/presences/seance/{seance}/pdf',          [PresenceController::class,'exportPDF'])->name('presences.export');
    Route::get('/presences/annees',                       [PresenceController::class,'annees'])->name('presences.annees');
    Route::post('/presences/annees',                      [PresenceController::class,'storeAnnee'])->name('presences.annees.store');
    Route::post('/presences/annees/{annee}/activer',      [PresenceController::class,'activerAnnee'])->name('presences.annees.activer');
});
