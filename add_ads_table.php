<?php
require_once 'includes/config.php';

echo "<h1>Ajout du système de publicité...</h1>";
echo "<pre>";

try {
    // 1. Créer la table ads
    echo "Création de la table 'ads'... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        image_url VARCHAR(255),
        link VARCHAR(255),
        ad_type ENUM('sponsored', 'promotion', 'event') DEFAULT 'sponsored',
        target_audience VARCHAR(255),
        start_date DATETIME NOT NULL,
        end_date DATETIME NOT NULL,
        clicks INT DEFAULT 0,
        views INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    echo "\n<b>Table 'ads' et système de publicité ajoutés avec succès !</b>";
} catch (PDOException $e) {
    echo "\n<b style='color:red'>ERREUR : " . $e->getMessage() . "</b>";
}

echo "</pre>";
?>