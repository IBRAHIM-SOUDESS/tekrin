<?php
require_once 'includes/config.php';

try {
    // 1. Mise à jour de la table users (ajout des colonnes si elles n'existent pas)
    $columns = [
        'profession' => "VARCHAR(100) DEFAULT NULL",
        'company_name' => "VARCHAR(100) DEFAULT NULL",
        'region' => "VARCHAR(100) DEFAULT NULL",
        'city' => "VARCHAR(100) DEFAULT NULL",
        'phone_number' => "VARCHAR(20) DEFAULT NULL",
        'whatsapp_number' => "VARCHAR(20) DEFAULT NULL",
        'bio' => "TEXT DEFAULT NULL",
        'services' => "TEXT DEFAULT NULL",
        'onboarding_completed' => "TINYINT(1) DEFAULT 0",
        'status' => "ENUM('active', 'pending', 'suspended') DEFAULT 'active'"
    ];

    foreach ($columns as $column => $definition) {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$column'");
        if ($check->rowCount() == 0) {
            $pdo->exec("ALTER TABLE users ADD $column $definition");
            echo "Colonne '$column' ajoutée à la table users.<br>";
        }
    }

    // 2. Mise à jour du rôle artisan dans l'ENUM
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('client', 'technicien', 'artisan', 'entreprise') NOT NULL");
    echo "Rôle 'artisan' ajouté à l'ENUM.<br>";

    // Désactiver les contraintes de clés étrangères temporairement pour éviter l'erreur 1824
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 3. Création de la table portfolio
    $sql_portfolio = "CREATE TABLE IF NOT EXISTS portfolio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        CONSTRAINT fk_user_portfolio FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql_portfolio);
    
    // Réactiver les contraintes
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "Table 'portfolio' créée avec succès (ou déjà existante).<br>";

    echo "<br><strong>Base de données mise à jour avec succès !</strong><br>";
    echo "<a href='dashboard.php'>Retourner au tableau de bord</a>";

} catch (PDOException $e) {
    die("Erreur lors de la mise à jour : " . $e->getMessage());
}
?>