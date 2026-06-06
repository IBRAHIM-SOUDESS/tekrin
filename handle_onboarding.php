<?php
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];

    // Vérifier que l'utilisateur existe toujours en base de données
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Votre session est invalide ou votre compte n\'existe plus. Veuillez vous reconnecter.']);
        exit;
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'save_step':
            $step = (int)($_POST['step'] ?? 1);
            $data = $_POST['data'] ?? [];
            
            // Update current step
            $stmt = $pdo->prepare("UPDATE users SET current_onboarding_step = ? WHERE id = ?");
            $stmt->execute([$step, $user_id]);
            
            // Save specific data based on step
            if (!empty($data)) {
                $updateFields = [];
                $params = [];
                
                foreach ($data as $key => $value) {
                    // Sanitize and validate keys to prevent SQL injection
                    $allowedKeys = ['profession', 'country', 'region', 'city', 'phone_number', 'whatsapp_number', 'bio', 'services'];
                    if (in_array($key, $allowedKeys)) {
                        $updateFields[] = "$key = ?";
                        $params[] = $value;
                    }
                }
                
                if (!empty($updateFields)) {
                    $params[] = $user_id;
                    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                }
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'upload_photo':
            if (!isset($_FILES['photo'])) {
                echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu.']);
                exit;
            }
            
            $file = $_FILES['photo'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Erreur PHP lors de l\'upload : ' . $file['error']]);
                exit;
            }

            $uploadDir = 'assets/uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$uploadPath, $user_id]);
                $_SESSION['user_avatar'] = $uploadPath;
                echo json_encode(['success' => true, 'path' => $uploadPath]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Impossible d\'enregistrer l\'image sur le serveur.']);
            }
            break;

        case 'upload_portfolio':
            if (!isset($_FILES['images'])) {
                echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu.']);
                exit;
            }
            
            $files = $_FILES['images'];
            $uploadDir = 'assets/uploads/portfolio/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $uploaded = [];
            $errors = [];

            foreach ($files['tmp_name'] as $key => $tmpName) {
                $error = $files['error'][$key];
                
                if ($error !== UPLOAD_ERR_OK) {
                    $errors[] = "Erreur PHP ({$error}) pour le fichier " . $files['name'][$key];
                    continue;
                }

                $ext = pathinfo($files['name'][$key], PATHINFO_EXTENSION);
                $fileName = 'portfolio_' . $user_id . '_' . time() . '_' . $key . '.' . $ext;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $uploadPath)) {
                    $stmt = $pdo->prepare("INSERT INTO portfolio (user_id, image_path) VALUES (?, ?)");
                    $stmt->execute([$user_id, $uploadPath]);
                    $uploaded[] = ['id' => $pdo->lastInsertId(), 'path' => $uploadPath];
                } else {
                    $errors[] = "Impossible de déplacer le fichier " . $files['name'][$key];
                }
            }
            
            if (empty($uploaded)) {
                $msg = !empty($errors) ? implode(", ", $errors) : "Aucune image n'a pu être téléchargée.";
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            
            echo json_encode(['success' => true, 'images' => $uploaded, 'warnings' => $errors]);
            break;

        case 'delete_portfolio':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT image_path FROM portfolio WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $img = $stmt->fetch();
            
            if ($img) {
                if (file_exists($img['image_path'])) unlink($img['image_path']);
                $stmt = $pdo->prepare("DELETE FROM portfolio WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
            break;

        case 'get_portfolio':
            $stmt = $pdo->prepare("SELECT id, image_path as path FROM portfolio WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'images' => $images]);
            break;

        case 'complete_onboarding':
            $stmt = $pdo->prepare("UPDATE users SET onboarding_completed = 1 WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['onboarding_completed'] = 1;
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    $message = $e->getMessage();
    if (strpos($message, '1452') !== false) {
        $message = "Erreur de synchronisation de session (votre utilisateur n'existe pas en base). Veuillez vous déconnecter et vous reconnecter.";
    }
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $message]);
}

