<?php require_once __DIR__ . '/../includes/functions.php'; requireAdmin(); ?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $nom = trim($_POST['nom'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $prix = (float)($_POST['prix'] ?? 0);
  $image = trim($_POST['image'] ?? '');
  $categorie = trim($_POST['categorie'] ?? '');
  $existing = $id ? getProductById($id) : null;
  $uploaded = null;
  if (isset($_FILES['image_file']) && !empty($_FILES['image_file']['name'])) {
    if ((int)$_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
      $tmp = $_FILES['image_file']['tmp_name'];
      $size = (int)$_FILES['image_file']['size'];
      if ($size <= 4194304) {
        $mime = function_exists('finfo_open') ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp) : (function_exists('mime_content_type') ? mime_content_type($tmp) : '');
        $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        if (isset($map[$mime])) {
          $ext = $map[$mime];
          $dir = __DIR__ . '/../uploads';
          if (!is_dir($dir)) { mkdir($dir, 0775, true); }
          $name = uniqid('img_', true) . '.' . $ext;
          $dest = $dir . '/' . $name;
          if (move_uploaded_file($tmp, $dest)) { $uploaded = 'uploads/' . $name; }
        }
      }
    }
  }
  if ($uploaded) {
    if ($existing && isset($existing['image']) && strpos($existing['image'], 'uploads/') === 0) {
      $old = __DIR__ . '/../' . $existing['image'];
      if (is_file($old)) { @unlink($old); }
    }
    $image = $uploaded;
  }
  if ($id) {
    $st = db()->prepare('UPDATE produits SET nom=:n, description=:d, prix=:p, image=:i, categorie=:c WHERE id=:id');
    $st->execute([':n'=>$nom, ':d'=>$description, ':p'=>$prix, ':i'=>$image, ':c'=>$categorie, ':id'=>$id]);
  } else {
    $st = db()->prepare('INSERT INTO produits(nom,description,prix,image,categorie) VALUES(:n,:d,:p,:i,:c)');
    $st->execute([':n'=>$nom, ':d'=>$description, ':p'=>$prix, ':i'=>$image, ':c'=>$categorie]);
  }
  header('Location: ' . base('admin/products.php'));
  exit;
}
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $prod = getProductById($id);
  if ($prod && isset($prod['image']) && strpos($prod['image'], 'uploads/') === 0) {
    $path = __DIR__ . '/../' . $prod['image'];
    if (is_file($path)) { @unlink($path); }
  }
  $st = db()->prepare('DELETE FROM produits WHERE id=:id');
  $st->execute([':id'=>$id]);
  header('Location: ' . base('admin/products.php'));
  exit;
}
$edit = null;
if (isset($_GET['edit'])) { $edit = getProductById((int)$_GET['edit']); }
$items = db()->query('SELECT * FROM produits ORDER BY id DESC')->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<section>
  <div class="admin-top">
    <h2>Produits</h2>
    <a class="btn" href="<?php echo base('admin/index.php'); ?>">Retour</a>
  </div>
  <form method="post" class="form" enctype="multipart/form-data">
    <?php echo csrfField(); ?>
    <input type="hidden" name="id" value="<?php echo $edit? (int)$edit['id']: 0; ?>">
    <input name="nom" placeholder="Nom" value="<?php echo $edit? sanitize($edit['nom']) : ''; ?>" required>
    <textarea name="description" placeholder="Description" rows="3"><?php echo $edit? sanitize($edit['description']) : ''; ?></textarea>
    <input name="prix" type="number" step="0.01" placeholder="Prix" value="<?php echo $edit? sanitize($edit['prix']) : ''; ?>" required>
    <input name="image" placeholder="URL image (optionnel)" value="<?php echo $edit? sanitize($edit['image']) : ''; ?>">
    <input type="file" name="image_file" accept="image/*">
    <div>
      <?php if ($edit && !empty($edit['image'])): ?>
        <img id="image_preview" src="<?php echo sanitize($edit['image']); ?>" alt="" style="max-width:200px;display:block">
      <?php else: ?>
        <img id="image_preview" src="" alt="" style="max-width:200px;display:none">
      <?php endif; ?>
    </div>
    <input name="categorie" placeholder="Catégorie" value="<?php echo $edit? sanitize($edit['categorie']) : ''; ?>" required>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?php echo $edit? 'Mettre à jour' : 'Ajouter'; ?></button>
      <?php if ($edit): ?><a class="btn" href="<?php echo base('admin/products.php'); ?>">Annuler</a><?php endif; ?>
    </div>
  </form>
  <script>
  const fileInput=document.querySelector('input[name="image_file"]');
  const urlInput=document.querySelector('input[name="image"]');
  const preview=document.getElementById('image_preview');
  function show(src){ if(!preview) return; if(src){ preview.src=src; preview.style.display='block'; } else { preview.style.display='none'; } }
  if(fileInput){ fileInput.addEventListener('change',()=>{ const f=fileInput.files&&fileInput.files[0]; if(f){ const r=new FileReader(); r.onload=e=>show(e.target.result); r.readAsDataURL(f); }}); }
  if(urlInput){ urlInput.addEventListener('input',()=>show(urlInput.value)); }
  </script>
  <table class="table" style="margin-top:16px">
    <thead><tr><th>ID</th><th>Nom</th><th>Prix</th><th>Catégorie</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?php echo (int)$it['id']; ?></td>
          <td><?php echo sanitize($it['nom']); ?></td>
          <td><?php echo formatPrice($it['prix']); ?></td>
          <td><?php echo sanitize($it['categorie']); ?></td>
          <td>
            <a class="btn" href="<?php echo base('admin/products.php?edit='.(int)$it['id']); ?>">Modifier</a>
            <a class="btn" href="<?php echo base('admin/products.php?delete='.(int)$it['id']); ?>" onclick="return confirm('Supprimer ?')">Supprimer</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>