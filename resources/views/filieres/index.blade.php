@extends('layouts.app')
@section('titre', 'Référentiel Pédagogique')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="mb-0" style="font-size:13px;color:var(--marron);">
        Gérez ici les filières, leurs options, les niveaux et les matières. Ce référentiel est global — partagé entre tous les centres.
    </p>
    <button class="btn text-white rounded-3 px-4" style="background:var(--fonce);"
            data-bs-toggle="modal" data-bs-target="#modalFiliere">
        <i class="bi bi-plus-lg me-1"></i> Nouvelle filière
    </button>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card"><span class="stat-icon">🎓</span>
            <div><div class="stat-value">{{ $filieres->count() }}</div><div class="stat-label">Filières</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#e3f2fd;"><span class="stat-icon">📂</span>
            <div><div class="stat-value">{{ $filieres->sum(fn($f) => $f->filiereOptions->count()) }}</div><div class="stat-label">Options</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#e8f5e9;"><span class="stat-icon">📊</span>
            <div><div class="stat-value">{{ $filieres->sum(fn($f) => $f->filiereOptions->sum(fn($o) => $o->niveaux->count())) }}</div><div class="stat-label">Niveaux</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#f3e5f5;"><span class="stat-icon">📚</span>
            <div><div class="stat-value">{{ $filieres->sum(fn($f) => $f->filiereOptions->sum(fn($o) => $o->niveaux->sum(fn($n) => $n->matieres->count()))) }}</div><div class="stat-label">Matières</div></div>
        </div>
    </div>
</div>

