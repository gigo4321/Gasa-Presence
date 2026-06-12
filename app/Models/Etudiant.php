<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Etudiant extends Model {
    protected $table    = 'etudiants';
    protected $fillable = ['matricule', 'nom', 'prenom', 'email', 'badge_uid', 'statut', 'option_id'];
    public function option()    { return $this->belongsTo(Option::class); }
    public function presences() { return $this->hasMany(Presence::class); }
    public function getCentreAttribute() { return $this->option?->centre; }
}
