<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── CENTRES ───────────────────────────────────────────────────────
        DB::table('centres')->insert([
            ['nom' => 'Centre de Gbégamey',  'ville' => 'Cotonou',    'created_at' => now(), 'updated_at' => now()],
            ['nom' => "Centre d'Akpakpa",    'ville' => 'Cotonou',    'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Centre de Porto-Novo','ville' => 'Porto-Novo', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Centre de Calavi',    'ville' => 'Calavi',     'created_at' => now(), 'updated_at' => now()],
        ]);

        $gbegameyId  = DB::table('centres')->where('nom', 'Centre de Gbégamey')->value('id');
        $akpakpaId   = DB::table('centres')->where('nom', "Centre d'Akpakpa")->value('id');
        $portoNovoId = DB::table('centres')->where('nom', 'Centre de Porto-Novo')->value('id');

        // ── UTILISATEURS ──────────────────────────────────────────────────
        // NOTE : Le projet Gemini utilise ROLE_ADMIN, ROLE_RESPONSABLE_CENTRE, etc.
        DB::table('users')->insert([
            [
                'name'       => 'AYI Théophane',
                'email'      => 'directeur@gasa.bj',
                'password'   => Hash::make('Gasa2026!'),
                'role'       => 'ROLE_ADMIN',
                'centre_id'  => null, // Directeur = accès global
                'email_verified_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name'       => 'SOSSOU Marc',
                'email'      => 'responsable.gbegamey@gasa.bj',
                'password'   => Hash::make('Gbegamey2026!'),
                'role'       => 'ROLE_RESPONSABLE_CENTRE',
                'centre_id'  => $gbegameyId,
                'email_verified_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name'       => 'HOUNSOU Alice',
                'email'      => 'responsable.akpakpa@gasa.bj',
                'password'   => Hash::make('Akpakpa2026!'),
                'role'       => 'ROLE_RESPONSABLE_CENTRE',
                'centre_id'  => $akpakpaId,
                'email_verified_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name'       => 'DOSSOU Fatima',
                'email'      => 'secretaire.gbegamey@gasa.bj',
                'password'   => Hash::make('Secret2026!'),
                'role'       => 'ROLE_SECRETAIRE',
                'centre_id'  => $gbegameyId,
                'email_verified_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ],
            // Professeur pour les matières
            [
                'name'       => 'KPOSSOU Jean',
                'email'      => 'prof.kpossou@gasa.bj',
                'password'   => Hash::make('Prof2026!'),
                'role'       => 'ROLE_AGENT',
                'centre_id'  => $gbegameyId,
                'email_verified_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        $profId = DB::table('users')->where('email', 'prof.kpossou@gasa.bj')->value('id');

        // ── FILIÈRES (globales) ───────────────────────────────────────────
        DB::table('filieres')->insert([
            ['nom' => 'Génie Logiciel',                               'code' => 'GL',   'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Bases de Données et Intelligence Artificielle','code' => 'BDAI', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Réseaux et Systèmes Informatiques',            'code' => 'RSI',  'created_at' => now(), 'updated_at' => now()],
        ]);

        $glId   = DB::table('filieres')->where('code', 'GL')->value('id');
        $bdaiId = DB::table('filieres')->where('code', 'BDAI')->value('id');

        // ── MATIÈRES (globales) ───────────────────────────────────────────
        DB::table('matieres')->insert([
            ['nom' => 'Bases de Données',          'code' => 'BDD',   'semestre' => 1, 'hp_initial' => 30, 'tpe_initial' => 10, 'filiere_id' => $glId,   'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Algorithmique',             'code' => 'ALGO',  'semestre' => 1, 'hp_initial' => 40, 'tpe_initial' => 15, 'filiere_id' => $glId,   'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Réseaux Informatiques',     'code' => 'RESEAU','semestre' => 2, 'hp_initial' => 30, 'tpe_initial' => 10, 'filiere_id' => $glId,   'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Intelligence Artificielle', 'code' => 'IA',    'semestre' => 1, 'hp_initial' => 35, 'tpe_initial' => 20, 'filiere_id' => $bdaiId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $bddId = DB::table('matieres')->where('code', 'BDD')->value('id');

        // ── OPTIONS (déclinaisons locales par centre) ─────────────────────
        DB::table('options')->insert([
            ['nom' => 'GL Niveau 1',  'niveau' => 1, 'filiere_id' => $glId,   'centre_id' => $gbegameyId,  'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'GL Niveau 2',  'niveau' => 2, 'filiere_id' => $glId,   'centre_id' => $gbegameyId,  'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'GL Niveau 1',  'niveau' => 1, 'filiere_id' => $glId,   'centre_id' => $akpakpaId,   'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'BDAI Niveau 1','niveau' => 1, 'filiere_id' => $bdaiId, 'centre_id' => $gbegameyId,  'created_at' => now(), 'updated_at' => now()],
        ]);

        $optGL1GbeId = DB::table('options')
            ->where('filiere_id', $glId)->where('centre_id', $gbegameyId)->where('niveau', 1)
            ->value('id');

        // ── ÉTUDIANTS ─────────────────────────────────────────────────────
        DB::table('etudiants')->insert([
            ['matricule' => 'GASA-2026-001', 'nom' => 'AHOUANSOU', 'prenom' => 'Kossivi',  'email' => 'k.ahouansou@gasa.bj', 'badge_uid' => 'ETU-001', 'statut' => 'actif', 'option_id' => $optGL1GbeId, 'created_at' => now(), 'updated_at' => now()],
            ['matricule' => 'GASA-2026-002', 'nom' => 'MONTCHO',   'prenom' => 'Diane',    'email' => 'd.montcho@gasa.bj',   'badge_uid' => 'ETU-002', 'statut' => 'actif', 'option_id' => $optGL1GbeId, 'created_at' => now(), 'updated_at' => now()],
            ['matricule' => 'GASA-2026-003', 'nom' => 'AGOSSOU',   'prenom' => 'Pierre',   'email' => 'p.agossou@gasa.bj',   'badge_uid' => 'ETU-003', 'statut' => 'actif', 'option_id' => $optGL1GbeId, 'created_at' => now(), 'updated_at' => now()],
            ['matricule' => 'GASA-2026-004', 'nom' => 'HOUNGNIBO', 'prenom' => 'Sandra',   'email' => 's.houngnibo@gasa.bj', 'badge_uid' => 'ETU-004', 'statut' => 'suspendu', 'option_id' => $optGL1GbeId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── SALLES ────────────────────────────────────────────────────────
        DB::table('salles')->insert([
            ['nom' => 'Salle A101', 'capacite' => 40, 'type' => 'banalisee',   'centre_id' => $gbegameyId, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Salle B201', 'capacite' => 30, 'type' => 'banalisee',   'centre_id' => $gbegameyId, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Labo Info',  'capacite' => 20, 'type' => 'laboratoire', 'centre_id' => $gbegameyId, 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'Salle C101', 'capacite' => 35, 'type' => 'banalisee',   'centre_id' => $akpakpaId,  'created_at' => now(), 'updated_at' => now()],
        ]);

        $salleA101Id = DB::table('salles')->where('nom', 'Salle A101')->value('id');

        // ── QUOTAS MATIÈRE PAR CENTRE (table matiere_centre) ─────────────
        DB::table('matiere_centre')->insert([
            ['matiere_id' => $bddId, 'centre_id' => $gbegameyId, 'hp_restant' => 30, 'tpe_dynamique' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['matiere_id' => $bddId, 'centre_id' => $akpakpaId,  'hp_restant' => 30, 'tpe_dynamique' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ── SÉANCE D'EXEMPLE ──────────────────────────────────────────────
        DB::table('seances')->insert([
            [
                'matiere_id'     => $bddId,
                'salle_id'       => $salleA101Id,
                'professeur_id'  => $profId,
                'debut'          => now()->setTime(8, 0),
                'fin'            => now()->setTime(10, 0),
                'type'           => 'HP',
                'statut'         => 'planifiee',
                'is_inter_centre'=> false,
                'created_at'     => now(), 'updated_at' => now(),
            ],
        ]);

        $seanceId = DB::table('seances')->latest()->value('id');
        DB::table('option_seance')->insert([
            ['seance_id' => $seanceId, 'option_id' => $optGL1GbeId],
        ]);

        $this->command->info('✅ GASA-ERP seeded successfully!');
        $this->command->table(
            ['Email', 'Mot de passe', 'Rôle'],
            [
                ['directeur@gasa.bj',              'Gasa2026!',      'ROLE_ADMIN'],
                ['responsable.gbegamey@gasa.bj',   'Gbegamey2026!',  'ROLE_RESPONSABLE_CENTRE'],
                ['responsable.akpakpa@gasa.bj',    'Akpakpa2026!',   'ROLE_RESPONSABLE_CENTRE'],
                ['secretaire.gbegamey@gasa.bj',    'Secret2026!',    'ROLE_SECRETAIRE'],
            ]
        );
    }
}
