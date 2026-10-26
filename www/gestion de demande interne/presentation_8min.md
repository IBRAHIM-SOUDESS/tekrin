# Script 8 minutes — KAMAL Support Interne (orienté vente)

Durée cible: 8:00. Objectif: convaincre par la valeur métier + démo fluide.

## 0:00–0:30 — Ouverture (Hook)
- “Dans nos entreprises, les demandes internes se perdent entre mails et oral. Résultat: retards, oublis, et priorités floues.”
- “KAMAL Support Interne centralise, priorise automatiquement, et rend le suivi transparent.”
- “Je vous montre la valeur en 3 écrans.”

## 0:30–1:30 — Problème et valeur
- “Aujourd’hui: demandes dispersées, pas de priorisation partagée, faible traçabilité.”
- “Demain avec KAMAL: un point d’entrée unique, une priorité calculée (impact × urgence), des décisions basées sur des indicateurs.”
- “Bénéfices: moins d’oubli, meilleure visibilité, traitement plus rapide.”

## 1:30–2:30 — Proposition de valeur (Pain → Claim → Gain)
- Pain: “Les tickets critiques noyés parmi les mineurs.”
- Claim: “Normalisation de l’urgence + impact métier → file d’attente alignée.”
- Gain: “Les cas ‘bloquants entreprise’ ressortent immédiatement, réduisant les délais.”

## 2:30–5:00 — Démo guidée (3 écrans)
Phrase d’annonce: “Regardez ce parcours simple.”
- Écran 1 — Formulaire (index.php)
  - “Je saisis objet, catégorie, impact, urgence. Ici, la priorité se calcule en direct.”
  - Action: remplir les champs; montrer le badge “Priorité calculée”.
  - “Soumettre: un ticket est créé avec son numéro, prêt à être suivi.”
- Écran 2 — Suivi (track.php)
  - “Je recherche par numéro/nom. Le statut apparaît: en cours ou terminé.”
  - Si terminé: “La note de résolution est visible pour la traçabilité.”
  - “C’est le point de vérité: on voit l’avancement réel.”
- Écran 3 — Tableau de bord (admin.php)
  - “Charge immédiate par urgence, répartition par catégorie, filtres.”
  - “Export CSV pour analyse/rapport.”
  - “Outil d’action: prioriser, clôturer, mesurer.”

## 5:00–6:00 — Architecture & Sécurité (simple et fiable)
- “Stack: PHP/HTML/CSS/JS léger, MySQL. Facile à déployer, maintenir.”
- “Sécurité de base: CSRF, échappement des données, sessions, rôles.”
- “Traçabilité: note de résolution + logs d’audit.”
- “Exports: CSV côté admin; PDF A4 propre pour guides/pages.”

## 6:00–7:00 — Preuves, ROI, différenciation
- “Différenciation: priorisation normalisée + suivi simple + indicateurs.”
- “ROI attendu: baisse des mails internes, réduction des retards, meilleure visibilité des charges.”
- “Perspectives: SLA par catégorie, notifications, rôles avancés, rapports PDF mensuels.”

## 7:00–8:00 — Conclusion & CTA
- “En 3 écrans, on centralise, on priorise, on suit — au quotidien.”
- “Prêt pour une mise en service progressive; extensible selon vos besoins.”
- “Questions? Je peux aussi vous montrer un export et la fiche d’un ticket clôturé.”

---

## Cues de démo (timing et phrases exactes)
- À chaque écran: annonce (“Je passe au suivi…”) + ce que le jury doit observer (“badge priorité”, “statut”, “filtres”).
- Garder 15–20 s de silence utile après chaque point clé pour laisser regarder.
- Si incident technique: basculer sur captures ou résumé narratif (“Voici ce que vous verriez…”).

## Techniques de prise de parole (règles utiles)
- Signposts: “D’abord le problème… Ensuite la démo… Enfin l’impact.”
- Rythme: phrases courtes, fin audibles, 130–150 mots/min.
- Regard: balayage gauche-centre-droite; 2–3 s par point.
- Gestes: mains au-dessus de la taille, gestes sobres pour accentuer.
- Voix: articulation, volume stable; respirations 4–4 pour gérer le trac.
- Triades: “Centraliser, prioriser, tracer” (mémorisable).
- Storyline courte: “Problème → Solution → Preuve → Impact → Next Steps.”

## Techniques de vente (persuasion)
- Pain/Claim/Gain: problème réel → promesse claire → résultat attendu.
- Chiffres et verbes d’action: réduire, accélérer, clarifier.
- Réassurance: sécurité, traçabilité, simplicité de déploiement.
- Next Step: “Pilote sur un service avec reporting simple.”

## Préparation Jour J (checklist)
- **Réinitialiser les données**: Accéder à `generate_data.php` juste avant la démo pour avoir un tableau de bord propre avec 20 tickets variés.
- Ouvrir onglets: index.php, track.php, admin.php; préparer 2 tickets (en cours/terminé).
- Charger la présentation: [presentation.html](presentation.html). Raccourcis: F (plein écran), P (impression PDF).
- Avoir captures d’écran de secours.
- Répéter 2–3 fois avec chrono; viser 7:45 pour garder marge Q&A.
