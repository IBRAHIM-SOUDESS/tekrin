<?php
require_once 'includes/auth.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    redirect('wizard.php?step=1');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $result = registerUser($fullname, $email, $password, $confirm_password);

    if (is_numeric($result)) {
        // Connexion automatique après inscription
        $_SESSION['user_id'] = $result;
        $_SESSION['user_fullname'] = $fullname;
        $_SESSION['user_role'] = 'technicien'; // Rôle par défaut, sera mis à jour dans le wizard
        $_SESSION['user_email'] = $email;
        $_SESSION['user_avatar'] = null;
        $_SESSION['onboarding_completed'] = 0;

        setFlashMessage("Bienvenue sur TEKRIN ! Votre compte a été créé. Commençons par configurer votre profil professionnel.", "success");
        redirect('onboarding.php');
    } else {
        $errors = $result;
    }
}

require_once 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon">
                <i data-lucide="user-plus"></i>
            </div>
            <h2>Créer un compte</h2>
            <p class="auth-subtitle">Rejoignez la première plateforme des professionnels de proximité</p>
        </div>

        <?php if (!empty($errors) && isset($errors['general'])): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-circle"></i>
                <span><?php echo $errors['general']; ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm" novalidate>
            <div class="form-grid">
                <div class="form-group">
                    <label for="fullname">Nom complet</label>
                    <div class="input-wrapper">
                        <i data-lucide="user" class="input-icon"></i>
                        <input type="text" id="fullname" name="fullname" placeholder="Ex: Moussa Diop" required value="<?php echo htmlspecialchars($fullname ?? ''); ?>">
                    </div>
                    <small class="error-text" id="fullname-error"><?php echo $errors['fullname'] ?? ''; ?></small>
                </div>

                <div class="form-group">
                    <label for="email">Adresse Email</label>
                    <div class="input-wrapper">
                        <i data-lucide="mail" class="input-icon"></i>
                        <input type="email" id="email" name="email" placeholder="votre@email.com" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                    <small class="error-text" id="email-error"><?php echo $errors['email'] ?? ''; ?></small>
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
                    <div class="password-strength" id="password-strength">
                        <div class="strength-bar"></div>
                    </div>
                    <small class="error-text" id="password-error"><?php echo $errors['password'] ?? ''; ?></small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <div class="input-wrapper">
                        <i data-lucide="check-circle" class="input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i data-lucide="eye" id="confirm_password-eye"></i>
                        </button>
                    </div>
                    <small class="error-text" id="confirm_password-error"><?php echo $errors['confirm_password'] ?? ''; ?></small>
                </div>
            </div>

            <div class="form-legal-notice" style="margin: 20px 0; font-size: 13px; color: var(--text-muted); text-align: center;">
                En cliquant sur "Créer mon compte", vous acceptez nos 
                <a href="terms.php" style="color: var(--primary-blue); font-weight: 600;">Conditions d'utilisation</a> et notre 
                <a href="privacy.php" style="color: var(--primary-blue); font-weight: 600;">Politique de confidentialité</a>.
            </div>

            <button type="submit" class="btn btn-register btn-block" style="width: 100%; margin-top: 10px; padding: 16px;">
                <span>Créer mon compte</span>
                <i data-lucide="arrow-right" style="margin-left: 8px; width: 18px;"></i>
            </button>
        </form>

        <div class="form-footer">
            <p>Déjà un compte ? <a href="login.php">Connectez-vous</a></p>
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
.input-wrapper input, .input-wrapper select {
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

.password-strength {
    height: 4px;
    background: #f1f5f9;
    border-radius: 2px;
    margin-top: 8px;
    overflow: hidden;
}
.strength-bar {
    height: 100%;
    width: 0;
    transition: var(--transition);
}
.strength-weak { background: #ef4444; width: 33.33%; }
.strength-medium { background: #f59e0b; width: 66.66%; }
.strength-strong { background: #10b981; width: 100%; }

.error-text {
    color: #ef4444;
    font-size: 12px;
    margin-top: 4px;
    display: block;
    min-height: 18px;
    font-weight: 500;
}
input.invalid, select.invalid {
    border-color: #ef4444 !important;
    background-color: #fef2f2 !important;
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
    const form = document.getElementById('registerForm');
    const inputs = form.querySelectorAll('input, select');
    const strengthBar = document.querySelector('.strength-bar');

    const validateField = (input) => {
        const id = input.id;
        const value = input.value.trim();
        const errorEl = document.getElementById(`${id}-error`);
        let error = '';

        if (id === 'fullname') {
            if (!value) error = 'Le nom est requis.';
            else if (value.length < 3) error = 'Le nom doit contenir au moins 3 caractères.';
        } else if (id === 'email') {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!value) error = 'L\'email est requis.';
            else if (!re.test(value)) error = 'Format d\'email invalide.';
        } else if (id === 'role') {
            if (!value) error = 'Veuillez choisir un type de compte.';
        } else if (id === 'password') {
            if (!value) error = 'Le mot de passe est requis.';
            else if (value.length < 8) error = 'Minimum 8 caractères.';
            else if (!/[A-Z]/.test(value)) error = 'Au moins une majuscule requise.';
            else if (!/[a-z]/.test(value)) error = 'Au moins une minuscule requise.';
            else if (!/[0-9]/.test(value)) error = 'Au moins un chiffre requis.';
            
            // Update strength bar
            strengthBar.className = 'strength-bar';
            if (value.length >= 8 && /[A-Z]/.test(value) && /[0-9]/.test(value)) {
                strengthBar.classList.add('strength-strong');
            } else if (value.length >= 6) {
                strengthBar.classList.add('strength-medium');
            } else if (value.length > 0) {
                strengthBar.classList.add('strength-weak');
            }
        } else if (id === 'confirm_password') {
            const pass = document.getElementById('password').value;
            if (!value) error = 'Veuillez confirmer votre mot de passe.';
            else if (value !== pass) error = 'Les mots de passe ne correspondent pas.';
        }

        if (error) {
            input.classList.add('invalid');
            errorEl.textContent = error;
        } else {
            input.classList.remove('invalid');
            errorEl.textContent = '';
        }
        return !error;
    };

    inputs.forEach(input => {
        input.addEventListener('input', () => validateField(input));
        input.addEventListener('blur', () => validateField(input));
    });

    form.addEventListener('submit', (e) => {
        let isValid = true;
        inputs.forEach(input => {
            if (!validateField(input)) isValid = false;
        });
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = form.querySelector('.invalid');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    lucide.createIcons();
});
</script>

<?php require_once 'includes/footer.php'; ?>