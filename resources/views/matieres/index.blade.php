@extends('layouts.app')
@section('titre', 'Matières — ' . $centre->nom)

@section('content')

<div class="mb-4 d-flex justify-content-between align-items-center">
    <p class="mb-0" style="font-size:13px;color:var(--marron);">
        Suivi des volumes HP/TPE pour <strong>{{ $centre->nom }}</strong>.
        Les matières sont globales — les quotas ci-dessous sont propres à ce centre.
    </p>
    @admin
    <a href="{{ route('filieres.index') }}" class="btn btn-sm rounded-3 text-white" style="background:var(--fonce);font-size:12px;">
        <i class="bi bi-pencil-square me-1"></i> Gérer le référentiel
    </a>
    @endadmin
</div>

@forelse($filieres as $filiere)
@if($filiere->matieres->isNotEmpty())
<div class="bg-white rounded-4 border mb-4 overflow-hidden">
    {{-- En-tête filière --}}
    <div class="px-4 py-3 d-flex align-items-center gap-3" style="background:var(--fonce);">
        <span class="badge rounded-2 px-3 py-2" style="background:var(--marron);font-family:monospace;font-size:13px;">{{ $filiere->code }}</span>
        <span style="font-weight:700;font-size:15px;color:#fff;">{{ $filiere->nom }}</span>
    </div>

    @php $parNiveau = $filiere->matieres->groupBy('niveau'); @endphp

    @foreach([1,2,3] as $niveau)
    @if($parNiveau->has($niveau))
    <div class="px-4 py-2 border-bottom" style="background:#f9f6f1;">
        <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--marron);">Niveau {{ $niveau }}</span>
    </div>
    <table class="table table-hover mb-0">
        <thead style="background:var(--beige);">
            <tr style="font-size:12px;color:var(--fonce);">
                <th class="px-4 py-2">Code</th>
                <th>Matière</th>
                <th>S</th>
                <th>HP Initial</th>
                <th>HP Restant</th>
                <th>TPE Initial</th>
                <th>TPE Dyn.</th>
                <th>MHT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($parNiveau[$niveau]->sortBy('semestre') as $m)
            @php
                $quota   = $m->quotasCentres->first();
                $mht     = $m->hp_initial + $m->tpe_initial;
                $pctHP   = ($quota && $m->hp_initial > 0) ? round($quota->hp_restant / $m->hp_initial * 100) : null;
                $couleur = $pctHP === null ? '#aaa' : ($pctHP > 50 ? '#4caf50' : ($pctHP > 20 ? '#ff9800' : '#f44336'));
            @endphp
            <tr>
                <td class="px-4 py-2">
                    <code style="font-size:11px;background:#f0ebe4;padding:2px 8px;border-radius:4px;">{{ $m->code }}</code>
                </td>
                <td style="font-weight:600;font-size:13px;color:var(--fonce);">{{ $m->nom }}</td>
                <td>
                    <span class="badge rounded-pill px-2" style="font-size:10px;background:{{ $m->semestre==1?'#e3f2fd':'#f3e5f5' }};color:{{ $m->semestre==1?'#1565c0':'#6a1b9a' }};">
                        S{{ $m->semestre }}
                    </span>
                </td>
                <td style="font-size:13px;">{{ $m->hp_initial }}h</td>
                <td>
                    @if($quota)
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:60px;height:6px;background:#eee;border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:{{ $pctHP }}%;background:{{ $couleur }};border-radius:3px;"></div>
                        </div>
                        <span style="font-size:13px;font-weight:600;color:{{ $couleur }}">{{ $quota->hp_restant }}h</span>
                    </div>
                    @else
                    <span style="font-size:12px;color:#aaa;">Non démarré</span>
                    @endif
                </td>
                <td style="font-size:13px;">{{ $m->tpe_initial }}h</td>
                <td style="font-size:13px;">
                    @if($quota)
                    <span style="{{ $quota->tpe_dynamique < $m->tpe_initial ? 'color:#e65100;font-weight:600;' : '' }}">
                        {{ $quota->tpe_dynamique }}h
                    </span>
                    @else
                    <span style="color:#aaa;">—</span>
                    @endif
                </td>
                <td style="font-size:13px;font-weight:700;">{{ $mht }}h</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    @endforeach
</div>
@endif
@empty
<div class="bg-white rounded-4 border p-5 text-center" style="color:#aaa;">
    <div style="font-size:48px;">📚</div>
    <p class="mt-3">Aucune matière dans le référentiel. Le Directeur doit en créer.</p>
    @admin
    <a href="{{ route('filieres.index') }}" class="btn text-white rounded-3 px-4 mt-2" style="background:var(--fonce);">
        Accéder au référentiel
    </a>
    @endadmin
</div>
@endforelse
@endsection
