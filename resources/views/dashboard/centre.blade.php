@extends('layouts.app')
@section('titre','Tableau de Bord — '.$centre->nom)
@section('content')
@if($annee)
<div class="alert rounded-3 mb-4 d-flex align-items-center justify-content-between"
     style="background:var(--beige);border-color:var(--marron);color:var(--fonce);">
    <div class="d-flex align-items-center gap-3">
        <i class="bi bi-calendar-check" style="font-size:20px;"></i>
        <span>Données affichées pour : <strong>{{ $annee->libelle }}</strong>
        ({{ $annee->date_debut->locale('fr')->isoFormat('D MMM YYYY') }} — {{ $annee->date_fin->locale('fr')->isoFormat('D MMM YYYY') }})</span>
    </div>
    <div class="dropdown">
        <button class="btn btn-sm dropdown-toggle text-white" type="button" data-bs-toggle="dropdown" style="background:var(--fonce);">
            Changer d'année
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            @foreach($annees as $a)
                <li><a class="dropdown-item {{ $annee->id == $a->id ? 'active' : '' }}" href="{{ route('dashboard.centre', ['centreId' => $centreId, 'annee_id' => $a->id]) }}">{{ $a->libelle }}</a></li>
            @endforeach
        </ul>
    </div>
</div>
@endif
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><span class="stat-icon">🎓</span><div><div class="stat-value">{{ $nbInscrits }}</div><div class="stat-label">Inscrits actifs</div></div></div></div>
    <div class="col-md-4"><div class="stat-card" style="background:#EBF0F5;"><span class="stat-icon">📅</span><div><div class="stat-value">{{ $seancesAujourdhui->count() }}</div><div class="stat-label">Séances aujourd'hui</div></div></div></div>
    <div class="col-md-4"><div class="stat-card" style="background:#EBF0EA;"><span class="stat-icon">▶️</span><div><div class="stat-value">{{ $seancesEnCours }}</div><div class="stat-label">En cours</div></div></div></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-4">
        <div class="stat-card">
            <span class="stat-icon">📋</span>
            <div><div class="stat-value">{{ $nbGroupes }}</div><div class="stat-label">Groupes actifs</div></div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card" style="background:#EDE8F3;">
            <span class="stat-icon">👨‍🏫</span>
            <div><div class="stat-value">{{ $nbProfesseurs }}</div><div class="stat-label">Professeurs</div></div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card" style="background:#E8EEF5;">
            <span class="stat-icon">📅</span>
            <div><div class="stat-value">{{ $nbSeancesSemaine }}</div><div class="stat-label">Séances cette semaine</div></div>
        </div>
    </div>
</div>
<div class="row g-3">
    <div class="col-md-8">
        <div class="bg-white rounded-4 p-4 border">
            <h6 class="fw-bold mb-3" style="color:var(--fonce)">Prochaines séances — reste de la semaine</h6>
            @forelse($seancesSemaine as $s)
            <div class="d-flex align-items-center gap-3 border-bottom pb-2 mb-2">
                <div style="min-width:52px;text-align:center;background:var(--beige);border-radius:8px;padding:4px 6px;">
                    <div style="font-size:10px;color:var(--marron);font-weight:600;">{{ $s->debut->locale('fr')->isoFormat('ddd') }}</div>
                    <div style="font-size:15px;font-weight:700;color:var(--fonce);">{{ $s->debut->format('d') }}</div>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:var(--fonce);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $s->matiere?->nom }}</div>
                    <div style="font-size:11px;color:var(--marron);">{{ $s->salle?->nom }} · {{ $s->professeur?->name }}</div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:12px;font-family:monospace;color:var(--fonce);">{{ $s->debut->format('H:i') }}</div>
                    <span class="badge rounded-pill" style="font-size:10px;background:#EBF0F5;color:#2D4A6B;">{{ $s->type }}</span>
                </div>
            </div>
            @empty
            <p class="text-center py-4 mb-0" style="font-size:13px;color:#aaa;">
                <i class="bi bi-calendar-x" style="font-size:24px;display:block;margin-bottom:6px;"></i>
                Aucune séance planifiée pour le reste de la semaine.
            </p>
            @endforelse
        </div>
    </div>
    <div class="col-md-4">
        <div class="bg-white rounded-4 p-4 border">
            <h6 class="fw-bold mb-3" style="color:var(--fonce)">Séances aujourd'hui</h6>
            @forelse($seancesAujourdhui as $s)
            <div class="border-bottom pb-2 mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:12px;font-family:monospace;color:var(--marron)">{{ $s->debut->format('H:i') }} – {{ $s->fin->format('H:i') }}</span>
                    <span class="badge rounded-pill" style="font-size:10px;background:{{ $s->statut=='en_cours'?'#EBF0EA':'#f5f5f5' }};color:{{ $s->statut=='en_cours'?'#3A5C38':'#616161' }}">{{ $s->statut }}</span>
                </div>
                <div style="font-size:13px;font-weight:600;color:var(--fonce)">{{ $s->matiere?->nom }}</div>
                <div style="font-size:11px;color:var(--marron)">{{ $s->salle?->nom }} · {{ $s->professeur?->name }}</div>
            </div>
            @empty<p class="text-center py-3" style="font-size:13px;color:#aaa;">Aucune séance.</p>@endforelse
        </div>
    </div>
</div>
@endsection
