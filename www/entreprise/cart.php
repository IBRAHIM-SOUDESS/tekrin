<?php require_once __DIR__ . '/includes/functions.php'; ?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  if (isset($_POST['add'])) {
    $id = (int)$_POST['add'];
    $q = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    cartAdd($id, $q);
  }
  if (isset($_POST['update'])) {
    foreach ($_POST['qty'] as $pid => $qty) { cartUpdate((int)$pid, (int)$qty); }
  }
  if (isset($_POST['remove'])) { cartRemove((int)$_POST['remove']); }
  header('Location: ' . base('cart.php'));
  exit;
}
$cart = cartGet();
$items = [];
if ($cart) {
  $ids = implode(',', array_map('intval', array_keys($cart)));
  $items = db()->query("SELECT * FROM produits WHERE id IN ($ids)")->fetchAll();
}
$total = cartTotal();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section>
  <h2>Panier</h2>
  <?php if (!$items): ?>
    <p>Votre panier est vide.</p>
  <?php else: ?>
    <form id="cart-form" method="post" class="form">
      <?php echo csrfField(); ?>
      <table class="table">
        <thead><tr><th>Produit</th><th>Prix</th><th>Quantité</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): $pid=(int)$it['id']; $q=(int)$cart[$pid]; ?>
          <tr>
            <td><?php echo sanitize($it['nom']); ?></td>
            <td><?php echo formatPrice($it['prix']); ?></td>
            <td><input type="number" name="qty[<?php echo $pid; ?>]" min="1" value="<?php echo $q; ?>"></td>
            <td>
              <a class="btn" href="#" data-remove="<?php echo $pid; ?>">Supprimer</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div class="form-actions">
        <button class="btn" type="submit" name="update" value="1">Mettre à jour</button>
        <a class="btn btn-primary" href="<?php echo base('checkout.php'); ?>">Commander</a>
      </div>
      <div>Total: <?php echo formatPrice($total); ?></div>
    </form>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>