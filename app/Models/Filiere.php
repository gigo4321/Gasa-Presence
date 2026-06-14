<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Filiere extends Model {
    use SoftDeletes;
    protected $fillable = ['nom','code','archive'];
    protected $casts = ['archive'=>'boolean'];
    public function filiereOptions() { return $this->hasMany(FiliereOption::class)->orderBy('nom'); }
    public function matieres()       { return $this->hasMany(Matiere::class); }
    public function scopeActives($q) { return $q->where('archive',false); }
    public function canDelete(): bool { return $this->matieres()->withTrashed()->count() === 0; }
}
