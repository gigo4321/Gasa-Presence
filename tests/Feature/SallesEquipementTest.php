<?php

namespace Tests\Feature;

use App\Models\{Centre, Equipement, Salle};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class SallesEquipementTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMinimalData();
    }

    // ── Nommage GASA ────────────────────────────────────────────────────────

    public function test_salle_cree_avec_convention_gasa(): void
    {
        $salle = Salle::create(['nom' => '2E1', 'capacite' => 40, 'type' => 'Salle de cours', 'centre_id' => $this->centre->id]);
        $this->assertEquals('2E1', $salle->nom);
    }

    public function test_meme_nom_dans_deux_centres_differents_autorise(): void
    {
        $autreCentre = Centre::create(['nom' => 'Akpakpa', 'ville' => 'Cotonou']);
        Salle::create(['nom' => '1E1', 'capacite' => 40, 'type' => 'Salle de cours', 'centre_id' => $autreCentre->id]);

        // La salle 1E1 du centre de test a été créée dans seedMinimalData().
        // Une salle 1E1 dans un autre centre est autorisée (contrainte unique sur nom+centre_id).
        $this->assertDatabaseCount('salles', 2);
    }

    public function test_nom_duplique_dans_meme_centre_leve_exception(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // '1E1' dans $this->centre existe déjà (seedMinimalData)
        Salle::create(['nom' => '1E1', 'capacite' => 30, 'type' => 'Salle de cours', 'centre_id' => $this->centre->id]);
    }

    // ── Équipement scanner ──────────────────────────────────────────────────

    public function test_scanner_rfid_peut_etre_associe_a_une_salle(): void
    {
        \App\Models\Equipement::create([
            'nom'           => 'Scanner RFID',
            'type_materiel' => 'Scanner RFID',
            'numero_serie'  => 'RFID-TEST-1E1-01',
            'etat'          => 'bon',
            'quantite'      => 1,
            'salle_id'      => $this->salle->id,
        ]);

        $this->assertDatabaseHas('equipements', [
            'type_materiel' => 'Scanner RFID',
            'salle_id'      => $this->salle->id,
            'etat'          => 'bon',
        ]);
    }

    public function test_salle_expose_ses_equipements(): void
    {
        \App\Models\Equipement::create([
            'nom'           => 'Scanner RFID',
            'type_materiel' => 'Scanner RFID',
            'numero_serie'  => 'RFID-TEST-1E1-01',
            'etat'          => 'bon',
            'quantite'      => 1,
            'salle_id'      => $this->salle->id,
        ]);

        $this->assertCount(1, $this->salle->equipements);
        $this->assertEquals('Scanner RFID', $this->salle->equipements->first()->type_materiel);
    }

    public function test_suppression_salle_supprime_equipements(): void
    {
        \App\Models\Equipement::create([
            'nom'           => 'Tableau blanc',
            'type_materiel' => 'Mobilier',
            'numero_serie'  => 'TB-TEST-01',
            'etat'          => 'bon',
            'quantite'      => 1,
            'salle_id'      => $this->salle->id,
        ]);

        $salleId = $this->salle->id;
        $this->salle->delete();

        $this->assertDatabaseMissing('equipements', ['salle_id' => $salleId]);
    }

    // ── API scan terminal : liste des salles du centre ───────────────────────

    public function test_scan_terminal_liste_salles_du_centre(): void
    {
        $annee = $this->annee; // créée dans seedMinimalData

        $this->actingAs($this->admin)
            ->get(route('scan.index', $this->centre->id))
            ->assertOk()
            ->assertSee('1E1');
    }
}
