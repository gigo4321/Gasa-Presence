<?php
namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'centre_id', 'telephone', 'badge_uid',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
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

    // ── Vérifications de rôle ──────────────────────────────────────
    public function estAdmin()        { return $this->role === 'ROLE_ADMIN'; }
    public function estResponsable()  { return $this->role === 'ROLE_RESPONSABLE_CENTRE'; }
    public function estSecretaire()   { return $this->role === 'ROLE_SECRETAIRE'; }
    public function estAgent()        { return $this->role === 'ROLE_AGENT'; }
    public function estProfesseur()   { return $this->role === 'ROLE_PROFESSEUR'; }
    public function peutGererCentre() { return $this->estAdmin() || $this->estResponsable(); }
    public function estActif()        { return $this->email_verified_at !== null; }

    public function getRoleLibelleAttribute(): string
    {
        return match ($this->role) {
            'ROLE_ADMIN'              => 'Directeur Général',
            'ROLE_RESPONSABLE_CENTRE' => 'Responsable de Centre',
            'ROLE_SECRETAIRE'         => 'Secrétaire',
            'ROLE_AGENT'              => 'Agent',
            'ROLE_PROFESSEUR'         => 'Professeur',
            default                   => $this->role,
        };
    }
}
