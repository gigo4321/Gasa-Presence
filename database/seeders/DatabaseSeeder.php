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
            // hp_initial=6h / tpe_initial=3h pour les matières SIL-L1 utilisées en séances
            // (2 séances HP de 3h = quota HP complet → TPE débloqué)
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

            // IDs des matières SIL-L1 utilisées dans les séances
            $mBDD  = DB::table('matieres')->where('code', 'BDD-SIL')->where('niveau_id', $nSIL_L1)->value('id');
            $mALG  = DB::table('matieres')->where('code', 'ALG-SIL')->where('niveau_id', $nSIL_L1)->value('id');
            $mRES  = DB::table('matieres')->where('code', 'RES-SIL')->where('niveau_id', $nSIL_L1)->value('id');
            // IDs GEER-L1 pour matiere_professeur de prof3
            $mENR  = DB::table('matieres')->where('code', 'ENR-GEER')->where('niveau_id', $nGEER_L1)->value('id');
            $mAUTO = DB::table('matieres')->where('code', 'AUTO-GEER')->where('niveau_id', $nGEER_L1)->value('id');
            // ID ELEC-GEER-L1 pour matiere_professeur de prof2
            $mELEC = DB::table('matieres')->where('code', 'ELEC-GEER')->where('niveau_id', $nGEER_L1)->value('id');

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

            // 3 professeurs rattachés à Gbégamey, chacun avec badge_uid unique
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

            // === MATIERE_PROFESSEUR ===
            // Chaque prof enseigne 2 matières (habilitation, pas nécessairement les mêmes que les séances)
            DB::table('matiere_professeur')->insert([
                ['user_id' => $prof1, 'matiere_id' => $mBDD],   // Prof1 → BDD-SIL-L1
                ['user_id' => $prof1, 'matiere_id' => $mALG],   // Prof1 → ALG-SIL-L1
                ['user_id' => $prof2, 'matiere_id' => $mRES],   // Prof2 → RES-SIL-L1
                ['user_id' => $prof2, 'matiere_id' => $mELEC],  // Prof2 → ELEC-GEER-L1
                ['user_id' => $prof3, 'matiere_id' => $mENR],   // Prof3 → ENR-GEER-L1
                ['user_id' => $prof3, 'matiere_id' => $mAUTO],  // Prof3 → AUTO-GEER-L1
            ]);

            // === OPTIONS (groupes-classes, 2 dans Gbégamey pour 2025-2026) ===
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

            // === ETUDIANTS (10, badge_uid unique pour chacun) ===
            $etudiantsDef = [
                ['ETU-2526-001', 'ADJOVI',   'Rosine',  'adjovi.rosine@etu.gasa.bj',    '+229 96001001', 'BADGE-ETU-001', '2003-04-12'],
                ['ETU-2526-002', 'BELLO',    'Kouamé',  'bello.kouame@etu.gasa.bj',     '+229 96001002', 'BADGE-ETU-002', '2003-07-23'],
                ['ETU-2526-003', 'CHABI',    'Farida',  'chabi.farida@etu.gasa.bj',     '+229 96001003', 'BADGE-ETU-003', '2002-11-05'],
                ['ETU-2526-004', 'DOSSOU',   'Alain',   'dossou.alain@etu.gasa.bj',     '+229 96001004', 'BADGE-ETU-004', '2003-01-17'],
                ['ETU-2526-005', 'EGUE',     'Serge',   'egue.serge@etu.gasa.bj',       '+229 96001005', 'BADGE-ETU-005', '2004-03-30'],
                ['ETU-2526-006', 'FAVI',     'Hélène',  'favi.helene@etu.gasa.bj',      '+229 96001006', 'BADGE-ETU-006', '2003-06-14'],
                ['ETU-2526-007', 'GANDJI',   'Pierre',  'gandji.pierre@etu.gasa.bj',    '+229 96001007', 'BADGE-ETU-007', '2002-09-08'],
                ['ETU-2526-008', 'HOUETO',   'Marlène', 'houeto.marlene@etu.gasa.bj',   '+229 96001008', 'BADGE-ETU-008', '2003-12-21'],
                ['ETU-2526-009', 'IDOSSOU',  'Gilles',  'idossou.gilles@etu.gasa.bj',   '+229 96001009', 'BADGE-ETU-009', '2004-02-03'],
                ['ETU-2526-010', 'JOHNSON',  'Laïda',   'johnson.laida@etu.gasa.bj',    '+229 96001010', 'BADGE-ETU-010', '2003-08-16'],
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
            // Étudiants 0-4 → optSIL ; Étudiants 5-9 → optRIT
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

            // === SALLES (naming convention GASA : [étage]E[num]) ===
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
            $salleC = DB::table('salles')->insertGetId([
                'nom'        => '1E1',
                'capacite'   => 40,
                'type'       => 'Salle de cours',
                'centre_id'  => $cAkp,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach (['1E2', '2E1', '2E2'] as $nomSalle) {
                DB::table('salles')->insert([
                    'nom' => $nomSalle, 'capacite' => 40, 'type' => 'Salle de cours',
                    'centre_id' => $cAkp, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }

            // === EQUIPEMENTS : tableau + scanner RFID pour chaque salle ===
            DB::table('equipements')->insert([
                ['nom' => 'Tableau blanc',  'type_materiel' => 'Mobilier',     'numero_serie' => 'TB-GBE-1E1-01',  'etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleA, 'created_at' => $now, 'updated_at' => $now],
                ['nom' => 'Vidéoprojecteur','type_materiel' => 'Électronique', 'numero_serie' => 'VP-GBE-1E1-01',  'etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleA, 'created_at' => $now, 'updated_at' => $now],
                ['nom' => 'Tableau blanc',  'type_materiel' => 'Mobilier',     'numero_serie' => 'TB-GBE-1E2-01',  'etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleB, 'created_at' => $now, 'updated_at' => $now],
                ['nom' => 'Tableau noir',   'type_materiel' => 'Mobilier',     'numero_serie' => 'TN-AKP-1E1-01',  'etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleC, 'created_at' => $now, 'updated_at' => $now],
                ['nom' => 'Climatiseur',    'type_materiel' => 'Électroménager','numero_serie'=> 'CLIM-AKP-1E1-01','etat' => 'bon', 'quantite' => 1, 'salle_id' => $salleC, 'created_at' => $now, 'updated_at' => $now],
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

            // === MATIERE_CENTRE_ANNEE ===
            // Initialiser hp_restant=hp_initial et tpe_dynamique=tpe_initial pour chaque (matière × centre)
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

            // === SEANCES ===
            // Règles respectées :
            //   • Durée minimum 3h (08:00-11:00) pour toutes les séances
            //   • HP terminés avant TPE pour chaque matière (hp_restant=0 requis)
            //   • Aucun chevauchement salle ni professeur sur le même créneau
            //   • Cas d'absence (HP2-ALG 03/06) avec vases communicants simulés dans MCA
            //
            // Chronologie et effet sur MCA (hp_initial=6, tpe_initial=3) :
            //   26/05 HP1-BDD prof1 salleA ✓ → BDD hp_restant: 6→3
            //   27/05 HP1-ALG prof1 salleA ✓ → ALG hp_restant: 6→3
            //   28/05 HP1-RES prof2 salleA ✓ → RES hp_restant: 6→3
            //   02/06 HP2-BDD prof1 salleA ✓ → BDD hp_restant: 3→0
            //   03/06 HP2-ALG prof1 salleA ✗ ABSENT → ALG hp_restant: inchangé (3), tpe_dyn: 3→0
            //   04/06 HP2-RES prof2 salleA ✓ → RES hp_restant: 3→0
            //   09/06 TPE-BDD prof1 salleB ✓ (BDD hp_restant=0 ✓)
            //   10/06 HPratt-ALG prof1 salleB ✓ (rattrapage) → ALG hp_restant: 3→0
            //   11/06 TPE-RES prof2 salleA ✓ (RES hp_restant=0 ✓)
            //
            // État final MCA Gbégamey :
            //   BDD → hp_restant=0, tpe_dynamique=3
            //   ALG → hp_restant=0, tpe_dynamique=0  (HP complets, TPE bloqué — pénalité absence)
            //   RES → hp_restant=0, tpe_dynamique=3

            // HP1-BDD — 26/05 présent
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

            // HP1-ALG — 27/05 présent
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

            // HP1-RES — 28/05 présent
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

            // HP2-BDD — 02/06 présent → BDD hp_restant: 3→0
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

            // HP2-ALG — 03/06 ABSENT (heure_scan_professeur=null)
            // Vases communicants : ALG hp_restant: 3+3=6, tpe_dynamique: max(0,3-3)=0
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

            // HP2-RES — 04/06 présent → RES hp_restant: 3→0
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

            // TPE-BDD — 09/06 (BDD hp_restant=0 ✓ → TPE autorisé, pas de professeur)
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

            // HPratt-ALG — 10/06 rattrapage HP → ALG hp_restant: 6→3
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

            // TPE-RES — 11/06 (RES hp_restant=0 ✓ → TPE autorisé, pas de professeur)
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


            // Mise à jour matiere_centre_annee : état final après toutes les séances terminées
            // hp_restant  = hp_initial  − Σ(heures scannées)
            // tpe_dynamique = tpe_initial − Σ(heures absences HP)
            DB::table('matiere_centre_annee')
                ->where('matiere_id', $mBDD)->where('centre_id', $cGbe)->where('annee_scolaire_id', $a2526)
                ->update(['hp_restant' => 0, 'tpe_dynamique' => 3]);  // 2×3h scannées → 0 restant ; 0 absence
            DB::table('matiere_centre_annee')
                ->where('matiere_id', $mALG)->where('centre_id', $cGbe)->where('annee_scolaire_id', $a2526)
                ->update(['hp_restant' => 0, 'tpe_dynamique' => 0]);  // HP1+HPratt scannées → 0 restant ; 1 absence 3h → tpe=0
            DB::table('matiere_centre_annee')
                ->where('matiere_id', $mRES)->where('centre_id', $cGbe)->where('annee_scolaire_id', $a2526)
                ->update(['hp_restant' => 0, 'tpe_dynamique' => 3]);  // 2×3h scannées → 0 restant ; 0 absence

            // === OPTION_SEANCE ===
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
            ]);

            // === PRESENCES ===
            // 5 étudiants SIL × 9 séances terminées = 45 présences
            // Cycle normal (prof présent) : present, present, absent, present, presence_insuffisante
            // HP2-ALG (prof absent) : tous les étudiants absents (aucun cours n'a eu lieu)
            $seancesDates = [
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
            $presRows = [];

            foreach ($seancesDates as $sId => [$debStr, $finStr, $profPresent]) {
                $deb = Carbon::parse($debStr);
                $fin = Carbon::parse($finStr);

                foreach ($inscSIL as $idx => $inscId) {
                    if (!$profPresent) {
                        $presRows[] = [
                            'seance_id'               => $sId,
                            'inscription_id'          => $inscId,
                            'heure_entree'            => null,
                            'heure_sortie_definitive' => null,
                            'statut'                  => 'absent',
                            'created_at'              => $now,
                            'updated_at'              => $now,
                        ];
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

                    $presRows[] = [
                        'seance_id'               => $sId,
                        'inscription_id'          => $inscId,
                        'heure_entree'            => $hEntree,
                        'heure_sortie_definitive' => $hSortie,
                        'statut'                  => $statut,
                        'created_at'              => $now,
                        'updated_at'              => $now,
                    ];
                }
            }
            DB::table('presences')->insert($presRows);

            // === SORTIES_TEMPORAIRES ===
            // Sur HP1-BDD (stu0=present heure_entree=08:00, stu1=present heure_entree=08:05)
            $presHP1BDD_stu0 = DB::table('presences')
                ->where('seance_id', $seanceHP1BDD)
                ->where('inscription_id', $inscSIL[0])
                ->value('id');
            $presHP1BDD_stu1 = DB::table('presences')
                ->where('seance_id', $seanceHP1BDD)
                ->where('inscription_id', $inscSIL[1])
                ->value('id');

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

        });

        $this->command->info('✅ GASA-ERP — Seed complet effectué avec succès !');
        $this->command->table(
            ['Email', 'Mot de passe', 'Rôle'],
            [
                ['admin@gasa.bj',           'Password123!', 'ROLE_ADMIN'],
                ['resp.gbegamey@gasa.bj',   'Password123!', 'ROLE_RESPONSABLE_CENTRE (Gbégamey)'],
                ['resp.akpakpa@gasa.bj',    'Password123!', 'ROLE_RESPONSABLE_CENTRE (Akpakpa)'],
                ['degboe@gasa.bj',          'Password123!', 'ROLE_PROFESSEUR — BDD-SIL, ALG-SIL'],
                ['akponna@gasa.bj',         'Password123!', 'ROLE_PROFESSEUR — RES-SIL, ELEC-GEER'],
                ['laleye@gasa.bj',          'Password123!', 'ROLE_PROFESSEUR — ENR-GEER, AUTO-GEER'],
            ]
        );
        $this->command->info('Séances test (toutes ≥ 3h) :');
        $this->command->table(
            ['Date', 'Type', 'Matière', 'Prof', 'Statut', 'Note'],
            [
                ['26/05', 'HP', 'BDD-SIL', 'DEGBOE',  'terminee', 'présent → hp_restant BDD 6→3'],
                ['27/05', 'HP', 'ALG-SIL', 'DEGBOE',  'terminee', 'présent → hp_restant ALG 6→3'],
                ['28/05', 'HP', 'RES-SIL', 'AKPONNA', 'terminee', 'présent → hp_restant RES 6→3'],
                ['02/06', 'HP', 'BDD-SIL', 'DEGBOE',  'terminee', 'présent → hp_restant BDD 3→0 ✓'],
                ['03/06', 'HP', 'ALG-SIL', 'DEGBOE',  'terminee', 'ABSENT  → hp_restant ALG 6, tpe_dyn 0'],
                ['04/06', 'HP', 'RES-SIL', 'AKPONNA', 'terminee', 'présent → hp_restant RES 3→0 ✓'],
                ['09/06', 'TPE','BDD-SIL', 'DEGBOE',  'terminee', 'HP complets → autorisé'],
                ['10/06', 'HP', 'ALG-SIL', 'DEGBOE',  'terminee', 'rattrapage → hp_restant ALG 6→3'],
                ['11/06', 'TPE','RES-SIL', 'AKPONNA', 'terminee', 'HP complets → autorisé'],
                ['17/06', 'HP', 'ALG-SIL', 'DEGBOE',  'planifiee','2e rattrapage (dashboard)'],
            ]
        );
    }
}
