<?php
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="legal-page-container">
    <div class="container">
        <div class="legal-card" data-reveal>
            <div class="legal-header">
                <h1>Politique de confidentialité</h1>
                <p class="last-updated">Dernière mise à jour : 5 Juin 2026</p>
            </div>

            <div class="legal-content">
                <section>
                    <h2>1. Introduction</h2>
                    <p>Chez Tekrin, nous accordons une importance capitale à la protection de vos données personnelles. Cette politique détaille comment nous collectons, utilisons et sécurisons vos informations.</p>
                </section>

                <section>
                    <h2>2. Données collectées</h2>
                    <p>Pour fournir nos services, nous collectons les informations suivantes :</p>
                    <ul>
                        <li><strong>Identité :</strong> Nom complet.</li>
                        <li><strong>Contact :</strong> Adresse email, Numéro de téléphone, Numéro WhatsApp.</li>
                        <li><strong>Profil professionnel :</strong> Photo de profil, Profession, Ville et région, Services proposés.</li>
                        <li><strong>Portfolio :</strong> Images de vos réalisations professionnelles.</li>
                    </ul>
                </section>

                <section>
                    <h2>3. Finalités du traitement</h2>
                    <p>Vos données sont utilisées pour :</p>
                    <ul>
                        <li>La création et la gestion de votre compte professionnel.</li>
                        <li>Assurer votre visibilité auprès des clients sur la plateforme.</li>
                        <li>Permettre le fonctionnement technique des outils de mise en relation.</li>
                        <li>Améliorer nos services via des fonctionnalités futures, telles que la géolocalisation.</li>
                    </ul>
                </section>

                <section>
                    <h2>4. Sécurité des données</h2>
                    <p>Nous mettons en œuvre des mesures de sécurité robustes pour protéger vos données :</p>
                    <ul>
                        <li><strong>Hachage des mots de passe :</strong> Vos mots de passe sont stockés sous forme cryptée.</li>
                        <li><strong>Authentification sécurisée :</strong> Protocoles de connexion protégeant contre les accès non autorisés.</li>
                        <li><strong>Protection des serveurs :</strong> Infrastructures sécurisées et mises à jour régulièrement.</li>
                    </ul>
                </section>

                <section>
                    <h2>5. Vos droits</h2>
                    <p>Conformément aux réglementations sur la protection des données, vous disposez des droits suivants :</p>
                    <ul>
                        <li><strong>Droit d'accès :</strong> Consulter les données que nous détenons sur vous.</li>
                        <li><strong>Droit de rectification :</strong> Mettre à jour vos informations à tout moment via votre profil.</li>
                        <li><strong>Droit à l'effacement :</strong> Demander la suppression de votre compte et de vos données.</li>
                        <li><strong>Droit d'information :</strong> Poser des questions sur l'utilisation de vos données.</li>
                    </ul>
                </section>

                <section>
                    <h2>6. Partage des données</h2>
                    <p>Tekrin ne vend jamais vos données personnelles à des tiers. Vos informations professionnelles (nom, profession, portfolio, contact) sont uniquement partagées publiquement sur la plateforme pour vous permettre d'être trouvé par des clients.</p>
                </section>

                <section>
                    <h2>7. Contact Confidentialité</h2>
                    <p>Pour toute question concernant vos données personnelles, contactez-nous à privacy@tekrin.com.</p>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
.legal-page-container {
    background: var(--grad-soft);
    padding: 60px 20px;
    min-height: calc(100vh - 80px);
}

.legal-card {
    background: white;
    border-radius: 24px;
    padding: 50px;
    box-shadow: var(--shadow-premium);
    max-width: 900px;
    margin: 0 auto;
}

.legal-header {
    text-align: center;
    margin-bottom: 40px;
    border-bottom: 2px solid #F1F5F9;
    padding-bottom: 30px;
}

.legal-header h1 {
    font-size: 36px;
    color: var(--text-color);
    margin-bottom: 10px;
}

.last-updated {
    color: var(--text-muted);
    font-size: 14px;
}

.legal-content section {
    margin-bottom: 35px;
}

.legal-content h2 {
    font-size: 22px;
    color: var(--primary-blue);
    margin-bottom: 15px;
}

.legal-content p, .legal-content li {
    font-size: 16px;
    line-height: 1.8;
    color: #475569;
}

.legal-content ul {
    padding-left: 20px;
    margin-top: 10px;
}

.legal-content ul li {
    margin-bottom: 8px;
}

@media (max-width: 768px) {
    .legal-card {
        padding: 30px 20px;
    }
    .legal-header h1 {
        font-size: 28px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
