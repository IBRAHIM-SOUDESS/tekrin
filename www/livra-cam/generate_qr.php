<?php
// generate_qr.php - Script utilitaire pour la génération de QR Code
// Ce script peut être appelé pour générer et sauvegarder un QR code spécifique

require_once 'config.php';

// Vérification de la bibliothèque
$local_qr_lib = 'lib/phpqrcode/qrlib.php';

if (!file_exists($local_qr_lib)) {
    die("Erreur : La bibliothèque phpqrcode n'est pas installée dans lib/phpqrcode/");
}

include $local_qr_lib;

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $filename = 'qrcodes/' . $code . '.png';
    
    // Créer le dossier s'il n'existe pas
    if (!file_exists('qrcodes')) {
        mkdir('qrcodes');
    }
    
    // Générer le fichier PNG
    // Paramètres : contenu, fichier de sortie, niveau de correction (L,M,Q,H), taille, marge
    QRcode::png($code, $filename, QR_ECLEVEL_M, 4, 2);
    
    echo "QR Code généré avec succès : <a href='$filename'>$filename</a>";
} else {
    echo "Veuillez fournir un code via ?code=...";
}
?>
