<?php
/**
 * SCRIPT POUR AJOUTER LA TABLE DES SUIVIS UNIQUEMENT
 */

require_once 'includes/config.php';

echo "<h1>Ajout de la table 'follows'...</h1>";
echo "<pre>";

try {
    echo "Création de la table 'follows'... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS follows (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY follow_pair (follower_id, following_id),
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    echo "\n<b>Table 'follows' ajoutée avec succès !</b>";
} catch (PDOException $e) {
    echo "\n<b style='color:red'>ERREUR : " . $e->getMessage() . "</b>";
}

echo "</pre>";
?>
