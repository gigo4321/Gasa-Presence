@extends('layouts.app')
@section('titre', 'Années Scolaires')

@section('content')
<div class="row g-4">
    <div class="col-md-7">
        <div class="bg-white rounded-4 border overflow-hidden">
            <div class="px-4 py-3 border-bottom" style="background:var(--fonce);">
                <h6 class="mb-0 fw-bold text-white">Années scolaires configurées</h6>
            </div>
            @forelse($annees as $a)
            <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span style="font-weight:700;font-size:15px;color:var(--fonce)">{{ $a->libelle }}</span>
                        @if($a->active)
                        <span class="badge rounded-pill px-3" style="background:#e8f5e9;color:#2e7d32;font-size:11px;">● Active</span>
                        @endif
                    </div>
                    <div style="font-size:12px;color:#888;">
                        Du {{ $a->date_debut->locale('fr')->isoFormat('D MMMM YYYY') }}
                        au {{ $a->date_fin->locale('fr')->isoFormat('D MMMM YYYY') }}
                    </div>
                </div>
                @if(!$a->active)
                <form method="POST" action="{{ route('presences.annees.activer', $a->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm rounded-3"
                            style="font-size:12px;border:1px solid var(--marron);color:var(--marron);">
                        Définir comme active
                    </button>
                </form>
                @endif
            </div>
            @empty
            <div class="p-5 text-center" style="color:#aaa;">
                <div style="font-size:36px;margin-bottom:12px;">📅</div>
                Aucune année scolaire configurée.
            </div>
            @endforelse
        </div>
    </div>

    <div class="col-md-5">
        <div class="bg-white rounded-4 border p-4">
            <h6 class="fw-bold mb-4" style="color:var(--fonce)">Créer une année scolaire</h6>
            <form method="POST" action="{{ route('presences.annees.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Libellé *</label>
                    <input type="text" name="libelle" class="form-control rounded-3"
                           placeholder="2025-2026" required>
                    <small class="text-muted">Format recommandé : AAAA-AAAA</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Date de début *</label>
                    <input type="date" name="date_debut" class="form-control rounded-3" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Date de fin *</label>
                    <input type="date" name="date_fin" class="form-control rounded-3" required>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="active" id="active" value="1">
                    <label class="form-check-label" for="active" style="font-size:13px;">
                        Définir comme année active
                    </label>
                </div>
                <button type="submit" class="btn text-white rounded-3 w-100" style="background:var(--fonce);">
                    Créer l'année scolaire
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
