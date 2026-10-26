<?php
require_once __DIR__ . '/functions.php';
ensureSession();
$itemsCount = array_sum(cartGet());
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ets_Kamal</title>
  <link rel="stylesheet" href="<?php echo base('assets/css/styles.css'); ?>">
  <base href="<?php echo rtrim(base(), '/'); ?>/">
</head>
<body>
<header class="header">
  <div class="container header-bar">
    <a class="logo" href="<?php echo base(''); ?>">Ets_Kamal</a>
    <nav class="nav">
      <a href="<?php echo base(''); ?>">Accueil</a>
      <a href="<?php echo base('products.php'); ?>">Catalogue</a>
      <a href="<?php echo base('cart.php'); ?>">Panier (<?php echo (int)$itemsCount; ?>)</a>
      <a href="<?php echo base('admin/login.php'); ?>">Admin</a>
    </nav>
  </div>
</header>
<main class="container">
<?php $flash = flashGet(); if ($flash): ?>
  <div class="alert <?php echo $flash['type']==='error'?'alert-error':'alert-info'; ?>">
    <?php echo sanitize($flash['msg']); ?>
  </div>
<?php endif; ?>