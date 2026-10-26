<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

$post_id = $_POST['post_id'] ?? null;

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'ID de publication invalide']);
    exit;
}

try {
    // Incrémenter le compteur de partages
    $stmt = $pdo->prepare("UPDATE posts SET share_count = share_count + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
    
    // Récupérer le nouveau compteur
    $stmt = $pdo->prepare("SELECT share_count FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'share_count' => $post['share_count']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
