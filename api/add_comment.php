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
$content = trim($_POST['content'] ?? '');

if (!$post_id || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Ajouter le commentaire
    $stmt = $pdo->prepare("INSERT INTO post_comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $content]);
    
    // Incrémenter le compteur de commentaires sur la publication
    $stmt = $pdo->prepare("UPDATE posts SET comment_count = comment_count + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
    
    // Récupérer les infos du commentaire avec les infos de l'utilisateur
    $comment_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT c.*, u.fullname, u.profile_picture 
        FROM post_comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'comment' => $comment,
        'comment_count' => $comment['comment_count'] + 1 // Car on a incrémenté
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
