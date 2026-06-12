<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Niveau extends Model
{
    protected $table    = 'niveaux';
    protected $fillable = ['libelle', 'code', 'ordre', 'filiere_option_id'];

    public function filiereOption() { return $this->belongsTo(FiliereOption::class); }
    public function matieres()      { return $this->hasMany(Matiere::class)->orderBy('semestre')->orderBy('nom'); }
}
