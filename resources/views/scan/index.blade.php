@extends('layouts.app')
@section('titre', 'Scan Accès — ' . $centre->nom)

@section('content')
<div class="row g-3">

    {{-- ── Colonne gauche : terminal de scan ─────────────────────────────── --}}
    <div class="col-lg-4">
        <div class="bg-white rounded-4 border p-4 sticky-top" style="top:80px;">
            <h6 class="fw-bold mb-4" style="color:var(--fonce);font-size:14px;">
                <i class="bi bi-upc-scan me-2"></i>Terminal de scan
            </h6>

            {{-- Salle --}}
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Salle</label>
                <select id="salleSelect" class="form-select form-select-sm rounded-3" onchange="chargerSeance()">
                    <option value="">— Sélectionner une salle —</option>
                    @foreach($salles as $s)
                    <option value="{{ $s->id }}">{{ $s->nom }} ({{ $s->capacite }} pl.{{ $s->type ? ' — '.$s->type : '' }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Mode --}}
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Mode</label>
                <div class="d-flex gap-2">
                    <button id="btnEntree" onclick="setMode('entree')"
                            class="btn btn-sm flex-fill rounded-3 fw-semibold text-white" style="background:var(--fonce);">
                        <i class="bi bi-box-arrow-in-right"></i> Entrée
                    </button>
                    <button id="btnSortie" onclick="setMode('sortie')"
                            class="btn btn-sm flex-fill rounded-3 fw-semibold" style="background:#eee;color:var(--fonce);">
                        <i class="bi bi-box-arrow-right"></i> Sortie
                    </button>
                </div>
            </div>

            {{-- Badge --}}
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Badge UID</label>
                <input type="text" id="badgeInput" class="form-control rounded-3"
                       style="font-family:monospace;font-size:16px;border:2px solid var(--marron);"
                       placeholder="Scanner ou saisir…" autocomplete="off">
                <small class="text-muted" style="font-size:11px;">Appuyez sur Entrée après le scan</small>
            </div>

            <button onclick="scanner()" class="btn w-100 text-white rounded-3 py-2 fw-bold"
                    style="background:var(--marron);">
                <i class="bi bi-upc-scan me-1"></i>Valider le scan
            </button>

            {{-- Résultat --}}
            <div id="resultat" class="rounded-3 p-4 mt-3 text-center d-none">
                <div id="resultatIcon" style="font-size:40px;margin-bottom:6px;"></div>
                <div id="resultatTitre" style="font-size:16px;font-weight:700;margin-bottom:4px;"></div>
                <div id="resultatMessage" style="font-size:12px;line-height:1.4;"></div>
            </div>

            {{-- Historique compact --}}
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:12px;font-weight:600;color:var(--fonce);">Historique</span>
                    <button onclick="viderHistorique()" class="btn btn-sm rounded-3 py-0 px-2"
                            style="font-size:10px;border:1px solid #ddd;">Effacer</button>
                </div>
                <div id="historique" style="max-height:220px;overflow-y:auto;">
                    <p class="text-center py-3" style="color:#bbb;font-size:12px;">Aucun passage.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Colonne droite : séance + annuaire ─────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- Séance en cours --}}
        <div class="bg-white rounded-4 border p-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="color:var(--fonce);font-size:14px;">
                    <i class="bi bi-camera-video me-2"></i>Séance dans la salle sélectionnée
                </h6>
                <button onclick="chargerSeance()" class="btn btn-sm rounded-3 py-0 px-2"
                        style="font-size:11px;border:1px solid #ddd;" title="Rafraîchir">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <div id="seancePanneau">
                <div class="text-center py-4" style="color:#bbb;font-size:13px;">
                    <i class="bi bi-door-open" style="font-size:28px;"></i>
                    <p class="mt-2 mb-0">Sélectionner une salle pour voir la séance en cours.</p>
                </div>
            </div>
        </div>

        {{-- Annuaire des étudiants --}}
        <div class="bg-white rounded-4 border p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0" style="color:var(--fonce);font-size:14px;">
                    <i class="bi bi-people me-2"></i>Étudiants du centre
                    <span id="annuaireCompteur" class="badge rounded-pill ms-1" style="background:var(--marron);font-size:10px;"></span>
                </h6>
                <div class="d-flex gap-2 align-items-center">
                    <select id="filtreGroupe" class="form-select form-select-sm rounded-3" style="font-size:12px;width:auto;" onchange="filtrerAnnuaire()">
                        <option value="">Tous les groupes</option>
                        @foreach($groupes as $g)
                        <option value="{{ $g->id }}">{{ $g->nom }}</option>
                        @endforeach
                    </select>
                    <input type="text" id="rechercheAnnuaire" class="form-control form-control-sm rounded-3"
                           style="font-size:12px;width:160px;" placeholder="Rechercher…" oninput="filtrerAnnuaire()">
                </div>
            </div>

            <div id="annuaireLoading" class="text-center py-4" style="color:#bbb;">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <span class="ms-2" style="font-size:13px;">Chargement des étudiants…</span>
            </div>

            <div id="annuaireWrap" class="d-none" style="max-height:480px;overflow-y:auto;">
                <table class="table table-sm table-hover mb-0" style="font-size:12px;">
                    <thead style="position:sticky;top:0;background:#fff;z-index:1;">
                        <tr style="color:#888;">
                            <th style="font-weight:600;width:110px;">Badge UID</th>
                            <th style="font-weight:600;">Étudiant</th>
                            <th style="font-weight:600;">Groupe</th>
                            <th style="font-weight:600;width:70px;text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="annuaireCorps"></tbody>
                </table>
                <p id="annuaireVide" class="text-center py-4 d-none" style="color:#bbb;font-size:13px;">
                    Aucun étudiant trouvé.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const ROUTE_ETUDIANTS   = '{{ route("scan.etudiants", $centreId) }}';
