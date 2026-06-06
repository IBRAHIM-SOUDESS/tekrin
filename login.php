<?php
require_once 'includes/auth.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    if ($_SESSION['onboarding_completed']) {
        redirect('feed.php');
    } else {
        redirect('onboarding.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (loginUser($email, $password)) {
        if ($_SESSION['onboarding_completed']) {
            redirect('feed.php');
        } else {
            redirect('onboarding.php');
        }
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}

require_once 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon">
                <i data-lucide="log-in"></i>
            </div>
            <h2>Connexion</h2>
            <p class="auth-subtitle">Accédez à votre espace professionnel TEKRIN</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Adresse Email</label>
                <div class="input-wrapper">
                    <i data-lucide="mail" class="input-icon"></i>
                    <input type="email" id="email" name="email" placeholder="votre@email.com" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <i data-lucide="lock" class="input-icon"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i data-lucide="eye" id="password-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-register btn-block" style="width: 100%; margin-top: 10px; padding: 16px;">
                <span>Se connecter</span>
                <i data-lucide="arrow-right" style="margin-left: 8px; width: 18px;"></i>
            </button>
        </form>

        <div class="form-footer">
            <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous gratuitement</a></p>
        </div>
    </div>
</div>

<style>
.auth-header {
    margin-bottom: 32px;
}
.auth-icon {
    width: 64px;
    height: 64px;
    background: rgba(21, 101, 192, 0.1);
    color: var(--primary-blue);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}
.auth-icon i { width: 32px; height: 32px; }

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.input-icon {
    position: absolute;
    left: 16px;
    color: var(--text-muted);
    width: 18px;
    height: 18px;
}
.input-wrapper input {
    padding-left: 48px !important;
}

.password-toggle {
    position: absolute;
    right: 16px;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-block {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 700;
}
</style>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = document.getElementById(id + '-eye');
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    lucide.createIcons();
}

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});
</script>

<?php require_once 'includes/footer.php'; ?>
