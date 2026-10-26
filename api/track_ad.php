<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_id = $_POST['ad_id'] ?? null;
    $type = $_POST['type'] ?? null;

    if (!$ad_id || !in_array($type, ['view', 'click'])) {
        echo json_encode(['success' => false]);
        exit;
    }

    try {
        if ($type === 'view') {
            $stmt = $pdo->prepare("UPDATE ads SET views = views + 1 WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE ads SET clicks = clicks + 1 WHERE id = ?");
        }
        $stmt->execute([$ad_id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>