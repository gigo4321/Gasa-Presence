<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Salle extends Model {
    protected $table    = 'salles';
    protected $fillable = ['nom', 'capacite', 'type', 'centre_id'];
    public function centre()  { return $this->belongsTo(Centre::class); }
    public function estLabo() { return $this->type === 'laboratoire'; }
}
