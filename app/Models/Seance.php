<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Seance extends Model {
    protected $table = 'seances';
    protected $fillable = ['matiere_id','salle_id','professeur_id','annee_scolaire_id','debut','fin','type','statut','is_inter_centre','est_composition'];
    protected $casts = ['debut'=>'datetime','fin'=>'datetime','is_inter_centre'=>'boolean','est_composition'=>'boolean','heure_fin_pause'=>'datetime','heure_scan_professeur'=>'datetime'];
    public function matiere()       { return $this->belongsTo(Matiere::class); }
    public function salle()         { return $this->belongsTo(Salle::class); }
    public function professeur()    { return $this->belongsTo(User::class,'professeur_id'); }
    public function anneeScolaire() { return $this->belongsTo(AnneeScolaire::class); }
    public function options()       { return $this->belongsToMany(Option::class,'option_seance'); }
    public function presences()     { return $this->hasMany(Presence::class); }
    public function getDureeHeuresAttribute(): float { return $this->debut->diffInMinutes($this->fin) / 60; }
}
