@extends('layouts.app')
@section('titre', 'Scan Accès — ' . $centre->nom)

@section('content')
<div class="row g-4">
    {{-- Panneau de scan --}}
    <div class="col-md-6">
        <div class="bg-white rounded-4 border p-5">
            <h6 class="fw-bold mb-4" style="color:var(--fonce)">Terminal de Contrôle d'Accès</h6>

            <div class="mb-4">
                <label class="form-label fw-semibold" style="font-size:13px;">Salle</label>
                <select id="salleSelect" class="form-select rounded-3">
                    <option value="">— Sélectionner une salle —</option>
                    @foreach($salles as $s)
                    <option value="{{ $s->id }}" data-type="{{ $s->type }}">
                        {{ $s->nom }} ({{ $s->capacite }} places — {{ $s->type }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold" style="font-size:13px;">Mode</label>
                <div class="d-flex gap-2">
                    <button id="btnEntree" onclick="setMode('entree')"
                            class="btn flex-1 rounded-3 text-white fw-semibold" style="background:var(--fonce);flex:1;">
                        📥 Entrée
                    </button>
                    <button id="btnSortie" onclick="setMode('sortie')"
                            class="btn flex-1 rounded-3 fw-semibold" style="background:#eee;flex:1;">
                        📤 Sortie
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold" style="font-size:13px;">Badge UID</label>
                <input type="text" id="badgeInput" class="form-control form-control-lg rounded-3"
                       style="font-family:monospace;font-size:18px;border:2px solid var(--marron);"
                       placeholder="Scanner ou saisir le badge…" autocomplete="off">
                <small class="text-muted">Appuyez sur Entrée après le scan</small>
            </div>

            <button onclick="scanner()" class="btn w-100 text-white rounded-3 py-3 fw-bold"
                    style="background:var(--marron);font-size:15px;">
                Valider le scan
            </button>

            {{-- Résultat --}}
            <div id="resultat" class="rounded-3 p-4 mt-4 text-center d-none">
                <div id="resultatIcon" style="font-size:48px;margin-bottom:8px;"></div>
                <div id="resultatTitre" style="font-size:18px;font-weight:700;margin-bottom:6px;"></div>
                <div id="resultatMessage" style="font-size:13px;"></div>
            </div>
        </div>
    </div>

    {{-- Historique --}}
    <div class="col-md-6">
        <div class="bg-white rounded-4 border p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0" style="color:var(--fonce)">Historique des passages</h6>
                <button onclick="viderHistorique()" class="btn btn-sm rounded-3" style="font-size:11px;border:1px solid rgba(0,0,0,.1);">
                    Effacer
                </button>
            </div>
            <div id="historique" style="max-height:500px;overflow-y:auto;">
                <p class="text-center py-4" style="color:#aaa;font-size:13px;">Aucun passage enregistré.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let mode = 'entree';
let historique = [];

function setMode(m) {
    mode = m;
    document.getElementById('btnEntree').style.background = m === 'entree' ? 'var(--fonce)' : '#eee';
    document.getElementById('btnEntree').style.color      = m === 'entree' ? '#fff' : 'var(--fonce)';
    document.getElementById('btnSortie').style.background = m === 'sortie' ? 'var(--fonce)' : '#eee';
    document.getElementById('btnSortie').style.color      = m === 'sortie' ? '#fff' : 'var(--fonce)';
}

// Scan sur Entrée
document.getElementById('badgeInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') scanner();
});

async function scanner() {
    const badge   = document.getElementById('badgeInput').value.trim();
    const salleId = document.getElementById('salleSelect').value;

    if (!badge)   { afficherResultat('orange', '⚠️', 'Saisir un badge', ''); return; }
    if (!salleId) { afficherResultat('orange', '⚠️', 'Sélectionner une salle', ''); return; }

    try {
        const res = await fetch('{{ route("scan.badge") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ badge_uid: badge, salle_id: salleId, mode }),
        });

        const data = await res.json();

        const iconMap = { vert: '✅', rouge: '🚫', orange: '⚠️' };
        const titreMap = {
            vert:   'ACCÈS AUTORISÉ',
            rouge:  'ACCÈS REFUSÉ',
            orange: 'ATTENTION',
        };
        const bgMap = {
            vert:   '#e8f5e9',
            rouge:  '#ffebee',
            orange: '#fff8e1',
        };

        afficherResultat(data.couleur, iconMap[data.couleur], titreMap[data.couleur], data.message, bgMap[data.couleur]);

        // Ajouter à l'historique
        ajouterHistorique(badge, data, mode);

        // Vider le champ badge et refocus
        document.getElementById('badgeInput').value = '';
        setTimeout(() => document.getElementById('badgeInput').focus(), 100);

    } catch(e) {
        afficherResultat('orange', '⚠️', 'Erreur réseau', e.message);
    }
}

function afficherResultat(couleur, icon, titre, message, bg) {
    const div = document.getElementById('resultat');
    div.className = 'rounded-3 p-4 mt-4 text-center';
    div.style.background = bg || '#f5f5f5';
    document.getElementById('resultatIcon').textContent    = icon;
    document.getElementById('resultatTitre').textContent   = titre;
    document.getElementById('resultatMessage').textContent = message;
}

function ajouterHistorique(badge, data, mode) {
    const heure = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const item  = { badge, message: data.message, couleur: data.couleur, mode, heure };
    historique.unshift(item);
    if (historique.length > 20) historique.pop();
    renderHistorique();
}

function renderHistorique() {
    const el = document.getElementById('historique');
    if (!historique.length) {
        el.innerHTML = '<p class="text-center py-4" style="color:#aaa;font-size:13px;">Aucun passage.</p>';
        return;
    }
    const bgMap = { vert: '#e8f5e9', rouge: '#ffebee', orange: '#fff8e1' };
    const icMap = { vert: '✅', rouge: '🚫', orange: '⚠️' };
    el.innerHTML = historique.map(h => `
        <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2" style="background:${bgMap[h.couleur]||'#f5f5f5'}">
            <span style="font-size:18px;">${icMap[h.couleur]||'•'}</span>
            <div class="flex-1">
                <div style="font-family:monospace;font-size:11px;color:#888;">${h.badge}</div>
                <div style="font-size:12px;">${h.message}</div>
            </div>
            <div class="text-end" style="font-size:11px;color:#aaa;white-space:nowrap;">
                ${h.heure}<br>${h.mode.toUpperCase()}
            </div>
        </div>
    `).join('');
}

function viderHistorique() {
    historique = [];
    renderHistorique();
}

// Focus auto au chargement
window.onload = () => document.getElementById('badgeInput').focus();
</script>
@endpush
@endsection
