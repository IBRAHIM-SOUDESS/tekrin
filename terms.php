<?php
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="legal-page-container">
    <div class="container">
        <div class="legal-card" data-reveal>
            <div class="legal-header">
                <h1>Conditions d'utilisation</h1>
                <p class="last-updated">Dernière mise à jour : 5 Juin 2026</p>
            </div>

            <div class="legal-content">
                <section>
                    <h2>1. Objet de la plateforme</h2>
                    <p>Tekrin est une plateforme de mise en relation entre des professionnels de proximité (artisans, techniciens, entreprises) et des clients potentiels. Notre objectif est de faciliter la visibilité des experts locaux et de simplifier la recherche de services de qualité.</p>
                </section>

                <section>
                    <h2>2. Responsabilités de l'utilisateur</h2>
                    <p>En tant qu'utilisateur de Tekrin, vous vous engagez à :</p>
                    <ul>
                        <li>Fournir des informations exactes et véridiques lors de votre inscription.</li>
                        <li>Maintenir la confidentialité de vos identifiants de connexion.</li>
                        <li>Être responsable de toute activité effectuée via votre compte.</li>
                        <li>Respecter les lois et réglementations en vigueur dans l'exercice de votre profession.</li>
                    </ul>
                </section>

                <section>
                    <h2>3. Activités interdites</h2>
                    <p>Il est strictement interdit de :</p>
                    <ul>
                        <li>Publier du contenu faux, trompeur ou diffamatoire.</li>
                        <li>Usurper l'identité d'un autre professionnel ou d'une entreprise.</li>
                        <li>Utiliser la plateforme pour diffuser des messages non sollicités (spam).</li>
                        <li>Tenter de porter atteinte à l'intégrité technique de la plateforme.</li>
                    </ul>
                </section>

                <section>
                    <h2>4. Conduite professionnelle</h2>
                    <p>Les professionnels inscrits sur Tekrin s'engagent à maintenir un standard de conduite exemplaire, incluant la ponctualité, l'honnêteté dans les tarifs et la qualité du travail fourni. Tekrin se réserve le droit de retirer tout profil faisant l'objet de signalements répétés et fondés.</p>
                </section>

                <section>
                    <h2>5. Propriété du contenu</h2>
                    <p>Vous conservez la propriété des contenus (textes, photos de portfolio) que vous publiez sur Tekrin. Cependant, en les publiant, vous accordez à Tekrin une licence mondiale et non exclusive pour utiliser, héberger et afficher ces contenus dans le cadre du fonctionnement de la plateforme.</p>
                </section>

                <section>
                    <h2>6. Suspension de compte</h2>
                    <p>Tekrin se réserve le droit de suspendre ou de supprimer définitivement l'accès à ses services pour tout utilisateur ne respectant pas les présentes conditions, sans préavis et sans indemnité.</p>
                </section>

                <section>
                    <h2>7. Limitation de responsabilité</h2>
                    <p>Tekrin agit en tant qu'intermédiaire technique. Nous ne sommes pas partie aux contrats conclus entre les professionnels et leurs clients. En conséquence, Tekrin ne saurait être tenu responsable des litiges, dommages ou malfaçons liés aux prestations de services réalisées.</p>
                </section>

                <section>
                    <h2>8. Contact</h2>
                    <p>Pour toute question relative aux présentes conditions d'utilisation, vous pouvez nous contacter via la page <a href="contact.php">Contact</a> ou à l'adresse support@tekrin.com.</p>
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
