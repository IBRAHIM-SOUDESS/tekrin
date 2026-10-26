<?php
/**
 * Script pour exporter la base de données en format CSV (Excel)
 */
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    exit("Accès refusé");
}

require_once 'db.php';

// Nom du fichier avec la date du jour
$filename = "export_tickets_" . date('Y-m-d') . ".csv";

// Entêtes pour forcer le téléchargement du fichier
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Création du pointeur de fichier pour la sortie
$output = fopen('php://output', 'w');

// Ajout du BOM UTF-8 pour qu'Excel reconnaisse les accents
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Titres des colonnes
fputcsv($output, array('ID', 'Date création', 'Date clôture', 'Employé', 'Service', 'Objet', 'Catégorie', 'Urgence', 'Message', 'Statut', 'Lien Pièce Jointe'));

// Récupération de toutes les données
$query = "SELECT * FROM demandes ORDER BY date_creation DESC";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $pj_link = !empty($row['piece_jointe']) ? 'uploads/' . $row['piece_jointe'] : 'Aucune';
    fputcsv($output, array(
        $row['id'],
        $row['date_creation'],
        $row['date_cloture'],
        $row['nom_employe'],
        $row['service'],
        $row['objet'],
        $row['categorie'],
        $row['urgence'],
        $row['message'],
        $row['statut'],
        $pj_link
    ));
}

fclose($output);
exit();
?>
