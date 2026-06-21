<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();

            // === ANNEES_SCOLAIRES ===
            $a2526 = DB::table('annees_scolaires')->insertGetId([
                'libelle'    => '2025-2026',
                'date_debut' => '2025-09-01',
                'date_fin'   => '2026-07-31',
                'active'     => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // === CENTRES ===
            $cGbe = DB::table('centres')->insertGetId([
                'nom'        => 'Gbégamey',
                'ville'      => 'Cotonou',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $cAkp = DB::table('centres')->insertGetId([
                'nom'        => 'Akpakpa',
                'ville'      => 'Cotonou',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // === FILIERES ===
            $fGE = DB::table('filieres')->insertGetId([
                'nom'        => 'Génie Électrique',
                'code'       => 'GE',
                'archive'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // === FILIERE_OPTIONS ===
            $foSIL = DB::table('filiere_options')->insertGetId([
                'nom'        => 'Système Informatique et Logiciel',
                'code'       => 'SIL',
                'filiere_id' => $fGE,
                'archive'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $foGEER = DB::table('filiere_options')->insertGetId([
                'nom'        => 'Génie Électrique Énergie Renouvelable',
                'code'       => 'GEER',
                'filiere_id' => $fGE,
                'archive'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $foRIT = DB::table('filiere_options')->insertGetId([
                'nom'        => 'Réseaux et Ingénierie Télécom',
                'code'       => 'RIT',
                'filiere_id' => $fGE,
                'archive'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // === NIVEAUX (3 par filiere_option = 9 total) ===
            $nSIL_L1  = DB::table('niveaux')->insertGetId(['libelle' => 'Licence 1', 'code' => 'L1', 'ordre' => 1, 'filiere_option_id' => $foSIL,  'archive' => false, 'created_at' => $now, 'updated_at' => $now]);
            $nSIL_L2  = DB::table('niveaux')->insertGetId(['libelle' => 'Licence 2', 'code' => 'L2', 'ordre' => 2, 'filiere_option_id' => $foSIL,  'archive' => false, 'created_at' => $now, 'updated_at' => $now]);
            $nSIL_L3  = DB::table('niveaux')->insertGetId(['libelle' => 'Licence 3', 'code' => 'L3', 'ordre' => 3, 'filiere_option_id' => $foSIL,  'archive' => false, 'created_at' => $now, 'updated_at' => $now]);
            $nGEER_L1 = DB::table('niveaux')->insertGetId(['libelle' => 'Licence 1', 'code' => 'L1', 'ordre' => 1, 'filiere_option_id' => $foGEER, 'archive' => false, 'created_at' => $now, 'updated_at' => $now]);
            $nGEER_L2 = DB::table('niveaux')->insertGetId(['libelle' => 'Licence 2', 'code' => 'L2', 'ordre' => 2, 'filiere_option_id' => $foGEER, 'archive' => false, 'created_at' => $now, 'updated_at' => $now]);
            $nGEER_L3 = DB::table('niveaux')->insertGetId(['libelle' => 'Licence 3', 'code' => 'L3', 'ordre' => 3, 'filiere_option_id' => $foGEER, 'archive' => false, 'created_at' => $now, 'updated_at' => $now]);
            $nRIT_L1  = DB::table('niveaux')->insertGetId(['libelle' => 'Licence 1', 'code' => 'L1', 'ordre' => 1, 'filiere_option_id' => $foRIT,  'archive' => false, 'created_at' => $now, 'updated_at' => $now]);
            $nRIT_L2  = DB::table('niveaux')->insertGetId(['libelle' => 'Licence 2', 'code' => 'L2', 'ordre' => 2, 'filiere_option_id' => $foRIT,  'archive' => false, 'created_at' => $now, 'updated_at' => $now]);
            $nRIT_L3  = DB::table('niveaux')->insertGetId(['libelle' => 'Licence 3', 'code' => 'L3', 'ordre' => 3, 'filiere_option_id' => $foRIT,  'archive' => false, 'created_at' => $now, 'updated_at' => $now]);

            // === MATIERES (3 par filiere_option × 3 niveaux = 27 total) ===
            // hp_initial=6h / tpe_initial=3h pour toutes (2 séances HP de 3h = quota)
            $matieresSpec = [
                $foSIL  => [
                    [$nSIL_L1, $nSIL_L2, $nSIL_L3],
                    [
                        ['BDD-SIL',   'Bases de Données et SGBDR',               2, 6, 3],
                        ['ALG-SIL',   'Algorithmique et Structures de Données',   1, 6, 3],
                        ['RES-SIL',   'Réseaux Informatiques',                    2, 6, 3],
                    ],
                ],
                $foGEER => [
                    [$nGEER_L1, $nGEER_L2, $nGEER_L3],
                    [
                        ['ELEC-GEER', 'Électricité Générale',                    1, 6, 3],
                        ['ENR-GEER',  'Énergies Renouvelables',                   1, 6, 3],
                        ['AUTO-GEER', 'Automatismes Industriels',                 2, 6, 3],
                    ],
                ],
                $foRIT  => [
                    [$nRIT_L1, $nRIT_L2, $nRIT_L3],
                    [
                        ['TRANS-RIT', 'Transmission des Données',                1, 6, 3],
                        ['COMM-RIT',  'Commutation et Routage',                   2, 6, 3],
                        ['PROTO-RIT', 'Protocoles Réseau',                        2, 6, 3],
                    ],
                ],
            ];

            foreach ($matieresSpec as $foId => [$niveaux, $mats]) {
                foreach ($niveaux as $nId) {
                    foreach ($mats as [$code, $nom, $sem, $hp, $tpe]) {
                        DB::table('matieres')->insert([
                            'nom'         => $nom,
                            'code'        => $code,
                            'semestre'    => $sem,
                            'hp_initial'  => $hp,
                            'tpe_initial' => $tpe,
                            'filiere_id'  => $fGE,
                            'niveau_id'   => $nId,
                            'archive'     => false,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ]);
                    }
                }
            }

            // IDs des matières utilisées dans les séances
            $mBDD   = DB::table('matieres')->where('code', 'BDD-SIL')->where('niveau_id', $nSIL_L1)->value('id');
            $mALG   = DB::table('matieres')->where('code', 'ALG-SIL')->where('niveau_id', $nSIL_L1)->value('id');
            $mRES   = DB::table('matieres')->where('code', 'RES-SIL')->where('niveau_id', $nSIL_L1)->value('id');
            $mENR   = DB::table('matieres')->where('code', 'ENR-GEER')->where('niveau_id', $nGEER_L1)->value('id');
            $mAUTO  = DB::table('matieres')->where('code', 'AUTO-GEER')->where('niveau_id', $nGEER_L1)->value('id');
            $mELEC  = DB::table('matieres')->where('code', 'ELEC-GEER')->where('niveau_id', $nGEER_L1)->value('id');
            $mTRANS = DB::table('matieres')->where('code', 'TRANS-RIT')->where('niveau_id', $nRIT_L1)->value('id');

            // === USERS ===
            $admin = DB::table('users')->insertGetId([
                'name'               => 'AYI Théophane',
                'role'               => 'ROLE_ADMIN',
                'centre_id'          => null,
                'email'              => 'admin@gasa.bj',
                'telephone'          => '+229 97000001',
                'badge_uid'          => null,
                'email_verified_at'  => $now,
                'password'           => Hash::make('Password123!'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            $respGbe = DB::table('users')->insertGetId([
                'name'               => 'SOSSOU Marc',
                'role'               => 'ROLE_RESPONSABLE_CENTRE',
                'centre_id'          => $cGbe,
                'email'              => 'resp.gbegamey@gasa.bj',
                'telephone'          => '+229 97000002',
                'badge_uid'          => null,
                'email_verified_at'  => $now,
                'password'           => Hash::make('Password123!'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            $respAkp = DB::table('users')->insertGetId([
                'name'               => 'HOUNSOU Alice',
                'role'               => 'ROLE_RESPONSABLE_CENTRE',
                'centre_id'          => $cAkp,
                'email'              => 'resp.akpakpa@gasa.bj',
                'telephone'          => '+229 97000003',
                'badge_uid'          => null,
                'email_verified_at'  => $now,
                'password'           => Hash::make('Password123!'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            // 3 professeurs rattachés à Gbégamey
            $prof1 = DB::table('users')->insertGetId([
                'name'               => 'DEGBOE Désiré',
                'role'               => 'ROLE_PROFESSEUR',
                'centre_id'          => $cGbe,
                'email'              => 'degboe@gasa.bj',
                'telephone'          => '+229 97000004',
                'badge_uid'          => 'BADGE-PROF-GBE-001',
                'email_verified_at'  => $now,
                'password'           => Hash::make('Password123!'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            $prof2 = DB::table('users')->insertGetId([
                'name'               => 'AKPONNA Marc-Aurèle',
                'role'               => 'ROLE_PROFESSEUR',
                'centre_id'          => $cGbe,
                'email'              => 'akponna@gasa.bj',
                'telephone'          => '+229 97000005',
                'badge_uid'          => 'BADGE-PROF-GBE-002',
                'email_verified_at'  => $now,
                'password'           => Hash::make('Password123!'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            $prof3 = DB::table('users')->insertGetId([
                'name'               => 'LALEYE Sylvestre',
                'role'               => 'ROLE_PROFESSEUR',
                'centre_id'          => $cGbe,
                'email'              => 'laleye@gasa.bj',
                'telephone'          => '+229 97000006',
                'badge_uid'          => 'BADGE-PROF-GBE-003',
                'email_verified_at'  => $now,
                'password'           => Hash::make('Password123!'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            // 2 professeurs rattachés à Akpakpa
            $prof4 = DB::table('users')->insertGetId([
                'name'               => 'GNIMAVO Basile',
                'role'               => 'ROLE_PROFESSEUR',
                'centre_id'          => $cAkp,
                'email'              => 'gnimavo@gasa.bj',
                'telephone'          => '+229 97000007',
                'badge_uid'          => 'BADGE-PROF-AKP-001',
                'email_verified_at'  => $now,
                'password'           => Hash::make('Password123!'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            $prof5 = DB::table('users')->insertGetId([
                'name'               => 'KPANOU Virginie',
                'role'               => 'ROLE_PROFESSEUR',
                'centre_id'          => $cAkp,
                'email'              => 'kpanou@gasa.bj',
                'telephone'          => '+229 97000008',
                'badge_uid'          => 'BADGE-PROF-AKP-002',
                'email_verified_at'  => $now,
                'password'           => Hash::make('Password123!'),
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

            // === MATIERE_PROFESSEUR ===
            DB::table('matiere_professeur')->insert([
                // Gbégamey
                ['user_id' => $prof1, 'matiere_id' => $mBDD],
                ['user_id' => $prof1, 'matiere_id' => $mALG],
                ['user_id' => $prof2, 'matiere_id' => $mRES],
                ['user_id' => $prof2, 'matiere_id' => $mELEC],
                ['user_id' => $prof3, 'matiere_id' => $mENR],
                ['user_id' => $prof3, 'matiere_id' => $mAUTO],
                // Akpakpa
                ['user_id' => $prof4, 'matiere_id' => $mBDD],
                ['user_id' => $prof4, 'matiere_id' => $mALG],
                ['user_id' => $prof5, 'matiere_id' => $mRES],
                ['user_id' => $prof5, 'matiere_id' => $mTRANS],
            ]);

            // === OPTIONS (groupes-classes) ===
            // Gbégamey : SIL-L1 + RIT-L1
            $optSIL = DB::table('options')->insertGetId([
                'nom'               => 'GE-SIL L1 Gbégamey 2025-2026',
                'filiere_option_id' => $foSIL,
                'niveau_id'         => $nSIL_L1,
                'centre_id'         => $cGbe,
                'annee_scolaire_id' => $a2526,
                'responsable_nom'   => null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            $optRIT = DB::table('options')->insertGetId([
                'nom'               => 'GE-RIT L1 Gbégamey 2025-2026',
                'filiere_option_id' => $foRIT,
                'niveau_id'         => $nRIT_L1,
                'centre_id'         => $cGbe,
                'annee_scolaire_id' => $a2526,
                'responsable_nom'   => null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            // Akpakpa : SIL-L1 + GEER-L1
            $optSIL_AKP = DB::table('options')->insertGetId([
                'nom'               => 'GE-SIL L1 Akpakpa 2025-2026',
                'filiere_option_id' => $foSIL,
                'niveau_id'         => $nSIL_L1,
                'centre_id'         => $cAkp,
                'annee_scolaire_id' => $a2526,
                'responsable_nom'   => 'ZINSOU Fidèle',
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            $optGEER_AKP = DB::table('options')->insertGetId([
                'nom'               => 'GE-GEER L1 Akpakpa 2025-2026',
                'filiere_option_id' => $foGEER,
                'niveau_id'         => $nGEER_L1,
                'centre_id'         => $cAkp,
                'annee_scolaire_id' => $a2526,
                'responsable_nom'   => null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            // === ETUDIANTS (20 au total : 10 Gbégamey + 10 Akpakpa) ===
            $etudiantsDef = [
                // Gbégamey (001-010)
                ['ETU-2526-001', 'ADJOVI',    'Rosine',   'adjovi.rosine@etu.gasa.bj',    '+229 96001001', 'BADGE-ETU-001', '2003-04-12'],
                ['ETU-2526-002', 'BELLO',     'Kouamé',   'bello.kouame@etu.gasa.bj',     '+229 96001002', 'BADGE-ETU-002', '2003-07-23'],
                ['ETU-2526-003', 'CHABI',     'Farida',   'chabi.farida@etu.gasa.bj',     '+229 96001003', 'BADGE-ETU-003', '2002-11-05'],
                ['ETU-2526-004', 'DOSSOU',    'Alain',    'dossou.alain@etu.gasa.bj',     '+229 96001004', 'BADGE-ETU-004', '2003-01-17'],
                ['ETU-2526-005', 'EGUE',      'Serge',    'egue.serge@etu.gasa.bj',       '+229 96001005', 'BADGE-ETU-005', '2004-03-30'],
                ['ETU-2526-006', 'FAVI',      'Hélène',   'favi.helene@etu.gasa.bj',      '+229 96001006', 'BADGE-ETU-006', '2003-06-14'],
                ['ETU-2526-007', 'GANDJI',    'Pierre',   'gandji.pierre@etu.gasa.bj',    '+229 96001007', 'BADGE-ETU-007', '2002-09-08'],
                ['ETU-2526-008', 'HOUETO',    'Marlène',  'houeto.marlene@etu.gasa.bj',   '+229 96001008', 'BADGE-ETU-008', '2003-12-21'],
                ['ETU-2526-009', 'IDOSSOU',   'Gilles',   'idossou.gilles@etu.gasa.bj',   '+229 96001009', 'BADGE-ETU-009', '2004-02-03'],
                ['ETU-2526-010', 'JOHNSON',   'Laïda',    'johnson.laida@etu.gasa.bj',    '+229 96001010', 'BADGE-ETU-010', '2003-08-16'],
                // Akpakpa (011-020)
                ['ETU-2526-011', 'KOUGBLENOU','Sèdami',   'kougblenou.sedami@etu.gasa.bj','+229 96001011', 'BADGE-ETU-011', '2003-05-19'],
                ['ETU-2526-012', 'LAGNIDE',   'Bertrand', 'lagnide.bertrand@etu.gasa.bj', '+229 96001012', 'BADGE-ETU-012', '2002-10-02'],
                ['ETU-2526-013', 'MEVO',      'Clarisse', 'mevo.clarisse@etu.gasa.bj',    '+229 96001013', 'BADGE-ETU-013', '2003-03-27'],
                ['ETU-2526-014', 'NOUDOKPIN', 'Hervé',    'noudokpin.herve@etu.gasa.bj',  '+229 96001014', 'BADGE-ETU-014', '2004-01-11'],
                ['ETU-2526-015', 'OLOU',      'Aminata',  'olou.aminata@etu.gasa.bj',     '+229 96001015', 'BADGE-ETU-015', '2003-09-04'],
                ['ETU-2526-016', 'PADONOU',   'Roméo',    'padonou.romeo@etu.gasa.bj',    '+229 96001016', 'BADGE-ETU-016', '2002-12-15'],
                ['ETU-2526-017', 'QUENUM',    'Fatouma',  'quenum.fatouma@etu.gasa.bj',   '+229 96001017', 'BADGE-ETU-017', '2003-07-07'],
                ['ETU-2526-018', 'ROBINEAU',  'Dossou',   'robineau.dossou@etu.gasa.bj',  '+229 96001018', 'BADGE-ETU-018', '2004-04-22'],
                ['ETU-2526-019', 'SOGLO',     'Blandine', 'soglo.blandine@etu.gasa.bj',   '+229 96001019', 'BADGE-ETU-019', '2003-11-30'],
                ['ETU-2526-020', 'TOKOUDAGBA','Eric',     'tokoudagba.eric@etu.gasa.bj',  '+229 96001020', 'BADGE-ETU-020', '2002-08-13'],
            ];

            $etuIds = [];
            foreach ($etudiantsDef as [$mat, $nom, $prenom, $email, $tel, $badge, $dob]) {
                $etuIds[] = DB::table('etudiants')->insertGetId([
                    'matricule'       => $mat,
                    'nom'             => $nom,
                    'prenom'          => $prenom,
                    'email'           => $email,
                    'telephone'       => $tel,
                    'badge_uid'       => $badge,
                    'date_naissance'  => $dob,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }

            // === INSCRIPTIONS ===
            // Gbégamey : etu 0-4 → SIL, etu 5-9 → RIT
            $inscSIL = [];
            foreach (array_slice($etuIds, 0, 5) as $eId) {
                $inscSIL[] = DB::table('inscriptions')->insertGetId([
                    'etudiant_id'       => $eId,
                    'option_id'         => $optSIL,
                    'annee_scolaire_id' => $a2526,
                    'statut'            => 'actif',
                    'date_inscription'  => '2025-09-15',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
            foreach (array_slice($etuIds, 5, 5) as $eId) {
                DB::table('inscriptions')->insert([
                    'etudiant_id'       => $eId,
                    'option_id'         => $optRIT,
                    'annee_scolaire_id' => $a2526,
                    'statut'            => 'actif',
                    'date_inscription'  => '2025-09-15',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
            // Akpakpa : etu 10-14 → SIL_AKP, etu 15-19 → GEER_AKP
            $inscSIL_AKP = [];
            foreach (array_slice($etuIds, 10, 5) as $eId) {
                $inscSIL_AKP[] = DB::table('inscriptions')->insertGetId([
                    'etudiant_id'       => $eId,
                    'option_id'         => $optSIL_AKP,
                    'annee_scolaire_id' => $a2526,
                    'statut'            => 'actif',
                    'date_inscription'  => '2025-09-15',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }
            $inscGEER_AKP = [];
            foreach (array_slice($etuIds, 15, 5) as $eId) {
                $inscGEER_AKP[] = DB::table('inscriptions')->insertGetId([
                    'etudiant_id'       => $eId,
                    'option_id'         => $optGEER_AKP,
                    'annee_scolaire_id' => $a2526,
                    'statut'            => 'actif',
                    'date_inscription'  => '2025-09-15',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            }

            // === SALLES ===
            // Gbégamey : 1E1 (salleA) + 1E2 (salleB) + autres
            $salleA = DB::table('salles')->insertGetId([
                'nom'        => '1E1',
                'capacite'   => 40,
                'type'       => 'Salle de cours',
                'centre_id'  => $cGbe,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $salleB = DB::table('salles')->insertGetId([
                'nom'        => '1E2',
                'capacite'   => 40,
                'type'       => 'Salle de cours',
                'centre_id'  => $cGbe,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach (['2E1', '2E2', '3E1', '3E2', '3E3'] as $nomSalle) {
                DB::table('salles')->insert([
                    'nom' => $nomSalle, 'capacite' => 40, 'type' => 'Salle de cours',
                    'centre_id' => $cGbe, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            // Akpakpa : 1E1 (salleC) + 1E2 (salleD) + autres
            $salleC = DB::table('salles')->insertGetId([
                'nom'        => '1E1',
                'capacite'   => 40,
                'type'       => 'Salle de cours',
                'centre_id'  => $cAkp,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $salleD = DB::table('salles')->insertGetId([
                'nom'        => '1E2',
                'capacite'   => 40,
                'type'       => 'Salle de cours',
                'centre_id'  => $cAkp,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach (['2E1', '2E2'] as $nomSalle) {
                DB::table('salles')->insert([
                    'nom' => $nomSalle, 'capacite' => 40, 'type' => 'Salle de cours',
                    'centre_id' => $cAkp, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            // === EQUIPEMENTS ===
            DB::table('equipements')->insert([
                ['nom' => 'Tableau blanc',  'type_materiel' => 'Mobilier',     'numero_serie' => 'TB-GBE-1E1-01',  'etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleA, 'created_at' => $now, 'updated_at' => $now],
                ['nom' => 'Vidéoprojecteur','type_materiel' => 'Électronique', 'numero_serie' => 'VP-GBE-1E1-01',  'etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleA, 'created_at' => $now, 'updated_at' => $now],
                ['nom' => 'Tableau blanc',  'type_materiel' => 'Mobilier',     'numero_serie' => 'TB-GBE-1E2-01',  'etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleB, 'created_at' => $now, 'updated_at' => $now],
                ['nom' => 'Tableau noir',   'type_materiel' => 'Mobilier',     'numero_serie' => 'TN-AKP-1E1-01',  'etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleC, 'created_at' => $now, 'updated_at' => $now],
                ['nom' => 'Climatiseur',    'type_materiel' => 'Électroménager','numero_serie'=> 'CLIM-AKP-1E1-01','etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleC, 'created_at' => $now, 'updated_at' => $now],
                ['nom' => 'Tableau blanc',  'type_materiel' => 'Mobilier',     'numero_serie' => 'TB-AKP-1E2-01',  'etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleD, 'created_at' => $now, 'updated_at' => $now],
            ]);
            $toutesLesSalles = DB::table('salles')->get();
            foreach ($toutesLesSalles as $salle) {
                $centreCode = ($salle->centre_id === $cGbe) ? 'GBE' : 'AKP';
                $salleCode  = str_replace(['E', ' '], ['-', ''], $salle->nom);
                DB::table('equipements')->insert([
                    'nom'           => 'Scanner RFID',
                    'type_materiel' => 'Scanner RFID',
                    'numero_serie'  => "RFID-{$centreCode}-{$salleCode}-01",
                    'etat'          => 'bon',
                    'quantite'      => 1,
                    'salle_id'      => $salle->id,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }

            // === MATIERE_CENTRE_ANNEE (initialisation) ===
            $allMats = DB::table('matieres')->select('id', 'hp_initial', 'tpe_initial')->get();
            $mcaRows = [];
            foreach ($allMats as $mat) {
                foreach ([$cGbe, $cAkp] as $cId) {
                    $mcaRows[] = [
                        'matiere_id'        => $mat->id,
                        'centre_id'         => $cId,
                        'annee_scolaire_id' => $a2526,
                        'hp_restant'        => $mat->hp_initial,
                        'tpe_dynamique'     => $mat->tpe_initial,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
            }
            DB::table('matiere_centre_annee')->insert($mcaRows);

            // ══════════════════════════════════════════════════════════════════════
            // === SEANCES GBÉGAMEY ===
            // ══════════════════════════════════════════════════════════════════════
            //   26/05 HP1-BDD prof1 salleA ✓ → BDD hp_restant GBE: 6→3
            //   27/05 HP1-ALG prof1 salleA ✓ → ALG hp_restant GBE: 6→3
            //   28/05 HP1-RES prof2 salleA ✓ → RES hp_restant GBE: 6→3
            //   02/06 HP2-BDD prof1 salleA ✓ → BDD hp_restant GBE: 3→0
            //   03/06 HP2-ALG prof1 salleA ✗ ABSENT → tpe_dyn ALG GBE: 3→0
            //   04/06 HP2-RES prof2 salleA ✓ → RES hp_restant GBE: 3→0
            //   09/06 TPE-BDD salleB (BDD hp_restant=0 ✓)
            //   10/06 HPratt-ALG prof1 salleB ✓ → ALG hp_restant GBE: 3→0
            //   11/06 TPE-RES salleA (RES hp_restant=0 ✓)

            $seanceHP1BDD = DB::table('seances')->insertGetId([
                'matiere_id'             => $mBDD,
                'salle_id'               => $salleA,
                'professeur_id'          => $prof1,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-26 08:00:00',
                'fin'                    => '2026-05-26 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-05-26 07:55:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 3,
                'cloture_validee_at'     => '2026-05-26 11:10:00',
                'cloture_validee_par'    => $prof1,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceHP1ALG = DB::table('seances')->insertGetId([
                'matiere_id'             => $mALG,
                'salle_id'               => $salleA,
                'professeur_id'          => $prof1,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-27 08:00:00',
                'fin'                    => '2026-05-27 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-05-27 07:58:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 3,
                'cloture_validee_at'     => '2026-05-27 11:05:00',
                'cloture_validee_par'    => $prof1,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceHP1RES = DB::table('seances')->insertGetId([
                'matiere_id'             => $mRES,
                'salle_id'               => $salleA,
                'professeur_id'          => $prof2,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-28 08:00:00',
                'fin'                    => '2026-05-28 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-05-28 08:02:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 3,
                'cloture_validee_at'     => '2026-05-28 11:15:00',
                'cloture_validee_par'    => $prof2,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceHP2BDD = DB::table('seances')->insertGetId([
                'matiere_id'             => $mBDD,
                'salle_id'               => $salleA,
                'professeur_id'          => $prof1,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-06-02 08:00:00',
                'fin'                    => '2026-06-02 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-06-02 07:57:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 4,
                'cloture_validee_at'     => '2026-06-02 11:08:00',
                'cloture_validee_par'    => $prof1,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            // HP2-ALG GBE — 03/06 ABSENT — prof ne badgé pas, étudiants absents
            $seanceHP2ALG = DB::table('seances')->insertGetId([
                'matiere_id'             => $mALG,
                'salle_id'               => $salleA,
                'professeur_id'          => $prof1,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-06-03 08:00:00',
                'fin'                    => '2026-06-03 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => null,
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => null,
                'cloture_validee_at'     => null,
                'cloture_validee_par'    => null,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceHP2RES = DB::table('seances')->insertGetId([
                'matiere_id'             => $mRES,
                'salle_id'               => $salleA,
                'professeur_id'          => $prof2,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-06-04 08:00:00',
                'fin'                    => '2026-06-04 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-06-04 08:01:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 3,
                'cloture_validee_at'     => '2026-06-04 11:12:00',
                'cloture_validee_par'    => $prof2,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceTPEBDD = DB::table('seances')->insertGetId([
                'matiere_id'             => $mBDD,
                'salle_id'               => $salleB,
                'professeur_id'          => null,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-06-09 08:00:00',
                'fin'                    => '2026-06-09 11:00:00',
                'type'                   => 'TPE',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => null,
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => null,
                'cloture_validee_at'     => '2026-06-09 11:00:00',
                'cloture_validee_par'    => null,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceHPrattALG = DB::table('seances')->insertGetId([
                'matiere_id'             => $mALG,
                'salle_id'               => $salleB,
                'professeur_id'          => $prof1,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-06-10 08:00:00',
                'fin'                    => '2026-06-10 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-06-10 07:55:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 3,
                'cloture_validee_at'     => '2026-06-10 11:10:00',
                'cloture_validee_par'    => $prof1,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceTPERES = DB::table('seances')->insertGetId([
                'matiere_id'             => $mRES,
                'salle_id'               => $salleA,
                'professeur_id'          => null,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-06-11 08:00:00',
                'fin'                    => '2026-06-11 11:00:00',
                'type'                   => 'TPE',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => null,
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => null,
                'cloture_validee_at'     => '2026-06-11 11:00:00',
                'cloture_validee_par'    => null,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            // MCA Gbégamey — état final
            DB::table('matiere_centre_annee')
                ->where('matiere_id', $mBDD)->where('centre_id', $cGbe)->where('annee_scolaire_id', $a2526)
                ->update(['hp_restant' => 0, 'tpe_dynamique' => 3]);
            DB::table('matiere_centre_annee')
                ->where('matiere_id', $mALG)->where('centre_id', $cGbe)->where('annee_scolaire_id', $a2526)
                ->update(['hp_restant' => 0, 'tpe_dynamique' => 0]);  // 1 absence → pénalité TPE
            DB::table('matiere_centre_annee')
                ->where('matiere_id', $mRES)->where('centre_id', $cGbe)->where('annee_scolaire_id', $a2526)
                ->update(['hp_restant' => 0, 'tpe_dynamique' => 3]);

            // ══════════════════════════════════════════════════════════════════════
            // === SEANCES AKPAKPA (salleC / salleD, prof4 / prof5) ===
            // ══════════════════════════════════════════════════════════════════════
            //   07/05 HP1-BDD-AKP prof4 salleC ✓ → BDD hp_restant AKP: 6→3
            //   08/05 HP1-ALG-AKP prof4 salleC ✓ → ALG hp_restant AKP: 6→3
            //   09/05 HP1-RES-AKP prof5 salleD ✓ → RES hp_restant AKP: 6→3
            //   14/05 HP2-BDD-AKP prof4 salleC ✓ → BDD hp_restant AKP: 3→0
            //   15/05 HP2-ALG-AKP prof4 salleC ✗ ABSENT (étudiants présents) → tpe_dyn ALG AKP: 3→0
            //   16/05 HP2-RES-AKP prof5 salleD ✓ → RES hp_restant AKP: 3→0
            //   21/05 TPE-BDD-AKP salleC (BDD AKP hp_restant=0 ✓)
            //   22/05 HPratt-ALG-AKP prof4 salleC ✓ → ALG hp_restant AKP: 3→0
            //   23/05 TPE-RES-AKP salleD (RES AKP hp_restant=0 ✓)

            $seanceHP1BDD_AKP = DB::table('seances')->insertGetId([
                'matiere_id'             => $mBDD,
                'salle_id'               => $salleC,
                'professeur_id'          => $prof4,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-07 08:00:00',
                'fin'                    => '2026-05-07 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-05-07 07:52:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 4,
                'cloture_validee_at'     => '2026-05-07 11:05:00',
                'cloture_validee_par'    => $prof4,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceHP1ALG_AKP = DB::table('seances')->insertGetId([
                'matiere_id'             => $mALG,
                'salle_id'               => $salleC,
                'professeur_id'          => $prof4,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-08 08:00:00',
                'fin'                    => '2026-05-08 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-05-08 08:03:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 5,
                'cloture_validee_at'     => '2026-05-08 11:07:00',
                'cloture_validee_par'    => $prof4,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceHP1RES_AKP = DB::table('seances')->insertGetId([
                'matiere_id'             => $mRES,
                'salle_id'               => $salleD,
                'professeur_id'          => $prof5,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-09 08:00:00',
                'fin'                    => '2026-05-09 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-05-09 08:00:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 4,
                'cloture_validee_at'     => '2026-05-09 11:10:00',
                'cloture_validee_par'    => $prof5,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceHP2BDD_AKP = DB::table('seances')->insertGetId([
                'matiere_id'             => $mBDD,
                'salle_id'               => $salleC,
                'professeur_id'          => $prof4,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-14 08:00:00',
                'fin'                    => '2026-05-14 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-05-14 07:58:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 3,
                'cloture_validee_at'     => '2026-05-14 11:12:00',
                'cloture_validee_par'    => $prof4,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            // HP2-ALG AKP — 15/05 ABSENT — professeur absent mais 3 étudiants sont venus
            $seanceHP2ALG_AKP = DB::table('seances')->insertGetId([
                'matiere_id'             => $mALG,
                'salle_id'               => $salleC,
                'professeur_id'          => $prof4,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-15 08:00:00',
                'fin'                    => '2026-05-15 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => null,
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => null,
                'cloture_validee_at'     => null,
                'cloture_validee_par'    => null,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceHP2RES_AKP = DB::table('seances')->insertGetId([
                'matiere_id'             => $mRES,
                'salle_id'               => $salleD,
                'professeur_id'          => $prof5,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-16 08:00:00',
                'fin'                    => '2026-05-16 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-05-16 08:05:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 4,
                'cloture_validee_at'     => '2026-05-16 11:08:00',
                'cloture_validee_par'    => $prof5,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceTPEBDD_AKP = DB::table('seances')->insertGetId([
                'matiere_id'             => $mBDD,
                'salle_id'               => $salleC,
                'professeur_id'          => null,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-21 08:00:00',
                'fin'                    => '2026-05-21 11:00:00',
                'type'                   => 'TPE',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => null,
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => null,
                'cloture_validee_at'     => '2026-05-21 11:00:00',
                'cloture_validee_par'    => null,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceHPrattALG_AKP = DB::table('seances')->insertGetId([
                'matiere_id'             => $mALG,
                'salle_id'               => $salleC,
                'professeur_id'          => $prof4,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-22 08:00:00',
                'fin'                    => '2026-05-22 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-05-22 07:59:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 4,
                'cloture_validee_at'     => '2026-05-22 11:05:00',
                'cloture_validee_par'    => $prof4,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            $seanceTPERES_AKP = DB::table('seances')->insertGetId([
                'matiere_id'             => $mRES,
                'salle_id'               => $salleD,
                'professeur_id'          => null,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-05-23 08:00:00',
                'fin'                    => '2026-05-23 11:00:00',
                'type'                   => 'TPE',
                'statut'                 => 'terminee',
                'is_inter_centre'        => false,
                'est_composition'        => false,
                'heure_scan_professeur'  => null,
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => null,
                'cloture_validee_at'     => '2026-05-23 11:00:00',
                'cloture_validee_par'    => null,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            // MCA Akpakpa — état final (SIL matières)
            DB::table('matiere_centre_annee')
                ->where('matiere_id', $mBDD)->where('centre_id', $cAkp)->where('annee_scolaire_id', $a2526)
                ->update(['hp_restant' => 0, 'tpe_dynamique' => 3]);
            DB::table('matiere_centre_annee')
                ->where('matiere_id', $mALG)->where('centre_id', $cAkp)->where('annee_scolaire_id', $a2526)
                ->update(['hp_restant' => 0, 'tpe_dynamique' => 0]);  // 1 absence → pénalité TPE
            DB::table('matiere_centre_annee')
                ->where('matiere_id', $mRES)->where('centre_id', $cAkp)->where('annee_scolaire_id', $a2526)
                ->update(['hp_restant' => 0, 'tpe_dynamique' => 3]);

            // ══════════════════════════════════════════════════════════════════════
            // === SEANCES INTER-CENTRES (is_inter_centre = true) ===
            // ══════════════════════════════════════════════════════════════════════
            // Scénario : les étudiants GEER d'Akpakpa viennent à Gbégamey pour
            // ELEC-GEER (équipement spécifique uniquement disponible à Gbégamey).
            // prof2 (Gbégamey, habilité ELEC-GEER) enseigne dans salleA.
            //
            //   13/06 INTER1-ELEC: salleA (GBE), prof2, optGEER_AKP, présent ✓
            //          → ELEC-GEER AKP hp_restant: 6→3
            //   16/06 INTER2-ELEC: salleA (GBE), prof2, optGEER_AKP, ABSENT ✗
            //          → étudiants AKP sont venus à Gbégamey mais prof absent
            //          → ELEC-GEER AKP tpe_dyn: 3→0 (pénalité vases communicants)
            //   20/06 INTER3-BDD : salleC (AKP), prof1 (GBE va à Akpakpa),
            //          optSIL + optSIL_AKP — séance mixte des deux centres → planifiée

            $seanceINTER1 = DB::table('seances')->insertGetId([
                'matiere_id'             => $mELEC,
                'salle_id'               => $salleA,       // Gbégamey
                'professeur_id'          => $prof2,        // prof Gbégamey
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-06-13 08:00:00',
                'fin'                    => '2026-06-13 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => true,           // ← étudiants AKP dans salle GBE
                'est_composition'        => false,
                'heure_scan_professeur'  => '2026-06-13 07:56:00',
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => 3,
                'cloture_validee_at'     => '2026-06-13 11:15:00',
                'cloture_validee_par'    => $prof2,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            // INTER2 — prof absent, mais les étudiants AKP ont fait le déplacement à Gbégamey
            $seanceINTER2 = DB::table('seances')->insertGetId([
                'matiere_id'             => $mELEC,
                'salle_id'               => $salleA,       // Gbégamey
                'professeur_id'          => $prof2,
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-06-16 08:00:00',
                'fin'                    => '2026-06-16 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'terminee',
                'is_inter_centre'        => true,
                'est_composition'        => false,
                'heure_scan_professeur'  => null,           // prof absent
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => null,
                'cloture_validee_at'     => null,
                'cloture_validee_par'    => null,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            // INTER3 — séance mixte planifiée : prof1 (GBE) enseigne à Akpakpa
            // pour les deux groupes SIL (GBE + AKP) ensemble
            $seanceINTER3 = DB::table('seances')->insertGetId([
                'matiere_id'             => $mBDD,
                'salle_id'               => $salleC,       // Akpakpa
                'professeur_id'          => $prof1,        // prof de Gbégamey se déplace
                'annee_scolaire_id'      => $a2526,
                'debut'                  => '2026-06-20 08:00:00',
                'fin'                    => '2026-06-20 11:00:00',
                'type'                   => 'HP',
                'statut'                 => 'planifiee',
                'is_inter_centre'        => true,           // ← options des deux centres
                'est_composition'        => false,
                'heure_scan_professeur'  => null,
                'heure_debut_pause'      => null,
                'heure_fin_pause'        => null,
                'durees_pauses_minutes'  => 0,
                'nb_presents_valide'     => null,
                'cloture_validee_at'     => null,
                'cloture_validee_par'    => null,
                'emploi_du_temps_id'     => null,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);

            // MCA Akpakpa — ELEC-GEER (depuis séances inter-centres)
            // INTER1 (scannée 3h) + INTER2 (absente 3h) → hp_restant=3, tpe_dyn=0
            DB::table('matiere_centre_annee')
                ->where('matiere_id', $mELEC)->where('centre_id', $cAkp)->where('annee_scolaire_id', $a2526)
                ->update(['hp_restant' => 3, 'tpe_dynamique' => 0]);

            // === OPTION_SEANCE ===
            // Gbégamey SIL sessions
            DB::table('option_seance')->insert([
                ['option_id' => $optSIL, 'seance_id' => $seanceHP1BDD],
                ['option_id' => $optSIL, 'seance_id' => $seanceHP1ALG],
                ['option_id' => $optSIL, 'seance_id' => $seanceHP1RES],
                ['option_id' => $optSIL, 'seance_id' => $seanceHP2BDD],
                ['option_id' => $optSIL, 'seance_id' => $seanceHP2ALG],
                ['option_id' => $optSIL, 'seance_id' => $seanceHP2RES],
                ['option_id' => $optSIL, 'seance_id' => $seanceTPEBDD],
                ['option_id' => $optSIL, 'seance_id' => $seanceHPrattALG],
                ['option_id' => $optSIL, 'seance_id' => $seanceTPERES],
                // Akpakpa SIL sessions
                ['option_id' => $optSIL_AKP, 'seance_id' => $seanceHP1BDD_AKP],
                ['option_id' => $optSIL_AKP, 'seance_id' => $seanceHP1ALG_AKP],
                ['option_id' => $optSIL_AKP, 'seance_id' => $seanceHP1RES_AKP],
                ['option_id' => $optSIL_AKP, 'seance_id' => $seanceHP2BDD_AKP],
                ['option_id' => $optSIL_AKP, 'seance_id' => $seanceHP2ALG_AKP],
                ['option_id' => $optSIL_AKP, 'seance_id' => $seanceHP2RES_AKP],
                ['option_id' => $optSIL_AKP, 'seance_id' => $seanceTPEBDD_AKP],
                ['option_id' => $optSIL_AKP, 'seance_id' => $seanceHPrattALG_AKP],
                ['option_id' => $optSIL_AKP, 'seance_id' => $seanceTPERES_AKP],
                // Inter-centres
                ['option_id' => $optGEER_AKP, 'seance_id' => $seanceINTER1],    // AKP GEER → GBE
                ['option_id' => $optGEER_AKP, 'seance_id' => $seanceINTER2],    // AKP GEER → GBE (absent)
                ['option_id' => $optSIL,       'seance_id' => $seanceINTER3],   // GBE SIL → AKP
                ['option_id' => $optSIL_AKP,   'seance_id' => $seanceINTER3],   // AKP SIL → AKP (même salle)
            ]);

            // === PRESENCES Gbégamey SIL ===
            // 5 étudiants × 9 séances = 45 présences
            // HP2-ALG GBE (prof absent) : tous absents (aucun cours n'a eu lieu)
            $seancesDatesGBE = [
                $seanceHP1BDD    => ['2026-05-26 08:00:00', '2026-05-26 11:00:00', true],
                $seanceHP1ALG    => ['2026-05-27 08:00:00', '2026-05-27 11:00:00', true],
                $seanceHP1RES    => ['2026-05-28 08:00:00', '2026-05-28 11:00:00', true],
                $seanceHP2BDD    => ['2026-06-02 08:00:00', '2026-06-02 11:00:00', true],
                $seanceHP2ALG    => ['2026-06-03 08:00:00', '2026-06-03 11:00:00', false],
                $seanceHP2RES    => ['2026-06-04 08:00:00', '2026-06-04 11:00:00', true],
                $seanceTPEBDD    => ['2026-06-09 08:00:00', '2026-06-09 11:00:00', true],
                $seanceHPrattALG => ['2026-06-10 08:00:00', '2026-06-10 11:00:00', true],
                $seanceTPERES    => ['2026-06-11 08:00:00', '2026-06-11 11:00:00', true],
            ];

            $statutCycle = ['present', 'present', 'absent', 'present', 'presence_insuffisante'];
            $presRows    = [];

            foreach ($seancesDatesGBE as $sId => [$debStr, $finStr, $profPresent]) {
                $deb = Carbon::parse($debStr);
                $fin = Carbon::parse($finStr);
                foreach ($inscSIL as $idx => $inscId) {
                    if (!$profPresent) {
                        $presRows[] = ['seance_id' => $sId, 'inscription_id' => $inscId,
                            'heure_entree' => null, 'heure_sortie_definitive' => null,
                            'statut' => 'absent', 'created_at' => $now, 'updated_at' => $now];
                        continue;
                    }
                    $statut  = $statutCycle[$idx];
                    $hEntree = null;
                    $hSortie = null;
                    if ($statut === 'present') {
                        $offsetMin = [0 => 0, 1 => 5, 3 => 15][$idx] ?? 0;
                        $hEntree   = $deb->copy()->addMinutes($offsetMin)->toDateTimeString();
                        $hSortie   = $fin->toDateTimeString();
                    } elseif ($statut === 'presence_insuffisante') {
                        $hEntree = $deb->copy()->addMinutes(20)->toDateTimeString();
                        $hSortie = $fin->copy()->subMinutes(20)->toDateTimeString();
                    }
                    $presRows[] = ['seance_id' => $sId, 'inscription_id' => $inscId,
                        'heure_entree' => $hEntree, 'heure_sortie_definitive' => $hSortie,
                        'statut' => $statut, 'created_at' => $now, 'updated_at' => $now];
                }
            }

            // === PRESENCES Akpakpa SIL ===
            // HP2-ALG AKP (prof absent) : 3 étudiants venus malgré l'absence du prof
            $seancesDatesAKP = [
                $seanceHP1BDD_AKP     => ['2026-05-07 08:00:00', '2026-05-07 11:00:00', 'present'],
                $seanceHP1ALG_AKP     => ['2026-05-08 08:00:00', '2026-05-08 11:00:00', 'present'],
                $seanceHP1RES_AKP     => ['2026-05-09 08:00:00', '2026-05-09 11:00:00', 'present'],
                $seanceHP2BDD_AKP     => ['2026-05-14 08:00:00', '2026-05-14 11:00:00', 'present'],
                $seanceHP2ALG_AKP     => ['2026-05-15 08:00:00', '2026-05-15 11:00:00', 'prof_absent'],
                $seanceHP2RES_AKP     => ['2026-05-16 08:00:00', '2026-05-16 11:00:00', 'present'],
                $seanceTPEBDD_AKP     => ['2026-05-21 08:00:00', '2026-05-21 11:00:00', 'present'],
                $seanceHPrattALG_AKP  => ['2026-05-22 08:00:00', '2026-05-22 11:00:00', 'present'],
                $seanceTPERES_AKP     => ['2026-05-23 08:00:00', '2026-05-23 11:00:00', 'present'],
            ];

            foreach ($seancesDatesAKP as $sId => [$debStr, $finStr, $mode]) {
                $deb = Carbon::parse($debStr);
                $fin = Carbon::parse($finStr);
                foreach ($inscSIL_AKP as $idx => $inscId) {
                    if ($mode === 'prof_absent') {
                        // 3 premiers étudiants sont venus (et repartis après ~1h30 d'attente)
                        if ($idx < 3) {
                            $presRows[] = ['seance_id' => $sId, 'inscription_id' => $inscId,
                                'heure_entree'            => $deb->copy()->addMinutes($idx * 5)->toDateTimeString(),
                                'heure_sortie_definitive' => $deb->copy()->addMinutes(90 + $idx * 10)->toDateTimeString(),
                                'statut' => 'presence_insuffisante',
                                'created_at' => $now, 'updated_at' => $now];
                        } else {
                            $presRows[] = ['seance_id' => $sId, 'inscription_id' => $inscId,
                                'heure_entree' => null, 'heure_sortie_definitive' => null,
                                'statut' => 'absent', 'created_at' => $now, 'updated_at' => $now];
                        }
                        continue;
                    }
                    $statut  = $statutCycle[$idx];
                    $hEntree = null;
                    $hSortie = null;
                    if ($statut === 'present') {
                        $offsetMin = [0 => 0, 1 => 5, 3 => 15][$idx] ?? 0;
                        $hEntree   = $deb->copy()->addMinutes($offsetMin)->toDateTimeString();
                        $hSortie   = $fin->toDateTimeString();
                    } elseif ($statut === 'presence_insuffisante') {
                        $hEntree = $deb->copy()->addMinutes(20)->toDateTimeString();
                        $hSortie = $fin->copy()->subMinutes(20)->toDateTimeString();
                    }
                    $presRows[] = ['seance_id' => $sId, 'inscription_id' => $inscId,
                        'heure_entree' => $hEntree, 'heure_sortie_definitive' => $hSortie,
                        'statut' => $statut, 'created_at' => $now, 'updated_at' => $now];
                }
            }

            // === PRESENCES INTER-CENTRES (séances ELEC-GEER pour étudiants AKP) ===
            // INTER1 (prof présent) : cycle normal
            $debINTER1 = Carbon::parse('2026-06-13 08:00:00');
            $finINTER1 = Carbon::parse('2026-06-13 11:00:00');
            foreach ($inscGEER_AKP as $idx => $inscId) {
                $statut  = $statutCycle[$idx];
                $hEntree = null;
                $hSortie = null;
                if ($statut === 'present') {
                    $offsetMin = [0 => 0, 1 => 5, 3 => 15][$idx] ?? 0;
                    $hEntree   = $debINTER1->copy()->addMinutes($offsetMin)->toDateTimeString();
                    $hSortie   = $finINTER1->toDateTimeString();
                } elseif ($statut === 'presence_insuffisante') {
                    $hEntree = $debINTER1->copy()->addMinutes(20)->toDateTimeString();
                    $hSortie = $finINTER1->copy()->subMinutes(20)->toDateTimeString();
                }
                $presRows[] = ['seance_id' => $seanceINTER1, 'inscription_id' => $inscId,
                    'heure_entree' => $hEntree, 'heure_sortie_definitive' => $hSortie,
                    'statut' => $statut, 'created_at' => $now, 'updated_at' => $now];
            }

            // INTER2 (prof absent) : 3 étudiants AKP ont fait le déplacement à Gbégamey
            $debINTER2 = Carbon::parse('2026-06-16 08:00:00');
            foreach ($inscGEER_AKP as $idx => $inscId) {
                if ($idx < 3) {
                    $presRows[] = ['seance_id' => $seanceINTER2, 'inscription_id' => $inscId,
                        'heure_entree'            => $debINTER2->copy()->addMinutes($idx * 8)->toDateTimeString(),
                        'heure_sortie_definitive' => $debINTER2->copy()->addMinutes(75 + $idx * 5)->toDateTimeString(),
                        'statut' => 'presence_insuffisante',
                        'created_at' => $now, 'updated_at' => $now];
                } else {
                    $presRows[] = ['seance_id' => $seanceINTER2, 'inscription_id' => $inscId,
                        'heure_entree' => null, 'heure_sortie_definitive' => null,
                        'statut' => 'absent', 'created_at' => $now, 'updated_at' => $now];
                }
            }

            DB::table('presences')->insert($presRows);

            // === SORTIES_TEMPORAIRES ===
            $presHP1BDD_stu0 = DB::table('presences')
                ->where('seance_id', $seanceHP1BDD)->where('inscription_id', $inscSIL[0])->value('id');
            $presHP1BDD_stu1 = DB::table('presences')
                ->where('seance_id', $seanceHP1BDD)->where('inscription_id', $inscSIL[1])->value('id');

            DB::table('sorties_temporaires')->insert([
                'presence_id'     => $presHP1BDD_stu0,
                'heure_sortie'    => '2026-05-26 09:00:00',
                'heure_rentree'   => '2026-05-26 09:12:00',
                'duree_minutes'   => 12,
                'rentree_refusee' => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
            DB::table('sorties_temporaires')->insert([
                'presence_id'     => $presHP1BDD_stu1,
                'heure_sortie'    => '2026-05-26 09:30:00',
                'heure_rentree'   => null,
                'duree_minutes'   => null,
                'rentree_refusee' => true,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            // Sortie temporaire sur HP1-BDD-AKP (premier étudiant AKP)
            $presHP1BDD_AKP_stu0 = DB::table('presences')
                ->where('seance_id', $seanceHP1BDD_AKP)->where('inscription_id', $inscSIL_AKP[0])->value('id');
            DB::table('sorties_temporaires')->insert([
                'presence_id'     => $presHP1BDD_AKP_stu0,
                'heure_sortie'    => '2026-05-07 09:15:00',
                'heure_rentree'   => '2026-05-07 09:25:00',
                'duree_minutes'   => 10,
                'rentree_refusee' => false,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

        });

        $this->command->info('GASA-ERP — Seed complet effectué avec succès !');
        $this->command->table(
            ['Email', 'Mot de passe', 'Rôle', 'Centre'],
            [
                ['admin@gasa.bj',           'Password123!', 'ROLE_ADMIN',                 '—'],
                ['resp.gbegamey@gasa.bj',   'Password123!', 'ROLE_RESPONSABLE_CENTRE',    'Gbégamey'],
                ['resp.akpakpa@gasa.bj',    'Password123!', 'ROLE_RESPONSABLE_CENTRE',    'Akpakpa'],
                ['degboe@gasa.bj',          'Password123!', 'ROLE_PROFESSEUR (BDD, ALG)', 'Gbégamey'],
                ['akponna@gasa.bj',         'Password123!', 'ROLE_PROFESSEUR (RES, ELEC)','Gbégamey'],
                ['laleye@gasa.bj',          'Password123!', 'ROLE_PROFESSEUR (ENR, AUTO)','Gbégamey'],
                ['gnimavo@gasa.bj',         'Password123!', 'ROLE_PROFESSEUR (BDD, ALG)', 'Akpakpa'],
                ['kpanou@gasa.bj',          'Password123!', 'ROLE_PROFESSEUR (RES, TRANS)','Akpakpa'],
            ]
        );
        $this->command->table(
            ['Date', 'Type', 'Matière', 'Prof', 'Centre salle', 'Statut', 'Note'],
            [
                // Gbégamey
                ['26/05', 'HP',  'BDD-SIL',   'DEGBOE',  'GBE', 'terminee', 'présent → hp_restant BDD GBE 6→3'],
                ['27/05', 'HP',  'ALG-SIL',   'DEGBOE',  'GBE', 'terminee', 'présent → hp_restant ALG GBE 6→3'],
                ['28/05', 'HP',  'RES-SIL',   'AKPONNA', 'GBE', 'terminee', 'présent → hp_restant RES GBE 6→3'],
                ['02/06', 'HP',  'BDD-SIL',   'DEGBOE',  'GBE', 'terminee', 'présent → hp_restant BDD GBE 3→0'],
                ['03/06', 'HP',  'ALG-SIL',   'DEGBOE',  'GBE', 'terminee', 'ABSENT  → tpe_dyn ALG GBE 3→0'],
                ['04/06', 'HP',  'RES-SIL',   'AKPONNA', 'GBE', 'terminee', 'présent → hp_restant RES GBE 3→0'],
                ['09/06', 'TPE', 'BDD-SIL',   '—',       'GBE', 'terminee', 'HP complets → TPE autorisé'],
                ['10/06', 'HP',  'ALG-SIL',   'DEGBOE',  'GBE', 'terminee', 'rattrapage → hp_restant ALG GBE 3→0'],
                ['11/06', 'TPE', 'RES-SIL',   '—',       'GBE', 'terminee', 'HP complets → TPE autorisé'],
                // Akpakpa
                ['07/05', 'HP',  'BDD-SIL',   'GNIMAVO', 'AKP', 'terminee', 'présent → hp_restant BDD AKP 6→3'],
                ['08/05', 'HP',  'ALG-SIL',   'GNIMAVO', 'AKP', 'terminee', 'présent → hp_restant ALG AKP 6→3'],
                ['09/05', 'HP',  'RES-SIL',   'KPANOU',  'AKP', 'terminee', 'présent → hp_restant RES AKP 6→3'],
                ['14/05', 'HP',  'BDD-SIL',   'GNIMAVO', 'AKP', 'terminee', 'présent → hp_restant BDD AKP 3→0'],
                ['15/05', 'HP',  'ALG-SIL',   'GNIMAVO', 'AKP', 'terminee', 'ABSENT (3 étudiants venus) → tpe_dyn ALG AKP 3→0'],
                ['16/05', 'HP',  'RES-SIL',   'KPANOU',  'AKP', 'terminee', 'présent → hp_restant RES AKP 3→0'],
                ['21/05', 'TPE', 'BDD-SIL',   '—',       'AKP', 'terminee', 'HP complets → TPE autorisé'],
                ['22/05', 'HP',  'ALG-SIL',   'GNIMAVO', 'AKP', 'terminee', 'rattrapage → hp_restant ALG AKP 3→0'],
                ['23/05', 'TPE', 'RES-SIL',   '—',       'AKP', 'terminee', 'HP complets → TPE autorisé'],
                // Inter-centres
                ['13/06', 'HP',  'ELEC-GEER', 'AKPONNA', 'GBE', 'terminee', 'INTER: étudiants AKP→GBE, prof présent → hp_restant ELEC AKP 6→3'],
                ['16/06', 'HP',  'ELEC-GEER', 'AKPONNA', 'GBE', 'terminee', 'INTER: étudiants AKP→GBE, PROF ABSENT (3 étudiants venus) → tpe_dyn 3→0'],
                ['20/06', 'HP',  'BDD-SIL',   'DEGBOE',  'AKP', 'planifiee','INTER mixte: prof GBE→AKP, optSIL+optSIL_AKP ensemble'],
            ]
        );
    }
}
