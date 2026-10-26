<?php
require_once 'includes/config.php';
echo "<h2>Schema Users:</h2>";
$stmt = $pdo->query("DESCRIBE users");
echo "<pre>"; print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); echo "</pre>";

echo "<h2>Schema Posts:</h2>";
$stmt = $pdo->query("DESCRIBE posts");
echo "<pre>"; print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); echo "</pre>";

echo "<h2>Schema Portfolio:</h2>";
$stmt = $pdo->query("DESCRIBE portfolio");
echo "<pre>"; print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); echo "</pre>";

echo "<h2>Dernier utilisateur inscrit:</h2>";
$stmt = $pdo->query("SELECT id, fullname, email, role, onboarding_completed, current_onboarding_step FROM users ORDER BY id DESC LIMIT 1");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>