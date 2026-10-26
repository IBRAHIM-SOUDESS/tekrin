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
$message = $_POST['message'] ?? '';

// Validation des données
if (!$conv_id) {
    echo json_encode(['success' => false, 'error' => 'Conversation ID manquant']);
    exit;
}

if (empty(trim($message)) && empty($_FILES['attachment']['name'])) {
    echo json_encode(['success' => false, 'error' => 'Message ou pièce jointe requis']);
    exit;
}

$message = sanitize($message);
$attachment_name = null;
$attachment_type = null;

try {
    $pdo->beginTransaction();

    // Vérifier si l'utilisateur fait partie de la conversation
    $stmt = $pdo->prepare("SELECT user_one, user_two FROM conversations WHERE id = ?");
    $stmt->execute([$conv_id]);
    $conv = $stmt->fetch();

    if (!$conv || ($conv['user_one'] != $me && $conv['user_two'] != $me)) {
        echo json_encode(['success' => false, 'error' => 'Conversation non trouvée']);
        exit;
    }

    $other_id = ($conv['user_one'] == $me) ? $conv['user_two'] : $conv['user_one'];

    // Gérer la pièce jointe
    if (!empty($_FILES['attachment']['name'])) {
        $upload_dir = '../assets/uploads/messages/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_name = $_FILES['attachment']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $attachment_name = 'msg_' . $conv_id . '_' . uniqid() . '.' . $ext;
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $attachment_name)) {
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $attachment_type = 'image';
            } elseif ($ext == 'pdf') {
                $attachment_type = 'pdf';
            } else {
                $attachment_type = 'doc';
            }
        }
    }

    // Insérer le message
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message, attachment_path, attachment_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$conv_id, $me, $message, $attachment_name, $attachment_type]);
    $message_id = $pdo->lastInsertId();

    // Mettre à jour le timestamp de la conversation
    $pdo->prepare("UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$conv_id]);

    // Créer une notification pour le destinataire
    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'new_message', ?, ?)");
    $notif_content = "Nouveau message de " . $_SESSION['user_fullname'];
    $notif_link = "messages.php?id=" . $conv_id;
    $stmt_notif->execute([$other_id, $notif_content, $notif_link]);

    $pdo->commit();

    // Récupérer le message envoyé pour le retourner
    $stmt_msg = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt_msg->execute([$message_id]);
    $sent_message = $stmt_msg->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'message' => $sent_message]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
