<?php
namespace App\Http\Controllers;

use App\Models\{Seance, Salle, Option, Centre, AnneeScolaire, EmploiDuTemps};
use App\Services\EmploiDuTempsImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EmploiDuTempsController extends Controller
{
    // ── Tranches horaires canoniques ─────────────────────────────────────────
    private const TIME_SLOTS = [
        ['start' => '07:30', 'end' => '08:00', 'label' => '07h30 - 08h',   'recreation' => false],
        ['start' => '08:00', 'end' => '09:00', 'label' => '08h - 09h',     'recreation' => false],
        ['start' => '09:00', 'end' => '10:00', 'label' => '09h - 10h',     'recreation' => false],
        ['start' => '10:00', 'end' => '10:30', 'label' => '10h - 10h30',   'recreation' => true],
        ['start' => '10:30', 'end' => '11:00', 'label' => '10h30 - 11h',   'recreation' => false],
        ['start' => '11:00', 'end' => '12:00', 'label' => '11h - 12h',     'recreation' => false],
        ['start' => '12:00', 'end' => '13:00', 'label' => '12h - 13h',     'recreation' => true],
        ['start' => '13:00', 'end' => '14:00', 'label' => '13h - 14h',     'recreation' => false],
        ['start' => '14:00', 'end' => '15:00', 'label' => '14h - 15h',     'recreation' => false],
        ['start' => '15:00', 'end' => '16:00', 'label' => '15h - 16h',     'recreation' => false],
        ['start' => '16:00', 'end' => '17:00', 'label' => '16h - 17h',     'recreation' => false],
        ['start' => '17:00', 'end' => '18:00', 'label' => '17h - 18h',     'recreation' => false],
    ];

    private const DAYS = [
        1 => 'LUNDI', 2 => 'MARDI', 3 => 'MERCREDI',
        4 => 'JEUDI', 5 => 'VENDREDI', 6 => 'SAMEDI',
    ];

    // ── Liste des EDTs + grille libre (filtre semaine/option) ────────────────
    public function index(Request $request, int $centreId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $centre = Centre::findOrFail($centreId);
        $annee  = AnneeScolaire::courante();

        $edts = EmploiDuTemps::where('centre_id', $centreId)
            ->with('option')
            ->orderByDesc('date_debut')
            ->orderByDesc('numero')
            ->get();

        $salles  = Salle::where('centre_id', $centreId)->orderBy('nom')->get();
        $options = $annee
            ? Option::where('centre_id', $centreId)->where('annee_scolaire_id', $annee->id)->orderBy('nom')->get()
            : collect();

        // Grille : EDt sélectionné OU semaine libre
        $edtId    = $request->input('edt_id');
        $optionId = $request->input('option_id');

        $edt          = $edtId ? EmploiDuTemps::find($edtId) : null;
        $weekStartStr = $request->input('semaine', $edt ? $edt->date_debut->format('Y-m-d') : today()->toDateString());
        $weekStart    = Carbon::parse($weekStartStr)->startOfWeek(Carbon::MONDAY);
        $weekEnd      = $weekStart->copy()->addDays(5)->endOfDay(); // Lundi → Samedi

        // Requête séances
        $query = Seance::with(['matiere', 'professeur', 'salle', 'options'])
            ->whereBetween('debut', [$weekStart->copy()->startOfDay(), $weekEnd]);

        if ($edt) {
            $query->where('emploi_du_temps_id', $edt->id);
        } else {
            $query->whereHas('salle', fn($q) => $q->where('centre_id', $centreId));
            if ($optionId) {
                $query->whereHas('options', fn($q) => $q->where('options.id', $optionId));
            }
        }

        $seances = $query->whereIn('statut', ['planifiee', 'en_cours', 'terminee'])->get();

        $grid = $this->buildGrid($seances);

        $prevWeek = $weekStart->copy()->subWeek()->format('Y-m-d');
        $nextWeek = $weekStart->copy()->addWeek()->format('Y-m-d');

        return view('planning.grille', compact(
            'centre', 'centreId', 'annee', 'edts', 'edt',
            'salles', 'options',
            'seances', 'grid', 'weekStart', 'weekEnd',
            'prevWeek', 'nextWeek', 'optionId'
        ) + ['timeSlots' => self::TIME_SLOTS, 'days' => self::DAYS]);
    }

    // ── Traitement de l'import ───────────────────────────────────────────────
    public function import(Request $request, int $centreId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);

        $request->validate([
            'fichier'   => 'required|file|max:5120',
            'salle_id'  => 'required|exists:salles,id',
            'option_id' => 'nullable|exists:options,id',
        ]);

        $file = $request->file('fichier');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['csv', 'txt', 'xlsx', 'xls'])) {
            return back()->withErrors(['fichier' => 'Format non supporté. Utilisez un fichier CSV (.csv) ou Excel (.xlsx, .xls)'])->withInput();
        }

        $path     = $file->store('temp');
        $fullPath = Storage::path($path);

        $service = new EmploiDuTempsImportService();

        $result = match ($ext) {
            'xlsx', 'xls' => $service->fromXLSX($fullPath, $centreId, (int)$request->salle_id, $request->option_id ? (int)$request->option_id : null),
            default       => $service->fromCSV($fullPath, $centreId, (int)$request->salle_id, $request->option_id ? (int)$request->option_id : null),
        };

        @unlink($fullPath);

        if (!$result['edt']) {
            return back()->withErrors(['import' => implode(' | ', $result['erreurs'])])->withInput();
        }

        $msg = "Import réussi — {$result['crees']} séance(s) créée(s) pour l'emploi du temps N°{$result['edt']->numero}.";
        if (!empty($result['erreurs'])) {
            $msg .= ' Avertissements : ' . implode('; ', $result['erreurs']);
        }

        return redirect()
            ->route('emplois-du-temps.index', ['centreId' => $centreId, 'edt_id' => $result['edt']->id])
            ->with('succes', $msg);
    }

    // ── Téléchargement du modèle CSV ─────────────────────────────────────────
    public function modele()
    {
        $content = EmploiDuTempsImportService::modeleCSV();
        return response($content, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="modele_emploi_du_temps.csv"',
        ]);
    }

    // ── Suppression d'un EDT (et de ses séances liées) ───────────────────────
    public function destroy(Request $request, int $centreId, EmploiDuTemps $edt)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->estAdmin() && $user->centre_id != $centreId) abort(403);
        if ($edt->centre_id != $centreId) abort(403);

        $nbSeances = $edt->seances()->count();
        $edt->seances()->update(['emploi_du_temps_id' => null]);
        $edt->delete();

        return back()->with('succes', "Emploi du temps supprimé ({$nbSeances} séance(s) conservée(s) mais dissociées).");
    }

    // ── Construction de la grille (algorithme rowspan) ───────────────────────
    private function buildGrid(\Illuminate\Database\Eloquent\Collection $seances): array
    {
        $cells    = [];   // cells[dayIso][slotIdx]   = ['seance' => ..., 'rowspan' => ...]
        $occupied = [];   // occupied[dayIso][slotIdx] = true  (couvert par rowspan précédent)

        foreach ($seances as $seance) {
            $dayIso    = $seance->debut->dayOfWeekIso;  // 1=Lun … 6=Sam
            if (!isset(self::DAYS[$dayIso])) continue;

            $debutTime = $seance->debut->format('H:i');
            $finTime   = $seance->fin->format('H:i');

            $startIdx = $this->slotByStart($debutTime);
            $endIdx   = $this->slotByEnd($finTime);

            if ($startIdx === null || $endIdx === null || $endIdx < $startIdx) continue;

            // Si une autre séance occupe déjà ce créneau pour ce jour → on ignore (conflit)
            if (isset($cells[$dayIso][$startIdx]) || isset($occupied[$dayIso][$startIdx])) continue;

            $rowspan = $endIdx - $startIdx + 1;
            $cells[$dayIso][$startIdx] = ['seance' => $seance, 'rowspan' => $rowspan];

            for ($i = $startIdx + 1; $i <= $endIdx; $i++) {
                $occupied[$dayIso][$i] = true;
            }
        }

        return compact('cells', 'occupied');
    }

    private function slotByStart(string $time): ?int
    {
        foreach (self::TIME_SLOTS as $idx => $slot) {
            if ($slot['start'] === $time) return $idx;
        }
        return null;
    }

    private function slotByEnd(string $time): ?int
    {
        // Retourne l'index du dernier slot dont la fin est <= $time
        $result = null;
        foreach (self::TIME_SLOTS as $idx => $slot) {
            if ($slot['end'] <= $time) $result = $idx;
            elseif ($slot['end'] > $time) break;
        }
        return $result;
    }
}
