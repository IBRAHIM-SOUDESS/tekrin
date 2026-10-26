<?php
// Désactiver l'affichage des erreurs pour ne pas casser le JSON
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

$follower_id = $_SESSION['user_id'];
$following_id = $_POST['user_id'] ?? null;

if (!$following_id || $follower_id == $following_id) {
    echo json_encode(['success' => false, 'error' => 'ID utilisateur invalide']);
    exit;
}

try {
    // Vérifiez si l'utilisateur suit déjà
    $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$follower_id, $following_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Annuler le suivi
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);
        echo json_encode(['success' => true, 'is_following' => false]);
    } else {
        // Commencer à suivre
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $following_id]);
        echo json_encode(['success' => true, 'is_following' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
