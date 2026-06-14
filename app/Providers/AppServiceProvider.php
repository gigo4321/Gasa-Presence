<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{Schema, Blade};

class AppServiceProvider extends ServiceProvider {
    public function boot(): void {
        Schema::defaultStringLength(191);
        Blade::if('admin',     fn()   => auth()->check() && auth()->user()->estAdmin());
        Blade::if('role',      fn($r) => auth()->check() && auth()->user()->role === $r);
        Blade::if('peutgerer', fn()   => auth()->check() && auth()->user()->peutGererCentre());
    }
    public function register(): void {}
}
