<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../includes/config.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');

try {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT ads.*, users.fullname, users.profile_picture FROM ads JOIN users ON ads.user_id = users.id WHERE ads.is_active = 1 AND ads.start_date <= ? AND ads.end_date >= ? ORDER BY RAND() LIMIT 5");
    $stmt->execute([$now, $now]);
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the ads
    foreach ($ads as &$ad) {
        $ad['avatar'] = get_user_avatar(['profile_picture' => $ad['profile_picture']]);
    }
    
    echo json_encode(['success' => true, 'ads' => $ads]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>