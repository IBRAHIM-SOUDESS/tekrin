<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    die("Veuillez vous connecter d'abord!");
}

// Test toggle_follow avec une requête POST
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/tekrin/api/toggle_follow.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['user_id' => 1]); // Remplacez 1 par un ID d'utilisateur existant
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id()); // Envoie les cookies de session
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>Réponse de l'API (raw):</h2>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h2>Réponse de l'API (JSON décodé):</h2>";
$data = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} else {
    echo "<p style='color:red;'>Erreur JSON : " . json_last_error_msg() . "</p>";
}

if ($error) {
    echo "<h2>Erreur cURL:</h2>";
    echo "<p style='color:red;'>" . $error . "</p>";
}
?>