<?php
// Fichier de diagnostic pour comprendre pourquoi ça ne marche pas
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic de l'application</h1>";

// 1. Test de la version PHP
echo "<h2>1. Version PHP</h2>";
echo "Version actuelle : " . PHP_VERSION;
if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
    echo " <span style='color:green;'>(OK)</span>";
} else {
    echo " <span style='color:orange;'>(Attention : PHP 8 est recommandé)</span>";
}

// 2. Test de la connexion à la base de données
echo "<h2>2. Connexion à la Base de Données</h2>";
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "gestion_tickets";

$conn = @mysqli_connect($host, $user, $pass);
if (!$conn) {
    echo "<p style='color:red;'>ERREUR : Impossible de se connecter au serveur MySQL (localhost). Est-ce que XAMPP/WAMP est lancé ?</p>";
    echo "Erreur : " . mysqli_connect_error();
} else {
    echo "<p style='color:green;'>SUCCÈS : Connexion au serveur MySQL établie.</p>";
    
    // Test de la base de données
    $db_selected = @mysqli_select_db($conn, $dbname);
    if (!$db_selected) {
        echo "<p style='color:red;'>ERREUR : La base de données '$dbname' n'existe pas. As-tu importé le fichier database.sql ?</p>";
    } else {
        echo "<p style='color:green;'>SUCCÈS : La base de données '$dbname' est bien accessible.</p>";
        
        // Test de la table
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'demandes'");
        if (mysqli_num_rows($result) == 0) {
            echo "<p style='color:red;'>ERREUR : La table 'demandes' est introuvable dans la base de données.</p>";
        } else {
            echo "<p style='color:green;'>SUCCÈS : La table 'demandes' est prête.</p>";
        }
    }
}

// 3. Test des fichiers
echo "<h2>3. Vérification des fichiers</h2>";
$files = ['index.php', 'db.php', 'style.css', 'admin.php', 'login.php', 'submit.php'];
echo "<ul>";
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<li>$file : <span style='color:green;'>Présent</span></li>";
    } else {
        echo "<li>$file : <span style='color:red;'>MANQUANT</span></li>";
    }
}
echo "</ul>";

echo "<hr><p>Si tout est au vert mais que rien ne change, essayez de vider le cache de votre navigateur (Ctrl + F5).</p>";
?>
