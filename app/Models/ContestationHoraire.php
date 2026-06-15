<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ContestationHoraire extends Model {
    protected $table = 'contestations_horaires';
    protected $fillable = [
        'seance_id', 'professeur_id',
        'duree_calculee_minutes', 'duree_contestee_minutes',
        'motif', 'statut', 'admin_note',
    ];
    public function seance()     { return $this->belongsTo(Seance::class); }
    public function professeur() { return $this->belongsTo(User::class, 'professeur_id'); }
}
