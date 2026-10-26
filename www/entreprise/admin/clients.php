<?php require_once __DIR__ . '/../includes/functions.php'; requireAdmin(); ?>
<?php
$rows = db()->query('SELECT id, nom, email, telephone FROM clients ORDER BY id DESC')->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section>
  <div class="admin-top">
    <h2>Clients</h2>
    <a class="btn" href="<?php echo base('admin/index.php'); ?>">Retour</a>
  </div>
  <table class="table">
    <thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Téléphone</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo sanitize($r['nom']); ?></td>
          <td><?php echo sanitize($r['email']); ?></td>
          <td><?php echo sanitize($r['telephone']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>