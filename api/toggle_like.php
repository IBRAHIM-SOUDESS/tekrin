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

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'] ?? null;

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'ID publication invalide']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Vérifier si déjà liké
    $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Annuler le like
        $stmt = $pdo->prepare("DELETE FROM post_likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        $is_liked = false;
        
        // Décrémenter le compteur
        $stmt = $pdo->prepare("UPDATE posts SET like_count = like_count - 1 WHERE id = ?");
        $stmt->execute([$post_id]);
    } else {
        // Ajouter le like
        $stmt = $pdo->prepare("INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
        $is_liked = true;
        
        // Incrémenter le compteur
        $stmt = $pdo->prepare("UPDATE posts SET like_count = like_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
    }
    
    $pdo->commit();
    
    // Récupérer le nouveau compteur
    $stmt = $pdo->prepare("SELECT like_count FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    echo json_encode([
        'success' => true, 
        'is_liked' => $is_liked, 
        'like_count' => $post['like_count']
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
