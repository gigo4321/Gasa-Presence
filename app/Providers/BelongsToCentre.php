<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToCentre
{
    protected static function bootBelongsToCentre(): void
    {
        static::addGlobalScope('centre_filter', function (Builder $builder) {
            // RG-002: Le Directeur (ROLE_ADMIN) a accès à tout (centre_id est null)
            if (Auth::check() && Auth::user()->role !== 'ROLE_ADMIN') {
                $builder->where('centre_id', Auth::user()->centre_id);
            }
        });
    }
}