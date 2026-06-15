<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ══════════════════════════════════════════════════════════════════
        // ANNÉES SCOLAIRES
        // ══════════════════════════════════════════════════════════════════
        DB::table('annees_scolaires')->insert([
            ['libelle'=>'2023-2024','date_debut'=>'2023-09-01','date_fin'=>'2024-07-31','active'=>false,'created_at'=>now(),'updated_at'=>now()],
            ['libelle'=>'2024-2025','date_debut'=>'2024-09-01','date_fin'=>'2025-07-31','active'=>false,'created_at'=>now(),'updated_at'=>now()],
            ['libelle'=>'2025-2026','date_debut'=>'2025-09-01','date_fin'=>'2026-07-31','active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
        $a2526 = DB::table('annees_scolaires')->where('libelle','2025-2026')->value('id');

        // ══════════════════════════════════════════════════════════════════
        // CENTRES
        // ══════════════════════════════════════════════════════════════════
        DB::table('centres')->insert([
            ['nom'=>'Centre de Gbégamey','ville'=>'Cotonou','created_at'=>now(),'updated_at'=>now()],
            ['nom'=>"Centre d'Akpakpa",'ville'=>'Cotonou','created_at'=>now(),'updated_at'=>now()],
            ['nom'=>'Centre de Porto-Novo','ville'=>'Porto-Novo','created_at'=>now(),'updated_at'=>now()],
            ['nom'=>'Centre de Calavi','ville'=>'Calavi','created_at'=>now(),'updated_at'=>now()],
        ]);
        $cGbe = DB::table('centres')->where('nom','Centre de Gbégamey')->value('id');
        $cAkp = DB::table('centres')->where('nom',"Centre d'Akpakpa")->value('id');
        $cPnv = DB::table('centres')->where('nom','Centre de Porto-Novo')->value('id');
        $cCal = DB::table('centres')->where('nom','Centre de Calavi')->value('id');

        // ══════════════════════════════════════════════════════════════════
        // FILIÈRES, OPTIONS PÉDAGOGIQUES & NIVEAUX
        // ══════════════════════════════════════════════════════════════════
        DB::table('filieres')->insert([
            ['nom'=>'Génie Électrique','code'=>'GE','archive'=>false,'created_at'=>now(),'updated_at'=>now()],
        ]);
        $fGE = DB::table('filieres')->where('code','GE')->value('id');

        $options_ped = [
            ['nom'=>'Système Informatique',              'code'=>'SI',      'filiere_id'=>$fGE],
            ['nom'=>'Système Informatique et Logiciel',  'code'=>'SIL',     'filiere_id'=>$fGE],
            ['nom'=>'Réseaux et Ingénierie Télécom',     'code'=>'RIT',     'filiere_id'=>$fGE],
            ['nom'=>'Électricité Réseau',                'code'=>'ER',      'filiere_id'=>$fGE],
            ['nom'=>'Biotechnologie',                    'code'=>'BIOTECH', 'filiere_id'=>$fGE],
            ['nom'=>'Sciences de Gestion',               'code'=>'SG',      'filiere_id'=>$fGE],
        ];
        foreach ($options_ped as $op) {
            DB::table('filiere_options')->insert(array_merge($op, ['archive'=>false,'created_at'=>now(),'updated_at'=>now()]));
        }
        $foSI  = DB::table('filiere_options')->where('code','SI')->value('id');
        $foSIL = DB::table('filiere_options')->where('code','SIL')->value('id');
        $foRIT = DB::table('filiere_options')->where('code','RIT')->value('id');
        $foER  = DB::table('filiere_options')->where('code','ER')->value('id');
        $foBIO = DB::table('filiere_options')->where('code','BIOTECH')->value('id');
        $foSG  = DB::table('filiere_options')->where('code','SG')->value('id');

        $niveaux_data = [
            [$foSI,  'GE1','L1',1], [$foSIL,'GE1','L1',1], [$foRIT,'GE1','L1',1],
            [$foER,  'GE1','L1',1], [$foBIO,'GE1','L1',1], [$foSG, 'GE1','L1',1],
        ];
        foreach ($niveaux_data as [$fo,$lib,$code,$ordre]) {
            DB::table('niveaux')->insert(['libelle'=>$lib,'code'=>$code,'ordre'=>$ordre,'filiere_option_id'=>$fo,'archive'=>false,'created_at'=>now(),'updated_at'=>now()]);
        }
        $nSI  = DB::table('niveaux')->where('filiere_option_id',$foSI)->value('id');
        $nSIL = DB::table('niveaux')->where('filiere_option_id',$foSIL)->value('id');
        $nRIT = DB::table('niveaux')->where('filiere_option_id',$foRIT)->value('id');
        $nER  = DB::table('niveaux')->where('filiere_option_id',$foER)->value('id');
        $nBIO = DB::table('niveaux')->where('filiere_option_id',$foBIO)->value('id');
        $nSG  = DB::table('niveaux')->where('filiere_option_id',$foSG)->value('id');

        // ══════════════════════════════════════════════════════════════════
        // MATIÈRES  (extraites des emplois du temps réels GASA/GBÉGAMEY)
        // ══════════════════════════════════════════════════════════════════
        $matieres = [
            // ── Communes GE1 – Semestre 1 (portées par SI)
            ['INFO-FOND',  'Informatique Fondamentale',              1, 30, 15, $fGE, $nSI],
            ['SYS-NUM',    'Systèmes de Numération',                 1, 20, 10, $fGE, $nSI],
            ['REV-MATHS',  'Révisions Mathématiques',                1, 20,  0, $fGE, $nSI],
            ['TEEO',       'Théorie des Éléments Électroniques',     1, 20, 10, $fGE, $nSI],
            ['ATO',        'Analyse et Traitement des Oscillations', 1, 15,  5, $fGE, $nSI],
            ['ELN01',      'Électronique 01-02',                     1, 30, 10, $fGE, $nSI],
            ['ANGLAIS',    'Anglais',                                1, 15,  5, $fGE, $nSI],
            ['ELC',        'Électricité',                            1, 20,  5, $fGE, $nSI],
            ['TP-COMP',    'TP Composants',                          1, 20,  0, $fGE, $nSI],
            // ── Communes GE1 – Semestre 2 (portées par SI)
            ['ANALYSE',    'Analyse Mathématique',                   2, 30, 10, $fGE, $nSI],
            ['STAT',       'Statistiques & Probabilités',            2, 20, 10, $fGE, $nSI],
            ['ELN03',      'Électronique 03',                        2, 25, 10, $fGE, $nSI],
            ['ALP',        'Algorithmique et Programmation',         2, 30, 15, $fGE, $nSI],
            ['PHYSIQUE',   'Physique',                               2, 30, 10, $fGE, $nSI],
            ['MECA-GEN',   'Mécanique Générale',                     2, 20,  5, $fGE, $nSI],
            ['ELEC-BAT',   'Électricité Bâtiment',                   2, 20,  5, $fGE, $nSI],
            ['TECHNOS1',   'Technologies et Schémas 1',              2, 20,  5, $fGE, $nSI],
            ['ELA01',      'Électronique Analogique 01',             2, 25, 10, $fGE, $nSI],
            ['AUTO',       'Automatique',                            2, 20,  5, $fGE, $nSI],
            ['TP-RM',      'TP Réseaux et Mesures',                  2, 20,  0, $fGE, $nSI],
            ['LANG-C',     'Langage C',                              2, 25, 15, $fGE, $nSI],
            ['DT',         'Dessin Technique',                       2, 15,  5, $fGE, $nSI],
            ['TP-PY',      'TP Python',                              2, 15,  0, $fGE, $nSI],
            // ── Spécifiques SIL
            ['ENV-LOG1',   'Environnement Logiciel 1',               1, 20,  5, $fGE, $nSIL],
            ['GRAPHES',    'Théorie des Graphes',                    1, 20, 10, $fGE, $nSIL],
            ['MATHS-F',    'Mathématiques Fines',                    1, 20,  5, $fGE, $nSIL],
            ['PROG-WEB1',  'Programmation Web 1',                    2, 25, 10, $fGE, $nSIL],
            ['BDD',        'Bases de Données et SGBDR',              2, 25, 15, $fGE, $nSIL],
            ['MERISE1',    'MERISE 1',                               2, 20, 10, $fGE, $nSIL],
            ['COMPTA',     'Comptabilité Générale',                  2, 20,  5, $fGE, $nSIL],
            // ── Spécifiques RIT
            ['COMMU1',     'Commutation 1',                          2, 25, 10, $fGE, $nRIT],
            ['TRANS1',     'Transmission 1',                         2, 25, 10, $fGE, $nRIT],
            // ── Spécifiques ER
            ['INTRO-ER',   'Introduction aux Énergies Renouvelables',2, 15,  5, $fGE, $nER],
            ['CHIMIE',     'Chimie et Thermochimie',                 2, 20,  5, $fGE, $nER],
            // ── Spécifiques BIOTECH
            ['BIO-CELL',   'Biologie Cellulaire',                    1, 25, 10, $fGE, $nBIO],
            ['BIOCHIMIE',  'Biochimie',                              2, 25, 10, $fGE, $nBIO],
            // ── Spécifiques SG
            ['COMPTA-GEN', 'Comptabilité Générale SG',               1, 25,  5, $fGE, $nSG],
            ['DROIT',      'Droit des Affaires',                     2, 20,  5, $fGE, $nSG],
        ];
        foreach ($matieres as [$code,$nom,$sem,$hp,$tpe,$fid,$nid]) {
            DB::table('matieres')->insert(['nom'=>$nom,'code'=>$code,'semestre'=>$sem,'hp_initial'=>$hp,'tpe_initial'=>$tpe,'filiere_id'=>$fid,'niveau_id'=>$nid,'archive'=>false,'created_at'=>now(),'updated_at'=>now()]);
        }

        // ══════════════════════════════════════════════════════════════════
        // UTILISATEURS — Directeur + Responsables de centre
        // ══════════════════════════════════════════════════════════════════
        DB::table('users')->insert([
            ['name'=>'AYI Théophane',  'email'=>'directeur@gasa.bj',            'password'=>Hash::make('Gasa2026!'),      'role'=>'ROLE_ADMIN',              'centre_id'=>null,  'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'SOSSOU Marc',    'email'=>'responsable.gbegamey@gasa.bj', 'password'=>Hash::make('Gbegamey2026!'), 'role'=>'ROLE_RESPONSABLE_CENTRE', 'centre_id'=>$cGbe, 'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'HOUNSOU Alice',  'email'=>'responsable.akpakpa@gasa.bj',  'password'=>Hash::make('Akpakpa2026!'),  'role'=>'ROLE_RESPONSABLE_CENTRE', 'centre_id'=>$cAkp, 'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'HONFO Pierre',   'email'=>'responsable.pnv@gasa.bj',      'password'=>Hash::make('Portonovo2026!'),'role'=>'ROLE_RESPONSABLE_CENTRE', 'centre_id'=>$cPnv, 'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'KPEKPASSI Jean', 'email'=>'responsable.calavi@gasa.bj',   'password'=>Hash::make('Calavi2026!'),   'role'=>'ROLE_RESPONSABLE_CENTRE', 'centre_id'=>$cCal, 'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ── Professeurs — Gbégamey
        $profsGbe = [
            ['DEGBOE Désiré',        'degboe@gasa.bj',       'PROF-GBE-001'],
            ['AKPONNA Marc-Aurèle',  'akponna@gasa.bj',      'PROF-GBE-002'],
            ['LALEYE Sylvestre',     'laleye@gasa.bj',       'PROF-GBE-003'],
            ['AHOUANSOU Fernand',    'ahouansou@gasa.bj',    'PROF-GBE-004'],
            ['AGBOCOU Emmanuel',     'agbocou@gasa.bj',      'PROF-GBE-005'],
            ['DJOHOU Arsène',        'djohou@gasa.bj',       'PROF-GBE-006'],
            ['LAURIANO Patrice',     'lauriano@gasa.bj',     'PROF-GBE-007'],
            ['SEFOU Rodrigue',       'sefou@gasa.bj',        'PROF-GBE-008'],
            ['AGUEGUE Norbert',      'aguegue@gasa.bj',      'PROF-GBE-009'],
            ['KWAK Stéphane',        'kwak@gasa.bj',         'PROF-GBE-010'],
            ['ADEGBOLA Raphaël',     'adegbola@gasa.bj',     'PROF-GBE-011'],
            ['ALLOGNON Bertrand',    'allognon@gasa.bj',     'PROF-GBE-012'],
            ['AYITEVI Claude',       'ayitevi@gasa.bj',      'PROF-GBE-013'],
            ['DOVONON Martin',       'dovonon@gasa.bj',      'PROF-GBE-014'],
            ['HOUETO Wilfrid',       'houeto@gasa.bj',       'PROF-GBE-015'],
            ['EGBAKO Salomé',        'egbako@gasa.bj',       'PROF-GBE-016'],
            ['FANDE Idriss',         'fande@gasa.bj',        'PROF-GBE-017'],
            ['SANNI Soumaïla',       'sanni@gasa.bj',        'PROF-GBE-018'],
            ['HOUSSOU Barnabé',      'houssou@gasa.bj',      'PROF-GBE-019'],
            ['MONTCHO David',        'montcho@gasa.bj',      'PROF-GBE-020'],
            ['AHOUAN-DJINOU Rosine', 'ahouandjinou@gasa.bj', 'PROF-GBE-021'],
        ];
        foreach ($profsGbe as [$name,$email,$badge]) {
            DB::table('users')->insert(['name'=>$name,'email'=>$email,'password'=>Hash::make('Prof2026!'),'role'=>'ROLE_PROFESSEUR','centre_id'=>$cGbe,'badge_uid'=>$badge,'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }

        // ── Professeurs — Akpakpa
        $profsAkp = [
            ['AZONDEKON Pascal','azondekon@gasa.bj','PROF-AKP-001'],
            ['BIAOU Roland',    'biaou@gasa.bj',    'PROF-AKP-002'],
            ['CODJIA Hermance', 'codjia@gasa.bj',   'PROF-AKP-003'],
            ['DANHOU Gérard',   'danhou@gasa.bj',   'PROF-AKP-004'],
            ['ELEGBE Serge',    'elegbe@gasa.bj',   'PROF-AKP-005'],
            ['FAGBEMI Lydie',   'fagbemi@gasa.bj',  'PROF-AKP-006'],
        ];
        foreach ($profsAkp as [$name,$email,$badge]) {
            DB::table('users')->insert(['name'=>$name,'email'=>$email,'password'=>Hash::make('Prof2026!'),'role'=>'ROLE_PROFESSEUR','centre_id'=>$cAkp,'badge_uid'=>$badge,'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }

        // ── Professeurs — Porto-Novo
        $profsPnv = [
            ['GBAGUIDI Albert','gbaguidi@gasa.bj','PROF-PNV-001'],
            ['HOUNYE Thérèse', 'hounye@gasa.bj',  'PROF-PNV-002'],
            ['IWEBI Camille',  'iwebi@gasa.bj',   'PROF-PNV-003'],
        ];
        foreach ($profsPnv as [$name,$email,$badge]) {
            DB::table('users')->insert(['name'=>$name,'email'=>$email,'password'=>Hash::make('Prof2026!'),'role'=>'ROLE_PROFESSEUR','centre_id'=>$cPnv,'badge_uid'=>$badge,'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }

        // ── Professeurs — Calavi
        $profsCal = [
            ['JOHNSON Maxime',   'johnson@gasa.bj',    'PROF-CAL-001'],
            ['KOUDOUS Inès',     'koudous@gasa.bj',    'PROF-CAL-002'],
            ['LANTONKPODE Félix','lantonkpode@gasa.bj','PROF-CAL-003'],
            ['MESSAN Virginie',  'messan@gasa.bj',     'PROF-CAL-004'],
        ];
        foreach ($profsCal as [$name,$email,$badge]) {
            DB::table('users')->insert(['name'=>$name,'email'=>$email,'password'=>Hash::make('Prof2026!'),'role'=>'ROLE_PROFESSEUR','centre_id'=>$cCal,'badge_uid'=>$badge,'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }

        // ══════════════════════════════════════════════════════════════════
        // SALLES
        // ══════════════════════════════════════════════════════════════════
        $sallesData = [
            [$cGbe,'Amphi A',    120,'Amphithéâtre'],
            [$cGbe,'Salle 101',   50,'Salle de cours'],
            [$cGbe,'Salle 102',   50,'Salle de cours'],
            [$cGbe,'Salle 103',   50,'Salle de cours'],
            [$cGbe,'Labo Info A', 25,'Laboratoire informatique'],
            [$cGbe,'Labo Info B', 25,'Laboratoire informatique'],
            [$cGbe,'Salle TP',    20,'Salle de travaux pratiques'],
            [$cAkp,'Amphi B',    100,'Amphithéâtre'],
            [$cAkp,'Salle A',     60,'Salle de cours'],
            [$cAkp,'Salle B',     60,'Salle de cours'],
            [$cAkp,'Labo Réseau', 24,'Laboratoire réseau'],
            [$cPnv,'Salle 01',    50,'Salle de cours'],
            [$cPnv,'Salle 02',    50,'Salle de cours'],
            [$cPnv,'Salle 03',    40,'Salle de cours'],
            [$cCal,'Salle Alpha', 60,'Salle de cours'],
            [$cCal,'Salle Bêta',  60,'Salle de cours'],
            [$cCal,'Labo Calavi', 30,'Laboratoire informatique'],
        ];
        foreach ($sallesData as [$cid,$nom,$cap,$type]) {
            DB::table('salles')->insert(['nom'=>$nom,'capacite'=>$cap,'type'=>$type,'centre_id'=>$cid,'created_at'=>now(),'updated_at'=>now()]);
        }

        // ══════════════════════════════════════════════════════════════════
        // GROUPES D'ÉTUDIANTS (Options) — 2025-2026 et 2024-2025
        // ══════════════════════════════════════════════════════════════════
        $a2425 = DB::table('annees_scolaires')->where('libelle','2024-2025')->value('id');

        $groupes2526 = [
            [$foSI, $nSI, $cGbe,'GE-SI L1 Gbégamey'],
            [$foER, $nER, $cGbe,'GE-ER L1 Gbégamey'],
            [$foSIL,$nSIL,$cGbe,'GE-SIL L1 Gbégamey'],
            [$foRIT,$nRIT,$cGbe,'GE-RIT L1 Gbégamey'],
            [$foBIO,$nBIO,$cGbe,'GE-BIOTECH L1 Gbégamey'],
            [$foSG, $nSG, $cGbe,'GE-SG L1 Gbégamey'],
            [$foSI, $nSI, $cAkp,'GE-SI L1 Akpakpa'],
            [$foSIL,$nSIL,$cAkp,'GE-SIL L1 Akpakpa'],
            [$foRIT,$nRIT,$cAkp,'GE-RIT L1 Akpakpa'],
            [$foBIO,$nBIO,$cAkp,'GE-BIOTECH L1 Akpakpa'],
            [$foSI, $nSI, $cPnv,'GE-SI L1 Porto-Novo'],
            [$foSIL,$nSIL,$cPnv,'GE-SIL L1 Porto-Novo'],
            [$foSG, $nSG, $cPnv,'GE-SG L1 Porto-Novo'],
            [$foSI, $nSI, $cCal,'GE-SI L1 Calavi'],
            [$foSIL,$nSIL,$cCal,'GE-SIL L1 Calavi'],
            [$foRIT,$nRIT,$cCal,'GE-RIT L1 Calavi'],
        ];
        foreach ($groupes2526 as [$fo,$niv,$cid,$nom]) {
            DB::table('options')->insert(['nom'=>$nom,'filiere_option_id'=>$fo,'niveau_id'=>$niv,'centre_id'=>$cid,'annee_scolaire_id'=>$a2526,'created_at'=>now(),'updated_at'=>now()]);
        }

        $groupes2425 = [
            [$foSI, $nSI, $cGbe,'GE-SI L1 Gbégamey 2024-2025'],
            [$foSIL,$nSIL,$cGbe,'GE-SIL L1 Gbégamey 2024-2025'],
            [$foRIT,$nRIT,$cGbe,'GE-RIT L1 Gbégamey 2024-2025'],
            [$foBIO,$nBIO,$cGbe,'GE-BIOTECH L1 Gbégamey 2024-2025'],
            [$foSG, $nSG, $cGbe,'GE-SG L1 Gbégamey 2024-2025'],
        ];
        foreach ($groupes2425 as [$fo,$niv,$cid,$nom]) {
            DB::table('options')->insert(['nom'=>$nom,'filiere_option_id'=>$fo,'niveau_id'=>$niv,'centre_id'=>$cid,'annee_scolaire_id'=>$a2425,'created_at'=>now(),'updated_at'=>now()]);
        }

        // ══════════════════════════════════════════════════════════════════
        // QUOTAS MATIÈRES — initialisation Gbégamey 2025-2026
        // ══════════════════════════════════════════════════════════════════
        $matieresAll = DB::table('matieres')->get();
        foreach ($matieresAll as $mat) {
            DB::table('matiere_centre_annee')->insert([
                'matiere_id'        => $mat->id,
                'centre_id'         => $cGbe,
                'annee_scolaire_id' => $a2526,
                'hp_restant'        => $mat->hp_initial,
                'tpe_dynamique'     => $mat->tpe_initial,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        $this->command->info('✅ GASA-ERP — Structure de base insérée !');
        $this->command->table(['Email','Mot de passe','Rôle'],[
            ['directeur@gasa.bj',            'Gasa2026!',       'Directeur (Admin)'],
            ['responsable.gbegamey@gasa.bj', 'Gbegamey2026!',  'Responsable Gbégamey'],
            ['responsable.akpakpa@gasa.bj',  'Akpakpa2026!',   'Responsable Akpakpa'],
            ['responsable.pnv@gasa.bj',      'Portonovo2026!', 'Responsable Porto-Novo'],
            ['responsable.calavi@gasa.bj',   'Calavi2026!',    'Responsable Calavi'],
            ['[prof]@gasa.bj',               'Prof2026!',      'Professeurs (34 au total)'],
        ]);
    }
}
