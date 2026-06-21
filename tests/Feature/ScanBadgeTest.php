<?php

namespace Tests\Feature;

use App\Models\{Inscription, Presence, Seance, SortieTemporaire};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class ScanBadgeTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMinimalData();
    }

    private function scan(string $badgeUid, string $mode, ?int $salleId = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)
            ->postJson(route('scan.badge'), [
                'badge_uid' => $badgeUid,
                'salle_id'  => $salleId ?? $this->salle->id,
                'mode'      => $mode,
            ]);
    }

    // ── Badge inconnu ────────────────────────────────────────────────────────

    public function test_badge_inconnu_refuse(): void
    {
        $this->scan('BADGE-INCONNU-XYZ', 'entree')
            ->assertOk()
            ->assertJsonPath('statut', 'badge_inconnu')
            ->assertJsonPath('autorise', false)
            ->assertJsonPath('couleur', 'rouge');
    }

    // ── Professeur : entrée ──────────────────────────────────────────────────

    public function test_prof_entree_enregistre_scan_et_passe_en_cours(): void
    {
        Carbon::setTestNow('2026-06-20 08:05:00');

        $seance = $this->makeSeance([
            'debut'  => '2026-06-20 08:00:00',
            'fin'    => '2026-06-20 11:00:00',
            'statut' => 'planifiee',
        ]);

        $this->scan($this->prof->badge_uid, 'entree')
            ->assertOk()
            ->assertJsonPath('statut', 'entree_ok')
            ->assertJsonPath('autorise', true);

        $seance->refresh();
        $this->assertEquals('en_cours', $seance->statut);
        $this->assertNotNull($seance->heure_scan_professeur);

        Carbon::setTestNow();
    }

    public function test_prof_entree_refuse_sans_seance_hp(): void
    {
        $this->scan($this->prof->badge_uid, 'entree')
            ->assertOk()
            ->assertJsonPath('statut', 'aucun_cours')
            ->assertJsonPath('autorise', false);
    }

    public function test_prof_deuxieme_scan_entree_ne_reecrit_pas_heure(): void
    {
        Carbon::setTestNow('2026-06-20 08:00:00');

        $seance = $this->makeSeance([
            'debut'                  => '2026-06-20 08:00:00',
            'fin'                    => '2026-06-20 11:00:00',
            'statut'                 => 'en_cours',
            'heure_scan_professeur'  => '2026-06-20 07:55:00',
        ]);

        $this->scan($this->prof->badge_uid, 'entree')->assertOk();

        $seance->refresh();
        // L'heure originale du premier scan doit être préservée
        $this->assertEquals('07:55', $seance->heure_scan_professeur->format('H:i'));

        Carbon::setTestNow();
    }

    // ── Professeur : sortie ──────────────────────────────────────────────────

    public function test_prof_sortie_enregistre_heure_scan_sortie(): void
    {
        Carbon::setTestNow('2026-06-20 11:05:00');

        $seance = $this->makeSeance([
            'debut'                  => '2026-06-20 08:00:00',
            'fin'                    => '2026-06-20 11:00:00',
            'statut'                 => 'terminee',
            'heure_scan_professeur'  => '2026-06-20 07:58:00',
        ]);

        $this->scan($this->prof->badge_uid, 'sortie')
            ->assertOk()
            ->assertJsonPath('statut', 'sortie_ok')
            ->assertJsonPath('autorise', true);

        $seance->refresh();
        $this->assertNotNull($seance->heure_scan_sortie_professeur);

        Carbon::setTestNow();
    }

    public function test_prof_sortie_acceptee_jusqu_a_4h_apres_fin(): void
    {
        // Séance terminée il y a 3h30 → dans la fenêtre de 4h
        Carbon::setTestNow('2026-06-20 14:30:00');

        $seance = $this->makeSeance([
            'debut'                 => '2026-06-20 08:00:00',
            'fin'                   => '2026-06-20 11:00:00',
            'statut'                => 'terminee',
            'heure_scan_professeur' => '2026-06-20 08:00:00',
        ]);

        $this->scan($this->prof->badge_uid, 'sortie')
            ->assertOk()
            ->assertJsonPath('autorise', true);

        $seance->refresh();
        $this->assertNotNull($seance->heure_scan_sortie_professeur);

        Carbon::setTestNow();
    }

    public function test_prof_sortie_sans_seance_recente_ne_cree_pas_enregistrement(): void
    {
        // Séance terminée il y a 5h → hors fenêtre, la sortie est quand même autorisée
        // mais aucune séance à mettre à jour
        Carbon::setTestNow('2026-06-20 16:00:00');

        $seance = $this->makeSeance([
            'debut'                 => '2026-06-20 08:00:00',
            'fin'                   => '2026-06-20 11:00:00',
            'statut'                => 'terminee',
            'heure_scan_professeur' => '2026-06-20 08:00:00',
        ]);

        $this->scan($this->prof->badge_uid, 'sortie')
            ->assertOk()
            ->assertJsonPath('statut', 'sortie_ok'); // sortie staff toujours autorisée

        $seance->refresh();
        // Hors fenêtre → heure_scan_sortie non mise à jour
        $this->assertNull($seance->heure_scan_sortie_professeur);

        Carbon::setTestNow();
    }

    // ── Étudiant : entrée ────────────────────────────────────────────────────

    public function test_etudiant_entree_dans_la_fenetre_cree_presence(): void
    {
        Carbon::setTestNow('2026-06-20 08:10:00');

        [$etudiant, $inscription] = $this->makeEtudiantInscrit();
        $seance = $this->makeSeance([
            'debut'  => '2026-06-20 08:00:00',
            'fin'    => '2026-06-20 11:00:00',
            'statut' => 'en_cours',
        ]);
        $seance->options()->attach($this->option->id);

        $this->scan($etudiant->badge_uid, 'entree')
            ->assertOk()
            ->assertJsonPath('statut', 'entree_ok')
            ->assertJsonPath('autorise', true);

        $this->assertDatabaseHas('presences', [
            'seance_id'      => $seance->id,
            'inscription_id' => $inscription->id,
            'statut'         => 'present',
        ]);

        Carbon::setTestNow();
    }

    public function test_etudiant_entree_retard_superieur_15min_refuse(): void
    {
        Carbon::setTestNow('2026-06-20 08:20:00'); // +20 min après début

        [$etudiant] = $this->makeEtudiantInscrit();
        $seance = $this->makeSeance([
            'debut'  => '2026-06-20 08:00:00',
            'fin'    => '2026-06-20 11:00:00',
            'statut' => 'en_cours',
        ]);
        $seance->options()->attach($this->option->id);

        $this->scan($etudiant->badge_uid, 'entree')
            ->assertOk()
            ->assertJsonPath('statut', 'retard_bloque')
            ->assertJsonPath('autorise', false)
            ->assertJsonPath('couleur', 'rouge');

        Carbon::setTestNow();
    }

    public function test_etudiant_sans_inscription_active_refuse(): void
    {
        $etudiant = \App\Models\Etudiant::create([
            'matricule' => 'TEST-002', 'nom' => 'SANS', 'prenom' => 'Inscription',
            'email' => 'sans.insc@test.bj', 'badge_uid' => 'BADGE-NO-INSC',
        ]);

        $this->scan('BADGE-NO-INSC', 'entree')
            ->assertOk()
            ->assertJsonPath('statut', 'non_inscrit')
            ->assertJsonPath('autorise', false);
    }

    // ── Étudiant : sortie temporaire ─────────────────────────────────────────

    public function test_sortie_avant_fin_cree_sortie_temporaire(): void
    {
        // Séance se termine dans 30 min → sortie temporaire
        Carbon::setTestNow('2026-06-20 10:30:00');

        [$etudiant, $inscription] = $this->makeEtudiantInscrit();
        $seance = $this->makeSeance([
            'debut'  => '2026-06-20 08:00:00',
            'fin'    => '2026-06-20 11:00:00',
            'statut' => 'en_cours',
        ]);
        $seance->options()->attach($this->option->id);

        // Créer une présence existante
        Presence::create([
            'seance_id'      => $seance->id,
            'inscription_id' => $inscription->id,
            'statut'         => 'present',
            'heure_entree'   => '2026-06-20 08:00:00',
        ]);

        $this->scan($etudiant->badge_uid, 'sortie')
            ->assertOk()
            ->assertJsonPath('statut', 'sortie_temporaire');

        $this->assertDatabaseCount('sorties_temporaires', 1);

        Carbon::setTestNow();
    }

    // ── Étudiant : ré-entrée ─────────────────────────────────────────────────

    public function test_reentree_dans_15min_acceptee(): void
    {
        Carbon::setTestNow('2026-06-20 09:10:00'); // sorti à 09:00, tente de rentrer à 09:10 = 10 min

        [$etudiant, $inscription] = $this->makeEtudiantInscrit();
        $seance = $this->makeSeance([
            'debut'  => '2026-06-20 08:00:00',
            'fin'    => '2026-06-20 11:00:00',
            'statut' => 'en_cours',
        ]);
        $seance->options()->attach($this->option->id);

        $presence = Presence::create([
            'seance_id'      => $seance->id,
            'inscription_id' => $inscription->id,
            'statut'         => 'present',
            'heure_entree'   => '2026-06-20 08:00:00',
        ]);
        SortieTemporaire::create([
            'presence_id'  => $presence->id,
            'heure_sortie' => '2026-06-20 09:00:00',
        ]);

        $this->scan($etudiant->badge_uid, 'entree')
            ->assertOk()
            ->assertJsonPath('statut', 'rentree_ok')
            ->assertJsonPath('autorise', true);

        Carbon::setTestNow();
    }

    public function test_reentree_apres_15min_refusee(): void
    {
        Carbon::setTestNow('2026-06-20 09:20:00'); // sorti à 09:00, tente de rentrer à 09:20 = 20 min

        [$etudiant, $inscription] = $this->makeEtudiantInscrit();
        $seance = $this->makeSeance([
            'debut'  => '2026-06-20 08:00:00',
            'fin'    => '2026-06-20 11:00:00',
            'statut' => 'en_cours',
        ]);
        $seance->options()->attach($this->option->id);

        $presence = Presence::create([
            'seance_id'      => $seance->id,
            'inscription_id' => $inscription->id,
            'statut'         => 'present',
            'heure_entree'   => '2026-06-20 08:00:00',
        ]);
        SortieTemporaire::create([
            'presence_id'  => $presence->id,
            'heure_sortie' => '2026-06-20 09:00:00',
        ]);

        $this->scan($etudiant->badge_uid, 'entree')
            ->assertOk()
            ->assertJsonPath('statut', 'rentree_refusee')
            ->assertJsonPath('autorise', false);

        Carbon::setTestNow();
    }

    // ── Blocage pause professeur ─────────────────────────────────────────────

    public function test_entree_etudiant_bloquee_pendant_pause(): void
    {
        // 08:05 : dans la fenêtre de 15 min → pas de retard
        // mais la pause est active jusqu'à 08:30 → bloqué par pause
        Carbon::setTestNow('2026-06-20 08:05:00');

        [$etudiant] = $this->makeEtudiantInscrit();
        $seance = $this->makeSeance([
            'debut'           => '2026-06-20 08:00:00',
            'fin'             => '2026-06-20 11:00:00',
            'statut'          => 'en_cours',
            'heure_fin_pause' => '2026-06-20 08:30:00',
        ]);
        $seance->options()->attach($this->option->id);

        $this->scan($etudiant->badge_uid, 'entree')
            ->assertOk()
            ->assertJsonPath('statut', 'pause_prof')
            ->assertJsonPath('couleur', 'orange');

        Carbon::setTestNow();
    }
}
