<?php
require_once 'includes/config.php';

try {
    // Vérifier et ajouter like_count
    echo "Vérification du champ like_count...<br>";
    $result = $pdo->query("SHOW COLUMNS FROM posts LIKE 'like_count'");
    if (!$result->fetch()) {
        echo "Ajout du champ like_count...<br>";
        $pdo->exec("ALTER TABLE posts ADD COLUMN like_count INT DEFAULT 0 AFTER content");
    }

    // Vérifier et ajouter comment_count
    echo "Vérification du champ comment_count...<br>";
    $result = $pdo->query("SHOW COLUMNS FROM posts LIKE 'comment_count'");
    if (!$result->fetch()) {
        echo "Ajout du champ comment_count...<br>";
        $pdo->exec("ALTER TABLE posts ADD COLUMN comment_count INT DEFAULT 0 AFTER like_count");
    }

    // Vérifier et ajouter share_count
    echo "Vérification du champ share_count...<br>";
    $result = $pdo->query("SHOW COLUMNS FROM posts LIKE 'share_count'");
    if (!$result->fetch()) {
        echo "Ajout du champ share_count...<br>";
        $pdo->exec("ALTER TABLE posts ADD COLUMN share_count INT DEFAULT 0 AFTER comment_count");
    }

    echo "<br>✅ Mise à jour terminée !<br><br>";
    echo "<a href='feed.php'>Retourner au fil d'actualité</a>";

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
