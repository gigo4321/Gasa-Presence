@extends('layouts.app')
@section('titre','Matières — '.$centre->nom)

@push('styles')
<style>
.fil-header  { cursor:pointer; user-select:none; }
.fil-header:hover  { filter:brightness(.95); }
.opt-header  { cursor:pointer; user-select:none; transition:background .15s; }
.opt-header:hover  { background:#ece2d6 !important; }
.niv-header  { cursor:pointer; user-select:none; transition:background .15s; }
.niv-header:hover  { background:#f0ece8 !important; }
.chv { transition:transform .2s; display:inline-block; }
</style>
@endpush

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center">
    <p class="mb-0" style="font-size:13px;color:var(--marron);">
        Suivi HP/TPE pour <strong>{{ $centre->nom }}</strong>{{ $annee?' — '.$annee->libelle:'' }}.
    </p>
    @admin
    <a href="{{ route('filieres.index') }}" class="btn btn-sm rounded-3 text-white" style="background:var(--fonce);font-size:12px;">
        <i class="bi bi-pencil-square me-1"></i> Gérer le référentiel
    </a>
    @endadmin
</div>

@php
$hasMatieres = $filieres->contains(fn($f)=>$f->filiereOptions->contains(fn($o)=>$o->niveaux->contains(fn($n)=>$n->matieres->isNotEmpty())));
@endphp

@if(!$hasMatieres)
<div class="bg-white rounded-4 border p-5 text-center" style="color:#aaa;">
    <div style="font-size:48px;">📚</div>
    <p class="mt-3">Aucune matière dans le référentiel.</p>
    @admin<a href="{{ route('filieres.index') }}" class="btn text-white rounded-3 px-4 mt-2" style="background:var(--fonce);">Accéder au référentiel</a>@endadmin
</div>
@else

{{-- Boutons globaux replier/déplier --}}
<div class="d-flex gap-2 mb-3">
    <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid #ddd;" onclick="toutReplier()">
        <i class="bi bi-arrows-collapse"></i> Tout replier
    </button>
    <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid #ddd;" onclick="toutDeplier()">
        <i class="bi bi-arrows-expand"></i> Tout déplier
    </button>
</div>

@foreach($filieres as $filiere)
@php $hasMat=$filiere->filiereOptions->contains(fn($o)=>$o->niveaux->contains(fn($n)=>$n->matieres->isNotEmpty())); @endphp
@if($hasMat)
<div class="bg-white rounded-4 border mb-4 overflow-hidden">

    {{-- ── En-tête filière (cliquable) ────────────────────────────────── --}}
    <div class="fil-header px-4 py-3 d-flex align-items-center justify-content-between"
         style="background:var(--fonce);"
         onclick="toggleFil({{ $filiere->id }})">
        <div class="d-flex align-items-center gap-3">
            <span class="badge rounded-2 px-3 py-2" style="background:var(--marron);font-family:monospace;font-size:13px;">{{ $filiere->code }}</span>
            <span style="font-weight:700;font-size:15px;color:#fff;">{{ $filiere->nom }}</span>
        </div>
        <i class="bi bi-chevron-down chv text-white" id="chv_fil_{{ $filiere->id }}" style="font-size:13px;"></i>
    </div>

    {{-- ── Corps filière (collapsible) ─────────────────────────────────── --}}
    <div id="fil_body_{{ $filiere->id }}">
    @foreach($filiere->filiereOptions as $opt)
    @if($opt->niveaux->contains(fn($n)=>$n->matieres->isNotEmpty()))

        {{-- ── En-tête option (cliquable) ──────────────────────────────── --}}
        <div class="opt-header px-4 py-2 border-top d-flex align-items-center justify-content-between"
             style="background:#f4ede4;"
             onclick="toggleOpt({{ $opt->id }})">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-2 px-2 py-1" style="background:var(--marron);font-size:10px;font-family:monospace;">{{ $opt->code }}</span>
                <span style="font-weight:600;font-size:13px;color:var(--fonce);">{{ $opt->nom }}</span>
            </div>
            <i class="bi bi-chevron-down chv" id="chv_opt_{{ $opt->id }}" style="font-size:11px;color:var(--fonce);"></i>
        </div>

        {{-- ── Corps option (collapsible) ───────────────────────────────── --}}
        <div id="opt_body_{{ $opt->id }}">
        @foreach($opt->niveaux as $niveau)
        @if($niveau->matieres->isNotEmpty())

            {{-- ── En-tête niveau (cliquable) ──────────────────────────── --}}
            <div class="niv-header px-4 py-2 border-top d-flex align-items-center justify-content-between"
                 style="background:#faf7f4;"
                 onclick="toggleNiv({{ $niveau->id }})">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill px-3" style="background:var(--fonce);color:var(--beige);font-size:11px;">{{ $niveau->code }}</span>
                    <span style="font-size:13px;font-weight:600;color:var(--fonce);">{{ $niveau->libelle }}</span>
                </div>
                <i class="bi bi-chevron-down chv" id="chv_niv_{{ $niveau->id }}" style="font-size:11px;color:#aaa;"></i>
            </div>

            {{-- ── Table matières (collapsible) ─────────────────────────── --}}
            <div id="niv_body_{{ $niveau->id }}">
            <table class="table table-hover mb-0 border-top">
                <thead style="background:var(--beige);"><tr style="font-size:12px;color:var(--fonce);">
                    <th class="px-4 py-2">Code</th><th>Matière</th><th>S</th><th>HP Init.</th><th>HP Restant</th><th>TPE Init.</th><th>TPE Dyn.</th><th>MHT</th>
                </tr></thead>
                <tbody>
                @foreach($niveau->matieres->sortBy('semestre') as $m)
                @php
                    $quota   = $m->quotas->first();
                    $mht     = $m->hp_initial + $m->tpe_initial;
                    $pctHP   = ($quota && $m->hp_initial>0) ? round($quota->hp_restant/$m->hp_initial*100) : null;
                    $couleur = $pctHP===null?'#aaa':($pctHP>50?'#4caf50':($pctHP>20?'#ff9800':'#f44336'));
                @endphp
                <tr>
                    <td class="px-4 py-2"><code style="font-size:11px;background:#f0ebe4;padding:2px 8px;border-radius:4px;">{{ $m->code }}</code></td>
                    <td style="font-weight:600;font-size:13px;color:var(--fonce);">{{ $m->nom }}</td>
                    <td><span class="badge rounded-pill px-2" style="font-size:10px;background:{{ $m->semestre==1?'#e3f2fd':'#f3e5f5' }};color:{{ $m->semestre==1?'#1565c0':'#6a1b9a' }}">S{{ $m->semestre }}</span></td>
                    <td style="font-size:13px;"><strong>{{ $m->hp_initial }}</strong>h</td>
                    <td>@if($quota)<div class="d-flex align-items-center gap-2"><div style="width:60px;height:6px;background:#eee;border-radius:3px;overflow:hidden;"><div style="height:100%;width:{{ $pctHP }}%;background:{{ $couleur }};border-radius:3px;"></div></div><span style="font-size:13px;font-weight:600;color:{{ $couleur }}">{{ $quota->hp_restant }}h</span></div>@else<span style="font-size:12px;color:#aaa;">—</span>@endif</td>
                    <td style="font-size:13px;">{{ $m->tpe_initial }}h</td>
                    <td>@if($quota)<span style="{{ $quota->tpe_dynamique<$m->tpe_initial?'color:#e65100;font-weight:600;':'' }}">{{ $quota->tpe_dynamique }}h</span>@else<span style="color:#aaa;">—</span>@endif</td>
                    <td style="font-size:13px;font-weight:700;">{{ $mht }}h</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>{{-- /niv_body --}}

        @endif
        @endforeach
        </div>{{-- /opt_body --}}

    @endif
    @endforeach
    </div>{{-- /fil_body --}}

</div>
@endif
@endforeach
@endif
@endsection

@push('scripts')
<script>
function toggleFil(id) { _toggle('fil_body_' + id, 'chv_fil_' + id); }
function toggleOpt(id)  { _toggle('opt_body_' + id, 'chv_opt_' + id); }
function toggleNiv(id)  { _toggle('niv_body_' + id, 'chv_niv_' + id); }

function _toggle(bodyId, chvId) {
    const body = document.getElementById(bodyId);
    const chv  = document.getElementById(chvId);
    if (!body) return;
    const open = body.style.display !== 'none';
    body.style.display = open ? 'none' : '';
    if (chv) chv.style.transform = open ? 'rotate(-90deg)' : '';
}

function toutReplier() {
    document.querySelectorAll('[id^="fil_body_"],[id^="opt_body_"],[id^="niv_body_"]').forEach(el => {
        el.style.display = 'none';
    });
    document.querySelectorAll('.chv').forEach(c => c.style.transform = 'rotate(-90deg)');
}

function toutDeplier() {
    document.querySelectorAll('[id^="fil_body_"],[id^="opt_body_"],[id^="niv_body_"]').forEach(el => {
        el.style.display = '';
    });
    document.querySelectorAll('.chv').forEach(c => c.style.transform = '');
}
</script>
@endpush
