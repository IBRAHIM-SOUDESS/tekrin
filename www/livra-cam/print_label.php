<?php
// print_label.php - Page d'impression d'étiquette colis
session_start();
require_once 'config.php';

// Sécurité : Accès réservé aux admins
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Accès refusé.");
}

if (!isset($_GET['id'])) {
    die("ID du colis manquant.");
}

$id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM colis WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $colis = $stmt->fetch();

    if (!$colis) {
        die("Colis introuvable.");
    }
} catch (PDOException $e) {
    die("Erreur de base de données.");
}

// Logique QR Code (copiée de admin.php pour cohérence)
$local_qr_lib = 'lib/phpqrcode/qrlib.php';
$use_local_qr = file_exists($local_qr_lib);
if ($use_local_qr) include $local_qr_lib;

function getQRCodeUrl($code, $use_local) {
    // Note: Pour l'impression, l'API externe est plus fiable si le dossier local n'est pas inscriptible
    return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($code);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Étiquette - <?= htmlspecialchars($colis['tracking_code']) ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #eee;
            margin: 0;
            padding: 20px;
        }
        .label-container {
            background: white;
            width: 10cm; /* Taille standard étiquette */
            height: 15cm;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
            border: 2px solid black;
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid black;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }
        .qr-section {
            text-align: center;
            margin-bottom: 20px;
        }
        .qr-section img {
            width: 120px;
            height: 120px;
        }
        .tracking-code {
            font-size: 18px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            margin-top: 5px;
        }
        .details-section {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .address-box {
            border: 1px solid #333;
            padding: 10px;
        }
        .address-label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        .address-content {
            font-size: 14px;
            font-weight: bold;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            border-top: 2px solid black;
            padding-top: 10px;
        }
        .meta-item {
            text-align: center;
        }
        .footer {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
        }
        
        @media print {
            body { background: none; padding: 0; }
            .label-container { border: none; margin: 0; width: 100%; height: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">🖨️ Imprimer cette étiquette</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Fermer</button>
    </div>

    <div class="label-container">
        <div class="header">
            <h1>LIVRA-CAM</h1>
            <small>Service Logistique Cameroun</small>
        </div>

        <div class="qr-section">
            <img src="<?= getQRCodeUrl($colis['tracking_code'], $use_local_qr) ?>" alt="QR">
            <div class="tracking-code"><?= htmlspecialchars($colis['tracking_code']) ?></div>
        </div>

        <div class="details-section">
            <div class="address-box">
                <div class="address-label">EXPÉDITEUR (FROM):</div>
                <div class="address-content">
                    <?= htmlspecialchars($colis['expediteur']) ?><br>
                    <?= htmlspecialchars($colis['ville_depart']) ?>
                </div>
            </div>

            <div class="address-box">
                <div class="address-label">DESTINATAIRE (TO):</div>
                <div class="address-content" style="font-size: 18px;">
                    <?= htmlspecialchars($colis['destinataire']) ?><br>
                    <?= htmlspecialchars($colis['ville_arrivee']) ?>
                </div>
            </div>
        </div>

        <div class="meta-info">
            <div class="meta-item">
                <div class="address-label">POIDS</div>
                <strong><?= htmlspecialchars($colis['poids']) ?> KG</strong>
            </div>
            <div class="meta-item">
                <div class="address-label">TYPE</div>
                <strong><?= strtoupper(htmlspecialchars($colis['type_colis'])) ?></strong>
            </div>
            <div class="meta-item">
                <div class="address-label">DATE</div>
                <strong><?= date('d/m/Y', strtotime($colis['created_at'])) ?></strong>
            </div>
        </div>

        <div class="footer">
            Merci de faire confiance à LIVRA-CAM - www.livracam.cm
        </div>
    </div>

    <script>
        // Lancer l'impression automatiquement au chargement
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
