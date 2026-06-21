# GASA-ERP — Diagrammes UML

> Rendu avec l'extension **PlantUML** (VS Code) ou sur [plantuml.com/plantuml](https://www.plantuml.com/plantuml).

---

## 1. Diagramme de cas d'utilisation

```plantuml
@startuml cas_utilisation
left to right direction
skinparam actorStyle awesome
skinparam packageStyle rectangle

actor "Directeur Général"   as DG
actor "Responsable Centre"  as RC
actor "Agent / Secrétaire"  as AG
actor "Professeur"          as PR
actor "Étudiant (badge)"    as ET

rectangle "GASA-ERP" {

    package "Authentification" {
        usecase "Se connecter"    as UC_LOGIN
        usecase "Se déconnecter" as UC_LOGOUT
    }

    package "Référentiel" {
        usecase "Gérer filières / options / niveaux" as UC_FILIERE
        usecase "Gérer matières & quotas HP/TPE"     as UC_MATIERE
        usecase "Gérer années scolaires"              as UC_ANNEE
    }

    package "Gestion du Centre" {
        usecase "Gérer les groupes (options)"   as UC_GROUPE
        usecase "Gérer les étudiants"           as UC_ETU
        usecase "Importer étudiants (CSV)"      as UC_ETU_IMP
        usecase "Gérer les professeurs"         as UC_PROF
        usecase "Gérer les salles & équipements" as UC_SALLE
    }

    package "Planning" {
        usecase "Planifier une séance"          as UC_SEANCE
        usecase "Importer emploi du temps"      as UC_EDT
        usecase "Démarrer / Terminer séance"    as UC_SEANCE_RT
        usecase "Mettre en pause"               as UC_PAUSE
        usecase "Clôturer & valider séance"     as UC_CLOTURE
    }

    package "Présences" {
        usecase "Scanner badge étudiant"        as UC_SCAN
        usecase "Consulter fiche de présence"   as UC_FICHE
        usecase "Exporter fiche PDF"            as UC_PDF
    }

    package "Tableaux de bord" {
        usecase "Vue d'ensemble (multi-centre)" as UC_DASH_DIR
        usecase "Vue tableau de bord centre"    as UC_DASH_CTR
    }
}

DG  --> UC_LOGIN
DG  --> UC_FILIERE
DG  --> UC_MATIERE
DG  --> UC_ANNEE
DG  --> UC_DASH_DIR
DG  --> UC_DASH_CTR

RC  --> UC_LOGIN
RC  --> UC_GROUPE
RC  --> UC_ETU
RC  --> UC_ETU_IMP
RC  --> UC_PROF
RC  --> UC_SALLE
RC  --> UC_SEANCE
RC  --> UC_EDT
RC  --> UC_SEANCE_RT
RC  --> UC_CLOTURE
RC  --> UC_FICHE
RC  --> UC_PDF
RC  --> UC_DASH_CTR

AG  --> UC_LOGIN
AG  --> UC_ETU
AG  --> UC_SCAN
AG  --> UC_SEANCE_RT

PR  --> UC_LOGIN
PR  --> UC_SEANCE_RT
PR  --> UC_PAUSE
PR  --> UC_FICHE

ET  --> UC_SCAN

UC_ETU_IMP  .> UC_ETU  : <<include>>
UC_PAUSE    .> UC_SEANCE_RT : <<include>>
UC_PDF      .> UC_FICHE : <<include>>

@enduml
```

---

## 2. Diagrammes de séquence

### 2.1 Connexion au système

```plantuml
@startuml seq_connexion
actor Utilisateur
participant "Navigateur"    as B
participant "LoginController" as C
participant "Auth (Laravel)" as A
database    "Base de données" as DB

Utilisateur -> B : Saisit email + mot de passe
B -> C : POST /login
C -> A : attempt(credentials)
A -> DB : SELECT users WHERE email=...
DB --> A : Utilisateur trouvé
A --> C : Authentifié
C --> B : Redirect /dashboard
B --> Utilisateur : Tableau de bord affiché

@enduml
```

---

### 2.2 Scan de badge étudiant

```plantuml
@startuml seq_scan
actor Agent
participant "ScanController" as SC
database    "BDD" as DB

Agent -> SC : POST /scan/badge {uid, salle_id}
SC -> DB : Cherche étudiant par badge_uid
DB --> SC : Étudiant + inscription active
SC -> DB : Cherche séance en_cours dans la salle
DB --> SC : Séance trouvée
SC -> DB : Crée ou met à jour Presence\n(heure_entree = now)
DB --> SC : OK
SC --> Agent : JSON {statut: "present", nom: "..."}

@enduml
```

---

### 2.3 Planification d'une séance

```plantuml
@startuml seq_seance
actor "Responsable" as R
participant "SeanceController" as SC
database    "BDD" as DB

R -> SC : POST /seances {matiere, salle, prof, debut, fin, type, groupes[]}
SC -> DB : Vérifie conflit de salle sur le créneau
DB --> SC : Pas de conflit
SC -> DB : INSERT seances
DB --> SC : Séance créée (id)
SC -> DB : INSERT option_seance (groupes associés)
DB --> SC : OK
SC --> R : Redirect planning avec message succès

@enduml
```

---

### 2.4 Déroulement et clôture d'une séance

