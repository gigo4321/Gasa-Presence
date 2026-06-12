@extends('layouts.app')
@section('titre', 'Planning des Séances — ' . $centre->nom)

@section('content')
{{-- Barre navigation date --}}
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    @php
        $datePrev = \Carbon\Carbon::parse($date)->subDay()->toDateString();
        $dateNext = \Carbon\Carbon::parse($date)->addDay()->toDateString();
    @endphp
    <a href="{{ route('seances.index', ['centreId' => $centreId, 'date' => $datePrev]) }}"
       class="btn btn-sm text-white rounded-3" style="background:var(--marron);">← Hier</a>

    <form method="GET" class="d-flex gap-2">
        <input type="hidden" name="centreId" value="{{ $centreId }}">
        <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm rounded-3">
        <button type="submit" class="btn btn-sm text-white rounded-3" style="background:var(--marron);">OK</button>
    </form>

    <a href="{{ route('seances.index', ['centreId' => $centreId, 'date' => today()->toDateString()]) }}"
       class="btn btn-sm rounded-3" style="border:1px solid var(--marron);color:var(--marron);">Aujourd'hui</a>

    <a href="{{ route('seances.index', ['centreId' => $centreId, 'date' => $dateNext]) }}"
       class="btn btn-sm text-white rounded-3" style="background:var(--marron);">Demain →</a>

    <div class="ms-auto">
        <button class="btn text-white rounded-3 px-4" style="background:var(--fonce);"
                data-bs-toggle="modal" data-bs-target="#modalSeance">
            <i class="bi bi-plus-lg me-1"></i> Nouvelle séance
        </button>
    </div>
</div>

{{-- Erreurs --}}
@if($errors->any())
<div class="alert alert-danger rounded-3 mb-3">
    @foreach($errors->all() as $e) <div><i class="bi bi-exclamation-circle me-1"></i>{{ $e }}</div> @endforeach
</div>
@endif

{{-- Liste des séances --}}
@forelse($seances as $s)
@php
    $bgMap = ['planifiee'=>'#f3e5f5','en_cours'=>'#e8f5e9','terminee'=>'#f5f5f5','annulee'=>'#ffebee'];
    $bg = $bgMap[$s->statut] ?? '#fff';
    $enPause = $s->heure_fin_pause && now()->lt($s->heure_fin_pause);
@endphp
<div class="rounded-4 border p-4 mb-3" style="background:{{ $bg }};border-color:rgba(0,0,0,.08)!important;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fw-bold" style="font-family:monospace;font-size:14px;">
                    {{ \Carbon\Carbon::parse($s->debut)->format('H:i') }} – {{ \Carbon\Carbon::parse($s->fin)->format('H:i') }}
                </span>
                <span class="badge rounded-pill px-3" style="font-size:11px;background:{{ ['HP'=>'#e3f2fd','TPE'=>'#f3e5f5'][$s->type]??'#eee' }};color:{{ ['HP'=>'#1565c0','TPE'=>'#6a1b9a'][$s->type]??'#333' }}">
                    {{ $s->type }}
                </span>
                <span class="badge rounded-pill px-3" style="font-size:11px;background:{{ $bg }};color:var(--fonce);border:1px solid rgba(0,0,0,.1)">
                    {{ $s->statut }}
                </span>
                @if($s->is_inter_centre)
                <span class="badge rounded-pill px-2" style="font-size:10px;background:#fff3e0;color:#e65100;">🌐 Inter-centres</span>
                @endif
                @if($enPause)
                <span class="badge rounded-pill px-2" style="font-size:10px;background:#fff8e1;color:#f57f17;">⏸ Pause jusqu'à {{ \Carbon\Carbon::parse($s->heure_fin_pause)->format('H:i') }}</span>
                @endif
            </div>
            <div class="fw-semibold mb-1" style="font-size:15px;color:var(--fonce)">{{ $s->matiere?->nom }}</div>
            <div style="font-size:13px;color:var(--marron)">
                <i class="bi bi-person me-1"></i>{{ $s->professeur?->name }}
                &nbsp;·&nbsp;
                <i class="bi bi-door-open me-1"></i>{{ $s->salle?->nom }} ({{ $s->salle?->capacite }} places)
            </div>
            @if($s->options->count())
            <div style="font-size:12px;color:#888;margin-top:4px;">
                <i class="bi bi-people me-1"></i>{{ $s->options->pluck('nom')->join(', ') }}
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="d-flex gap-2 flex-wrap">
            @if($s->statut === 'planifiee')
            <form method="POST" action="{{ route('seances.demarrer', $s->id) }}">
                @csrf
                <button type="submit" class="btn btn-sm text-white rounded-3" style="background:#2e7d32;font-size:12px;">
                    <i class="bi bi-play-fill"></i> Démarrer
                </button>
            </form>
            @endif

            @if($s->statut === 'en_cours')
            <button class="btn btn-sm rounded-3" style="background:#fff8e1;color:#f57f17;border:1px solid #f57f17;font-size:12px;"
                    data-bs-toggle="modal" data-bs-target="#pauseModal{{ $s->id }}">
                <i class="bi bi-pause-fill"></i> Pause
            </button>
            <form method="POST" action="{{ route('seances.terminer', $s->id) }}">
                @csrf
                <button type="submit" class="btn btn-sm text-white rounded-3" style="background:#c62828;font-size:12px;"
                        onclick="return confirm('Clôturer cette séance ?')">
                    <i class="bi bi-stop-fill"></i> Terminer
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

