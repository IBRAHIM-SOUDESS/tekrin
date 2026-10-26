<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETS KAMAL - Guide d'Utilisation</title>
    <link rel="stylesheet" href="style.css?v=4.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; margin: 0; }
        .guide-container { max-width: 900px; margin: 20px auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; }
        .guide-title { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .guide-subtitle { color: #64748b; margin-bottom: 24px; }
        .guide-section { margin-top: 28px; }
        .guide-h2 { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 8px; text-transform: uppercase; letter-spacing: .06em; }
        .guide-p { color: #334155; line-height: 1.7; }
        .guide-list { color: #334155; line-height: 1.7; padding-left: 18px; }
        .badge { display: inline-block; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; padding: 6px 10px; border-radius: 999px; border: 1px solid #bfdbfe; color: #1d4ed8; background: #dbeafe; }
        .cta { display: inline-flex; align-items: center; gap: 8px; background: #1d4ed8; color: #fff; font-weight: 700; padding: 10px 16px; border-radius: 12px; text-decoration: none; border: none; cursor: pointer; }
        .cta.secondary { background: #e2e8f0; color: #0f172a; }
        .guide-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; }
        .card h3 { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .06em; }
        .muted { color: #64748b; }
        .hr { height: 1px; background: #e2e8f0; margin: 24px 0; border: 0; }
        .cover { background: linear-gradient(180deg, #ffffff, #f1f5f9); border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; text-align: center; display: flex; flex-direction: column; justify-content: center; min-height: 40vh; position: relative; }
        .cover .title { font-size: 28px; font-weight: 900; color: #0f172a; margin-bottom: 6px; letter-spacing: .02em; }
        .cover .subtitle { color: #475569; font-size: 14px; margin-bottom: 12px; }
        .cover .meta { color: #64748b; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; }
        .toc { margin-top: 16px; }
        .toc a { display: block; color: #1d4ed8; text-decoration: none; padding: 6px 0; }
        .actions-bar { max-width: 900px; margin: 16px auto; display: flex; justify-content: space-between; align-items: center; padding: 0 16px; }
        .actions-right { display: flex; gap: 8px; }
        .cover .badge { position: absolute; top: 16px; right: 16px; }
        .print-header, .print-footer { display: none; }
        
        @media (max-width: 840px) { .guide-grid { grid-template-columns: 1fr; } }
        @media print {
            @page { size: A4; margin: 18mm 15mm; }
            body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-header, .print-footer { display: block; position: fixed; left: 0; right: 0; color: #64748b; font-size: 11px; }
            .print-header { top: 0; border-bottom: 1px solid #e2e8f0; padding: 6px 0; }
            .print-footer { bottom: 0; border-top: 1px solid #e2e8f0; padding: 6px 0; display: flex; justify-content: space-between; }
            .page-number:after { content: counter(page); }
            .no-print { display: none !important; }
            .guide-container { border: none; padding: 0; }
            .cover { page-break-after: always; }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
 
    <div class="cover">
        <span class="badge no-print">Documentation Officielle</span>
        <div class="title">Guide d'Utilisation du Portail</div>
        <p class="subtitle">Apprenez à gérer vos demandes techniques efficacement.</p>
    </div>

    <div class="guide-container">
        
        <p class="guide-subtitle">Centralisez, priorisez et suivez toutes vos demandes IT au même endroit.</p>
        <div class="guide-section" id="section-intro">
            <h2 class="guide-h2">1. À qui s’adresse l’application</h2>
            <p class="guide-p">L’application KAMAL Support Interne est destinée aux employés qui souhaitent signaler un incident ou demander un service, ainsi qu’aux techniciens qui traitent ces demandes. Elle fournit un point d’entrée unique, des priorités automatiques et un suivi transparent.</p>
        </div>
        <div class="guide-section page-break" id="section-usage">
            <h2 class="guide-h2">2. Comment créer une demande</h2>
            <div class="guide-grid">
                <div class="card">
                    <h3>Étapes employé</h3>
                    <ul class="guide-list">
                        <li>Ouvrir “Nouveau Ticket”.</li>
                        <li>Renseigner l’objet et la catégorie (Matériel, Logiciel, Réseau, Autre).</li>
                        <li>Décrire le besoin, joindre des pièces si nécessaire.</li>
                        <li>Indiquer l’impact (Moi, Département, Entreprise) et l’urgence métier (Informatif, Gênant, Bloquant).</li>
                        <li>Soumettre pour obtenir le numéro de ticket.</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>Priorité automatique</h3>
                    <p class="guide-p">L’urgence métier est normalisée (basse/moyenne/haute) et combinée à l’impact pour calculer la priorité. Les demandes “bloquantes” et “entreprise” ressortent immédiatement dans le tableau de bord.</p>
                </div>
            </div>
        </div>

        <div class="guide-section" id="section-suivi">
            <h2 class="guide-h2">3. Suivre l’avancement</h2>
            <p class="guide-p">Depuis “Suivre mes tickets”, recherchez par <strong>numéro</strong> de ticket ou par <strong>nom</strong>. Vous verrez le statut (en cours/terminé), la dernière mise à jour et, si clôturé, la <strong>note de résolution</strong> du technicien.</p>
        </div>

        <div class="guide-section" id="section-dashboard">
            <h2 class="guide-h2">4. Tableau de bord technicien</h2>
            <div class="guide-grid">
                <div class="card">
                    <h3>Filtres et lecture</h3>
                    <ul class="guide-list">
                        <li>Filtrer par statut (En cours / Historique), service, urgence, mot‑clé.</li>
                        <li>Voir les pièces jointes, les catégories, la priorité calculée.</li>
                        <li>Clôturer avec note de résolution (traçabilité audit).</li>
                    </ul>
                </div>
                <div class="card">
                    <h3>Visibilité et charge</h3>
                    <ul class="guide-list">
                        <li>Cartes “Urgence Basse/Moyenne/Haute” pour la charge immédiate.</li>
                        <li>Répartition “par catégorie” selon l’onglet En cours / Historique.</li>
                        <li>Export CSV pour analyse et reporting.</li>
                    </ul>
                </div>
            </div>
        </div>

        <hr class="hr page-break">

        <div class="guide-section page-break" id="section-bonnes-pratiques">
            <h2 class="guide-h2">5. Bonnes pratiques d’utilisation</h2>
            <ul class="guide-list">
                <li>Utiliser un objet court et explicite, éviter les abréviations internes.</li>
                <li>Décrire le problème avec étapes pour reproduire, messages d’erreur, contexte.</li>
                <li>Joindre une capture ou un PDF lorsque pertinent.</li>
                <li>Choisir l’impact et l’urgence <strong>réalistes</strong> pour un traitement équitable.</li>
                <li>Ne pas dupliquer un ticket: mettre à jour le ticket existant si besoin.</li>
            </ul>
        </div>

        <div class="guide-section" id="section-securite">
            <h2 class="guide-h2">6. Sécurité et conformité</h2>
            <ul class="guide-list">
                <li>Données employé et logs d’audit conservés pour la traçabilité (création, clôture, suppression).</li>
                <li>Pièces jointes limitées aux formats usuels et à une taille raisonnable.</li>
                <li>Aucune information sensible (mots de passe) ne doit être saisie dans les tickets.</li>
            </ul>
        </div>

        <div class="guide-section" id="section-avantages">
            <h2 class="guide-h2">7. Avantages pour l’entreprise</h2>
            <ul class="guide-list">
                <li><strong>Centralisation</strong>: un canal unique pour toutes les demandes.</li>
                <li><strong>Priorisation</strong>: impact × urgence = file d’attente alignée avec le métier.</li>
                <li><strong>Traçabilité</strong>: suivi transparent, notes de résolution, export.</li>
                <li><strong>Efficacité</strong>: moins de mails, moins d’oublis, délais maîtrisés.</li>
                <li><strong>Décision</strong>: vision des volumes par catégorie pour orienter les actions.</li>
            </ul>
        </div>

        <div class="guide-section" id="section-jury">
            <h2 class="guide-h2">8. Ce que les jurys et entreprises doivent savoir</h2>
            <ul class="guide-list">
                <li><strong>Parcours utilisateur</strong> clair: création, priorisation, suivi, clôture.</li>
                <li><strong>Modèle de données</strong> simple: catégories, urgence normalisée, impact, statut.</li>
                <li><strong>Tableau de bord</strong> orienté action: filtres, répartition, indicateurs synthétiques.</li>
                <li><strong>Extensibilité</strong> prévue: ajout de statuts intermédiaires, SLA par catégorie, notifications.</li>
                <li><strong>Bonne gouvernance</strong>: audit, limitation des rôles (employé vs technicien), conformité de base.</li>
            </ul>
        </div>

        <div class="hr"></div>
        <div id="guideToc" class="no-print" style="margin-top: 8px;">
            <div style="text-transform: uppercase; letter-spacing: .06em; font-weight: 800; font-size: 12px; color: #64748b; margin-bottom: 6px;">Sommaire</div>
            <div style="margin-top: 8px; display: flex; gap: 8px;">
                <button class="cta" onclick="window.print()">Imprimer le guide</button>
                <a href="help.php" class="cta secondary">Retour au Centre d’Aide</a>
            </div>
        </div>
    </div>
</body>
</html>
