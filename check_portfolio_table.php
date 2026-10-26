<?php
require_once 'includes/config.php';

echo "<h1>Vérification de la table portfolio</h1>";
echo "<pre>";

try {
    $stmt = $pdo->query("DESCRIBE portfolio");
    $columns = $stmt->fetchAll();
    
    echo "<strong>Colonnes de la table portfolio :</strong>\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}

echo "</pre>";
?>