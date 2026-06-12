<?php
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EtudiantController;
use App\Http\Controllers\SeanceController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\MatiereController;
use App\Http\Controllers\FiliereController;
use App\Http\Controllers\ProfesseurController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout',[LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    // Dashboards
    Route::get('/dashboard/directeur', [DashboardController::class, 'directeur'])->name('dashboard.directeur');
    Route::get('/dashboard/centre/{centreId}', [DashboardController::class, 'centre'])->name('dashboard.centre');

    // ── RÉFÉRENTIEL PÉDAGOGIQUE (Directeur uniquement) ──────────────────────
    // Filières
    Route::get('/filieres', [FiliereController::class, 'index'])->name('filieres.index');
    Route::post('/filieres', [FiliereController::class, 'store'])->name('filieres.store');
    Route::put('/filieres/{filiere}', [FiliereController::class, 'update'])->name('filieres.update');

    // Options d'une filière (SIL, Réseau…)
    Route::post('/filieres/{filiere}/options', [FiliereController::class, 'storeOption'])
         ->name('filieres.options.store');

    // Niveaux d'une option (L1, M2…)
    Route::post('/filiere-options/{filiereOption}/niveaux', [FiliereController::class, 'storeNiveau'])
         ->name('filieres.options.niveaux.store');

    // Matières d'un niveau
    Route::post('/niveaux/{niveau}/matieres', [FiliereController::class, 'storeMatiere'])
         ->name('filieres.niveaux.matieres.store');
    Route::put('/matieres/{matiere}', [FiliereController::class, 'updateMatiere'])
         ->name('filieres.matieres.update');

    // Vue matières par centre (suivi HP/TPE)
    Route::get('/centre/{centreId}/matieres', [MatiereController::class, 'index'])->name('matieres.index');

    // ── GESTION PAR CENTRE ──────────────────────────────────────────────────
    // Étudiants
    Route::get('/centre/{centreId}/etudiants', [EtudiantController::class, 'index'])->name('etudiants.index');
    Route::post('/etudiants', [EtudiantController::class, 'store'])->name('etudiants.store');
    Route::put('/etudiants/{etudiant}', [EtudiantController::class, 'update'])->name('etudiants.update');

    // Professeurs
    Route::get('/centre/{centreId}/professeurs', [ProfesseurController::class, 'index'])->name('professeurs.index');
    Route::post('/professeurs', [ProfesseurController::class, 'store'])->name('professeurs.store');
    Route::put('/professeurs/{professeur}', [ProfesseurController::class, 'update'])->name('professeurs.update');
    Route::post('/professeurs/{professeur}/toggle', [ProfesseurController::class, 'toggle'])->name('professeurs.toggle');

    // Séances
    Route::get('/centre/{centreId}/seances', [SeanceController::class, 'index'])->name('seances.index');
    Route::post('/seances', [SeanceController::class, 'store'])->name('seances.store');
    Route::post('/seances/{seance}/demarrer', [SeanceController::class, 'demarrer'])->name('seances.demarrer');
    Route::post('/seances/{seance}/terminer', [SeanceController::class, 'terminer'])->name('seances.terminer');
    Route::post('/seances/{seance}/pause', [SeanceController::class, 'pause'])->name('seances.pause');

    // Scan
    Route::get('/centre/{centreId}/scan', [ScanController::class, 'index'])->name('scan.index');
    Route::post('/scan/badge', [ScanController::class, 'scanner'])->name('scan.badge');
});
