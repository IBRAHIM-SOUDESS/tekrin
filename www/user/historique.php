<?php
require_once __DIR__.'/config.php';
require_login();
$msg = '';
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (has_any_role(['administrateur','magasinier'])) {
      $action = $_POST['action'] ?? '';
      if ($action === 'purge') {
        $ref = trim($_POST['reference'] ?? '');
        if ($ref !== '') {
          $st = $pdo->prepare('DELETE FROM mouvements WHERE reference_produit = ?');
          $st->execute([$ref]);
          $sum = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type IN ('entree','achat','retour') THEN quantite WHEN type IN ('sortie','vente') THEN -quantite ELSE 0 END),0) FROM mouvements WHERE reference_produit = ?");
          $sum->execute([$ref]);
          $delta = (int)$sum->fetchColumn();
          $upd = $pdo->prepare('UPDATE produits SET quantite = GREATEST(0, ?) WHERE reference = ?');
          $upd->execute([$delta, $ref]);
          $msg = 'Historique supprimé pour la référence';
        } else {
          $msg = 'Référence manquante';
        }
      } elseif ($action === 'delete_one') {
        $mid = (int)($_POST['mouvement_id'] ?? 0);
        if ($mid > 0) {
          $refq = $pdo->prepare('SELECT reference_produit FROM mouvements WHERE id = ?');
          $refq->execute([$mid]);
          $ref = $refq->fetchColumn();
          $pdo->prepare('DELETE FROM mouvements WHERE id = ?')->execute([$mid]);
          if ($ref) {
            $sum = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type IN ('entree','achat','retour') THEN quantite WHEN type IN ('sortie','vente') THEN -quantite ELSE 0 END),0) FROM mouvements WHERE reference_produit = ?");
            $sum->execute([$ref]);
            $delta = (int)$sum->fetchColumn();
            $upd = $pdo->prepare('UPDATE produits SET quantite = GREATEST(0, ?) WHERE reference = ?');
            $upd->execute([$delta, $ref]);
          }
          $msg = 'Mouvement supprimé';
        }
      }
    } else {
      $msg = 'Accès interdit';
    }
  } catch (Throwable $e) {
    $msg = 'Erreur base de données';
  }
}
$type = $_GET['type'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT m.id, m.type, m.reference_produit, m.quantite, m.date_mouvement, m.responsable, m.prix_unitaire, f.nom AS fournisseur, c.nom AS client, p.nom AS produit_nom FROM mouvements m LEFT JOIN fournisseurs f ON f.id=m.fournisseur_id LEFT JOIN clients c ON c.id=m.client_id LEFT JOIN produits p ON p.reference=m.reference_produit WHERE 1=1';
$params = [];
if ($type !== '') { $sql .= ' AND m.type = ?'; $params[] = $type; }
if ($from !== '') { $sql .= ' AND m.date_mouvement >= ?'; $params[] = $from; }
if ($to !== '') { $sql .= ' AND m.date_mouvement <= ?'; $params[] = $to; }
if ($q !== '') { $sql .= ' AND (m.reference_produit LIKE ? OR p.nom LIKE ?)'; $params[] = '%'.$q.'%'; $params[] = '%'.$q.'%'; }
$sql .= ' ORDER BY m.date_mouvement DESC, m.id DESC LIMIT 500';
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Historique · Gestion de stock</title>
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
      <a href="fournisseurs.php">Fournisseurs</a>
      <a href="clients.php">Clients</a>
      <a href="historique.php" class="active">Historique</a>
      <?php if (is_role('administrateur')): ?><a href="utilisateurs.php">Utilisateurs</a><?php endif; ?>
      <a href="apropos.php">À propos</a>
      <a href="logout.php">Déconnexion</a>
    </nav>
  </header>
  <main class="container">
    <h1>Historique des opérations</h1>
    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <div class="card">
      <form method="get" class="inline">
        <select name="type">
          <option value="">Tous types</option>
          <?php foreach (['entree','sortie','achat','vente','retour'] as $t): ?>
          <option value="<?php echo $t; ?>" <?php echo $type===$t?'selected':''; ?>><?php echo ucfirst($t); ?></option>
          <?php endforeach; ?>
        </select>
        <input type="date" name="from" value="<?php echo htmlspecialchars($from); ?>">
        <input type="date" name="to" value="<?php echo htmlspecialchars($to); ?>">
        <input type="text" name="q" placeholder="Nom ou référence" value="<?php echo htmlspecialchars($q); ?>">
        <button type="submit" class="btn">Filtrer</button>
      </form>
    </div>
    <?php if (has_any_role(['administrateur','magasinier'])): ?>
    <div class="card">
      <form method="post" class="inline" onsubmit="return confirm('Confirmer la suppression ?');">
        <select name="action">
          <option value="purge">Supprimer historique par référence</option>
          <option value="delete_one">Supprimer mouvement par ID</option>
        </select>
        <input type="text" name="reference" placeholder="Référence produit (pour historique)">
        <input type="number" min="1" name="mouvement_id" placeholder="ID mouvement (pour une ligne)">
        <button type="submit" class="btn">Supprimer</button>
      </form>
    </div>
    <?php endif; ?>
    <div class="card">
      <table class="table">
        <thead><tr><th>Date</th><th>Type</th><th>Référence</th><th>Produit</th><th>Quantité</th><th>Prix unitaire</th><th>Fournisseur</th><th>Client</th><th>Responsable</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['date_mouvement']); ?></td>
            <td><?php echo htmlspecialchars($r['type']); ?></td>
            <td><?php echo htmlspecialchars($r['reference_produit']); ?></td>
            <td><?php echo htmlspecialchars($r['produit_nom'] ?? ''); ?></td>
            <td><?php echo (int)$r['quantite']; ?></td>
            <td><?php echo $r['prix_unitaire']!==null ? number_format((float)$r['prix_unitaire'],2,'.','') : ''; ?></td>
            <td><?php echo htmlspecialchars($r['fournisseur'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['client'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['responsable']); ?></td>
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
