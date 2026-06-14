@extends('layouts.app')
@section('titre', 'Planning — ' . $centre->nom)

@push('styles')
<style>
.filiere-block { border-radius:12px; overflow:hidden; margin-bottom:16px; border:1px solid rgba(0,0,0,.08); background:#fff; }
.filiere-header { padding:10px 18px; cursor:pointer; user-select:none; display:flex; align-items:center; justify-content:space-between; }
.filiere-header:hover { filter:brightness(.97); }
.niveau-header { padding:7px 18px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:8px; }
.mat-row { transition:background .15s; }
.mat-row:hover { background:#f8f5f0 !important; }
.mat-row.hidden-by-filter { display:none !important; }
.filiere-block.all-hidden .filiere-body { display:none; }
.filiere-block.collapsed .filiere-body { display:none; }
.chevron-icon { transition:transform .2s; }
.collapsed .chevron-icon { transform:rotate(-90deg); }
.search-highlight { background:#fef9c3; border-radius:2px; }

/* Barre filtre sticky */
.filter-bar { position:sticky; top:0; z-index:10; background:#fff; border-bottom:1px solid rgba(0,0,0,.08); padding:10px 0; margin-bottom:16px; }
</style>
@endpush

@section('content')

@if(session('succes'))
<div class="alert alert-success rounded-3 mb-3"><i class="bi bi-check-circle me-2"></i>{{ session('succes') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger rounded-3 mb-3">@foreach($errors->all() as $e)<div><i class="bi bi-exclamation-circle me-1"></i>{{ $e }}</div>@endforeach</div>
@endif

@if(!$annee)
<div class="alert alert-warning rounded-3">Aucune année scolaire active.</div>
@else

{{-- ── En-tête actions ──────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h5 class="fw-bold mb-0" style="color:var(--fonce);">Suivi heures & Génération planning</h5>
        <div style="font-size:12px;color:#888;">{{ $centre->nom }} · {{ $annee->libelle }}</div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('seances.index', $centreId) }}" class="btn btn-sm rounded-3"
           style="border:1px solid var(--marron);color:var(--marron);">
            <i class="bi bi-calendar3 me-1"></i>Voir le planning
        </a>
        <button class="btn btn-sm text-white rounded-3" style="background:var(--marron);"
                data-bs-toggle="modal" data-bs-target="#modalGenerer">
            <i class="bi bi-magic me-1"></i>Séances récurrentes
        </button>
        <button class="btn btn-sm text-white rounded-3" style="background:#1d4ed8;"
                data-bs-toggle="modal" data-bs-target="#modalMiSemestre">
            <i class="bi bi-calendar-week me-1"></i>Mi-semestre
        </button>
    </div>
</div>

{{-- ── Barre de filtres ─────────────────────────────────────────────────────── --}}
<div class="filter-bar">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        {{-- Recherche --}}
        <div class="input-group input-group-sm" style="max-width:220px;">
            <span class="input-group-text rounded-start-3 border-end-0" style="background:#f9f9f9;"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="flt_search" class="form-control rounded-end-3 border-start-0"
                   placeholder="Rechercher une matière…" style="font-size:12px;" oninput="applyFilters()">
        </div>

        {{-- Filière --}}
        @if($filieres->count() > 1)
        <select id="flt_filiere" class="form-select form-select-sm rounded-3" style="max-width:200px;font-size:12px;" onchange="applyFilters()">
            <option value="">Toutes les filières</option>
            @foreach($filieres as $f)
            <option value="{{ $f->id }}">{{ $f->nom }}</option>
            @endforeach
        </select>
        @endif

        {{-- Semestre --}}
        <div class="btn-group btn-group-sm" role="group">
            <input type="radio" class="btn-check" name="flt_sem" id="sem_all" value="" checked onchange="applyFilters()">
            <label class="btn btn-outline-secondary rounded-start-3" for="sem_all" style="font-size:11px;">Tout</label>
            <input type="radio" class="btn-check" name="flt_sem" id="sem1" value="1" onchange="applyFilters()">
            <label class="btn btn-outline-secondary" for="sem1" style="font-size:11px;">S1</label>
            <input type="radio" class="btn-check" name="flt_sem" id="sem2" value="2" onchange="applyFilters()">
            <label class="btn btn-outline-secondary rounded-end-3" for="sem2" style="font-size:11px;">S2</label>
        </div>

        {{-- Tout masquer / tout afficher --}}
        <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid #ddd;" onclick="toggleAll(false)">
            <i class="bi bi-arrows-collapse"></i> Tout replier
        </button>
        <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid #ddd;" onclick="toggleAll(true)">
            <i class="bi bi-arrows-expand"></i> Tout déplier
        </button>

        <span id="flt_count" style="font-size:11px;color:#888;margin-left:4px;"></span>
    </div>
</div>

{{-- ── Tableau groupé par Filière → Niveau ────────────────────────────────── --}}
@php
    $parFiliere = $matieres->groupBy('filiere_id');
@endphp

@forelse($parFiliere as $filiereId => $matsFiliere)
@php
    $filiere     = $matsFiliere->first()->filiere;
    $parOption   = $matsFiliere->groupBy(fn($m) => $m->niveau?->filiere_option_id ?? 0);
    $totalMats   = $matsFiliere->count();
    $totalHpR    = $matsFiliere->sum(fn($m) => max(0, $m->hp_initial - $m->hp_fait - $m->hp_planifie));
    $completees  = $matsFiliere->filter(fn($m) =>
        max(0, $m->hp_initial - $m->hp_fait - $m->hp_planifie) <= 0 &&
        (max(0, $m->tpe_initial - $m->tpe_fait - $m->tpe_planifie) <= 0 || $m->tpe_initial == 0) &&
        $m->a_composition
    )->count();
@endphp

<div class="filiere-block" data-filiere-id="{{ $filiereId }}"
     id="fb_{{ $filiereId }}">

    {{-- En-tête filière cliquable --}}
    <div class="filiere-header" style="background:var(--fonce);" onclick="toggleFiliere({{ $filiereId }})">
        <div class="d-flex align-items-center gap-3">
            <span class="badge rounded-2 px-2 py-1" style="background:var(--marron);font-family:monospace;font-size:11px;">
                {{ $filiere?->code ?? '?' }}
            </span>
            <span style="font-weight:700;font-size:14px;color:#fff;">{{ $filiere?->nom ?? 'Sans filière' }}</span>
            <span style="font-size:11px;color:rgba(255,255,255,.55);">
                {{ $totalMats }} matière(s) · {{ $completees }}/{{ $totalMats }} complètes
                @if($totalHpR > 0) · <span style="color:#fca5a5;">{{ round($totalHpR, 1) }}h HP restantes</span>@endif
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm rounded-3" style="background:rgba(255,255,255,.15);color:#fff;font-size:11px;border:1px solid rgba(255,255,255,.2);"
                    onclick="event.stopPropagation(); preselectFiliereMS({{ $filiereId }})"
                    data-bs-toggle="modal" data-bs-target="#modalMiSemestre">
                <i class="bi bi-calendar-week me-1"></i>Mi-semestre
            </button>
            <i class="bi bi-chevron-down chevron-icon text-white" style="font-size:12px;"></i>
        </div>
    </div>

    {{-- Corps filière --}}
    <div class="filiere-body">
    @foreach($parOption as $optId => $matsOption)
    @php
        $optObj  = $matsOption->first()->niveau?->filiereOption;
        $optUid  = $filiereId . '_' . $optId;
        $parNiv  = $matsOption->groupBy('niveau_id');
    @endphp

        {{-- Sous-en-tête option (cliquable) --}}
        <div class="border-top d-flex align-items-center justify-content-between"
             style="background:#f0ebe0;padding:7px 18px;cursor:pointer;user-select:none;"
             onclick="toggleOpt('{{ $optUid }}')">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-2 px-2 py-1" style="background:var(--marron);font-size:10px;font-family:monospace;">
                    {{ $optObj?->code ?? '?' }}
                </span>
                <span style="font-size:12px;font-weight:600;color:var(--fonce);">{{ $optObj?->nom ?? 'Option inconnue' }}</span>
            </div>
            <i class="bi bi-chevron-down" id="opt_chv_{{ $optUid }}" style="font-size:11px;transition:.2s;color:var(--fonce);"></i>
        </div>

        {{-- Corps option (collapsible) --}}
        <div id="opt_body_{{ $optUid }}">
        @foreach($parNiv as $niveauId => $matsNiveau)
        @php
            $niveau = $matsNiveau->first()->niveau;
            $nivUid = $filiereId . '_' . $niveauId;
        @endphp

            {{-- Sous-en-tête niveau (cliquable) --}}
            <div class="niveau-header border-top d-flex align-items-center justify-content-between"
                 style="background:#f5f0eb;cursor:pointer;"
                 onclick="toggleNiv('{{ $nivUid }}')">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-2" style="background:var(--fonce);color:var(--beige);font-size:10px;">
                        {{ $niveau?->code ?? '?' }}
                    </span>
                    <span style="color:var(--fonce);">{{ $niveau?->libelle ?? 'Niveau inconnu' }}</span>
                </div>
                <i class="bi bi-chevron-down" id="niv_chv_{{ $nivUid }}" style="font-size:11px;transition:.2s;color:#aaa;"></i>
            </div>

            {{-- Table matières (collapsible) --}}
            <div id="niv_body_{{ $nivUid }}">
            <table class="table table-sm mb-0" style="font-size:12px;">
                <thead style="background:#f9f8f6;">
                    <tr style="color:#777;">
                        <th class="px-4 py-1" style="min-width:30px;font-size:10px;">#</th>
                        <th style="min-width:60px;">Code</th>
                        <th style="min-width:180px;">Matière</th>
                        <th style="min-width:25px;">S</th>
                        <th colspan="3" class="text-center" style="background:#eef2ff;color:#3730a3;min-width:180px;">HP</th>
                        <th colspan="3" class="text-center" style="background:#fdf4ff;color:#6b21a8;min-width:160px;">TPE</th>
                        <th style="min-width:80px;">Compo</th>
                        <th style="min-width:70px;">Action</th>
                    </tr>
                    <tr style="font-size:10px;color:#999;background:#fafafa;">
                        <th class="px-4"></th><th></th><th></th><th></th>
                        <th class="text-center" style="background:#eef2ff;">Prévu</th>
                        <th class="text-center" style="background:#eef2ff;">Fait+Plan.</th>
                        <th class="text-center" style="background:#eef2ff;">Restant</th>
                        <th class="text-center" style="background:#fdf4ff;">Prévu</th>
                        <th class="text-center" style="background:#fdf4ff;">Fait+Plan.</th>
                        <th class="text-center" style="background:#fdf4ff;">Restant</th>
                        <th></th><th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($matsNiveau->sortBy('semestre') as $idx => $m)
                @php
                    $hpTotal      = $m->hp_fait + $m->hp_planifie;
                    $tpeTotal     = $m->tpe_fait + $m->tpe_planifie;
                    $hpRestFinal  = max(0, round($m->hp_initial  - $hpTotal,  1));
                    $tpeRestFinal = max(0, round($m->tpe_initial - $tpeTotal, 1));
                    $hpOk         = $hpRestFinal  <= 0;
                    $tpeOk        = $tpeRestFinal <= 0 || $m->tpe_initial == 0;
                    $tout_ok      = $hpOk && $tpeOk && ($m->a_composition || $m->hp_initial == 0);
                @endphp
                <tr class="mat-row {{ $tout_ok ? 'opacity-50' : '' }}"
                    data-filiere="{{ $filiereId }}"
                    data-sem="{{ $m->semestre }}"
                    data-search="{{ strtolower($m->nom . ' ' . $m->code) }}">
                    <td class="px-4 py-1" style="color:#ccc;font-size:10px;">{{ $loop->iteration }}</td>
                    <td class="py-1"><code style="font-size:10px;background:#f5f2ee;padding:1px 5px;border-radius:3px;">{{ $m->code }}</code></td>
                    <td class="py-1 fw-semibold" style="color:var(--fonce);font-size:12px;">{{ $m->nom }}</td>
                    <td class="py-1 text-center">
                        <span class="badge rounded-pill px-2" style="font-size:10px;background:{{ $m->semestre==1?'#e3f2fd':'#f3e5f5' }};color:{{ $m->semestre==1?'#1565c0':'#6a1b9a' }}">
                            S{{ $m->semestre }}
                        </span>
                    </td>

                    {{-- HP --}}
                    <td class="text-center py-1" style="background:#eef2ff;">
                        <span style="font-weight:700;color:#3730a3;font-size:12px;">{{ $m->hp_initial }}h</span>
                    </td>
                    <td class="text-center py-1" style="background:#eef2ff;">
                        <span style="color:#4f46e5;">{{ $m->hp_fait }}h</span>
                        @if($m->hp_planifie > 0)<span style="color:#a5b4fc;font-size:10px;"> +{{ $m->hp_planifie }}</span>@endif
                    </td>
                    <td class="text-center py-1" style="background:#eef2ff;">
                        @if($hpOk)
                        <span class="badge rounded-pill" style="background:#d1fae5;color:#065f46;font-size:10px;">✓</span>
                        @else
                        <span style="font-weight:700;color:#dc2626;font-size:12px;">{{ $hpRestFinal }}h</span>
                        @endif
                    </td>

                    {{-- TPE --}}
                    <td class="text-center py-1" style="background:#fdf4ff;">
                        <span style="font-weight:700;color:#6b21a8;font-size:12px;">{{ $m->tpe_initial }}h</span>
                    </td>
                    <td class="text-center py-1" style="background:#fdf4ff;">
                        <span style="color:#9333ea;">{{ $m->tpe_fait }}h</span>
                        @if($m->tpe_planifie > 0)<span style="color:#d8b4fe;font-size:10px;"> +{{ $m->tpe_planifie }}</span>@endif
                        @if(!$hpOk && $m->tpe_initial > 0)
                        <i class="bi bi-lock-fill text-muted ms-1" title="HP non complètes — TPE bloqué" style="font-size:10px;"></i>
                        @endif
                    </td>
                    <td class="text-center py-1" style="background:#fdf4ff;">
                        @if($tpeOk)<span class="badge rounded-pill" style="background:#d1fae5;color:#065f46;font-size:10px;">✓</span>
                        @elseif($m->tpe_initial == 0)<span style="color:#aaa;font-size:10px;">—</span>
                        @else<span style="font-weight:700;color:#dc2626;font-size:12px;">{{ $tpeRestFinal }}h</span>@endif
                    </td>

                    <td class="text-center py-1">
                        @if($m->a_composition)
                        <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;font-size:10px;">✏</span>
                        @else
                        <span style="color:#ddd;font-size:10px;">—</span>
                        @endif
                    </td>

                    <td class="py-1">
                        @if(!$hpOk)
                        <button class="btn btn-sm rounded-3 text-white py-0 px-2"
                                style="font-size:10px;background:var(--marron);"
                                onclick="preselectMatiere({{ $m->id }}, {{ max(0, round($m->hp_initial - $m->hp_fait - $m->hp_planifie, 1)) }}, {{ max(0, round($m->tpe_initial - $m->tpe_fait - $m->tpe_planifie, 1)) }})"
                                data-bs-toggle="modal" data-bs-target="#modalGenerer"
                                title="Générer séances HP">
                            <i class="bi bi-magic"></i> HP
                        </button>
                        @elseif(!$tpeOk && $m->tpe_initial > 0)
                        <button class="btn btn-sm rounded-3 text-white py-0 px-2"
                                style="font-size:10px;background:#7c3aed;"
                                onclick="preselectMatiereTPE({{ $m->id }})"
                                data-bs-toggle="modal" data-bs-target="#modalGenerer"
                                title="Générer séances TPE">
                            <i class="bi bi-magic"></i> TPE
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>{{-- /niv_body --}}
        @endforeach
        </div>{{-- /opt_body --}}
    @endforeach
    </div>
</div>
@empty
<div class="text-center py-5" style="color:#bbb;">
    <i class="bi bi-inbox" style="font-size:32px;"></i>
    <p class="mt-2">Aucune matière trouvée pour ce centre.</p>
</div>
@endforelse

@endif {{-- fin si annee --}}

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL — GÉNÉRATION SÉANCES RÉCURRENTES (par matière)
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalGenerer" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:var(--fonce);">
                <h5 class="modal-title fw-bold text-white"><i class="bi bi-magic me-2"></i>Générer des séances récurrentes</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('planning.generer', $centreId) }}">
                @csrf
                <div class="modal-body p-4">
                    {{-- Filtres matière (UX à grande échelle) --}}
                    @if($filieres->count() > 1)
                    <div class="row g-2 mb-3 p-3 rounded-3" style="background:#f8f9fa;">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:12px;">Filtrer par filière</label>
                            <select id="gen_flt_filiere" class="form-select form-select-sm rounded-3" onchange="filtrerMatieresGen()">
                                <option value="">Toutes les filières</option>
                                @foreach($filieres as $f)
                                <option value="{{ $f->id }}">{{ $f->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size:12px;">Semestre</label>
                            <select id="gen_flt_sem" class="form-select form-select-sm rounded-3" onchange="filtrerMatieresGen()">
                                <option value="">Tout</option>
                                <option value="1">S1</option>
                                <option value="2">S2</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size:12px;">Type</label>
                            <select id="gen_flt_complet" class="form-select form-select-sm rounded-3" onchange="filtrerMatieresGen()">
                                <option value="">Toutes</option>
                                <option value="incomplete">HP non complètes</option>
                            </select>
                        </div>
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Matière *</label>
                            <select name="matiere_id" id="gen_matiere" class="form-select rounded-3" required>
                                <option value="">— Sélectionner —</option>
                                @foreach($matieres->sortBy(['filiere_id','niveau_id','semestre','nom']) as $m)
                                @php $hpR = max(0, round($m->hp_initial - $m->hp_fait - $m->hp_planifie, 1)); @endphp
                                <option value="{{ $m->id }}"
                                        data-filiere="{{ $m->filiere_id }}"
                                        data-sem="{{ $m->semestre }}"
                                        data-hp="{{ $hpR }}"
                                        data-tpe="{{ max(0, round($m->tpe_initial - $m->tpe_fait - $m->tpe_planifie, 1)) }}"
                                        data-hp-ok="{{ $hpR <= 0 ? '1' : '0' }}">
                                    [{{ $m->filiere?->code }}/S{{ $m->semestre }}] {{ $m->code }} — {{ $m->nom }}
                                    (HP rest.: {{ $hpR }}h)
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Type *</label>
                            <select name="type" id="gen_type" class="form-select rounded-3" onchange="calculerNbSemaines()" required>
                                <option value="HP">HP — Heures Professeur</option>
                                <option value="TPE">TPE — Travaux Encadrés</option>
                            </select>
                            <div id="gen_tpe_warning" class="mt-1" style="display:none;">
                                <small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>HP non complètes — TPE sera bloqué.</small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" style="font-size:13px;">Professeur *</label>
                            <select name="professeur_id" class="form-select rounded-3" required>
                                <option value="">— Sélectionner —</option>
                                @foreach($profs as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Salle *</label>
                            <select name="salle_id" class="form-select rounded-3" required>
                                <option value="">— Sélectionner —</option>
                                @foreach($salles as $s)
                                <option value="{{ $s->id }}">{{ $s->nom }} ({{ $s->capacite }} pl.)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Groupes *</label>
                            <select name="option_ids[]" class="form-select rounded-3" multiple size="4" required>
                                @foreach($groupes as $g)
                                <option value="{{ $g->id }}">{{ $g->nom }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size:11px;">Ctrl+clic pour plusieurs</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Jour *</label>
                            <select name="jour_semaine" class="form-select rounded-3" required>
                                <option value="1">Lundi</option><option value="2">Mardi</option>
                                <option value="3">Mercredi</option><option value="4">Jeudi</option>
                                <option value="5">Vendredi</option><option value="6">Samedi</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Heure début *</label>
                            <input type="time" name="heure_debut" id="gen_hdebut" class="form-control rounded-3"
                                   value="07:30" required onchange="calculerNbSemaines()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Durée *</label>
                            <select name="duree_heures" id="gen_duree" class="form-select rounded-3" required onchange="calculerNbSemaines()">
                                <option value="3">3 heures</option>
                                <option value="4">4 heures</option>
                            </select>
                            <small class="text-muted" id="gen_fin_preview" style="font-size:11px;"></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">À partir du *</label>
                            <input type="date" name="date_debut" class="form-control rounded-3"
                                   value="{{ today()->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Nombre de semaines *</label>
                            <div class="input-group">
                                <input type="number" name="nb_semaines" id="gen_nb_semaines" class="form-control rounded-start-3"
                                       value="1" min="1" max="52" required>
                                <span class="input-group-text rounded-end-3" style="font-size:11px;">sem.</span>
                            </div>
                            <small class="text-muted" style="font-size:11px;" id="gen_info_heures"></small>
                        </div>
                    </div>
                    <div class="rounded-3 p-3 mt-3" style="background:#f0fdf4;border:1px solid #bbf7d0;" id="gen_recap">
                        <div style="font-size:12px;color:#166534;"><i class="bi bi-info-circle me-1"></i>Sélectionnez une matière pour voir l'estimation.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn rounded-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn text-white rounded-3" style="background:var(--marron);">
                        <i class="bi bi-magic me-1"></i>Générer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     MODAL — PLANIFICATION MI-SEMESTRE
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalMiSemestre" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-4">
            <div class="modal-header" style="background:#1d4ed8;">
                <h5 class="modal-title fw-bold text-white">
                    <i class="bi bi-calendar-week me-2"></i>Planification Mi-semestre
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('planning.generer-mi-semestre', $centreId) }}" id="formMiSemestre">
                @csrf
                <div class="modal-body p-4">

                    <div class="rounded-3 p-2 mb-3" style="background:#eff6ff;border:1px solid #bfdbfe;font-size:12px;color:#1e40af;">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Algorithme rotatif :</strong> Les matières HP se partagent les jours de cours en alternance.
                        Dès qu'une matière épuise son quota HP → sa composition est programmée dans la salle dédiée
                        (la salle de cours reste libre pour les autres groupes).
                        <strong>Les HP doivent être entièrement planifiés avant les TPE.</strong>
                    </div>

                    <div class="row g-4">
                        {{-- ── Col gauche : liste matières ordonnée ── --}}
                        <div class="col-lg-5">
                            <div class="fw-semibold mb-2" style="font-size:13px;color:var(--fonce);">
                                <i class="bi bi-list-ol me-1"></i>Matières du mi-semestre
                                <span class="fw-normal text-muted" style="font-size:11px;">(glisser pour réordonner)</span>
                            </div>

                            {{-- Filtres rapides pour trouver une matière --}}
                            <div class="row g-1 mb-2">
                                <div class="col-7">
                                    <select id="ms_flt_filiere" class="form-select form-select-sm rounded-3"
                                            style="font-size:11px;" onchange="filtrerMatiereMS()">
                                        <option value="">Toutes filières</option>
                                        @foreach($filieres as $f)
                                        <option value="{{ $f->id }}">{{ $f->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-5">
                                    <select id="ms_flt_sem" class="form-select form-select-sm rounded-3"
                                            style="font-size:11px;" onchange="filtrerMatiereMS()">
                                        <option value="">S1 + S2</option>
                                        <option value="1">Semestre 1</option>
                                        <option value="2">Semestre 2</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Zone drag-drop --}}
                            <div id="ms_liste" class="rounded-3 border p-2 mb-2"
                                 style="min-height:120px;max-height:320px;overflow-y:auto;background:#f8fafc;"
                                 ondragover="event.preventDefault()" ondrop="dropMatiere(event)">
                                <div id="ms_vide" class="text-center py-3" style="color:#bbb;font-size:12px;">
                                    <i class="bi bi-arrow-down-circle me-1"></i>Ajoutez des matières
                                </div>
                            </div>

                            {{-- Ajout depuis dropdown --}}
                            <div class="input-group input-group-sm mb-1">
                                <select id="ms_add_sel" class="form-select rounded-start-3" style="font-size:11px;">
                                    <option value="">— Ajouter une matière —</option>
                                    @foreach($matieres->sortBy(['filiere_id','niveau_id','semestre','nom']) as $m)
                                    @php $hpR = max(0, round($m->hp_initial - $m->hp_fait - $m->hp_planifie, 1)); @endphp
                                    <option value="{{ $m->id }}"
                                            data-nom="{{ $m->nom }}"
                                            data-code="{{ $m->code }}"
                                            data-sem="{{ $m->semestre }}"
                                            data-filiere="{{ $m->filiere_id }}"
                                            data-filiere-nom="{{ $m->filiere?->code }}"
                                            data-niveau="{{ $m->niveau?->libelle }}"
                                            data-hp="{{ $hpR }}">
                                        [{{ $m->filiere?->code }}/S{{ $m->semestre }}] {{ $m->code }} — {{ $m->nom }} ({{ $hpR }}h HP rest.)
                                    </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm text-white rounded-end-3 px-3"
                                        style="background:#1d4ed8;" onclick="ajouterMatiere()">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>

                            {{-- Récap estimation --}}
                            <div id="ms_recap" style="display:none;font-size:11px;color:#166534;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:8px;"></div>
                        </div>

                        {{-- ── Col droite : paramètres horaires ── --}}
                        <div class="col-lg-7">
                            <div class="row g-3">
                                {{-- Groupes cours --}}
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Groupes pour les cours HP *</label>
                                    <select name="option_ids[]" class="form-select rounded-3" multiple size="4" required>
                                        @foreach($groupes as $g)
                                        <option value="{{ $g->id }}">{{ $g->nom }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted" style="font-size:11px;">Ctrl+clic pour plusieurs</small>
                                </div>

                                {{-- Groupes autres centres pour compo --}}
                                @if($autresOptions->count())
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size:13px;">
                                        <i class="bi bi-building me-1 text-warning"></i>
                                        Groupes autres centres (compositions)
                                        <span class="badge rounded-pill ms-1" style="background:#fff3e0;color:#e65100;font-size:10px;">Multi-centre</span>
                                    </label>
                                    <select name="option_ids_compo[]" class="form-select rounded-3" multiple size="3">
                                        @foreach($autresOptions->groupBy(fn($o) => $o->centre->nom) as $cNom => $opts)
                                        <optgroup label="{{ $cNom }}">
                                            @foreach($opts as $o)<option value="{{ $o->id }}">{{ $o->nom }}</option>@endforeach
                                        </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                @endif

                                {{-- Salles --}}
                                <div class="col-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Salle de cours *</label>
                                    <select name="salle_cours_id" class="form-select rounded-3" required>
                                        <option value="">—</option>
                                        @foreach($salles as $s)
                                        <option value="{{ $s->id }}">{{ $s->nom }} ({{ $s->capacite }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Salle composition *</label>
                                    <select name="salle_compo_id" class="form-select rounded-3" required>
                                        <option value="">—</option>
                                        @foreach($salles as $s)
                                        <option value="{{ $s->id }}">{{ $s->nom }} ({{ $s->capacite }})</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted" style="font-size:11px;">Idéalement différente de la salle cours</small>
                                </div>

                                {{-- Surveillant --}}
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Responsable surveillance compositions *</label>
                                    <select name="surveillant_id" class="form-select rounded-3" required>
                                        <option value="">—</option>
                                        @foreach($profs as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Date + jours --}}
                                <div class="col-5">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Date de début *</label>
                                    <input type="date" name="date_debut" class="form-control rounded-3"
                                           value="{{ today()->toDateString() }}" required onchange="recalcMiSemestre()">
                                </div>
                                <div class="col-7">
                                    <label class="form-label fw-semibold d-block" style="font-size:13px;">Jours de cours *</label>
                                    <div class="d-flex gap-2 flex-wrap pt-1">
                                        @foreach([1=>'Lun',2=>'Mar',3=>'Mer',4=>'Jeu',5=>'Ven',6=>'Sam'] as $j=>$lbl)
                                        <div class="form-check form-check-inline m-0">
                                            <input class="form-check-input" type="checkbox" name="jours_cours[]"
                                                   value="{{ $j }}" id="ms_j{{ $j }}"
                                                   {{ in_array($j,[1,3,5])?'checked':'' }}
                                                   onchange="recalcMiSemestre()">
                                            <label class="form-check-label" for="ms_j{{ $j }}" style="font-size:12px;">{{ $lbl }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Cours : heure + durée --}}
                                <div class="col-4">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Heure cours *</label>
                                    <input type="time" name="heure_debut_cours" id="ms_hdebut_cours"
                                           class="form-control rounded-3" value="07:30" required onchange="recalcMiSemestre()">
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Durée cours *</label>
                                    <select name="duree_cours" id="ms_duree_cours" class="form-select rounded-3" required onchange="recalcMiSemestre()">
                                        <option value="3">3 h</option>
                                        <option value="4">4 h</option>
                                    </select>
                                    <small class="text-muted" id="ms_fin_cours" style="font-size:11px;"></small>
                                </div>

                                {{-- Composition : jour + heure + durée --}}
                                <div class="col-4">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Jour composition *</label>
                                    <select name="jour_compo" class="form-select rounded-3" required>
                                        <option value="6">Samedi</option>
                                        <option value="5">Vendredi</option>
                                        <option value="4">Jeudi</option>
                                        <option value="3">Mercredi</option>
                                        <option value="2">Mardi</option>
                                        <option value="1">Lundi</option>
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Heure compo *</label>
                                    <input type="time" name="heure_debut_compo" class="form-control rounded-3" value="07:30" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Durée compo *</label>
                                    <select name="duree_compo" class="form-select rounded-3" required>
                                        <option value="3">3 h</option>
                                        <option value="4">4 h</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Récap global --}}
                    <div id="ms_recap_global" class="rounded-3 p-3 mt-3" style="background:#eff6ff;border:1px solid #bfdbfe;display:none;font-size:12px;color:#1e40af;"></div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn rounded-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn text-white rounded-3" style="background:#1d4ed8;">
                        <i class="bi bi-calendar-week me-1"></i>Générer le planning mi-semestre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ══════════════════════════════════════════════════════════════════════════════
// Filtrage global du tableau
// ══════════════════════════════════════════════════════════════════════════════
function applyFilters() {
    const search  = document.getElementById('flt_search')?.value.toLowerCase().trim() || '';
    const filiere = document.getElementById('flt_filiere')?.value || '';
    const sem     = document.querySelector('input[name="flt_sem"]:checked')?.value || '';

    let visible = 0;
    document.querySelectorAll('.mat-row').forEach(row => {
        const matchS   = !search  || row.dataset.search.includes(search);
        const matchF   = !filiere || row.dataset.filiere === filiere;
        const matchSem = !sem     || row.dataset.sem === sem;
        const show = matchS && matchF && matchSem;
        row.classList.toggle('hidden-by-filter', !show);
        if (show) visible++;
    });

    // Cacher/montrer les blocs filière
    document.querySelectorAll('.filiere-block').forEach(block => {
        const hasVisible = block.querySelectorAll('.mat-row:not(.hidden-by-filter)').length > 0;
        block.style.display = hasVisible ? '' : 'none';
        if (hasVisible) block.classList.remove('collapsed');
    });

    // Cacher/montrer les corps option
    document.querySelectorAll('[id^="opt_body_"]').forEach(el => {
        const hasVis = el.querySelectorAll('.mat-row:not(.hidden-by-filter)').length > 0;
        el.style.display = hasVis ? '' : 'none';
        const chv = document.getElementById('opt_chv_' + el.id.replace('opt_body_', ''));
        if (chv) chv.style.transform = hasVis ? '' : 'rotate(-90deg)';
    });

    // Cacher/montrer les corps niveau
    document.querySelectorAll('[id^="niv_body_"]').forEach(el => {
        const hasVis = el.querySelectorAll('.mat-row:not(.hidden-by-filter)').length > 0;
        el.style.display = hasVis ? '' : 'none';
        const chv = document.getElementById('niv_chv_' + el.id.replace('niv_body_', ''));
        if (chv) chv.style.transform = hasVis ? '' : 'rotate(-90deg)';
    });

    const countEl = document.getElementById('flt_count');
    if (countEl) countEl.textContent = search || filiere || sem ? `${visible} matière(s) affichée(s)` : '';
}

function toggleAll(expand) {
    document.querySelectorAll('.filiere-block').forEach(b => {
        if (b.style.display !== 'none') b.classList.toggle('collapsed', !expand);
    });
    document.querySelectorAll('[id^="opt_body_"],[id^="niv_body_"]').forEach(el => {
        el.style.display = expand ? '' : 'none';
    });
    document.querySelectorAll('[id^="opt_chv_"],[id^="niv_chv_"]').forEach(c => {
        c.style.transform = expand ? '' : 'rotate(-90deg)';
    });
}

function toggleFiliere(id) {
    const block = document.getElementById('fb_' + id);
    if (block) block.classList.toggle('collapsed');
}

function toggleOpt(uid) {
    const body = document.getElementById('opt_body_' + uid);
    const chv  = document.getElementById('opt_chv_' + uid);
    if (!body) return;
    const open = body.style.display !== 'none';
    body.style.display = open ? 'none' : '';
    if (chv) chv.style.transform = open ? 'rotate(-90deg)' : '';
}

function toggleNiv(uid) {
    const body = document.getElementById('niv_body_' + uid);
    const chv  = document.getElementById('niv_chv_' + uid);
    if (!body) return;
    const open = body.style.display !== 'none';
    body.style.display = open ? 'none' : '';
    if (chv) chv.style.transform = open ? 'rotate(-90deg)' : '';
}

// ══════════════════════════════════════════════════════════════════════════════
// Modal génération par matière
// ══════════════════════════════════════════════════════════════════════════════
function preselectMatiere(id, hpR, tpeR) {
    const sel = document.getElementById('gen_matiere');
    if (sel) { sel.value = id; filtrerMatieresGen(); calculerNbSemaines(); }
    const t = document.getElementById('gen_type');
    if (t) { t.value = 'HP'; calculerNbSemaines(); }
}

function preselectMatiereTPE(id) {
    const sel = document.getElementById('gen_matiere');
    if (sel) { sel.value = id; filtrerMatieresGen(); calculerNbSemaines(); }
    const t = document.getElementById('gen_type');
    if (t) { t.value = 'TPE'; calculerNbSemaines(); }
}

function filtrerMatieresGen() {
    const filiereId = document.getElementById('gen_flt_filiere')?.value || '';
    const sem       = document.getElementById('gen_flt_sem')?.value || '';
    const complet   = document.getElementById('gen_flt_complet')?.value || '';
    const sel       = document.getElementById('gen_matiere');
    if (!sel) return;
    Array.from(sel.options).forEach(opt => {
        if (!opt.value) return;
        const mF = !filiereId || opt.dataset.filiere === filiereId;
        const mS = !sem       || opt.dataset.sem === sem;
        const mC = !complet   || (complet === 'incomplete' && opt.dataset.hpOk === '0');
        opt.style.display = (mF && mS && mC) ? '' : 'none';
    });
    // Reset sélection si cachée
    if (sel.selectedOptions.length && sel.selectedOptions[0].style.display === 'none') sel.value = '';
    calculerNbSemaines();
}

function calculerNbSemaines() {
    const matSel  = document.getElementById('gen_matiere');
    const typeSel = document.getElementById('gen_type');
    const hdebut  = document.getElementById('gen_hdebut')?.value;
    const durSel  = document.getElementById('gen_duree');
    const nbInput = document.getElementById('gen_nb_semaines');
    const info    = document.getElementById('gen_info_heures');
    const recap   = document.getElementById('gen_recap');
    const finPrev = document.getElementById('gen_fin_preview');
    const warn    = document.getElementById('gen_tpe_warning');
    const dureeH  = durSel ? parseInt(durSel.value) : 0;

    if (hdebut && dureeH && finPrev) {
        const [h, m] = hdebut.split(':').map(Number);
        const fMin = h * 60 + m + dureeH * 60;
        finPrev.textContent = `Fin : ${String(Math.floor(fMin/60)%24).padStart(2,'0')}h${String(fMin%60).padStart(2,'0')}`;
    }

    // Avertissement TPE si HP non complètes
    if (warn && matSel?.value && typeSel?.value === 'TPE') {
        const opt = matSel.options[matSel.selectedIndex];
        warn.style.display = opt?.dataset.hpOk === '0' ? '' : 'none';
    } else if (warn) warn.style.display = 'none';

    if (!matSel?.value || !hdebut || !dureeH) return;
    const opt     = matSel.options[matSel.selectedIndex];
    const type    = typeSel?.value || 'HP';
    const restant = parseFloat(type === 'HP' ? opt.dataset.hp : opt.dataset.tpe || 0);
    const nbS     = restant > 0 ? Math.ceil(restant / dureeH) : 1;
    if (nbInput) nbInput.value = nbS;
    if (info) info.textContent = `${dureeH}h/séance — ${restant}h restantes → ${nbS} séance(s)`;
    if (recap) recap.innerHTML = `<div style="font-size:12px;color:#166534;"><i class="bi bi-calendar-check me-1"></i><strong>${nbS}</strong> séance(s) de <strong>${dureeH}h</strong> = <strong>${nbS*dureeH}h</strong> de ${type}.</div>`;
}

document.getElementById('gen_matiere')?.addEventListener('change', calculerNbSemaines);
document.getElementById('gen_type')?.addEventListener('change', calculerNbSemaines);

// ══════════════════════════════════════════════════════════════════════════════
// Modal Mi-semestre
// ══════════════════════════════════════════════════════════════════════════════
const msMatieresData = {};
let msDragSrc = null;

function filtrerMatiereMS() {
    const fId = document.getElementById('ms_flt_filiere')?.value || '';
    const sem = document.getElementById('ms_flt_sem')?.value || '';
    const sel = document.getElementById('ms_add_sel');
    if (!sel) return;
    Array.from(sel.options).forEach(opt => {
        if (!opt.value) return;
        const mF = !fId || opt.dataset.filiere === fId;
        const mS = !sem || opt.dataset.sem === sem;
        opt.style.display = (mF && mS) ? '' : 'none';
    });
}

function ajouterMatiere() {
    const sel = document.getElementById('ms_add_sel');
    if (!sel?.value) return;
    const id  = sel.value;
    if (document.getElementById('ms_item_' + id)) return;
    const opt = sel.options[sel.selectedIndex];

    msMatieresData[id] = {
        nom: opt.dataset.nom, code: opt.dataset.code, sem: opt.dataset.sem,
        filiereNom: opt.dataset.filiereNom, niveau: opt.dataset.niveau,
        hp: parseFloat(opt.dataset.hp || 0)
    };

    document.getElementById('ms_vide').style.display = 'none';
    const item = document.createElement('div');
    item.id = 'ms_item_' + id;
    item.className = 'ms-item d-flex align-items-center gap-2 p-2 mb-1 rounded-3 bg-white border';
    item.style.cursor = 'grab';
    item.draggable = true;
    item.dataset.id = id;
    item.innerHTML = `
        <input type="hidden" name="matiere_ids[]" value="${id}">
        <i class="bi bi-grip-vertical text-muted" style="font-size:13px;"></i>
        <span class="badge rounded-pill" style="font-size:10px;background:#dbeafe;color:#1e40af;">S${opt.dataset.sem}</span>
        <span style="font-size:11px;background:#f0ebe4;padding:1px 5px;border-radius:3px;font-family:monospace;">${opt.dataset.code}</span>
        <span style="font-size:12px;flex:1;font-weight:600;">${opt.dataset.nom}</span>
        <span style="font-size:11px;color:#6b7280;">${opt.dataset.hp}h HP</span>
        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="supprimerMatiere('${id}')">
            <i class="bi bi-x-circle"></i>
        </button>`;

    item.addEventListener('dragstart', e => { msDragSrc = item; e.dataTransfer.effectAllowed = 'move'; });
    item.addEventListener('dragover',  e => { e.preventDefault(); item.style.background = '#dbeafe'; });
    item.addEventListener('dragleave', () => item.style.background = '');
    item.addEventListener('drop', e => {
        e.preventDefault(); item.style.background = '';
        if (msDragSrc && msDragSrc !== item) {
            const liste = document.getElementById('ms_liste');
            const items = [...liste.querySelectorAll('.ms-item')];
            if (items.indexOf(msDragSrc) < items.indexOf(item))
                liste.insertBefore(msDragSrc, item.nextSibling);
            else liste.insertBefore(msDragSrc, item);
        }
    });
    item.addEventListener('dragend', () => { msDragSrc = null; recalcMiSemestre(); });

    document.getElementById('ms_liste').appendChild(item);
    sel.value = '';
    recalcMiSemestre();
}

function supprimerMatiere(id) {
    document.getElementById('ms_item_' + id)?.remove();
    delete msMatieresData[id];
    if (!document.querySelectorAll('#ms_liste .ms-item').length)
        document.getElementById('ms_vide').style.display = '';
    recalcMiSemestre();
}

function dropMatiere(e) { e.preventDefault(); }

function preselectFiliereMS(filiereId) {
    // Vider et repeupler avec les matières de la filière
    document.querySelectorAll('#ms_liste .ms-item').forEach(el => el.remove());
    Object.keys(msMatieresData).forEach(id => delete msMatieresData[id]);
    document.getElementById('ms_vide').style.display = '';

    // Appliquer filtre filière dans le dropdown
    const fltFil = document.getElementById('ms_flt_filiere');
    if (fltFil) { fltFil.value = filiereId; filtrerMatiereMS(); }

    // Ajouter les matières non-complètes de cette filière
    const sel = document.getElementById('ms_add_sel');
    Array.from(sel.options).forEach(opt => {
        if (opt.value && opt.dataset.filiere == filiereId && opt.style.display !== 'none') {
            sel.value = opt.value; ajouterMatiere();
        }
    });
}

function recalcMiSemestre() {
    const items  = [...document.querySelectorAll('#ms_liste .ms-item')];
    const dureeH = parseInt(document.getElementById('ms_duree_cours')?.value || 3);
    const hdebut = document.getElementById('ms_hdebut_cours')?.value;
    const finEl  = document.getElementById('ms_fin_cours');
    const recap  = document.getElementById('ms_recap');
    const recapG = document.getElementById('ms_recap_global');

    if (hdebut && dureeH && finEl) {
        const [h, m] = hdebut.split(':').map(Number);
        const fMin = h * 60 + m + dureeH * 60;
        finEl.textContent = `Fin : ${String(Math.floor(fMin/60)%24).padStart(2,'0')}h${String(fMin%60).padStart(2,'0')}`;
    }

    if (!items.length) { recap.style.display='none'; recapG.style.display='none'; return; }

    let totalS = 0, html = '<ul class="mb-0 ps-3">';
    items.forEach(item => {
        const d = msMatieresData[item.dataset.id] || {};
        const nb = d.hp > 0 ? Math.ceil(d.hp / dureeH) : 0;
        totalS += nb;
        html += `<li><strong>${d.code}</strong> — ${d.nom} : ${nb} cours HP + 1 compo (${d.hp}h ÷ ${dureeH}h)</li>`;
    });
    html += '</ul>';
    recap.innerHTML = html; recap.style.display = 'block';

    const jours = [...document.querySelectorAll('input[name="jours_cours[]"]:checked')].length;
    const semEst = jours > 0 ? Math.ceil(totalS / jours) : '?';
    recapG.style.display = 'block';
    recapG.innerHTML = `<i class="bi bi-calendar2-range me-2"></i>
        <strong>${items.length}</strong> matière(s) ·
        <strong>${totalS}</strong> cours HP + <strong>${items.length}</strong> compositions ·
        ${jours} jour(s)/semaine → durée estimée <strong>~${semEst} semaine(s)</strong>`;
}

document.getElementById('modalMiSemestre')?.addEventListener('show.bs.modal', recalcMiSemestre);
document.getElementById('ms_duree_cours')?.addEventListener('change', recalcMiSemestre);
</script>
@endpush
@endsection
