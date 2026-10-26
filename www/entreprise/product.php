<?php require_once __DIR__ . '/includes/functions.php'; ?>
<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p = $id ? getProductById($id) : null;
if (!$p) { header('Location: /products.php'); exit; }
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section>
  <div class="card" style="flex-direction:row">
    <img style="max-width:400px" src="<?php echo sanitize($p['image']); ?>" alt="<?php echo sanitize($p['nom']); ?>">
    <div class="card-body">
      <h2><?php echo sanitize($p['nom']); ?></h2>
      <div class="price"><?php echo formatPrice($p['prix']); ?></div>
      <p><?php echo nl2br(sanitize($p['description'])); ?></p>
      <form method="post" action="<?php echo base('cart.php'); ?>" class="form">
        <?php echo csrfField(); ?>
        <input type="hidden" name="add" value="<?php echo (int)$p['id']; ?>">
        <input type="number" name="qty" min="1" value="1">
        <button class="btn btn-primary" type="submit">Ajouter au panier</button>
      </form>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>