const ROUTE_SEANCE_BASE = '{{ url("/salles") }}/';
const ROUTE_SCAN        = '{{ route("scan.badge") }}';
const CSRF              = '{{ csrf_token() }}';

let mode          = 'entree';
let historique    = [];
let tousEtudiants = [];

// ── Mode entrée / sortie ─────────────────────────────────────────────────────
function setMode(m) {
    mode = m;
    const e = document.getElementById('btnEntree');
    const s = document.getElementById('btnSortie');
    e.style.background = m === 'entree' ? 'var(--fonce)' : '#eee';
    e.style.color      = m === 'entree' ? '#fff' : 'var(--fonce)';
    s.style.background = m === 'sortie' ? 'var(--fonce)' : '#eee';
    s.style.color      = m === 'sortie' ? '#fff' : 'var(--fonce)';
}

// ── Scan sur Entrée ──────────────────────────────────────────────────────────
document.getElementById('badgeInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') scanner();
});

async function scanner() {
    const badge   = document.getElementById('badgeInput').value.trim();
    const salleId = document.getElementById('salleSelect').value;

    if (!badge)   { afficherResultat('orange', '!', 'Saisir un badge', ''); return; }
    if (!salleId) { afficherResultat('orange', '!', 'Sélectionner une salle', ''); return; }

    try {
        const res  = await fetch(ROUTE_SCAN, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ badge_uid: badge, salle_id: salleId, mode }),
        });
        const data = await res.json();

        const iconMap  = { vert: '✓', rouge: '✗', orange: '!' };
        const titreMap = { vert: 'ACCÈS AUTORISÉ', rouge: 'ACCÈS REFUSÉ', orange: 'ATTENTION' };
        const bgMap    = { vert: '#e8f5e9', rouge: '#ffebee', orange: '#fff8e1' };

        afficherResultat(data.couleur, iconMap[data.couleur], titreMap[data.couleur], data.message, bgMap[data.couleur]);
        ajouterHistorique(badge, data);

        document.getElementById('badgeInput').value = '';
        setTimeout(() => document.getElementById('badgeInput').focus(), 100);
    } catch (err) {
        afficherResultat('orange', '!', 'Erreur réseau', err.message);
    }
}

function afficherResultat(couleur, icon, titre, message, bg) {
    const div = document.getElementById('resultat');
    div.className          = 'rounded-3 p-4 mt-3 text-center';
    div.style.background   = bg || '#f5f5f5';
    document.getElementById('resultatIcon').textContent    = icon;
    document.getElementById('resultatTitre').textContent   = titre;
    document.getElementById('resultatMessage').textContent = message;
}

function ajouterHistorique(badge, data) {
    const heure = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    historique.unshift({ badge, message: data.message, couleur: data.couleur, mode, heure });
    if (historique.length > 30) historique.pop();
    renderHistorique();
}

function renderHistorique() {
    const el = document.getElementById('historique');
    if (!historique.length) {
        el.innerHTML = '<p class="text-center py-3" style="color:#bbb;font-size:12px;">Aucun passage.</p>';
        return;
    }
    const bgMap = { vert: '#e8f5e9', rouge: '#ffebee', orange: '#fff8e1' };
    const icMap = { vert: '✓', rouge: '✗', orange: '!' };
    el.innerHTML = historique.map(h => `
        <div class="d-flex align-items-start gap-2 p-2 rounded-3 mb-1" style="background:${bgMap[h.couleur] || '#f5f5f5'}">
            <span>${icMap[h.couleur] || '•'}</span>
            <div class="flex-fill" style="min-width:0;">
                <div style="font-family:monospace;font-size:10px;color:#888;">${h.badge}</div>
                <div style="font-size:11px;word-break:break-word;">${h.message}</div>
            </div>
            <div style="font-size:10px;color:#aaa;white-space:nowrap;">${h.heure}</div>
        </div>
    `).join('');
}

