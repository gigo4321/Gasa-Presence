<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmploiDuTemps extends Model
{
    protected $table    = 'emplois_du_temps';
    protected $fillable = [
        'numero', 'centre_id', 'annee_scolaire_id', 'option_id',
        'orientation_label', 'date_debut', 'date_fin',
    ];
    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    public function centre()       { return $this->belongsTo(Centre::class); }
    public function anneeScolaire(){ return $this->belongsTo(AnneeScolaire::class); }
    public function option()       { return $this->belongsTo(Option::class); }
    public function seances()      { return $this->hasMany(Seance::class); }

    public function getPeriodeAttribute(): string
    {
        return 'Du ' . $this->date_debut->format('d/m/Y') . ' au ' . $this->date_fin->format('d/m/Y');
    }
}
