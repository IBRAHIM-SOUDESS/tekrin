<?php
require_once 'includes/config.php';

echo "<h1>Mise à jour de la table portfolio</h1>";
echo "<pre>";

try {
    // Vérifier si les colonnes existent déjà
    $stmt = $pdo->query("DESCRIBE portfolio");
    $existing_cols = [];
    while ($row = $stmt->fetch()) {
        $existing_cols[] = $row['Field'];
    }
    
    // Ajouter title si pas existant
    if (!in_array('title', $existing_cols)) {
        echo "Ajout du champ 'title'... ";
        $pdo->exec("ALTER TABLE portfolio ADD COLUMN title VARCHAR(255) NULL AFTER image_path");
        echo "OK\n";
    } else {
        echo "Champ 'title' déjà existant.\n";
    }
    
    // Ajouter description si pas existant
    if (!in_array('description', $existing_cols)) {
        echo "Ajout du champ 'description'... ";
        $pdo->exec("ALTER TABLE portfolio ADD COLUMN description TEXT NULL AFTER title");
        echo "OK\n";
    } else {
        echo "Champ 'description' déjà existant.\n";
    }
    
    echo "\n✅ Table portfolio mise à jour avec succès !";
} catch (PDOException $e) {
    echo "\n❌ Erreur : " . $e->getMessage();
}

echo "</pre>";
?>