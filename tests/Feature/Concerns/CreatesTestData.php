<?php

namespace Tests\Feature\Concerns;

use App\Models\{AnneeScolaire, Centre, Etudiant, Inscription, Matiere, Option, Salle, Seance, User};
use Illuminate\Support\Facades\DB;

/**
 * Helpers pour créer le jeu de données minimal nécessaire aux tests.
 * Utilisable dans n'importe quel TestCase avec RefreshDatabase.
 */
trait CreatesTestData
{
    protected Centre       $centre;
    protected Salle        $salle;
    protected User         $admin;
    protected User         $prof;
    protected AnneeScolaire $annee;
    protected Matiere      $matiere;
    protected Option       $option;

    protected function seedMinimalData(): void
    {
        $now = now();

        $this->centre = Centre::create(['nom' => 'Centre Test', 'ville' => 'Cotonou']);
        $this->salle  = Salle::create(['nom' => '1E1', 'capacite' => 40, 'type' => 'Salle de cours', 'centre_id' => $this->centre->id]);
        $this->annee  = AnneeScolaire::create(['libelle' => '2025-2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-07-31', 'active' => true]);

        $this->admin = User::factory()->create(['role' => 'ROLE_ADMIN', 'centre_id' => null]);
        $this->prof  = User::factory()->create([
            'role'      => 'ROLE_PROFESSEUR',
            'centre_id' => $this->centre->id,
            'badge_uid' => 'BADGE-PROF-TEST',
        ]);

        $filiereId = DB::table('filieres')->insertGetId([
            'nom' => 'Génie Électrique', 'code' => 'GE', 'archive' => false, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $foId = DB::table('filiere_options')->insertGetId([
            'nom' => 'SIL', 'code' => 'SIL', 'filiere_id' => $filiereId, 'archive' => false, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $niveauId = DB::table('niveaux')->insertGetId([
            'libelle' => 'Licence 1', 'code' => 'L1', 'ordre' => 1, 'filiere_option_id' => $foId, 'archive' => false, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->matiere = Matiere::create([
            'nom' => 'Bases de Données', 'code' => 'BDD', 'semestre' => 1,
            'hp_initial' => 6, 'tpe_initial' => 3,
            'filiere_id' => $filiereId, 'niveau_id' => $niveauId, 'archive' => false,
        ]);

        $this->option = Option::create([
            'nom'               => 'SIL L1 2025-2026',
            'filiere_option_id' => $foId,
            'niveau_id'         => $niveauId,
            'centre_id'         => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    protected function makeSeance(array $attrs = []): Seance
    {
        return Seance::create(array_merge([
            'matiere_id'            => $this->matiere->id,
            'salle_id'              => $this->salle->id,
            'professeur_id'         => $this->prof->id,
            'annee_scolaire_id'     => $this->annee->id,
            'debut'                 => today()->setTime(8, 0),
            'fin'                   => today()->setTime(11, 0),
            'type'                  => 'HP',
            'statut'                => 'planifiee',
            'is_inter_centre'       => false,
            'est_composition'       => false,
            'durees_pauses_minutes' => 0,
        ], $attrs));
    }

    protected function makeEtudiantInscrit(string $badgeUid = 'BADGE-ETU-TEST'): array
    {
        $etudiant = Etudiant::create([
            'matricule'      => 'TEST-001',
            'nom'            => 'DUPONT',
            'prenom'         => 'Jean',
            'email'          => 'jean.dupont@test.bj',
            'telephone'      => '+229 90000001',
            'badge_uid'      => $badgeUid,
            'date_naissance' => '2003-05-15',
        ]);

        $inscription = Inscription::create([
            'etudiant_id'       => $etudiant->id,
            'option_id'         => $this->option->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut'            => 'actif',
            'date_inscription'  => today(),
        ]);

        return [$etudiant, $inscription];
    }
}
