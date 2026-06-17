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
                          style="font-size:11px;background:{{ $seance->type==='HP'?'#EBF0F5':'#EDE8F3' }};color:{{ $seance->type==='HP'?'#2D4A6B':'#3F2A52' }}">
                        {{ $seance->type === 'HP' ? 'Heures Professeur' : 'Travaux Personnels' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Professeur + badge (HP uniquement) --}}
        <div class="col-md-4 px-4 py-3 border-end">
        @if($seance->type === 'HP')
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
            @php
                $ficheEfMin = $dureeEffectiveMinutes;
                $ficheEfH   = intdiv($ficheEfMin, 60);
                $ficheEfRem = $ficheEfMin % 60;
                $fichePauseMin = (int)($seance->durees_pauses_minutes ?? 0);
            @endphp
            <div class="row g-1">
                <div class="col-6">
                    <div style="font-size:10px;color:var(--marron);">Scan entrée</div>
                    <div style="font-size:12px;font-weight:600;color:{{ $seance->heure_scan_professeur?'#3A5C38':'#6B2737' }}">
                        {{ $seance->heure_scan_professeur?->format('H:i') ?? 'Non badgé' }}
                    </div>
                </div>
                <div class="col-6">
                    <div style="font-size:10px;color:var(--marron);">Fin séance</div>
                    <div style="font-size:12px;font-weight:600;color:var(--fonce);">{{ $seance->fin->format('H:i') }}</div>
                </div>
                @if($fichePauseMin > 0)
                <div class="col-6 mt-1">
                    <div style="font-size:10px;color:#8B6914;">Pause totale</div>
                    <div style="font-size:12px;font-weight:600;color:#8B6914;">{{ $fichePauseMin }} min</div>
                </div>
                @endif
                @if($seance->heure_scan_professeur)
                <div class="{{ $fichePauseMin > 0 ? 'col-6' : 'col-12' }} mt-1">
                    <div style="font-size:10px;color:#2D4A6B;">Durée effective</div>
                    <div style="font-size:12px;font-weight:700;color:#2D4A6B;">
                        {{ $ficheEfH }}h{{ $ficheEfRem > 0 ? str_pad($ficheEfRem,2,'0',STR_PAD_LEFT).'min' : '' }}
                    </div>
                </div>
                @endif
            </div>
        @else
            <div style="font-size:11px;color:var(--marron);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Encadrement</div>
            <div class="rounded-3 p-3" style="background:#EDE8F3;border:1px solid #D8CEDD;">
                <div style="font-weight:600;font-size:13px;color:#3F2A52;">Travaux autonomes</div>
                <div style="font-size:12px;color:#7A6585;margin-top:4px;">
                    Séance TPE — les étudiants travaillent sans professeur.<br>
                    L'accès est géré par RFID étudiant uniquement.
                </div>
            </div>
        @endif
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

{{-- ── SUIVI HEURES PROFESSEUR (HP uniquement) ────────────────────────────── --}}
@if($seance->type === 'HP')
@php
    $hpPct = $hpInitial > 0 ? min(100, round($profHpFait / $hpInitial * 100)) : 0;
@endphp

@if($estDerniereHP)
<div class="rounded-4 p-3 mb-4 d-flex align-items-start gap-3"
     style="background:#F2E9D8;border:2px solid #D8C898;">
    <span style="font-size:24px;">🏁</span>
    <div>
        <div style="font-weight:700;color:#6B4E0A;font-size:14px;">Dernière séance HP de ce module</div>
        <div style="font-size:13px;color:#6B4E0A;">
            Cette séance clôture le quota HP de <strong>{{ $seance->matiere?->nom }}</strong> pour ce professeur.
        </div>
    </div>
</div>
@endif

