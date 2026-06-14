<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Equipement extends Model {
    protected $table = 'equipements';
    protected $fillable = ['nom', 'type_materiel', 'numero_serie', 'etat', 'quantite', 'salle_id'];

    public function salle() { return $this->belongsTo(Salle::class); }

    public function etatLibelle(): string {
        return match($this->etat) {
            'bon'            => 'Bon état',
            'defectueux'     => 'Défectueux',
            'hors_service'   => 'Hors service',
            'en_maintenance' => 'En maintenance',
            default          => $this->etat,
        };
    }

    public function etatCouleur(): string {
        return match($this->etat) {
            'bon'            => '#e8f5e9',
            'defectueux'     => '#fff3e0',
            'hors_service'   => '#ffebee',
            'en_maintenance' => '#e3f2fd',
            default          => '#f5f5f5',
        };
    }

    public function etatTexte(): string {
        return match($this->etat) {
            'bon'            => '#2e7d32',
            'defectueux'     => '#e65100',
            'hors_service'   => '#c62828',
            'en_maintenance' => '#1565c0',
            default          => '#616161',
        };
    }
}
