<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Salle extends Model {
    protected $table = 'salles';
    protected $fillable = ['nom','capacite','type','centre_id'];
    public function centre()     { return $this->belongsTo(Centre::class); }
    public function seances()    { return $this->hasMany(Seance::class); }
    public function equipements(){ return $this->hasMany(Equipement::class); }
}