{{-- Modal pause --}}
<div class="modal fade" id="pauseModal{{ $s->id }}" tabindex="-1">
    <div class="modal-dialog modal-sm"><div class="modal-content rounded-4">
        <div class="modal-header border-0">
            <h6 class="modal-title fw-bold" style="color:var(--fonce)">Déclarer une pause</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="{{ route('seances.pause', $s->id) }}">
            @csrf
            <div class="modal-body">
                <label class="form-label fw-semibold" style="font-size:13px;">Durée (minutes)</label>
                <input type="number" name="duree_minutes" class="form-control rounded-3" value="15" min="1" max="60" required>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn text-white rounded-3" style="background:var(--fonce);">Valider</button>
            </div>
        </form>
    </div></div>
</div>
@empty
<div class="bg-white rounded-4 border p-5 text-center" style="color:#aaa;">
    <div style="font-size:40px;margin-bottom:12px;">📅</div>
    <div style="font-size:14px;">Aucune séance planifiée pour ce jour.</div>
    <button class="btn mt-3 text-white rounded-3 px-4" style="background:var(--marron);"
            data-bs-toggle="modal" data-bs-target="#modalSeance">
        Créer la première séance
    </button>
</div>
@endforelse

{{-- Modal création séance --}}
<div class="modal fade" id="modalSeance" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content rounded-4">
        <div class="modal-header border-0">
            <h6 class="modal-title fw-bold" style="color:var(--fonce)">Planifier une séance</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="{{ route('seances.store') }}">
            @csrf
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Matière *</label>
                        <select name="matiere_id" class="form-select rounded-3" required>
                            <option value="">— Sélectionner —</option>
                            @foreach($matieres as $m)
                            <option value="{{ $m->id }}">{{ $m->nom }} ({{ $m->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Type *</label>
                        <select name="type" class="form-select rounded-3" required>
                            <option value="HP">HP — Cours Professeur</option>
                            <option value="TPE">TPE — Travaux Personnels</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Salle *</label>
                        <select name="salle_id" class="form-select rounded-3" required>
                            <option value="">— Sélectionner —</option>
                            @foreach($salles as $s)
                            <option value="{{ $s->id }}">{{ $s->nom }} ({{ $s->capacite }} places — {{ $s->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Professeur *</label>
                        <select name="professeur_id" class="form-select rounded-3" required>
                            <option value="">— Sélectionner —</option>
                            @foreach($profs as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Début *</label>
                        <input type="datetime-local" name="debut" class="form-control rounded-3"
                               value="{{ $date }}T08:00" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Fin *</label>
                        <input type="datetime-local" name="fin" class="form-control rounded-3"
                               value="{{ $date }}T10:00" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:13px;">Options concernées * (cocher plusieurs = séance commune)</label>
                        <div class="border rounded-3 p-3" style="max-height:160px;overflow-y:auto;">
                            @foreach($options as $o)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="option_ids[]"
                                       value="{{ $o->id }}" id="opt{{ $o->id }}">
                                <label class="form-check-label" for="opt{{ $o->id }}" style="font-size:13px;">
                                    {{ $o->nom }} — Niv.{{ $o->niveau }} ({{ $o->filiere?->code }})
                                </label>
                            </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Cocher plusieurs options crée une séance commune (capacité vérifiée automatiquement).</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Créer la séance</button>
            </div>
        </form>
    </div></div>
</div>
@endsection
