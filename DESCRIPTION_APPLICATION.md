# GASA-ERP — Documentation Fonctionnelle et Technique

> Système de gestion intégré pour établissements d'enseignement supérieur.
> Contrôle d'accès par badge RFID/QR Code, suivi d'assiduité en temps réel, gestion pédagogique et emplois du temps.

---

## 1. Rôles et Périmètres d'Action

```mermaid
flowchart LR
    A([Directeur Général\nROLE_ADMIN]) -->|Accès total\ntous centres| SYS[(GASA-ERP)]
    B([Responsable Centre\nROLE_RESPONSABLE_CENTRE]) -->|Son centre uniquement| SYS
    C([Professeur\nROLE_PROFESSEUR]) -->|Son centre\nSes séances| SYS
    D([Étudiant]) -->|Badge RFID/QR| SCAN[Borne de scan]
    SCAN --> SYS
```

| Rôle | Peut créer | Peut modifier | Restriction |
|:---|:---|:---|:---|
| Directeur (Admin) | Tout | Tout | Aucune |
| Responsable | Groupes, séances, étudiants | Son centre | Un seul centre |
| Professeur | — | Clôture de ses séances | Ses séances uniquement |
| Étudiant | — | — | Scan badge uniquement |

---

## 2. Modèle de Données (Entités principales)

```mermaid
erDiagram
    CENTRES ||--o{ USERS : "appartient à"
    CENTRES ||--o{ SALLES : "contient"
    CENTRES ||--o{ OPTIONS : "accueille"

    FILIERES ||--o{ FILIERE_OPTIONS : "subdivise en"
    FILIERE_OPTIONS ||--o{ NIVEAUX : "comprend"
    NIVEAUX ||--o{ MATIERES : "enseigne"

    OPTIONS {
        int filiere_option_id
        int niveau_id
        int centre_id
        int annee_scolaire_id
        string nom
    }

    SEANCES {
        datetime debut
        datetime fin
        enum statut
        enum type
        int emploi_du_temps_id
        datetime heure_scan_professeur
        int nb_presents_valide
        datetime cloture_validee_at
        int cloture_validee_par
    }

    USERS ||--o{ SEANCES : "enseigne"
    SALLES ||--o{ SEANCES : "héberge"
    MATIERES ||--o{ SEANCES : "est enseignée"
    OPTIONS }o--o{ SEANCES : "option_seance"

    ETUDIANTS ||--o{ INSCRIPTIONS : "s'inscrit"
    OPTIONS ||--o{ INSCRIPTIONS : "accueille"
    INSCRIPTIONS ||--o{ PRESENCES : "génère"
    SEANCES ||--o{ PRESENCES : "enregistre"
    PRESENCES ||--o{ SORTIES_TEMPORAIRES : "peut avoir"

    SEANCES ||--o{ CONTESTATIONS_HORAIRES : "peut faire l'objet de"
    EMPLOIS_DU_TEMPS ||--o{ SEANCES : "génère"
```

---

## 3. Cycle de Vie d'une Séance

```mermaid
stateDiagram-v2
    [*] --> planifiee : Création par le responsable\nou import EDT

    planifiee --> en_cours : Début automatique (heure atteinte)\nOU badge prof scanné\nOU bouton "Démarrer"

    en_cours --> en_cours : Pause 30 min déclarée\n(fenêtres : 10h–11h ou 15h–16h)

    en_cours --> terminee : Heure de fin dépassée (auto)\nOU bouton "Terminer"

    planifiee --> terminee : Fin dépassée sans démarrage\n→ vases communicants activés\n→ rattrapage auto planifié

    terminee --> terminee : [Clôture en attente]

    terminee --> cloturee : Professeur valide :\nconfirme nb présents\n→ attestation à l'administration

    terminee --> contestee : Professeur conteste la durée\n→ réclamation envoyée à l'admin

    cloturee --> [*]
    contestee --> [*] : Admin statue (acceptée/rejetée)
```

> **Règle de clôture :** Seul le **professeur de la séance** (ou le Directeur) peut valider la clôture. Le responsable de centre n'a pas ce droit. La clôture est l'attestation du professeur envers l'administration que la séance a bien eu lieu avec N présents.

