<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Option extends Model {
    protected $table    = 'options';
    protected $fillable = ['nom', 'niveau', 'filiere_id', 'centre_id'];
    public function filiere()   { return $this->belongsTo(Filiere::class); }
    public function centre()    { return $this->belongsTo(Centre::class); }
    public function etudiants() { return $this->hasMany(Etudiant::class); }
    public function getNombreActifsAttribute(): int { return $this->etudiants()->where('statut','actif')->count(); }
}
