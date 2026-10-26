<?php
require_once 'includes/config.php';

try {
    // 1. Supprimer l'ancienne table si elle existe (attention: les données seront perdues)
    // Pour un projet en dev c'est acceptable, sinon on ferait des ALTER
    $pdo->exec("DROP TABLE IF EXISTS posts");

    // 2. Création de la nouvelle structure de la table posts
    $sql = "CREATE TABLE posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        images TEXT DEFAULT NULL, -- Stocké en JSON array
        type ENUM('text', 'image', 'service', 'project', 'event') DEFAULT 'text',
        like_count INT DEFAULT 0,
        comment_count INT DEFAULT 0,
        share_count INT DEFAULT 0,
        save_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);

    // 3. Création des tables pour l'engagement (préparation pour le futur)
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_likes (
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, post_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_saves (
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, post_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    echo "Base de données mise à jour avec succès !<br>";
    echo "<a href='feed.php'>Retourner au fil d'actualité</a>";

} catch (PDOException $e) {
    die("Erreur lors de la mise à jour : " . $e->getMessage());
}
?>
