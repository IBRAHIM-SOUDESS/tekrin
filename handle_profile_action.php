<?php
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Session expirée.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'upload_avatar') {
        if (!isset($_FILES['photo'])) throw new Exception('Aucun fichier reçu.');
        
        $file = $_FILES['photo'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Erreur d\'upload.');

        $uploadDir = 'assets/uploads/profiles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Optionnel : supprimer l'ancienne photo
            $stmt_old = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
            $stmt_old->execute([$user_id]);
            $old = $stmt_old->fetchColumn();
            if ($old && file_exists($old)) @unlink($old);

            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$uploadPath, $user_id]);
            $_SESSION['user_avatar'] = $uploadPath;
            echo json_encode(['success' => true, 'path' => $uploadPath]);
        } else {
            throw new Exception('Impossible d\'enregistrer le fichier.');
        }
    } 
    elseif ($action === 'upload_cover') {
        if (!isset($_FILES['photo'])) throw new Exception('Aucun fichier reçu.');
        
        $file = $_FILES['photo'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Erreur d\'upload.');

        $uploadDir = 'assets/uploads/covers/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'cover_' . $user_id . '_' . time() . '.' . $ext;
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $stmt_old = $pdo->prepare("SELECT cover_image FROM users WHERE id = ?");
            $stmt_old->execute([$user_id]);
            $old = $stmt_old->fetchColumn();
            if ($old && file_exists($old)) @unlink($old);

            $stmt = $pdo->prepare("UPDATE users SET cover_image = ? WHERE id = ?");
            $stmt->execute([$uploadPath, $user_id]);
            echo json_encode(['success' => true, 'path' => $uploadPath]);
        } else {
            throw new Exception('Impossible d\'enregistrer le fichier.');
        }
    }
    else {
        throw new Exception('Action non reconnue.');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