function viderHistorique() { historique = []; renderHistorique(); }

// ── Séance en cours ──────────────────────────────────────────────────────────
async function chargerSeance() {
    const salleId = document.getElementById('salleSelect').value;
    const panneau = document.getElementById('seancePanneau');

    if (!salleId) {
        panneau.innerHTML = `
            <div class="text-center py-4" style="color:#bbb;font-size:13px;">
                <i class="bi bi-door-open" style="font-size:28px;"></i>
                <p class="mt-2 mb-0">Sélectionner une salle pour voir la séance en cours.</p>
            </div>`;
        return;
    }

    panneau.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary" role="status"></div></div>';

    try {
        const res  = await fetch(ROUTE_SEANCE_BASE + salleId + '/seance-courante', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const data = await res.json();
        renderSeance(data.seance, panneau);
    } catch (err) {
        panneau.innerHTML = '<p class="text-danger text-center py-3" style="font-size:12px;">Impossible de charger la séance.</p>';
    }
}

function renderSeance(s, panneau) {
    if (!s) {
        panneau.innerHTML = `
            <div class="d-flex align-items-center gap-3 rounded-3 p-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                <i class="bi bi-unlock-fill" style="font-size:28px;color:#166534;"></i>
                <div>
                    <div style="font-weight:700;color:#166534;font-size:14px;">Aucune séance récente</div>
                    <div style="font-size:12px;color:#15803d;">Pas de séance dans les 4 dernières heures pour cette salle.</div>
                </div>
            </div>`;
        return;
    }

    // Séance terminée : panneau de consultation seulement
    if (s.statut === 'terminee') {
        const scanInfo = s.heure_scan_entree
            ? `<div style="font-size:12px;color:#6b7280;margin-top:6px;">
                  <i class="bi bi-clock me-1"></i>Scan entrée prof : <strong>${s.heure_scan_entree}</strong>
                  &nbsp;·&nbsp; Dernier scan sortie : <strong>${s.heure_scan_sortie || '—'}</strong>
               </div>`
            : '';
        panneau.innerHTML = `
            <div class="rounded-3 p-3" style="background:#f3f4f6;border:1px solid #d1d5db;">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <span class="fw-bold" style="font-size:15px;color:#374151;">${s.matiere_code}</span>
                        <span style="font-size:13px;color:#666;margin-left:6px;">${s.matiere_nom}</span>
                    </div>
                    <span class="badge rounded-pill" style="background:#6b7280;font-size:11px;">Terminée</span>
                </div>
                <div class="mt-2" style="font-size:12px;color:#555;">
                    <i class="bi bi-clock me-1"></i>${s.debut} – ${s.fin}
                    &nbsp;·&nbsp;<i class="bi bi-tag me-1"></i>${s.type}
                    &nbsp;·&nbsp;<i class="bi bi-person me-1"></i>${s.professeur}
                </div>
                ${scanInfo}
                <div class="mt-2 rounded-3 p-2" style="background:#fef3c7;border:1px solid #fde68a;font-size:12px;color:#92400e;">
                    <i class="bi bi-clock me-1"></i>Séance terminée — scan de sortie professeur accepté jusqu'à 4h après la fin.
                </div>
            </div>`;
        return;
    }

    const statutColor = { en_cours: '#16a34a', planifiee: '#d97706' };
    const statutLabel = { en_cours: 'En cours', planifiee: 'À venir (< 1h)' };

    const groupesPills = s.groupes.map(g =>
        `<span class="badge rounded-pill me-1" style="background:var(--marron);font-size:10px;">${g.nom}</span>`
    ).join('');

    const pauseHtml = s.pause_active ? `
        <div class="rounded-3 p-2 mt-2" style="background:#fff8e1;border:1px solid #fde68a;font-size:12px;">
            <i class="bi bi-exclamation-triangle-fill me-1" style="color:#d97706;"></i><strong>Pause professeur</strong> — reprise à ${s.pause_fin}. Entrées bloquées jusqu'à la reprise.
        </div>` : '';

    panneau.innerHTML = `
        <div class="rounded-3 p-3" style="background:#fafafa;border:1px solid #e5e7eb;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <span class="fw-bold" style="font-size:15px;color:var(--fonce);">${s.matiere_code}</span>
                    <span style="font-size:13px;color:#666;margin-left:6px;">${s.matiere_nom}</span>
                </div>
                <span class="badge rounded-pill" style="background:${statutColor[s.statut] || '#888'};font-size:11px;">
                    ${statutLabel[s.statut] || s.statut}
                </span>
            </div>
            <div class="mt-2" style="font-size:12px;color:#555;">
                <i class="bi bi-clock me-1"></i>${s.debut} – ${s.fin}
                &nbsp;·&nbsp;<i class="bi bi-tag me-1"></i>${s.type}
                &nbsp;·&nbsp;<i class="bi bi-person me-1"></i>${s.professeur}
            </div>
            <div class="mt-2">
                <span style="font-size:11px;color:#888;margin-right:4px;">Groupes concernés :</span>
                ${groupesPills || '<em style="font-size:11px;color:#bbb;">aucun</em>'}
            </div>
            ${pauseHtml}

            <div class="mt-3 pt-3" style="border-top:1px solid #ececec;">
                <div style="font-size:11px;font-weight:700;color:#888;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em;">
                    Contraintes actives
                </div>
                <div style="font-size:12px;display:flex;flex-direction:column;gap:5px;">
                    <div><i class="bi bi-check-circle-fill me-1" style="color:#16a34a;"></i>Étudiant d'un groupe concerné → <strong>Accès autorisé</strong></div>
                    <div><i class="bi bi-x-circle-fill me-1" style="color:#dc2626;"></i>Étudiant d'un autre groupe → <strong>Accès refusé</strong></div>
                    <div><i class="bi bi-x-circle-fill me-1" style="color:#dc2626;"></i>Étudiant d'un autre centre → <strong>Accès refusé</strong></div>
                    <div><i class="bi bi-x-circle-fill me-1" style="color:#dc2626;"></i>Absent depuis &gt; 15 min (sortie temp.) → <strong>Réentrée refusée</strong></div>
                    <div><i class="bi bi-exclamation-triangle-fill me-1" style="color:#d97706;"></i>Sortie avec &gt; 10 min avant la fin → <strong>Sortie temporaire (15 min max)</strong></div>
                    ${s.pause_active ? '<div><i class="bi bi-lock-fill me-1" style="color:#6b7280;"></i>Pause en cours → <strong>Entrées bloquées</strong></div>' : ''}
                </div>
            </div>
        </div>`;
}

// ── Annuaire étudiants ───────────────────────────────────────────────────────
async function chargerEtudiants() {
    try {
        const res  = await fetch(ROUTE_ETUDIANTS, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const data = await res.json();
        tousEtudiants = data.etudiants || [];
        document.getElementById('annuaireLoading').classList.add('d-none');
        document.getElementById('annuaireWrap').classList.remove('d-none');
        filtrerAnnuaire();
    } catch (err) {
        document.getElementById('annuaireLoading').innerHTML =
            '<p class="text-danger" style="font-size:12px;">Impossible de charger les étudiants.</p>';
    }
}

function filtrerAnnuaire() {
    const recherche = document.getElementById('rechercheAnnuaire').value.toLowerCase();
    const optionId  = document.getElementById('filtreGroupe').value;

    const filtres = tousEtudiants.filter(e => {
        const texte = `${e.nom} ${e.prenom} ${e.badge_uid} ${e.matricule}`.toLowerCase();
        return (!recherche || texte.includes(recherche))
            && (!optionId || String(e.option_id) === optionId);
    });

    document.getElementById('annuaireCompteur').textContent = filtres.length;

    const corps = document.getElementById('annuaireCorps');
    const vide  = document.getElementById('annuaireVide');

    if (!filtres.length) {
        corps.innerHTML = '';
        vide.classList.remove('d-none');
        return;
    }
    vide.classList.add('d-none');

    corps.innerHTML = filtres.map(e => `
        <tr>
            <td>
                <code style="font-size:11px;background:#f5f5f5;padding:2px 6px;border-radius:4px;">${e.badge_uid}</code>
            </td>
            <td style="font-size:12px;">
                <span class="fw-semibold">${e.nom}</span> ${e.prenom}
                <div style="font-size:10px;color:#aaa;">${e.matricule}</div>
            </td>
            <td style="font-size:11px;color:#555;">${e.groupe}</td>
            <td class="text-center">
                <button class="btn btn-sm rounded-3 py-0 px-2"
                        style="font-size:10px;background:var(--marron);color:#fff;"
                        onclick="utiliserBadge('${e.badge_uid}')"
                        title="Tester ce badge">
                    <i class="bi bi-upc-scan"></i> Tester
                </button>
            </td>
        </tr>
    `).join('');
}

function utiliserBadge(uid) {
    const input = document.getElementById('badgeInput');
    input.value = uid;
    input.style.borderColor = 'var(--marron)';
    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
    input.focus();
}

// ── Init ─────────────────────────────────────────────────────────────────────
window.onload = function () {
    document.getElementById('badgeInput').focus();
    chargerEtudiants();
};

// Rafraîchissement auto de la séance toutes les 60 s si une salle est sélectionnée
setInterval(function () {
    if (document.getElementById('salleSelect').value) chargerSeance();
}, 60000);
</script>
@endpush
@endsection