@forelse($filieres as $filiere)
<div class="bg-white rounded-4 border mb-4 overflow-hidden">

    {{-- En-tête Filière --}}
    <div class="px-4 py-3 d-flex align-items-center justify-content-between" style="background:var(--fonce);">
        <div class="d-flex align-items-center gap-3">
            <span class="badge rounded-2 px-3 py-2" style="background:var(--marron);font-family:monospace;font-size:13px;">
                {{ $filiere->code }}
            </span>
            <div>
                <div style="font-weight:700;font-size:16px;color:#fff;">{{ $filiere->nom }}</div>
                <div style="font-size:11px;color:rgba(255,255,255,.5);">
                    {{ $filiere->filiereOptions->count() }} option(s)
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-sm rounded-3"
                    style="background:rgba(255,255,255,.15);color:#fff;font-size:11px;border:1px solid rgba(255,255,255,.2);"
                    data-bs-toggle="modal" data-bs-target="#modalOption{{ $filiere->id }}">
                <i class="bi bi-plus-lg me-1"></i> Ajouter une option
            </button>
            <button class="btn btn-sm rounded-3"
                    style="background:rgba(255,255,255,.08);color:#fff;font-size:11px;border:1px solid rgba(255,255,255,.2);"
                    data-bs-toggle="modal" data-bs-target="#editFiliere{{ $filiere->id }}">
                <i class="bi bi-pencil"></i>
            </button>
        </div>
    </div>

    {{-- Options de la filière --}}
    @if($filiere->filiereOptions->isEmpty())
    <div class="p-4 text-center" style="color:#aaa;font-size:13px;">
        Aucune option. Cliquez sur "Ajouter une option" (ex: SIL, Réseau, Gestion…).
    </div>
    @else
    @foreach($filiere->filiereOptions as $option)
    <div class="border-top">
        {{-- En-tête Option --}}
        <div class="px-4 py-2 d-flex align-items-center justify-content-between"
             style="background:#f4ede4;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-folder2-open" style="color:var(--marron);"></i>
                <span style="font-weight:600;font-size:14px;color:var(--fonce);">
                    {{ $option->nom }}
                </span>
                <span class="badge rounded-2 px-2"
                      style="font-size:10px;font-family:monospace;background:var(--beige);color:var(--marron);border:1px solid rgba(141,110,99,.2);">
                    {{ $option->code }}
                </span>
                <span style="font-size:11px;color:#aaa;">
                    — {{ $option->niveaux->count() }} niveau(x)
                </span>
            </div>
            <button class="btn btn-sm rounded-3"
                    style="font-size:11px;background:var(--fonce);color:#fff;border:none;"
                    data-bs-toggle="modal" data-bs-target="#modalNiveau{{ $option->id }}">
                <i class="bi bi-plus-lg me-1"></i> Ajouter un niveau
            </button>
        </div>

        {{-- Niveaux --}}
        @if($option->niveaux->isEmpty())
        <div class="px-4 py-3" style="font-size:13px;color:#aaa;">
            Aucun niveau défini. Ajoutez Licence 1, Master 2, etc.
        </div>
        @else
        @foreach($option->niveaux as $niveau)
        <div class="px-4 py-2 border-top d-flex align-items-center justify-content-between"
             style="background:#faf7f4;">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill px-3"
                      style="background:var(--fonce);color:var(--beige);font-size:11px;">
                    {{ $niveau->code }}
                </span>
                <span style="font-weight:600;font-size:13px;color:var(--fonce);">
                    {{ $niveau->libelle }}
                </span>
                <span style="font-size:11px;color:#aaa;">
                    — {{ $niveau->matieres->count() }} matière(s)
                </span>
            </div>
            <button class="btn btn-sm rounded-3"
                    style="font-size:11px;border:1px solid var(--marron);color:var(--marron);"
                    data-bs-toggle="modal" data-bs-target="#modalMatiere{{ $niveau->id }}">
                <i class="bi bi-book me-1"></i> Ajouter une matière
            </button>
        </div>

        {{-- Matières du niveau --}}
        @if($niveau->matieres->isNotEmpty())
        <table class="table table-hover mb-0 border-top">
            <thead style="background:var(--beige);">
                <tr style="font-size:11px;color:var(--fonce);">
                    <th class="px-5 py-2">Code</th>
                    <th>Matière</th>
                    <th>Semestre</th>
                    <th>HP</th>
                    <th>TPE</th>
                    <th>MHT</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($niveau->matieres->groupBy('semestre') as $sem => $mats)
                <tr><td colspan="7" class="px-5 py-1"
                        style="background:rgba(141,110,99,.05);font-size:11px;color:var(--marron);font-weight:600;">
                    Semestre {{ $sem }}
                </td></tr>
                @foreach($mats as $m)
                <tr>
                    <td class="px-5 py-2">
                        <code style="font-size:11px;background:#f0ebe4;padding:2px 8px;border-radius:4px;">{{ $m->code }}</code>
                    </td>
                    <td style="font-size:13px;font-weight:600;color:var(--fonce);">{{ $m->nom }}</td>
                    <td>
                        <span class="badge rounded-pill px-2" style="font-size:10px;background:{{ $m->semestre==1?'#e3f2fd':'#f3e5f5' }};color:{{ $m->semestre==1?'#1565c0':'#6a1b9a' }};">
                            S{{ $m->semestre }}
                        </span>
                    </td>
                    <td style="font-size:13px;"><strong>{{ $m->hp_initial }}</strong>h</td>
                    <td style="font-size:13px;">{{ $m->tpe_initial }}h</td>
                    <td style="font-size:13px;font-weight:700;color:var(--fonce);">{{ $m->hp_initial + $m->tpe_initial }}h</td>
                    <td>
                        <button class="btn btn-sm rounded-3"
                                style="font-size:11px;border:1px solid rgba(0,0,0,.1);"
                                data-bs-toggle="modal" data-bs-target="#editMatiere{{ $m->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>

                {{-- Modal edit matière --}}
                <div class="modal fade" id="editMatiere{{ $m->id }}" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content rounded-4">
                        <div class="modal-header border-0">
                            <h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier — {{ $m->nom }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="{{ route('filieres.matieres.update', $m->id) }}">
                            @csrf @method('PUT')
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-8">
                                        <label class="form-label fw-semibold" style="font-size:13px;">Intitulé *</label>
                                        <input type="text" name="nom" value="{{ $m->nom }}" class="form-control rounded-3" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label fw-semibold" style="font-size:13px;">Code *</label>
                                        <input type="text" name="code" value="{{ $m->code }}" class="form-control rounded-3" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold" style="font-size:13px;">Semestre</label>
                                        <select name="semestre" class="form-select rounded-3">
                                            <option value="1" {{ $m->semestre==1?'selected':'' }}>Semestre 1</option>
                                            <option value="2" {{ $m->semestre==2?'selected':'' }}>Semestre 2</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label fw-semibold" style="font-size:13px;">HP (h) *</label>
                                        <input type="number" name="hp_initial" value="{{ $m->hp_initial }}" class="form-control rounded-3" min="1" required>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label fw-semibold" style="font-size:13px;">TPE (h)</label>
                                        <input type="number" name="tpe_initial" value="{{ $m->tpe_initial }}" class="form-control rounded-3" min="0" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Sauvegarder</button>
                            </div>
                        </form>
                    </div></div>
                </div>
                @endforeach
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Modal ajout matière --}}
        <div class="modal fade" id="modalMatiere{{ $niveau->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg"><div class="modal-content rounded-4">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-bold" style="color:var(--fonce)">
                        Ajouter une matière — {{ $option->nom }} / {{ $niveau->libelle }}
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('filieres.niveaux.matieres.store', $niveau->id) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="p-3 rounded-3 mb-3" style="background:var(--beige);font-size:12px;color:var(--fonce);">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette matière sera rattachée à :
                            <strong>{{ $filiere->nom }} → {{ $option->nom }} → {{ $niveau->libelle }}</strong>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold" style="font-size:13px;">Intitulé *</label>
                                <input type="text" name="nom" class="form-control rounded-3" placeholder="Bases de Données Relationnelles" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px;">Code * (unique global)</label>
                                <input type="text" name="code" class="form-control rounded-3" placeholder="BDD" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px;">Semestre *</label>
                                <select name="semestre" class="form-select rounded-3" required>
                                    <option value="1">Semestre 1</option>
                                    <option value="2">Semestre 2</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px;">HP Initial (h) *</label>
                                <input type="number" name="hp_initial" class="form-control rounded-3" placeholder="30" min="1" max="500"
                                       oninput="calcMHT_{{ $niveau->id }}()" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size:13px;">TPE Initial (h)</label>
                                <input type="number" name="tpe_initial" class="form-control rounded-3" placeholder="10" min="0" max="500"
                                       oninput="calcMHT_{{ $niveau->id }}()" required>
                            </div>
                            <div class="col-12">
                                <div class="p-3 rounded-3 d-flex justify-content-between align-items-center" style="background:var(--beige);">
                                    <span style="font-size:13px;color:var(--fonce);"><i class="bi bi-calculator me-1"></i>MHT = HP + TPE :</span>
                                    <span id="mht_{{ $niveau->id }}" style="font-size:20px;font-weight:700;color:var(--fonce);">0h</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Ajouter la matière</button>
                    </div>
                </form>
            </div></div>
        </div>

        @endforeach
        @endif

        {{-- Modal ajout niveau --}}
        <div class="modal fade" id="modalNiveau{{ $option->id }}" tabindex="-1">
            <div class="modal-dialog modal-sm"><div class="modal-content rounded-4">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-bold" style="color:var(--fonce)">Ajouter un niveau — {{ $option->nom }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('filieres.options.niveaux.store', $option->id) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px;">Libellé complet *</label>
                            <input type="text" name="libelle" class="form-control rounded-3"
                                   placeholder="Licence 1" required>
                            <small class="text-muted">Ex: Licence 1, Master 2, DUT 1, BTS 2…</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:13px;">Code court *</label>
                            <input type="text" name="code" class="form-control rounded-3"
                                   placeholder="L1" maxlength="10" required>
                            <small class="text-muted">Ex: L1, M2, DUT1…</small>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Ajouter</button>
                    </div>
                </form>
            </div></div>
        </div>

    </div>
    @endforeach
    @endif

    {{-- Modal edit filière --}}
    <div class="modal fade" id="editFiliere{{ $filiere->id }}" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier — {{ $filiere->nom }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('filieres.update', $filiere->id) }}">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold" style="font-size:13px;">Nom *</label>
                            <input type="text" name="nom" value="{{ $filiere->nom }}" class="form-control rounded-3" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">Code *</label>
                            <input type="text" name="code" value="{{ $filiere->code }}" class="form-control rounded-3" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Sauvegarder</button>
                </div>
            </form>
        </div></div>
    </div>

    {{-- Modal ajout option --}}
    <div class="modal fade" id="modalOption{{ $filiere->id }}" tabindex="-1">
        <div class="modal-dialog modal-sm"><div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold" style="color:var(--fonce)">Ajouter une option — {{ $filiere->nom }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('filieres.options.store', $filiere->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Nom de l'option *</label>
                        <input type="text" name="nom" class="form-control rounded-3"
                               placeholder="Système Informatique et Logiciel" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Code *</label>
                        <input type="text" name="code" class="form-control rounded-3"
                               placeholder="SIL" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Ajouter</button>
                </div>
            </form>
        </div></div>
    </div>

