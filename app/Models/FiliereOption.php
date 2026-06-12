<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FiliereOption extends Model
{
    protected $table    = 'filiere_options';
    protected $fillable = ['nom', 'code', 'filiere_id'];

    public function filiere()  { return $this->belongsTo(Filiere::class); }
    public function niveaux()  { return $this->hasMany(Niveau::class)->orderBy('ordre'); }
    public function matieres() { return $this->hasManyThrough(Matiere::class, Niveau::class); }
}
