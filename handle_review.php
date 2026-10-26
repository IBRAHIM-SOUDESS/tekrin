<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pro_id = (int)($_POST['pro_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $author_name = sanitize($_POST['author_name'] ?? 'Client Tekrin');
    $comment = sanitize($_POST['comment'] ?? '');
    $author_id = $_SESSION['user_id'] ?? null;

    // Sécurité : Un professionnel ne peut pas laisser d'avis sur son propre profil
    if ($author_id == $pro_id) {
        setFlashMessage("Vous ne pouvez pas laisser d'avis sur votre propre profil.", "danger");
        header("Location: pro.php?id=" . $pro_id);
        exit;
    }

    if ($pro_id > 0 && $rating >= 1 && $rating <= 5 && !empty($comment)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO reviews (pro_id, author_id, author_name, rating, comment) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$pro_id, $author_id, $author_name, $rating, $comment])) {
                setFlashMessage("Merci ! Votre avis a été enregistré avec succès.", "success");
            } else {
                setFlashMessage("Une erreur est survenue lors de l'enregistrement de l'avis.", "danger");
            }
        } catch (PDOException $e) {
            setFlashMessage("Erreur base de données : " . $e->getMessage(), "danger");
        }
    } else {
        setFlashMessage("Veuillez remplir tous les champs correctement.", "danger");
    }

    header("Location: pro.php?id=" . $pro_id);
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>
