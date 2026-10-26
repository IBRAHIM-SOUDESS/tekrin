<?php
require_once __DIR__.'/config.php';
require_login();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'seuil' && has_any_role(['administrateur','magasinier','manager'])) {
  $id = (int)($_POST['produit_id'] ?? 0);
  $seuil = (int)($_POST['seuil_alerte'] ?? 0);
  if ($id > 0 && $seuil >= 0) {
    $stmt = db()->prepare('UPDATE produits SET seuil_alerte = ? WHERE id = ?');
    $stmt->execute([$seuil, $id]);
    $msg = 'Seuil mis à jour';
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'image' && has_any_role(['administrateur','magasinier'])) {
  $id = (int)($_POST['produit_id'] ?? 0);
  if ($id > 0 && !empty($_FILES['image']) && isset($_FILES['image']['tmp_name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $size = (int)$_FILES['image']['size'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];
    if ($size <= 2*1024*1024 && in_array($ext, $allowed, true)) {
      $dir = __DIR__.'/assets/uploads';
      if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
      $name = uniqid('prod_', true).'.'.$ext;
      $destFs = $dir.'/'.$name;
      $destRel = 'assets/uploads/'.$name;
      $pdo = db();
      $old = $pdo->prepare('SELECT image_path FROM produits WHERE id = ?');
      $old->execute([$id]);
      $oldPath = $old->fetchColumn();
      if (@move_uploaded_file($_FILES['image']['tmp_name'], $destFs)) {
        if ($oldPath) {
          $oldFs = __DIR__.'/'.$oldPath;
          if (is_file($oldFs)) { @unlink($oldFs); }
        }
        $upd = $pdo->prepare('UPDATE produits SET image_path = ? WHERE id = ?');
        $upd->execute([$destRel, $id]);
        $msg = 'Image mise à jour';
      } else {
        $msg = 'Échec de l’envoi de l’image';
      }
    } else {
      $msg = 'Image non valide (format ou taille)';
    }
  } else {
    $msg = 'Aucun fichier sélectionné';
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && has_any_role(['administrateur','magasinier'])) {
  $id = (int)($_POST['produit_id'] ?? 0);
  if ($id > 0) {
    $pdo = db();
    $st = $pdo->prepare('SELECT image_path FROM produits WHERE id = ?');
    $st->execute([$id]);
    $oldPath = $st->fetchColumn();
    if ($oldPath) { $oldFs = __DIR__.'/'.$oldPath; if (is_file($oldFs)) { @unlink($oldFs); } }
    $pdo->prepare('DELETE FROM produits WHERE id = ?')->execute([$id]);
    $msg = 'Produit supprimé';
  }
}
$q = trim($_GET['q'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$per = (int)($_GET['per'] ?? 10);
if ($page < 1) $page = 1;
if ($per < 5) $per = 10; if ($per > 50) $per = 50;
$offset = ($page - 1) * $per;
$pdo = db();
$sort = strtolower($_GET['sort'] ?? 'nom');
$dir = strtolower($_GET['dir'] ?? 'asc');
$allowedSort = ['nom','reference','quantite','seuil_alerte','prix'];
if (!in_array($sort, $allowedSort, true)) $sort = 'nom';
if (!in_array($dir, ['asc','desc'], true)) $dir = 'asc';
$order = $sort.' '.strtoupper($dir);
if ($q !== '') {
$like = '%'.$q.'%';
  $count = $pdo->prepare('SELECT COUNT(*) FROM produits WHERE reference LIKE ? OR nom LIKE ?');
  $count->execute([$like, $like]);
  $total = (int)$count->fetchColumn();
  $stmt = $pdo->prepare('SELECT id, reference, nom, quantite, seuil_alerte, prix, image_path FROM produits WHERE reference LIKE ? OR nom LIKE ? ORDER BY '.$order.' LIMIT '.$per.' OFFSET '.$offset);
  $stmt->execute([$like, $like]);
} else {
  $total = (int)$pdo->query('SELECT COUNT(*) FROM produits')->fetchColumn();
  $stmt = $pdo->query('SELECT id, reference, nom, quantite, seuil_alerte, prix, image_path FROM produits ORDER BY '.$order.' LIMIT '.$per.' OFFSET '.$offset);
}
$pages = max(1, (int)ceil($total / $per));
$produits = $stmt->fetchAll();
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Stock actuel · Gestion de stock</title>
  <link rel="stylesheet" href="assets/styles.css">
  <script defer src="assets/app.js"></script>
</head>
<body>
  <header class="topbar">
    <div class="brand"><img src="assets/logo.svg" class="logo-img" alt="ETS_KAMAL">Gestion de stock</div>
    <nav class="nav">
      <a href="dashboard.php">Tableau de bord</a>
      <a href="produits.php" class="active">Stock</a>
      <a href="mouvements.php">Mouvements</a>
      <?php if (is_role('administrateur')): ?><a href="utilisateurs.php">Utilisateurs</a><?php endif; ?>
      <a href="apropos.php">À propos</a>
      <a href="logout.php">Déconnexion</a>
    </nav>
  </header>
  <main class="container">
    <h1>Stock actuel</h1>
    <div class="card">
      <form method="get" class="inline">
        <input type="text" name="q" placeholder="Rechercher (nom/référence)" value="<?php echo htmlspecialchars($q); ?>" style="max-width:320px">
        <select name="per">
          <?php foreach ([10,20,50] as $opt): ?>
          <option value="<?php echo $opt; ?>" <?php echo $per===$opt?'selected':''; ?>><?php echo $opt; ?>/page</option>
          <?php endforeach; ?>
        </select>
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
        <button type="submit" class="btn">Filtrer</button>
      </form>
    </div>
      <?php if (has_any_role(['administrateur','magasinier'])): ?>
      <div class="card"><a href="produit.php" class="btn">Ajouter un produit</a></div>
      <?php endif; ?>
    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <table class="table">
      <?php $toggle = function($col) use($q,$per,$sort,$dir){ $nd = ($sort===$col && $dir==='asc')?'desc':'asc'; return '?q='.urlencode($q).'&per='.(int)$per.'&sort='.$col.'&dir='.$nd; }; ?>
      <thead><tr>
        <th>Image</th>
        <th><a href="<?php echo $toggle('reference'); ?>">Référence</a></th>
        <th><a href="<?php echo $toggle('nom'); ?>">Produit</a></th>
        <th><a href="<?php echo $toggle('quantite'); ?>">Quantité</a></th>
        <th><a href="<?php echo $toggle('seuil_alerte'); ?>">Seuil</a></th>
        <th>État</th>
        <th><a href="<?php echo $toggle('prix'); ?>">Prix</a></th>
        <th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach ($produits as $p): $alerte = $p['quantite'] <= $p['seuil_alerte']; $ratio = ($p['seuil_alerte']>0)? min(100, (int)floor(($p['quantite']/$p['seuil_alerte'])*100)) : 100; ?>
        <tr class="<?php echo $alerte ? 'row-alert' : ''; ?>">
          <td>
            <?php if (!empty($p['image_path'])): ?>
              <img src="<?php echo htmlspecialchars($p['image_path']); ?>" class="thumb" alt="Image produit">
            <?php else: ?>
              <div class="thumb-ph" title="Pas d’image"><?php echo htmlspecialchars(strtoupper(substr($p['nom'],0,2))); ?></div>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($p['reference']); ?></td>
          <td><?php echo htmlspecialchars($p['nom']); ?></td>
          <td><?php echo (int)$p['quantite']; ?></td>
          <td><?php echo (int)$p['seuil_alerte']; ?></td>
          <td>
            <span class="chip <?php echo $alerte ? 'chip-alert' : 'chip-ok'; ?>" title="Seuil: <?php echo (int)$p['seuil_alerte']; ?> | Qté: <?php echo (int)$p['quantite']; ?>">
              <?php echo $alerte ? 'Alerte' : 'OK'; ?>
            </span>
            <div class="bar" aria-label="Niveau de stock" title="<?php echo (int)$ratio; ?>% du seuil">
              <div class="bar-fill <?php echo $alerte ? 'bar-low' : 'bar-ok'; ?>" style="width: <?php echo (int)$ratio; ?>%"></div>
            </div>
          </td>
          <td><?php echo number_format((float)$p['prix'],2,'.',''); ?></td>
          <td>
            <a class="btn btn-small" href="produit.php?id=<?php echo (int)$p['id']; ?>">Détails</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="pagination inline" style="margin-top:12px">
      <?php $prev = max(1, $page-1); $next = min($pages, $page+1); ?>
      <a class="btn btn-small" href="?q=<?php echo urlencode($q); ?>&per=<?php echo (int)$per; ?>&page=<?php echo (int)$prev; ?>">Précédent</a>
      <span style="padding:6px 10px">Page <?php echo (int)$page; ?> / <?php echo (int)$pages; ?></span>
      <a class="btn btn-small" href="?q=<?php echo urlencode($q); ?>&per=<?php echo (int)$per; ?>&page=<?php echo (int)$next; ?>">Suivant</a>
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
