<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['post_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de publication invalide']);
    exit;
}

$post_id = $_GET['post_id'];

try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.fullname, u.profile_picture 
        FROM post_comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'comments' => $comments]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