<div class="bg-white rounded-4 border p-4 mb-4">
    <h6 class="fw-bold mb-3" style="color:var(--fonce);font-size:14px;">
        <i class="bi bi-clock-history me-2"></i>Suivi HP — {{ $seance->professeur?->name }} · {{ $seance->matiere?->code }}
    </h6>
    <div class="row g-4">
        {{-- Heures HP professeur --}}
        <div class="col-md-8">
            <div class="p-3 rounded-3" style="background:#EBF0F5;border:1px solid #B5C5D8;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:12px;font-weight:700;color:#2D4A6B;text-transform:uppercase;letter-spacing:.04em;">Heures Professeur (HP)</span>
                    <span class="badge rounded-pill" style="background:#2D4A6B;font-size:11px;">{{ $hpInitial }}h prévues</span>
                </div>
                <div class="d-flex gap-4 mb-3">
                    <div>
                        <div style="font-size:22px;font-weight:800;color:#2D4A6B;">{{ $profHpFait }}h</div>
                        <div style="font-size:11px;color:#5A6E8A;">Effectuées</div>
                    </div>
                    <div>
                        <div style="font-size:22px;font-weight:800;color:{{ $profHpRestant > 0 ? '#6B2737' : '#4D7A4A' }};">{{ $profHpRestant }}h</div>
                        <div style="font-size:11px;color:#888;">Restantes</div>
                    </div>
                    <div>
                        <div style="font-size:22px;font-weight:800;color:var(--fonce);">{{ $profNbSeances }}</div>
                        <div style="font-size:11px;color:#888;">Séances HP</div>
                    </div>
                </div>
                <div class="progress rounded-pill" style="height:8px;">
                    <div class="progress-bar" role="progressbar"
                         style="width:{{ $hpPct }}%;background:{{ $hpPct >= 100 ? '#4D7A4A' : '#5A6E8A' }};">
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-1" style="font-size:10px;color:#888;">
                    <span>0h</span><span>{{ $hpPct }}% complété</span><span>{{ $hpInitial }}h</span>
                </div>
            </div>
        </div>

        {{-- TPE restant pour le centre (vases communicants) --}}
        @if($quota && $tpeInitial > 0)
        <div class="col-md-4">
            <div class="p-3 rounded-3 h-100" style="background:#EDE8F3;border:1px solid #D8CEDD;">
                <div style="font-size:11px;font-weight:700;color:#3F2A52;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;">TPE disponibles (centre)</div>
                <div style="font-size:28px;font-weight:800;color:{{ $quota->tpe_dynamique > 0 ? '#3F2A52' : '#6B2737' }};">
                    {{ $quota->tpe_dynamique }}h
                </div>
                <div style="font-size:11px;color:#7A6585;margin-top:4px;">
                    sur {{ $tpeInitial }}h prévues<br>
                    @if($profHpRestant > 0)
                    <span style="color:#6B2737;">HP non complets — TPE bloqués</span>
                    @else
                    <span style="color:#4D7A4A;">HP complets — TPE débloqués</span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Historique HP du professeur pour cette matière --}}
    @if($profSeancesTerminees->count() > 0)
    <div class="mt-3 pt-3" style="border-top:1px solid #f0f0f0;">
        <div style="font-size:12px;font-weight:600;color:#888;margin-bottom:8px;">
            Historique HP professeur ({{ $profSeancesTerminees->count() }} séance(s))
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($profSeancesTerminees->sortByDesc('debut') as $s)
            <span class="badge rounded-pill px-3 py-2"
                  style="font-size:11px;background:{{ $s->id === $seance->id ? 'var(--marron)' : '#E2EAF4' }};color:{{ $s->id === $seance->id ? '#fff' : '#2D4A6B' }};">
                {{ $s->debut->format('d/m') }} · {{ round($s->duree_heures, 1) }}h
                @if(!$s->heure_scan_professeur) <em>(absent)</em> @endif
                @if($s->id === $seance->id) ← actuelle @endif
            </span>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif {{-- fin type=HP --}}

