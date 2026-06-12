@extends('layouts.app')
@section('titre', 'Vue d\'ensemble — GASA-FORMATION')

@section('content')

{{-- KPI GLOBAUX --}}
<div class="row g-3 mb-5">
    <div class="col-md-3">
        <div class="stat-card">
            <span class="stat-icon">🏢</span>
            <div>
                <div class="stat-value">{{ $stats['nb_centres'] }}</div>
                <div class="stat-label">Centres actifs</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#e3f2fd;">
            <span class="stat-icon">🎓</span>
            <div>
                <div class="stat-value">{{ $stats['total_etudiants'] }}</div>
                <div class="stat-label">Étudiants actifs</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#e8f5e9;">
            <span class="stat-icon">📅</span>
            <div>
                <div class="stat-value">{{ $stats['seances_aujourd_hui'] }}</div>
                <div class="stat-label">Séances aujourd'hui</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:{{ $stats['total_interpellations'] > 0 ? '#ffebee' : '#e8f5e9' }};">
            <span class="stat-icon">⚠️</span>
            <div>
                <div class="stat-value">{{ $stats['total_interpellations'] }}</div>
                <div class="stat-label">Interpellations réseau</div>
            </div>
        </div>
    </div>
</div>

{{-- TABLEAU DE SUIVI PAR CENTRE --}}
<div class="bg-white rounded-4 border mb-5 overflow-hidden">
    <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between"
         style="background:var(--fonce);">
        <h6 class="mb-0 fw-bold text-white">Suivi consolidé par centre</h6>
        <span style="font-size:12px;color:rgba(255,255,255,.6);">
            {{ now()->locale('fr')->isoFormat('D MMMM YYYY') }}
        </span>
    </div>
    <table class="table table-hover mb-0">
        <thead style="background:var(--beige);">
            <tr style="font-size:12px;color:var(--fonce);">
                <th class="px-4 py-3">Centre</th>
                <th class="py-3">Ville</th>
                <th class="py-3">Étudiants actifs</th>
                <th class="py-3">Séances aujourd'hui</th>
                <th class="py-3">En cours</th>
                <th class="py-3">Interpellations</th>
                <th class="py-3">Taux assiduité</th>
                <th class="py-3"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($centresStats as $cs)
            <tr>
                <td class="px-4 py-3 fw-semibold" style="color:var(--fonce);">
                    {{ $cs['centre']->nom }}
                </td>
                <td class="py-3" style="font-size:13px;color:var(--marron);">
                    {{ $cs['centre']->ville }}
                </td>
                <td class="py-3">
                    <span class="fw-bold">{{ $cs['nb_etudiants'] }}</span>
                </td>
                <td class="py-3">{{ $cs['seances_aujourd_hui'] }}</td>
                <td class="py-3">
                    @if($cs['en_cours'] > 0)
                    <span class="badge rounded-pill px-2"
                          style="background:#e8f5e9;color:#2e7d32;font-size:11px;">
                        {{ $cs['en_cours'] }} en cours
                    </span>
                    @else
                    <span style="color:#aaa;font-size:12px;">—</span>
                    @endif
                </td>
                <td class="py-3">
                    @if($cs['interpellations'] > 0)
                    <span class="badge rounded-pill px-2"
                          style="background:#ffebee;color:#c62828;font-size:11px;">
                        {{ $cs['interpellations'] }} alertes
                    </span>
                    @else
                    <span style="color:#4caf50;font-size:12px;">✓ OK</span>
                    @endif
                </td>
                <td class="py-3">
                    @php $taux = $cs['taux_assiduite']; @endphp
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:70px;height:6px;background:#eee;border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:{{ $taux }}%;background:{{ $taux >= 75 ? '#4caf50' : ($taux >= 50 ? '#ff9800' : '#f44336') }};border-radius:3px;"></div>
                        </div>
                        <span style="font-size:12px;font-weight:600;color:{{ $taux >= 75 ? '#2e7d32' : ($taux >= 50 ? '#e65100' : '#c62828') }}">
                            {{ $taux }}%
                        </span>
                    </div>
                </td>
                <td class="py-3">
                    <a href="{{ route('dashboard.centre', $cs['centre']->id) }}"
                       class="btn btn-sm rounded-3 text-white"
                       style="background:var(--fonce);font-size:11px;white-space:nowrap;">
                        Accéder →
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- RÉPARTITION ÉTUDIANTS PAR FILIÈRE --}}
<div class="row g-4">
    <div class="col-md-6">
        <div class="bg-white rounded-4 border p-4">
            <h6 class="fw-bold mb-4" style="color:var(--fonce)">
                Répartition par filière
            </h6>
            @foreach($stats['par_filiere'] as $filiere => $nb)
            @php $pct = $stats['total_etudiants'] > 0 ? round($nb / $stats['total_etudiants'] * 100) : 0; @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span style="font-size:13px;font-weight:600;color:var(--fonce)">{{ $filiere }}</span>
                    <span style="font-size:13px;color:var(--marron)">{{ $nb }} étudiants ({{ $pct }}%)</span>
                </div>
                <div style="height:8px;background:#eee;border-radius:4px;overflow:hidden;">
                    <div style="height:100%;width:{{ $pct }}%;background:var(--marron);border-radius:4px;transition:width .3s;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="col-md-6">
        <div class="bg-white rounded-4 border p-4">
            <h6 class="fw-bold mb-4" style="color:var(--fonce)">
                Activité récente (7 derniers jours)
            </h6>
            @forelse($stats['seances_recentes'] as $s)
            <div class="d-flex align-items-start gap-3 pb-3 mb-3 border-bottom">
                <div class="rounded-3 px-2 py-1 text-center flex-shrink-0"
                     style="background:{{ $s->type === 'HP' ? '#e3f2fd' : '#f3e5f5' }};min-width:40px;">
                    <div style="font-size:10px;font-weight:700;color:{{ $s->type === 'HP' ? '#1565c0' : '#6a1b9a' }}">
                        {{ $s->type }}
                    </div>
                </div>
                <div class="flex-1">
                    <div style="font-size:13px;font-weight:600;color:var(--fonce)">
                        {{ $s->matiere?->nom }}
                    </div>
                    <div style="font-size:12px;color:var(--marron)">
                        {{ $s->salle?->centre?->nom }} · {{ \Carbon\Carbon::parse($s->debut)->locale('fr')->isoFormat('D MMM, H:mm') }}
                    </div>
                </div>
                <span class="badge rounded-pill"
                      style="font-size:10px;background:{{ $s->statut==='terminee' ? '#e8f5e9' : '#f5f5f5' }};color:{{ $s->statut==='terminee' ? '#2e7d32' : '#616161' }}">
                    {{ $s->statut }}
                </span>
            </div>
            @empty
            <p style="color:#aaa;font-size:13px;text-align:center;padding:20px 0;">
                Aucune séance cette semaine.
            </p>
            @endforelse
        </div>
    </div>
</div>

@endsection
