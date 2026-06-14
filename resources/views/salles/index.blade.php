@extends('layouts.app')
@section('titre', 'Salles — ' . $centre->nom)

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <span class="stat-icon">🚪</span>
            <div>
                <div class="stat-value">{{ $salles->count() }}</div>
                <div class="stat-label">Salles enregistrées</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background:#e3f2fd;">
            <span class="stat-icon">🖥️</span>
            <div>
                <div class="stat-value">{{ $salles->sum('equipements_count') }}</div>
                <div class="stat-label">Équipements au total</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background:#e8f5e9;">
            <span class="stat-icon">💺</span>
            <div>
                <div class="stat-value">{{ $salles->sum('capacite') }}</div>
                <div class="stat-label">Places disponibles</div>
            </div>
        </div>
    </div>
</div>

{{-- Bouton ajouter --}}
@if(auth()->user()->peutGererCentre())
<div class="d-flex justify-content-end mb-3">
    <button class="btn text-white rounded-3 px-4" style="background:var(--fonce);"
            data-bs-toggle="modal" data-bs-target="#modalAjoutSalle">
        <i class="bi bi-plus-lg me-1"></i> Ajouter une salle
    </button>
</div>
@endif

{{-- Liste des salles --}}
@forelse($salles as $salle)
<div class="bg-white rounded-4 border mb-3 overflow-hidden">

    {{-- En-tête de la salle --}}
    <div class="p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:46px;height:46px;background:var(--beige);color:var(--fonce);font-size:20px;">
                🚪
            </div>
            <div>
                <div class="fw-bold" style="font-size:15px;color:var(--fonce)">{{ $salle->nom }}</div>
                <div style="font-size:12px;color:var(--marron);">
                    <span class="badge rounded-pill px-3 me-1"
                          style="background:var(--beige);color:var(--fonce);font-size:11px;font-weight:500;">
                        {{ $salle->type ?: '—' }}
                    </span>
                    <i class="bi bi-people me-1"></i>{{ $salle->capacite }} places
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- Compteur équipements --}}
            <button class="btn btn-sm rounded-3"
                    style="font-size:12px;background:{{ $salle->equipements_count > 0 ? '#e3f2fd' : '#f5f5f5' }};color:{{ $salle->equipements_count > 0 ? '#1565c0' : '#9e9e9e' }};border:none;"
                    data-bs-toggle="collapse"
                    data-bs-target="#equip-{{ $salle->id }}"
                    aria-expanded="false">
                <i class="bi bi-tools me-1"></i>
                {{ $salle->equipements_count }} équipement{{ $salle->equipements_count != 1 ? 's' : '' }}
                <i class="bi bi-chevron-down ms-1" style="font-size:10px;"></i>
            </button>

            @if(auth()->user()->peutGererCentre())
            {{-- Modifier --}}
            <button class="btn btn-sm rounded-3"
                    style="font-size:11px;border:1px solid rgba(0,0,0,.1);"
                    data-bs-toggle="modal" data-bs-target="#modalEditSalle{{ $salle->id }}">
                <i class="bi bi-pencil"></i> Modifier
            </button>

            {{-- Supprimer --}}
            <form method="POST" action="{{ route('salles.destroy', $salle->id) }}"
                  onsubmit="return confirm('Supprimer la salle « {{ $salle->nom }} » et tous ses équipements ?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm rounded-3"
                        style="font-size:11px;background:#ffebee;color:#c62828;border:none;">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Section équipements (collapse) --}}
    <div class="collapse" id="equip-{{ $salle->id }}">
        <div class="border-top" style="background:#fafafa;">

            {{-- Tableau équipements --}}
            @if($salle->equipements->count())
            <table class="table table-hover table-gasa mb-0" style="font-size:13px;">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th>Type de matériel</th>
                        <th>N° de série</th>
                        <th>État</th>
                        <th>Quantité</th>
                        @if(auth()->user()->peutGererCentre())
                        <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($salle->equipements as $eq)
                    <tr>
                        <td>
                            <span class="fw-semibold" style="color:var(--fonce)">{{ $eq->nom }}</span>
                        </td>
                        <td style="color:var(--marron);">{{ $eq->type_materiel ?: '—' }}</td>
                        <td>
                            @if($eq->numero_serie)
                                <code style="font-size:11px;background:#f5f5f5;padding:2px 8px;border-radius:4px;">{{ $eq->numero_serie }}</code>
                            @else
                                <span style="color:#aaa;">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill px-3"
                                  style="font-size:11px;background:{{ $eq->etatCouleur() }};color:{{ $eq->etatTexte() }}">
                                {{ $eq->etatLibelle() }}
                            </span>
                        </td>
                        <td>
                            <span class="fw-semibold">{{ $eq->quantite }}</span>
                        </td>
                        @if(auth()->user()->peutGererCentre())
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm rounded-2"
                                        style="font-size:11px;border:1px solid rgba(0,0,0,.1);"
                                        data-bs-toggle="modal" data-bs-target="#modalEditEq{{ $eq->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('equipements.destroy', $eq->id) }}"
                                      onsubmit="return confirm('Supprimer « {{ $eq->nom }} » ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm rounded-2"
                                            style="font-size:11px;background:#ffebee;color:#c62828;border:none;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>

                    {{-- Modal édition équipement --}}
                    @if(auth()->user()->peutGererCentre())
                    <div class="modal fade" id="modalEditEq{{ $eq->id }}" tabindex="-1">
                        <div class="modal-dialog"><div class="modal-content rounded-4">
                            <div class="modal-header border-0">
                                <h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier — {{ $eq->nom }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="{{ route('equipements.update', $eq->id) }}">
                                @csrf @method('PUT')
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-semibold" style="font-size:13px;">Désignation *</label>
                                            <input type="text" name="nom" value="{{ $eq->nom }}"
                                                   class="form-control rounded-3" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" style="font-size:13px;">Type de matériel</label>
                                            <input type="text" name="type_materiel" value="{{ $eq->type_materiel }}"
                                                   class="form-control rounded-3" placeholder="ex : Ordinateur, Projecteur…">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" style="font-size:13px;">N° de série</label>
                                            <input type="text" name="numero_serie" value="{{ $eq->numero_serie }}"
                                                   class="form-control rounded-3" placeholder="SN-XXXXX">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" style="font-size:13px;">État *</label>
                                            <select name="etat" class="form-select rounded-3" required>
                                                @foreach(['bon'=>'Bon état','defectueux'=>'Défectueux','hors_service'=>'Hors service','en_maintenance'=>'En maintenance'] as $val => $lib)
                                                <option value="{{ $val }}" {{ $eq->etat == $val ? 'selected' : '' }}>{{ $lib }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold" style="font-size:13px;">Quantité *</label>
                                            <input type="number" name="quantite" value="{{ $eq->quantite }}"
                                                   class="form-control rounded-3" min="1" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Sauvegarder</button>
                                </div>
                            </form>
                        </div></div>
                    </div>
                    @endif
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-4 text-center" style="color:#aaa;font-size:13px;">
                <i class="bi bi-tools me-1"></i> Aucun équipement enregistré pour cette salle.
            </div>
            @endif

            {{-- Formulaire ajout équipement --}}
            @if(auth()->user()->peutGererCentre())
            <div class="p-3 border-top" style="background:#fff;">
                <form method="POST" action="{{ route('salles.equipements.store', $salle->id) }}">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Désignation *</label>
                            <input type="text" name="nom" class="form-control form-control-sm rounded-3"
                                   placeholder="ex : Ordinateur HP" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Type de matériel</label>
                            <input type="text" name="type_materiel" class="form-control form-control-sm rounded-3"
                                   placeholder="ex : Informatique">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;">N° de série</label>
                            <input type="text" name="numero_serie" class="form-control form-control-sm rounded-3"
                                   placeholder="SN-XXXXX">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;">État *</label>
                            <select name="etat" class="form-select form-select-sm rounded-3" required>
                                <option value="bon">Bon état</option>
                                <option value="defectueux">Défectueux</option>
                                <option value="hors_service">Hors service</option>
                                <option value="en_maintenance">En maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;">Qté *</label>
                            <input type="number" name="quantite" value="1" min="1"
                                   class="form-control form-control-sm rounded-3" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm text-white rounded-3 w-100"
                                    style="background:var(--marron);font-size:12px;">
                                <i class="bi bi-plus-lg me-1"></i> Ajouter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal modification salle --}}