---

## 4. Flux de Scan RFID/QR Code

### 4.1 Identification du porteur de badge

```mermaid
flowchart TD
    SCAN[Badge scanné] --> CHECK_STAFF{Badge dans\ntable users ?}
    CHECK_STAFF -->|OUI — Staff| STAFF_FLOW[Accès libre\naucune restriction horaire]
    STAFF_FLOW --> PROF_CHECK{Prof avec\nséance dans\ncette salle ?}
    PROF_CHECK -->|OUI| LOG_PROF[Log heure_scan_professeur\nStatut → en_cours]
    PROF_CHECK -->|NON| ACCES_VERT_STAFF[✅ Accès autorisé]
    LOG_PROF --> ACCES_VERT_STAFF

    CHECK_STAFF -->|NON| CHECK_ETU{Badge dans\ntable etudiants ?}
    CHECK_ETU -->|NON| BADGE_INC[❌ Badge inconnu]
    CHECK_ETU -->|OUI| CHECK_INSCR{Inscription\nactive cette année ?}
    CHECK_INSCR -->|NON| NON_INSCR[❌ Non inscrit]
    CHECK_INSCR -->|OUI| CHECK_CENTRE{Bon centre ?}
    CHECK_CENTRE -->|NON| MAUVAIS_CTR[❌ Mauvais centre]
    CHECK_CENTRE -->|OUI| CHECK_SEANCE{Séance pour\nce groupe dans\ncette salle ?}
    CHECK_SEANCE -->|NON| FERME[❌ Salle fermée\nAucun cours prévu]
    CHECK_SEANCE -->|OUI| ENTREE_FLOW[→ Flux Entrée Étudiant]
```

### 4.2 Entrée étudiant (mode entrée)

```mermaid
flowchart TD
    A[Mode ENTRÉE] --> B{Retard ?\ndiff = now - debut_seance}
    B -->|diff > 15 min| RETARD[❌ Entrée refusée\nRetard bloqué]
    B -->|diff ≤ 15 min| C{Pause prof\nen cours ?}
    C -->|OUI| PAUSE[🟠 Accès suspendu\nReprise à HH:MM]
    C -->|NON| D{Présence déjà\nenregistrée ?}
    D -->|NON| CREATE[✅ Présence créée\nStatut = present\nheure_entree = now]
    D -->|OUI — en sortie temporaire| E{Durée absence\n> 15 min ?}
    E -->|OUI| REFUS[❌ Réentrée refusée\nPresence → insuffisante]
    E -->|NON| RENTREE[✅ Réentrée OK\nSortie temporaire clôturée]
    D -->|OUI — déjà présent| DEJA[🟠 Déjà enregistré]
```

### 4.3 Sortie étudiant (mode sortie)

```mermaid
flowchart TD
    A[Mode SORTIE] --> B{Séance\nen cours ?}
    B -->|NON| OK_LIBRE[✅ Sortie enregistrée\naccès libre]
    B -->|OUI| C{Présence\nenregistrée ?}
    C -->|NON| ERR[🟠 Pas d'entrée connue]
    C -->|OUI| D{Temps restant\navant fin séance > 10 min ?}
    D -->|OUI| TEMP[🟠 Sortie TEMPORAIRE\nRetour obligatoire sous 15 min]
    D -->|NON| DEF[✅ Sortie définitive\nTolérance fin de séance]
```

---

## 5. Règles de Gestion Métier

### RG-01 — Accès à une salle (étudiant)

| Condition | Résultat |
|:---|:---|
| Badge inconnu | ❌ Refusé — badge_inconnu |
| Pas d'inscription active | ❌ Refusé — non_inscrit |
| Étudiant d'un autre centre | ❌ Refusé — mauvais_centre |
| Aucune séance pour son groupe dans la salle | ❌ Refusé — aucun_cours |
| Séance trouvée mais retard > 15 min | ❌ Refusé — retard_bloqué |
| Séance en pause prof | 🟠 Suspendu — pause_prof |
| Séance trouvée, dans les temps | ✅ Autorisé |

