<?php
require_once __DIR__.'/config.php';
$pdo = db();
$roles = ['administrateur','manager','magasinier','vendeur'];
$counts = [];
try {
  foreach ($roles as $r) {
    $st = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE role = ?');
    $st->execute([$r]);
    $counts[$r] = (int)$st->fetchColumn();
  }
} catch (Throwable $e) {
  foreach ($roles as $r) { $counts[$r] = 1; }
}
$total = array_sum($counts);
if ($total <= 0) { foreach ($roles as $r) { $counts[$r] = 1; } $total = 4; }
$deg = [];
$acc = 0;
foreach ($roles as $r) { $acc += 360 * ($counts[$r]/$total); $deg[] = $acc; }
$perc = [];
foreach ($roles as $r) { $perc[$r] = round(100 * ($counts[$r]/$total)); }
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>À propos · Gestion de stock</title>
  <link   rel="stylesheet" href="assets/styles.css">
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
      <a href="apropos.php" class="active">À propos</a>
      <a href="logout.php">Déconnexion</a>
    </nav>
  </header>
  <main class="container">
    <h1>À propos</h1>
    <div class="card">
      <p><strong>Bienvenue sur la plateforme de gestion de stock de l’entreprise ETS Kamal</strong></p>
      <p>Située au cœur de Yaoundé, ETS Kamal est une entreprise dynamique spécialisée dans la vente de produits variés, la maintenance informatique et bien plus encore. Pour optimiser la gestion quotidienne de ses produits, ETS Kamal met en place ce système digital de gestion de stock, moderne, intuitif et accessible.</p>
      <h3>Objectifs de la plateforme</h3>
      <ul>
        <li>Centraliser la gestion de tous les produits disponibles.</li>
        <li>Suivre efficacement les mouvements de stock (entrées, sorties).</li>
        <li>Éviter les ruptures et les excédents de stock.</li>
        <li>Automatiser la génération de rapports statistiques.</li>
        <li>Offrir une interface multi‑utilisateur sécurisée.</li>
      </ul>
      <h3>Fonctionnalités principales</h3>
      <ul>
        <li>Gestion des produits: ajout, édition, suppression avec description détaillée (nom, prix, quantité, image).</li>
        <li>Statistiques en temps réel via un tableau de bord clair.</li>
        <li>Suivi des fournisseurs et des clients.</li>
        <li>Historique complet des opérations (ventes, achats, retours).</li>
        <li>Alertes automatiques pour niveaux de stock critiques.</li>
        <li>Système d’authentification sécurisé avec rôles d’utilisateur (admin, vendeur, etc.).</li>
      </ul>
    </div>
    <div class="card">
      <h2>Répartition des rôles</h2>
      <p>Vue circulaire et responsive des utilisateurs par rôle au sein d’ETS Kamal.</p>
      <div class="chart-wrap">
        <div class="pie" style="background: conic-gradient(#10b981 0deg <?php echo (int)round($deg[0]); ?>deg, #6366f1 <?php echo (int)round($deg[0]); ?>deg <?php echo (int)round($deg[1]); ?>deg, #0ea5e9 <?php echo (int)round($deg[1]); ?>deg <?php echo (int)round($deg[2]); ?>deg, #f59e0b <?php echo (int)round($deg[2]); ?>deg 360deg);"></div>
        <ul class="legend">
          <li><span class="swatch sw-admin"></span> Administrateur (<?php echo (int)$counts['administrateur']; ?>, <?php echo (int)$perc['administrateur']; ?>%)</li>
          <li><span class="swatch sw-manager"></span> Manager (<?php echo (int)$counts['manager']; ?>, <?php echo (int)$perc['manager']; ?>%)</li>
          <li><span class="swatch sw-magasinier"></span> Magasinier (<?php echo (int)$counts['magasinier']; ?>, <?php echo (int)$perc['magasinier']; ?>%)</li>
          <li><span class="swatch sw-vendeur"></span> Vendeur (<?php echo (int)$counts['vendeur']; ?>, <?php echo (int)$perc['vendeur']; ?>%)</li>
        </ul>
      </div>
    </div>
  </main>
  <footer class="footer">
    <div class="container">
      <div>© ETS_KAMAL — Gestion de stock</div>
      <div><a href="apropos.php" class="active">À propos</a></div>
    </div>
  </footer>
</body>
</html>
