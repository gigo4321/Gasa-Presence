# GASA-ERP — Règles de gestion implémentées

---

## 1. Authentification & Rôles

| Rôle | Accès |
|---|---|
| `ROLE_ADMIN` (Directeur Général) | Accès complet à tout le système, tous les centres |
| `ROLE_RESPONSABLE_CENTRE` | Accès restreint à son centre uniquement |
| `ROLE_PROFESSEUR` | Accès à ses séances uniquement ; redirigé vers le planning de son centre |

- Un utilisateur non authentifié est redirigé vers `/login`.
- Le professeur ne voit que ses propres séances (filtre automatique `prof_id = user.id`).
- Le responsable de centre ne peut pas accéder aux données d'un autre centre (abort 403).

---

## 2. Référentiel pédagogique

### Hiérarchie
```
Filière → FiliereOption → Niveau → Matière
```
- Une matière est rattachée à un niveau, donc à une filière via `filiere_id`.
- Une matière possède deux quotas horaires globaux : `hp_initial` (Heures Professeur) et `tpe_initial` (Travaux Personnels Encadrés).
- Un `code` de matière doit être unique par filière.

### Groupes (Options)
- Un groupe (`Option`) appartient à un centre, une filière-option, un niveau et une année scolaire.
- Un groupe peut avoir un responsable nommé (`responsable_nom`).
- Un étudiant est inscrit dans un groupe via la table `inscriptions` (statut : `actif` / `inactif`).

---

## 3. Séances — Création

### Types
- **HP** (Heures Professeur) : un professeur est obligatoire. L'assiduité des étudiants est mesurée par badge RFID.
- **TPE** (Travaux Personnels Encadrés) : aucun professeur assigné. Les étudiants travaillent de façon autonome.

### Durées autorisées
- Uniquement **3h** ou **4h** par séance.

### Règle HP avant TPE
- Un TPE ne peut être planifié pour une matière/centre/année que si `hp_restant = 0` (toutes les HP de la matière sont terminées).
- Si des HP sont encore dues, la création de TPE est bloquée avec le message d'erreur indiquant le nombre d'heures restantes.

### Vérifications à la création
1. **Capacité de salle** : le nombre total d'étudiants actifs dans les groupes sélectionnés ne peut pas dépasser la capacité de la salle.
2. **Conflit de salle** : aucune autre séance planifiée ou en cours ne doit se chevaucher sur le même créneau pour la même salle.
3. **Séance inter-centres** : automatiquement détectée si des groupes de plusieurs centres différents sont sélectionnés.

---

## 4. Cycle de vie d'une séance

```
planifiee → en_cours → terminee
                     ↘ annulee
```

### Transition automatique (syncStatuts)
Déclenchée à chaque chargement du planning :
- `planifiee → en_cours` : si `debut <= now < fin`.
- `planifiee / en_cours → terminee` : si `fin <= now`.

### Démarrer manuellement
- Action réservée au bouton "Démarrer" dans l'interface.
- Enregistre `heure_scan_professeur = now()` (badge professeur).

### Terminer manuellement
- Action "Terminer" disponible pour une séance en cours.
- Déclenche les mêmes effets que la terminaison automatique (voir §5).

---

## 5. Effets à la terminaison d'une séance

### Pour toutes les séances
- Les étudiants inscrits dans les groupes attachés et **sans enregistrement de présence** reçoivent automatiquement le statut `absent`.

### Pour les séances HP
- Si le professeur **a badgé** (`heure_scan_professeur` renseigné) :
  - `hp_restant` du quota matière/centre/année est décrémenté du nombre d'heures de la séance.
