<?php
require_once __DIR__.'/config.php';
require_login();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && has_any_role(['administrateur','magasinier'])) {
  $nom = trim($_POST['nom'] ?? '');
  $contact = trim($_POST['contact'] ?? '');
  if ($nom) {
    db()->prepare('INSERT INTO fournisseurs(nom, contact) VALUES(?,?)')->execute([$nom,$contact]);
    $msg = 'Fournisseur ajouté';
  }
}
$list = db()->query('SELECT id, nom, contact FROM fournisseurs ORDER BY nom ASC')->fetchAll();
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Fournisseurs · Gestion de stock</title>
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
      <a href="fournisseurs.php" class="active">Fournisseurs</a>
      <a href="clients.php">Clients</a>
      <a href="historique.php">Historique</a>
      <?php if (is_role('administrateur')): ?><a href="utilisateurs.php">Utilisateurs</a><?php endif; ?>
      <a href="apropos.php">À propos</a>
      <a href="logout.php">Déconnexion</a>
    </nav>
  </header>
  <main class="container">
    <h1>Fournisseurs</h1>
    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if (has_any_role(['administrateur','manager'])): ?>
    <form method="post" class="card">
      <div class="grid">
        <div>
          <label>Nom</label>
          <input name="nom" required>
        </div>
        <div>
          <label>Contact</label>
          <input name="contact">
        </div>
      </div>
      <button type="submit" class="btn">Ajouter</button>
    </form>
    <?php endif; ?>
    <div class="card">
      <table class="table">
        <thead><tr><th>Nom</th><th>Contact</th></tr></thead>
        <tbody>
          <?php foreach ($list as $f): ?>
          <tr>
            <td><?php echo htmlspecialchars($f['nom']); ?></td>
            <td><?php echo htmlspecialchars($f['contact']); ?></td>
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
