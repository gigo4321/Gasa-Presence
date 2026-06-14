<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Etudiant extends Model {
    protected $table = 'etudiants';
    protected $fillable = ['matricule','nom','prenom','email','telephone','badge_uid','date_naissance'];
    protected $casts = ['date_naissance'=>'date'];
    public function inscriptions()     { return $this->hasMany(Inscription::class)->orderByDesc('created_at'); }
    public function inscriptionActive(){ return $this->hasOne(Inscription::class)->where('statut','actif')->latest(); }
    public function inscriptionAnnee(AnneeScolaire $annee) {
        return $this->inscriptions()->where('annee_scolaire_id',$annee->id)->first();
    }
    public function presences() {
        return $this->hasManyThrough(Presence::class, Inscription::class);
    }
}
