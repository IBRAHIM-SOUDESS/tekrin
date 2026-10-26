<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

$me = $_SESSION['user_id'];

try {
    $stmt_unread = $pdo->prepare("
        SELECT COUNT(*) 
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        WHERE (c.user_one = ? OR c.user_two = ?) 
        AND m.sender_id != ? 
        AND m.is_read = 0
    ");
    $stmt_unread->execute([$me, $me, $me]);
    $total_unread = $stmt_unread->fetchColumn();

    echo json_encode(['success' => true, 'total_unread' => $total_unread]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
