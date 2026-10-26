<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    setFlashMessage("Vous devez être connecté pour envoyer une demande.", "error");
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$client_id = $_SESSION['user_id'];
$pro_id = $_POST['pro_id'];
$category = sanitize($_POST['service_category']);
$title = sanitize($_POST['project_title']);
$description = sanitize($_POST['description']);
$budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
$date = !empty($_POST['desired_date']) ? $_POST['desired_date'] : null;
$location = sanitize($_POST['location']);
$contact = sanitize($_POST['contact_preference']);

try {
    $pdo->beginTransaction();

    // Insérer la demande
    $stmt = $pdo->prepare("INSERT INTO service_requests (client_id, pro_id, service_category, project_title, description, budget, desired_date, location, contact_preference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$client_id, $pro_id, $category, $title, $description, $budget, $date, $location, $contact]);
    $request_id = $pdo->lastInsertId();

    // Gérer les fichiers
    if (!empty($_FILES['attachments']['name'][0])) {
        $upload_dir = '../assets/uploads/requests/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['attachments']['name'][$key];
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_name = 'req_' . $request_id . '_' . uniqid() . '.' . $ext;
            $target = $upload_dir . $new_name;

            if (move_uploaded_file($tmp_name, $target)) {
                $file_type = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : 'document';
                $stmt_file = $pdo->prepare("INSERT INTO request_attachments (request_id, file_path, file_type) VALUES (?, ?, ?)");
                $stmt_file->execute([$request_id, $new_name, $file_type]);
            }
        }
    }

    // Créer une notification pour le professionnel
    $notif_content = "Vous avez reçu une nouvelle demande de service pour : " . $title;
    $notif_link = "request_details.php?id=" . $request_id;
    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'new_request', ?, ?)");
    $stmt_notif->execute([$pro_id, $notif_content, $notif_link]);

    $pdo->commit();

    setFlashMessage("Votre demande a été envoyée avec succès. Le professionnel sera notifié immédiatement.", "success");
    header('Location: ../my_requests.php');

} catch (Exception $e) {
    $pdo->rollBack();
    setFlashMessage("Une erreur est survenue lors de l'envoi de votre demande : " . $e->getMessage(), "error");
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
