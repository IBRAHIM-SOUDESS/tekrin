<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

try {
    $query = "SELECT id, fullname, profession, city, profile_picture, role 
              FROM users 
              WHERE role IN ('technicien', 'artisan', 'entreprise')
              AND status = 'active'
              AND (fullname LIKE :q OR profession LIKE :q OR city LIKE :q OR services LIKE :q)
              LIMIT 10";
    
    $stmt = $pdo->prepare($query);
    $searchTerm = '%' . $q . '%';
    $stmt->bindParam(':q', $searchTerm);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as &$pro) {
        $pro['avatar'] = get_user_avatar($pro);
    }
    
    echo json_encode(['success' => true, 'results' => $results]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
