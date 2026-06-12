<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MatiereCentre extends Model {
    protected $table    = 'matiere_centre';
    protected $fillable = ['matiere_id', 'centre_id', 'hp_restant', 'tpe_dynamique'];
    public function matiere() { return $this->belongsTo(Matiere::class); }
    public function centre()  { return $this->belongsTo(Centre::class); }
    public function appliquerVasesCommunicants(int $heures): void {
        $this->hp_restant    = $this->hp_restant + $heures;
        $this->tpe_dynamique = max(0, $this->tpe_dynamique - $heures);
        $this->save();
    }
}
