<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetData extends Command
{
    protected $signature = 'db:reset-data';
    protected $description = 'Supprime toutes les données sauf les utilisateurs';

    public function handle(): int
    {
        if (!$this->confirm('Supprimer TOUTES les données (sauf utilisateurs) ? Cette action est irréversible.')) {
            $this->info('Annulé.');
            return 0;
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        $tables = [
            'sorties_temporaires',
            'presences',
            'option_seance',
            'emplois_du_temps',
            'seances',
            'matiere_centre_annee',
            'equipements',
            'salles',
            'inscriptions',
            'etudiants',
            'options',
            'annees_scolaires',
            'matieres',
            'niveaux',
            'filiere_options',
            'filieres',
            'centres',
        ];

        foreach ($tables as $table) {
            DB::table($table)->delete();
            $this->line("  ✓ $table vidée");
        }

        DB::statement('PRAGMA foreign_keys = ON');

        $this->info('Toutes les données ont été supprimées. Les utilisateurs sont conservés.');
        return 0;
    }
}
