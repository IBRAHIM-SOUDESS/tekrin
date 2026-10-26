<?php
// config.php - Connexion sécurisée à la base de données

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'livra_cam_db');
define('DB_USER', 'root'); // À modifier selon la config locale (souvent 'root' sur WAMP/XAMPP)
define('DB_PASS', '');     // À modifier selon la config locale

try {
    // Connexion PDO avec encodage UTF-8
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    
    // Configuration des attributs PDO pour la sécurité et le débogage
    // ERRMODE_EXCEPTION : Lance une exception en cas d'erreur SQL (indispensable pour try/catch)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // DEFAULT_FETCH_MODE : Retourne les résultats sous forme de tableau associatif par défaut
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Désactiver l'émulation des requêtes préparées (sécurité accrue contre les injections SQL)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // En cas d'erreur, on arrête tout et on affiche un message générique pour ne pas fuiter d'infos sensibles
    // Dans un vrai projet, on loggerait l'erreur dans un fichier
    die("Erreur de connexion : Impossible de se connecter à la base de données.");
}
?>