**Fenêtre d'accès anticipé :** L'étudiant peut entrer jusqu'à **1 heure avant** le début de la séance (si statut = `planifiee` et dans l'heure à venir).

### RG-02 — Accès staff (professeur, responsable, admin)

- Accès libre à toute salle, à tout moment, sans restriction horaire.
- Si le badge correspond au professeur affecté à une séance dans la salle scannée : le champ `heure_scan_professeur` est automatiquement renseigné et la séance passe en `en_cours`.

### RG-03 — Sortie temporaire étudiant

- Autorisée **une seule fois** par séance par étudiant.
- L'étudiant a **15 minutes** pour revenir.
- Si > 15 min : réentrée refusée, présence marquée `presence_insuffisante`.
- La sortie définitive (fin de cours) est tolérée si la séance se termine dans ≤ 10 min.

### RG-04 — Pause professeur

```
Conditions d'autorisation :
  ✓ Séance en statut en_cours
  ✓ Heure actuelle dans 10h00–11h00 OU 15h00–16h00
  ✗ Séances du soir (début ≥ 17h30) → aucune pause
  ✗ Groupes Master (M1, M2, Master) → aucune pause
  Durée fixe : 30 minutes
```

Pendant une pause, tout scan d'étudiant en entrée retourne le statut `pause_prof` (🟠 orange).

### RG-05 — HP avant TPE

Lors de la planification, une séance de type **TPE** ne peut être créée que si le volume total de séances HP (terminées + planifiées) couvre **100%** du quota `hp_initial` de la matière.

```
HPcouvert = Σ durées(HP terminées) + Σ durées(HP planifiées)
Si HPcouvert < hp_initial → erreur bloquante, TPE refusé
```

### RG-06 — Vases Communicants (HP manqué)

Déclenchement : séance HP terminée **sans** scan professeur (prof absent) ET pas une composition.

```mermaid
flowchart LR
    ABSENT[Prof absent\nheure_scan_professeur = NULL] --> TRIGGER[Fin séance HP auto]
    TRIGGER --> VC[Vases Communicants\npar centre et par groupe]
    VC --> HP[hp_restant += durée_séance\nRattrapage obligatoire]
    VC --> TPE[tpe_dynamique -= durée_séance\nSacrifice TPE]
    TRIGGER --> RATT[Planification automatique\ndu rattrapage\nMême créneau +1 sem\nmax 8 tentatives]
```

- La matière ne peut être déclarée terminée que si `hp_restant = 0` ET `tpe_dynamique = 0`.
- Si aucun créneau libre dans les 8 prochaines semaines : les heures restent visibles dans le suivi des quotas.

### RG-07 — Absents automatiques

Lors de la clôture d'une séance (`terminee`), tous les étudiants inscrits dans les groupes rattachés à la séance qui n'ont pas de présence enregistrée reçoivent automatiquement le statut `absent`.

### RG-08 — Clôture de séance (cahier de texte numérique)

```mermaid
sequenceDiagram
    participant P as Professeur
    participant APP as GASA-ERP
    participant A as Administration (Admin)

    P->>APP: Séance terminée → bouton "Valider clôture"
    APP->>APP: Vérifie statut = terminee
    APP->>APP: Vérifie professeur_id = user.id
    P->>APP: Saisit nb_présents confirmé
    APP->>APP: Enregistre nb_presents_valide\ncloture_validee_at = now\ncloture_validee_par = prof.id
    APP-->>P: Clôture validée ✅
    APP-->>A: Visible en administration
```

> ⚠️ **Règle critique :** Le **responsable de centre ne peut pas valider la clôture**. Seul le professeur de la séance (ou le Directeur Général) peut attester la réalité du cours auprès de l'administration.

### RG-09 — Contestation horaire

