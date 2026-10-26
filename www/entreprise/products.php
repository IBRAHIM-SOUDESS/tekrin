<?php require_once __DIR__ . '/includes/functions.php'; ?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php
$cat = isset($_GET['category']) ? $_GET['category'] : null;
$q = isset($_GET['q']) ? trim($_GET['q']) : null;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
list($items, $total) = getProducts($cat, $page, 12, $q);
$pages = max(1, (int)ceil($total / 12));
$cats = getCategories();
?>
<section>
  <h2>Catalogue</h2>
  <div class="filter-bar">
    <form method="get">
      <select name="category">
        <option value="">Toutes catégories</option>
        <?php foreach ($cats as $c): $v=$c['categorie']; ?>
          <option value="<?php echo sanitize($v); ?>" <?php echo $cat===$v? 'selected':''; ?>><?php echo sanitize($v); ?></option>
        <?php endforeach; ?>
      </select>
      <input name="q" placeholder="Recherche" value="<?php echo $q? sanitize($q):''; ?>">
      <button class="btn" type="submit">Filtrer</button>
    </form>
  </div>
  <?php if (galleryPublicEnabled()): ?>
    <h3>Galerie du catalogue</h3>
    <?php $galleryCat = $cat ? $cat : 'Ordinateurs'; list($galleryProducts, $galleryTotal) = getProducts($galleryCat, 1, 100); ?>
    <div class="gallery-grid">
      <?php if ($galleryProducts): ?>
        <?php foreach ($galleryProducts as $p): $src = !empty($p['image']) ? sanitize($p['image']) : 'https://placehold.co/640x360?text=Ets_Kamal'; ?>
          <a class="tile" href="<?php echo base('product.php?id='.(int)$p['id']); ?>">
            <img loading="lazy" src="<?php echo $src; ?>" alt="<?php echo sanitize($p['nom']); ?>">
            <span class="badge">Ets_Kamal</span>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <?php for($i=1;$i<=100;$i++): ?>
          <a class="tile" href="<?php echo base('products.php'); ?>">
            <img loading="lazy" src="https://placehold.co/640x360?text=Ets_Kamal" alt="Galerie <?php echo $i; ?>">
            <span class="badge">Ets_Kamal</span>
          </a>
        <?php endfor; ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="grid">
    <?php foreach ($items as $p): ?>
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
  <div class="pagination">
    <?php for($i=1;$i<=$pages;$i++): ?>
      <a class="btn <?php echo $i===$page?'btn-primary':''; ?>" href="<?php echo base('products.php?page='.$i.($cat? '&category='.urlencode($cat):'').($q? '&q='.urlencode($q):'')); ?>">Page <?php echo $i; ?></a>
    <?php endfor; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>