# Script de Démo — KAMAL Support Interne

Durée: 6–7 minutes. Objectif: montrer la valeur en 3 écrans.

## Étape 1 — Création de Ticket (Formulaire)
- Phrase d’annonce: “Je crée un ticket; l’urgence/impact calculent la priorité.”
- Action:
  - Ouvrir index.php
  - Remplir Nom, Service, Objet
  - Choisir Catégorie; montrer champ conditionnel
  - Sélectionner Impact (utilisateur/département/entreprise)
  - Sélectionner Urgence (informatif/gênant/bloquant)
  - Observer le badge “Priorité calculée”
  - Soumettre
- À observer:
  - Matrice de priorité réactive
  - Message de confirmation et lien vers Suivi

## Étape 2 — Suivi d’Avancement
- Phrase d’annonce: “Je retrouve mon ticket par numéro/nom.”
- Action:
  - Ouvrir track.php
  - Chercher le numéro du ticket
  - Montrer statut (en cours/terminé), dates, priorité
  - Si terminé: afficher la note de résolution
- À observer:
  - Indicateurs clairs, composant de carte ticket

## Étape 3 — Tableau de Bord Admin
- Phrase d’annonce: “Je pilote la charge et j’exporte.”
- Action:
  - Ouvrir admin.php
  - Montrer cartes “Urgence”, répartition par catégorie
  - Utiliser filtres (service, urgence, recherche)
  - Export CSV
- À observer:
  - Vision synthétique, filtres utiles, export

## Conclusion Démo
- “En 3 écrans, on centralise, on priorise, on suit.”
- “C’est utilisable au quotidien et extensible.”

## Q&A (Réponses courtes)
- Sécurité: “CSRF, échappement, sessions, rôles; pièces jointes limitées.”
- Extensibilité: “Ajout de SLA, notifications, rôles; facile à étendre.”
- Données: “Modèle simple; normalisation urgence; logs d’audit.”
- Tests: “Vérif manuelle des flows; exports; cohérence UI; prochaine étape: tests unitaires.”
- Limites: “Pas encore de SSO/notifications; prévu en perspectives.”

## Checklists Jour J
- Préparer 2–3 tickets de test (en cours/terminé)
- Ouvrir onglets: index.php, track.php, admin.php
- Charger slides en PDF local
- Avoir captures d’écran de secours
- Répéter 2–3 fois le script avec chrono
