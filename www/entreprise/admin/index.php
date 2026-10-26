<?php require_once __DIR__ . '/../includes/functions.php'; requireAdmin(); ?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php $status = dbStatus(); ?>
<section>
  <div class="admin-top">
    <h2>Tableau de bord</h2>
    <a class="btn" href="<?php echo base('admin/logout.php'); ?>">Déconnexion</a>
  </div>
  <div class="card" style="margin-bottom:16px">
    <div class="card-body">
      <div>Base de données: <?php echo $status['ok']? '<span style="color:green">OK</span>' : '<span style="color:red">ERREUR</span>'; ?></div>
      <div>Produits: <?php echo (int)$status['counts']['produits']; ?> • Clients: <?php echo (int)$status['counts']['clients']; ?> • Commandes: <?php echo (int)$status['counts']['commandes']; ?></div>
      <?php if (!$status['ok']): ?><div><?php echo sanitize($status['error']); ?></div><?php endif; ?>
    </div>
  </div>
  <div class="grid">
    <a class="card" href="<?php echo base('admin/products.php'); ?>"><div class="card-body">Produits</div></a>
    <a class="card" href="<?php echo base('admin/orders.php'); ?>"><div class="card-body">Commandes</div></a>
    <a class="card" href="<?php echo base('admin/clients.php'); ?>"><div class="card-body">Clients</div></a>
    <a class="card" href="<?php echo base('admin/settings.php'); ?>"><div class="card-body">Réglages</div></a>
    <a class="card" href="<?php echo base('admin/notifications.php'); ?>"><div class="card-body">Notifications</div></a>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>