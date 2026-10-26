<?php require_once __DIR__ . '/../includes/functions.php'; requireAdmin(); ?>
<?php
$rows = db()->query('SELECT id, channel, target, status, created_at, SUBSTRING(content,1,150) AS content_preview, SUBSTRING(error,1,150) AS error_preview FROM notifications ORDER BY id DESC LIMIT 200')->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section>
  <div class="admin-top">
    <h2>Notifications</h2>
    <a class="btn" href="<?php echo base('admin/index.php'); ?>">Retour</a>
  </div>
  <table class="table">
    <thead><tr><th>ID</th><th>Canal</th><th>Cible</th><th>Statut</th><th>Date</th><th>Contenu</th><th>Erreur</th></tr></thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7">Aucune notification enregistrée. Active les canaux dans Réglages, passe une commande ou mets à jour une commande en cochant “Notifier le client”.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo sanitize($r['channel']); ?></td>
          <td><?php echo sanitize($r['target']); ?></td>
          <td><?php echo sanitize($r['status']); ?></td>
          <td><?php echo sanitize($r['created_at']); ?></td>
          <td><?php echo sanitize($r['content_preview']); ?></td>
          <td><?php echo sanitize($r['error_preview']); ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>