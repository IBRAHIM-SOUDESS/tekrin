<?php
require_once __DIR__.'/config.php';
require_login();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!has_any_role(['administrateur','magasinier'])) { $msg = 'Accès interdit'; throw new Exception(''); }
    $type = $_POST['type'] ?? '';
    $reference = trim($_POST['reference'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $quantite = (int)($_POST['quantite'] ?? 0);
    $date_mvt = $_POST['date_mvt'] ?? date('Y-m-d');
    $responsable = current_user()['nom'] ?? 'N/A';
    if ($type && $reference && $nom && $quantite >= 0) {
      $pdo = db();
      $st = $pdo->prepare('SELECT id, quantite FROM produits WHERE reference = ?');
      $st->execute([$reference]);
      $row = $st->fetch();
      $imageRel = null;
      $fournisseurId = (int)($_POST['fournisseur_id'] ?? 0);
      $clientId = (int)($_POST['client_id'] ?? 0);
      $prixUnit = isset($_POST['prix_unitaire']) && $_POST['prix_unitaire'] !== '' ? (float)$_POST['prix_unitaire'] : null;
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
      if ($row) {
        $pid = (int)$row['id'];
        if ($type === 'entree' || $type === 'achat' || $type === 'retour') {
          $pdo->prepare('UPDATE produits SET quantite = quantite + ? WHERE id = ?')->execute([$quantite, $pid]);
        } else {
          $pdo->prepare('UPDATE produits SET quantite = GREATEST(0, quantite - ?) WHERE id = ?')->execute([$quantite, $pid]);
        }
        if ($imageRel) { $pdo->prepare('UPDATE produits SET image_path = ? WHERE id = ?')->execute([$imageRel, $pid]); }
      } else {
        $pdo->prepare('INSERT INTO produits(reference, nom, quantite, seuil_alerte, image_path) VALUES(?,?,?,?,?)')->execute([$reference,$nom, ($type==='entree'||$type==='achat'||$type==='retour')?$quantite:0, 0, $imageRel]);
      }
      $pdo->prepare('INSERT INTO mouvements(type, reference_produit, quantite, date_mouvement, responsable, prix_unitaire, fournisseur_id, client_id) VALUES(?,?,?,?,?,?,?,?)')->execute([$type,$reference,$quantite,$date_mvt,$responsable, $prixUnit, $fournisseurId?:null, $clientId?:null]);
      $msg = 'Mouvement enregistré';
    } else {
      $msg = 'Champs requis manquants';
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
  <title>Mouvements · Gestion de stock</title>
  <link rel="stylesheet" href="assets/styles.css">
  <script defer src="assets/app.js"></script>
</head>
<body>
  <header class="topbar">
    <div class="brand"><img src="assets/logo.svg" class="logo-img" alt="ETS_KAMAL">Gestion de stock</div>
    <nav class="nav">
      <a href="dashboard.php">Tableau de bord</a>
      <a href="produits.php">Stock</a>
      <a href="mouvements.php" class="active">Mouvements</a>
      <?php if (is_role('administrateur')): ?><a href="utilisateurs.php">Utilisateurs</a><?php endif; ?>
      <a href="fournisseurs.php">Fournisseurs</a>
      <a href="clients.php">Clients</a>
      <a href="historique.php">Historique</a>
      <a href="apropos.php">À propos</a>
      <a href="logout.php">Déconnexion</a>
    </nav>
  </header>
  <main class="container">
    <h1>Enregistrer un mouvement</h1>
    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if (has_any_role(['administrateur','magasinier'])): ?>
    <form method="post" class="card" enctype="multipart/form-data">
      <div class="grid">
        <div>
          <label>Type</label>
          <select name="type" required>
            <option value="entree">Entrée</option>
            <option value="sortie">Sortie</option>
            <option value="achat">Achat</option>
            <option value="vente">Vente</option>
            <option value="retour">Retour</option>
          </select>
        </div>
        <div>
          <label>Référence du produit</label>
          <input name="reference" required>
        </div>
        <div>
          <label>Nom du produit</label>
          <input name="nom" required>
        </div>
        <div>
          <label>Quantité</label>
          <input type="number" min="0" name="quantite" required>
        </div>
        <div>
          <label>Prix unitaire (optionnel)</label>
          <input type="number" step="0.01" min="0" name="prix_unitaire">
        </div>
        <div>
          <label>Date</label>
          <input type="date" name="date_mvt" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div>
          <label>Fournisseur</label>
          <select name="fournisseur_id">
            <option value="">—</option>
            <?php foreach (db()->query('SELECT id, nom FROM fournisseurs ORDER BY nom ASC') as $f): ?>
            <option value="<?php echo (int)$f['id']; ?>"><?php echo htmlspecialchars($f['nom']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Client</label>
          <select name="client_id">
            <option value="">—</option>
            <?php foreach (db()->query('SELECT id, nom FROM clients ORDER BY nom ASC') as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['nom']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Image (optionnel)</label>
          <input type="file" name="image" accept="image/*">
        </div>
      </div>
      <button type="submit" class="btn">Enregistrer</button>
    </form>
    <?php else: ?>
    <div class="card">Accès en écriture non autorisé pour votre rôle.
      <div class="inline" style="margin-top:8px">
        <a href="historique.php" class="btn btn-small">Voir l’historique</a>
        <a href="produits.php" class="btn btn-small">Voir le stock</a>
      </div>
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
