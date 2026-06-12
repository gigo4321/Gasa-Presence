<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Centre extends Model {
    protected $fillable = ['nom', 'ville'];
    public function utilisateurs() { return $this->hasMany(User::class); }
    public function options()      { return $this->hasMany(Option::class); }
    public function salles()       { return $this->hasMany(Salle::class); }
    public function getNombreEtudiantsActifsAttribute(): int {
        return Etudiant::whereHas('option', fn($q) => $q->where('centre_id', $this->id))->where('statut', 'actif')->count();
    }
}
