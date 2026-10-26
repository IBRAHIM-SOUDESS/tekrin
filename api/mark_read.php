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

$me = $_SESSION['user_id'];
$conv_id = $_POST['conversation_id'] ?? null;

if (!$conv_id) {
    echo json_encode(['success' => false, 'error' => 'Conversation ID manquant']);
    exit;
}

try {
    // Vérifier si l'utilisateur fait partie de la conversation
    $stmt = $pdo->prepare("SELECT user_one, user_two FROM conversations WHERE id = ?");
    $stmt->execute([$conv_id]);
    $conv = $stmt->fetch();

    if (!$conv || ($conv['user_one'] != $me && $conv['user_two'] != $me)) {
        echo json_encode(['success' => false, 'error' => 'Conversation non trouvée']);
        exit;
    }

    // Marquer les messages comme lus
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?")->execute([$conv_id, $me]);

    // Récupérer le nouveau compteur de non lus
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
