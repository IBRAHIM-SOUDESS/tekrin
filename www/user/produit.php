<?php
require_once __DIR__.'/config.php';
require_login();
$isManager = is_role('manager');
if (!$isManager && !has_any_role(['administrateur','magasinier'])) { header('Location: produits.php'); exit; }
$pdo = db();
$msg = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isCreate = $id === 0;
if ($isManager && $isCreate) { header('Location: produits.php'); exit; }
$prod = null;
if ($id) {
  $st = $pdo->prepare('SELECT * FROM produits WHERE id = ?');
  $st->execute([$id]);
  $prod = $st->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete' && has_any_role(['administrateur','magasinier']) && $id) {
      $st = $pdo->prepare('SELECT image_path FROM produits WHERE id = ?');
      $st->execute([$id]);
      $oldPath = $st->fetchColumn();
      if ($oldPath) { $oldFs = __DIR__.'/'.$oldPath; if (is_file($oldFs)) { @unlink($oldFs); } }
      $pdo->prepare('DELETE FROM produits WHERE id = ?')->execute([$id]);
      $msg = 'Produit supprimé';
    } else if ($isManager) {
      $seuil = (int)($_POST['seuil_alerte'] ?? 0);
      if ($id) {
        $upd = $pdo->prepare('UPDATE produits SET seuil_alerte=? WHERE id=?');
        $upd->execute([$seuil,$id]);
        $msg = 'Seuil mis à jour';
      }
    } else {
      $reference = trim($_POST['reference'] ?? '');
      $nom = trim($_POST['nom'] ?? '');
      $quantite = (int)($_POST['quantite'] ?? 0);
      $seuil = (int)($_POST['seuil_alerte'] ?? 0);
      $prix = (float)($_POST['prix'] ?? ($prod['prix'] ?? 0));
      $description = trim($_POST['description'] ?? ($prod['description'] ?? ''));
      $imageRel = $prod['image_path'] ?? null;
      if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $size = (int)$_FILES['image']['size'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if ($size <= 2*1024*1024 && in_array($ext, $allowed, true)) {
          $dir = __DIR__.'/assets/uploads';
          if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
          $name = uniqid('prod_', true).'.'.$ext;
          $destFs = $dir.'/'.$name;
          $imageRel = 'assets/uploads/'.$name;
          @move_uploaded_file($_FILES['image']['tmp_name'], $destFs);
        }
      }
      if ($id) {
        $upd = $pdo->prepare('UPDATE produits SET reference=?, nom=?, quantite=?, seuil_alerte=?, prix=?, description=?, image_path=? WHERE id=?');
        $upd->execute([$reference,$nom,$quantite,$seuil,$prix,$description,$imageRel,$id]);
        $msg = 'Produit mis à jour';
      } else {
        $ins = $pdo->prepare('INSERT INTO produits(reference, nom, quantite, seuil_alerte, prix, description, image_path) VALUES(?,?,?,?,?,?,?)');
        $ins->execute([$reference,$nom,$quantite,$seuil,$prix,$description,$imageRel]);
        $msg = 'Produit créé';
      }
    }
  } catch (Throwable $e) {
    $msg = 'Erreur base de données';
  }
}
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $id?'Éditer':'Créer'; ?> un produit · Gestion de stock</title>
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
    <h1><?php echo $id?'Éditer':'Créer'; ?> un produit · Gestion de stock</h1>
    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <form method="post" class="card" enctype="multipart/form-data">
      <div class="grid">
        <div>
          <label>Référence</label>
          <input name="reference" value="<?php echo htmlspecialchars($prod['reference'] ?? ''); ?>" <?php echo $isManager?'disabled':''; ?> required>
        </div>
        <div>
          <label>Nom</label>
          <input name="nom" value="<?php echo htmlspecialchars($prod['nom'] ?? ''); ?>" <?php echo $isManager?'disabled':''; ?> required>
        </div>
        <div>
          <label>Quantité</label>
          <input type="number" min="0" name="quantite" value="<?php echo (int)($prod['quantite'] ?? 0); ?>" <?php echo $isManager?'disabled':''; ?> required>
        </div>
        <div>
          <label>Seuil d’alerte</label>
          <input type="number" min="0" name="seuil_alerte" value="<?php echo (int)($prod['seuil_alerte'] ?? 0); ?>" required>
        </div>
        <div>
          <label>Prix</label>
          <input type="number" step="0.01" min="0" name="prix" value="<?php echo number_format((float)($prod['prix'] ?? 0),2,'.',''); ?>" <?php echo $isManager?'disabled':''; ?> required>
        </div>
        <div class="span">
          <label>Description</label>
          <input name="description" value="<?php echo htmlspecialchars($prod['description'] ?? ''); ?>" <?php echo $isManager?'disabled':''; ?>>
        </div>
        <?php if (!$isManager): ?>
        <div class="span">
          <label>Image (optionnel)</label>
          <input type="file" name="image" accept="image/*">
        </div>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn"><?php echo $id?($isManager?'Enregistrer le seuil':'Enregistrer'):'Créer'; ?></button>
    </form>
    <?php if ($id && has_any_role(['administrateur','magasinier'])): ?>
    <div class="card">
      <form method="post" class="inline" onsubmit="return confirm('Supprimer ce produit ?');">
        <input type="hidden" name="action" value="delete">
        <button type="submit" class="btn">Supprimer le produit</button>
      </form>
    </div>
    <?php endif; ?>
  </main>
  <footer class="footer">
    <div class="container">
      <div>© ETS_KAMAL — Gestion de stock</div>
      <div><a href="apropos.php">À propos</a></div>
    </div>
  </footer>
</body>
</html>
