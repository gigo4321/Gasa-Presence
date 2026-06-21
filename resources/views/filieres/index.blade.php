@extends('layouts.app')
@section('titre','Référentiel Pédagogique')

@push('styles')
<style>
.fil-card { border-radius:12px; overflow:hidden; border:1px solid rgba(0,0,0,.08); margin-bottom:12px; }
.fil-header { padding:10px 18px; cursor:pointer; user-select:none; }
.fil-header:hover { filter:brightness(.95); }
.fil-card.collapsed .fil-body { display:none; }
.chevron { transition:transform .2s; }
.fil-card.collapsed .chevron { transform:rotate(-90deg); }
.mat-row-fil { transition:background .12s; }
.mat-row-fil:hover { background:#faf7f4 !important; }
.mat-row-fil.hidden-fil { display:none !important; }
.opt-row, .niv-row { transition:background .1s; }
/* Barre de recherche sticky */
.ref-bar { position:sticky; top:0; z-index:20; background:#fff; padding:10px 0 8px; border-bottom:1px solid rgba(0,0,0,.07); margin-bottom:12px; }
</style>
@endpush

@section('content')

{{-- ── Barre outils sticky ──────────────────────────────────────────────────── --}}
<div class="ref-bar">
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div class="d-flex gap-2 align-items-center flex-wrap">
        {{-- Recherche --}}
        <div class="input-group input-group-sm" style="max-width:240px;">
            <span class="input-group-text rounded-start-3 border-end-0" style="background:#f9f9f9;"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="ref_search" class="form-control rounded-end-3 border-start-0"
                   placeholder="Rechercher code, matière…" style="font-size:12px;" oninput="refSearch()">
        </div>
        {{-- Filtre archivé --}}
        <div class="form-check form-check-inline mb-0 ms-1">
            <input class="form-check-input" type="checkbox" id="ref_show_arch" onchange="refSearch()">
            <label class="form-check-label" for="ref_show_arch" style="font-size:12px;">Afficher archivés</label>
        </div>
        {{-- Collapse all --}}
        <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid #ddd;" onclick="refToggleAll(false)">
            <i class="bi bi-arrows-collapse"></i> Replier tout
        </button>
        <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid #ddd;" onclick="refToggleAll(true)">
            <i class="bi bi-arrows-expand"></i> Déplier tout
        </button>
        <span id="ref_count" style="font-size:11px;color:#999;"></span>
    </div>
    <button class="btn btn-sm text-white rounded-3 px-3" style="background:var(--fonce);"
            data-bs-toggle="modal" data-bs-target="#modalFiliere">
        <i class="bi bi-plus-lg me-1"></i> Nouvelle filière
    </button>
</div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><span class="stat-icon"><i class="bi bi-diagram-3"></i></span><div><div class="stat-value">{{ $filieres->where('archive',false)->count() }}</div><div class="stat-label">Filières actives</div></div></div></div>
    <div class="col-md-3"><div class="stat-card" style="background:#e3f2fd;"><span class="stat-icon"><i class="bi bi-folder2-open"></i></span><div><div class="stat-value">{{ $filieres->sum(fn($f)=>$f->filiereOptions->where('archive',false)->count()) }}</div><div class="stat-label">Options actives</div></div></div></div>
    <div class="col-md-3"><div class="stat-card" style="background:#e8f5e9;"><span class="stat-icon"><i class="bi bi-layers"></i></span><div><div class="stat-value">{{ $filieres->sum(fn($f)=>$f->filiereOptions->sum(fn($o)=>$o->niveaux->where('archive',false)->count())) }}</div><div class="stat-label">Niveaux actifs</div></div></div></div>
    <div class="col-md-3"><div class="stat-card" style="background:#f3e5f5;"><span class="stat-icon"><i class="bi bi-book"></i></span><div><div class="stat-value">{{ $filieres->sum(fn($f)=>$f->filiereOptions->sum(fn($o)=>$o->niveaux->sum(fn($n)=>$n->matieres->where('archive',false)->count()))) }}</div><div class="stat-label">Matières actives</div></div></div></div>
</div>

@forelse($filieres as $filiere)
<div class="fil-card bg-white {{ $filiere->archive?'opacity-60':'' }}" id="fil_{{ $filiere->id }}">

    {{-- En-tête filière cliquable --}}
    <div class="fil-header d-flex align-items-center justify-content-between"
         style="background:{{ $filiere->archive?'#9e9e9e':'var(--fonce)' }};"
         onclick="refToggle({{ $filiere->id }})">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-chevron-down chevron text-white" style="font-size:12px;"></i>
            <span class="badge rounded-2 px-2 py-1" style="background:var(--marron);font-family:monospace;font-size:12px;">{{ $filiere->code }}</span>
            <div>
                <div style="font-weight:700;font-size:14px;color:#fff;">{{ $filiere->nom }}</div>
                <div style="font-size:11px;color:rgba(255,255,255,.5);">
                    {{ $filiere->filiereOptions->count() }} option(s) ·
                    {{ $filiere->filiereOptions->sum(fn($o)=>$o->niveaux->sum(fn($n)=>$n->matieres->where('archive',false)->count())) }} matière(s) ·
                    {{ $filiere->archive?'Archivée':'Active' }}
                </div>
            </div>
        </div>
        <div class="d-flex gap-2" onclick="event.stopPropagation()">
            <button class="btn btn-sm rounded-3" style="background:rgba(255,255,255,.15);color:#fff;font-size:11px;border:1px solid rgba(255,255,255,.2);"
                    data-bs-toggle="modal" data-bs-target="#modalOption{{ $filiere->id }}">
                <i class="bi bi-plus-lg me-1"></i> Option
            </button>
            <button class="btn btn-sm rounded-3" style="background:rgba(255,255,255,.15);color:#fff;font-size:11px;border:1px solid rgba(255,255,255,.2);"
                    data-bs-toggle="modal" data-bs-target="#editFiliere{{ $filiere->id }}">
                <i class="bi bi-pencil"></i>
            </button>
            <form method="POST" action="{{ route('filieres.archive',$filiere->id) }}" class="d-inline">@csrf
                <button type="submit" class="btn btn-sm rounded-3" style="background:rgba(255,255,255,.15);color:#fff;font-size:11px;border:1px solid rgba(255,255,255,.2);"
                        title="{{ $filiere->archive?'Réactiver':'Archiver' }}">
                    <i class="bi bi-{{ $filiere->archive?'eye':'archive' }}"></i>
                </button>
            </form>
            @if($filiere->canDelete())
            <form method="POST" action="{{ route('filieres.destroy',$filiere->id) }}" class="d-inline" onsubmit="return confirm('Supprimer cette filière ?')">@csrf @method('DELETE')
                <button type="submit" class="btn btn-sm rounded-3" style="background:rgba(220,50,50,.5);color:#fff;font-size:11px;border:none;" title="Supprimer">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Corps filière --}}
    <div class="fil-body">

    {{-- Options --}}
    @foreach($filiere->filiereOptions as $opt)
    <div class="border-top opt-row {{ $opt->archive?'opacity-50':'' }}">
        <div class="px-4 py-2 d-flex align-items-center justify-content-between" style="background:#f4ede4;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-folder2-open" style="color:var(--marron);"></i>
                <span style="font-weight:600;font-size:13px;color:var(--fonce);">{{ $opt->nom }}</span>
                <span class="badge rounded-2 px-2" style="font-size:10px;font-family:monospace;background:var(--beige);color:var(--marron);">{{ $opt->code }}</span>
                @if($opt->archive)<span class="badge rounded-pill px-2" style="font-size:10px;background:#f5f5f5;color:#9e9e9e;">Archivée</span>@endif
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm rounded-3" style="font-size:11px;background:var(--fonce);color:#fff;border:none;"
                        data-bs-toggle="modal" data-bs-target="#modalNiveau{{ $opt->id }}">
                    <i class="bi bi-plus-lg me-1"></i> Niveau
                </button>
                <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid rgba(0,0,0,.1);"
                        data-bs-toggle="modal" data-bs-target="#editOption{{ $opt->id }}">
                    <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="{{ route('filieres.options.archive',$opt->id) }}" class="d-inline">@csrf
                    <button type="submit" class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid rgba(0,0,0,.1);" title="{{ $opt->archive?'Réactiver':'Archiver' }}">
                        <i class="bi bi-{{ $opt->archive?'eye':'archive' }}"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Niveaux --}}
        @foreach($opt->niveaux as $niveau)
        <div class="px-4 py-2 border-top niv-row d-flex align-items-center justify-content-between {{ $niveau->archive?'opacity-50':'' }}" style="background:#faf7f4;">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill px-3" style="background:var(--fonce);color:var(--beige);font-size:11px;">{{ $niveau->code }}</span>
                <span style="font-weight:600;font-size:13px;color:var(--fonce);">{{ $niveau->libelle }}</span>
                <span style="font-size:11px;color:#aaa;">— {{ $niveau->matieres->where('archive',false)->count() }} matière(s)</span>
                @if($niveau->archive)<span class="badge rounded-pill px-2" style="font-size:10px;background:#f5f5f5;color:#9e9e9e;">Archivé</span>@endif
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid var(--marron);color:var(--marron);"
                        data-bs-toggle="modal" data-bs-target="#modalMat{{ $niveau->id }}">
                    <i class="bi bi-book me-1"></i> Matière
                </button>
                <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid rgba(0,0,0,.1);"
                        data-bs-toggle="modal" data-bs-target="#editNiveau{{ $niveau->id }}">
                    <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" action="{{ route('filieres.niveaux.archive',$niveau->id) }}" class="d-inline">@csrf
                    <button type="submit" class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid rgba(0,0,0,.1);">
                        <i class="bi bi-{{ $niveau->archive?'eye':'archive' }}"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Matières --}}
        @if($niveau->matieres->isNotEmpty())
        <table class="table table-hover mb-0 border-top">
            <thead style="background:var(--beige);">
                <tr style="font-size:11px;color:var(--fonce);">
                    <th class="px-5">Code</th><th>Matière</th><th>S</th><th>HP</th><th>TPE</th><th>MHT</th><th>État</th><th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($niveau->matieres->sortBy('semestre') as $m)
            <tr class="mat-row-fil {{ $m->archive?'opacity-50':'' }}"
                data-search="{{ strtolower($m->nom . ' ' . $m->code) }}"
                data-archive="{{ $m->archive ? '1' : '0' }}">
                <td class="px-5"><code style="font-size:11px;background:#f0ebe4;padding:2px 8px;border-radius:4px;">{{ $m->code }}</code></td>
                <td style="font-weight:600;font-size:13px;color:var(--fonce);">{{ $m->nom }}</td>
                <td><span class="badge rounded-pill px-2" style="font-size:10px;background:{{ $m->semestre==1?'#e3f2fd':'#f3e5f5' }};color:{{ $m->semestre==1?'#1565c0':'#6a1b9a' }}">S{{ $m->semestre }}</span></td>
                <td style="font-size:13px;"><strong>{{ $m->hp_initial }}</strong>h</td>
                <td style="font-size:13px;">{{ $m->tpe_initial }}h</td>
                <td style="font-size:13px;font-weight:700;">{{ $m->mht }}h</td>
                <td><span class="badge rounded-pill px-2" style="font-size:10px;background:{{ $m->archive?'#f5f5f5':'#e8f5e9' }};color:{{ $m->archive?'#9e9e9e':'#2e7d32' }}">{{ $m->archive?'Archivée':'Active' }}</span></td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid rgba(0,0,0,.1);"
                                data-bs-toggle="modal" data-bs-target="#editMat{{ $m->id }}"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="{{ route('filieres.matieres.archive',$m->id) }}" class="d-inline">@csrf
                            <button type="submit" class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid rgba(0,0,0,.1);" title="{{ $m->archive?'Réactiver':'Archiver' }}">
                                <i class="bi bi-{{ $m->archive?'eye':'archive' }}"></i>
                            </button>
                        </form>
                        @if($m->canDelete())
                        <form method="POST" action="{{ route('filieres.matieres.destroy',$m->id) }}" class="d-inline" onsubmit="return confirm('Supprimer cette matière ?')">@csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm rounded-3" style="font-size:11px;background:#ffebee;color:#c62828;border:none;"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            {{-- Modal edit matière --}}
            <div class="modal fade" id="editMat{{ $m->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content rounded-4">
                <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier — {{ $m->nom }}</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="{{ route('filieres.matieres.update',$m->id) }}">@csrf @method('PUT')
                    <div class="modal-body"><div class="row g-3">
                        <div class="col-8"><label class="form-label fw-semibold" style="font-size:13px;">Intitulé</label><input type="text" name="nom" value="{{ $m->nom }}" class="form-control rounded-3" required></div>
                        <div class="col-4"><label class="form-label fw-semibold" style="font-size:13px;">Code</label><input type="text" name="code" value="{{ $m->code }}" class="form-control rounded-3" required></div>
                        <div class="col-4"><label class="form-label fw-semibold" style="font-size:13px;">Semestre</label><select name="semestre" class="form-select rounded-3"><option value="1" {{ $m->semestre==1?'selected':'' }}>S1</option><option value="2" {{ $m->semestre==2?'selected':'' }}>S2</option></select></div>
                        <div class="col-4"><label class="form-label fw-semibold" style="font-size:13px;">HP (h)</label><input type="number" name="hp_initial" value="{{ $m->hp_initial }}" class="form-control rounded-3" min="1" required></div>
                        <div class="col-4"><label class="form-label fw-semibold" style="font-size:13px;">TPE (h)</label><input type="number" name="tpe_initial" value="{{ $m->tpe_initial }}" class="form-control rounded-3" min="0" required></div>
                    </div></div>
                    <div class="modal-footer border-0"><button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Sauvegarder</button></div>
                </form>
            </div></div></div>
            @endforeach
            </tbody>
        </table>
        @endif

        {{-- Modal ajout matière --}}
        <div class="modal fade" id="modalMat{{ $niveau->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content rounded-4">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Ajouter une matière — {{ $opt->nom }} / {{ $niveau->libelle }}</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('filieres.matieres.store',$niveau->id) }}">@csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;color:var(--marron);">Importer depuis une matière existante</label>
                        <select class="form-select rounded-3 shadow-sm" style="font-size:13px;border-color:var(--marron);" onchange="preRemplirMatiere(this, '{{ $niveau->id }}')">
                            <option value="">-- Choisir un modèle (optionnel) --</option>
                            @foreach($templatesMatiere as $tm)
                                <option value="{{ json_encode($tm) }}">{{ $tm->nom }} [{{ $tm->code }}]</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="p-3 rounded-3 mb-3" style="background:var(--beige);font-size:12px;color:var(--fonce);"><i class="bi bi-info-circle me-1"></i>Rattachée à : <strong>{{ $filiere->nom }} → {{ $opt->nom }} → {{ $niveau->libelle }}</strong></div>
                    <div class="row g-3">
                        <div class="col-8"><label class="form-label fw-semibold" style="font-size:13px;">Intitulé *</label><input type="text" name="nom" class="form-control rounded-3" required></div>
                        <div class="col-4"><label class="form-label fw-semibold" style="font-size:13px;">Code * (unique)</label><input type="text" name="code" class="form-control rounded-3" required></div>
                        <div class="col-4"><label class="form-label fw-semibold" style="font-size:13px;">Semestre</label><select name="semestre" class="form-select rounded-3"><option value="1">S1</option><option value="2">S2</option></select></div>
                        <div class="col-4"><label class="form-label fw-semibold" style="font-size:13px;">HP Initial (h)</label><input type="number" name="hp_initial" class="form-control rounded-3" placeholder="30" min="1" id="hp_{{ $niveau->id }}" oninput="calcMHT('{{ $niveau->id }}')" required></div>
                        <div class="col-4"><label class="form-label fw-semibold" style="font-size:13px;">TPE Initial (h)</label><input type="number" name="tpe_initial" class="form-control rounded-3" placeholder="10" min="0" id="tpe_{{ $niveau->id }}" oninput="calcMHT('{{ $niveau->id }}')" required></div>
                        <div class="col-12"><div class="p-3 rounded-3 d-flex justify-content-between" style="background:var(--beige);"><span style="font-size:13px;color:var(--fonce);"><i class="bi bi-calculator me-1"></i>MHT :</span><span id="mht_{{ $niveau->id }}" style="font-size:20px;font-weight:700;color:var(--fonce);">0h</span></div></div>
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Ajouter</button></div>
            </form>
        </div></div></div>

        {{-- Modal edit niveau --}}
        <div class="modal fade" id="editNiveau{{ $niveau->id }}" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content rounded-4">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier le niveau</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('filieres.niveaux.update',$niveau->id) }}">@csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Libellé</label><input type="text" name="libelle" value="{{ $niveau->libelle }}" class="form-control rounded-3" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Code</label><input type="text" name="code" value="{{ $niveau->code }}" class="form-control rounded-3" required></div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Sauvegarder</button></div>
            </form>
        </div></div></div>

        @endforeach

        {{-- Modal ajout niveau (Placé HORS de la boucle niveaux) --}}
        <div class="modal fade" id="modalNiveau{{ $opt->id }}" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content rounded-4">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Ajouter un niveau — {{ $opt->nom }}</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('filieres.niveaux.store',$opt->id) }}">@csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Libellé *</label><input type="text" name="libelle" class="form-control rounded-3" placeholder="Licence 1" required><small class="text-muted">Ex: Licence 1, Master 2, BTS 1…</small></div>
                    <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Code *</label><input type="text" name="code" class="form-control rounded-3" placeholder="L1" maxlength="10" required></div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Ajouter</button></div>
            </form>
        </div></div></div>

        {{-- Modal edit option (Placé HORS de la boucle niveaux) --}}
        <div class="modal fade" id="editOption{{ $opt->id }}" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content rounded-4">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier l'option</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('filieres.options.update',$opt->id) }}">@csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Nom</label><input type="text" name="nom" value="{{ $opt->nom }}" class="form-control rounded-3" required></div>
                    <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Code</label><input type="text" name="code" value="{{ $opt->code }}" class="form-control rounded-3" required></div>
                </div>
                <div class="modal-footer border-0"><button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Sauvegarder</button></div>
            </form>
        </div></div></div>
    </div>
    @endforeach

    </div>{{-- /fil-body --}}

    {{-- Modal edit filière --}}
    <div class="modal fade" id="editFiliere{{ $filiere->id }}" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content rounded-4">
        <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier la filière</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="{{ route('filieres.update',$filiere->id) }}">@csrf @method('PUT')
            <div class="modal-body">
                <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Nom</label><input type="text" name="nom" value="{{ $filiere->nom }}" class="form-control rounded-3" required></div>
                <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Code</label><input type="text" name="code" value="{{ $filiere->code }}" class="form-control rounded-3" required></div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Sauvegarder</button></div>
        </form>
    </div></div></div>

    {{-- Modal ajout option --}}
    <div class="modal fade" id="modalOption{{ $filiere->id }}" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content rounded-4">
        <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Ajouter une option — {{ $filiere->nom }}</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="{{ route('filieres.options.store',$filiere->id) }}">@csrf
            <div class="modal-body">
                <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Nom *</label><input type="text" name="nom" class="form-control rounded-3" placeholder="Système Informatique et Logiciel" required></div>
                <div class="mb-3"><label class="form-label fw-semibold" style="font-size:13px;">Code *</label><input type="text" name="code" class="form-control rounded-3" placeholder="SIL" required></div>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Ajouter</button></div>
        </form>
    </div></div></div>
