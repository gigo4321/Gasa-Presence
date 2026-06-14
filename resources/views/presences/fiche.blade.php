@extends('layouts.app')
@section('titre', 'Fiche de Présence')

@section('content')

{{-- EN-TÊTE FICHE --}}
<div class="bg-white rounded-4 border mb-4 overflow-hidden">
    <div class="px-4 py-3 d-flex align-items-center justify-content-between" style="background:var(--fonce);">
        <div>
            <div style="font-size:11px;color:rgba(255,255,255,.5);margin-bottom:4px;">
                @php $f = $seance->matiere?->niveau?->filiereOption?->filiere; $o = $seance->matiere?->niveau?->filiereOption; $n = $seance->matiere?->niveau; @endphp
                {{ $f?->nom }} › {{ $o?->nom }} › {{ $n?->libelle }}
            </div>
            <h5 class="mb-0 fw-bold text-white">{{ $seance->matiere?->nom }}</h5>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('presences.export', $seance->id) }}" target="_blank"
               class="btn btn-sm rounded-3 text-white"
               style="background:rgba(255,255,255,.2);font-size:12px;border:1px solid rgba(255,255,255,.3);">
                <i class="bi bi-printer me-1"></i> Imprimer / PDF
            </a>
            <a href="{{ url()->previous() }}" class="btn btn-sm rounded-3"
               style="background:rgba(255,255,255,.15);color:#fff;font-size:12px;border:1px solid rgba(255,255,255,.3);">
                ← Retour
            </a>
        </div>
    </div>

    {{-- INFOS SÉANCE --}}
    <div class="row g-0">
        {{-- Détails séance --}}
        <div class="col-md-4 px-4 py-3 border-end">
            <div class="row g-2">
                <div class="col-6">
                    <div style="font-size:11px;color:var(--marron);text-transform:uppercase;letter-spacing:.05em;">Date</div>
                    <div style="font-size:13px;font-weight:600;color:var(--fonce);">
                        {{ \Carbon\Carbon::parse($seance->debut)->locale('fr')->isoFormat('dddd D MMM YYYY') }}
                    </div>
                </div>
                <div class="col-6">
                    <div style="font-size:11px;color:var(--marron);text-transform:uppercase;letter-spacing:.05em;">Horaire</div>
                    <div style="font-size:13px;font-weight:600;color:var(--fonce);">
                        {{ $seance->debut->format('H:i') }} – {{ $seance->fin->format('H:i') }}
                        <span style="font-size:11px;color:#aaa;">({{ $dureeActuelle }}h)</span>
                    </div>
                </div>
                <div class="col-6">
                    <div style="font-size:11px;color:var(--marron);text-transform:uppercase;letter-spacing:.05em;">Salle</div>
                    <div style="font-size:13px;font-weight:600;color:var(--fonce);">{{ $seance->salle?->nom }}</div>
                    <div style="font-size:11px;color:#aaa;">{{ $seance->salle?->centre?->nom }}</div>
                </div>
                <div class="col-6">
                    <div style="font-size:11px;color:var(--marron);text-transform:uppercase;letter-spacing:.05em;">Type</div>
                    <span class="badge rounded-2 px-2"
                          style="font-size:11px;background:{{ $seance->type==='HP'?'#e3f2fd':'#f3e5f5' }};color:{{ $seance->type==='HP'?'#1565c0':'#6a1b9a' }}">
                        {{ $seance->type === 'HP' ? 'Heures Professeur' : 'Travaux Personnels' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Professeur + badge --}}
        <div class="col-md-4 px-4 py-3 border-end">
            <div style="font-size:11px;color:var(--marron);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Professeur</div>
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:36px;height:36px;background:var(--fonce);color:var(--beige);font-size:14px;font-weight:700;">
                    {{ strtoupper(substr($seance->professeur?->name ?? 'P', 0, 1)) }}
                </div>
                <div>
                    <div style="font-weight:600;font-size:13px;color:var(--fonce);">{{ $seance->professeur?->name }}</div>
                    <div style="font-size:11px;color:#aaa;">{{ $seance->professeur?->email }}</div>
                </div>
            </div>
            <div class="row g-1">
                <div class="col-6">
                    <div style="font-size:10px;color:var(--marron);">Scan entrée</div>
                    <div style="font-size:12px;font-weight:600;color:{{ $seance->heure_scan_professeur?'#2e7d32':'#c62828' }}">
                        {{ $seance->heure_scan_professeur?->format('H:i') ?? 'Non badgé' }}
                    </div>
                </div>
                <div class="col-6">
                    <div style="font-size:10px;color:var(--marron);">Fin réelle</div>
                    <div style="font-size:12px;font-weight:600;color:var(--fonce);">{{ $seance->fin->format('H:i') }}</div>
                </div>
                @if($seance->heure_debut_pause)
                <div class="col-12 mt-1">
                    <div style="font-size:10px;color:#e65100;">Pause</div>
                    <div style="font-size:11px;color:#e65100;">
                        {{ \Carbon\Carbon::parse($seance->heure_debut_pause)->format('H:i') }}
                        – {{ \Carbon\Carbon::parse($seance->heure_fin_pause)->format('H:i') }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Groupes + responsables --}}
        <div class="col-md-4 px-4 py-3">
            <div style="font-size:11px;color:var(--marron);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Groupes concernés</div>
            @foreach($seance->options as $opt)
            <div class="mb-2 pb-2" style="border-bottom:1px solid #f5f5f5;">
                <div style="font-weight:600;font-size:13px;color:var(--fonce);">{{ $opt->nom }}</div>
                <div style="font-size:11px;color:#888;">
                    {{ $opt->niveau?->libelle }} · {{ $opt->centre?->nom }}
                </div>
                @if($opt->responsable_nom)
                <div style="font-size:11px;color:var(--marron);margin-top:2px;">
                    <i class="bi bi-person-badge me-1"></i>Responsable : <strong>{{ $opt->responsable_nom }}</strong>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ── SUIVI HEURES PROFESSEUR ────────────────────────────────────────────── --}}
