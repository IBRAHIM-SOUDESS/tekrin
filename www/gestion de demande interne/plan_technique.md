# Plan Technique – ETS KAMAL Support Interne

Ce document synthétise l'architecture technique et les choix technologiques du projet pour la soutenance.

## 1. Architecture Globale
L'application repose sur une architecture **Monolithique Web** classique (3-Tier) :
- **Présentation** : HTML5, CSS3 (Tailwind CSS), JavaScript (Vanilla).
- **Logique Métier** : PHP 8.x (Mode procédural structuré).
- **Données** : Base de données relationnelle MySQL (MariaDB).

### Avantages du choix :
- Rapidité de déploiement et maintenance facilitée.
- Compatibilité maximale avec les hébergements standards.
- Performance optimale pour une utilisation interne.

## 2. Technologies & Bibliothèques
| Technologie | Usage |
| :--- | :--- |
| **PHP (MySQLi)** | Traitement serveur, gestion des sessions et accès BDD. |
| **Tailwind CSS** | Framework utilitaire pour une UI moderne et responsive. |
| **Chart.js** | Visualisation de données (Graphiques du Dashboard). |
| **html2pdf.js** | Génération de rapports PDF côté client. |
| **Google Fonts** : Inter | Police de caractère pour une lisibilité professionnelle. |

## 3. Schéma de la Base de Données
Le modèle de données est conçu pour respecter les standards **ITSM** (Information Technology Service Management).

### Table `demandes` (Principale) :
- **Identification** : `id`, `nom_employe`, `service`.
- **Classification** : `objet`, `categorie`, `impact`, `urgence`.
- **ITSM** : `priorite_calculee` (calculée automatiquement par matrice).
- **Suivi** : `statut` (en cours, terminé, annulé), `technicien_assigne`, `note_resolution`.
- **Dates** : `date_creation`, `date_cloture`.

### Table `logs_audit` (Traçabilité) :
- Enregistre chaque action majeure (création, assignation, clôture) pour garantir la transparence et l'historique des interventions.

## 4. Sécurité du Système
Plusieurs couches de protection ont été implémentées :
- **Protection CSRF** : Utilisation de jetons (`csrf_token`) pour sécuriser l'envoi des formulaires.
- **Assainissement (Sanitization)** : Utilisation de `mysqli_real_escape_string` et `htmlspecialchars` pour prévenir les injections SQL et les failles XSS.
- **Gestion des Sessions** : Authentification sécurisée via `session_start()` pour l'accès administrateur.
- **Migrations Automatiques** : Script de vérification de structure de table dans `admin.php` pour assurer l'intégrité de la BDD.

## 5. Fonctionnalités Techniques Avancées
### Matrice de Priorité Automatique
L'application utilise une logique métier qui calcule la priorité en temps réel selon la formule :
`Priorité = Impact (Utilisateur/Département/Entreprise) × Urgence (Mineur/Gênant/Bloquant)`

### Verrouillage Responsive & Anti-Zoom
- Implémentation de `viewport-fit=cover` et blocage des gestes de zoom (`user-scalable=no`).
- Utilisation des unités dynamiques `100dvh` pour éviter les sauts de mise en page sur mobile.

### Système d'Export
- **CSV** : Extraction brute des données pour analyse Excel.
- **PDF** : Mise en page A4 optimisée pour l'impression des fiches d'intervention.

## 6. Déploiement & Prérequis
- Serveur : Apache / Nginx.
- Version PHP : >= 7.4.
- Base de données : MySQL / MariaDB.
- Navigateurs : Chrome, Safari, Edge (Modernes).
