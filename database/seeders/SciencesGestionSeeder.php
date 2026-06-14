<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Filiere, FiliereOption, Niveau, Matiere, Seance, AnneeScolaire, Centre, Option, User, Salle, Inscription, Etudiant, Presence, MatiereCentreAnnee, SortieTemporaire};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SciencesGestionSeeder extends Seeder
{
    public function run(): void
    {
        $annee = AnneeScolaire::courante() ?? AnneeScolaire::first();
        $centre = Centre::first() ?? Centre::create(['nom' => 'Centre Principal', 'ville' => 'Cotonou']);

        if (!$annee) {
            $this->command->error("Aucune année scolaire active trouvée. Créez-en une d'abord.");
            return;
        }

        DB::transaction(function () use ($annee, $centre) {
            // 1. Création de la structure Sciences de Gestion (Si inexistante)
            $filiere = Filiere::firstOrCreate(['code' => 'SG'], ['nom' => 'Sciences de Gestion']);

            $optionsGestion = [
                'CG' => 'Comptabilité et Gestion',
                'MK' => 'Marketing et Communication',
                'GRH' => 'Gestion des Ressources Humaines'
            ];

            foreach ($optionsGestion as $code => $nom) {
                $fo = FiliereOption::firstOrCreate(['code' => $code, 'filiere_id' => $filiere->id], ['nom' => $nom]);
                for ($i = 1; $i <= 3; $i++) {
                    $niv = Niveau::firstOrCreate(
                        ['code' => "L$i-$code", 'filiere_option_id' => $fo->id],
                        ['libelle' => "Licence $i", 'ordre' => $i]
                    );

                    // Création du groupe (Option) pour l'année en cours
                    Option::firstOrCreate([
                        'filiere_option_id' => $fo->id,
                        'niveau_id' => $niv->id,
                        'centre_id' => $centre->id,
                        'annee_scolaire_id' => $annee->id
                    ], ['nom' => "$nom - L$i"]);
                }
            }

            // 2. Récupération des ressources globales
            $profs = User::where('role', 'ROLE_PROFESSEUR')->get();
            if ($profs->isEmpty()) {
                $profs = User::factory()->count(5)->create(['role' => 'ROLE_PROFESSEUR', 'centre_id' => $centre->id]);
            }
            $salles = Salle::where('centre_id', $centre->id)->get();
            if ($salles->isEmpty()) {
                $salles[] = Salle::create(['nom' => 'Amphi A', 'capacite' => 100, 'type' => 'banalisee', 'centre_id' => $centre->id]);
                $salles[] = Salle::create(['nom' => 'Salle B1', 'capacite' => 40, 'type' => 'banalisee', 'centre_id' => $centre->id]);
                $salles[] = Salle::create(['nom' => 'Amphi A', 'capacite' => 100, 'type' => 'banalisee', 'equipements' => 'Vidéoprojecteur, Sonorisation', 'centre_id' => $centre->id]);
                $salles[] = Salle::create(['nom' => 'Labo Info 1', 'capacite' => 30, 'type' => 'laboratoire', 'equipements' => '30 PC, Climatisation, Tableau blanc', 'centre_id' => $centre->id]);
            }

            // 3. TRAITEMENT DE TOUTES LES OPTIONS SANS DONNÉES
            $toutesLesOptions = Option::where('annee_scolaire_id', $annee->id)->get();

            foreach ($toutesLesOptions as $option) {
                $this->command->info("Traitement de l'option : {$option->nom}");

                // A. Vérifier/Créer les matières pour ce niveau
                $matieres = Matiere::where('niveau_id', $option->niveau_id)->get();
                if ($matieres->isEmpty()) {
                    for ($i = 1; $i <= 4; $i++) {
                        $matieres[] = Matiere::create([
                            'nom' => "Matière Générique $i " . $option->niveau->code,
                            'code' => "GEN-" . $option->niveau->code . "-$i",
                            'semestre' => rand(1, 2),
                            'hp_initial' => 40,
                            'tpe_initial' => 20,
                            'filiere_id' => $option->filiereOption->filiere_id,
                            'niveau_id' => $option->niveau_id
                        ]);
                    }
                }

                // B. Initialiser les quotas (MatiereCentreAnnee)
                foreach ($matieres as $mat) {
                    MatiereCentreAnnee::firstOrCreate([
                        'matiere_id' => $mat->id,
                        'centre_id' => $option->centre_id,
                        'annee_scolaire_id' => $annee->id
                    ], [
                        'hp_restant' => $mat->hp_initial,
                        'tpe_dynamique' => $mat->tpe_initial
                    ]);
                }

                // C. Vérifier/Créer des inscriptions (Stats réelles)
                $inscriptions = Inscription::where('option_id', $option->id)->get();
                if ($inscriptions->isEmpty()) {
                    $etudiants = Etudiant::factory()->count(15)->create();
                    foreach ($etudiants as $etu) {
                        $inscriptions[] = Inscription::create([
                            'etudiant_id' => $etu->id,
                            'option_id' => $option->id,
                            'annee_scolaire_id' => $annee->id,
                            'statut' => 'actif',
                            'date_inscription' => $annee->date_debut
                        ]);
                    }
                }

                // D. Générer l'historique des séances si aucune séance n'existe
                if ($option->seances()->count() === 0) {
                    $this->genererHistorique($annee, $option, $matieres, $profs, $salles, $inscriptions);
                }
            }
        });

        $this->command->info("Test des limites terminé : Toutes les options sont désormais peuplées !");
    }

    private function genererHistorique(AnneeScolaire $annee, Option $option, $matieres, $profs, $salles, $inscriptions)
    {
        $currentDate = clone $annee->date_debut;
        $today = Carbon::now();
        // On planifie jusqu'à 2 semaines après aujourd'hui pour voir le planning futur
        $dateLimite = Carbon::today()->addWeeks(2);

        while ($currentDate <= $dateLimite) {
            if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                // 1 séance par jour aléatoire pour simuler un emploi du temps
                $matiere = $matieres->random();
                $debut = (clone $currentDate)->setHour(rand(8, 14))->setMinute(0);
                $fin = (clone $debut)->addHours(3);

                $statut = 'planifiee';
                if ($currentDate->isPast() && !$currentDate->isToday()) $statut = 'terminee';
                if ($currentDate->isToday()) $statut = 'en_cours';

                $seance = Seance::create([
                    'matiere_id' => $matiere->id,
                    'salle_id' => $salles->random()->id,
                    'professeur_id' => $profs->random()->id,
                    'annee_scolaire_id' => $annee->id,
                    'debut' => $debut,
                    'fin' => $fin,
                    'type' => 'HP',
                    'statut' => $statut,
                    'heure_scan_professeur' => $statut === 'terminee' ? $debut->copy()->addMinutes(rand(0, 10)) : null
                ]);
                $seance->options()->attach($option->id);

                // Générer des présences (90% de présence pour des stats réalistes)
                if ($statut === 'terminee') {
                    foreach ($inscriptions as $index => $insc) {
                        $dice = rand(1, 100);
                        $presenceData = [
                            'seance_id' => $seance->id,
                            'inscription_id' => $insc->id,
                        ];

                        if ($dice <= 10) { // 10% Absents
                            $presenceData['statut'] = 'absent';
                            $presenceData['heure_entree'] = null;
                            Presence::create($presenceData);
                        }
                        elseif ($dice <= 50) { // 40% À l'heure
                            $presenceData['statut'] = 'present';
                            $presenceData['heure_entree'] = $debut->copy()->addMinutes(rand(-10, 5));
                            Presence::create($presenceData);
                        }
                        elseif ($dice <= 70) { // 20% En retard
                            $presenceData['statut'] = 'present';
                            $presenceData['heure_entree'] = $debut->copy()->addMinutes(rand(16, 30));
                            Presence::create($presenceData);
                        }
                        elseif ($dice <= 85) { // 15% Sortie temporaire et retour OK
                            $presenceData['statut'] = 'present';
                            $presenceData['heure_entree'] = $debut->copy()->addMinutes(rand(-5, 2));
                            $p = Presence::create($presenceData);

                            SortieTemporaire::create([
                                'presence_id' => $p->id,
                                'heure_sortie' => $debut->copy()->addHour(),
                                'heure_rentree' => $debut->copy()->addHour()->addMinutes(10), // < 15min
                                'duree_minutes' => 10,
                                'rentree_refusee' => false
                            ]);
                        }
                        else { // 15% Sortie SANS retour ou rentrée refusée (> 15 min)
                            $presenceData['statut'] = 'presence_insuffisante';
                            $presenceData['heure_entree'] = $debut->copy()->addMinutes(rand(-5, 2));
                            $presenceData['heure_sortie_definitive'] = $debut->copy()->addHour();
                            $p = Presence::create($presenceData);

                            // On enregistre la tentative de sortie qui a mal tourné
                            SortieTemporaire::create([
                                'presence_id' => $p->id,
                                'heure_sortie' => $debut->copy()->addHour(),
                                'heure_rentree' => $dice > 95 ? $debut->copy()->addHour()->addMinutes(20) : null,
                                'duree_minutes' => $dice > 95 ? 20 : null,
                                'rentree_refusee' => true
                            ]);
                        }
                    }
                }
            }
            $currentDate->addDay();
        }
    }
}