```mermaid
sequenceDiagram
    participant P as Professeur
    participant APP as GASA-ERP
    participant A as Directeur (Admin)

    P->>APP: Conteste la durée calculée\n(motif + durée revendiquée)
    APP->>APP: Vérifie aucune contestation en_attente\nCrée ContestationHoraire\nstatut = en_attente
    APP-->>P: Réclamation envoyée ✅
    A->>APP: Examine la réclamation
    alt Acceptée
        A->>APP: Statue "acceptée" + note admin
        APP-->>P: Durée corrigée
    else Rejetée
        A->>APP: Statue "rejetée" + motif
        APP-->>P: Refus notifié
    end
```

Conditions : séance en statut `terminee`, pas de contestation `en_attente` déjà ouverte.

### RG-10 — Contrôle de capacité

Lors de la création d'une séance, le total des étudiants actifs de tous les groupes rattachés est comparé à la capacité de la salle :

```
total_inscrits = Σ inscriptions_actives(option_ids)
Si total_inscrits > salle.capacite → erreur bloquante
```

### RG-11 — Conflit de salle

Une salle ne peut accueillir qu'une seule séance à la fois. La vérification porte sur les séances `planifiee` ou `en_cours`. Le chevauchement est détecté par comparaison des plages `[debut, fin]`.

### RG-12 — Synchronisation automatique des statuts

À chaque chargement de la page Planning, le système synchronise automatiquement les statuts :

```
planifiee → en_cours  : si debut ≤ now < fin
planifiee/en_cours → terminee : si fin ≤ now
                                → vases communicants si prof absent
                                → absents automatiques
```

---

## 6. Emploi du Temps — Import et Grille

### 6.1 Flux d'import

```mermaid
flowchart TD
    UPLOAD[Fichier uploadé\n.csv / .xlsx] --> DETECT{Extension ?}
    DETECT -->|xlsx ou xls| XLSX[Lecture XLSX native\nZipArchive + SimpleXML\nsans dépendance externe]
    DETECT -->|csv ou txt| CSV[Lecture CSV\nséparateur point-virgule]
    XLSX --> CONVERT[Converti en CSV interne]
    CONVERT --> PARSE
    CSV --> PARSE[Parsing en-tête :\nNumero; Orientation;\nDateDebut; DateFin; ---]
    PARSE --> LIGNES[Parsing lignes :\nJour;Debut;Fin;Matiere\nProfesseur;Type;Groupe;Salle]
    LIGNES --> MATCH[Résolution floue :\n- Professeur LIKE %nom%\n- Matière par code ou nom LIKE]
    MATCH --> CREATE[Création EmploiDuTemps\n+ Séances pour chaque date\nde la période]
    CREATE --> DONE[✅ EDT importé\nRedirigé sur la grille]
```

### 6.2 Grille d'affichage

La grille reproduit le format matriciel des emplois du temps papier de l'établissement :

- Axe vertical : **12 tranches horaires** de 07h30 à 18h00
- Axe horizontal : **LUNDI à SAMEDI**
- Lignes spéciales `RÉCRÉATION` à 10h–10h30 et 12h–13h
- Algorithme **rowspan** : un cours de 3h occupe visuellement 3 lignes (même si récréation au milieu)

### 6.3 Format CSV attendu

```
# En-tête (clé;valeur)
Numero;1
Orientation;GE2 (SIL2)
DateDebut;23/06/2026
DateFin;04/07/2026
---
Jour;Debut;Fin;Matiere;Professeur;Type;Groupe;Salle
LUNDI;07:30;08:00;TP-RM;DEGBOE;HP;GE-SI L1;Salle 101
LUNDI;08:00;11:00;ALGO;AKPONNA;HP;;
MARDI;13:00;16:00;BDD;MONTCHO;HP;GE-SIL L1;Labo Info A
```

Règles de résolution à l'import :
- **Professeur** : recherche `LIKE '%nom%'` dans les users du centre avec rôle ROLE_PROFESSEUR
- **Matière** : code exact (insensible à la casse) OU nom `LIKE '%code%'`
- **Salle** : nom `LIKE '%nom%'` dans le centre, sinon salle par défaut du formulaire
- **Groupe** : nom ou code `LIKE` dans les options du centre, sinon option choisie dans le formulaire

---

## 7. Flux Pédagogique Complet

