<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

$userId = $_GET['id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'ID utilisateur manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, fullname, profile_picture, profession, city, bio, avg_rating FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']);
        exit;
    }
    
    // Add avatar URL
    $user['avatar'] = get_user_avatar($user);
    
    echo json_encode(['success' => true, 'user' => $user]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
