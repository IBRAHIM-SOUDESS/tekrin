<?php require_once __DIR__ . '/includes/functions.php'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>
<section class="banner">
  <div class="container">
    <h1>Matériel informatique • Maintenance • Réparation</h1>
    <p>Ordinateurs, imprimantes, accessoires de qualité. Services professionnels à Yaoundé.</p>
    <a class="btn btn-primary" href="<?php echo base('products.php'); ?>">Découvrir le catalogue</a>
  </div>
</section>
<section>
  <h2>Produits en vedette</h2>
  <div class="grid">
    <?php foreach (getFeaturedProducts(8) as $p): ?>
      <div class="card">
        <img src="<?php echo sanitize($p['image']); ?>" alt="<?php echo sanitize($p['nom']); ?>">
        <div class="card-body">
          <div><?php echo sanitize($p['nom']); ?></div>
          <div class="price"><?php echo formatPrice($p['prix']); ?></div>
          <div class="form-actions">
            <a class="btn" href="<?php echo base('product.php?id='.(int)$p['id']); ?>">Voir</a>
            <form method="post" action="<?php echo base('cart.php'); ?>">
              <?php echo csrfField(); ?>
              <input type="hidden" name="add" value="<?php echo (int)$p['id']; ?>">
              <button class="btn btn-primary" type="submit">Ajouter au panier</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php if (galleryPublicEnabled()): ?>
<section>
  <h2>Galerie</h2>
  <?php $galleryProducts = getFeaturedProducts(15); ?>
  <div class="gallery-grid">
    <?php if ($galleryProducts): ?>
      <?php foreach ($galleryProducts as $p): $src = !empty($p['image']) ? sanitize($p['image']) : 'https://placehold.co/640x360?text=Ets_Kamal'; ?>
        <a class="tile" href="<?php echo base('product.php?id='.(int)$p['id']); ?>">
          <img loading="lazy" src="<?php echo $src; ?>" alt="<?php echo sanitize($p['nom']); ?>">
          <span class="badge">Ets_Kamal</span>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php for($i=1;$i<=15;$i++): ?>
        <a class="tile" href="<?php echo base('products.php'); ?>">
          <img loading="lazy" src="https://placehold.co/640x360?text=Ets_Kamal" alt="Galerie <?php echo $i; ?>">
          <span class="badge">Ets_Kamal</span>
        </a>
      <?php endfor; ?>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>