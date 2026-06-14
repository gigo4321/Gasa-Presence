@extends('layouts.app')
@section('titre','Étudiants — '.($annee?->libelle ?? 'Toutes années'))

@section('content')

{{-- Sélecteur d'année --}}
<div class="d-flex gap-3 align-items-center mb-4 flex-wrap">
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="fw-semibold" style="font-size:13px;color:var(--fonce);white-space:nowrap;">Année scolaire :</label>
        <select name="annee_id" class="form-select form-select-sm rounded-3" style="width:auto;" onchange="this.form.submit()">
            @foreach($annees as $a)
            <option value="{{ $a->id }}" {{ $annee?->id==$a->id?'selected':'' }}>
                {{ $a->libelle }}{{ $a->active?' ★':'' }}
            </option>
            @endforeach
        </select>
    </form>
    <div class="ms-auto d-flex gap-2">
        @if(auth()->user()->peutGererCentre())
        <button class="btn btn-sm text-white rounded-3 px-3" style="background:var(--marron);font-size:13px;"
                data-bs-toggle="modal" data-bs-target="#modalImport">
            <i class="bi bi-upload me-1"></i> Importer CSV
        </button>
        <button class="btn btn-sm text-white rounded-3 px-3" style="background:var(--fonce);font-size:13px;"
                data-bs-toggle="modal" data-bs-target="#modalAjout">
            <i class="bi bi-plus-lg me-1"></i> Inscrire un étudiant
        </button>
        @endif
    </div>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="stat-card"><span class="stat-icon">🎓</span><div><div class="stat-value">{{ $inscriptions->total() }}</div><div class="stat-label">Inscrits</div></div></div></div>
    <div class="col-md-3"><div class="stat-card" style="background:#e8f5e9;"><span class="stat-icon">✅</span><div><div class="stat-value">{{ $inscriptions->where('statut','actif')->count() }}</div><div class="stat-label">Actifs</div></div></div></div>
    <div class="col-md-3"><div class="stat-card" style="background:#fff3e0;"><span class="stat-icon">⏸</span><div><div class="stat-value">{{ $inscriptions->where('statut','suspendu')->count() }}</div><div class="stat-label">Suspendus</div></div></div></div>
    <div class="col-md-3"><div class="stat-card" style="background:#e3f2fd;"><span class="stat-icon">🏆</span><div><div class="stat-value">{{ $inscriptions->where('statut','diplome')->count() }}</div><div class="stat-label">Diplômés</div></div></div></div>
</div>

{{-- Recherche --}}
<div class="bg-white rounded-4 p-3 border mb-3">
    <form method="GET" class="d-flex gap-2 flex-wrap">
        <input type="hidden" name="annee_id" value="{{ $annee?->id }}">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control rounded-3 flex-grow-1" placeholder="Nom, prénom, matricule…">
        <select name="statut" class="form-select rounded-3" style="width:auto;">
            <option value="">Tous statuts</option>
            <option value="actif" {{ request('statut')=='actif'?'selected':'' }}>Actifs</option>
            <option value="suspendu" {{ request('statut')=='suspendu'?'selected':'' }}>Suspendus</option>
            <option value="diplome" {{ request('statut')=='diplome'?'selected':'' }}>Diplômés</option>
        </select>
        <button type="submit" class="btn text-white rounded-3 px-3" style="background:var(--marron);"><i class="bi bi-search"></i></button>
    </form>
</div>

{{-- Tableau --}}
<div class="bg-white rounded-4 border overflow-hidden">
    <table class="table table-hover table-gasa mb-0">
        <thead><tr>
            <th>Matricule</th><th>Nom & Prénom</th><th>Badge</th>
            <th>Option / Niveau</th><th>Année</th><th>Statut</th><th>Actions</th>
        </tr></thead>
        <tbody>
        @forelse($inscriptions as $insc)
        @php
            $e = $insc->etudiant;
            $sc = match($insc->statut){
                'actif'=>['#e8f5e9','#2e7d32'],'suspendu'=>['#fff3e0','#e65100'],
                'diplome'=>['#e3f2fd','#1565c0'],'abandonne'=>['#f5f5f5','#616161'],
                default=>['#f5f5f5','#616161']
            };
        @endphp
        <tr>
            <td><code style="font-size:11px;background:#f5f5f5;padding:2px 6px;border-radius:4px;">{{ $e->matricule }}</code></td>
            <td>
                <div style="font-weight:600;font-size:13px;color:var(--fonce);">{{ $e->nom }} {{ $e->prenom }}</div>
                <div style="font-size:11px;color:#aaa;">{{ $e->email }}</div>
            </td>
            <td>
                @if($e->badge_uid)<code style="font-size:11px;background:#f5f5f5;padding:2px 6px;border-radius:4px;">{{ $e->badge_uid }}</code>
                @else<span style="color:#aaa;font-size:12px;">—</span>@endif
            </td>
            <td style="font-size:12px;">
                <div>{{ $insc->option?->filiereOption?->filiere?->code }} — {{ $insc->option?->filiereOption?->nom }}</div>
                <div style="color:var(--marron);">{{ $insc->option?->niveau?->libelle }}</div>
            </td>
            <td style="font-size:12px;">{{ $insc->anneeScolaire?->libelle }}</td>
            <td><span class="badge rounded-pill" style="font-size:11px;background:{{ $sc[0] }};color:{{ $sc[1] }}">{{ ucfirst($insc->statut) }}</span></td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid rgba(0,0,0,.1);"
                            data-bs-toggle="modal" data-bs-target="#editEtu{{ $e->id }}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    @if($insc->statut === 'actif' && auth()->user()->peutGererCentre())
                    <button class="btn btn-sm rounded-3" style="font-size:11px;background:#e3f2fd;color:#1565c0;border:none;"
                            data-bs-toggle="modal" data-bs-target="#reinscrire{{ $insc->id }}"
                            title="Réinscrire">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                    @endif
                </div>
            </td>
        </tr>

        {{-- Modal édition profil --}}
        <div class="modal fade" id="editEtu{{ $e->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content rounded-4">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Modifier le profil</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('etudiants.update',$e->id) }}">@csrf @method('PUT')
                <div class="modal-body"><div class="row g-3">
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Nom</label><input type="text" name="nom" value="{{ $e->nom }}" class="form-control rounded-3" required></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Prénom</label><input type="text" name="prenom" value="{{ $e->prenom }}" class="form-control rounded-3" required></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Email</label><input type="email" name="email" value="{{ $e->email }}" class="form-control rounded-3" required></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Téléphone</label><input type="text" name="telephone" value="{{ $e->telephone }}" class="form-control rounded-3"></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Badge UID</label><input type="text" name="badge_uid" value="{{ $e->badge_uid }}" class="form-control rounded-3"></div>
                    <div class="col-6"><label class="form-label fw-semibold" style="font-size:13px;">Date de naissance</label><input type="date" name="date_naissance" value="{{ $e->date_naissance?->format('Y-m-d') }}" class="form-control rounded-3"></div>
                </div></div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Sauvegarder</button>
                </div>
            </form>
        </div></div></div>

        {{-- Modal réinscription --}}
        <div class="modal fade" id="reinscrire{{ $insc->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold" style="color:var(--fonce)">Réinscrire — {{ $e->nom }} {{ $e->prenom }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('inscriptions.reinscrire',$insc->id) }}">@csrf
                <div class="modal-body">
                    <div class="p-3 rounded-3 mb-3" style="background:var(--beige);font-size:12px;color:var(--fonce);">
                        <strong>Inscription actuelle :</strong> {{ $insc->option?->filiereOption?->nom }} — {{ $insc->option?->niveau?->libelle }} ({{ $insc->anneeScolaire?->libelle }})
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Nouvelle année scolaire *</label>
                        <select name="annee_scolaire_id" class="form-select rounded-3" required>
                            <option value="">— Sélectionner —</option>
                            @foreach($annees as $a)
                            @if($a->id !== $insc->annee_scolaire_id)
                            <option value="{{ $a->id }}">{{ $a->libelle }}{{ $a->active?' (active)':'' }}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Nouveau groupe / option *</label>
                        <select name="option_id" class="form-select rounded-3" required>
                            <option value="">— Sélectionner —</option>
                            @foreach($options as $o)
                            <option value="{{ $o->id }}" {{ $insc->option?->niveau?->niveauSuivant()?->id === $o->niveau_id ? 'selected' : '' }}>
                                {{ $o->filiereOption?->nom }} — {{ $o->niveau?->libelle }} ({{ $o->anneeScolaire?->libelle }})
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Le niveau suivant ({{ $insc->option?->niveau?->niveauSuivant()?->libelle ?? 'non défini' }}) est présélectionné si disponible.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:13px;">Notes</label>
                        <textarea name="notes" class="form-control rounded-3" rows="2" placeholder="Mention, résultats…"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Réinscrire</button>
                </div>
            </form>
        </div></div></div>

        @empty
        <tr><td colspan="7" class="text-center py-5" style="color:#aaa;">Aucun étudiant inscrit pour cette période.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="d-flex justify-content-between align-items-center mt-3">
    <span style="font-size:13px;color:var(--marron)">{{ $inscriptions->total() }} inscription(s)</span>
    {{ $inscriptions->links() }}
