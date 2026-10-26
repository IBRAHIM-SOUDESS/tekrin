<?php
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Rediriger si l'onboarding est déjà complété
if (isset($_SESSION['onboarding_completed']) && $_SESSION['onboarding_completed']) {
    redirect('feed.php');
}

$user_id = $_SESSION['user_id'];
$step = isset($_GET['step']) ? (int)$_GET['step'] : 0; // 0 = Entry Point
$errors = [];

// Récupérer les données actuelles
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Traitement (pour l'instant vide car on ne construit pas les étapes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 0) {
    // Rediriger vers l'étape 1 quand elle sera prête
    // Pour l'instant on reste sur l'entry point ou on simule un passage
    setFlashMessage("L'assistant de configuration sera bientôt disponible !", "info");
}

require_once 'includes/header.php';
?>

<div class="app-container" style="background: var(--grad-soft); min-height: calc(100vh - 80px); display: flex; align-items: center; padding: 40px 20px;">
    <div class="container">
        <div class="auth-card onboarding-card" style="max-width: 600px; margin: 0 auto;" data-reveal>
            <div class="onboarding-content">
                <div class="welcome-badge">
                    <i data-lucide="sparkles"></i>
                    <span>Bienvenue sur TEKRIN</span>
                </div>
                <h1>Configurons votre <span class="highlight">Identité Numérique</span></h1>
                <p class="onboarding-intro">
                    Félicitations <strong><?php echo explode(' ', htmlspecialchars($user['fullname']))[0]; ?></strong> ! Votre compte est créé. 
                    Suivez ces étapes rapides pour rendre votre profil irrésistible aux yeux de vos futurs clients.
                </p>

                <div class="steps-preview">
                    <div class="step-item">
                        <div class="step-icon"><i data-lucide="briefcase"></i></div>
                        <div class="step-text">
                            <h4>Profession</h4>
                            <p>Définissez votre métier et votre expertise</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon"><i data-lucide="camera"></i></div>
                        <div class="step-text">
                            <h4>Photo de Profil</h4>
                            <p>Une photo professionnelle pour inspirer confiance</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-icon"><i data-lucide="map-pin"></i></div>
                        <div class="step-text">
                            <h4>Localisation</h4>
                            <p>Indiquez votre ville pour les clients locaux</p>
                        </div>
                    </div>
                </div>

                <div class="onboarding-actions">
                    <a href="wizard.php?step=1" class="btn btn-register btn-lg" style="width: 100%; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px;">
                        <span>Commencer la configuration</span>
                        <i data-lucide="arrow-right"></i>
                    </a>
                </div>
                <p class="skip-text" style="text-align: center; margin-top: 16px;">Cela ne prendra que 2 minutes</p>
            </div>
        </div>
    </div>
</div>

<style>
.onboarding-card {
    padding: 40px;
}

.onboarding-content {
    text-align: left;
}

.welcome-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(124, 77, 255, 0.1);
    color: var(--secondary-purple);
    border-radius: 50px;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 24px;
}

.onboarding-content h1 {
    font-size: 40px;
    margin-bottom: 20px;
    line-height: 1.2;
}

.onboarding-intro {
    font-size: 18px;
    color: var(--text-muted);
    margin-bottom: 40px;
    line-height: 1.6;
}

.steps-preview {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-bottom: 48px;
}

.step-item {
    display: flex;
    align-items: center;
    gap: 16px;
}

.step-icon {
    width: 44px;
    height: 44px;
    background: #f8fafc;
    border: 2px solid #f1f5f9;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-blue);
}

.step-text h4 {
    font-size: 16px;
    color: var(--dark-bg);
}

.step-text p {
    font-size: 13px;
    color: var(--text-muted);
}

.onboarding-actions {
    display: flex;
    align-items: center;
    gap: 24px;
}

.skip-text {
    font-size: 14px;
    color: var(--text-muted);
}

@media (max-width: 1024px) {
    .onboarding-card { padding: 40px; }
}
</style>

<?php require_once 'includes/footer.php'; ?>