{{-- KPI présence --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card"><span class="stat-icon">👥</span>
            <div><div class="stat-value">{{ $totalInscrits }}</div><div class="stat-label">Inscrits</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#EBF0EA;"><span class="stat-icon">✅</span>
            <div><div class="stat-value">{{ $nbPresents }}</div><div class="stat-label">Présents</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#F2EAEB;"><span class="stat-icon">❌</span>
            <div><div class="stat-value">{{ $nbAbsents }}</div><div class="stat-label">Absents</div></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background:#F3EAE7;"><span class="stat-icon">⚠️</span>
            <div><div class="stat-value">{{ $nbInsuffisants }}</div><div class="stat-label">Présence insuffisante</div></div>
        </div>
    </div>
</div>

{{-- ── PANNEAU VALIDATION PROFESSEUR ─────────────────────────────────────── --}}
@php
    $authUser       = Auth::user();
    $estProfConcerne = $authUser->estProfesseur() && $authUser->id === $seance->professeur_id;
    $peutValider    = ($estProfConcerne || $authUser->estAdmin()) && $seance->statut === 'terminee';
    $dejaClose      = (bool) $seance->cloture_validee_at;
@endphp

@if($peutValider)
<div class="mb-4">

    @if($dejaClose)
    {{-- ── État : séance déjà clôturée ── --}}
    <div class="rounded-4 p-4 d-flex align-items-start gap-3"
         style="background:#EDF2EC;border:2px solid #A0BAA0;">
        <div class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-circle"
             style="width:44px;height:44px;background:#D4E3D3;font-size:20px;">✅</div>
        <div>
            <div style="font-weight:700;color:#2A4528;font-size:15px;">Clôture validée</div>
            <div style="font-size:13px;color:#374151;margin-top:2px;">
                Le {{ $seance->cloture_validee_at->format('d/m/Y à H:i') }}
                &middot; <strong>{{ $seance->nb_presents_valide }}</strong> présent(s) confirmé(s) par le professeur
            </div>
        </div>
    </div>

    @else
    {{-- ── État : validation en attente ── --}}
    <div class="bg-white rounded-4 border p-4" style="border-color:#e5e7eb;">
        <h6 class="fw-bold mb-3" style="color:var(--fonce);font-size:14px;">
            <i class="bi bi-pencil-square me-2"></i>Validation requise &mdash; Action du professeur
        </h6>

        {{-- Récapitulatif automatique --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 rounded-3 text-center" style="background:#EDF2EC;border:1px solid #B8CEB8;">
                    <div style="font-size:30px;font-weight:800;color:#3A5C38;">{{ $nbPresents }}</div>
                    <div style="font-size:11px;color:#2A4528;text-transform:uppercase;letter-spacing:.04em;">Présents RFID</div>
                    <div style="font-size:10px;color:#9ca3af;margin-top:2px;">comptage automatique</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 text-center" style="background:#EBF0F5;border:1px solid #B5C5D8;">
                    @php $dm = $dureeEffectiveMinutes; @endphp
                    <div style="font-size:30px;font-weight:800;color:#2D4A6B;">
                        {{ floor($dm / 60) }}h{{ str_pad($dm % 60, 2, '0', STR_PAD_LEFT) }}
                    </div>
                    <div style="font-size:11px;color:#2D4A6B;text-transform:uppercase;letter-spacing:.04em;">Durée calculée</div>
                    <div style="font-size:10px;color:#9ca3af;margin-top:2px;">
                        @if($seance->heure_scan_professeur)
                            scan {{ $seance->heure_scan_professeur->format('H:i') }} → {{ $seance->fin->format('H:i') }}
                        @else
                            <span style="color:#6B2737;">aucun scan professeur</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 text-center" style="background:#EDE8F3;border:1px solid #D8CEDD;">
                    <div style="font-size:30px;font-weight:800;color:#3F2A52;">{{ $totalInscrits }}</div>
                    <div style="font-size:11px;color:#3F2A52;text-transform:uppercase;letter-spacing:.04em;">Inscrits</div>
                    <div style="font-size:10px;color:#9ca3af;margin-top:2px;">total des groupes</div>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-3" style="background:#EDF2EC;border:2px solid #A0BAA0;">
                    <div class="fw-bold mb-1" style="color:#3A5C38;font-size:14px;">
                        <i class="bi bi-check2-circle me-2"></i>Valider la clôture
                    </div>
                    <p style="font-size:12px;color:#2A4528;margin-bottom:16px;">
                        Saisissez le nombre d'étudiants réellement présents.
                        Le comptage RFID ({{ $nbPresents }}) est pré-rempli — modifiez-le si nécessaire.
                    </p>
                    @error('cloture')
                    <div class="alert alert-danger py-2 mb-2" style="font-size:12px;">{{ $message }}</div>
                    @enderror
                    <form action="{{ route('seances.cloturer', $seance->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:12px;color:#3A5C38;">
                                Nombre de présents <span style="color:red;">*</span>
                            </label>
                            <input type="number" name="nb_presents"
                                   class="form-control form-control-lg text-center fw-bold"
                                   value="{{ old('nb_presents', $nbPresents) }}"
                                   min="0" max="{{ $totalInscrits }}" required
                                   style="border-color:#A0BAA0;font-size:22px;">
                        </div>
                        <button type="submit" class="btn w-100 fw-bold py-2"
                                style="background:#3A5C38;color:#fff;border-radius:10px;font-size:13px;">
                            <i class="bi bi-check-lg me-2"></i>Valider et clôturer
                        </button>
                    </form>
                </div>
    </div>
    @endif

</div>
@endif

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
        @forelse($seance->presences->sortBy(fn($p) => [$p->statut !== 'present', $p->inscription?->etudiant?->nom]) as $i => $presence)
        @php
            $etu = $presence->inscription?->etudiant;
            $dureeSeance = $seance->debut->diffInMinutes($seance->fin);
            $dureeSorties = $presence->sortiesTemporaires->sum('duree_minutes');
            $dureeEff = ($presence->heure_entree && $presence->heure_sortie_definitive)
                ? $presence->heure_entree->diffInMinutes($presence->heure_sortie_definitive) - $dureeSorties
                : null;
            $statutColors = [
                'present'                     => ['#EBF0EA','#3A5C38'],
                'absent'                      => ['#F2EAEB','#6B2737'],
                'presence_insuffisante'       => ['#F3EAE7','#7A3D28'],
                'sortie_anticipee_toleree'    => ['#EBF0EA','#3A5C38'],
                'sortie_anticipee_non_toleree'=> ['#F3EAE7','#6B2737'],
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
                <span style="font-size:12px;font-weight:600;color:#3A5C38;">{{ $presence->heure_entree->format('H:i') }}</span>
                @else <span style="color:#aaa;font-size:12px;">—</span> @endif
            </td>
            <td>
                @if($presence->heure_sortie_definitive)
                <span style="font-size:12px;font-weight:600;color:var(--fonce);">{{ $presence->heure_sortie_definitive->format('H:i') }}</span>
                @else <span style="color:#aaa;font-size:12px;">—</span> @endif
            </td>
            <td>
                @if($presence->sortiesTemporaires->count())
                <span class="badge rounded-pill px-2" style="background:#F3EAE7;color:#7A3D28;font-size:11px;">
                    {{ $presence->sortiesTemporaires->count() }}× ({{ $dureeSorties }} min)
                </span>
                @else <span style="color:#aaa;font-size:12px;">—</span> @endif
            </td>
            <td>
                @if($dureeEff !== null)
                <span style="font-size:12px;font-weight:600;color:{{ $dureeEff >= $dureeSeance * 0.5 ? 'var(--fonce)' : '#6B2737' }}">
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
