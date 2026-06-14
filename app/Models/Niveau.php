<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Niveau extends Model {
    use SoftDeletes;
    protected $table = 'niveaux';
    protected $fillable = ['libelle','code','ordre','filiere_option_id','archive'];
    protected $casts = ['archive'=>'boolean'];
    public function filiereOption() { return $this->belongsTo(FiliereOption::class); }
    public function matieres()      { return $this->hasMany(Matiere::class)->orderBy('semestre')->orderBy('nom'); }
    public function niveauSuivant(): ?self {
        return static::where('filiere_option_id',$this->filiere_option_id)
            ->where('ordre', $this->ordre + 1)->first();
    }
    public function scopeActives($q) { return $q->where('archive',false); }
}