@if(auth()->user()->peutGererCentre())
<div class="modal fade" id="modalEditSalle{{ $salle->id }}" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content rounded-4">
        <div class="modal-header border-0">
            <h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier la salle — {{ $salle->nom }}</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="{{ route('salles.update', $salle->id) }}">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:13px;">Nom de la salle *</label>
                        <input type="text" name="nom" value="{{ $salle->nom }}"
                               class="form-control rounded-3" placeholder="ex : Amphi A, Salle 101…" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Type de salle *</label>
                        <input type="text" name="type" value="{{ $salle->type }}"
                               class="form-control rounded-3" placeholder="ex : Amphithéâtre, Labo info…" required>
                        <small class="text-muted" style="font-size:11px;">Librement défini par vos soins.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Capacité (places) *</label>
                        <input type="number" name="capacite" value="{{ $salle->capacite }}"
                               class="form-control rounded-3" min="1" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Sauvegarder</button>
            </div>
        </form>
    </div></div>
</div>
@endif

@empty
<div class="bg-white rounded-4 border p-5 text-center" style="color:#aaa;">
    <div style="font-size:48px;margin-bottom:12px;">🚪</div>
    <div style="font-size:14px;">Aucune salle enregistrée pour ce centre.</div>
    @if(auth()->user()->peutGererCentre())
    <button class="btn mt-3 text-white rounded-3 px-4" style="background:var(--marron);"
            data-bs-toggle="modal" data-bs-target="#modalAjoutSalle">
        Créer la première salle
    </button>
    @endif
</div>
@endforelse

{{-- Modal création salle --}}
@if(auth()->user()->peutGererCentre())
<div class="modal fade" id="modalAjoutSalle" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content rounded-4">
        <div class="modal-header border-0">
            <h6 class="modal-title fw-bold" style="color:var(--fonce)">Ajouter une salle</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="{{ route('salles.store', $centreId) }}">
            @csrf
            <input type="hidden" name="centre_id" value="{{ $centreId }}">
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size:13px;">Nom de la salle *</label>
                        <input type="text" name="nom" class="form-control rounded-3"
                               placeholder="ex : Amphi A, Salle 101, Labo Réseau…" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Type de salle *</label>
                        <input type="text" name="type" class="form-control rounded-3"
                               placeholder="ex : Amphithéâtre, Labo informatique…" required>
                        <small class="text-muted" style="font-size:11px;">Tapez librement le type que vous souhaitez.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size:13px;">Capacité (places) *</label>
                        <input type="number" name="capacite" class="form-control rounded-3"
                               min="1" placeholder="60" required>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded-3" style="background:var(--beige);font-size:12px;color:var(--fonce);">
                            <i class="bi bi-info-circle me-1"></i>
                            Cette salle sera rattachée à <strong>{{ $centre->nom }}</strong>.
                            Vous pourrez y associer des équipements après création.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Créer la salle</button>
            </div>
        </form>
    </div></div>
</div>
@endif

@endsection