</div>

{{-- Modal ajout --}}
<div class="modal fade" id="modalAjout" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content rounded-4">
    <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Inscrire un étudiant</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="{{ route('etudiants.store') }}">@csrf
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Nom *</label><input type="text" name="nom" class="form-control rounded-3" placeholder="AHOUANSOU" required></div>
            <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Prénom *</label><input type="text" name="prenom" class="form-control rounded-3" required></div>
            <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Email *</label><input type="email" name="email" class="form-control rounded-3" required></div>
            <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Matricule *</label><input type="text" name="matricule" class="form-control rounded-3" placeholder="GASA-2026-001" required></div>
            <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:13px;">Téléphone</label><input type="text" name="telephone" class="form-control rounded-3"></div>
            <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:13px;">Badge UID</label><input type="text" name="badge_uid" class="form-control rounded-3"></div>
            <div class="col-md-4"><label class="form-label fw-semibold" style="font-size:13px;">Date de naissance</label><input type="date" name="date_naissance" class="form-control rounded-3"></div>
            <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Groupe (option) *</label>
                <select name="option_id" class="form-select rounded-3" required>
                    <option value="">— Sélectionner —</option>
                    @foreach($options as $o)
                    <option value="{{ $o->id }}">{{ $o->filiereOption?->nom }} — {{ $o->niveau?->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6"><label class="form-label fw-semibold" style="font-size:13px;">Année scolaire *</label>
                <select name="annee_scolaire_id" class="form-select rounded-3" required>
                    @foreach($annees as $a)
                    <option value="{{ $a->id }}" {{ $a->active?'selected':'' }}>{{ $a->libelle }}{{ $a->active?' (active)':'' }}</option>
                    @endforeach
                </select>
            </div>
        </div></div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);">Inscrire</button>
        </div>
    </form>
</div></div></div>

{{-- Modal import --}}
<div class="modal fade" id="modalImport" tabindex="-1"><div class="modal-dialog"><div class="modal-content rounded-4">
    <div class="modal-header border-0"><h6 class="modal-title fw-bold" style="color:var(--fonce)">Importer une liste CSV</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="{{ route('etudiants.import',$centreId) }}" enctype="multipart/form-data">@csrf
        <div class="modal-body">
            <div class="p-3 rounded-3 mb-3" style="background:var(--beige);font-size:12px;color:var(--fonce);">
                <i class="bi bi-info-circle me-1"></i>
                Colonnes requises : <strong>matricule, nom, prenom, email</strong> (optionnel: telephone, badge_uid)<br>
                <a href="{{ route('etudiants.modele') }}" class="fw-semibold" style="color:var(--fonce);">📥 Télécharger le modèle CSV</a>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Groupe cible *</label>
                <select name="option_id" class="form-select rounded-3" required>
                    <option value="">— Sélectionner —</option>
                    @foreach($options as $o)
                    <option value="{{ $o->id }}">{{ $o->filiereOption?->nom }} — {{ $o->niveau?->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Année scolaire *</label>
                <select name="annee_scolaire_id" class="form-select rounded-3" required>
                    @foreach($annees as $a)
                    <option value="{{ $a->id }}" {{ $a->active?'selected':'' }}>{{ $a->libelle }}{{ $a->active?' (active)':'' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px;">Fichier CSV *</label>
                <input type="file" name="fichier" class="form-control rounded-3" accept=".csv,.txt" required>
            </div>
        </div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn text-white rounded-3 px-4" style="background:var(--fonce);"><i class="bi bi-upload me-1"></i>Importer</button>
        </div>
    </form>
</div></div></div>
@endsection
