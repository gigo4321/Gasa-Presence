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
    <button onclick="window.print()">🖨️ Imprimer / Sauvegarder en PDF</button>
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
        <div class="info-item">
            <label>Scan d'entrée</label>
            <span style="color:{{ $seance->heure_scan_professeur?'#2e7d32':'#c62828' }}">
                {{ $seance->heure_scan_professeur?->format('H:i') ?? 'Non badgé' }}
            </span>
        </div>
        @if($seance->heure_debut_pause)
        <div class="info-item">
            <label>Pause déclarée</label>
            <span>{{ \Carbon\Carbon::parse($seance->heure_debut_pause)->format('H:i') }}
                – {{ \Carbon\Carbon::parse($seance->heure_fin_pause)->format('H:i') }}</span>
        </div>
        @endif
    </div>
</div>

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
    @foreach($seance->presences as $i => $presence)
    @php
        $etu = $presence->etudiant;
        $dureeSeance  = $seance->debut->diffInMinutes($seance->fin);
        $dureeSorties = $presence->sortiesTemporaires->sum('duree_minutes');
        $dureeEff = ($presence->heure_entree && $presence->heure_sortie_definitive)
            ? $presence->heure_entree->diffInMinutes($presence->heure_sortie_definitive) - $dureeSorties
            : null;
        $sc = match($presence->statut) {
            'present'                     => 's-present',
            'absent'                      => 's-absent',
            'presence_insuffisante'       => 's-insuffisant',
            default                       => 's-absent',
        };
        $statutLabel = match($presence->statut) {
            'present'                     => 'Présent',
            'absent'                      => 'Absent',
            'presence_insuffisante'       => 'Insuffisant',
            'sortie_anticipee_toleree'    => 'Sortie OK',
            'sortie_anticipee_non_toleree'=> 'Sortie NOK',
            default                       => $presence->statut,
        };
    @endphp
    <tr>
        <td>{{ $i + 1 }}</td>
        <td><code>{{ $etu?->matricule }}</code></td>
        <td><strong>{{ $etu?->nom }} {{ $etu?->prenom }}</strong></td>
        <td>{{ $etu?->option?->nom }}<br><small>{{ $etu?->option?->niveau?->libelle ?? '' }}</small></td>
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
        <div class="signature-box">Signature du Professeur</div>
        <div style="margin-top:4px;text-align:center;font-size:10px;">{{ $seance->professeur?->name }}</div>
    </div>
    <div>
        <div class="signature-box">Signature du Responsable</div>
    </div>
    <div style="text-align:right;font-size:10px;color:#aaa;max-width:200px;">
        UATM GASA-FORMATION<br>
        Document généré automatiquement<br>
        {{ now()->locale('fr')->isoFormat('D MMMM YYYY à H:mm') }}
    </div>
</div>

</body>
</html>
