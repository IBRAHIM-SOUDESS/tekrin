<?php
/**
 * Page de connexion simple pour l'administration
 */
session_start();

// Si déjà connecté, on redirige vers l'admin
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin.php");
    exit();
}

$error = "";

// 2. Configuration des comptes sécurisée avec hashs BCrypt
$comptes = [
    'admin' => [
        'hash' => '$2y$10$fW.UPP2uO.CJTlgEdBBqQuFMUoNq9BE.EpFO5WWz0lIvVihgWgJTC',
        'role' => 'admin',
        'name' => 'Administrateur'
    ],
    'koffi' => [
        'hash' => '$2y$10$nKAm6x5qm4VG9oPQEdOFl.Q6jOl0htiU.BC.EmNCocxaei82N9Y76',
        'role' => 'technicien',
        'name' => 'Koffi ADO'
    ],
    'amina' => [
        'hash' => '$2y$10$tA8xzyaKECinXW82ta5ehOiAKpmqZQvToZf/voXlRqtZFnW0qURAm',
        'role' => 'technicien',
        'name' => 'Amina OUATTARA'
    ],
    'oumar' => [
        'hash' => '$2y$10$.8bTW1fwl72BddJY7ZAJHOnw1Ja9vSmLsmMMpQjCm1.QHSH4PR7d2',
        'role' => 'technicien',
        'name' => 'Oumar TRAORE'
    ],
    'fatou' => [
        'hash' => '$2y$10$CP7X1lUCDkj98vVWenhCK.81J3kbcvykLZX8c6tiBLiDoAnKcS1OW',
        'role' => 'technicien',
        'name' => 'Fatou DIALLO'
    ]
];

// Vérification du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifiant = htmlspecialchars(trim($_POST['identifiant']), ENT_QUOTES, 'UTF-8');
    $password = $_POST['password']; // NE PAS échapper le mot de passe brut

    if (isset($comptes[$identifiant]) && password_verify($password, $comptes[$identifiant]['hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = $identifiant;
        $_SESSION['user_role'] = $comptes[$identifiant]['role'];
        $_SESSION['user_name'] = $comptes[$identifiant]['name'];
        
        header("Location: admin.php");
        exit();
    } else {
        $error = "Identifiant ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETS KAMAL - Connexion Admin</title>
    <link rel="stylesheet" href="style.css?v=4.1">
    <style>
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="login-box">
        <h2 style="text-align: center; margin-bottom: 20px;">Connexion Administration</h2>
        <p style="text-align: center; color: #64748b; font-size: 0.9em; margin-bottom: 20px;">Accès réservé aux techniciens et administrateurs.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="identifiant">Identifiant</label>
                <input type="text" id="identifiant" name="identifiant" placeholder="Votre identifiant" required 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="Votre mot de passe" required 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1em;">Se connecter</button>
        </form>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="index.php" style="color: #7f8c8d; text-decoration: none; font-size: 0.9em;">Retour à l'accueil</a>
        </div>
    </div>

</body>
</html>
