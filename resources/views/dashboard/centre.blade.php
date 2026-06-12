@extends('layouts.app')
@section('titre', 'Tableau de Bord — ' . $centre->nom)

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <span class="stat-icon">🎓</span>
            <div><div class="stat-value">{{ $nbEtudiants }}</div><div class="stat-label">Étudiants actifs</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#e3f2fd;">
            <span class="stat-icon">📅</span>
            <div><div class="stat-value">{{ $seancesAujourdhui->count() }}</div><div class="stat-label">Séances aujourd'hui</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#e8f5e9;">
            <span class="stat-icon">▶️</span>
            <div><div class="stat-value">{{ $seancesEnCours }}</div><div class="stat-label">En cours</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:{{ $nbInterpellations > 0 ? '#ffebee' : '#e8f5e9' }};">
            <span class="stat-icon">⚠️</span>
            <div><div class="stat-value">{{ $nbInterpellations }}</div><div class="stat-label">Interpellations</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="bg-white rounded-4 p-4 border">
            <h6 class="fw-bold mb-3" style="color:var(--fonce)">Modules</h6>
            <div class="row g-2">
                @foreach([
                    ['🎓','Étudiants','Inscriptions et effectifs', route('etudiants.index',$centreId)],
                    ['📅','Planning','Séances et calendrier','#'],
                    ['📡','Scan Accès','Contrôle des badges','#'],
                    ['📚','Matières','Volumes HP/TPE','#'],
                    ['⚠️','Interpellations','Assiduité < 75%','#'],
                    ['📊','Rapports','Statistiques','#'],
                ] as [$ico,$titre,$desc,$url])
                <div class="col-4">
                    <a href="{{ $url }}" class="d-block text-decoration-none p-3 rounded-3 border h-100"
                       style="transition:all .2s" onmouseover="this.style.background='var(--beige)'" onmouseout="this.style.background='#fff'">
                        <div style="font-size:22px;margin-bottom:6px;">{{ $ico }}</div>
                        <div style="font-weight:600;font-size:13px;color:var(--fonce)">{{ $titre }}</div>
                        <div style="font-size:11px;color:var(--marron)">{{ $desc }}</div>
                    </a>
                </div>
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
                    <span style="font-size:12px;font-family:monospace;color:var(--marron)">
                        {{ $s->debut->format('H:i') }} – {{ $s->fin->format('H:i') }}
                    </span>
                    <span class="badge rounded-pill" style="font-size:10px;background:{{ $s->statut=='en_cours'?'#e8f5e9':'#f5f5f5' }};color:{{ $s->statut=='en_cours'?'#2e7d32':'#616161' }}">
                        {{ $s->statut }}
                    </span>
                </div>
                <div style="font-size:13px;font-weight:600;color:var(--fonce)">{{ $s->matiere?->nom }}</div>
                <div style="font-size:11px;color:var(--marron)">{{ $s->salle?->nom }} · {{ $s->professeur?->name }}</div>
            </div>
            @empty
            <p class="text-center py-4" style="font-size:13px;color:#aaa;">Aucune séance prévue.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
