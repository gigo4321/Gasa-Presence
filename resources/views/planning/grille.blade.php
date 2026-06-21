@extends('layouts.app')
@section('titre', 'Emploi du Temps — ' . $centre->nom)

@push('styles')
<style>
/* ── Grid Colors ─────────────────────────────────────── */
:root {
    --edt-hp-bg:   #e3f2fd;
    --edt-hp-br:   #90caf9;
    --edt-hp-tx:   #1565c0;
    --edt-tpe-bg:  #f3e5f5;
    --edt-tpe-br:  #ce93d8;
    --edt-tpe-tx:  #6a1b9a;
    --edt-rec-bg:  #fff8e1;
    --edt-rec-tx:  #f57f17;
    --edt-empty:   #fafafa;
}

/* ── Table layout ─────────────────────────────────────── */
.edt-table { border-collapse: collapse; min-width: 700px; font-size: 12px; }
.edt-table th, .edt-table td { border: 1px solid #dee2e6 !important; vertical-align: middle; }
.edt-table thead th {
    background: var(--fonce); color: #fff;
    text-align: center; font-size: 11px;
    letter-spacing: .06em; padding: 8px 4px;
}
.edt-time {
    font-size: 10px; font-weight: 600; color: var(--marron);
    text-align: center; white-space: nowrap;
    min-width: 70px; width: 70px; padding: 4px 6px;
    background: #faf7f4;
}
.edt-time-rec {
    background: var(--edt-rec-bg); color: var(--edt-rec-tx);
    font-size: 9px; letter-spacing: .12em; font-weight: 700;
    writing-mode: vertical-lr; transform: rotate(180deg);
    height: 28px; padding: 4px 2px;
}

/* ── Récréation ─────────────────────────────────────── */
.edt-recreation td.edt-cell-rec {
    background: var(--edt-rec-bg);
    border-color: #ffe082 !important;
}
.edt-cell-rec-first::after {
    content: 'R E C R É A T I O N';
    display: block; text-align: center;
    font-size: 9px; letter-spacing: .15em;
    color: var(--edt-rec-tx); font-weight: 700;
}

/* ── Cellule cours ─────────────────────────────────── */
.edt-course-cell {
    padding: 6px 8px !important;
    text-align: center;
    vertical-align: middle !important;
}
.edt-course-cell.hp  { background: var(--edt-hp-bg); border-color: var(--edt-hp-br) !important; }
.edt-course-cell.tpe { background: var(--edt-tpe-bg); border-color: var(--edt-tpe-br) !important; }
.edt-course-inner { display: flex; flex-direction: column; gap: 3px; align-items: center; }
.edt-matiere { font-weight: 700; font-size: 12px; color: var(--fonce); line-height: 1.2; }
.edt-prof    { font-size: 11px; color: var(--marron); font-style: italic; }
.edt-badge   { font-size: 9px; padding: 1px 6px; border-radius: 10px; font-weight: 700; }
.edt-badge.HP  { background: var(--edt-hp-br); color: var(--edt-hp-tx); }
.edt-badge.TPE { background: var(--edt-tpe-br); color: var(--edt-tpe-tx); }
.edt-groupes { font-size: 9px; color: #888; margin-top: 2px; }
.edt-salle   { font-size: 9px; color: #aaa; }
.edt-empty-cell { background: var(--edt-empty); min-width: 80px; }

/* ── Import zone drag-drop ─────────────────────────── */
.drop-zone {
    border: 2px dashed var(--marron); border-radius: 16px;
    padding: 40px 20px; text-align: center;
    transition: background .2s, border-color .2s;
    cursor: pointer;
}
.drop-zone.dragover { background: #fff3dd; border-color: var(--fonce); }
.drop-zone .bi-cloud-upload { font-size: 48px; color: var(--marron); }

/* ── Responsive ──────────────────────────────────────── */
.edt-scroll-wrapper { overflow-x: auto; border-radius: 12px; border: 1px solid rgba(0,0,0,.08); }

/* ── Header badge ────────────────────────────────────── */
.edt-header-card {
    background: var(--fonce); color: #fff; border-radius: 12px;
    padding: 12px 20px; margin-bottom: 16px;
}
</style>
@endpush

@section('content')

{{-- ── Onglets ─────────────────────────────────────────────────────────── --}}
<ul class="nav nav-pills mb-4" id="edtTabs">
    <li class="nav-item">
        <button class="nav-link {{ !request('tab') || request('tab') === 'grille' ? 'active' : '' }}"
                onclick="switchTab('grille')" style="font-size:13px;">
            <i class="bi bi-grid-3x3 me-1"></i> Grille
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link {{ request('tab') === 'import' ? 'active' : '' }}"
                onclick="switchTab('import')" style="font-size:13px;">
            <i class="bi bi-cloud-upload me-1"></i> Importer
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link {{ request('tab') === 'liste' ? 'active' : '' }}"
                onclick="switchTab('liste')" style="font-size:13px;">
            <i class="bi bi-list-ul me-1"></i> Mes EDTs
            @if($edts->count())
            <span class="badge rounded-pill ms-1" style="background:var(--marron);font-size:10px;">{{ $edts->count() }}</span>
            @endif
        </button>
    </li>
</ul>

{{-- ══════════════════════════════════════════════════════════════════════════
     TAB 1 : GRILLE HEBDOMADAIRE
══════════════════════════════════════════════════════════════════════════ --}}
<div id="tab-grille" class="tab-pane {{ !request('tab') || request('tab') === 'grille' ? '' : 'd-none' }}">

    {{-- Filtres ─────────────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('emplois-du-temps.index', $centreId) }}" class="row g-2 mb-3 align-items-end">
        <input type="hidden" name="tab" value="grille">
        <div class="col-md-3">
            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Emploi du temps</label>
            <select name="edt_id" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                <option value="">— Semaine libre —</option>
                @foreach($edts as $e)
                <option value="{{ $e->id }}" {{ $edt?->id == $e->id ? 'selected' : '' }}>
                    N°{{ $e->numero }} — {{ $e->orientation_label }} ({{ $e->date_debut->format('d/m') }}→{{ $e->date_fin->format('d/m/Y') }})
                </option>
                @endforeach
            </select>
        </div>
        @if(!$edt)
        <div class="col-md-3">
            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Groupe/Option</label>
            <select name="option_id" class="form-select form-select-sm rounded-3">
                <option value="">Tous les groupes</option>
                @foreach($options as $o)
                <option value="{{ $o->id }}" {{ $optionId == $o->id ? 'selected' : '' }}>{{ $o->nom }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-md-3">
            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Semaine</label>
            <input type="date" name="semaine" value="{{ $weekStart->format('Y-m-d') }}"
                   class="form-control form-control-sm rounded-3">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm text-white rounded-3" style="background:var(--marron);">
                <i class="bi bi-funnel me-1"></i>Afficher
            </button>
        </div>
    </form>

    {{-- En-tête EDT ─────────────────────────────────────────────────────── --}}
    @if($edt)
    <div class="edt-header-card d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <span style="font-size:11px;opacity:.6;letter-spacing:.06em;">EMPLOI DU TEMPS N°{{ $edt->numero }}</span>
            <div style="font-size:16px;font-weight:700;">{{ $edt->orientation_label }}</div>
            <div style="font-size:12px;opacity:.8;">{{ $edt->periode }}</div>
        </div>
        <div class="d-flex gap-2">
            @if($weekStart->copy()->subWeek()->gte(Carbon\Carbon::parse($edt->date_debut)->startOfWeek()))
            <a href="{{ route('emplois-du-temps.index', ['centreId' => $centreId, 'edt_id' => $edt->id, 'semaine' => $prevWeek, 'tab' => 'grille']) }}"
               class="btn btn-sm rounded-3" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">
                ← Semaine précédente
            </a>
            @endif
            @if($weekStart->copy()->addWeek()->lte(Carbon\Carbon::parse($edt->date_fin)))
            <a href="{{ route('emplois-du-temps.index', ['centreId' => $centreId, 'edt_id' => $edt->id, 'semaine' => $nextWeek, 'tab' => 'grille']) }}"
               class="btn btn-sm rounded-3" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);">
                Semaine suivante →
            </a>
            @endif
        </div>
    </div>
    @else
    {{-- Navigation semaine libre ──────────────────────────────────────── --}}
    <div class="d-flex align-items-center gap-3 mb-3">
        <a href="{{ route('emplois-du-temps.index', ['centreId' => $centreId, 'semaine' => $prevWeek, 'option_id' => $optionId, 'tab' => 'grille']) }}"
           class="btn btn-sm text-white rounded-3" style="background:var(--marron);">← Préc.</a>
        <span style="font-size:13px;font-weight:600;color:var(--fonce);">
            Semaine du {{ $weekStart->locale('fr')->isoFormat('D MMMM YYYY') }}
        </span>
        <a href="{{ route('emplois-du-temps.index', ['centreId' => $centreId, 'semaine' => $nextWeek, 'option_id' => $optionId, 'tab' => 'grille']) }}"
           class="btn btn-sm text-white rounded-3" style="background:var(--marron);">Suiv. →</a>
    </div>
    @endif

    {{-- Titre des jours avec dates ───────────────────────────────────── --}}
    @php
        $datesCols = [];
        foreach ($days as $iso => $label) {
            $datesCols[$iso] = $weekStart->copy()->addDays($iso - 1);
        }
    @endphp

    {{-- TABLE GRILLE ─────────────────────────────────────────────────── --}}
    <div class="edt-scroll-wrapper">
    <table class="edt-table w-100">
        <thead>
            <tr>
                <th style="width:70px;">HORAIRES</th>
                @foreach($days as $iso => $label)
                <th>
                    {{ $label }}<br>
                    <span style="font-size:10px;opacity:.7;font-weight:400;">{{ $datesCols[$iso]->format('d/m') }}</span>
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        @foreach($timeSlots as $slotIdx => $slot)
            <tr class="{{ $slot['recreation'] ? 'edt-recreation' : '' }}">
                {{-- Colonne HORAIRES --}}
                <td class="edt-time {{ $slot['recreation'] ? 'edt-time-rec' : '' }}">
                    @if(!$slot['recreation'])
                        {{ $slot['label'] }}
                    @endif
                </td>

                {{-- Colonnes jours --}}
                @php $firstFreeRec = true; @endphp
                @foreach($days as $dayIso => $dayLabel)
                    @if(isset($grid['occupied'][$dayIso][$slotIdx]))
                        @continue
                    @endif

                    @if($slot['recreation'])
                        <td class="edt-cell-rec {{ $firstFreeRec ? 'edt-cell-rec-first' : '' }}"></td>
                        @php $firstFreeRec = false; @endphp
                    @elseif(isset($grid['cells'][$dayIso][$slotIdx]))
                        @php
                            $cell    = $grid['cells'][$dayIso][$slotIdx];
                            $seance  = $cell['seance'];
                            $typeClass = strtolower($seance->type ?? 'hp');
                            $groupeStr = $seance->options->pluck('nom')->join(', ');
                        @endphp
                        <td class="edt-course-cell {{ $typeClass }}" rowspan="{{ $cell['rowspan'] }}">
                            <div class="edt-course-inner">
                                <div class="edt-matiere">{{ $seance->matiere?->nom ?? '—' }}</div>
                                <div class="edt-prof">{{ $seance->professeur?->name ?? '—' }}</div>
                                <span class="edt-badge {{ strtoupper($seance->type ?? 'HP') }}">{{ $seance->type ?? 'HP' }}</span>
                                @if($groupeStr)
                                <div class="edt-groupes"><i class="bi bi-people"></i> {{ $groupeStr }}</div>
                                @endif
                                @if($seance->salle)
                                <div class="edt-salle"><i class="bi bi-geo-alt"></i> {{ $seance->salle->nom }}</div>
                                @endif
                            </div>
                        </td>
                    @else
                        <td class="edt-empty-cell"></td>
                    @endif
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>{{-- /edt-scroll-wrapper --}}

    @if($seances->isEmpty())
    <div class="text-center p-4 mt-2" style="color:#aaa;font-size:13px;">
        <i class="bi bi-calendar-x" style="font-size:28px;display:block;margin-bottom:8px;"></i>
        Aucune séance pour cette semaine{{ $edt ? ' dans cet emploi du temps' : '' }}.
        <br>
        <a href="{{ route('emplois-du-temps.index', ['centreId' => $centreId, 'tab' => 'import']) }}"
           class="btn btn-sm mt-3 text-white rounded-3" style="background:var(--fonce);">
            Importer un emploi du temps
        </a>
    </div>
    @endif

    {{-- Légende ────────────────────────────────────────────────────────── --}}
    <div class="d-flex gap-3 mt-3 flex-wrap" style="font-size:11px;">
        <span><span class="badge rounded-pill" style="background:var(--edt-hp-bg);color:var(--edt-hp-tx);border:1px solid var(--edt-hp-br);">HP</span> Cours Professeur</span>
        <span><span class="badge rounded-pill" style="background:var(--edt-tpe-bg);color:var(--edt-tpe-tx);border:1px solid var(--edt-tpe-br);">TPE</span> Travaux Personnels</span>
        <span><span class="badge rounded-pill" style="background:var(--edt-rec-bg);color:var(--edt-rec-tx);">—</span> Récréation</span>
    </div>

</div>{{-- /tab-grille --}}


{{-- ══════════════════════════════════════════════════════════════════════════
     TAB 2 : IMPORT
══════════════════════════════════════════════════════════════════════════ --}}
<div id="tab-import" class="tab-pane {{ request('tab') === 'import' ? '' : 'd-none' }}">

    <div class="row g-4">
        <div class="col-md-7">
            <div class="bg-white rounded-4 border p-4">
                <h6 class="fw-bold mb-3" style="color:var(--fonce);">
                    <i class="bi bi-cloud-upload me-2"></i>Importer un emploi du temps
                </h6>

                <form method="POST" action="{{ route('emplois-du-temps.import', $centreId) }}"
                      enctype="multipart/form-data" id="importForm">
                    @csrf

                    {{-- Zone drag-drop --}}
                    <div class="drop-zone mb-3" id="dropZone" onclick="document.getElementById('fileInput').click()">
                        <i class="bi bi-cloud-upload d-block mb-2"></i>
                        <div style="font-size:13px;font-weight:600;color:var(--fonce);">
                            Glissez votre fichier ici
                        </div>
                        <div style="font-size:12px;color:#aaa;margin-top:4px;">
                            ou <span style="color:var(--marron);text-decoration:underline;">cliquez pour parcourir</span>
                        </div>
                        <div id="fileNameDisplay" style="font-size:12px;color:var(--marron);margin-top:8px;font-weight:600;"></div>
                        <input type="file" id="fileInput" name="fichier"
                               accept=".csv,.txt,.xlsx,.xls" style="display:none;"
                               onchange="showFileName(this)">
                    </div>
                    <div class="mb-2" style="font-size:11px;color:#aaa;text-align:center;">
                        Formats acceptés : <strong>CSV (.csv)</strong> · Excel <strong>(.xlsx)</strong> *<br>
                        <span style="font-size:10px;">* Excel nécessite : <code>composer require phpoffice/phpspreadsheet</code></span>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Salle par défaut *</label>
                            <select name="salle_id" class="form-select rounded-3" required>
                                <option value="">— Sélectionner —</option>
                                @foreach($salles as $s)
                                <option value="{{ $s->id }}">{{ $s->nom }} ({{ $s->capacite }} pl.)</option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size:11px;">Salle utilisée si non précisée dans le fichier.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Groupe concerné</label>
                            <select name="option_id" class="form-select rounded-3">
                                <option value="">— Auto (depuis le fichier) —</option>
                                @foreach($options as $o)
                                <option value="{{ $o->id }}">{{ $o->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn w-100 text-white rounded-3 mt-3" style="background:var(--fonce);">
                        <i class="bi bi-upload me-2"></i>Lancer l'importation
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-5">
            {{-- Guide CSV ─────────────────────────────────────── --}}
            <div class="bg-white rounded-4 border p-4 mb-3">
                <h6 class="fw-bold mb-2" style="color:var(--fonce);font-size:13px;">
                    <i class="bi bi-file-earmark-text me-2"></i>Format du fichier CSV
                </h6>
                <pre style="font-size:10px;background:#f5f5f5;border-radius:8px;padding:12px;overflow-x:auto;color:#333;line-height:1.5;"># En-tête (clé;valeur)
Numero;1
Orientation;GE2 (SIL2)
DateDebut;23/06/2026
DateFin;04/07/2026
---
Jour;Debut;Fin;Matiere;Professeur;Type;Groupe;Salle
LUNDI;07:30;08:00;TP-RM;LAFITTE;HP;SI2-G1;
LUNDI;08:00;11:00;CONST-DESSIN;SEFOU;HP;;SALLE-A
MARDI;13:00;16:00;C++;AKPONNA;HP;;</pre>
                <a href="{{ route('emplois-du-temps.modele') }}"
                   class="btn btn-sm w-100 rounded-3 mt-2"
                   style="background:var(--beige);color:var(--marron);border:1px solid var(--marron);">
                    <i class="bi bi-download me-1"></i>Télécharger le modèle CSV
                </a>
            </div>

            {{-- Notes importantes ────────────────────────────── --}}
            <div class="rounded-4 p-3" style="background:var(--beige);border:1px solid rgba(0,0,0,.06);">
                <div style="font-size:11px;color:var(--marron);font-weight:600;margin-bottom:8px;">
                    <i class="bi bi-info-circle me-1"></i>Règles de correspondance
                </div>
                <ul style="font-size:11px;color:var(--fonce);padding-left:16px;margin:0;line-height:1.7;">
                    <li>La colonne <strong>Matière</strong> peut être le code ou le nom partiel.</li>
                    <li>La colonne <strong>Professeur</strong> est le nom partiel (MAJUSCULE ou minuscule).</li>
                    <li>Les séances sont créées pour <strong>chaque occurrence</strong> du jour dans la période.</li>
                    <li>Les doublons exacts sont ignorés automatiquement.</li>
                    <li>Séparateur : <strong>point-virgule (;)</strong>, encodage <strong>UTF-8</strong>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>{{-- /tab-import --}}


{{-- ══════════════════════════════════════════════════════════════════════════
     TAB 3 : LISTE DES EDTS
══════════════════════════════════════════════════════════════════════════ --}}
<div id="tab-liste" class="tab-pane {{ request('tab') === 'liste' ? '' : 'd-none' }}">
    @if($edts->isEmpty())
    <div class="bg-white rounded-4 border p-5 text-center" style="color:#aaa;">
        <i class="bi bi-calendar-x" style="font-size:40px;margin-bottom:12px;display:block;color:#ccc;"></i>
        <p style="font-size:14px;">Aucun emploi du temps importé. Utilisez l'onglet <strong>Importer</strong>.</p>
    </div>
    @else
    <div class="row g-3">
    @foreach($edts as $e)
    <div class="col-md-6">
        <div class="bg-white rounded-4 border p-3 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge rounded-2 px-3" style="background:var(--fonce);font-size:11px;">N°{{ $e->numero }}</span>
                    @if($e->option)
                    <span class="badge rounded-pill px-2" style="background:var(--beige);color:var(--marron);font-size:10px;border:1px solid var(--marron);">{{ $e->option->nom }}</span>
                    @endif
                </div>
                <div style="font-weight:700;font-size:14px;color:var(--fonce);">{{ $e->orientation_label }}</div>
                <div style="font-size:12px;color:var(--marron);margin-top:2px;">
                    <i class="bi bi-calendar-range me-1"></i>{{ $e->periode }}
                </div>
                <div style="font-size:11px;color:#aaa;margin-top:4px;">
                    {{ $e->seances()->count() }} séance(s) liée(s)
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                <a href="{{ route('emplois-du-temps.index', ['centreId' => $centreId, 'edt_id' => $e->id, 'tab' => 'grille']) }}"
                   class="btn btn-sm rounded-3 flex-fill text-white" style="background:var(--fonce);font-size:11px;">
                    <i class="bi bi-grid-3x3 me-1"></i>Voir la grille
                </a>
                <form method="POST" action="{{ route('emplois-du-temps.destroy', ['centreId' => $centreId, 'edt' => $e->id]) }}"
                      class="d-inline" onsubmit="return confirm('Supprimer cet emploi du temps ?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm rounded-3"
                            style="background:#ffebee;color:#c62828;border:none;font-size:11px;">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
    </div>
    @endif
</div>{{-- /tab-liste --}}

@endsection

@push('scripts')
<script>
// ── Gestion onglets ──────────────────────────────────────────────────────
function switchTab(name) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.add('d-none'));
    document.querySelectorAll('#edtTabs .nav-link').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name)?.classList.remove('d-none');
    document.querySelector(`[onclick="switchTab('${name}')"]`)?.classList.add('active');
    // Mettre à jour l'URL sans rechargement
    const url = new URL(window.location);
    url.searchParams.set('tab', name);
    history.replaceState(null, '', url);
}

// ── Drag & drop upload ───────────────────────────────────────────────────
const dropZone = document.getElementById('dropZone');
if (dropZone) {
    ['dragenter','dragover'].forEach(ev => {
        dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    });
    ['dragleave','drop'].forEach(ev => {
        dropZone.addEventListener(ev, e => { e.preventDefault(); dropZone.classList.remove('dragover'); });
    });
    dropZone.addEventListener('drop', e => {
        const file = e.dataTransfer.files[0];
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('fileInput').files = dt.files;
        showFileName(document.getElementById('fileInput'));
    });
}

function showFileName(input) {
    const name = input.files[0]?.name || '';
    const display = document.getElementById('fileNameDisplay');
    if (display) {
        display.textContent = name ? name : '';
    }
}
</script>
@endpush
