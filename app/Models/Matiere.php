<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Matiere extends Model
{
    protected $table    = 'matieres';
    protected $fillable = ['nom', 'code', 'semestre', 'hp_initial', 'tpe_initial', 'filiere_id', 'niveau_id'];

    public function filiere()       { return $this->belongsTo(Filiere::class); }
    public function niveau()        { return $this->belongsTo(Niveau::class); }
    public function seances()       { return $this->hasMany(Seance::class); }
    public function quotasCentres() { return $this->hasMany(MatiereCentre::class); }

    public function quotaPourCentre($centreId)
    {
        return $this->quotasCentres()->where('centre_id', $centreId)->first();
    }

    public function getMHTAttribute(): int
    {
        return $this->hp_initial + $this->tpe_initial;
    }
}
