<?php

namespace App\Http\Controllers;

use App\Models\{Seance, Matiere, Salle, Option, AnneeScolaire, User};
use Illuminate\Http\Request;
use Carbon\Carbon;

class SeancesImport extends Controller
{
    // ── Formulaire d'import ──────────────────────────────────────────────────
    public function form(int $centreId)
    {
        return view('seances.import', compact('centreId'));
    }

    // ── Traitement du fichier CSV ────────────────────────────────────────────
    public function import(Request $request, int $centreId)
    {
        $request->validate([
            'fichier'          => 'required|file|mimes:csv,txt|max:4096',
            'annee_scolaire_id'=> 'required|exists:annees_scolaires,id',
        ]);

        $path    = $request->file('fichier')->getRealPath();
        $lignes  = array_map('str_getcsv', file($path));
        $entete  = array_shift($lignes); // retire la ligne d'en-tête

        $importes = 0;
        $erreurs  = [];
        $anneeId  = $request->annee_scolaire_id;

        foreach ($lignes as $n => $cols) {
            $num = $n + 2;

            [$matiereCode, $salleNom, $profEmail, $debut, $fin, $type, $optionId]
                = array_pad($cols, 7, null);

            $matiere = Matiere::where('code', trim((string) $matiereCode))->first();
            if (!$matiere) { $erreurs[] = "L{$num} : matière «{$matiereCode}» introuvable."; continue; }

            $salle = Salle::where('centre_id', $centreId)
                          ->where('nom', trim((string) $salleNom))->first();
            if (!$salle) { $erreurs[] = "L{$num} : salle «{$salleNom}» introuvable."; continue; }

            $prof = User::where('email', trim((string) $profEmail))->first();
            if (!$prof) { $erreurs[] = "L{$num} : professeur «{$profEmail}» introuvable."; continue; }

            $typeVal = strtoupper(trim((string) $type));
            if (!in_array($typeVal, ['HP', 'TPE'])) { $erreurs[] = "L{$num} : type «{$type}» invalide (HP ou TPE)."; continue; }

            $seance = Seance::create([
                'matiere_id'        => $matiere->id,
                'salle_id'          => $salle->id,
                'professeur_id'     => $prof->id,
                'annee_scolaire_id' => $anneeId,
                'debut'             => Carbon::parse(trim((string) $debut)),
                'fin'               => Carbon::parse(trim((string) $fin)),
                'type'              => $typeVal,
                'statut'            => 'planifiee',
            ]);

            if ($optionId) {
                $seance->options()->sync([(int) $optionId]);
            }

            $importes++;
        }

        $msg = "{$importes} séance(s) importée(s).";
        return back()->with('succes', $msg)->with('import_erreurs', $erreurs);
    }

    // ── Modèle CSV à télécharger ─────────────────────────────────────────────
    public function modele()
    {
        $csv = "code_matiere,salle,email_professeur,debut,fin,type,option_id\n"
             . "INFO-101,Amphi A,prof@gasa.bj,2026-09-01 08:00,2026-09-01 11:00,HP,\n"
             . "ALGO-201,Salle 12,autre@gasa.bj,2026-09-01 13:00,2026-09-01 16:00,TPE,\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="modele_seances.csv"',
        ]);
    }
}