@php
    $hpPct  = $hpInitial  > 0 ? min(100, round($profHpFait  / $hpInitial  * 100)) : 0;
    $tpePct = $tpeInitial > 0 ? min(100, round($profTpeFait / $tpeInitial * 100)) : 0;
@endphp

@if($estDerniereHP || $estDerniereTPE)
<div class="rounded-4 p-3 mb-4 d-flex align-items-start gap-3"
     style="background:#fff8e1;border:2px solid #fde68a;">
    <span style="font-size:24px;">🏁</span>
    <div>
        <div style="font-weight:700;color:#92400e;font-size:14px;">Dernière séance de ce module</div>
        <div style="font-size:13px;color:#78350f;">
            Cette séance clôture le quota
            {{ $estDerniereHP ? 'HP' : '' }}{{ $estDerniereHP && $estDerniereTPE ? ' et ' : '' }}{{ $estDerniereTPE ? 'TPE' : '' }}
            de <strong>{{ $seance->matiere?->nom }}</strong> pour ce professeur.
        </div>
    </div>
</div>
@endif

<div class="bg-white rounded-4 border p-4 mb-4">
    <h6 class="fw-bold mb-3" style="color:var(--fonce);font-size:14px;">
        <i class="bi bi-clock-history me-2"></i>Suivi des heures — {{ $seance->professeur?->name }} / {{ $seance->matiere?->code }}
    </h6>
    <div class="row g-4">
        {{-- HP --}}
        <div class="col-md-6">
            <div class="p-3 rounded-3" style="background:#f0f4ff;border:1px solid #c7d2fe;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:12px;font-weight:700;color:#3730a3;text-transform:uppercase;letter-spacing:.04em;">Heures Professeur (HP)</span>
                    <span class="badge rounded-pill" style="background:#3730a3;font-size:11px;">{{ $hpInitial }}h prévues</span>
                </div>
                <div class="d-flex gap-4 mb-3">
                    <div>
                        <div style="font-size:22px;font-weight:800;color:#3730a3;">{{ $profHpFait }}h</div>
                        <div style="font-size:11px;color:#6366f1;">Effectuées</div>
                    </div>
                    <div>
                        <div style="font-size:22px;font-weight:800;color:{{ $profHpRestant > 0 ? '#dc2626' : '#16a34a' }};">{{ $profHpRestant }}h</div>
                        <div style="font-size:11px;color:#888;">Restantes</div>
                    </div>
                    <div>
                        <div style="font-size:22px;font-weight:800;color:var(--fonce);">{{ $profNbSeances }}</div>
                        <div style="font-size:11px;color:#888;">Séances faites</div>
                    </div>
                </div>
                <div class="progress rounded-pill" style="height:8px;">
                    <div class="progress-bar" role="progressbar"
                         style="width:{{ $hpPct }}%;background:{{ $hpPct >= 100 ? '#16a34a' : '#6366f1' }};">
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-1" style="font-size:10px;color:#888;">
                    <span>0h</span><span>{{ $hpPct }}% complété</span><span>{{ $hpInitial }}h</span>
                </div>
            </div>
        </div>

        {{-- TPE --}}
        <div class="col-md-6">
            <div class="p-3 rounded-3" style="background:#fdf4ff;border:1px solid #e9d5ff;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:12px;font-weight:700;color:#6b21a8;text-transform:uppercase;letter-spacing:.04em;">Travaux Encadrés (TPE)</span>
                    <span class="badge rounded-pill" style="background:#6b21a8;font-size:11px;">{{ $tpeInitial }}h prévues</span>
                </div>
                <div class="d-flex gap-4 mb-3">
                    <div>
                        <div style="font-size:22px;font-weight:800;color:#6b21a8;">{{ $profTpeFait }}h</div>
                        <div style="font-size:11px;color:#a855f7;">Effectuées</div>
                    </div>
                    <div>
                        <div style="font-size:22px;font-weight:800;color:{{ $profTpeRestant > 0 ? '#dc2626' : '#16a34a' }};">{{ $profTpeRestant }}h</div>
                        <div style="font-size:11px;color:#888;">Restantes</div>
                    </div>
                    @if($quota)
                    <div>
                        <div style="font-size:22px;font-weight:800;color:var(--marron);">{{ $quota->tpe_dynamique }}h</div>
                        <div style="font-size:11px;color:#888;">TPE dyn. centre</div>
                    </div>
                    @endif
                </div>
                @if($tpeInitial > 0)
                <div class="progress rounded-pill" style="height:8px;">
                    <div class="progress-bar" role="progressbar"
                         style="width:{{ $tpePct }}%;background:{{ $tpePct >= 100 ? '#16a34a' : '#a855f7' }};">
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-1" style="font-size:10px;color:#888;">
                    <span>0h</span><span>{{ $tpePct }}% complété</span><span>{{ $tpeInitial }}h</span>
                </div>
                @else
                <div style="font-size:12px;color:#aaa;font-style:italic;">Pas de TPE prévu pour cette matière.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Séances précédentes de ce prof pour cette matière --}}
    @if($profSeancesTerminees->count() > 0)
    <div class="mt-3 pt-3" style="border-top:1px solid #f0f0f0;">
        <div style="font-size:12px;font-weight:600;color:#888;margin-bottom:8px;">
            Historique séances terminées ({{ $profSeancesTerminees->count() }})
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($profSeancesTerminees->sortByDesc('debut') as $s)
            <span class="badge rounded-pill px-3 py-2"
                  style="font-size:11px;background:{{ $s->id === $seance->id ? 'var(--marron)' : ($s->type==='HP'?'#e0e7ff':'#f3e8ff') }};color:{{ $s->id === $seance->id ? '#fff' : ($s->type==='HP'?'#3730a3':'#6b21a8') }};">
                {{ $s->debut->format('d/m') }} · {{ $s->type }} · {{ round($s->duree_heures, 1) }}h
                @if($s->id === $seance->id) ← actuelle @endif
            </span>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- KPI présence --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card"><span class="stat-icon">👥</span>
            <div><div class="stat-value">{{ $totalInscrits }}</div><div class="stat-label">Inscrits</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#e8f5e9;"><span class="stat-icon">✅</span>
            <div><div class="stat-value">{{ $nbPresents }}</div><div class="stat-label">Présents</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#ffebee;"><span class="stat-icon">❌</span>
            <div><div class="stat-value">{{ $nbAbsents }}</div><div class="stat-label">Absents</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#fff3e0;"><span class="stat-icon">⚠️</span>
            <div><div class="stat-value">{{ $nbInsuffisants }}</div><div class="stat-label">Présence insuffisante</div></div>
        </div>
    </div>
