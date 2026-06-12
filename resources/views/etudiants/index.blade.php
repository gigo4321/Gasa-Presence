@extends('layouts.app')
@section('titre', 'Gestion des Étudiants')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card"><span class="stat-icon">🎓</span>
            <div><div class="stat-value">{{ $etudiants->total() }}</div><div class="stat-label">Total</div></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background:#e8f5e9;"><span class="stat-icon">✅</span>
            <div><div class="stat-value">{{ $etudiants->where('statut','actif')->count() }}</div><div class="stat-label">Actifs</div></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background:#fff3e0;"><span class="stat-icon">⚠️</span>
            <div><div class="stat-value">{{ $etudiants->where('statut','suspendu')->count() }}</div><div class="stat-label">Suspendus</div></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-4 p-4 border mb-3">
    <div class="d-flex gap-3 flex-wrap align-items-center">
        <form method="GET" class="d-flex gap-2 flex-grow-1">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control rounded-3"
                   placeholder="Rechercher par nom, prénom, matricule…">
            <button type="submit" class="btn text-white px-3 rounded-3" style="background:var(--marron);">
                <i class="bi bi-search"></i>
            </button>
        </form>
        @if(auth()->user()->peutGererCentre())
        <button class="btn text-white px-4 rounded-3" style="background:var(--fonce);"
                data-bs-toggle="modal" data-bs-target="#modalAjout">
            <i class="bi bi-plus-lg me-1"></i> Ajouter
        </button>
        @endif
    </div>
</div>

<div class="bg-white rounded-4 border overflow-hidden">
    <table class="table table-hover table-gasa mb-0">
        <thead>
            <tr>
                <th>Matricule</th><th>Nom & Prénom</th><th>Email</th>
                <th>Option</th><th>Badge</th><th>Statut</th><th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($etudiants as $e)
            <tr>
                <td><code style="font-size:12px;">{{ $e->matricule }}</code></td>
                <td>{{ $e->nom }} {{ $e->prenom }}</td>
                <td style="font-size:13px;">{{ $e->email }}</td>
                <td style="font-size:12px;">{{ $e->option?->nom }}</td>
                <td>
                    @if($e->badge_uid)
                        <code style="font-size:11px;background:#f5f5f5;padding:2px 6px;border-radius:4px;">{{ $e->badge_uid }}</code>
                    @else
                        <span style="font-size:12px;color:#aaa;">Non assigné</span>
                    @endif
                </td>
                <td>
                    @php $colors = ['actif'=>['#e8f5e9','#2e7d32'],'suspendu'=>['#fff3e0','#e65100'],'diplome'=>['#f5f5f5','#616161']]; $c = $colors[$e->statut] ?? ['#f5f5f5','#616161']; @endphp
                    <span class="badge rounded-pill" style="background:{{ $c[0] }};color:{{ $c[1] }};font-size:11px;">{{ ucfirst($e->statut) }}</span>
                </td>
                <td>
                    <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid rgba(0,0,0,.1);"
                            data-bs-toggle="modal" data-bs-target="#edit{{ $e->id }}">Modifier</button>
                </td>
            </tr>
            {{-- Modal édition --}}
            <div class="modal fade" id="edit{{ $e->id }}" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content rounded-4">
                    <div class="modal-header border-0">
                        <h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier l'étudiant</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="{{ route('etudiants.update',$e->id) }}">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Nom</label>
                                    <input type="text" name="nom" value="{{ $e->nom }}" class="form-control rounded-3" required></div>
                                <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Prénom</label>
                                    <input type="text" name="prenom" value="{{ $e->prenom }}" class="form-control rounded-3" required></div>
                                <div class="col-12"><label class="form-label fw-semibold" style="font-size:13px;">Badge UID</label>
                                    <input type="text" name="badge_uid" value="{{ $e->badge_uid }}" class="form-control rounded-3" placeholder="A1B2C3D4"></div>
                                <div class="col-12"><label class="form-label fw-semibold" style="font-size:13px;">Statut</label>
                                    <select name="statut" class="form-select rounded-3">
                                        <option value="actif" {{ $e->statut=='actif'?'selected':'' }}>Actif</option>
                                        <option value="suspendu" {{ $e->statut=='suspendu'?'selected':'' }}>Suspendu</option>
                                        <option value="diplome" {{ $e->statut=='diplome'?'selected':'' }}>Diplômé</option>
                                    </select></div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Sauvegarder</button>
                        </div>
                    </form>
                </div></div>
            </div>
            @empty
            <tr><td colspan="7" class="text-center py-5" style="color:#aaa;">Aucun étudiant trouvé.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-between align-items-center mt-3">
    <span style="font-size:13px;color:var(--marron)">{{ $etudiants->total() }} étudiant(s)</span>
    {{ $etudiants->links() }}
</div>

{{-- Modal ajout --}}
<div class="modal fade" id="modalAjout" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content rounded-4">
        <div class="modal-header border-0">
            <h6 class="modal-title fw-bold" style="color:var(--fonce)">Ajouter un étudiant</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="{{ route('etudiants.store') }}">
            @csrf
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Nom *</label>
                        <input type="text" name="nom" class="form-control rounded-3" placeholder="AHOUANSOU" required></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Prénom *</label>
                        <input type="text" name="prenom" class="form-control rounded-3" placeholder="Kossivi" required></div>
                    <div class="col-12"><label class="form-label fw-semibold" style="font-size:13px;">Email *</label>
                        <input type="email" name="email" class="form-control rounded-3" required></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Matricule *</label>
                        <input type="text" name="matricule" class="form-control rounded-3" placeholder="GASA-2026-001" required></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Badge UID</label>
                        <input type="text" name="badge_uid" class="form-control rounded-3" placeholder="A1B2C3D4"></div>
                    <div class="col-12"><label class="form-label fw-semibold" style="font-size:13px;">Option *</label>
                        <select name="option_id" class="form-select rounded-3" required>
                            <option value="">— Sélectionner —</option>
                            @foreach($options as $o)
                            <option value="{{ $o->id }}">{{ $o->nom }} (Niv.{{ $o->niveau }}) — {{ $o->filiere?->code }}</option>
                            @endforeach
                        </select></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Ajouter</button>
            </div>
        </form>
    </div></div>
</div>
@endsection
