<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MatiereCentreAnnee extends Model {
    protected $table = 'matiere_centre_annee';
    protected $fillable = ['matiere_id','centre_id','annee_scolaire_id','hp_restant','tpe_dynamique'];
    public function matiere()       { return $this->belongsTo(Matiere::class); }
    public function centre()        { return $this->belongsTo(Centre::class); }
    public function anneeScolaire() { return $this->belongsTo(AnneeScolaire::class); }
    public function appliquerVasesCommunicants(int $heures): void {
        // Le prof doit rattraper ses heures
        $this->hp_restant    = $this->hp_restant + $heures;

        // On déduit ces heures du quota de TPE (le temps de pratique est sacrifié)
        // On s'assure de ne pas descendre en dessous de zéro
        $this->tpe_dynamique = max(0, $this->tpe_dynamique - $heures);
        $this->save();
    }
}
