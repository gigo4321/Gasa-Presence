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
    <div class="col-md-3"><div class="stat-card"><span class="stat-icon">🎓</span><div><div class="stat-value">{{ $nbInscrits }}</div><div class="stat-label">Inscrits actifs</div></div></div></div>
    <div class="col-md-3"><div class="stat-card" style="background:#e3f2fd;"><span class="stat-icon">📅</span><div><div class="stat-value">{{ $seancesAujourdhui->count() }}</div><div class="stat-label">Séances aujourd'hui</div></div></div></div>
    <div class="col-md-3"><div class="stat-card" style="background:#e8f5e9;"><span class="stat-icon">▶️</span><div><div class="stat-value">{{ $seancesEnCours }}</div><div class="stat-label">En cours</div></div></div></div>
    <div class="col-md-3"><div class="stat-card" style="background:#fff3e0;"><span class="stat-icon">⚠️</span><div><div class="stat-value">0</div><div class="stat-label">Interpellations</div></div></div></div>
</div>
<div class="row g-3">
    <div class="col-md-8">
        <div class="bg-white rounded-4 p-4 border">
            <h6 class="fw-bold mb-3" style="color:var(--fonce)">Modules</h6>
            <div class="row g-2">
                @foreach([
                    ['📋','Groupes','Options par année',route('options.index',$centreId)],
                    ['🎓','Étudiants','Inscriptions',route('etudiants.index',$centreId)],
                    ['👨‍🏫','Professeurs','Corps enseignant',route('professeurs.index',$centreId)],
                    ['📅','Planning','Séances HP/TPE',route('seances.index',$centreId)],
                    ['🚪','Salles','Salles & Équipements',route('salles.index',$centreId)],
                    ['📡','Scan Accès','Badges RFID/QR',route('scan.index',$centreId)],
                    ['📚','Matières','Quotas HP/TPE',route('matieres.index',$centreId)],
                    ['📊','Présences','Fiches & rapports',route('presences.centre',$centreId)],
                ] as [$ico,$t,$d,$u])
                <div class="col-4"><a href="{{ $u }}" class="d-block text-decoration-none p-3 rounded-3 border h-100"
                    onmouseover="this.style.background='var(--beige)'" onmouseout="this.style.background='#fff'">
                    <div style="font-size:22px;margin-bottom:6px;">{{ $ico }}</div>
                    <div style="font-weight:600;font-size:13px;color:var(--fonce)">{{ $t }}</div>
                    <div style="font-size:11px;color:var(--marron)">{{ $d }}</div>
                </a></div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="bg-white rounded-4 p-4 border">
            <h6 class="fw-bold mb-3" style="color:var(--fonce)">Séances aujourd'hui</h6>
            @forelse($seancesAujourdhui as $s)
            <div class="border-bottom pb-2 mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:12px;font-family:monospace;color:var(--marron)">{{ $s->debut->format('H:i') }} – {{ $s->fin->format('H:i') }}</span>
                    <span class="badge rounded-pill" style="font-size:10px;background:{{ $s->statut=='en_cours'?'#e8f5e9':'#f5f5f5' }};color:{{ $s->statut=='en_cours'?'#2e7d32':'#616161' }}">{{ $s->statut }}</span>
                </div>
                <div style="font-size:13px;font-weight:600;color:var(--fonce)">{{ $s->matiere?->nom }}</div>
                <div style="font-size:11px;color:var(--marron)">{{ $s->salle?->nom }} · {{ $s->professeur?->name }}</div>
            </div>
            @empty<p class="text-center py-3" style="font-size:13px;color:#aaa;">Aucune séance.</p>@endforelse
        </div>
    </div>
</div>
@endsection
