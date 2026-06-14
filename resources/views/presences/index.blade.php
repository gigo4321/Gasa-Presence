@extends('layouts.app')
@section('titre', 'Présences' . ($centre ? ' — ' . $centre->nom : ' — Tous centres'))

@section('content')

{{-- FILTRES --}}
<div class="bg-white rounded-4 border p-4 mb-4">
    <h6 class="fw-bold mb-3" style="color:var(--fonce)">Filtres de recherche</h6>
    <form method="GET" class="row g-3">
        @if(auth()->user()->estAdmin())
        <div class="col-md-3">
            <label class="form-label fw-semibold" style="font-size:13px;">Centre</label>
            <select name="centre_id" class="form-select rounded-3" onchange="this.form.submit()">
                <option value="">Tous les centres</option>
                @foreach($centres as $c)
                <option value="{{ $c->id }}" {{ request('centre_id')==$c->id?'selected':'' }}>{{ $c->nom }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="col-md-3">
            <label class="form-label fw-semibold" style="font-size:13px;">Filière</label>
            <select name="filiere_id" class="form-select rounded-3" onchange="this.form.submit()">
                <option value="">Toutes les filières</option>
                @foreach($filieres as $f)
                <option value="{{ $f->id }}" {{ request('filiere_id')==$f->id?'selected':'' }}>{{ $f->nom }}</option>
                @endforeach
            </select>
        </div>

        @if($matieres->count())
        <div class="col-md-3">
            <label class="form-label fw-semibold" style="font-size:13px;">Matière</label>
            <select name="matiere_id" class="form-select rounded-3">
                <option value="">Toutes</option>
                @foreach($matieres as $m)
                <option value="{{ $m->id }}" {{ request('matiere_id')==$m->id?'selected':'' }}>{{ $m->nom }}</option>
                @endforeach
            </select>
        </div>
        @endif

        @if($anneesScolaires->count())
        <div class="col-md-3">
            <label class="form-label fw-semibold" style="font-size:13px;">Année scolaire</label>
            <select name="annee_id" class="form-select rounded-3">
                <option value="">Toutes les années</option>
                @foreach($anneesScolaires as $a)
                <option value="{{ $a->id }}" {{ request('annee_id')==$a->id?'selected':'' }}>
                    {{ $a->libelle }}{{ $a->active?' (active)':'' }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="col-md-2">
            <label class="form-label fw-semibold" style="font-size:13px;">Du</label>
            <input type="date" name="date_debut" value="{{ request('date_debut') }}"
                   class="form-control rounded-3">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold" style="font-size:13px;">Au</label>
            <input type="date" name="date_fin" value="{{ request('date_fin') }}"
                   class="form-control rounded-3">
        </div>

        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn text-white rounded-3 flex-1" style="background:var(--fonce);">
                <i class="bi bi-search me-1"></i> Filtrer
            </button>
            <a href="{{ request()->url() }}" class="btn rounded-3" style="border:1px solid rgba(0,0,0,.15);">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>

        @if(auth()->user()->estAdmin())
        <div class="col-12 d-flex justify-content-end">
            <a href="{{ route('presences.annees') }}" class="btn btn-sm rounded-3"
               style="font-size:12px;border:1px solid var(--marron);color:var(--marron);">
                <i class="bi bi-calendar-range me-1"></i> Gérer les années scolaires
            </a>
        </div>
        @endif
    </form>
</div>

{{-- LISTE DES SÉANCES --}}
<div class="bg-white rounded-4 border overflow-hidden">
    <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between"
         style="background:var(--fonce);">
        <h6 class="mb-0 fw-bold text-white">Séances terminées</h6>
        <span style="font-size:12px;color:rgba(255,255,255,.6);">{{ $seances->total() }} séance(s)</span>
    </div>

    @forelse($seances as $seance)
    @php
        $niveauLibelle = $seance->matiere?->niveau?->libelle ?? '—';
        $optionNom     = $seance->matiere?->niveau?->filiereOption?->nom ?? '—';
        $filiereNom    = $seance->matiere?->niveau?->filiereOption?->filiere?->nom ?? '—';
        $nbPresents    = $seance->presences->where('statut','present')->count();
        $nbTotal       = $seance->presences->count();
        $taux          = $nbTotal > 0 ? round($nbPresents / $nbTotal * 100) : 0;
    @endphp
    <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-3"
         style="transition:background .15s;" onmouseover="this.style.background='var(--beige)'" onmouseout="this.style.background='#fff'">
        <div class="flex-1">
            {{-- Hiérarchie --}}
            <div style="font-size:11px;color:var(--marron);margin-bottom:4px;">
                {{ $filiereNom }}
                @if($optionNom !== '—') <span style="opacity:.5;">›</span> {{ $optionNom }} @endif
                @if($niveauLibelle !== '—') <span style="opacity:.5;">›</span> {{ $niveauLibelle }} @endif
            </div>
            {{-- Matière + type --}}
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge rounded-2 px-2"
                      style="font-size:10px;background:{{ $seance->type==='HP'?'#e3f2fd':'#f3e5f5' }};color:{{ $seance->type==='HP'?'#1565c0':'#6a1b9a' }};">
                    {{ $seance->type }}
                </span>
                <span style="font-weight:600;font-size:14px;color:var(--fonce);">
                    {{ $seance->matiere?->nom }}
                </span>
                @if($seance->is_inter_centre)
                <span class="badge rounded-pill px-2" style="font-size:10px;background:#fff3e0;color:#e65100;">🌐 Inter-centres</span>
                @endif
            </div>
            {{-- Infos --}}
            <div style="font-size:12px;color:#888;">
                <i class="bi bi-calendar3 me-1"></i>
                {{ \Carbon\Carbon::parse($seance->debut)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
                &nbsp;·&nbsp;
                <i class="bi bi-clock me-1"></i>
                {{ $seance->debut->format('H:i') }} – {{ $seance->fin->format('H:i') }}
                &nbsp;·&nbsp;
                <i class="bi bi-person me-1"></i>
                {{ $seance->professeur?->name }}
                &nbsp;·&nbsp;
                <i class="bi bi-door-open me-1"></i>
                {{ $seance->salle?->nom }} ({{ $seance->salle?->centre?->nom }})
            </div>
        </div>

        {{-- Taux de présence --}}
        <div class="text-center" style="min-width:80px;">
            <div style="font-size:22px;font-weight:700;color:{{ $taux>=75?'#2e7d32':($taux>=50?'#e65100':'#c62828') }}">
                {{ $taux }}%
            </div>
            <div style="font-size:11px;color:#aaa;">{{ $nbPresents }}/{{ $nbTotal }}</div>
            <div style="height:4px;background:#eee;border-radius:2px;margin-top:4px;width:80px;">
                <div style="height:100%;width:{{ $taux }}%;background:{{ $taux>=75?'#4caf50':($taux>=50?'#ff9800':'#f44336') }};border-radius:2px;"></div>
            </div>
        </div>

        {{-- Action --}}
        <a href="{{ route('presences.fiche', $seance->id) }}"
           class="btn btn-sm rounded-3 text-white"
           style="background:var(--fonce);font-size:12px;white-space:nowrap;">
            <i class="bi bi-file-text me-1"></i> Voir la fiche
        </a>
    </div>
    @empty
    <div class="p-5 text-center" style="color:#aaa;">
        <div style="font-size:40px;margin-bottom:12px;">📋</div>
        <p>Aucune séance terminée ne correspond aux filtres.</p>
    </div>
    @endforelse
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <span style="font-size:13px;color:var(--marron)">{{ $seances->total() }} séance(s)</span>
    {{ $seances->links() }}
</div>

@endsection
