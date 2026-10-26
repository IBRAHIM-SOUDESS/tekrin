<?php
require_once __DIR__.'/config.php';
$error = '';
try {
  $pdo = db();
  $c = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
  if ($c === 0) {
    $pdo->exec("INSERT IGNORE INTO roles (nom) VALUES ('administrateur'), ('manager'), ('magasinier'), ('vendeur')");
    $rid = $pdo->query("SELECT id FROM roles WHERE nom='administrateur'")->fetchColumn();
    if ($rid) {
      $hash = password_hash('password', PASSWORD_DEFAULT);
      $stmt = $pdo->prepare('INSERT INTO users(nom,email,mot_de_passe,role_id) VALUES(?,?,?,?)');
      $stmt->execute(['Administrateur','admin@pme.local',$hash,(int)$rid]);
    }
  }
} catch (Exception $e) {
  $error = 'Erreur base de données';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['mot_de_passe'] ?? '';
  if ($email && $password) {
    try {
      $stmt = db()->prepare('SELECT u.id, u.nom, u.email, u.mot_de_passe, r.nom AS role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?');
      $stmt->execute([$email]);
      $user = $stmt->fetch();
      if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nom'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: dashboard.php');
        exit;
      } else {
        $error = 'Identifiants invalides';
      }
    } catch (Exception $e) {
      $error = 'Erreur base de données';
    }
  } else {
    $error = 'Veuillez renseigner votre email et mot de passe';
  }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion · Gestion de stock</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="container auth">
    <img src="assets/logo.svg" class="logo-big" alt="ETS_KAMAL">
    <form method="post" class="card">
      <h2>Connexion</h2>
      <?php if ($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Mot de passe</label>
      <input type="password" name="mot_de_passe" required>
      <button type="submit" class="btn">Se connecter</button>
    </form>
  </div>
  <footer class="footer">
    <div class="container">
      <div>© ETS_KAMAL — Gestion de stock</div>
      <div><a href="apropos.php">À propos</a></div>
    </div>
  </footer>
</body>
</html>
