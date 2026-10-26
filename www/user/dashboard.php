<?php
require_once __DIR__.'/config.php';
require_login();
$user = current_user();
$pdo = db();
$totalProduits = (int)$pdo->query('SELECT COUNT(*) FROM produits')->fetchColumn();
$totalQuantite = (int)$pdo->query('SELECT COALESCE(SUM(quantite),0) FROM produits')->fetchColumn();
$lowCount = (int)$pdo->query('SELECT COUNT(*) FROM produits WHERE quantite <= seuil_alerte')->fetchColumn();
$mvtToday = (int)$pdo->query("SELECT COUNT(*) FROM mouvements WHERE date_mouvement = CURDATE()")->fetchColumn();
$stmt = $pdo->query('SELECT id, reference, nom, quantite, seuil_alerte FROM produits WHERE quantite <= seuil_alerte ORDER BY quantite ASC');
$alerts = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tableau de bord · Gestion de stock</title>
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
      <?php if (is_role('administrateur')): ?><a href="utilisateurs.php">Utilisateurs</a><?php endif; ?>
      <a href="apropos.php">À propos</a>
      <a href="logout.php">Déconnexion</a>
    </nav>
  </header>
  <main class="container">
    <h1>Tableau de bord</h1>
    <div class="inline" style="gap:12px; margin-bottom:12px">
      <div class="card" style="flex:1"><strong>Total produits</strong><div style="font-size:24px; font-weight:700; margin-top:6px"><?php echo (int)$totalProduits; ?></div></div>
      <div class="card" style="flex:1"><strong>Total quantités</strong><div style="font-size:24px; font-weight:700; margin-top:6px"><?php echo (int)$totalQuantite; ?></div></div>
      <div class="card" style="flex:1"><strong>Proches de rupture</strong><div style="font-size:24px; font-weight:700; margin-top:6px"><?php echo (int)$lowCount; ?></div></div>
      <div class="card" style="flex:1"><strong>Mouvements aujourd’hui</strong><div style="font-size:24px; font-weight:700; margin-top:6px"><?php echo (int)$mvtToday; ?></div></div>
    </div>
    <div class="card">
      <h2>Produits proches de rupture</h2>
      <?php if (!$alerts): ?>
        <p>Aucun produit proche de rupture.</p>
      <?php else: ?>
      <table class="table">
        <thead><tr><th>Référence</th><th>Produit</th><th>Quantité</th><th>Seuil</th></tr></thead>
        <tbody>
          <?php foreach ($alerts as $p): ?>
          <tr class="row-alert">
            <td><?php echo htmlspecialchars($p['reference']); ?></td>
            <td><?php echo htmlspecialchars($p['nom']); ?></td>
            <td><?php echo (int)$p['quantite']; ?></td>
            <td><?php echo (int)$p['seuil_alerte']; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
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
