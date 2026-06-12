@extends('layouts.app')
@section('titre', 'Professeurs — ' . $centre->nom)

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <span class="stat-icon">👨‍🏫</span>
            <div>
                <div class="stat-value">{{ $professeurs->count() }}</div>
                <div class="stat-label">Professeurs rattachés</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background:#e8f5e9;">
            <span class="stat-icon">✅</span>
            <div>
                <div class="stat-value">{{ $professeurs->filter(fn($p) => $p->email_verified_at)->count() }}</div>
                <div class="stat-label">Actifs</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background:#fff3e0;">
            <span class="stat-icon">⏸</span>
            <div>
                <div class="stat-value">{{ $professeurs->filter(fn($p) => !$p->email_verified_at)->count() }}</div>
                <div class="stat-label">Inactifs</div>
            </div>
        </div>
    </div>
</div>

{{-- Bouton ajouter --}}
@if(auth()->user()->peutGererCentre())
<div class="d-flex justify-content-end mb-3">
    <button class="btn text-white rounded-3 px-4" style="background:var(--fonce);"
            data-bs-toggle="modal" data-bs-target="#modalAjout">
        <i class="bi bi-plus-lg me-1"></i> Ajouter un professeur
    </button>
</div>
@endif

{{-- Tableau --}}
<div class="bg-white rounded-4 border overflow-hidden">
    <table class="table table-hover table-gasa mb-0">
        <thead>
            <tr>
                <th>Professeur</th>
                <th>Téléphone</th>
                <th>Badge UID</th>
                <th>Matières enseignées</th>
                <th>Statut</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($professeurs as $p)
            @php $actif = (bool) $p->email_verified_at; @endphp
            <tr style="{{ !$actif ? 'opacity:.55;' : '' }}">

                {{-- Nom + email --}}
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:36px;height:36px;background:var(--fonce);color:var(--beige);font-size:14px;font-weight:700;">
                            {{ strtoupper(substr($p->name, 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:13px;color:var(--fonce)">{{ $p->name }}</div>
                            <div style="font-size:11px;color:#aaa;">{{ $p->email }}</div>
                        </div>
                    </div>
                </td>

                {{-- Téléphone --}}
                <td style="font-size:13px;">
                    {{ $p->telephone ?? '—' }}
                </td>

                {{-- Badge --}}
                <td>
                    @if($p->badge_uid)
                        <code style="font-size:11px;background:#f5f5f5;padding:2px 8px;border-radius:4px;">{{ $p->badge_uid }}</code>
                    @else
                        <span style="font-size:12px;color:#aaa;">Non assigné</span>
                    @endif
                </td>

                {{-- Matières --}}
                <td>
                    @if($p->matieres->count())
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($p->matieres as $m)
                            <span class="badge rounded-2 px-2"
                                  style="font-size:10px;background:var(--beige);color:var(--fonce);border:1px solid rgba(141,110,99,.2);">
                                {{ $m->code }}
                            </span>
                            @endforeach
                        </div>
                    @else
                        <span style="font-size:12px;color:#aaa;">Aucune matière</span>
                    @endif
                </td>

                {{-- Statut --}}
                <td>
                    <span class="badge rounded-pill px-3"
                          style="font-size:11px;background:{{ $actif ? '#e8f5e9' : '#f5f5f5' }};color:{{ $actif ? '#2e7d32' : '#9e9e9e' }}">
                        {{ $actif ? 'Actif' : 'Inactif' }}
                    </span>
                </td>

                {{-- Actions --}}
                <td>
                    @if(auth()->user()->peutGererCentre())
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm rounded-3"
                                style="font-size:11px;border:1px solid rgba(0,0,0,.1);"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEdit{{ $p->id }}">
                            <i class="bi bi-pencil"></i> Modifier
                        </button>
                        <form method="POST" action="{{ route('professeurs.toggle', $p->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm rounded-3"
                                    style="font-size:11px;background:{{ $actif ? '#ffebee' : '#e8f5e9' }};color:{{ $actif ? '#c62828' : '#2e7d32' }};border:none;">
                                <i class="bi bi-{{ $actif ? 'pause-circle' : 'play-circle' }}"></i>
                                {{ $actif ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>
                    </div>
                    @endif
                </td>
            </tr>

            {{-- Modal modification --}}
            <div class="modal fade" id="modalEdit{{ $p->id }}" tabindex="-1">
                <div class="modal-dialog modal-lg"><div class="modal-content rounded-4">
                    <div class="modal-header border-0">
                        <h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier — {{ $p->name }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="{{ route('professeurs.update', $p->id) }}">
                        @csrf @method('PUT')
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Nom complet *</label>
                                    <input type="text" name="name" value="{{ $p->name }}"
                                           class="form-control rounded-3" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Email *</label>
                                    <input type="email" name="email" value="{{ $p->email }}"
                                           class="form-control rounded-3" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Téléphone *</label>
                                    <input type="text" name="telephone" value="{{ $p->telephone }}"
                                           class="form-control rounded-3" placeholder="+229 97 00 00 00" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Badge UID</label>
                                    <input type="text" name="badge_uid" value="{{ $p->badge_uid }}"
                                           class="form-control rounded-3" placeholder="PROF-GBE-001">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size:13px;">Matières enseignées</label>
                                    <div class="border rounded-3 p-3" style="max-height:180px;overflow-y:auto;">
                                        @foreach($matieres as $m)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="matiere_ids[]" value="{{ $m->id }}"
                                                   id="edit_m{{ $p->id }}_{{ $m->id }}"
                                                   {{ $p->matieres->contains($m->id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="edit_m{{ $p->id }}_{{ $m->id }}"
                                                   style="font-size:13px;">
                                                <strong>{{ $m->code }}</strong> — {{ $m->nom }}
                                                <span style="font-size:11px;color:#aaa;">({{ $m->filiere?->code }})</span>
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn text-white rounded-3 px-4"
                                    style="background:var(--fonce);">Sauvegarder</button>
                        </div>
                    </form>
                </div></div>
            </div>

            @empty
            <tr>
                <td colspan="6" class="text-center py-5" style="color:#aaa;">
                    <div style="font-size:36px;margin-bottom:12px;">👨‍🏫</div>
                    Aucun professeur rattaché à ce centre.<br>
                    <small>Cliquez sur "Ajouter un professeur" pour commencer.</small>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal ajout --}}
<div class="modal fade" id="modalAjout" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content rounded-4">
        <div class="modal-header border-0">
            <h6 class="modal-title fw-bold" style="color:var(--fonce)">Ajouter un professeur</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="{{ route('professeurs.store') }}">
            @csrf
            <input type="hidden" name="centre_id" value="{{ $centreId }}">
            <div class="modal-body">
                <div class="row g-3">

                    {{-- Nom / Prénom --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Nom *</label>
                        <input type="text" name="name" class="form-control rounded-3"
                               placeholder="KPOSSOU" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Prénom *</label>
                        <input type="text" name="prenom" class="form-control rounded-3"
                               placeholder="Jean" required>
                    </div>

                    {{-- Email --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Email professionnel *</label>
                        <input type="email" name="email" class="form-control rounded-3"
                               placeholder="j.kpossou@gasa.bj" required>
                    </div>

                    {{-- Téléphone --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Téléphone *</label>
                        <input type="text" name="telephone" class="form-control rounded-3"
                               placeholder="+229 97 00 00 00" required>
                    </div>

                    {{-- Badge + Mot de passe --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Badge UID</label>
                        <input type="text" name="badge_uid" class="form-control rounded-3"
                               placeholder="PROF-GBE-001">
                        <small class="text-muted" style="font-size:11px;">Pour le scan d'accès aux salles</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Mot de passe *</label>
                        <input type="password" name="password" class="form-control rounded-3"
                               placeholder="Min. 6 caractères" required>
                    </div>

                    {{-- Matières --}}
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:13px;">
                            Matières enseignées
                            <span style="font-weight:400;color:#aaa;">(cocher une ou plusieurs)</span>
                        </label>
                        <div class="border rounded-3 p-3" style="max-height:200px;overflow-y:auto;">
                            @forelse($matieres as $m)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="matiere_ids[]" value="{{ $m->id }}"
                                       id="new_m{{ $m->id }}">
                                <label class="form-check-label" for="new_m{{ $m->id }}"
                                       style="font-size:13px;">
                                    <strong>{{ $m->code }}</strong> — {{ $m->nom }}
                                    <span style="font-size:11px;color:#aaa;">
                                        S{{ $m->semestre }} · {{ $m->hp_initial }}h HP
                                        · {{ $m->filiere?->code }}
                                    </span>
                                </label>
                            </div>
                            @empty
                            <p style="font-size:13px;color:#aaa;margin:0;">
                                Aucune matière disponible. Le Directeur doit en créer d'abord.
                            </p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Info centre --}}
                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background:var(--beige);font-size:12px;color:var(--fonce);">
                            <i class="bi bi-info-circle me-1"></i>
                            Ce professeur sera rattaché à <strong>{{ $centre->nom }}</strong>
                            et n'apparaîtra dans les plannings que de ce centre.
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn text-white rounded-3 px-4"
                        style="background:var(--fonce);">Ajouter le professeur</button>
            </div>
        </form>
    </div></div>
</div>

@endsection
