<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$success = false;
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } else {
        // Dans une application réelle, on enverrait un email ici
        // Pour l'instant, on simule un succès
        $success = true;
    }
}
?>

<div class="contact-page-container">
    <div class="container">
        <div class="contact-wrapper" data-reveal>
            <div class="contact-info-card">
                <h2>Contactez-nous</h2>
                <p>Une question, un signalement ou un bug à nous rapporter ? Notre équipe est à votre écoute.</p>
                
                <div class="contact-methods">
                    <div class="method-item">
                        <div class="method-icon"><i data-lucide="mail"></i></div>
                        <div class="method-text">
                            <h3>Support Email</h3>
                            <p>support@tekrin.com</p>
                        </div>
                    </div>
                    <div class="method-item">
                        <div class="method-icon"><i data-lucide="help-circle"></i></div>
                        <div class="method-text">
                            <h3>Aide générale</h3>
                            <p>Consultez notre FAQ</p>
                        </div>
                    </div>
                    <div class="method-item">
                        <div class="method-icon"><i data-lucide="bug"></i></div>
                        <div class="method-text">
                            <h3>Rapport de bug</h3>
                            <p>Aidez-nous à nous améliorer</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-form-card">
                <?php if ($success): ?>
                    <div class="success-screen">
                        <div class="success-icon"><i data-lucide="check-circle"></i></div>
                        <h3>Message envoyé !</h3>
                        <p>Merci pour votre message. Notre équipe vous répondra dans les plus brefs délais.</p>
                        <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
                    </div>
                <?php else: ?>
                    <form action="contact.php" method="POST">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="name">Nom complet</label>
                            <input type="text" id="name" name="name" placeholder="Votre nom" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Adresse Email</label>
                            <input type="email" id="email" name="email" placeholder="votre@email.com" required>
                        </div>

                        <div class="form-group">
                            <label for="subject">Sujet</label>
                            <select id="subject" name="subject" required>
                                <option value="general">Demande générale</option>
                                <option value="support">Support technique</option>
                                <option value="bug">Rapport de bug</option>
                                <option value="legal">Question légale</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="5" placeholder="Comment pouvons-nous vous aider ?" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-register btn-block" style="width: 100%;">
                            <span>Envoyer le message</span>
                            <i data-lucide="send" style="margin-left: 8px; width: 18px;"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.contact-page-container {
    background: var(--grad-soft);
    padding: 80px 20px;
    min-height: calc(100vh - 80px);
}

.contact-wrapper {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 40px;
    max-width: 1100px;
    margin: 0 auto;
}

.contact-info-card {
    background: var(--grad-primary);
    border-radius: 24px;
    padding: 50px;
    color: white;
    box-shadow: var(--shadow-premium);
}

.contact-info-card h2 {
    font-size: 32px;
    margin-bottom: 20px;
}

.contact-info-card p {
    font-size: 16px;
    opacity: 0.9;
    line-height: 1.6;
    margin-bottom: 40px;
}

.contact-methods {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.method-item {
    display: flex;
    align-items: center;
    gap: 20px;
}

.method-icon {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.method-text h3 {
    font-size: 16px;
    margin-bottom: 4px;
}

.method-text p {
    margin-bottom: 0;
    font-size: 14px;
    opacity: 0.8;
}

.contact-form-card {
    background: white;
    border-radius: 24px;
    padding: 50px;
    box-shadow: var(--shadow-premium);
}

.success-screen {
    text-align: center;
    padding: 40px 0;
}

.success-icon {
    font-size: 64px;
    color: #10B981;
    margin-bottom: 20px;
}

.success-screen h3 {
    font-size: 24px;
    margin-bottom: 10px;
}

.success-screen p {
    color: var(--text-muted);
    margin-bottom: 30px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 14px;
    border: 2px solid #F1F5F9;
    border-radius: 12px;
    font-size: 15px;
    transition: var(--transition);
}

.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--primary-blue);
    outline: none;
}

@media (max-width: 992px) {
    .contact-wrapper {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
