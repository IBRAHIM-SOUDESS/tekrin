<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Gère l'inscription d'un nouvel utilisateur
 */
function registerUser($fullname, $email, $password, $confirm_password, $role = 'technicien') {
    global $pdo;

    $errors = [];

    // Validation PHP (Double sécurité)
    if (empty($fullname) || strlen($fullname) < 3) $errors['fullname'] = "Le nom doit contenir au moins 3 caractères.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Adresse email invalide.";
    
    // Complexité du mot de passe
    if (strlen($password) < 8) {
        $errors['password'] = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (!preg_match("#[0-9]+#", $password)) {
        $errors['password'] = "Le mot de passe doit inclure au moins un chiffre.";
    } elseif (!preg_match("#[a-z]+#", $password)) {
        $errors['password'] = "Le mot de passe doit inclure au moins une minuscule.";
    } elseif (!preg_match("#[A-Z]+#", $password)) {
        $errors['password'] = "Le mot de passe doit inclure au moins une majuscule.";
    }

    if ($password !== $confirm_password) $errors['confirm_password'] = "Les mots de passe ne correspondent pas.";

    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors['email'] = "Cet email est déjà utilisé.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
            if ($stmt->execute([$fullname, $email, $hashed_password, $role])) {
                return $pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            $errors['general'] = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }

    return $errors;
}

/**
 * Gère la connexion d'un utilisateur
 */
function loginUser($email, $password) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_fullname'] = $user['fullname'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_avatar'] = $user['profile_picture'];
        $_SESSION['onboarding_completed'] = $user['onboarding_completed'];
        return true;
    }

    return false;
}

/**
 * Déconnecte l'utilisateur
 */
function logoutUser() {
    session_unset();
    session_destroy();
    redirect('login.php');
}
?>