<?php
// app/Models/User.php

namespace App\Models;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'grade',
        'email',
        'password',
        'role',
        'centre_id',
        'telephone',
        'badge_uid',
        'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Relations ─────────────────────────────────────────────────
    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    public function matieres()
    {
        return $this->belongsToMany(Matiere::class, 'matiere_professeur', 'user_id', 'matiere_id');
    }

    // ── Vérifications de rôle ─────────────────────────────────────
    public function estAdmin()
    {
        return $this->role === 'ROLE_ADMIN';
    }

    public function estResponsable(): bool
    {
        return $this->role === 'ROLE_RESPONSABLE_CENTRE';
    }

    public function estSecretaire(): bool
    {
        return $this->role === 'ROLE_SECRETAIRE';
    }

    public function estAgent(): bool
    {
        return $this->role === 'ROLE_AGENT';
    }

    public function estProfesseur(): bool
    {
        return $this->role === 'ROLE_PROFESSEUR';
    }

    public function estActif(): bool
    {
        return (bool) $this->email_verified_at;
    }

    // Peut créer/modifier des ressources dans son centre
    public function peutGererCentre(): bool
    {
        return $this->estAdmin() || $this->estResponsable();
    }

    // ── Accesseur : libellé lisible du rôle ───────────────────────
    public function getRoleLibelleAttribute(): string
    {
        return match ($this->role) {
            'ROLE_ADMIN'              => 'Directeur Général',
            'ROLE_RESPONSABLE_CENTRE' => 'Responsable de Centre',
            'ROLE_SECRETAIRE'         => 'Secrétaire',
            'ROLE_AGENT'              => 'Agent',
            'ROLE_PROFESSEUR'         => 'Enseignant / Professeur',
            default                   => $this->role,
        };
    }
}
