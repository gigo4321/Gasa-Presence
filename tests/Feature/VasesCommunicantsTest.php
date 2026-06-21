<?php

namespace Tests\Feature;

use App\Models\{MatiereCentreAnnee, Seance};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class VasesCommunicantsTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMinimalData();
    }

    // ── Méthode modèle ────────────────────────────────────────────────────────

    public function test_prof_absent_hp_restant_inchange_tpe_reduit(): void
    {
        $mca = MatiereCentreAnnee::create([
            'matiere_id'        => $this->matiere->id,
            'centre_id'         => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'hp_restant'        => 6,
            'tpe_dynamique'     => 3,
        ]);

        $mca->appliquerVasesCommunicants(3);

        $mca->refresh();
        $this->assertEquals(6, $mca->hp_restant,    'hp_restant ne doit pas changer quand le prof est absent');
        $this->assertEquals(0, $mca->tpe_dynamique, 'tpe_dynamique doit être réduit des heures manquées');
    }

    public function test_vases_communicants_tpe_ne_descend_pas_sous_zero(): void
    {
        $mca = MatiereCentreAnnee::create([
            'matiere_id'        => $this->matiere->id,
            'centre_id'         => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'hp_restant'        => 6,
            'tpe_dynamique'     => 1,
        ]);

        $mca->appliquerVasesCommunicants(3); // 3h perdues > 1h restante

        $mca->refresh();
        $this->assertEquals(6, $mca->hp_restant);
        $this->assertEquals(0, $mca->tpe_dynamique, 'tpe_dynamique ne peut pas être négatif');
    }

    public function test_deux_absences_consécutives_tpe_reste_a_zero(): void
    {
        $mca = MatiereCentreAnnee::create([
            'matiere_id'        => $this->matiere->id,
            'centre_id'         => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'hp_restant'        => 6,
            'tpe_dynamique'     => 3,
        ]);

        $mca->appliquerVasesCommunicants(3); // première absence
        $mca->refresh();
        $mca->appliquerVasesCommunicants(3); // deuxième absence (rattrapage manqué)

        $mca->refresh();
        $this->assertEquals(6, $mca->hp_restant,    'hp_restant reste intact après deux absences');
        $this->assertEquals(0, $mca->tpe_dynamique, 'tpe_dynamique ne descend pas sous 0');
    }

    // ── Via endpoint HTTP /seances/{id}/terminer ──────────────────────────────

    public function test_terminer_sans_scan_prof_reduit_tpe_et_cree_rattrapage(): void
    {
        Carbon::setTestNow('2026-06-21 08:00:00');

        $seance = $this->makeSeance([
            'debut'                  => '2026-06-14 08:00:00', // date passée
            'fin'                    => '2026-06-14 11:00:00',
            'statut'                 => 'en_cours',
            'heure_scan_professeur'  => null, // prof absent
        ]);
        $seance->options()->sync([$this->option->id]);

        $this->actingAs($this->admin)
            ->post(route('seances.terminer', $seance))
            ->assertRedirect();

        $mca = MatiereCentreAnnee::where([
            'matiere_id'        => $this->matiere->id,
            'centre_id'         => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ])->first();

        $this->assertNotNull($mca, 'MatiereCentreAnnee doit être créé');
        $this->assertEquals(6, $mca->hp_restant,    'hp_restant inchangé : prof absent');
        $this->assertEquals(0, $mca->tpe_dynamique, 'tpe_dynamique réduit de 3h');

        // Une séance de rattrapage doit avoir été planifiée
        $rattrapage = Seance::where('type', 'HP')
            ->where('statut', 'planifiee')
            ->where('matiere_id', $this->matiere->id)
            ->whereNot('id', $seance->id)
            ->first();
        $this->assertNotNull($rattrapage, 'Une séance de rattrapage doit être planifiée');
        $this->assertEquals($this->prof->id, $rattrapage->professeur_id);

        Carbon::setTestNow();
    }

    public function test_terminer_avec_scan_prof_decremente_hp_restant(): void
    {
        $seance = $this->makeSeance([
            'debut'                  => '2026-06-14 08:00:00',
            'fin'                    => '2026-06-14 11:00:00',
            'statut'                 => 'en_cours',
            'heure_scan_professeur'  => '2026-06-14 08:02:00',
        ]);
        $seance->options()->sync([$this->option->id]);

        $this->actingAs($this->admin)
            ->post(route('seances.terminer', $seance))
            ->assertRedirect();

        $mca = MatiereCentreAnnee::where([
            'matiere_id'        => $this->matiere->id,
            'centre_id'         => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ])->first();

        $this->assertNotNull($mca);
        $this->assertEquals(3, $mca->hp_restant,    'hp_restant doit passer de 6 à 3 (3h effectuées)');
        $this->assertEquals(3, $mca->tpe_dynamique, 'tpe_dynamique reste inchangé');

        // Aucun rattrapage planifié
        $rattrapage = Seance::where('type', 'HP')
            ->where('statut', 'planifiee')
            ->where('matiere_id', $this->matiere->id)
            ->whereNot('id', $seance->id)
            ->first();
        $this->assertNull($rattrapage, 'Aucun rattrapage ne doit être créé si le prof était présent');
    }

    public function test_terminer_deux_fois_bloque_la_seconde(): void
    {
        $seance = $this->makeSeance([
            'debut'  => '2026-06-14 08:00:00',
            'fin'    => '2026-06-14 11:00:00',
            'statut' => 'terminee', // déjà terminée
        ]);

        $this->actingAs($this->admin)
            ->post(route('seances.terminer', $seance))
            ->assertSessionHasErrors('statut');
    }

    // ── Règle HP avant TPE ────────────────────────────────────────────────────

    public function test_tpe_bloque_si_hp_restant_positif(): void
    {
        // hp_restant = 6 (défaut initial — aucune séance HP terminée)
        MatiereCentreAnnee::create([
            'matiere_id'        => $this->matiere->id,
            'centre_id'         => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'hp_restant'        => 6,
            'tpe_dynamique'     => 3,
        ]);

        $debut = now()->addDay()->setTime(8, 0)->toDateTimeString();

        $this->actingAs($this->admin)
            ->post(route('seances.store'), [
                'matiere_id'   => $this->matiere->id,
                'salle_id'     => $this->salle->id,
                'debut'        => $debut,
                'duree_heures' => 3,
                'type'         => 'TPE',
                'option_ids'   => [$this->option->id],
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_tpe_autorise_quand_hp_restant_zero(): void
    {
        MatiereCentreAnnee::create([
            'matiere_id'        => $this->matiere->id,
            'centre_id'         => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'hp_restant'        => 0, // toutes les HP terminées
            'tpe_dynamique'     => 3,
        ]);

        $debut = now()->addDay()->setTime(8, 0)->toDateTimeString();

        $this->actingAs($this->admin)
            ->post(route('seances.store'), [
                'matiere_id'   => $this->matiere->id,
                'salle_id'     => $this->salle->id,
                'debut'        => $debut,
                'duree_heures' => 3,
                'type'         => 'TPE',
                'option_ids'   => [$this->option->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('seances', [
            'matiere_id' => $this->matiere->id,
            'type'       => 'TPE',
            'statut'     => 'planifiee',
        ]);
    }

    // ── Scénario complet ──────────────────────────────────────────────────────

    /**
     * Matière : 6h HP (2×3h) + 3h TPE
     * - Séance 1 manquée → hp_restant=6, tpe=0, rattrapage planifié
     * - Séance 2 effectuée (avec scan) → hp_restant=3
     * - Rattrapage effectué (avec scan) → hp_restant=0 → TPE débloqué
     */
    public function test_scenario_complet_deux_sessions_dont_une_manquee(): void
    {
        Carbon::setTestNow('2026-06-21 12:00:00');

        // Séance 1 — prof absent
        $s1 = $this->makeSeance([
            'debut'                 => '2026-06-01 08:00:00',
            'fin'                   => '2026-06-01 11:00:00',
            'statut'                => 'en_cours',
            'heure_scan_professeur' => null,
        ]);
        $s1->options()->sync([$this->option->id]);

        $this->actingAs($this->admin)->post(route('seances.terminer', $s1));

        $mca = MatiereCentreAnnee::where('matiere_id', $this->matiere->id)
            ->where('centre_id', $this->centre->id)->first();
        $this->assertEquals(6, $mca->hp_restant,    'Après S1 manquée : hp_restant inchangé');
        $this->assertEquals(0, $mca->tpe_dynamique, 'Après S1 manquée : tpe réduit à 0');

        $rattrapage = Seance::where('type', 'HP')->where('statut', 'planifiee')
            ->where('matiere_id', $this->matiere->id)->whereNot('id', $s1->id)->first();
        $this->assertNotNull($rattrapage, 'Un rattrapage doit être planifié');

        // Séance 2 — prof présent
        $s2 = $this->makeSeance([
            'debut'                 => '2026-06-08 08:00:00',
            'fin'                   => '2026-06-08 11:00:00',
            'statut'                => 'en_cours',
            'heure_scan_professeur' => '2026-06-08 08:01:00',
        ]);
        $s2->options()->sync([$this->option->id]);

        $this->actingAs($this->admin)->post(route('seances.terminer', $s2));

        $mca->refresh();
        $this->assertEquals(3, $mca->hp_restant,    'Après S2 effectuée : hp_restant = 3');
        $this->assertEquals(0, $mca->tpe_dynamique, 'tpe reste à 0');

        // TPE encore bloqué
        $this->actingAs($this->admin)
            ->post(route('seances.store'), [
                'matiere_id'   => $this->matiere->id,
                'salle_id'     => $this->salle->id,
                'debut'        => now()->addDay()->setTime(8, 0)->toDateTimeString(),
                'duree_heures' => 3,
                'type'         => 'TPE',
                'option_ids'   => [$this->option->id],
            ])
            ->assertSessionHasErrors('type');

        // Séance de rattrapage — prof présent
        $rattrapage->update([
            'statut'                => 'en_cours',
            'heure_scan_professeur' => now()->subHour(),
        ]);
        $rattrapage->options()->sync([$this->option->id]);

        $this->actingAs($this->admin)->post(route('seances.terminer', $rattrapage));

        $mca->refresh();
        $this->assertEquals(0, $mca->hp_restant, 'Après rattrapage : hp_restant = 0 → TPE débloqué');

        // TPE maintenant autorisé
        $this->actingAs($this->admin)
            ->post(route('seances.store'), [
                'matiere_id'   => $this->matiere->id,
                'salle_id'     => $this->salle->id,
                'debut'        => now()->addDays(2)->setTime(8, 0)->toDateTimeString(),
                'duree_heures' => 3,
                'type'         => 'TPE',
                'option_ids'   => [$this->option->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('seances', ['type' => 'TPE', 'statut' => 'planifiee']);

        Carbon::setTestNow();
    }
}
