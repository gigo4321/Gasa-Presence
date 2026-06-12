<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Filiere extends Model
{
    protected $table    = 'filieres';
    protected $fillable = ['nom', 'code'];

    public function filiereOptions() { return $this->hasMany(FiliereOption::class)->orderBy('nom'); }
    public function matieres()       { return $this->hasMany(Matiere::class); }

    // Tous les niveaux de cette filière (via ses options)
    public function niveaux()
    {
        return Niveau::whereHas('filiereOption', fn($q) => $q->where('filiere_id', $this->id));
    }
}
