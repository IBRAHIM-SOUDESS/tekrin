<?php
/**
 * Script de traitement de la soumission du formulaire
 */
session_start();

// 1. Inclusion de la connexion à la base de données
require_once 'db.php';

// 2. Vérification que les données ont bien été envoyées via la méthode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Un administrateur n'est pas censé soumettre un ticket
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        header("Location: admin.php");
        exit();
    }
    
    // Vérification de la sécurité CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ticket.php?status=error&reason=csrf");
        exit();
    }

    // 3. Récupération et assainissement (Sanitization) des données
    // htmlspecialchars empêche les attaques XSS en convertissant les caractères spéciaux en entités HTML
    $nom_employe = htmlspecialchars(trim($_POST['nom_employe']), ENT_QUOTES, 'UTF-8');
    $service = htmlspecialchars($_POST['service'], ENT_QUOTES, 'UTF-8');
    $objet = htmlspecialchars(trim($_POST['objet']), ENT_QUOTES, 'UTF-8');
    $categorie = htmlspecialchars($_POST['categorie'], ENT_QUOTES, 'UTF-8');
    $urgence_brute = htmlspecialchars($_POST['urgence_brute'], ENT_QUOTES, 'UTF-8'); // Valeur brute (bloquant, gênant, informatif)
    // Mapping de l'urgence brute vers l'ENUM 'urgence' attendu par la BDD (basse, moyenne, haute)
    $map_urgence = [
        'informatif' => 'basse',
        'gênant' => 'moyenne',
        'genant' => 'moyenne', // fallback ASCII si accent perdu
        'bloquant' => 'haute'
    ];
    $urgence = isset($map_urgence[$urgence_brute]) ? $map_urgence[$urgence_brute] : 'moyenne';
    $message = htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8');
    $num_serie = isset($_POST['num_serie']) ? htmlspecialchars(trim($_POST['num_serie']), ENT_QUOTES, 'UTF-8') : null;
    $type_equipement = isset($_POST['type_equipement']) ? htmlspecialchars($_POST['type_equipement'], ENT_QUOTES, 'UTF-8') : null;
    $nom_logiciel = isset($_POST['nom_logiciel']) ? htmlspecialchars(trim($_POST['nom_logiciel']), ENT_QUOTES, 'UTF-8') : null;
    $impact = htmlspecialchars($_POST['impact'], ENT_QUOTES, 'UTF-8');
    $priorite_calculee = htmlspecialchars($_POST['priorite_calculee'], ENT_QUOTES, 'UTF-8');

    // --- GESTION DE LA PIÈCE JOINTE ---
    $piece_jointe_nom = null;
    if (isset($_FILES['piece_jointe']) && $_FILES['piece_jointe']['error'] == 0) {
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
        $filename = $_FILES['piece_jointe']['name'];
        $filetype = $_FILES['piece_jointe']['type'];
        $filesize = $_FILES['piece_jointe']['size'];

        // Vérification de l'extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $allowed)) {
            header("Location: ticket.php?status=error&reason=file_type");
            exit();
        }

        // Vérification de la taille (max 5 Mo)
        $max_size = 5 * 1024 * 1024;
        if ($filesize > $max_size) {
            header("Location: ticket.php?status=error&reason=file_size");
            exit();
        }

        // Création du dossier uploads s'il n'existe pas
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        // Nom unique pour éviter les doublons
        $piece_jointe_nom = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        move_uploaded_file($_FILES['piece_jointe']['tmp_name'], 'uploads/' . $piece_jointe_nom);
    }

    // 4. Utilisation d'une requête préparée (Sécurité contre les Injections SQL)
    $sql = "INSERT INTO demandes (nom_employe, service, objet, categorie, urgence, message, piece_jointe, num_serie, type_equipement, nom_logiciel, impact, priorite_calculee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Préparation de la requête
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Liaison des paramètres : "ssssssssssss" pour 12 chaînes
        mysqli_stmt_bind_param($stmt, "ssssssssssss", $nom_employe, $service, $objet, $categorie, $urgence, $message, $piece_jointe_nom, $num_serie, $type_equipement, $nom_logiciel, $impact, $priorite_calculee);

        // Exécution de la requête
        if (mysqli_stmt_execute($stmt)) {
            $ticket_id = mysqli_insert_id($conn);
            
            // Log d'audit (Action de création)
            $log_sql = "INSERT INTO logs_audit (ticket_id, action, details) VALUES (?, 'Création', 'Demande soumise par l\'utilisateur')";
            $log_stmt = mysqli_prepare($conn, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "i", $ticket_id);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);

            // Succès : redirection vers ticket.php avec l'ID du ticket
            header("Location: ticket.php?status=success&id=" . $ticket_id);
            exit();
        } else {
            // Erreur lors de l'exécution
            header("Location: ticket.php?status=error");
            exit();
        }

        // Fermeture de la requête préparée
        mysqli_stmt_close($stmt);
    } else {
        // Erreur lors de la préparation de la requête
        header("Location: ticket.php?status=error");
        exit();
    }
} else {
    // Si on essaie d'accéder à ce fichier directement sans formulaire, on redirige
    header("Location: ticket.php");
    exit();
}

// Fermeture de la connexion à la base de données
mysqli_close($conn);
?>
