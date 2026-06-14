<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Inscription extends Model {
    protected $table = 'inscriptions';
    protected $fillable = ['etudiant_id','option_id','annee_scolaire_id','statut','date_inscription','notes'];
    protected $casts = ['date_inscription'=>'date'];
    public function etudiant()      { return $this->belongsTo(Etudiant::class); }
    public function option()        { return $this->belongsTo(Option::class); }
    public function anneeScolaire() { return $this->belongsTo(AnneeScolaire::class); }
    public function presences()     { return $this->hasMany(Presence::class); }
    public function tauxAssiduite(): int {
        $total = $this->presences()->count();
        if ($total === 0) return 100;
        $presents = $this->presences()->where('statut','present')->count();
        return round($presents / $total * 100);
    }
}