```plantuml
@startuml seq_cloture
actor Professeur
participant "SeanceController" as SC
participant "Système (auto)"   as SYS
database    "BDD" as DB

Professeur -> SC : POST /seances/{id}/demarrer
SC -> DB : UPDATE statut = 'en_cours', heure_scan_professeur = now
DB --> SC : OK
SC --> Professeur : Séance démarrée

opt Pause
    Professeur -> SC : POST /seances/{id}/pause
    SC -> DB : Enregistre heure_debut_pause / heure_fin_pause
    DB --> SC : OK
end

Professeur -> SC : POST /seances/{id}/terminer
SC -> DB : UPDATE statut = 'terminee'
SC -> DB : Crée Presence 'absent' pour inscrits sans badge
DB --> SC : OK
SC --> Professeur : Séance terminée, présences générées

note over SYS : Tâche automatique (sync statuts)\napplique vases communicants HP→TPE\nsi le prof n'a pas badgé

@enduml
```

---

## 3. Diagramme de classes

```plantuml
@startuml classe
skinparam classAttributeIconSize 0

class Centre {
    +nom : string
    +ville : string
}

class Salle {
    +nom : string
    +capacite : int
    +type : string
}

class Equipement {
    +nom : string
    +type_materiel : string
    +etat : enum
    +quantite : int
}

class Filiere {
    +nom : string
    +code : string
    +archive : bool
}

class FiliereOption {
    +nom : string
    +code : string
}

class Niveau {
    +libelle : string
    +code : string
    +ordre : int
}

class Matiere {
    +nom : string
    +code : string
    +semestre : int
    +hp_initial : int
    +tpe_initial : int
}

class MatiereCentreAnnee {
    +hp_restant : int
    +tpe_dynamique : int
}

class AnneeScolaire {
    +libelle : string
    +date_debut : date
    +date_fin : date
    +active : bool
}

class Option {
    +nom : string
    +responsable_nom : string
}

class Etudiant {
    +matricule : string
    +nom : string
    +prenom : string
    +email : string
    +badge_uid : string
    +date_naissance : date
}

class Inscription {
    +statut : enum
    +date_inscription : date
}

class User {
    +name : string
    +email : string
    +role : enum
}

class EmploiDuTemps {
    +numero : int
    +orientation_label : string
    +date_debut : date
    +date_fin : date
}

class Seance {
    +debut : datetime
    +fin : datetime
    +type : enum {HP, TPE}
    +statut : enum
    +est_composition : bool
}

class Presence {
    +heure_entree : datetime
    +heure_sortie_definitive : datetime
    +statut : enum
}

class SortieTemporaire {
    +heure_sortie : datetime
    +heure_rentree : datetime
    +duree_minutes : int
}

class ContestationHoraire {
    +duree_calculee_minutes : int
    +duree_contestee_minutes : int
    +motif : string
    +statut : enum {en_attente, approuvee, rejetee}
    +admin_note : string
}

' Relations structurelles
Centre "1" *-- "0..*" Salle
Centre "1" *-- "0..*" Option
Centre "1" o-- "0..*" User

Salle "1" *-- "0..*" Equipement
Salle "1" o-- "0..*" Seance

Filiere "1" *-- "1..*" FiliereOption
Filiere "1" *-- "0..*" Matiere
FiliereOption "1" *-- "1..*" Niveau
Niveau "1" o-- "0..*" Matiere

Matiere "1" o-- "0..*" Seance
Matiere "1" o-- "0..*" MatiereCentreAnnee
Centre "1" o-- "0..*" MatiereCentreAnnee
AnneeScolaire "1" o-- "0..*" MatiereCentreAnnee

AnneeScolaire "1" o-- "0..*" Option
FiliereOption "1" o-- "0..*" Option
Niveau "1" o-- "0..*" Option

Option "1" *-- "0..*" Inscription
Etudiant "1" *-- "0..*" Inscription
Inscription "1" o-- "0..*" Presence

Option "0..*" -- "0..*" Seance : option_seance

User "1" o-- "0..*" Seance : professeur_id
AnneeScolaire "1" o-- "0..*" Seance
EmploiDuTemps "1" o-- "0..*" Seance
Centre "1" *-- "0..*" EmploiDuTemps

Seance "1" *-- "0..*" Presence
Seance "1" o-- "0..*" ContestationHoraire
Presence "1" *-- "0..*" SortieTemporaire

@enduml
```

---

## 4. Diagramme d'activité — Cycle de vie d'une séance

```plantuml
@startuml activite
|Responsable / Système|
start

:Créer la séance\n(matière, salle, prof, groupes, horaire);
:statut = **planifiée**;

if (Heure de début atteinte ?) then (oui — auto)
    :statut = **en_cours**;
    |Professeur|
    :Scanner son badge (heure_scan_professeur);

    repeat
        :Cours en cours;
        if (Pause demandée ?) then (oui)
            :Enregistrer heure_debut_pause;
            :Enregistrer heure_fin_pause;
            :Cumuler durees_pauses_minutes;
        endif
    repeat while (Séance terminée ?) is (non)

    |Responsable / Système|
    :statut = **terminée**;
    :Créer Présence "absent" pour\nchaque inscrit sans badge;

    if (Prof n'a pas badgé ET séance HP ?) then (oui)
        :Vases communicants\nHP → TPE (quota matière);
        :Planifier séance de rattrapage\n(même créneau, +1 semaine…+8);
    endif

else (non — annulation)
    :statut = **annulée**;
    stop
endif

|Responsable|
:Consulter fiche de présence;
:Valider / Clôturer la séance;
:Exporter PDF (optionnel);

stop
@enduml
```
