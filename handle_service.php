<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $service_id = $_POST['service_id'] ?? null;

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price_starting = $_POST['price_starting'] ?? 0;

        if (empty($name) || empty($description)) {
            setFlashMessage("Le nom et la description sont obligatoires.", 'error');
            redirect('manage_services.php');
        }

        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO services (user_id, name, description, price_starting) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $name, $description, $price_starting]);
                setFlashMessage("Service ajouté avec succès !");
            } else {
                // Vérifier si le service appartient bien à l'utilisateur
                $stmt = $pdo->prepare("SELECT id FROM services WHERE id = ? AND user_id = ?");
                $stmt->execute([$service_id, $user_id]);
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, price_starting = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $price_starting, $service_id]);
                    setFlashMessage("Service mis à jour avec succès !");
                } else {
                    setFlashMessage("Action non autorisée.", 'error');
                }
            }
        } catch (PDOException $e) {
            setFlashMessage("Erreur lors de l'enregistrement : " . $e->getMessage(), 'error');
        }
    } elseif ($action === 'delete' && $service_id) {
        try {
            // Vérifier si le service appartient bien à l'utilisateur
            $stmt = $pdo->prepare("SELECT id FROM services WHERE id = ? AND user_id = ?");
            $stmt->execute([$service_id, $user_id]);
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
                $stmt->execute([$service_id]);
                setFlashMessage("Service supprimé avec succès !");
            } else {
                setFlashMessage("Action non autorisée.", 'error');
            }
        } catch (PDOException $e) {
            setFlashMessage("Erreur lors de la suppression : " . $e->getMessage(), 'error');
        }
    }

    redirect('manage_services.php');
} else {
    redirect('manage_services.php');
}
