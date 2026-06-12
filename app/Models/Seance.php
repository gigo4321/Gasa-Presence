<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Seance extends Model {
    protected $table    = 'seances';
    protected $fillable = ['matiere_id','salle_id','professeur_id','debut','fin','type','statut','is_inter_centre'];
    protected $casts    = ['debut'=>'datetime','fin'=>'datetime','is_inter_centre'=>'boolean'];
    public function matiere()    { return $this->belongsTo(Matiere::class); }
    public function salle()      { return $this->belongsTo(Salle::class); }
    public function professeur() { return $this->belongsTo(User::class,'professeur_id'); }
    public function options()    { return $this->belongsToMany(Option::class,'option_seance'); }
    public function presences()  { return $this->hasMany(Presence::class); }
    public function getDureeHeuresAttribute(): float { return $this->debut->diffInMinutes($this->fin) / 60; }
    public function getCentreAttribute() { return $this->salle?->centre; }
}
