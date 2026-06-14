<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Presence extends Model {
    protected $table = 'presences';
    protected $fillable = ['seance_id','inscription_id','heure_entree','heure_sortie_definitive','statut'];
    protected $casts = ['heure_entree'=>'datetime','heure_sortie_definitive'=>'datetime'];
    public function seance()             { return $this->belongsTo(Seance::class); }
    public function inscription()        { return $this->belongsTo(Inscription::class); }
    public function sortiesTemporaires() { return $this->hasMany(SortieTemporaire::class); }
}
