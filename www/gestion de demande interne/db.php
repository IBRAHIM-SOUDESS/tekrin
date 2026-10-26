<?php
// Activation complète du rapport d'erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Fichier de configuration de la base de données
 * Utilisation de l'extension MySQLi en mode procédural
 */

$host = "localhost";      // Serveur local
$user = "root";           // Utilisateur par défaut de XAMPP/WAMP
$pass = "";               // Par défaut, pas de mot de passe sur XAMPP
$dbname = "gestion_tickets"; // Nom de la base de données créée via database.sql

// Connexion à MySQL
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Vérification de la connexion
if (!$conn) {
    die("La connexion a échoué : " . mysqli_connect_error());
}

// Définition de l'encodage pour éviter les problèmes d'accents
mysqli_set_charset($conn, "utf8mb4");
?>
