<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Correction longueur de clé pour MySQL < 5.7.7
        Schema::defaultStringLength(191);

        // Directives Blade pour la gestion des rôles (RG-002 et Matrice des Rôles)
        Blade::if('role', function ($role) {
            return auth()->check() && auth()->user()->role === $role;
        });

        Blade::if('admin', function () {
            return auth()->check() && auth()->user()->role === 'ROLE_ADMIN';
        });

        // Helper pour afficher les photos du dossier images
        Blade::directive('photo', function ($path) {
            return "<?php echo asset('images/' . $path); ?>";
        });
    }

    public function register(): void
    {
        //
    }
}