</div>
@empty
<div class="bg-white rounded-4 border p-5 text-center" style="color:#aaa;">
    <i class="bi bi-diagram-3" style="font-size:48px;margin-bottom:16px;display:block;color:var(--marron);"></i>
    <p>Aucune filière. Commencez par créer une filière.</p>
    <button class="btn text-white rounded-3 px-4 mt-2" style="background:var(--fonce);" data-bs-toggle="modal" data-bs-target="#modalFiliere">Créer la première filière</button>
</div>
@endforelse

{{-- Modal nouvelle filière --}}
<div class="modal fade" id="modalFiliere" tabindex="-1"><div class="modal-dialog"><div class="modal-content rounded-4">
    <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Nouvelle filière</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="{{ route('filieres.store') }}">@csrf
        <div class="modal-body"><div class="row g-3">
            <div class="col-8"><label class="form-label fw-semibold" style="font-size:13px;">Nom complet *</label><input type="text" name="nom" class="form-control rounded-3" placeholder="Génie Électrique" required></div>
            <div class="col-4"><label class="form-label fw-semibold" style="font-size:13px;">Code *</label><input type="text" name="code" class="form-control rounded-3" placeholder="GE" required></div>
        </div></div>
        <div class="modal-footer border-0"><button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Créer</button></div>
    </form>
