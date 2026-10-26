<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    exit('Accès non autorisé');
}

$request_id = $_POST['request_id'];
$pro_id = $_POST['pro_id'];
$client_id = $_SESSION['user_id'];
$rating = (int)$_POST['rating'];
$comment = sanitize($_POST['comment']);

try {
    $pdo->beginTransaction();

    // Insérer l'avis
    $stmt = $pdo->prepare("INSERT INTO reviews (request_id, client_id, pro_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$request_id, $client_id, $pro_id, $rating, $comment]);

    // Mettre à jour la note moyenne du professionnel
    // (Cette partie dépend de votre structure actuelle, on peut recalculer à la volée ou stocker)
    // Ici on crée une notification pour le pro
    $notif_content = "Vous avez reçu un nouvel avis (" . $rating . "/5⭐) de la part d'un client.";
    $notif_link = "pro.php?id=" . $pro_id . "#reviews";
    $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, content, link) VALUES (?, 'new_review', ?, ?)");
    $stmt_notif->execute([$pro_id, $notif_content, $notif_link]);

    $pdo->commit();

    setFlashMessage("Merci ! Votre avis a été publié avec succès.", "success");
    header('Location: ../request_details.php?id=' . $request_id);

} catch (Exception $e) {
    $pdo->rollBack();
    setFlashMessage("Une erreur est survenue : " . $e->getMessage(), "error");
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
