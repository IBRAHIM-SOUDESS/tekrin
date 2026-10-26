<?php
session_start();
function db() {
  static $pdo;
  if ($pdo) return $pdo;
  $host = getenv('DB_HOST') ?: 'localhost';
  $name = getenv('DB_NAME') ?: 'pme_stock';
  $user = getenv('DB_USER') ?: 'root';
  $pass = getenv('DB_PASS') ?: '';
  $charset = 'utf8mb4';
  $dsn = "mysql:host=$host;dbname=$name;charset=$charset";
  $opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
  ];
  $pdo = new PDO($dsn, $user, $pass, $opt);
  return $pdo;
}
function ensure_schema() {
  try {
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS fournisseurs (id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(150) NOT NULL, contact VARCHAR(150))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS clients (id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(150) NOT NULL, contact VARCHAR(150))");
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'produits' AND column_name = 'image_path'");
    $check->execute(); if ((int)$check->fetchColumn() === 0) { $pdo->exec("ALTER TABLE produits ADD COLUMN image_path VARCHAR(255) NULL"); }
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'produits' AND column_name = 'prix'");
    $check->execute(); if ((int)$check->fetchColumn() === 0) { $pdo->exec("ALTER TABLE produits ADD COLUMN prix DECIMAL(10,2) NOT NULL DEFAULT 0.00"); }
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'produits' AND column_name = 'description'");
    $check->execute(); if ((int)$check->fetchColumn() === 0) { $pdo->exec("ALTER TABLE produits ADD COLUMN description TEXT"); }
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mouvements' AND column_name = 'fournisseur_id'");
    $check->execute(); if ((int)$check->fetchColumn() === 0) { $pdo->exec("ALTER TABLE mouvements ADD COLUMN fournisseur_id INT NULL"); }
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mouvements' AND column_name = 'client_id'");
    $check->execute(); if ((int)$check->fetchColumn() === 0) { $pdo->exec("ALTER TABLE mouvements ADD COLUMN client_id INT NULL"); }
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mouvements' AND column_name = 'prix_unitaire'");
    $check->execute(); if ((int)$check->fetchColumn() === 0) { $pdo->exec("ALTER TABLE mouvements ADD COLUMN prix_unitaire DECIMAL(10,2) NULL"); }
    try { $pdo->exec("ALTER TABLE mouvements MODIFY COLUMN type ENUM('entree','sortie','achat','vente','retour') NOT NULL"); } catch (Exception $e) {}
  } catch (Exception $e) {}
}
ensure_schema();
function require_login() {
  if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
  }
}
function current_user() {
  if (!isset($_SESSION['user_id'])) return null;
  return ['id'=>$_SESSION['user_id'], 'nom'=>$_SESSION['user_name'], 'role'=>$_SESSION['user_role']];
}
function is_role($role) {
  return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}
function has_any_role($roles) {
  return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles, true);
}
function logout() {
  session_destroy();
  header('Location: index.php');
  exit;
}
