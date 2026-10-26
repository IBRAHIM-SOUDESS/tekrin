<?php
// login.php - Page de connexion sécurisée
session_start();
require_once 'config.php';

// Si déjà connecté, rediriger vers admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            // Préparation de la requête pour éviter les injections SQL
            $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = :username");
            $stmt->execute([':username' => $username]);
            
            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch();
                $hashed_password = $row['password'];
                
                // Vérification du mot de passe haché
                if (password_verify($password, $hashed_password)) {
                    // Mot de passe correct, on démarre la session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $row['id'];
                    $_SESSION['admin_username'] = $row['username'];
                    
                    header("Location: admin.php");
                    exit;
                } else {
                    $error = "Nom d'utilisateur ou mot de passe incorrect.";
                }
            } else {
                $error = "Nom d'utilisateur ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Erreur système. Veuillez réessayer plus tard.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - LIVRA-CAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 80px auto;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-primary">LIVRA-CAM</h2>
            <p class="text-muted">Espace Administration</p>
        </div>
        
        <div class="card">
            <div class="card-body p-4">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label class="form-label">Identifiant</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>    
                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center bg-white border-top-0 py-3">
                <a href="index.php" class="text-decoration-none small text-muted">&larr; Retour au site client</a>
            </div>
        </div>
    </div>
</body>
</html>
