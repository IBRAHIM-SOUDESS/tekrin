<?php
require_once __DIR__.'/config.php';
require_login();
if (!is_role('administrateur')) { header('Location: dashboard.php'); exit; }
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $mdp = $_POST['mot_de_passe'] ?? '';
    if ($nom && $email && $role && $mdp) {
      $pdo = db();
      $rid = $pdo->prepare('SELECT id FROM roles WHERE nom = ?');
      $rid->execute([$role]);
      $roleId = $rid->fetchColumn();
      if ($roleId) {
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        $st = $pdo->prepare('INSERT INTO users(nom,email,mot_de_passe,role_id) VALUES(?,?,?,?)');
        $st->execute([$nom,$email,$hash,(int)$roleId]);
        $msg = 'Utilisateur ajouté';
      }
    }
  } catch (Throwable $e) {
    $msg = 'Erreur base de données';
  }
}
$users = db()->query('SELECT u.id, u.nom, u.email, r.nom AS role FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.nom ASC')->fetchAll();
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Utilisateurs · Gestion de stock</title>
  <link rel="stylesheet" href="assets/styles.css">
  <script defer src="assets/app.js"></script>
</head>
<body>
  <header class="topbar">
    <div class="brand"><img src="assets/logo.svg" class="logo-img" alt="ETS_KAMAL">Gestion de stock</div>
    <nav class="nav">
      <a href="dashboard.php">Tableau de bord</a>
      <a href="produits.php">Stock</a>
      <a href="mouvements.php">Mouvements</a>
      <?php if (has_any_role(['administrateur','manager'])): ?><a href="utilisateurs.php" class="active">Utilisateurs</a><?php endif; ?>
      <a href="apropos.php">À propos</a>
      <a href="logout.php">Déconnexion</a>
    </nav>
  </header>
  <main class="container">
    <h1>Utilisateurs</h1>
    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <form method="post" class="card">
      <div class="grid">
        <div>
          <label>Nom</label>
          <input name="nom" required>
        </div>
        <div>
          <label>Email</label>
          <input type="email" name="email" required>
        </div>
        <div>
          <label>Mot de passe</label>
          <input type="password" name="mot_de_passe" required>
        </div>
        <div>
          <label>Rôle</label>
          <select name="role" required>
            <option value="administrateur">Administrateur</option>
            <option value="manager">Manager</option>
            <option value="magasinier">Magasinier</option>
            <option value="vendeur">Vendeur</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn">Ajouter</button>
    </form>
    <div class="card">
      <table class="table">
        <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><?php echo htmlspecialchars($u['nom']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo htmlspecialchars($u['role']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
  <footer class="footer">
    <div class="container">
      <div>© ETS_KAMAL — Gestion de stock</div>
      <div><a href="apropos.php">À propos</a></div>
    </div>
  </footer>
</body>
</html>
