<?php
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Bloquer l'action si l'onboarding n'est pas fini
if (!isOnboardingCompleted()) {
    redirect('wizard.php?step=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $content = sanitize($_POST['content'] ?? '');
    $type = sanitize($_POST['type'] ?? 'text');
    $images = [];

    if (empty($content) && empty($_FILES['images']['name'][0])) {
        setFlashMessage("La publication ne peut pas être vide.", "danger");
        redirect('feed.php');
    }

    // Gestion des images multiples
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $files = $_FILES['images'];
        $upload_dir = 'assets/uploads/posts/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        foreach ($files['name'] as $key => $name) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
                if (in_array($files['type'][$key], $allowed_types)) {
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $new_file_name = "post_" . $user_id . "_" . time() . "_" . $key . "." . $extension;
                    
                    if (move_uploaded_file($files['tmp_name'][$key], $upload_dir . $new_file_name)) {
                        $images[] = $new_file_name;
                        // Si on a des images, on s'assure que le type est au moins 'image' si c'était 'text'
                        if ($type === 'text') $type = 'image';
                    }
                }
            }
        }
    }

    $images_json = !empty($images) ? json_encode($images) : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, images, type) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $content, $images_json, $type])) {
            setFlashMessage("Publication partagée avec succès !", "success");
        } else {
            setFlashMessage("Une erreur est survenue lors de la publication.", "danger");
        }
    } catch (PDOException $e) {
        setFlashMessage("Erreur base de données : " . $e->getMessage(), "danger");
    }

    redirect('feed.php');
} else {
    redirect('feed.php');
}
?>
