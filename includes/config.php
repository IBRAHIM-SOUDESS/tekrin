<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tekrin');

// Tentative de connexion à la base de données MySQL
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Définir le mode d'erreur PDO sur Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERREUR : Impossible de se connecter. " . $e->getMessage());
}

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * AUTO-MIGRATION TEMPORAIRE
 * Ce bloc peut être supprimé après exécution réussie
 */
try {
    // 1. Vérifier/Ajouter cover_image
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'cover_image'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD cover_image VARCHAR(255) DEFAULT NULL");
    }

    // 2. Créer table services
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price_starting DECIMAL(10, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Créer table reviews
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pro_id INT NOT NULL,
        author_id INT DEFAULT NULL,
        author_name VARCHAR(100) DEFAULT 'Client Tekrin',
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pro_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. Ajouter de nouveaux champs pour le profil premium
    $new_columns = [
        'onboarding_completed' => "TINYINT(1) DEFAULT 0",
        'current_onboarding_step' => "INT DEFAULT 1",
        'experience_years' => "INT DEFAULT NULL",
        'work_philosophy' => "TEXT DEFAULT NULL",
        'availability_status' => "VARCHAR(50) DEFAULT 'Disponible'",
        'response_speed' => "VARCHAR(50) DEFAULT 'Très réactif'",
        'service_area' => "VARCHAR(255) DEFAULT NULL",
        'languages' => "VARCHAR(255) DEFAULT 'Français'",
        'certifications' => "TEXT DEFAULT NULL", // Stocké en JSON
        'badges' => "TEXT DEFAULT NULL", // Stocké en JSON
        'category' => "VARCHAR(100) DEFAULT NULL",
        'latitude' => "DECIMAL(10, 8) DEFAULT NULL",
        'longitude' => "DECIMAL(11, 8) DEFAULT NULL"
    ];

    foreach ($new_columns as $col => $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE users ADD $col $definition");
        }
    }

} catch (PDOException $e) {
    // Si vous voyez cette erreur, c'est que la migration a échoué.
    // die("Erreur migration : " . $e->getMessage()); 
}
?>