<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['success' => false, 'message' => 'Méthode non autorisée']));
}

$data = json_decode(file_get_contents('php://input'), true);
$request_id = $data['request_id'] ?? null;
$new_status = $data['status'] ?? null;

if (!$request_id || !$new_status) {
    exit(json_encode(['success' => false, 'message' => 'Données manquantes']));
}

try {
    // Vérifier les droits d'accès
    $stmt = $pdo->prepare("SELECT * FROM service_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();

    if (!$request) {
        exit(json_encode(['success' => false, 'message' => 'Demande non trouvée']));
    }

    $is_pro = ($_SESSION['user_id'] == $request['pro_id']);
    $is_client = ($_SESSION['user_id'] == $request['client_id']);

    // Logique de changement de statut autorisée
    $allowed = false;
    if ($is_pro && in_array($new_status, ['accepted', 'declined', 'discussion', 'completed'])) {
        $allowed = true;
    }
    if ($is_client && $new_status == 'cancelled' && in_array($request['status'], ['pending', 'discussion'])) {
        $allowed = true;
    }

    if (!$allowed) {
        exit(json_encode(['success' => false, 'message' => 'Action non autorisée pour votre rôle ou statut actuel']));
    }

    // Mettre à jour le statut
    $stmt = $pdo->prepare("UPDATE service_requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$new_status, $request_id]);

    // Créer une notification
    $target_user = $is_pro ? $request['client_id'] : $request['pro_id'];
    $status_labels = [
        'accepted' => 'acceptée',
        'declined' => 'refusée',
        'discussion' => 'mise en discussion',
        'completed' => 'marquée comme terminée',
        'cancelled' => 'annulée'
    ];
    $label = $status_labels[$new_status];
    
    $notif_content = "Votre demande pour '" . $request['project_title'] . "' a été " . $label . ".";
    $notif_link = "request_details.php?id=" . $request_id;
    
    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'request_update', ?, ?)");
    $stmt_notif->execute([$target_user, $notif_content, $notif_link]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()]);
}
