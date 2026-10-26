<?php
require_once 'includes/config.php';

echo "<h2>Réparation des tables Tekrin</h2>";

try {
    // 1. Table Services
    echo "Création de la table 'services'... ";
    $sql1 = "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price_starting DECIMAL(10, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql1);
    echo "<span style='color:green'>OK</span><br>";

    // 2. Table Reviews
    echo "Création de la table 'reviews'... ";
    $sql2 = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pro_id INT NOT NULL,
        author_id INT DEFAULT NULL,
        author_name VARCHAR(100) DEFAULT 'Client Tekrin',
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(pro_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql2);
    echo "<span style='color:green'>OK</span><br>";

    // 3. Colonne cover_image
    echo "Vérification de la colonne 'cover_image'... ";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'cover_image'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD cover_image VARCHAR(255) DEFAULT NULL");
        echo "<span style='color:green'>Ajoutée</span><br>";
    } else {
        echo "<span style='color:blue'>Déjà présente</span><br>";
    }

    echo "<br><strong style='color:green'>Toutes les tables sont prêtes !</strong><br>";
    echo "<a href='profile.php'>Retourner à mon profil</a>";

} catch (PDOException $e) {
    echo "<br><strong style='color:red'>ERREUR :</strong> " . $e->getMessage();
}
?>
