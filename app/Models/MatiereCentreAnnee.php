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
        // hp_restant reste inchangé : les heures manquées seront déduites lors du rattrapage
        // Les heures perdues réduisent le quota TPE disponible en compensation
        $this->tpe_dynamique = max(0, $this->tpe_dynamique - $heures);
        $this->save();
    }
}
