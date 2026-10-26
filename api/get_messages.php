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

$me = $_SESSION['user_id'];
$conv_id = $_GET['conversation_id'] ?? null;
$last_id = $_GET['last_id'] ?? 0;

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

    // Récupérer les nouveaux messages (après last_id si fourni)
    if ($last_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE conversation_id = ? AND id > ? ORDER BY created_at ASC");
        $stmt->execute([$conv_id, $last_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at ASC");
        $stmt->execute([$conv_id]);
    }

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
