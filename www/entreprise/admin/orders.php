<?php require_once __DIR__ . '/../includes/functions.php'; requireAdmin(); ?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
  verifyCsrf();
  $id = (int)($_POST['id'] ?? 0);
  $statut = trim($_POST['statut'] ?? '');
  $notify = isset($_POST['notify_client']);
  if ($id && $statut !== '') {
    $st = db()->prepare('UPDATE commandes SET statut = :s WHERE id = :id');
    $st->execute([':s'=>$statut, ':id'=>$id]);
    if ($notify) { notifyClientOrderStatus($id, $statut); }
    if ($notify) { flashSet('Statut mis à jour. Notification client déclenchée.', 'info'); }
    else { flashSet('Statut mis à jour.', 'info'); }
  }
  header('Location: ' . base('admin/orders.php'));
  exit;
}
$rows = db()->query('SELECT commandes.id, commandes.date, commandes.statut, clients.nom AS client_nom, clients.email AS client_email, clients.telephone AS client_tel FROM commandes JOIN clients ON clients.id = commandes.id_client ORDER BY commandes.id DESC')->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section>
  <div class="admin-top">
    <h2>Commandes</h2>
    <a class="btn" href="<?php echo base('admin/index.php'); ?>">Retour</a>
  </div>
  <table class="table">
    <thead><tr><th>ID</th><th>Client</th><th>Contact</th><th>Date</th><th>Statut</th><th>Action</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id']; ?></td>
          <td><?php echo sanitize($r['client_nom']); ?></td>
          <td><?php echo sanitize($r['client_tel']); ?><?php echo $r['client_email']? ' • '.sanitize($r['client_email']) : ''; ?></td>
          <td><?php echo sanitize($r['date']); ?></td>
          <td><?php echo sanitize($r['statut']); ?></td>
          <td>
            <form method="post" style="display:flex;gap:8px;align-items:center">
              <?php echo csrfField(); ?>
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <select name="statut">
                <?php $opts=['en_attente','validee','livree','annulee']; foreach ($opts as $o): ?>
                  <option value="<?php echo $o; ?>" <?php echo $r['statut']===$o? 'selected':''; ?>><?php echo $o; ?></option>
                <?php endforeach; ?>
              </select>
              <label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="notify_client"><span>Notifier le client</span></label>
              <button class="btn" type="submit" name="update_status" value="1">Mettre à jour</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>