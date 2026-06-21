<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Seance extends Model {
    protected $table = 'seances';
    protected $fillable = ['matiere_id','salle_id','professeur_id','annee_scolaire_id','emploi_du_temps_id','debut','fin','type','statut','is_inter_centre','est_composition','heure_scan_professeur','heure_scan_sortie_professeur','heure_debut_pause','heure_fin_pause','durees_pauses_minutes','nb_presents_valide','cloture_validee_at','cloture_validee_par'];
    protected $casts = ['debut'=>'datetime','fin'=>'datetime','is_inter_centre'=>'boolean','est_composition'=>'boolean','heure_scan_professeur'=>'datetime','heure_scan_sortie_professeur'=>'datetime','heure_debut_pause'=>'datetime','heure_fin_pause'=>'datetime','cloture_validee_at'=>'datetime'];
    public function matiere()       { return $this->belongsTo(Matiere::class); }
    public function salle()         { return $this->belongsTo(Salle::class); }
    public function professeur()    { return $this->belongsTo(User::class,'professeur_id'); }
    public function anneeScolaire() { return $this->belongsTo(AnneeScolaire::class); }
    public function options()       { return $this->belongsToMany(Option::class,'option_seance'); }
    public function presences()     { return $this->hasMany(Presence::class); }
    public function getDureeHeuresAttribute(): float { return $this->debut->diffInMinutes($this->fin) / 60; }

    /**
     * Durée effective = premier scan entrée → dernier scan sortie professeur.
     * La clôture est un acte administratif distinct et n'est pas prise en compte.
     */
    public function calculerDureeEffective(): int
    {
        if (!$this->heure_scan_professeur) return 0;

        if ($this->heure_scan_sortie_professeur) {
            $fin = $this->heure_scan_sortie_professeur;
        } elseif ($this->statut === 'terminee') {
            $fin = $this->fin;
        } else {
            $fin = now();
        }

        $dureeTotale = (int) $this->heure_scan_professeur->diffInMinutes($fin);
        return max(0, $dureeTotale - ($this->durees_pauses_minutes ?? 0));
    }
}
