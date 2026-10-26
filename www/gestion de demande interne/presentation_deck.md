# Deck de Présentation — KAMAL Support Interne

## Slide 1 — Problème
- Les demandes internes se dispersent (mails, WhatsApp, oral)
- Priorités floues, retards, oublis, manque de traçabilité
- Impact business: perte de temps et de visibilité

Notes orateur:
- Situez le contexte en 20–30 s
- Donnez un exemple concret d’incident non traité à temps

## Slide 2 — Solution
- Centraliser toutes les demandes en un point d’entrée
- Prioriser automatiquement: impact × urgence
- Suivi transparent: statut, note de résolution, export

Notes orateur:
- Résumez en 3 points mémorisables
- Terminez par “Je vous montre en 3 écrans”

## Slide 3 — Démo (Parcours)
- Créer un ticket (formulaire, priorité calculée)
- Suivre l’avancement (recherche par numéro/nom)
- Piloter (tableau de bord: urgences, catégories, export)

Notes orateur:
- Annoncez chaque étape avant de cliquer
- Dites ce que le jury doit observer (badge priorité, statut, filtres)

## Slide 4 — Modèle de Données
- Ticket: objet, message, pièce jointe, auteur
- Catégorie, impact, urgence (normalisée), priorité calculée
- Statut (en cours, terminé), note de résolution, dates

Notes orateur:
- Montrez la simplicité et la logique ITSM
- Mentionnez la normalisation “informatif/gênant/bloquant”

## Slide 5 — Architecture & Techniques
- Front: PHP + HTML/CSS (Inter, Tailwind utilitaires), JS simple
- Back: PHP + MySQL (sécurité de base, filtrage, CSRF)
- Exports: CSV (admin), PDF A4 (guides/pages), logs d’audit

Notes orateur:
- Justifiez le choix: simplicité, disponibilité, compatibilité
- Soulignez sécurité basique (CSRF, échappement, rôles)

## Slide 6 — Tableau de Bord & Indicateurs
- Cartes par urgence (basse/moyenne/haute)
- Répartition par catégorie (+ filtres)
- Export CSV pour reporting

Notes orateur:
- Expliquez l’usage au quotidien pour un technicien/responsable
- Mentionnez le SLA indicatif (si présent)

## Slide 7 — Perspectives
- SLA par catégorie, notifications (email/WhatsApp)
- Rôles & permissions, audit renforcé
- Rapports PDF mensuels, intégration SSO future

Notes orateur:
- Ancrez le projet dans une trajectoire réaliste
- Précisez ce qui est prêt vs ce qui est à venir

## Slide 8 — Conclusion
- Une app simple pour centraliser, prioriser, tracer
- Démo fluide, architecture maîtrisée, impacts mesurables
- Questions

Notes orateur:
- Terminez en 20 s, invitez aux questions
- Gardez 3 messages clés: Centralisation, Priorisation, Traçabilité
