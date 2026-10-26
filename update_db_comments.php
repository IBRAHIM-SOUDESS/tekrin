<?php
require_once 'includes/config.php';

try {
    // Créer la table des commentaires si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS post_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    
    echo "Table post_comments créée avec succès !<br>";
    echo "<a href='feed.php'>Retour au fil d'actualité</a>";
    
} catch (PDOException $e) {
    die("Erreur lors de la création de la table : " . $e->getMessage());
}
