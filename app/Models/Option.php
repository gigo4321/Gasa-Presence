<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Option extends Model {
    protected $table = 'options';
    protected $fillable = ['nom','filiere_option_id','niveau_id','centre_id','annee_scolaire_id','responsable_nom'];
    public function filiereOption()  { return $this->belongsTo(FiliereOption::class); }
    public function niveau()         { return $this->belongsTo(Niveau::class); }
    public function centre()         { return $this->belongsTo(Centre::class); }
    public function anneeScolaire()  { return $this->belongsTo(AnneeScolaire::class); }
    public function inscriptions()   { return $this->hasMany(Inscription::class); }
    public function seances()        { return $this->belongsToMany(Seance::class,'option_seance'); }
    public function getNombreActifsAttribute(): int {
        return $this->inscriptions()->where('statut','actif')->count();
    }
}
