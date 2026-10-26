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

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'ID publication invalide']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Vérifier si déjà enregistré
    $stmt = $pdo->prepare("SELECT id FROM post_saves WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Annuler l'enregistrement
        $stmt = $pdo->prepare("DELETE FROM post_saves WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        $is_saved = false;
        
        // Décrémenter le compteur
        $stmt = $pdo->prepare("UPDATE posts SET save_count = save_count - 1 WHERE id = ?");
        $stmt->execute([$post_id]);
    } else {
        // Ajouter l'enregistrement
        $stmt = $pdo->prepare("INSERT INTO post_saves (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $post_id]);
        $is_saved = true;
        
        // Incrémenter le compteur
        $stmt = $pdo->prepare("UPDATE posts SET save_count = save_count + 1 WHERE id = ?");
        $stmt->execute([$post_id]);
    }
    
    $pdo->commit();
    
    // Récupérer le nouveau compteur
    $stmt = $pdo->prepare("SELECT save_count FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    echo json_encode([
        'success' => true, 
        'is_saved' => $is_saved, 
        'save_count' => $post['save_count']
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
