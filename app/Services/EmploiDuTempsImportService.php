<?php
namespace App\Services;

use App\Models\{Seance, EmploiDuTemps, User, Matiere, Salle, Option, AnneeScolaire};
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmploiDuTempsImportService
{
    // Tranches horaires canoniques
    public const CRENEAUX = [
        ['start' => '07:30', 'end' => '08:00'],
        ['start' => '08:00', 'end' => '09:00'],
        ['start' => '09:00', 'end' => '10:00'],
        ['start' => '10:00', 'end' => '10:30'],  // récréation
        ['start' => '10:30', 'end' => '11:00'],
        ['start' => '11:00', 'end' => '12:00'],
        ['start' => '12:00', 'end' => '13:00'],  // récréation
        ['start' => '13:00', 'end' => '14:00'],
        ['start' => '14:00', 'end' => '15:00'],
        ['start' => '15:00', 'end' => '16:00'],
        ['start' => '16:00', 'end' => '17:00'],
        ['start' => '17:00', 'end' => '18:00'],
    ];

    private const JOURS_ISO = [
        'LUNDI'     => 1,
        'MARDI'     => 2,
        'MERCREDI'  => 3,
        'JEUDI'     => 4,
        'VENDREDI'  => 5,
        'SAMEDI'    => 6,
    ];

    // ── Point d'entrée principal ─────────────────────────────────────────────
    public function fromCSV(string $path, int $centreId, int $salleId, ?int $optionId): array
    {
        $raw = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($raw === false) {
            return ['edt' => null, 'crees' => 0, 'erreurs' => ["Impossible de lire le fichier CSV (chemin : $path)."]];
        }
        $lines  = array_map('trim', $raw);
        $meta   = [];
        $lignes = [];
        $errors = [];
        $inData = false;

        foreach ($lines as $line) {
            if (str_starts_with($line, '#')) continue;  // commentaire

            if ($line === '---') {
                $inData = true;
                continue;
            }

            $cols = array_map('trim', explode(';', $line));

            if (!$inData) {
                // Section en-tête : clé;valeur
                $key = strtolower($cols[0] ?? '');
                $val = $cols[1] ?? '';
                match ($key) {
                    'numero'      => $meta['numero']      = (int) $val,
                    'orientation' => $meta['orientation'] = $val,
                    'datedebut'   => $meta['date_debut']  = $this->parseDate($val),
                    'datefin'     => $meta['date_fin']    = $this->parseDate($val),
                    default       => null,
                };
            } else {
                // Ignorer la ligne d'en-tête des colonnes
                if (strtoupper($cols[0] ?? '') === 'JOUR') continue;

                $jourLabel = strtoupper($cols[0] ?? '');
                if (!isset(self::JOURS_ISO[$jourLabel])) continue;

                $lignes[] = [
                    'jour'        => $jourLabel,
                    'jour_iso'    => self::JOURS_ISO[$jourLabel],
                    'debut'       => $this->normaliseHeure($cols[1] ?? ''),
                    'fin'         => $this->normaliseHeure($cols[2] ?? ''),
                    'matiere'     => $cols[3] ?? '',
                    'professeur'  => $cols[4] ?? '',
                    'type'        => strtoupper($cols[5] ?? 'HP'),
                    'groupe'      => $cols[6] ?? '',
                    'salle_nom'   => $cols[7] ?? '',
                ];
            }
        }

        if (empty($meta['date_debut']) || empty($meta['date_fin'])) {
            $errors[] = 'Dates de période manquantes dans l\'en-tête (DateDebut; / DateFin;).';
            return ['edt' => null, 'crees' => 0, 'erreurs' => $errors];
        }

        return $this->createSeances($meta, $lignes, $centreId, $salleId, $optionId);
    }

    public function fromXLSX(string $path, int $centreId, int $salleId, ?int $optionId): array
    {
        if (!class_exists('ZipArchive')) {
            return ['edt' => null, 'crees' => 0, 'erreurs' => ['Extension PHP ZipArchive non disponible.']];
        }

        $grid = $this->parseXLSXNatif($path);

        if (empty($grid)) {
            return ['edt' => null, 'crees' => 0, 'erreurs' => ['Impossible de lire le fichier XLSX. Vérifiez qu\'il n\'est pas protégé ou corrompu.']];
        }

        // Convertir la grille en CSV ; (même pipeline que fromCSV)
        $safe = fn(string $v): string => str_replace([';', "\n", "\r"], [',', ' ', ''], $v);
        $content = '';
        foreach ($grid as $row) {
            $content .= implode(';', array_map(fn($v) => $safe((string)$v), $row)) . "\n";
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'edt_') . '.csv';
        file_put_contents($tmpPath, $content);
        $result = $this->fromCSV($tmpPath, $centreId, $salleId, $optionId);
        @unlink($tmpPath);
        return $result;
    }

    // ── Lecteur XLSX natif (ZipArchive + SimpleXML, sans phpspreadsheet) ─────
    private function parseXLSXNatif(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) return [];

        // Supprime les namespaces XML pour simplifier le parsing SimpleXML
        $stripNs = static function (string $xml): string {
            $xml = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $xml);   // déclarations
            $xml = preg_replace('/(<\/?)\w+:(\w+)/', '$1$2', $xml);          // préfixes
            return $xml;
        };

        // 1. Shared strings (table des chaînes partagées)
        $sharedStrings = [];
        $ssRaw = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssRaw !== false) {
            $ss = simplexml_load_string($stripNs($ssRaw));
            if ($ss !== false) {
                foreach ($ss->si as $si) {
                    if (count($si->r) > 0) {
                        $text = '';
                        foreach ($si->r as $r) { $text .= (string) $r->t; }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = (string) $si->t;
                    }
                }
            }
        }

        // 2. Première feuille de calcul
        $sheetRaw = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheetRaw === false) return [];

        $xml = simplexml_load_string($stripNs($sheetRaw));
        if ($xml === false) return [];

        $rows   = [];
        $maxCol = 0;

        foreach ($xml->sheetData->row as $row) {
            $r = (int) $row['r'];
            foreach ($row->c as $cell) {
                $ref    = (string) $cell['r'];                          // ex: "B3"
                $colStr = rtrim(preg_replace('/[0-9]/', '', $ref));     // ex: "B"
                $colIdx = $this->colToIndex($colStr);
                $maxCol = max($maxCol, $colIdx);

                $type   = (string) $cell['t'];
                $rawVal = isset($cell->v) ? (string) $cell->v : '';

                $val = match ($type) {
                    's'         => $sharedStrings[(int) $rawVal] ?? '',
                    'inlineStr' => (string) ($cell->is->t ?? ''),
                    'b'         => $rawVal === '1' ? 'TRUE' : 'FALSE',
                    default     => $rawVal,
                };

                $rows[$r][$colIdx] = $val;
            }
        }

        if (empty($rows)) return [];

        // 3. Construire la grille ordonnée (combler les trous)
        ksort($rows);
        $grid = [];
        foreach ($rows as $cols) {
            $row = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $row[] = $cols[$i] ?? '';
            }
            $grid[] = $row;
        }

        return $grid;
    }

    private function colToIndex(string $col): int
    {
        $col    = strtoupper(trim($col));
        $result = 0;
        for ($i = 0, $len = strlen($col); $i < $len; $i++) {
            $result = $result * 26 + (ord($col[$i]) - 64);
        }
        return $result - 1; // 0-based
    }

    // ── Création effective des séances ───────────────────────────────────────
    private function createSeances(array $meta, array $lignes, int $centreId, int $salleId, ?int $optionId): array
    {
        $annee  = AnneeScolaire::courante();
        $errors = [];
        $crees  = 0;

        $edt = EmploiDuTemps::create([
            'numero'           => $meta['numero'] ?? 1,
            'centre_id'        => $centreId,
            'annee_scolaire_id'=> $annee?->id,
            'option_id'        => $optionId,
            'orientation_label'=> $meta['orientation'] ?? 'Non définie',
            'date_debut'       => $meta['date_debut'],
            'date_fin'         => $meta['date_fin'],
        ]);

        // Construire la liste de toutes les dates de chaque jour dans la période
        $dateDebut = Carbon::parse($meta['date_debut'])->startOfDay();
        $dateFin   = Carbon::parse($meta['date_fin'])->endOfDay();

        // Datespar jour ISO (1=Lun … 6=Sam)
        $datesParJour = [];
        $cursor = $dateDebut->copy();
        while ($cursor->lte($dateFin)) {
            $iso = $cursor->dayOfWeekIso;
            if ($iso <= 6) {
                $datesParJour[$iso][] = $cursor->format('Y-m-d');
            }
            $cursor->addDay();
        }

        $salleDefaut = Salle::find($salleId);

        foreach ($lignes as $i => $ligne) {
            $jourIso = $ligne['jour_iso'];
            $dates   = $datesParJour[$jourIso] ?? [];

            if (empty($dates)) {
                $errors[] = "Ligne " . ($i + 1) . " : aucune date pour {$ligne['jour']} dans la période.";
                continue;
            }

            // Résolution professeur
            $prof = $this->findProfesseur($ligne['professeur'], $centreId);
            if (!$prof) {
                $errors[] = "Ligne " . ($i + 1) . " : professeur '{$ligne['professeur']}' non trouvé — séance(s) ignorée(s).";
                continue;
            }

            // Résolution matière
            $matiere = $this->findMatiere($ligne['matiere']);
            if (!$matiere) {
                $errors[] = "Ligne " . ($i + 1) . " : matière '{$ligne['matiere']}' non trouvée — séance(s) ignorée(s).";
                continue;
            }

            // Résolution salle (salle explicite dans CSV > salle par défaut du formulaire)
            $salle = $salleDefaut;
            if (!empty($ligne['salle_nom'])) {
                $salleAlt = Salle::where('centre_id', $centreId)
                    ->where('nom', 'LIKE', '%' . $ligne['salle_nom'] . '%')
                    ->first();
                if ($salleAlt) $salle = $salleAlt;
            }
            if (!$salle) {
                $errors[] = "Ligne " . ($i + 1) . " : aucune salle disponible.";
                continue;
            }

            // Résolution option/groupe
            $optIds = $optionId ? [$optionId] : [];
            if (!empty($ligne['groupe']) && !$optionId) {
                $opt = Option::where('centre_id', $centreId)
                    ->where(fn($q) => $q->where('nom', 'LIKE', '%' . $ligne['groupe'] . '%')
                                        ->orWhere('code', 'LIKE', '%' . $ligne['groupe'] . '%'))
                    ->first();
                if ($opt) $optIds = [$opt->id];
            }

            $type = in_array($ligne['type'], ['HP', 'TPE']) ? $ligne['type'] : 'HP';

            foreach ($dates as $dateStr) {
                $debut = Carbon::parse($dateStr . ' ' . $ligne['debut']);
                $fin   = Carbon::parse($dateStr . ' ' . $ligne['fin']);

                // Éviter les doublons exacts
                if (Seance::where('emploi_du_temps_id', $edt->id)
                          ->where('professeur_id', $prof->id)
                          ->where('debut', $debut)->exists()) {
                    continue;
                }

                $seance = Seance::create([
                    'emploi_du_temps_id' => $edt->id,
                    'matiere_id'         => $matiere->id,
                    'salle_id'           => $salle->id,
                    'professeur_id'      => $prof->id,
                    'annee_scolaire_id'  => $annee?->id,
                    'debut'              => $debut,
                    'fin'                => $fin,
                    'type'               => $type,
                    'statut'             => 'planifiee',
                    'is_inter_centre'    => false,
                    'est_composition'    => false,
                ]);

                if (!empty($optIds)) {
                    $seance->options()->sync($optIds);
                }

                $crees++;
            }
        }

        return ['edt' => $edt, 'crees' => $crees, 'erreurs' => $errors];
    }

    // ── Utilitaires ──────────────────────────────────────────────────────────
    private function findProfesseur(string $nom, int $centreId): ?User
    {
        if (empty(trim($nom))) return null;
        return User::where('centre_id', $centreId)
            ->where('role', 'ROLE_PROFESSEUR')
            ->where(fn($q) => $q->where('name', 'LIKE', '%' . $nom . '%')
                                ->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($nom) . '%']))
            ->first();
    }

    private function findMatiere(string $code): ?Matiere
    {
        if (empty(trim($code))) return null;
        return Matiere::where('archive', false)
            ->where(fn($q) => $q->where('code', strtoupper(trim($code)))
                                ->orWhere('nom', 'LIKE', '%' . $code . '%'))
            ->first();
    }

    private function parseDate(string $val): ?string
    {
        try {
            if (str_contains($val, '/')) {
                $parts = explode('/', $val);
                if (count($parts) === 3) {
                    return Carbon::createFromFormat('d/m/Y', trim($val))->format('Y-m-d');
                }
            }
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function normaliseHeure(string $h): string
    {
        $h = trim($h);
        // "07h30" → "07:30", "7:30" → "07:30"
        $h = preg_replace('/h/', ':', strtolower($h));
        if (!str_contains($h, ':')) return $h . ':00';
        [$hh, $mm] = explode(':', $h, 2);
        return str_pad((int)$hh, 2, '0', STR_PAD_LEFT) . ':' . str_pad((int)$mm, 2, '0', STR_PAD_LEFT);
    }

    // ── Génère le contenu du modèle CSV à télécharger ───────────────────────
    public static function modeleCSV(): string
    {
        return implode("\n", [
            '# GASA-ERP — Modèle Emploi du Temps',
            '# Remplissez les méta-données puis les lignes du planning.',
            '# Séparateur : point-virgule (;)',
            'Numero;1',
            'Orientation;GE2 (SIL2)',
            'DateDebut;23/06/2026',
            'DateFin;04/07/2026',
            '---',
            'Jour;Debut;Fin;Matiere;Professeur;Type;Groupe;Salle',
            'LUNDI;07:30;08:00;TP-RM;LAFITTE;HP;SI2-G1;',
            'LUNDI;08:00;11:00;CONST-DESSIN;SEFOU;HP;;',
            'LUNDI;13:00;16:00;C++;AKPONNA;HP;;',
            'MARDI;08:00;11:00;ALGO;PROF-NOM;HP;;',
            'MERCREDI;08:00;11:00;RESEAUX;PROF-NOM;HP;;',
            '# JOURS VALIDES : LUNDI MARDI MERCREDI JEUDI VENDREDI SAMEDI',
            '# TYPE valide   : HP ou TPE',
            '# DEBUT/FIN     : hh:mm (ex: 07:30, 10:30, 13:00)',
            '# SALLE         : nom partiel de la salle (facultatif, sinon salle choisie dans le formulaire)',
        ]);
    }
}