- Si le professeur **n'a pas badgé** (absent) :
  - Les vases communicants s'appliquent : `hp_restant` reste inchangé, `tpe_dynamique` est réduit en compensation.
  - Une **séance de rattrapage** est automatiquement planifiée : même créneau horaire la semaine suivante (jusqu'à +8 semaines). Si aucun créneau libre dans cette fenêtre, aucun rattrapage automatique n'est créé.

### Pour les séances TPE
- Clôture automatique immédiate (`cloture_validee_at = now()`) sans validation professeur.

---

## 6. Quota HP/TPE — Vases communicants

- Chaque matière dispose d'un quota `hp_initial` et `tpe_initial` par centre et par année.
- Le suivi est stocké dans `matiere_centre_annee` (`hp_restant`, `tpe_dynamique`).
- Quand un prof est absent :
  - Le modèle `appliquerVasesCommunicants(h)` est appelé.
  - `tpe_dynamique` est réduit de `h` heures (les heures manquées réduisent le volume TPE disponible).
- Les TPE sont **débloqués** uniquement quand `hp_restant = 0`.

---

## 7. Pauses

### Déclenchement
- Disponible uniquement pour une séance de type HP avec statut `en_cours`.
- Le professeur peut déclencher la pause **à n'importe quel moment** compris dans la plage horaire de la séance (`debut ≤ now < fin`).
- **Une seule pause active à la fois** : si une pause est déjà en cours (`heure_fin_pause > now`), une nouvelle est refusée.

### Calcul
- Le système enregistre automatiquement **30 minutes fixes** à partir du moment du déclenchement.
- `heure_debut_pause = now()`, `heure_fin_pause = now() + 30 min`.
- `durees_pauses_minutes` est **incrémenté de 30** à chaque pause déclenchée (cumul sur toute la séance).

### Affichage
- Dans la carte de séance (planning) : "Effectif : Xh Ymin · Pause : Z min" visible pour les séances en cours et terminées avec scan.
- Dans la fiche de présence : "Scan entrée", "Pause totale", "Durée effective" affichés côte à côte.
- Badge "⏸ Pause jusqu'à H:i" visible tant que la pause est active.

---

## 8. Durée effective du professeur

### Formule
```
Durée effective = (Fin - Scan entrée) - durees_pauses_minutes
```

### Détail de la borne de fin
1. Si `cloture_validee_at` est renseigné → utilise la date de clôture.
2. Sinon, si `statut = terminee` → utilise `fin` (heure de fin planifiée).
1. Si `heure_scan_sortie_professeur` est renseigné → utilise l'heure du scan de sortie.
2. Sinon, si `cloture_validee_at` est renseigné → utilise la date de clôture (plafonné à l'heure de fin prévue `fin`).
3. Sinon, si `statut = terminee` → utilise `fin` (heure de fin planifiée).
3. Sinon (séance en cours) → utilise `now()`.

### Cas sans scan
- Si `heure_scan_professeur` est null (prof absent) → durée effective = **0 min**.

---

## 9. Présences étudiants (RFID)

### Scan badge
- `POST /scan/badge {uid, salle_id}` : le badge UID est résolu en étudiant, puis en inscription active pour la séance en cours dans la salle.
- Si présence déjà enregistrée : mise à jour de `heure_entree`.
- Si l'étudiant n'est pas inscrit dans un groupe de la séance → scan refusé.

### Statuts de présence
| Statut | Condition |
|---|---|
| `present` | Badge scanné dans les délais normaux |
| `absent` | Aucun scan enregistré (généré automatiquement à la terminaison) |
| `presence_insuffisante` | Durée de présence trop courte (sorties temporaires) |

### Sorties temporaires
- Un étudiant peut sortir et re-rentrer pendant la séance.
- Chaque sortie/rentrée est enregistrée dans `sorties_temporaires`.

---

## 10. Clôture de séance (validation professeur)

### Qui peut clôturer ?
- Le **professeur de la séance** ou l'**admin**.
- Le responsable de centre n'a **pas** ce droit (c'est le professeur qui atteste à l'administration).

### Conditions
- La séance doit avoir le statut `terminee`.
- Elle ne doit pas déjà avoir une clôture (`cloture_validee_at` null).

### Action
- Le professeur saisit le nombre d'étudiants réellement présents (`nb_presents_valide`).
- Valeur suggérée : le comptage RFID automatique, modifiable si nécessaire.
- Enregistre `cloture_validee_at = now()` et `cloture_validee_par = user.id`.

---

## 11. Tableaux de bord

### Dashboard Directeur Général (Admin)
- Vue consolidée de tous les centres.
- Statistiques globales : nombre de centres, étudiants actifs, séances du jour.
- Tableau par centre : étudiants, séances aujourd'hui, séances en cours, taux d'assiduité.
- Répartition par filière et activité récente (7 derniers jours).

### Dashboard Centre (Responsable / Admin)
- Statistiques du centre pour l'année sélectionnée.
- Inscrits actifs, séances aujourd'hui, séances en cours.
- Groupes actifs, professeurs, séances de la semaine.
- Navigation par année scolaire via un dropdown.

### Redirection automatique par rôle
- **Professeur** → redirigé vers le planning de ses séances (`seances.index` filtré sur son id).
- **Responsable de centre** → redirigé vers le dashboard de son centre.
- **Admin** → accède au dashboard Directeur Général.

---

## 12. Fiche de présence

### Contenu
- En-tête : matière, date, horaire, salle, centre, type de séance.
- Section HP : professeur, scan entrée, fin séance, pause totale (min), durée effective.
- Suivi HP : heures effectuées / restantes / prévues avec barre de progression.
- TPE disponibles pour le centre (vases communicants).
- Historique HP du professeur pour cette matière (toutes séances terminées).
- KPIs présence : inscrits, présents, absents, présence insuffisante.
- Panneau de validation de clôture (si séance terminée et non clôturée).
- Liste détaillée des présences avec heure d'entrée, statut, sorties temporaires.

### Export PDF
- Vue identique, optimisée pour l'impression.

---

## 13. Emplois du temps

- Un emploi du temps (`EmploiDuTemps`) appartient à un centre avec une date de début et de fin.
- Il peut contenir un `numero` et un `orientation_label`.
- Les séances peuvent être rattachées à un emploi du temps via `emploi_du_temps_id`.

---

## 14. Rattrapage automatique

- Déclenché uniquement pour les séances HP non assurées (prof absent).
- Cherche le même créneau la semaine suivante (J+7, J+14, …, J+56 = 8 semaines max).
- Même salle, même professeur, mêmes groupes, durée identique.
- Si conflit sur tous les créneaux → aucun rattrapage créé (les heures restent visibles via `hp_restant`).

---

## 15. Contestation d'horaire

- **Optionnel** : Un professeur peut contester la durée calculée par le système s'il estime qu'une erreur technique a eu lieu (ex: badge non détecté).
- **Champs requis** :
    - `duree_calculee_minutes` : Valeur du système au moment de la contestation.
    - `duree_contestee_minutes` : Valeur revendiquée par le professeur.
    - `motif` : Justification détaillée de l'anomalie.
- **Workflow** :
    1. Le professeur soumet la contestation (statut `en_attente`).
    2. L'administrateur examine les logs et le motif.
    3. L'administrateur approuve ou rejette avec une `admin_note`.
    4. Si approuvée, les compteurs de la table `matiere_centre_annee` doivent être ajustés manuellement.

---

*Dernière mise à jour : 2026-06-17*
