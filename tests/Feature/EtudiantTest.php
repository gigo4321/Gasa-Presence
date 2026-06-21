<?php

namespace Tests\Feature;

use App\Models\{Etudiant, Inscription};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class EtudiantTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMinimalData();
    }

    // ── Modèle : date_naissance ──────────────────────────────────────────────

    public function test_date_naissance_est_castee_en_carbon(): void
    {
        $e = Etudiant::create([
            'matricule'      => 'TEST-001',
            'nom'            => 'ADJOVI',
            'prenom'         => 'Rosine',
            'email'          => 'adjovi@test.bj',
            'date_naissance' => '2003-04-12',
        ]);

        $fetched = Etudiant::find($e->id);
        $this->assertInstanceOf(Carbon::class, $fetched->date_naissance);
        $this->assertEquals('12/04/2003', $fetched->date_naissance->format('d/m/Y'));
    }

    public function test_date_naissance_peut_etre_null(): void
    {
        $e = Etudiant::create([
            'matricule' => 'TEST-002',
            'nom'       => 'BELLO',
            'prenom'    => 'Kouamé',
            'email'     => 'bello@test.bj',
        ]);

        $this->assertNull(Etudiant::find($e->id)->date_naissance);
    }

    // ── Contrôleur : liste des étudiants ─────────────────────────────────────

    public function test_index_etudiant_accessible_par_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('etudiants.index', ['centreId' => $this->centre->id]))
            ->assertOk();
    }

    public function test_index_etudiant_accessible_par_responsable_du_centre(): void
    {
        $resp = \App\Models\User::factory()->create([
            'role'      => 'ROLE_RESPONSABLE_CENTRE',
            'centre_id' => $this->centre->id,
        ]);

        $this->actingAs($resp)
            ->get(route('etudiants.index', ['centreId' => $this->centre->id]))
            ->assertOk();
    }

    public function test_inscription_statut_actif_par_defaut(): void
    {
        [$etudiant, $inscription] = $this->makeEtudiantInscrit();

        $this->assertEquals('actif', $inscription->statut);
        $this->assertDatabaseHas('inscriptions', [
            'etudiant_id' => $etudiant->id,
            'statut'      => 'actif',
        ]);
    }

    public function test_etudiant_inscrit_visible_dans_la_liste(): void
    {
        $this->makeEtudiantInscrit();

        $this->actingAs($this->admin)
            ->get(route('etudiants.index', ['centreId' => $this->centre->id]))
            ->assertOk()
            ->assertSee('DUPONT');
    }

    // ── Création d'un étudiant via formulaire ────────────────────────────────

    public function test_creation_etudiant_avec_date_naissance(): void
    {
        $resp = \App\Models\User::factory()->create([
            'role'      => 'ROLE_RESPONSABLE_CENTRE',
            'centre_id' => $this->centre->id,
        ]);

        $this->actingAs($resp)->post(route('etudiants.store'), [
            'matricule'        => 'TEST-NEW-001',
            'nom'              => 'CHABI',
            'prenom'           => 'Farida',
            'email'            => 'chabi.farida@test.bj',
            'date_naissance'   => '2002-11-05',
            'option_id'        => $this->option->id,
            'annee_scolaire_id'=> $this->annee->id,
        ]);

        // assertDatabaseHas ne passe pas par les casts Eloquent — on vérifie via le modèle
        $this->assertDatabaseHas('etudiants', ['matricule' => 'TEST-NEW-001']);
        $etudiant = Etudiant::where('matricule', 'TEST-NEW-001')->first();
        $this->assertNotNull($etudiant->date_naissance);
        $this->assertEquals('2002-11-05', $etudiant->date_naissance->format('Y-m-d'));
    }

    // ── Statistiques sur la page étudiants ───────────────────────────────────

    public function test_page_etudiants_naffiche_pas_carte_diplomes(): void
    {
        $this->actingAs($this->admin)
            ->get(route('etudiants.index', ['centreId' => $this->centre->id]))
            ->assertOk()
            ->assertDontSee('Diplômés');  // carte stat supprimée
    }

    public function test_page_etudiants_naffiche_pas_option_filtre_diplome(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('etudiants.index', ['centreId' => $this->centre->id]))
            ->assertOk()
            ->getContent();

        // L'option <option value="diplome"> ne doit plus exister dans le filtre
        $this->assertStringNotContainsString('>Diplômés<', $html);
    }
}
