<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SortieTemporaire extends Model {
    protected $table    = 'sorties_temporaires';
    protected $fillable = ['presence_id','heure_sortie','heure_rentree'];
    protected $casts    = ['heure_sortie'=>'datetime','heure_rentree'=>'datetime'];
    public function presence() { return $this->belongsTo(Presence::class); }
    public function getDureeMinutesAttribute(): ?int {
        if (!$this->heure_rentree) return null;
        return $this->heure_sortie->diffInMinutes($this->heure_rentree);
    }
    public function estRentreeRefusee(): bool {
        if (!$this->heure_rentree) return now()->diffInMinutes($this->heure_sortie) > 15;
        return $this->getDureeMinutesAttribute() > 15;
    }
}
