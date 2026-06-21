<?php

namespace Tests\Feature;

use App\Models\{Seance, User};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class ScanSeanceCouranteTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMinimalData();
    }

    // ── Aucune séance ────────────────────────────────────────────────────────

    public function test_retourne_null_si_aucune_seance(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('scan.seance-courante', $this->salle->id))
            ->assertOk()
            ->assertJson(['seance' => null, 'salle' => '1E1']);
    }

    // ── Séance active ────────────────────────────────────────────────────────

    public function test_retourne_seance_en_cours(): void
    {
        Carbon::setTestNow('2026-06-20 09:00:00');

        $this->makeSeance([
            'debut'  => '2026-06-20 08:00:00',
            'fin'    => '2026-06-20 11:00:00',
            'statut' => 'en_cours',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('scan.seance-courante', $this->salle->id))
            ->assertOk();

        $response->assertJsonPath('seance.statut', 'en_cours');
        $response->assertJsonPath('seance.type', 'HP');
        $response->assertJsonPath('seance.salle_nom', '1E1');

        Carbon::setTestNow();
    }

    public function test_retourne_seance_planifiee_dans_lheure(): void
    {
        Carbon::setTestNow('2026-06-20 07:30:00');

        $this->makeSeance([
            'debut'  => '2026-06-20 08:00:00',
            'fin'    => '2026-06-20 11:00:00',
            'statut' => 'planifiee',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('scan.seance-courante', $this->salle->id))
            ->assertOk()
            ->assertJsonPath('seance.statut', 'planifiee');

        Carbon::setTestNow();
    }

    // ── Fallback séance terminée ─────────────────────────────────────────────

    public function test_retourne_seance_terminee_recente_moins_de_4h(): void
    {
        Carbon::setTestNow('2026-06-20 13:00:00');

        // Séance terminée il y a 2h
        $this->makeSeance([
            'debut'  => '2026-06-20 08:00:00',
            'fin'    => '2026-06-20 11:00:00',
            'statut' => 'terminee',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('scan.seance-courante', $this->salle->id))
            ->assertOk()
            ->assertJsonPath('seance.statut', 'terminee');

        Carbon::setTestNow();
    }

    public function test_retourne_null_si_seance_terminee_trop_ancienne(): void
    {
        Carbon::setTestNow('2026-06-20 17:00:00');

        // Séance terminée il y a 6h → hors fenêtre de 4h
        $this->makeSeance([
            'debut'  => '2026-06-20 08:00:00',
            'fin'    => '2026-06-20 11:00:00',
            'statut' => 'terminee',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('scan.seance-courante', $this->salle->id))
            ->assertOk()
            ->assertJson(['seance' => null]);

        Carbon::setTestNow();
    }

    // ── Données de la réponse ────────────────────────────────────────────────

    public function test_reponse_contient_heures_de_scan_professeur(): void
    {
        Carbon::setTestNow('2026-06-20 13:00:00');

        $this->makeSeance([
            'debut'                       => '2026-06-20 08:00:00',
            'fin'                         => '2026-06-20 11:00:00',
            'statut'                      => 'terminee',
            'heure_scan_professeur'       => '2026-06-20 07:55:00',
            'heure_scan_sortie_professeur'=> '2026-06-20 11:03:00',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('scan.seance-courante', $this->salle->id))
            ->assertOk()
            ->assertJsonPath('seance.heure_scan_entree', '07:55')
            ->assertJsonPath('seance.heure_scan_sortie', '11:03');

        Carbon::setTestNow();
    }

    // ── Contrôle d'accès ─────────────────────────────────────────────────────

    public function test_responsable_meme_centre_autorise(): void
    {
        $resp = User::factory()->create([
            'role'      => 'ROLE_RESPONSABLE_CENTRE',
            'centre_id' => $this->centre->id,
        ]);

        $this->actingAs($resp)
            ->getJson(route('scan.seance-courante', $this->salle->id))
            ->assertOk();
    }

    public function test_responsable_autre_centre_refuse(): void
    {
        $autreCentre = \App\Models\Centre::create(['nom' => 'Autre Centre', 'ville' => 'Cotonou']);
        $resp = User::factory()->create([
            'role'      => 'ROLE_RESPONSABLE_CENTRE',
            'centre_id' => $autreCentre->id,
        ]);

        $this->actingAs($resp)
            ->getJson(route('scan.seance-courante', $this->salle->id))
            ->assertForbidden();
    }
}
