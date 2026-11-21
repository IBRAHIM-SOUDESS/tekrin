# 🛠️ TEKRIN — Plateforme Numérique de Mise en Relation & Marketplace d'Artisans

> **Conçu et développé par [Moumbagna Nchare Ibrahim Soudess](https://github.com/IBRAHIM-SOUDESS)**  
> *Étudiant en Informatique à l'Université de Yaoundé 1 | Passionné par la Cybersécurité et l'Intelligence Artificielle*

---

## 📌 Présentation du Projet

**Tekrin** est une plateforme web moderne conçue pour valoriser les artisans, techniciens et professionnels locaux. Elle connecte directement les prestataires de services à leurs clients finaux grâce à un ensemble d'outils interactifs :

- **Onboarding guidé pas-à-pas** pour la création de profil professionnel (tarification, zones de couverture géographique, certifications).
- **Marketplace de services** avec fiches prestations détaillées et demandes de devis / rendez-vous.
- **Réseau social professionnel** : fil d'actualités, publications de réalisations de chantiers, système de likes, commentaires et abonnements (*follows*).
- **Système de réputation et d'avis certifiés** avec calcul de score moyen et modération.
- **Tableau de bord de gestion des demandes** reçues par les artisans avec statuts de suivi.

---

## 🔒 Architecture & Principes de Sécurité

Développé selon une approche **Security-First** inspirée des bonnes pratiques de l'OWASP :

1. **Protection contre les Injections SQL** : Utilisation exclusive de l'API **PDO** avec requêtes préparées (`$pdo->prepare()`) et typage strict des paramètres pour toutes les interactions base de données.
2. **Intégrité Référentielle** : Gestion des clés étrangères avec clauses `ON DELETE CASCADE` pour préserver l'intégrité relationnelle entre utilisateurs, services, avis et publications.
3. **Gestion Sécurisée des Sessions** : Vérification systématique de l'état d'authentification (`session_start()`, validation des rôles utilisateur).
4. **Sanitisation des Entrées** : Filtrage des données utilisateurs via `htmlspecialchars()` et validation des formats de données avant traitement.

---

## 🗄️ Structure de la Base de Données

Le schéma relationnel MySQL (`database/tekrin.sql`) s'articule autour des tables principales :

- `users` : Comptes artisans et clients, coordonnées, bio, coordonnées GPS, métadonnées d'onboarding.
- `services` : Catalogue de prestations avec tarification indicative et descriptions.
- `reviews` : Système d'évaluation (score 1-5 étoiles, commentaires, traçabilité des auteurs).
- `posts` : Fil d'actualité des réalisations, photos de travaux et mises à jour.
- `follows` : Relations sociales de suivi entre professionnels et clients.
- `requests` : Demandes de services entrantes, historiques et statuts.

---

## 🚀 Installation & Lancement Local

### Prérequis
- **PHP** 8.0 ou supérieur
- **MySQL** 5.7+ ou MariaDB
- Serveur local type **WampServer**, **XAMPP** ou **Laragon**

### Étapes
1. Cloner ou copier le projet dans le répertoire de votre serveur web (ex. `C:/wamp64/www/tekrin`).
2. Créer une base de données MySQL nommée `tekrin` :
   ```sql
   CREATE DATABASE tekrin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Importer le fichier SQL `database/tekrin.sql`.
4. Configurer les accès dans `includes/config.php` si nécessaire :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'tekrin');
   ```
5. Accéder à l'application dans votre navigateur :  
   `http://localhost/tekrin/`

---

## 👨‍💻 Auteur & Contact

- **Auteur** : Moumbagna Nchare Ibrahim Soudess
- **Portfolio** : [portfolio-ibrahim-kohl.vercel.app](https://portfolio-ibrahim-kohl.vercel.app/)
- **LinkedIn** : [moumbagna-nchare-ibrahim-soudess-85072a390](https://www.linkedin.com/in/moumbagna-nchare-ibrahim-soudess-85072a390)
- **Email** : `ibrahimsoudess7@gmail.com`
- **WhatsApp** : `+237 689 98 18 51`