</div>
@empty
<div class="bg-white rounded-4 border p-5 text-center" style="color:#aaa;">
    <div style="font-size:48px;margin-bottom:16px;">🎓</div>
    <p>Aucune filière. Commencez par en créer une.</p>
    <button class="btn text-white rounded-3 px-4 mt-2" style="background:var(--fonce);"
            data-bs-toggle="modal" data-bs-target="#modalFiliere">Créer la première filière</button>
</div>
@endforelse

{{-- Modal nouvelle filière --}}
<div class="modal fade" id="modalFiliere" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content rounded-4">
        <div class="modal-header border-0">
            <h6 class="modal-title fw-bold" style="color:var(--fonce)">Nouvelle filière</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="{{ route('filieres.store') }}">
            @csrf
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-8">
                        <label class="form-label fw-semibold" style="font-size:13px;">Nom complet *</label>
                        <input type="text" name="nom" class="form-control rounded-3" placeholder="Génie Électrique" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold" style="font-size:13px;">Code * (unique)</label>
                        <input type="text" name="code" class="form-control rounded-3" placeholder="GE" required>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background:var(--beige);font-size:12px;color:var(--fonce);">
                            <i class="bi bi-info-circle me-1"></i>
                            Après avoir créé la filière, ajoutez-y des <strong>options</strong> (SIL, Réseau…),
                            puis définissez les <strong>niveaux</strong> (L1, M2…) et enfin les <strong>matières</strong>.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Créer</button>
            </div>
        </form>
    </div></div>
</div>

@push('scripts')
<script>
@foreach($filieres as $filiere)
@foreach($filiere->filiereOptions as $option)
@foreach($option->niveaux as $niveau)
function calcMHT_{{ $niveau->id }}() {
    const modal = document.getElementById('modalMatiere{{ $niveau->id }}');
    const hp  = parseInt(modal.querySelector('[name="hp_initial"]').value)  || 0;
    const tpe = parseInt(modal.querySelector('[name="tpe_initial"]').value) || 0;
    document.getElementById('mht_{{ $niveau->id }}').textContent = (hp + tpe) + 'h';
}
@endforeach
@endforeach
@endforeach
</script>
@endpush
@endsection