</div></div></div>

@push('scripts')
<script>
// ── Référentiel : recherche et collapse ──────────────────────────────────────
function refToggle(id) {
    const card = document.getElementById('fil_' + id);
    if (card) card.classList.toggle('collapsed');
}

function refToggleAll(expand) {
    document.querySelectorAll('.fil-card').forEach(c => c.classList.toggle('collapsed', !expand));
}

function refSearch() {
    const q       = document.getElementById('ref_search')?.value.toLowerCase().trim() || '';
    const showArch = document.getElementById('ref_show_arch')?.checked || false;
    let visible = 0;

    document.querySelectorAll('.mat-row-fil').forEach(row => {
        const matchQ = !q || row.dataset.search.includes(q);
        const matchA = showArch || row.dataset.archive === '0';
        row.classList.toggle('hidden-fil', !(matchQ && matchA));
        if (matchQ && matchA) visible++;
    });

    // Cacher les blocs entiers sans résultats
    document.querySelectorAll('.fil-card').forEach(card => {
        const hasVis = card.querySelectorAll('.mat-row-fil:not(.hidden-fil)').length > 0;
        card.style.display = (!q && showArch) || hasVis ? '' : (q ? 'none' : '');
        if (q && hasVis) card.classList.remove('collapsed'); // auto-expand si recherche
    });

    const c = document.getElementById('ref_count');
    if (c) c.textContent = q ? `${visible} matière(s) trouvée(s)` : '';
}

function preRemplirMatiere(select, niveauId) {
    if (!select.value) return;
    const data = JSON.parse(select.value);
    const modal = document.querySelector(`#modalMat${niveauId}`);
    modal.querySelector('[name="nom"]').value = data.nom;
    modal.querySelector('[name="code"]').value = data.code;
    modal.querySelector('[name="hp_initial"]').value = data.hp_initial;
    modal.querySelector('[name="tpe_initial"]').value = data.tpe_initial;
    calcMHT(niveauId); // Recalculer le total MHT affiché
}

function calcMHT(id) {
    const hp  = parseInt(document.getElementById('hp_'+id)?.value)  || 0;
    const tpe = parseInt(document.getElementById('tpe_'+id)?.value) || 0;
    const el  = document.getElementById('mht_'+id);
    if (el) el.textContent = (hp + tpe) + 'h';
}
</script>
@endpush
@endsection
