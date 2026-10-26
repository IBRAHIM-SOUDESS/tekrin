<?php require_once __DIR__ . '/../includes/functions.php'; ?>
<?php
$config = require __DIR__ . '/../config/config.php';
$error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $u = trim($_POST['user'] ?? '');
  $p = trim($_POST['pass'] ?? '');
  if ($u === $config['admin_user'] && $p === $config['admin_pass']) {
    ensureSession();
    $_SESSION['admin'] = true;
    header('Location: ' . base('admin/index.php'));
    exit;
  } else { $error = true; }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section>
  <h2>Admin</h2>
  <?php if ($error): ?><div>Identifiants incorrects</div><?php endif; ?>
  <form method="post" class="form" style="max-width:400px">
    <?php echo csrfField(); ?>
    <input name="user" placeholder="Email admin" required>
    <input name="pass" type="password" placeholder="Mot de passe" required>
    <button class="btn btn-primary" type="submit">Se connecter</button>
  </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>