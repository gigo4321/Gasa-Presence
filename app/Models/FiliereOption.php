<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class FiliereOption extends Model {
    use SoftDeletes;
    protected $table = 'filiere_options';
    protected $fillable = ['nom','code','filiere_id','archive'];
    protected $casts = ['archive'=>'boolean'];
    public function filiere() { return $this->belongsTo(Filiere::class); }
    public function niveaux() { return $this->hasMany(Niveau::class)->orderBy('ordre'); }
    public function scopeActives($q) { return $q->where('archive',false); }
}
