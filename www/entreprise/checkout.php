<?php require_once __DIR__ . '/includes/functions.php'; ?>
<?php
$cart = cartGet();
if (!$cart) { header('Location: ' . base('cart.php')); exit; }
$errors = [];
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $nom = trim($_POST['nom'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telephone = trim($_POST['telephone'] ?? '');
  $adresse = trim($_POST['adresse'] ?? '');
  $paiement = trim($_POST['paiement'] ?? '');
  if ($nom==='') $errors[] = 'nom';
  if ($telephone==='') $errors[] = 'telephone';
  if ($adresse==='') $errors[] = 'adresse';
  if ($paiement==='') $errors[] = 'paiement';
  if (!$errors) {
    $pdo = db();
    $pdo->beginTransaction();
    $clientId = null;
    if ($email !== '') {
      $st = $pdo->prepare('SELECT id FROM clients WHERE email = :e');
      $st->execute([':e'=>$email]);
      $row = $st->fetch();
      if ($row) { $clientId = (int)$row['id']; }
    }
    if ($clientId === null) {
      $pass = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
      $st = $pdo->prepare('INSERT INTO clients(nom,email,telephone,mot_de_passe) VALUES(:n,:e,:t,:p)');
      $st->execute([':n'=>$nom, ':e'=>$email, ':t'=>$telephone, ':p'=>$pass]);
      $clientId = (int)$pdo->lastInsertId();
    }
    $st = $pdo->prepare('INSERT INTO commandes(id_client,date,statut) VALUES(:c,NOW(),:s)');
    $st->execute([':c'=>$clientId, ':s'=>'en_attente']);
    $cmdId = (int)$pdo->lastInsertId();
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $rows = $pdo->query("SELECT id, prix FROM produits WHERE id IN ($ids)")->fetchAll();
    foreach ($rows as $r) {
      $pid = (int)$r['id'];
      $q = (int)$cart[$pid];
      $st = $pdo->prepare('INSERT INTO commande_produits(id_commande,id_produit,quantite) VALUES(:c,:p,:q)');
      $st->execute([':c'=>$cmdId, ':p'=>$pid, ':q'=>$q]);
    }
    $pdo->commit();
    notifyNewOrder($cmdId, $nom, $telephone, $email, $adresse, $paiement);
    cartSave([]);
    $done = true;
  }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section>
  <h2>Commande</h2>
  <?php if ($done): ?>
    <p>Merci. Votre commande a été enregistrée.</p>
    <a class="btn" href="<?php echo base(''); ?>">Retour à l’accueil</a>
  <?php else: ?>
    <form method="post" class="form">
      <?php echo csrfField(); ?>
      <input name="nom" placeholder="Nom complet" required>
      <input name="email" type="email" placeholder="Email (optionnel)">
      <input name="telephone" placeholder="Téléphone" required>
      <input name="adresse" placeholder="Adresse" required>
      <select name="paiement" required>
        <option value="">Mode de paiement</option>
        <option>Espèces</option>
        <option>Mobile Money</option>
        <option>Carte bancaire</option>
      </select>
      <button class="btn btn-primary" type="submit">Valider</button>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>