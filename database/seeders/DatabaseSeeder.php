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
        $a2324 = DB::table('annees_scolaires')->where('libelle','2023-2024')->value('id');
        $a2425 = DB::table('annees_scolaires')->where('libelle','2024-2025')->value('id');
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
        $cGbe  = DB::table('centres')->where('nom','Centre de Gbégamey')->value('id');
        $cAkp  = DB::table('centres')->where('nom',"Centre d'Akpakpa")->value('id');
        $cPnv  = DB::table('centres')->where('nom','Centre de Porto-Novo')->value('id');
        $cCal  = DB::table('centres')->where('nom','Centre de Calavi')->value('id');

        // ══════════════════════════════════════════════════════════════════
        // FILIÈRES & OPTIONS PÉDAGOGIQUES
        // ══════════════════════════════════════════════════════════════════
        DB::table('filieres')->insert([
            ['nom'=>'Génie Électrique','code'=>'GE','archive'=>false,'created_at'=>now(),'updated_at'=>now()],
        ]);
        $fGE = DB::table('filieres')->where('code','GE')->value('id');

        $options_ped = [
            ['nom'=>'Système Informatique',              'code'=>'SI',     'filiere_id'=>$fGE],
            ['nom'=>'Système Informatique et Logiciel',  'code'=>'SIL',    'filiere_id'=>$fGE],
            ['nom'=>'Réseaux et Ingénierie Télécom',     'code'=>'RIT',    'filiere_id'=>$fGE],
            ['nom'=>'Électricité Réseau',                'code'=>'ER',     'filiere_id'=>$fGE],
            ['nom'=>'Biotechnologie',                    'code'=>'BIOTECH','filiere_id'=>$fGE],
            ['nom'=>'Sciences de Gestion',               'code'=>'SG',     'filiere_id'=>$fGE],
        ];
        foreach ($options_ped as $op) {
            DB::table('filiere_options')->insert(array_merge($op, ['archive'=>false,'created_at'=>now(),'updated_at'=>now()]));
        }
        $foSI     = DB::table('filiere_options')->where('code','SI')->value('id');
        $foSIL    = DB::table('filiere_options')->where('code','SIL')->value('id');
        $foRIT    = DB::table('filiere_options')->where('code','RIT')->value('id');
        $foER     = DB::table('filiere_options')->where('code','ER')->value('id');
        $foBIO    = DB::table('filiere_options')->where('code','BIOTECH')->value('id');
        $foSG     = DB::table('filiere_options')->where('code','SG')->value('id');

        // Niveaux — GE1 pour chaque option
        $niveaux_data = [
            [$foSI,  'GE1','L1',1], [$foSIL, 'GE1','L1',1], [$foRIT, 'GE1','L1',1],
            [$foER,  'GE1','L1',1], [$foBIO, 'GE1','L1',1], [$foSG,  'GE1','L1',1],
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
        // Format : [code, nom, semestre, hp_initial, tpe_initial, filiere_id, niveau_id]
        $matieres = [
            // ── Communes GE1 – Semestre 1 (portées par SI)
            ['INFO-FOND',  'Informatique Fondamentale',          1, 30, 15, $fGE, $nSI],
            ['SYS-NUM',    'Systèmes de Numération',             1, 20, 10, $fGE, $nSI],
            ['REV-MATHS',  'Révisions Mathématiques',            1, 20,  0, $fGE, $nSI],
            ['TEEO',       'Théorie des Éléments Électroniques', 1, 20, 10, $fGE, $nSI],
            ['ATO',        'Analyse et Traitement des Oscillations', 1, 15,  5, $fGE, $nSI],
            ['ELN01',      'Électronique 01-02',                 1, 30, 10, $fGE, $nSI],
            ['ANGLAIS',    'Anglais',                            1, 15,  5, $fGE, $nSI],
            ['ELC',        'Électricité',                        1, 20,  5, $fGE, $nSI],
            ['TP-COMP',    'TP Composants',                      1, 20,  0, $fGE, $nSI],
            // ── Communes GE1 – Semestre 2 (portées par SI)
            ['ANALYSE',    'Analyse Mathématique',               2, 30, 10, $fGE, $nSI],
            ['STAT',       'Statistiques & Probabilités',        2, 20, 10, $fGE, $nSI],
            ['ELN03',      'Électronique 03',                    2, 25, 10, $fGE, $nSI],
            ['ALP',        'Algorithmique et Programmation',     2, 30, 15, $fGE, $nSI],
            ['PHYSIQUE',   'Physique',                           2, 30, 10, $fGE, $nSI],
            ['MECA-GEN',   'Mécanique Générale',                 2, 20,  5, $fGE, $nSI],
            ['ELEC-BAT',   'Électricité Bâtiment',              2, 20,  5, $fGE, $nSI],
            ['TECHNOS1',   'Technologies et Schémas 1',          2, 20,  5, $fGE, $nSI],
            ['ELA01',      'Électronique Analogique 01',         2, 25, 10, $fGE, $nSI],
            ['AUTO',       'Automatique',                        2, 20,  5, $fGE, $nSI],
            ['TP-RM',      'TP Réseaux et Mesures',              2, 20,  0, $fGE, $nSI],
            ['LANG-C',     'Langage C',                          2, 25, 15, $fGE, $nSI],
            ['DT',         'Dessin Technique',                   2, 15,  5, $fGE, $nSI],
            ['TP-PY',      'TP Python',                          2, 15,  0, $fGE, $nSI],
            // ── Spécifiques SIL
            ['ENV-LOG1',   'Environnement Logiciel 1',           1, 20,  5, $fGE, $nSIL],
            ['GRAPHES',    'Théorie des Graphes',                1, 20, 10, $fGE, $nSIL],
            ['MATHS-F',    'Mathématiques Fines',                1, 20,  5, $fGE, $nSIL],
            ['PROG-WEB1',  'Programmation Web 1',                2, 25, 10, $fGE, $nSIL],
            ['BDD',        'Bases de Données et SGBDR',          2, 25, 15, $fGE, $nSIL],
            ['MERISE1',    'MERISE 1',                           2, 20, 10, $fGE, $nSIL],
            ['COMPTA',     'Comptabilité Générale',              2, 20,  5, $fGE, $nSIL],
            // ── Spécifiques RIT
            ['COMMU1',     'Commutation 1',                      2, 25, 10, $fGE, $nRIT],
            ['TRANS1',     'Transmission 1',                     2, 25, 10, $fGE, $nRIT],
            // ── Spécifiques ER
            ['INTRO-ER',   'Introduction aux Énergies Renouvelables', 2, 15,  5, $fGE, $nER],
            ['CHIMIE',     'Chimie et Thermochimie',             2, 20,  5, $fGE, $nER],
            // ── Spécifiques BIOTECH (partagent beaucoup avec GE commun)
            ['BIO-CELL',   'Biologie Cellulaire',                1, 25, 10, $fGE, $nBIO],
            ['BIOCHIMIE',  'Biochimie',                          2, 25, 10, $fGE, $nBIO],
            // ── Spécifiques SG
            ['COMPTA-GEN', 'Comptabilité Générale SG',           1, 25,  5, $fGE, $nSG],
            ['DROIT',      'Droit des Affaires',                 2, 20,  5, $fGE, $nSG],
        ];
        foreach ($matieres as [$code,$nom,$sem,$hp,$tpe,$fid,$nid]) {
            DB::table('matieres')->insert(['nom'=>$nom,'code'=>$code,'semestre'=>$sem,'hp_initial'=>$hp,'tpe_initial'=>$tpe,'filiere_id'=>$fid,'niveau_id'=>$nid,'archive'=>false,'created_at'=>now(),'updated_at'=>now()]);
        }

        // ══════════════════════════════════════════════════════════════════
        // UTILISATEURS — Administrateur + Responsables de centre
        // ══════════════════════════════════════════════════════════════════
        DB::table('users')->insert([
            ['name'=>'AYI Théophane',    'email'=>'directeur@gasa.bj',            'password'=>Hash::make('Gasa2026!'),      'role'=>'ROLE_ADMIN',                'centre_id'=>null,  'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'SOSSOU Marc',      'email'=>'responsable.gbegamey@gasa.bj', 'password'=>Hash::make('Gbegamey2026!'), 'role'=>'ROLE_RESPONSABLE_CENTRE',   'centre_id'=>$cGbe, 'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'HOUNSOU Alice',    'email'=>'responsable.akpakpa@gasa.bj',  'password'=>Hash::make('Akpakpa2026!'),  'role'=>'ROLE_RESPONSABLE_CENTRE',   'centre_id'=>$cAkp, 'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'HONFO Pierre',     'email'=>'responsable.pnv@gasa.bj',      'password'=>Hash::make('Portonovo2026!'),'role'=>'ROLE_RESPONSABLE_CENTRE',   'centre_id'=>$cPnv, 'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'KPEKPASSI Jean',   'email'=>'responsable.calavi@gasa.bj',   'password'=>Hash::make('Calavi2026!'),   'role'=>'ROLE_RESPONSABLE_CENTRE',   'centre_id'=>$cCal, 'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()],
        ]);

        // ── Professeurs — Gbégamey (noms extraits des emplois du temps réels)
        $profsGbe = [
            ['DEGBOE Désiré',        'degboe@gasa.bj',         'PROF-GBE-001'],
            ['AKPONNA Marc-Aurèle',  'akponna@gasa.bj',        'PROF-GBE-002'],
            ['LALEYE Sylvestre',     'laleye@gasa.bj',         'PROF-GBE-003'],
            ['AHOUANSOU Fernand',    'ahouansou@gasa.bj',      'PROF-GBE-004'],
            ['AGBOCOU Emmanuel',     'agbocou@gasa.bj',        'PROF-GBE-005'],
            ['DJOHOU Arsène',        'djohou@gasa.bj',         'PROF-GBE-006'],
            ['LAURIANO Patrice',     'lauriano@gasa.bj',       'PROF-GBE-007'],
            ['SEFOU Rodrigue',       'sefou@gasa.bj',          'PROF-GBE-008'],
            ['AGUEGUE Norbert',      'aguegue@gasa.bj',        'PROF-GBE-009'],
            ['KWAK Stéphane',        'kwak@gasa.bj',           'PROF-GBE-010'],
            ['ADEGBOLA Raphaël',     'adegbola@gasa.bj',       'PROF-GBE-011'],
            ['ALLOGNON Bertrand',    'allognon@gasa.bj',       'PROF-GBE-012'],
            ['AYITEVI Claude',       'ayitevi@gasa.bj',        'PROF-GBE-013'],
            ['DOVONON Martin',       'dovonon@gasa.bj',        'PROF-GBE-014'],
            ['HOUETO Wilfrid',       'houeto@gasa.bj',         'PROF-GBE-015'],
            ['EGBAKO Salomé',        'egbako@gasa.bj',         'PROF-GBE-016'],
            ['FANDE Idriss',         'fande@gasa.bj',          'PROF-GBE-017'],
            ['SANNI Soumaïla',       'sanni@gasa.bj',          'PROF-GBE-018'],
            ['HOUSSOU Barnabé',      'houssou@gasa.bj',        'PROF-GBE-019'],
            ['MONTCHO David',        'montcho@gasa.bj',        'PROF-GBE-020'],
            ['AHOUAN-DJINOU Rosine', 'ahouandjinou@gasa.bj',   'PROF-GBE-021'],
        ];
        foreach ($profsGbe as [$name,$email,$badge]) {
            DB::table('users')->insert(['name'=>$name,'email'=>$email,'password'=>Hash::make('Prof2026!'),'role'=>'ROLE_PROFESSEUR','centre_id'=>$cGbe,'badge_uid'=>$badge,'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }

        // ── Professeurs — Akpakpa
        $profsAkp = [
            ['AZONDEKON Pascal', 'azondekon@gasa.bj', 'PROF-AKP-001'],
            ['BIAOU Roland',     'biaou@gasa.bj',     'PROF-AKP-002'],
            ['CODJIA Hermance',  'codjia@gasa.bj',    'PROF-AKP-003'],
            ['DANHOU Gérard',    'danhou@gasa.bj',     'PROF-AKP-004'],
            ['ELEGBE Serge',     'elegbe@gasa.bj',     'PROF-AKP-005'],
            ['FAGBEMI Lydie',    'fagbemi@gasa.bj',    'PROF-AKP-006'],
        ];
        foreach ($profsAkp as [$name,$email,$badge]) {
            DB::table('users')->insert(['name'=>$name,'email'=>$email,'password'=>Hash::make('Prof2026!'),'role'=>'ROLE_PROFESSEUR','centre_id'=>$cAkp,'badge_uid'=>$badge,'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }

        // ── Professeurs — Porto-Novo
        $profsPnv = [
            ['GBAGUIDI Albert',  'gbaguidi@gasa.bj',  'PROF-PNV-001'],
            ['HOUNYE Thérèse',   'hounye@gasa.bj',    'PROF-PNV-002'],
            ['IWEBI Camille',    'iwebi@gasa.bj',     'PROF-PNV-003'],
        ];
        foreach ($profsPnv as [$name,$email,$badge]) {
            DB::table('users')->insert(['name'=>$name,'email'=>$email,'password'=>Hash::make('Prof2026!'),'role'=>'ROLE_PROFESSEUR','centre_id'=>$cPnv,'badge_uid'=>$badge,'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }

        // ── Professeurs — Calavi
        $profsCal = [
            ['JOHNSON Maxime',  'johnson@gasa.bj',   'PROF-CAL-001'],
            ['KOUDOUS Inès',    'koudous@gasa.bj',   'PROF-CAL-002'],
            ['LANTONKPODE Félix','lantonkpode@gasa.bj','PROF-CAL-003'],
            ['MESSAN Virginie', 'messan@gasa.bj',    'PROF-CAL-004'],
        ];
        foreach ($profsCal as [$name,$email,$badge]) {
            DB::table('users')->insert(['name'=>$name,'email'=>$email,'password'=>Hash::make('Prof2026!'),'role'=>'ROLE_PROFESSEUR','centre_id'=>$cCal,'badge_uid'=>$badge,'email_verified_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }

        // IDs des professeurs Gbégamey
        $pDegboe   = DB::table('users')->where('email','degboe@gasa.bj')->value('id');
        $pAkponna  = DB::table('users')->where('email','akponna@gasa.bj')->value('id');
        $pLaleye   = DB::table('users')->where('email','laleye@gasa.bj')->value('id');
        $pAhouansou= DB::table('users')->where('email','ahouansou@gasa.bj')->value('id');
        $pAgbocou  = DB::table('users')->where('email','agbocou@gasa.bj')->value('id');
        $pDjohou   = DB::table('users')->where('email','djohou@gasa.bj')->value('id');
        $pSefou    = DB::table('users')->where('email','sefou@gasa.bj')->value('id');
        $pAguegue  = DB::table('users')->where('email','aguegue@gasa.bj')->value('id');
        $pKwak     = DB::table('users')->where('email','kwak@gasa.bj')->value('id');
        $pHoueto   = DB::table('users')->where('email','houeto@gasa.bj')->value('id');
        $pSanni    = DB::table('users')->where('email','sanni@gasa.bj')->value('id');
        $pMontcho  = DB::table('users')->where('email','montcho@gasa.bj')->value('id');

        // ══════════════════════════════════════════════════════════════════
        // SALLES
        // ══════════════════════════════════════════════════════════════════
        $sallesData = [
            // Gbégamey
            [$cGbe,'Amphi A',       120,'Amphithéâtre'],
            [$cGbe,'Salle 101',      50,'Salle de cours'],
            [$cGbe,'Salle 102',      50,'Salle de cours'],
            [$cGbe,'Salle 103',      50,'Salle de cours'],
            [$cGbe,'Labo Info A',    25,'Laboratoire informatique'],
            [$cGbe,'Labo Info B',    25,'Laboratoire informatique'],
            [$cGbe,'Salle TP',       20,'Salle de travaux pratiques'],
            // Akpakpa
            [$cAkp,'Amphi B',       100,'Amphithéâtre'],
            [$cAkp,'Salle A',        60,'Salle de cours'],
            [$cAkp,'Salle B',        60,'Salle de cours'],
            [$cAkp,'Labo Réseau',    24,'Laboratoire réseau'],
            // Porto-Novo
            [$cPnv,'Salle 01',       50,'Salle de cours'],
            [$cPnv,'Salle 02',       50,'Salle de cours'],
            [$cPnv,'Salle 03',       40,'Salle de cours'],
            // Calavi
            [$cCal,'Salle Alpha',    60,'Salle de cours'],
            [$cCal,'Salle Bêta',     60,'Salle de cours'],
            [$cCal,'Labo Calavi',    30,'Laboratoire informatique'],
        ];
        foreach ($sallesData as [$cid,$nom,$cap,$type]) {
            DB::table('salles')->insert(['nom'=>$nom,'capacite'=>$cap,'type'=>$type,'centre_id'=>$cid,'created_at'=>now(),'updated_at'=>now()]);
        }
        $sAmphiA   = DB::table('salles')->where('nom','Amphi A')->where('centre_id',$cGbe)->value('id');
        $sSalle101 = DB::table('salles')->where('nom','Salle 101')->where('centre_id',$cGbe)->value('id');
        $sSalle102 = DB::table('salles')->where('nom','Salle 102')->where('centre_id',$cGbe)->value('id');
        $sLaboA    = DB::table('salles')->where('nom','Labo Info A')->where('centre_id',$cGbe)->value('id');

        // ══════════════════════════════════════════════════════════════════
        // GROUPES D'ÉTUDIANTS (Options) — 2025-2026
        // ══════════════════════════════════════════════════════════════════
        $groupes2526 = [
            // Gbégamey
            [$foSI, $nSI, $cGbe,'GE-SI L1 Gbégamey'],
            [$foER, $nER, $cGbe,'GE-ER L1 Gbégamey'],
            [$foSIL,$nSIL,$cGbe,'GE-SIL L1 Gbégamey'],
            [$foRIT,$nRIT,$cGbe,'GE-RIT L1 Gbégamey'],
            [$foBIO,$nBIO,$cGbe,'GE-BIOTECH L1 Gbégamey'],
            [$foSG, $nSG, $cGbe,'GE-SG L1 Gbégamey'],
            // Akpakpa
            [$foSI, $nSI, $cAkp,'GE-SI L1 Akpakpa'],
            [$foSIL,$nSIL,$cAkp,'GE-SIL L1 Akpakpa'],
            [$foRIT,$nRIT,$cAkp,'GE-RIT L1 Akpakpa'],
            [$foBIO,$nBIO,$cAkp,'GE-BIOTECH L1 Akpakpa'],
            // Porto-Novo
            [$foSI, $nSI, $cPnv,'GE-SI L1 Porto-Novo'],
            [$foSIL,$nSIL,$cPnv,'GE-SIL L1 Porto-Novo'],
            [$foSG, $nSG, $cPnv,'GE-SG L1 Porto-Novo'],
            // Calavi
            [$foSI, $nSI, $cCal,'GE-SI L1 Calavi'],
            [$foSIL,$nSIL,$cCal,'GE-SIL L1 Calavi'],
            [$foRIT,$nRIT,$cCal,'GE-RIT L1 Calavi'],
        ];
        foreach ($groupes2526 as [$fo,$niv,$cid,$nom]) {
            DB::table('options')->insert(['nom'=>$nom,'filiere_option_id'=>$fo,'niveau_id'=>$niv,'centre_id'=>$cid,'annee_scolaire_id'=>$a2526,'created_at'=>now(),'updated_at'=>now()]);
        }

        // Groupes 2024-2025 (Gbégamey uniquement, historique)
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

        // IDs des groupes 2025-2026 Gbégamey
        $oSI  = DB::table('options')->where('nom','GE-SI L1 Gbégamey')->value('id');
        $oER  = DB::table('options')->where('nom','GE-ER L1 Gbégamey')->value('id');
        $oSIL = DB::table('options')->where('nom','GE-SIL L1 Gbégamey')->value('id');
        $oRIT = DB::table('options')->where('nom','GE-RIT L1 Gbégamey')->value('id');
        $oBIO = DB::table('options')->where('nom','GE-BIOTECH L1 Gbégamey')->value('id');
        $oSG  = DB::table('options')->where('nom','GE-SG L1 Gbégamey')->value('id');

        // IDs groupes Akpakpa
        $oAkpSI  = DB::table('options')->where('nom','GE-SI L1 Akpakpa')->value('id');
        $oAkpSIL = DB::table('options')->where('nom','GE-SIL L1 Akpakpa')->value('id');
        $oAkpRIT = DB::table('options')->where('nom','GE-RIT L1 Akpakpa')->value('id');

        // ══════════════════════════════════════════════════════════════════
        // ÉTUDIANTS & INSCRIPTIONS — 2025-2026
        // ══════════════════════════════════════════════════════════════════
        $prenoms = ['Kossi','Diane','Pierre','Fatima','Brice','Yvonne','Maxime','Inès','Rodrigue','Nathalie','Serge','Aline','Gaëtan','Mireille','Hermann','Joëlle','Fiacre','Sandra','Edgard','Laure'];
        $noms    = ['AHOUANSOU','AKAKPO','ATTINDEHOU','BATCHO','DOSSOU','EGBEDE','FAGNISSE','GBEDOU','HOUNKPATIN','IGLESIAS','JOHNSON','KPOSSOU','LAGNIKA','MEDENOU','NONVIDE','OLADELE','PADONOU','QUENUM','RASSINON','SOUBEROU'];

        $groupesEtu = [
            [$oSI,    'GBE-SI'],
            [$oER,    'GBE-ER'],
            [$oSIL,   'GBE-SIL'],
            [$oRIT,   'GBE-RIT'],
            [$oBIO,   'GBE-BIO'],
            [$oSG,    'GBE-SG'],
            [$oAkpSI, 'AKP-SI'],
            [$oAkpSIL,'AKP-SIL'],
            [$oAkpRIT,'AKP-RIT'],
        ];

        $etuCounter = 1;
        foreach ($groupesEtu as [$groupeId, $prefix]) {
            for ($i = 1; $i <= 20; $i++) {
                $prenom = $prenoms[($etuCounter - 1) % count($prenoms)];
                $nom    = $noms[($i - 1) % count($noms)];
                $mat    = sprintf('GASA-%04d', $etuCounter);
                $badge  = sprintf('ETU-%04d', $etuCounter);
                $email  = strtolower($prenom).'.'.$etuCounter.'@etudiant.gasa.bj';
                DB::table('etudiants')->insert(['matricule'=>$mat,'nom'=>$nom,'prenom'=>$prenom,'email'=>$email,'badge_uid'=>$badge,'created_at'=>now(),'updated_at'=>now()]);
                $etuId = DB::table('etudiants')->where('matricule',$mat)->value('id');
                DB::table('inscriptions')->insert(['etudiant_id'=>$etuId,'option_id'=>$groupeId,'annee_scolaire_id'=>$a2526,'statut'=>'actif','date_inscription'=>'2025-09-15','created_at'=>now(),'updated_at'=>now()]);
                $etuCounter++;
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // SÉANCES — Semaine type (basée sur les emplois du temps réels)
        // Semaine du 09 au 14 Juin 2026 (semaine courante)
        // ══════════════════════════════════════════════════════════════════
        $mInfoFond  = DB::table('matieres')->where('code','INFO-FOND')->value('id');
        $mSysNum    = DB::table('matieres')->where('code','SYS-NUM')->value('id');
        $mTeeo      = DB::table('matieres')->where('code','TEEO')->value('id');
        $mAto       = DB::table('matieres')->where('code','ATO')->value('id');
        $mEln01     = DB::table('matieres')->where('code','ELN01')->value('id');
        $mAlp       = DB::table('matieres')->where('code','ALP')->value('id');
        $mAngles    = DB::table('matieres')->where('code','ANGLAIS')->value('id');
        $mAnalyse   = DB::table('matieres')->where('code','ANALYSE')->value('id');
        $mStat      = DB::table('matieres')->where('code','STAT')->value('id');
        $mProgWeb   = DB::table('matieres')->where('code','PROG-WEB1')->value('id');
        $mBdd       = DB::table('matieres')->where('code','BDD')->value('id');
        $mMathsF    = DB::table('matieres')->where('code','MATHS-F')->value('id');
        $mCommu1    = DB::table('matieres')->where('code','COMMU1')->value('id');
        $mTrans1    = DB::table('matieres')->where('code','TRANS1')->value('id');

        // Séances : [date, debut_h, fin_h, matiere_id, salle_id, prof_id, type, statut, options[]]
        $seances = [
            // Lundi 09/06
            ['2026-06-09','07:30','09:30',$mInfoFond,$sAmphiA,  $pDegboe,  'HP','terminee',[$oSI]],
            ['2026-06-09','07:30','09:30',$mSysNum,  $sSalle101,$pAkponna, 'HP','terminee',[$oSIL,$oBIO,$oSG]],
            ['2026-06-09','09:30','12:00',$mAnalyse, $sSalle102,$pAhouansou,'HP','terminee',[$oSI,$oER]],
            ['2026-06-09','13:00','15:00',$mProgWeb, $sLaboA,   $pSanni,   'HP','terminee',[$oSIL,$oRIT]],
            ['2026-06-09','13:00','15:00',$mTeeo,    $sSalle101,$pLaleye,  'HP','terminee',[$oSI,$oER]],
            // Mardi 10/06
            ['2026-06-10','07:30','09:30',$mEln01,   $sSalle102,$pAkponna, 'HP','terminee',[$oSI,$oRIT]],
            ['2026-06-10','07:30','09:30',$mStat,    $sSalle101,$pAguegue, 'HP','terminee',[$oSIL,$oBIO,$oSG]],
            ['2026-06-10','09:30','12:00',$mAlp,     $sLaboA,   $pKwak,   'HP','terminee',[$oSI,$oRIT]],
            ['2026-06-10','13:00','17:00',$mBdd,     $sLaboA,   $pMontcho,'HP','terminee',[$oSIL,$oRIT]],
            ['2026-06-10','13:00','15:00',$mAngles,  $sAmphiA,  $pLaleye, 'HP','terminee',[$oSI,$oSI,$oER,$oRIT]],
            // Mercredi 11/06
            ['2026-06-11','07:30','09:30',$mInfoFond,$sSalle101,$pDegboe,  'HP','terminee',[$oSI,$oER,$oRIT]],
            ['2026-06-11','07:30','09:30',$mStat,    $sSalle102,$pAguegue, 'HP','terminee',[$oSIL,$oBIO,$oSG]],
            ['2026-06-11','13:00','16:00',$mProgWeb, $sLaboA,   $pSanni,   'HP','terminee',[$oSIL,$oRIT]],
            ['2026-06-11','13:00','17:00',$mBdd,     null,       $pMontcho,'TPE','terminee',[$oSIL]],
            // Jeudi 12/06
            ['2026-06-12','07:30','09:30',$mTeeo,    $sSalle101,$pLaleye,  'HP','terminee',[$oSI,$oRIT]],
            ['2026-06-12','07:30','09:30',$mInfoFond,$sAmphiA,  $pDegboe,  'HP','terminee',[$oSIL,$oBIO,$oSG]],
            ['2026-06-12','13:00','15:00',$mAnalyse, $sSalle102,$pAhouansou,'HP','terminee',[$oSI,$oER]],
            ['2026-06-12','13:00','15:00',$mMathsF,  $sSalle101,$pAguegue, 'HP','terminee',[$oSIL]],
            // Vendredi 13/06
            ['2026-06-13','07:30','09:30',$mAto,     $sSalle102,$pDjohou,  'HP','terminee',[$oSI,$oRIT]],
            ['2026-06-13','07:30','09:30',$mStat,    $sSalle101,$pAguegue, 'HP','terminee',[$oSIL,$oRIT]],
            ['2026-06-13','13:00','17:00',$mCommu1,  $sLaboA,   $pSefou,  'HP','terminee',[$oRIT]],
            ['2026-06-13','13:00','17:00',$mTrans1,  null,       $pSefou,  'TPE','terminee',[$oRIT]],
            // Samedi 14/06 (aujourd'hui)
            ['2026-06-14','07:30','09:30',$mProgWeb, $sLaboA,   $pSanni,  'HP','en_cours',[$oSIL,$oRIT]],
            ['2026-06-14','07:30','09:30',$mEln01,   $sSalle101,$pAkponna,'HP','en_cours',[$oSI,$oSI,$oRIT]],
            ['2026-06-14','10:30','12:00',$mAnalyse, $sSalle102,$pAhouansou,'HP','planifiee',[$oSI,$oER]],
            ['2026-06-14','13:00','15:00',$mBdd,     $sLaboA,   $pMontcho,'HP','planifiee',[$oSIL]],
            ['2026-06-14','13:00','15:00',$mCommu1,  $sSalle101,$pSefou,  'HP','planifiee',[$oRIT]],
        ];

        foreach ($seances as $s) {
            [$date,$dh,$fh,$mId,$salleId,$profId,$type,$statut,$optIds] = $s;
            $debut = $date . ' ' . $dh . ':00';
            $fin   = $date . ' ' . $fh . ':00';
            $scanProf = $statut === 'terminee' ? $debut : null;
            $seanceId = DB::table('seances')->insertGetId([
                'matiere_id'          => $mId,
                'salle_id'            => $salleId ?? $sSalle101,
                'professeur_id'       => $profId,
                'annee_scolaire_id'   => $a2526,
                'debut'               => $debut,
                'fin'                 => $fin,
                'type'                => $type,
                'statut'              => $statut,
                'is_inter_centre'     => false,
                'heure_scan_professeur' => $scanProf,
                'durees_pauses_minutes' => 0,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
            foreach (array_unique($optIds) as $oid) {
                DB::table('option_seance')->insert(['option_id'=>$oid,'seance_id'=>$seanceId]);
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // QUOTAS MATIÈRES (MatiereCentreAnnee) — initialisation Gbégamey
        // ══════════════════════════════════════════════════════════════════
        $matieresAll = DB::table('matieres')->get();
        foreach ($matieresAll as $mat) {
            DB::table('matiere_centre_annee')->insert([
                'matiere_id'       => $mat->id,
                'centre_id'        => $cGbe,
                'annee_scolaire_id'=> $a2526,
                'hp_restant'       => $mat->hp_initial,
                'tpe_dynamique'    => $mat->tpe_initial,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        $this->command->info('✅ GASA-ERP — Données réelles insérées !');
        $this->command->table(['Email','Mot de passe','Rôle'],[
            ['directeur@gasa.bj',            'Gasa2026!',       'Directeur (Admin)'],
            ['responsable.gbegamey@gasa.bj', 'Gbegamey2026!',  'Responsable Gbégamey'],
            ['responsable.akpakpa@gasa.bj',  'Akpakpa2026!',   'Responsable Akpakpa'],
            ['responsable.pnv@gasa.bj',      'Portonovo2026!', 'Responsable Porto-Novo'],
            ['responsable.calavi@gasa.bj',   'Calavi2026!',    'Responsable Calavi'],
            ['[prof]@gasa.bj',               'Prof2026!',      'Professeurs (21 à Gbégamey)'],
        ]);
    }
}
