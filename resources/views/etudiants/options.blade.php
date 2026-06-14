@extends('layouts.app')
@section('titre','Groupes — '.$centre->nom)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="fw-semibold" style="font-size:13px;color:var(--fonce)">Année :</label>
        <select name="annee_id" class="form-select form-select-sm rounded-3" style="width:auto;" onchange="this.form.submit()">
            @foreach($annees as $a)
            <option value="{{ $a->id }}" {{ $annee?->id==$a->id?'selected':'' }}>{{ $a->libelle }}{{ $a->active?' ★':'' }}</option>
            @endforeach
        </select>
    </form>
    @if(auth()->user()->peutGererCentre())
    <button class="btn text-white rounded-3 px-4" style="background:var(--fonce);font-size:13px;"
            data-bs-toggle="modal" data-bs-target="#modalGroupe">
        <i class="bi bi-plus-lg me-1"></i> Créer un groupe
    </button>
    @endif
</div>

<div class="row g-3">
@forelse($options as $opt)
<div class="col-md-4">
    <div class="bg-white rounded-4 border p-4 h-100">
        <div class="d-flex align-items-start justify-content-between mb-2">
            <div>
                <span class="badge rounded-2 px-2 mb-2" style="background:var(--fonce);color:var(--beige);font-size:10px;font-family:monospace;">
                    {{ $opt->filiereOption?->filiere?->code }} — {{ $opt->filiereOption?->code }}
                </span>
                <div style="font-weight:700;font-size:14px;color:var(--fonce);">{{ $opt->nom }}</div>
                <div style="font-size:12px;color:var(--marron);">{{ $opt->niveau?->libelle }}</div>
            </div>
            <div class="text-end">
                <div style="font-size:22px;font-weight:700;color:var(--fonce);">{{ $opt->nombre_actifs }}</div>
                <div style="font-size:11px;color:#aaa;">étudiants</div>
            </div>
        </div>
        <div style="font-size:12px;color:#aaa;margin-bottom:12px;">{{ $opt->anneeScolaire?->libelle }}</div>
        <div class="d-flex gap-2">
            <a href="{{ route('etudiants.index', ['centreId'=>$centreId,'annee_id'=>$opt->annee_scolaire_id]) }}"
               class="btn btn-sm rounded-3 flex-1 text-white" style="background:var(--fonce);font-size:12px;">
                Voir les étudiants
            </a>
            @if(auth()->user()->peutGererCentre())
            <button class="btn btn-sm rounded-3" style="font-size:12px;border:1px solid var(--marron);color:var(--marron);"
                    data-bs-toggle="modal" data-bs-target="#reconduire{{ $opt->id }}">
                <i class="bi bi-arrow-repeat"></i> Reconduire
            </button>
            @endif
        </div>
    </div>
</div>

{{-- Modal reconduire --}}
<div class="modal fade" id="reconduire{{ $opt->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content rounded-4">
    <div class="modal-header border-0">
        <h6 class="modal-title fw-bold" style="color:var(--fonce)">Reconduire — {{ $opt->nom }}</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST" action="{{ route('options.reconduire',$opt->id) }}">@csrf
        <div class="modal-body">
            <div class="p-3 rounded-3 mb-3" style="background:var(--beige);font-size:12px;color:var(--fonce);">
                Tous les <strong>{{ $opt->nombre_actifs }} étudiants actifs</strong> de ce groupe seront réinscrits dans le groupe cible pour l'année sélectionnée.
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Groupe cible *</label>
                <select name="option_cible_id" class="form-select rounded-3" required>
                    <option value="">— Sélectionner —</option>
                    @foreach($options->where('id','!=',$opt->id) as $o2)
                    <option value="{{ $o2->id }}">{{ $o2->nom }} ({{ $o2->anneeScolaire?->libelle }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Année scolaire cible *</label>
                <select name="annee_scolaire_id" class="form-select rounded-3" required>
                    @foreach($anneesScolaires as $a)
                    <option value="{{ $a->id }}" {{ $a->active?'selected':'' }}>{{ $a->libelle }}{{ $a->active?' (active)':'' }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Reconduire la promotion</button>
        </div>
    </form>
</div></div></div>
@empty
<div class="col-12"><div class="bg-white rounded-4 border p-5 text-center" style="color:#aaa;">
    <div style="font-size:40px;margin-bottom:12px;">📋</div>
    <p>Aucun groupe pour cette année. Créez un groupe pour commencer à inscrire des étudiants.</p>
</div></div>
@endforelse
</div>

{{-- Modal création groupe --}}
<div class="modal fade" id="modalGroupe" tabindex="-1"><div class="modal-dialog"><div class="modal-content rounded-4">
    <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Créer un groupe</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="{{ route('options.store',$centreId) }}">@csrf
        <div class="modal-body"><div class="row g-3">
            <div class="col-12"><label class="form-label fw-semibold" style="font-size:13px;">Nom du groupe *</label>
                <input type="text" name="nom" class="form-control rounded-3" placeholder="GE-SIL L1 Gbégamey 2025-2026" required></div>
            <div class="col-12"><label class="form-label fw-semibold" style="font-size:13px;">Option pédagogique *</label>
                <select name="filiere_option_id" class="form-select rounded-3" required>
                    <option value="">— Sélectionner —</option>
                    @foreach($filiereOptions as $fo)
                    <option value="{{ $fo->id }}">{{ $fo->filiere?->nom }} — {{ $fo->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12"><label class="form-label fw-semibold" style="font-size:13px;">Niveau *</label>
                <select name="niveau_id" class="form-select rounded-3" required>
                    <option value="">— Sélectionner —</option>
                    @foreach($filiereOptions as $fo)
                    @foreach($fo->niveaux as $n)
                    <option value="{{ $n->id }}">{{ $fo->nom }} — {{ $n->libelle }}</option>
                    @endforeach
                    @endforeach
                </select>
            </div>
            <div class="col-12"><label class="form-label fw-semibold" style="font-size:13px;">Année scolaire *</label>
                <select name="annee_scolaire_id" class="form-select rounded-3" required>
                    @foreach($anneesScolaires as $a)
                    <option value="{{ $a->id }}" {{ $a->active?'selected':'' }}>{{ $a->libelle }}{{ $a->active?' (active)':'' }}</option>
                    @endforeach
                </select>
            </div>
        </div></div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Créer</button>
        </div>
    </form>
</div></div></div>
@endsection
