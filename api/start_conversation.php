<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si c'est une requête AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || 
          isset($_GET['ajax']) || 
          (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

if (!isset($_SESSION['user_id'])) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    } else {
        setFlashMessage("Vous devez être connecté pour envoyer un message.", "error");
        header('Location: ../login.php');
    }
    exit;
}

$me = $_SESSION['user_id'];
$with = $_GET['with'] ?? null;
$request_id = $_GET['request_id'] ?? null;

if (!$with || $me == $with) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'ID utilisateur invalide']);
    } else {
        header('Location: ../discover.php');
    }
    exit;
}

try {
    // Vérifier si une conversation existe déjà entre ces deux utilisateurs
    $u1 = min($me, $with);
    $u2 = max($me, $with);

    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE user_one = ? AND user_two = ?");
    $stmt->execute([$u1, $u2]);
    $conv = $stmt->fetch();

    if ($conv) {
        $conv_id = $conv['id'];
        if ($request_id) {
            $update = $pdo->prepare("UPDATE conversations SET request_id = ? WHERE id = ? AND request_id IS NULL");
            $update->execute([$request_id, $conv_id]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO conversations (user_one, user_two, request_id) VALUES (?, ?, ?)");
        $stmt->execute([$u1, $u2, $request_id]);
        $conv_id = $pdo->lastInsertId();
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'conv_id' => $conv_id, 'other_user_id' => $with]);
    } else {
        header('Location: ../messages.php?id=' . $conv_id);
    }

} catch (PDOException $e) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } else {
        setFlashMessage("Erreur : " . $e->getMessage(), "error");
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }
}