```mermaid
flowchart TD
    A[1. Directeur crée\nFilières / Options péda. / Niveaux] --> B
    B[2. Responsable crée\nGroupes-classes de l'année\n+ Inscrit les étudiants] --> C
    C[3. Quotas HP/TPE\ninitiali sés par centre] --> D
    D[4. Import EDT\nou planification manuelle\ndes séances] --> E
    E[5. Déroulement\nScan badge prof → en_cours\nScan badge etu → présence] --> F
    F[6. Fin de séance\nAbsents automatiques\nVases communicants si prof absent] --> G
    G[7. Clôture par le professeur\nAttestation nb présents\nà l'administration] --> H
    H[8. Contestation optionnelle\nProfesseur → Admin\nsur la durée calculée]
```

---

## 8. Statuts de Présence

| Statut | Signification |
|:---|:---|
| `present` | Entré à temps, sorti correctement ou toujours en salle |
| `absent` | Aucune entrée enregistrée (mis automatiquement à la clôture) |
| `retard` | Entré mais après la tolérance (calculé selon heure_entree) |
| `presence_insuffisante` | Réentrée refusée après sortie temporaire > 15 min |
| `sortie_anticipee_toleree` | Sorti dans les 10 dernières minutes de cours |

---

## 9. Base de Données — Tables et Champs Clés

### Tables de référentiel

| Table | Rôle |
|:---|:---|
| `annees_scolaires` | Années académiques ; une seule `active = true` à la fois |
| `centres` | Sites physiques (Gbégamey, Akpakpa, Porto-Novo, Calavi) |
| `filieres` | Domaines (ex: Génie Électrique) |
| `filiere_options` | Spécialités (ex: SIL, RIT) |
| `niveaux` | Paliers (L1, L2, M1…) |
| `matieres` | Unités d'enseignement avec quota `hp_initial` et `tpe_initial` |

### Tables opérationnelles

| Table | Rôle |
|:---|:---|
| `users` | Staff : admin, responsables, professeurs — champ `badge_uid` pour le scan |
| `options` | Groupes-classes liés à une année, un centre, une spécialité |
| `etudiants` | Profils permanents — champ `badge_uid` pour le scan |
| `inscriptions` | Lien Étudiant ↔ Groupe, avec statut (`actif`, `redoublant`…) |
| `salles` | Salles physiques avec `type` et `capacite` |
| `equipements` | Inventaire des équipements par salle |
| `seances` | Cours : `debut`, `fin`, `type` (HP/TPE), `statut`, `emploi_du_temps_id` |
| `emplois_du_temps` | Périodes d'EDT importées (numero, orientation, date_debut, date_fin) |

### Tables de suivi

| Table | Rôle |
|:---|:---|
| `presences` | `heure_entree`, `heure_sortie_definitive`, `statut` par étudiant et séance |
| `sorties_temporaires` | Pauses étudiants : `heure_sortie`, `heure_rentree`, `rentree_refusee` |
| `matiere_centre_annee` | Quotas HP/TPE restants par matière, centre et année |
| `option_seance` | Pivot : une séance peut concerner plusieurs groupes |
| `matiere_professeur` | Habilitations : quel professeur enseigne quelle matière |
| `contestations_horaires` | Réclamations de durée prof → admin, statut `en_attente/acceptee/rejetee` |

---

## 10. Points d'Attention et Contraintes

| Contrainte | Détail |
|:---|:---|
| Badge RFID/QR | Deux tables distinctes : `users.badge_uid` (staff) et `etudiants.badge_uid` (étudiants). Priorité au staff lors d'un scan. |
| Import XLSX natif | Implémenté via `ZipArchive` + `SimpleXML`. Aucun package externe requis (pas de phpspreadsheet). |
| Séparateur CSV | Point-virgule `;` obligatoire. |
| Retard bloqué | Strictement > 15 min après le début de la séance. |
| Clôture | Professeur uniquement (pas le responsable de centre). |
| Rattrapage auto | Cherche le même créneau +1 semaine, jusqu'à +8 semaines. Échec silencieux : les heures restent dans le quota. |
| Compositions | Séances `est_composition = true` : les vases communicants ne s'appliquent pas si le prof est absent. |
