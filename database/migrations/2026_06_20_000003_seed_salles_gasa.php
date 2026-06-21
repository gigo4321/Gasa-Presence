<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        $now = Carbon::now();

        $cGbe = DB::table('centres')->where('nom', 'Gbégamey')->value('id');
        $cAkp = DB::table('centres')->where('nom', 'Akpakpa')->value('id');

        // 1. Rename existing seeded rooms to GASA convention
        if ($cGbe) {
            DB::table('salles')->where('centre_id', $cGbe)->where('nom', 'Salle 101')->update(['nom' => '1E1']);
            DB::table('salles')->where('centre_id', $cGbe)->where('nom', 'Amphi A')->update(['nom' => '1E2', 'type' => 'Salle de cours']);
        }
        if ($cAkp) {
            DB::table('salles')->where('centre_id', $cAkp)->where('nom', 'Salle A')->update(['nom' => '1E1']);
        }

        // 2. Insert missing rooms for each centre
        $sallesParCentre = [
            $cGbe => ['1E1', '1E2', '2E1', '2E2', '3E1', '3E2', '3E3'],
            $cAkp => ['1E1', '1E2', '2E1', '2E2'],
        ];

        foreach ($sallesParCentre as $centreId => $noms) {
            if (!$centreId) continue;
            foreach ($noms as $nom) {
                $exists = DB::table('salles')->where('centre_id', $centreId)->where('nom', $nom)->exists();
                if (!$exists) {
                    DB::table('salles')->insert([
                        'nom'        => $nom,
                        'capacite'   => 40,
                        'type'       => 'Salle de cours',
                        'centre_id'  => $centreId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // 3. Add scanner RFID equipment to every room that doesn't already have one
        $allSalles = DB::table('salles')->get();
        foreach ($allSalles as $salle) {
            $hasScanner = DB::table('equipements')
                ->where('salle_id', $salle->id)
                ->where('type_materiel', 'Scanner RFID')
                ->exists();
            if ($hasScanner) continue;

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
    }

    public function down(): void
    {
        DB::table('equipements')->where('type_materiel', 'Scanner RFID')->delete();

        // Revert renamed rooms (best-effort, only if no sessions reference them)
        $cGbe = DB::table('centres')->where('nom', 'Gbégamey')->value('id');
        $cAkp = DB::table('centres')->where('nom', 'Akpakpa')->value('id');

        $usedSalles = DB::table('seances')->pluck('salle_id')->unique()->toArray();

        DB::table('salles')
            ->where('centre_id', $cGbe)->where('nom', '1E1')
            ->whereNotIn('id', $usedSalles)
            ->update(['nom' => 'Salle 101']);
        DB::table('salles')
            ->where('centre_id', $cGbe)->where('nom', '1E2')
            ->whereNotIn('id', $usedSalles)
            ->update(['nom' => 'Amphi A', 'type' => 'Amphithéâtre']);
        DB::table('salles')
            ->where('centre_id', $cAkp)->where('nom', '1E1')
            ->whereNotIn('id', $usedSalles)
            ->update(['nom' => 'Salle A']);

        // Remove rooms that have no sessions
        DB::table('salles')
            ->whereIn('nom', ['2E1', '2E2', '3E1', '3E2', '3E3', '1E2'])
            ->whereNotIn('id', $usedSalles)
            ->delete();
    }
};
