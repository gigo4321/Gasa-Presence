<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de Présence — {{ $seance->matiere?->nom }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a1a; background:#fff; }
        @media print {
            body { font-size:11px; }
            .no-print { display:none !important; }
            @page { margin:15mm; size:A4; }
        }
        .no-print { text-align:center; padding:12px; background:#f5f5f5; }
        .no-print button { padding:8px 24px; background:#3E2723; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:14px; }
        .header { background:#3E2723; color:#fff; padding:16px 20px; margin-bottom:16px; }
        .header h1 { font-size:18px; margin-bottom:4px; }
        .header .sub { font-size:11px; opacity:.7; }
        .badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:10px; font-weight:700; }
        .badge-hp  { background:#e3f2fd; color:#1565c0; }
        .badge-tpe { background:#f3e5f5; color:#6a1b9a; }
        .section { margin-bottom:16px; padding:12px 16px; border:1px solid #ddd; border-radius:6px; }
        .section-title { font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:#8D6E63; font-weight:700; margin-bottom:8px; }
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .info-item label { font-size:10px; color:#888; display:block; }
        .info-item span  { font-size:13px; font-weight:600; color:#1a1a1a; }
        .kpi-row { display:flex; gap:12px; margin-bottom:16px; }
        .kpi { flex:1; border:1px solid #ddd; border-radius:6px; padding:10px; text-align:center; }
        .kpi .val { font-size:24px; font-weight:700; color:#3E2723; }
        .kpi .lbl { font-size:10px; color:#888; }
        table { width:100%; border-collapse:collapse; font-size:11px; }
        thead th { background:#3E2723; color:#fff; padding:8px 6px; text-align:left; }
        tbody td { padding:7px 6px; border-bottom:1px solid #eee; }
        tbody tr:nth-child(even) { background:#faf7f4; }
        .statut { display:inline-block; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:600; }
        .s-present     { background:#e8f5e9; color:#2e7d32; }
        .s-absent      { background:#ffebee; color:#c62828; }
        .s-insuffisant { background:#fff3e0; color:#e65100; }
        .footer { margin-top:24px; padding-top:12px; border-top:1px solid #ddd; display:flex; justify-content:space-between; font-size:10px; color:#888; }
        .signature-box { width:180px; border:1px solid #ccc; border-radius:4px; height:60px; text-align:center; padding-top:40px; font-size:10px; color:#aaa; }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">Imprimer / Sauvegarder en PDF</button>
</div>

{{-- EN-TÊTE --}}
<div class="header">
    <h1>Fiche de Présence — {{ $seance->matiere?->nom }}</h1>
    <div class="sub">
        @php
            $f = $seance->matiere?->niveau?->filiereOption?->filiere?->nom;
            $o = $seance->matiere?->niveau?->filiereOption?->nom;
            $n = $seance->matiere?->niveau?->libelle;
        @endphp
        {{ $f }} › {{ $o }} › {{ $n }}
        &nbsp;·&nbsp;
        Généré le {{ now()->locale('fr')->isoFormat('D MMMM YYYY à H:mm') }}
    </div>
</div>

{{-- INFOS SÉANCE --}}
<div class="section">
    <div class="section-title">Informations de la séance</div>
    <div class="info-grid">
        <div class="info-item">
            <label>Date</label>
            <span>{{ \Carbon\Carbon::parse($seance->debut)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</span>
        </div>
        <div class="info-item">
            <label>Horaire</label>
            <span>{{ $seance->debut->format('H:i') }} – {{ $seance->fin->format('H:i') }}
                ({{ round($seance->duree_heures, 1) }}h)
            </span>
        </div>
        <div class="info-item">
            <label>Salle</label>
            <span>{{ $seance->salle?->nom }} — {{ $seance->salle?->centre?->nom }}</span>
        </div>
        <div class="info-item">
            <label>Type</label>
            <span class="badge {{ $seance->type==='HP'?'badge-hp':'badge-tpe' }}">{{ $seance->type }}</span>
        </div>
    </div>
</div>

{{-- INFOS PROFESSEUR --}}
<div class="section">
    <div class="section-title">Professeur</div>
    <div class="info-grid">
        <div class="info-item">
            <label>Nom complet</label>
            <span>{{ $seance->professeur?->name }}</span>
        </div>
        @if($seance->professeur?->grade)
        <div class="info-item">
            <label>Grade / Titre</label>
            <span style="font-style:italic;color:#8D6E63;">{{ $seance->professeur->grade }}</span>
        </div>
        @endif
        <div class="info-item">
            <label>Scan d'entrée</label>
            <span style="color:{{ $seance->heure_scan_professeur?'#2e7d32':'#c62828' }}">
                {{ $seance->heure_scan_professeur?->format('H:i') ?? 'Non badgé' }}
            </span>
        </div>
        <div class="info-item">
            <label>Fin / Scan sortie</label>
            @if($seance->heure_scan_sortie_professeur)
            <span style="color:#1a1a1a;">{{ $seance->heure_scan_sortie_professeur->format('H:i') }}</span>
            @elseif($seance->heure_scan_professeur)
            <span style="color:#1565c0;">{{ $seance->fin->format('H:i') }}</span>
            <span style="font-size:9px;color:#aaa;"> (fin planifiée)</span>
            @else
            <span style="color:#aaa;">—</span>
            @endif
        </div>
        @if(($seance->durees_pauses_minutes ?? 0) > 0)
        <div class="info-item">
            <label>Pause(s) totale</label>
            <span>{{ $seance->durees_pauses_minutes }} min</span>
        </div>
        @endif
        @if($seance->heure_scan_professeur)
        @php
            $pdfEfMin = $seance->calculerDureeEffective();
            $pdfEfH   = intdiv($pdfEfMin, 60);
            $pdfEfRem = $pdfEfMin % 60;
        @endphp
        <div class="info-item">
            <label>Durée effective</label>
            <span style="color:#2e7d32;">
                {{ $pdfEfH }}h{{ $pdfEfRem > 0 ? str_pad($pdfEfRem,2,'0',STR_PAD_LEFT).'min' : '' }}
            </span>
        </div>
        @endif
    </div>
</div>

{{-- CLÔTURE & CONTESTATION --}}
@if($seance->cloture_validee_at)
@php
    $pdfEstValideAdmin = $validateur && (int)$validateur->id !== (int)$seance->professeur_id;
    $pdfDmEf = $seance->calculerDureeEffective();
@endphp
<div class="section" style="border-color:#A0BAA0;background:#EDF2EC;">
    <div class="section-title" style="color:#2A4528;">Clôture validée</div>
    <div class="info-grid">
        <div class="info-item">
            <label>Professeur du cours</label>
            <span>{{ $seance->professeur?->name }}</span>
        </div>
        <div class="info-item">
            <label>Validé le</label>
            <span>{{ $seance->cloture_validee_at->format('d/m/Y à H:i') }}</span>
        </div>
        <div class="info-item">
            <label>Présents confirmés</label>
            <span style="font-size:15px;color:#2A4528;"><strong>{{ $seance->nb_presents_valide }}</strong></span>
        </div>
        <div class="info-item">
            <label>Durée effective</label>
            <span style="color:#2A4528;">
                {{ floor($pdfDmEf/60) }}h{{ str_pad($pdfDmEf%60,2,'0',STR_PAD_LEFT) }}
            </span>
        </div>
    </div>
    @if($pdfEstValideAdmin)
    <div style="margin-top:8px;font-size:11px;color:#78350F;background:#FEF9C3;padding:4px 8px;border-radius:4px;">
        Validation effectuée par l'administrateur {{ $validateur->name }} à la place du professeur.
    </div>
    @endif

    @if($contestation)
    @php
        $pdfDmCalc    = $contestation->duree_calculee_minutes;
        $pdfDmContest = $contestation->duree_contestee_minutes;
        $pdfCcBg = match($contestation->statut) { 'acceptee' => '#e8f5e9', 'refusee' => '#ffebee', default => '#fffde7' };
        $pdfCcCl = match($contestation->statut) { 'acceptee' => '#2e7d32', 'refusee' => '#c62828', default => '#e65100' };
        $pdfCcLabel = match($contestation->statut) { 'acceptee' => 'Acceptée', 'refusee' => 'Refusée', default => 'En attente' };
    @endphp
    <div style="margin-top:10px;padding:8px 10px;border-radius:4px;background:{{ $pdfCcBg }};border:1px solid {{ $pdfCcCl }};">
        <div style="font-weight:700;font-size:12px;color:{{ $pdfCcCl }};margin-bottom:5px;">
            Contestation horaire du professeur
            <span style="font-weight:400;font-size:10px;padding:1px 6px;background:{{ $pdfCcCl }};color:#fff;border-radius:10px;margin-left:6px;">{{ $pdfCcLabel }}</span>
        </div>
        <div style="font-size:11px;color:{{ $pdfCcCl }};margin-bottom:4px;">
            Durée système : <strong>{{ floor($pdfDmCalc/60) }}h{{ str_pad($pdfDmCalc%60,2,'0',STR_PAD_LEFT) }}</strong>
            &nbsp;→&nbsp;
            Durée réclamée : <strong>{{ floor($pdfDmContest/60) }}h{{ str_pad($pdfDmContest%60,2,'0',STR_PAD_LEFT) }}</strong>
        </div>
        <div style="font-size:11px;color:{{ $pdfCcCl }};font-style:italic;">
            « {{ $contestation->motif }} »
        </div>
    </div>
    @endif
</div>
@endif

{{-- OPTIONS --}}
<div class="section">
    <div class="section-title">Options & Niveaux concernés</div>
    @foreach($seance->options as $opt)
    <div style="margin-bottom:4px;">
        <strong>{{ $opt->nom }}</strong>
        <span style="color:#888;font-size:11px;">— {{ $opt->filiere?->nom }} · {{ $opt->niveau?->libelle ?? $opt->niveau_libelle }} · {{ $opt->centre?->nom }}</span>
    </div>
    @endforeach
</div>

{{-- NOTIFICATION VASES COMMUNICANTS (PDF) --}}
@if($seance->type === 'HP' && !$seance->heure_scan_professeur && $seance->statut === 'terminee' && $nbPresents > 0)
@php
    $pdfHeuresAbsence = (int) ceil($seance->duree_heures);
    $pdfTpeAvant      = $quota ? $quota->tpe_dynamique + $pdfHeuresAbsence : null;
@endphp
<div style="margin-bottom:16px;padding:12px 16px;border:2px solid #F59E0B;border-radius:6px;background:#FFFBEB;">
    <div style="font-weight:700;color:#78350F;font-size:13px;margin-bottom:6px;">
        Professeur absent — Vases communicants appliqués
    </div>
    <div style="font-size:11px;color:#92400E;margin-bottom:10px;">
        {{ $seance->professeur?->name }} n'a pas badgé. Les <strong>{{ $pdfHeuresAbsence }}h HP</strong>
        sont maintenues au quota et une séance de rattrapage a été planifiée.
    </div>
    <div style="display:flex;gap:10px;">
        <div style="flex:1;border:1px solid #FDE68A;border-radius:4px;padding:8px;text-align:center;background:#FEF3C7;">
            <div style="font-size:9px;color:#78350F;text-transform:uppercase;margin-bottom:3px;">Étudiants présents</div>
            <div style="font-size:22px;font-weight:700;color:#92400E;">{{ $nbPresents }}</div>
            <div style="font-size:10px;color:#A16207;">sur {{ $totalInscrits }} inscrit(s)</div>
        </div>
        <div style="flex:1;border:1px solid #B5C5D8;border-radius:4px;padding:8px;text-align:center;background:#EBF0F5;">
            <div style="font-size:9px;color:#2D4A6B;text-transform:uppercase;margin-bottom:3px;">HP restantes</div>
            <div style="font-size:22px;font-weight:700;color:#2D4A6B;">{{ $profHpRestant }}h</div>
            <div style="font-size:10px;color:#5A6E8A;">inchangées · rattrapage requis</div>
        </div>
        @if($quota && $pdfTpeAvant !== null)
        <div style="flex:1;border:1px solid #FECACA;border-radius:4px;padding:8px;text-align:center;background:#FEE2E2;">
            <div style="font-size:9px;color:#991B1B;text-transform:uppercase;margin-bottom:3px;">TPE déduit (−{{ $pdfHeuresAbsence }}h)</div>
            <div style="font-size:16px;font-weight:700;color:#991B1B;">
                {{ $pdfTpeAvant }}h → {{ $quota->tpe_dynamique }}h
            </div>
            <div style="font-size:10px;color:#B91C1C;">sur {{ $tpeInitial }}h prévues</div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- KPI --}}
<div class="kpi-row">
    <div class="kpi"><div class="val">{{ $totalInscrits }}</div><div class="lbl">Inscrits</div></div>
    <div class="kpi" style="border-color:#4caf50;"><div class="val" style="color:#2e7d32;">{{ $nbPresents }}</div><div class="lbl">Présents</div></div>
    <div class="kpi" style="border-color:#f44336;"><div class="val" style="color:#c62828;">{{ $nbAbsents }}</div><div class="lbl">Absents</div></div>
    <div class="kpi" style="border-color:#ff9800;"><div class="val" style="color:#e65100;">{{ $nbInsuffisants }}</div><div class="lbl">Insuffisants</div></div>
    <div class="kpi">
        @php $taux = $totalInscrits > 0 ? round($nbPresents/$totalInscrits*100) : 0; @endphp
        <div class="val" style="color:{{ $taux>=75?'#2e7d32':($taux>=50?'#e65100':'#c62828') }}">{{ $taux }}%</div>
        <div class="lbl">Taux de présence</div>
    </div>
</div>

{{-- LISTE ÉTUDIANTS --}}
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Matricule</th>
            <th>Nom & Prénom</th>
            <th>Option / Niveau</th>
            <th>Entrée</th>
            <th>Sortie</th>
            <th>Durée eff.</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
    @foreach($seance->presences->sortBy(fn($p) => [$p->statut !== 'present', $p->inscription?->etudiant?->nom]) as $i => $presence)
    @php
        $etu          = $presence->inscription?->etudiant;
        $opt          = $presence->inscription?->option;
        $dureeSeance  = $seance->debut->diffInMinutes($seance->fin);
        $dureeSorties = $presence->sortiesTemporaires->sum('duree_minutes');
        $dureeEff     = ($presence->heure_entree && $presence->heure_sortie_definitive)
            ? $presence->heure_entree->diffInMinutes($presence->heure_sortie_definitive) - $dureeSorties
            : null;
        $sc = match($presence->statut) {
            'present'                      => 's-present',
            'absence_insuffisante',
            'presence_insuffisante'        => 's-insuffisant',
            default                        => 's-absent',
        };
        $statutLabel = match($presence->statut) {
            'present'                      => 'Présent',
            'absent'                       => 'Absent',
            'presence_insuffisante'        => 'Insuffisant',
            'sortie_anticipee_toleree'     => 'Sortie OK',
            'sortie_anticipee_non_toleree' => 'Sortie NOK',
            default                        => $presence->statut,
        };
    @endphp
    <tr>
        <td>{{ $i + 1 }}</td>
        <td><code>{{ $etu?->matricule ?? '—' }}</code></td>
        <td><strong>{{ $etu ? strtoupper($etu->nom).' '.$etu->prenom : '—' }}</strong></td>
        <td>{{ $opt?->nom }}<br><small>{{ $opt?->niveau?->libelle ?? '' }}</small></td>
        <td>{{ $presence->heure_entree?->format('H:i') ?? '—' }}</td>
        <td>{{ $presence->heure_sortie_definitive?->format('H:i') ?? '—' }}</td>
        <td>{{ $dureeEff !== null ? floor($dureeEff/60).'h'.str_pad($dureeEff%60,2,'0',STR_PAD_LEFT) : '—' }}</td>
        <td><span class="statut {{ $sc }}">{{ $statutLabel }}</span></td>
    </tr>
    @endforeach
    </tbody>
</table>

{{-- SIGNATURES --}}
<div class="footer">
    <div>
        @if($seance->cloture_validee_at)
        <div class="signature-box" style="background:#f9fff9;border-color:#4caf50;padding-top:8px;">
            <div style="font-weight:700;font-size:11px;color:#2e7d32;">Validé le {{ $seance->cloture_validee_at->format('d/m/Y') }}</div>
            <div style="font-size:10px;color:#2e7d32;">à {{ $seance->cloture_validee_at->format('H:i') }}</div>
        </div>
        @else
        <div class="signature-box">Signature du Professeur</div>
        @endif
        <div style="margin-top:4px;text-align:center;font-size:10px;">
            {{ $seance->professeur?->name }}
            @if($seance->professeur?->grade)
            <br><em style="color:#8D6E63;">{{ $seance->professeur->grade }}</em>
            @endif
        </div>
    </div>
    <div>
        <div class="signature-box">Signature du Responsable</div>
        <div style="margin-top:4px;text-align:center;font-size:10px;">{{ $seance->salle?->centre?->nom }}</div>
    </div>
    <div style="text-align:right;font-size:10px;color:#aaa;max-width:200px;">
        UATM GASA-FORMATION<br>
        Document généré automatiquement<br>
        {{ now()->locale('fr')->isoFormat('D MMMM YYYY à H:mm') }}
    </div>
</div>

</body>
</html>
