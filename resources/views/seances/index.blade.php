@extends('layouts.app')
@section('titre', 'Planning des Séances — ' . $centre->nom)

@section('content')

{{-- ── FILTRES ────────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-4 border p-3 mb-4">
    <form method="GET" action="{{ route('seances.index', $centreId) }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Salle</label>
            <select name="salle_id" class="form-select form-select-sm rounded-3">
                <option value="">Toutes les salles</option>
                @foreach($salles as $s)
                <option value="{{ $s->id }}" {{ $salleId == $s->id ? 'selected' : '' }}>{{ $s->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Matière</label>
            <select name="matiere_id" class="form-select form-select-sm rounded-3">
                <option value="">Toutes les matières</option>
                @foreach($matieres as $m)
                <option value="{{ $m->id }}" {{ $matiereId == $m->id ? 'selected' : '' }}>{{ $m->code }} — {{ $m->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Professeur</label>
            <select name="prof_id" class="form-select form-select-sm rounded-3">
                <option value="">Tous</option>
                @foreach($profs as $p)
                <option value="{{ $p->id }}" {{ ($profId ?? '') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Statut</label>
            <select name="statut" class="form-select form-select-sm rounded-3">
                <option value="">Tous</option>
                <option value="planifiee"  {{ ($statut ?? '') === 'planifiee'  ? 'selected' : '' }}>Planifiée</option>
                <option value="en_cours"   {{ ($statut ?? '') === 'en_cours'   ? 'selected' : '' }}>En cours</option>
                <option value="terminee"   {{ ($statut ?? '') === 'terminee'   ? 'selected' : '' }}>Terminée</option>
                <option value="annulee"    {{ ($statut ?? '') === 'annulee'    ? 'selected' : '' }}>Annulée</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm flex-fill text-white rounded-3" style="background:var(--marron);">
                <i class="bi bi-funnel me-1"></i>Filtrer
            </button>
            @if($filtreActif)
            <a href="{{ route('seances.index', ['centreId' => $centreId, 'date' => today()->toDateString()]) }}"
               class="btn btn-sm rounded-3" style="border:1px solid #ddd;" title="Réinitialiser">
                <i class="bi bi-x-lg"></i>
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Barre navigation date (cachée si filtres actifs) --}}
@if(!$filtreActif)
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
@else
{{-- Mode filtre : bouton nouvelle séance flottant --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div style="font-size:13px;color:#888;">
        <i class="bi bi-funnel me-1"></i>
        {{ $seances->count() }} séance(s) trouvée(s) — 15 derniers jours + 60 jours à venir
    </div>
    <button class="btn text-white rounded-3" style="background:var(--fonce);"
            data-bs-toggle="modal" data-bs-target="#modalSeance">
        <i class="bi bi-plus-lg me-1"></i> Nouvelle séance
    </button>
</div>
@endif

{{-- Alertes --}}
@if(session('succes'))
<div class="alert alert-success rounded-3 mb-3"><i class="bi bi-check-circle me-2"></i>{{ session('succes') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger rounded-3 mb-3">
    @foreach($errors->all() as $e)<div><i class="bi bi-exclamation-circle me-1"></i>{{ $e }}</div>@endforeach
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
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                @if($filtreActif)
                <span style="font-size:12px;color:#888;font-weight:600;">
                    {{ \Carbon\Carbon::parse($s->debut)->locale('fr')->isoFormat('ddd D MMM') }}
                </span>
                @endif
                <span class="fw-bold" style="font-family:monospace;font-size:14px;">
                    {{ \Carbon\Carbon::parse($s->debut)->format('H:i') }} – {{ \Carbon\Carbon::parse($s->fin)->format('H:i') }}
                </span>
                @if($s->est_composition)
                <span class="badge rounded-pill px-3" style="font-size:11px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;">
                    ✏ Composition
                </span>
                @else
                <span class="badge rounded-pill px-3" style="font-size:11px;background:{{ ['HP'=>'#e3f2fd','TPE'=>'#f3e5f5'][$s->type]??'#eee' }};color:{{ ['HP'=>'#1565c0','TPE'=>'#6a1b9a'][$s->type]??'#333' }}">
                    {{ $s->type }}
                </span>
                @endif
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
        <div class="d-flex gap-2 flex-wrap align-items-center">
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

            @if($s->statut === 'terminee')
                @if($s->cloture_validee_at)
                <span class="badge rounded-pill px-3 py-2" style="background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;font-size:11px;">
                    <i class="bi bi-check2-circle me-1"></i>Clôture validée — {{ $s->nb_presents_valide }} présent(s)
                </span>
                @else
                <button class="btn btn-sm rounded-3" style="background:var(--fonce);color:#fff;font-size:12px;"
                        data-bs-toggle="modal" data-bs-target="#clotureModal{{ $s->id }}">
                    <i class="bi bi-journal-check me-1"></i> Valider clôture
                </button>
                @endif
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
                <div class="rounded-3 p-3 mb-3" style="background:#fff8e1;border:1px solid #fde68a;font-size:12px;">
                    <strong>Pause fixe de 30 min</strong><br>
                    Autorisée uniquement entre <strong>10h00–11h00</strong> ou <strong>15h00–16h00</strong>.<br>
                    Non disponible pour les séances du soir (≥ 17h30) et les Master.
                </div>
                <p style="font-size:13px;color:#555;">
                    Confirmer la pause de <strong>30 minutes</strong> pour la séance
                    <strong>{{ $s->matiere?->code }}</strong>
                    ({{ \Carbon\Carbon::parse($s->debut)->format('H:i') }} – {{ \Carbon\Carbon::parse($s->fin)->format('H:i') }}) ?
                </p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn text-white rounded-3" style="background:var(--fonce);">
                    <i class="bi bi-pause-fill me-1"></i>Confirmer la pause
                </button>
            </div>
        </form>
    </div></div>
</div>

{{-- Modal clôture de séance --}}
@if($s->statut === 'terminee' && !$s->cloture_validee_at)
@php
    $dureeMin = $s->debut->diffInMinutes($s->fin);
    $dureeH   = intdiv((int)$dureeMin, 60);
    $dureeRem = (int)$dureeMin % 60;
@endphp
<div class="modal fade" id="clotureModal{{ $s->id }}" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content rounded-4">
        <div class="modal-header border-0">
            <h6 class="modal-title fw-bold" style="color:var(--fonce)">
                <i class="bi bi-journal-check me-2"></i>Clôture — {{ $s->matiere?->code }}
                <span style="font-size:12px;font-weight:400;color:#aaa;">
                    {{ \Carbon\Carbon::parse($s->debut)->format('d/m/Y') }}
                </span>
            </h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

            {{-- Durée calculée (lecture seule) --}}
            <div class="p-3 rounded-3 mb-3" style="background:#f5f5f5;border:1px solid #e0e0e0;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:13px;font-weight:600;color:var(--fonce);">Durée calculée par le système</span>
                    <span style="font-size:18px;font-weight:700;color:var(--marron);">
                        {{ $dureeH }}h{{ $dureeRem > 0 ? str_pad($dureeRem,2,'0',STR_PAD_LEFT).'min' : '' }}
                    </span>
                </div>
                <small style="color:#888;font-size:11px;">
                    <i class="bi bi-clock me-1"></i>
                    {{ $s->debut->format('H:i') }} → {{ $s->fin->format('H:i') }}
                    &nbsp;·&nbsp; Ce volume ne peut pas être modifié directement.
                </small>
            </div>

            {{-- Contestation (accordion) --}}
            <div class="accordion mb-3" id="contestAcc{{ $s->id }}">
                <div class="accordion-item border rounded-3" style="overflow:hidden;">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2" type="button"
                                data-bs-toggle="collapse" data-bs-target="#contestBody{{ $s->id }}"
                                style="font-size:12px;background:#fffde7;">
                            <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
                            Je conteste cette durée — envoyer une réclamation
                        </button>
                    </h2>
                    <div id="contestBody{{ $s->id }}" class="accordion-collapse collapse">
                        <div class="accordion-body pt-2 pb-3" style="background:#fffde7;">
                            <form method="POST" action="{{ route('seances.contester', $s->id) }}">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label fw-semibold" style="font-size:12px;">Durée réelle estimée (en minutes) *</label>
                                    <input type="number" name="duree_contestee_minutes"
                                           class="form-control form-control-sm rounded-3"
                                           min="1" placeholder="{{ $dureeMin }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold" style="font-size:12px;">Motif de la réclamation *</label>
                                    <textarea name="motif" class="form-control form-control-sm rounded-3"
                                              rows="2" required
                                              placeholder="Ex : coupure de courant, retard de démarrage du système…"></textarea>
                                </div>
                                <button type="submit" class="btn btn-sm rounded-3"
                                        style="background:#fff3e0;color:#e65100;border:1px solid #ffcc80;font-size:12px;">
                                    <i class="bi bi-send me-1"></i>Envoyer la réclamation
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Validation des présences --}}
            <form method="POST" action="{{ route('seances.cloturer', $s->id) }}">
                @csrf
                <label class="form-label fw-semibold" style="font-size:13px;color:var(--fonce);">
                    Nombre d'étudiants présents à confirmer *
                </label>
                <div class="input-group mb-1">
                    <input type="number" name="nb_presents"
                           value="{{ $s->nb_presents_auto ?? 0 }}"
                           class="form-control rounded-start-3" min="0" required>
                    <span class="input-group-text rounded-end-3" style="font-size:12px;color:#888;">
                        comptage auto : {{ $s->nb_presents_auto ?? 0 }}
                    </span>
                </div>
                <small class="text-muted d-block mb-3" style="font-size:11px;">
                    Vous pouvez ajuster si le comptage automatique ne correspond pas à la réalité.
                </small>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light rounded-3 flex-fill" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn text-white rounded-3 flex-fill" style="background:var(--fonce);">
                        <i class="bi bi-check2-circle me-1"></i>Valider la clôture
                    </button>
                </div>
            </form>
        </div>
    </div></div>
</div>
@endif

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
                        <label class="form-label fw-semibold" style="font-size:13px;">Date et heure de début *</label>
                        <input type="datetime-local" name="debut" id="seanceDebut" class="form-control rounded-3"
                               value="{{ $date }}T07:30" required onchange="updateFinPreview()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Durée *</label>
                        <select name="duree_heures" id="seanceDuree" class="form-select rounded-3" required onchange="updateFinPreview()">
                            <option value="3">3 heures</option>
                            <option value="4">4 heures</option>
                        </select>
                        <small class="text-muted" id="seanceFinPreview" style="font-size:11px;"></small>
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

@push('scripts')
<script>
function updateFinPreview() {
    const debutVal = document.getElementById('seanceDebut')?.value;
    const duree    = parseInt(document.getElementById('seanceDuree')?.value || 3);
    const preview  = document.getElementById('seanceFinPreview');
    if (!debutVal || !preview) return;

    const debut = new Date(debutVal);
    const fin   = new Date(debut.getTime() + duree * 3600000);
    const fmt   = h => String(h).padStart(2, '0');
    preview.textContent = `Fin prévue : ${fmt(fin.getHours())}h${fmt(fin.getMinutes())}`;
}
// Initialiser au chargement
document.addEventListener('DOMContentLoaded', updateFinPreview);
</script>
@endpush
@endsection