</div>

{{-- LISTE DES ÉTUDIANTS --}}
<div class="bg-white rounded-4 border overflow-hidden">
    <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between" style="background:var(--beige);">
        <h6 class="fw-bold mb-0" style="color:var(--fonce)">Liste de présence</h6>
        <span style="font-size:12px;color:#888;">{{ $seance->presences->count() }} enregistrements</span>
    </div>
    <table class="table table-hover table-gasa mb-0">
        <thead>
            <tr>
                <th class="px-4" style="width:40px;">#</th>
                <th style="width:110px;">Matricule</th>
                <th>Nom & Prénom</th>
                <th>Groupe</th>
                <th style="width:90px;">Entrée</th>
                <th style="width:90px;">Sortie déf.</th>
                <th style="width:100px;">Sorties temp.</th>
                <th style="width:80px;">Durée eff.</th>
                <th style="width:130px;">Statut</th>
            </tr>
        </thead>
        <tbody>
        @forelse($seance->presences as $i => $presence)
        @php
            $etu = $presence->inscription?->etudiant;
            $dureeSeance = $seance->debut->diffInMinutes($seance->fin);
            $dureeSorties = $presence->sortiesTemporaires->sum('duree_minutes');
            $dureeEff = ($presence->heure_entree && $presence->heure_sortie_definitive)
                ? $presence->heure_entree->diffInMinutes($presence->heure_sortie_definitive) - $dureeSorties
                : null;
            $statutColors = [
                'present'                     => ['#e8f5e9','#2e7d32'],
                'absent'                      => ['#ffebee','#c62828'],
                'presence_insuffisante'       => ['#fff3e0','#e65100'],
                'sortie_anticipee_toleree'    => ['#e8f5e9','#558b2f'],
                'sortie_anticipee_non_toleree'=> ['#fff3e0','#bf360c'],
            ];
            $sc = $statutColors[$presence->statut] ?? ['#f5f5f5','#616161'];
            $opt = $presence->inscription?->option;
        @endphp
        <tr>
            <td class="px-4" style="font-size:12px;color:#aaa;">{{ $i + 1 }}</td>
            <td><code style="font-size:11px;background:#f5f5f5;padding:2px 5px;border-radius:4px;">{{ $etu?->matricule }}</code></td>
            <td>
                <div style="font-weight:600;font-size:13px;color:var(--fonce);">{{ $etu?->nom }} {{ $etu?->prenom }}</div>
                @if($etu?->badge_uid)
                <div style="font-family:monospace;font-size:10px;color:#bbb;">{{ $etu->badge_uid }}</div>
                @endif
            </td>
            <td style="font-size:12px;color:#666;">
                {{ $opt?->nom }}
                @if($opt?->responsable_nom)
                <br><span style="font-size:10px;color:var(--marron);">resp. {{ $opt->responsable_nom }}</span>
                @endif
            </td>
            <td>
                @if($presence->heure_entree)
                <span style="font-size:12px;font-weight:600;color:#2e7d32;">{{ $presence->heure_entree->format('H:i') }}</span>
                @else <span style="color:#aaa;font-size:12px;">—</span> @endif
            </td>
            <td>
                @if($presence->heure_sortie_definitive)
                <span style="font-size:12px;font-weight:600;color:var(--fonce);">{{ $presence->heure_sortie_definitive->format('H:i') }}</span>
                @else <span style="color:#aaa;font-size:12px;">—</span> @endif
            </td>
            <td>
                @if($presence->sortiesTemporaires->count())
                <span class="badge rounded-pill px-2" style="background:#fff3e0;color:#e65100;font-size:11px;">
                    {{ $presence->sortiesTemporaires->count() }}× ({{ $dureeSorties }} min)
                </span>
                @else <span style="color:#aaa;font-size:12px;">—</span> @endif
            </td>
            <td>
                @if($dureeEff !== null)
                <span style="font-size:12px;font-weight:600;color:{{ $dureeEff >= $dureeSeance * 0.5 ? 'var(--fonce)' : '#c62828' }}">
                    {{ floor($dureeEff/60) }}h{{ str_pad($dureeEff%60, 2, '0', STR_PAD_LEFT) }}
                </span>
                @else <span style="color:#aaa;font-size:12px;">—</span> @endif
            </td>
            <td>
                <span class="badge rounded-pill px-2"
                      style="font-size:11px;background:{{ $sc[0] }};color:{{ $sc[1] }}">
                    {{ match($presence->statut) {
                        'present'                     => 'Présent',
                        'absent'                      => 'Absent',
                        'presence_insuffisante'       => 'Insuffisante',
                        'sortie_anticipee_toleree'    => 'Sortie tolérée',
                        'sortie_anticipee_non_toleree'=> 'Non tolérée',
                        default                       => $presence->statut,
                    } }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="text-center py-5" style="color:#aaa;">Aucun enregistrement pour cette séance.</td>
        </tr>
        @endforelse
        </tbody>
    </table>
</div>

@endsection
