<?php
require_once 'includes/config.php';
$stmt = $pdo->query("DESCRIBE users");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>