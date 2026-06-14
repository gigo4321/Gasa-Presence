<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AnneeScolaire extends Model {
    protected $table = 'annees_scolaires';
    protected $fillable = ['libelle','date_debut','date_fin','active'];
    protected $casts = ['date_debut'=>'date','date_fin'=>'date','active'=>'boolean'];
    public function seances()     { return $this->hasMany(Seance::class); }
    public function options()     { return $this->hasMany(Option::class); }
    public function inscriptions(){ return $this->hasMany(Inscription::class); }
    public static function courante(): ?self { return static::where('active',true)->first(); }
}
