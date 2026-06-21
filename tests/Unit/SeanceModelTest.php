<?php

namespace Tests\Unit;

use App\Models\Seance;
use Carbon\Carbon;
use Tests\TestCase;

class SeanceModelTest extends TestCase
{
    // ── calculerDureeEffective() ─────────────────────────────────────────────

    public function test_duree_zero_sans_scan_professeur(): void
    {
        $s = new Seance([
            'debut'                      => '2026-06-20 08:00:00',
            'fin'                        => '2026-06-20 11:00:00',
            'heure_scan_professeur'      => null,
            'heure_scan_sortie_professeur' => null,
            'durees_pauses_minutes'      => 0,
            'statut'                     => 'terminee',
        ]);
        $this->assertSame(0, $s->calculerDureeEffective());
    }

    public function test_duree_avec_entree_et_sortie_scannees(): void
    {
        $s = new Seance([
            'debut'                      => '2026-06-20 08:00:00',
            'fin'                        => '2026-06-20 11:00:00',
            'heure_scan_professeur'      => '2026-06-20 07:55:00',
            'heure_scan_sortie_professeur' => '2026-06-20 11:05:00',
            'durees_pauses_minutes'      => 0,
            'statut'                     => 'terminee',
        ]);
        // 07:55 → 11:05 = 190 min
        $this->assertSame(190, $s->calculerDureeEffective());
    }

    public function test_duree_sans_scan_sortie_utilise_fin_planifiee_si_terminee(): void
    {
        $s = new Seance([
            'debut'                      => '2026-06-20 08:00:00',
            'fin'                        => '2026-06-20 11:00:00',
            'heure_scan_professeur'      => '2026-06-20 07:58:00',
            'heure_scan_sortie_professeur' => null,
            'durees_pauses_minutes'      => 0,
            'statut'                     => 'terminee',
        ]);
        // 07:58 → 11:00 = 182 min
        $this->assertSame(182, $s->calculerDureeEffective());
    }

    public function test_duree_soustrait_pauses(): void
    {
        $s = new Seance([
            'debut'                      => '2026-06-20 08:00:00',
            'fin'                        => '2026-06-20 11:00:00',
            'heure_scan_professeur'      => '2026-06-20 08:00:00',
            'heure_scan_sortie_professeur' => '2026-06-20 11:00:00',
            'durees_pauses_minutes'      => 15,
            'statut'                     => 'terminee',
        ]);
        // 180 min - 15 min = 165 min
        $this->assertSame(165, $s->calculerDureeEffective());
    }

    public function test_duree_jamais_negative(): void
    {
        $s = new Seance([
            'debut'                      => '2026-06-20 08:00:00',
            'fin'                        => '2026-06-20 08:05:00',
            'heure_scan_professeur'      => '2026-06-20 08:00:00',
            'heure_scan_sortie_professeur' => '2026-06-20 08:02:00',
            'durees_pauses_minutes'      => 999,
            'statut'                     => 'terminee',
        ]);
        $this->assertSame(0, $s->calculerDureeEffective());
    }

    public function test_duree_seance_en_cours_utilise_maintenant(): void
    {
        Carbon::setTestNow('2026-06-20 10:00:00');

        $s = new Seance([
            'debut'                      => '2026-06-20 08:00:00',
            'fin'                        => '2026-06-20 11:00:00',
            'heure_scan_professeur'      => '2026-06-20 08:00:00',
            'heure_scan_sortie_professeur' => null,
            'durees_pauses_minutes'      => 0,
            'statut'                     => 'en_cours',
        ]);
        // 08:00 → 10:00 (maintenant) = 120 min
        $this->assertSame(120, $s->calculerDureeEffective());

        Carbon::setTestNow();
    }

    // ── Accesseur duree_heures ───────────────────────────────────────────────

    public function test_duree_heures_calculee_depuis_debut_fin(): void
    {
        $s = new Seance([
            'debut' => '2026-06-20 08:00:00',
            'fin'   => '2026-06-20 11:00:00',
        ]);
        $this->assertEqualsWithDelta(3.0, $s->duree_heures, 0.001);
    }
}
