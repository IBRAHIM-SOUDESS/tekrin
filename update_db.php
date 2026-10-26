<?php
/**
 * SCRIPT DE MISE À JOUR DE LA BASE DE DONNÉES TEKRIN
 * Ce script ajoute les tables nécessaires pour le système de demandes de service,
 * les notifications et les avis clients.
 */

require_once 'includes/config.php';

echo "<h1>Mise à jour de la base de données Tekrin...</h1>";
echo "<pre>";

try {
    // 1. Table des demandes de service
    echo "Création de la table 'service_requests'... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        pro_id INT NOT NULL,
        service_category VARCHAR(50) NOT NULL,
        project_title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        budget DECIMAL(15, 2) DEFAULT NULL,
        desired_date DATE DEFAULT NULL,
        location VARCHAR(255) DEFAULT NULL,
        contact_preference ENUM('whatsapp', 'phone', 'email') DEFAULT 'whatsapp',
        status ENUM('pending', 'discussion', 'accepted', 'declined', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (pro_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    // 2. Table des pièces jointes
    echo "Création de la table 'request_attachments'... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS request_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_type VARCHAR(50) DEFAULT 'image',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    // 3. Mise à jour ou création de la table 'reviews'
    echo "Mise à jour de la table 'reviews'... ";
    // On vérifie si request_id existe, sinon on recrée la table proprement
    $stmt = $pdo->query("SHOW COLUMNS FROM reviews LIKE 'request_id'");
    if (!$stmt->fetch()) {
        // Sauvegarde temporaire si nécessaire ou simple recréation car nouveau système
        $pdo->exec("DROP TABLE IF EXISTS reviews;");
        $pdo->exec("CREATE TABLE reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id INT NOT NULL UNIQUE,
            client_id INT NOT NULL,
            pro_id INT NOT NULL,
            rating INT CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (pro_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    echo "OK\n";

    // 4. Table des notifications
    echo "Création de la table 'notifications'... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        content TEXT NOT NULL,
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    // 5. Ajout de colonnes manquantes dans 'users' pour le profil premium (sécurité)
    echo "Vérification des colonnes 'users'... ";
    $columns = [
        'profession' => "VARCHAR(100) DEFAULT NULL",
        'bio' => "TEXT DEFAULT NULL",
        'city' => "VARCHAR(100) DEFAULT NULL",
        'avg_rating' => "DECIMAL(3,2) DEFAULT 5.00",
        'experience_years' => "INT DEFAULT 0"
    ];
    foreach ($columns as $col => $def) {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD $col $def");
        }
    }
    echo "OK\n";

    // 6. Tables de messagerie professionnelle
    echo "Création des tables de messagerie... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_one INT NOT NULL,
        user_two INT NOT NULL,
        request_id INT DEFAULT NULL,
        last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY pair (user_one, user_two),
        FOREIGN KEY (user_one) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (user_two) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_id INT NOT NULL,
        message TEXT,
        attachment_path VARCHAR(255) DEFAULT NULL,
        attachment_type ENUM('image', 'pdf', 'doc') DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "OK\n";

    // 7. Table des suivis
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

    echo "\n<b>Mise à jour terminée avec succès !</b>";
    echo "\nVous pouvez maintenant supprimer ce fichier pour plus de sécurité.";

} catch (PDOException $e) {
    echo "\n<b style='color:red'>ERREUR : " . $e->getMessage() . "</b>";
}

echo "</pre>";
?>
