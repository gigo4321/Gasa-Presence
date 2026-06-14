<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Matiere extends Model {
    use SoftDeletes;
    protected $table = 'matieres';
    protected $fillable = ['nom','code','semestre','hp_initial','tpe_initial','filiere_id','niveau_id','archive'];
    protected $casts = ['archive'=>'boolean'];
    public function filiere()        { return $this->belongsTo(Filiere::class); }
    public function niveau()         { return $this->belongsTo(Niveau::class); }
    public function seances()        { return $this->hasMany(Seance::class); }
    public function professeurs()    { return $this->belongsToMany(User::class,'matiere_professeur','matiere_id','user_id'); }
    public function quotas()         { return $this->hasMany(MatiereCentreAnnee::class); }
    public function getMHTAttribute(): int { return $this->hp_initial + $this->tpe_initial; }
    public function canDelete(): bool { return $this->seances()->count() === 0; }
    public function scopeActives($q) { return $q->where('archive',false); }
}
