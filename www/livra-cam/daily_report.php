<?php
// daily_report.php - Rapport journalier des expéditions
session_start();
require_once 'config.php';

// Sécurité : Accès réservé aux admins
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Récupération de la date (aujourd'hui par défaut)
$date = date('Y-m-d');
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $date = $_GET['date'];
}

// Récupération des colis de la date sélectionnée
$stmt = $pdo->prepare("SELECT * FROM colis WHERE DATE(created_at) = :date ORDER BY created_at DESC");
$stmt->execute([':date' => $date]);
$colis_du_jour = $stmt->fetchAll();

// Totaux
$total = count($colis_du_jour);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport Journalier - <?= date('d/m/Y', strtotime($date)) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .report-container { max-width: 210mm; margin: 20px auto; background: white; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        @media print {
            body { background: none; }
            .report-container { box-shadow: none; margin: 0; padding: 0; width: 100%; max-width: 100%; }
            .no-print { display: none !important; }
        }
        .header-logo { font-weight: bold; font-size: 24px; color: #0d6efd; }
    </style>
</head>
<body>

<div class="report-container">
    <!-- Contrôles (cachés à l'impression) -->
    <div class="d-flex justify-content-between mb-4 no-print align-items-center">
        <a href="admin.php" class="btn btn-outline-secondary">&larr; Retour Admin</a>
        <div class="d-flex gap-2">
            <form action="" method="GET" class="d-flex gap-2">
                <input type="date" name="date" class="form-control" value="<?= $date ?>">
                <button type="submit" class="btn btn-primary">Voir</button>
            </form>
            <button onclick="window.print()" class="btn btn-success"><i class="bi bi-printer"></i> Imprimer</button>
        </div>
    </div>

    <!-- En-tête du Rapport -->
    <div class="row mb-4 border-bottom pb-3">
        <div class="col-6">
            <div class="header-logo">LIVRA-CAM</div>
            <div class="text-muted">Logistique & Transport Cameroun</div>
        </div>
        <div class="col-6 text-end">
            <h3>RAPPORT JOURNALIER</h3>
            <p class="mb-0">Date : <strong><?= date('d/m/Y', strtotime($date)) ?></strong></p>
            <p>Généré le : <?= date('d/m/Y H:i') ?></p>
        </div>
    </div>

    <!-- Résumé -->
    <div class="alert alert-light border mb-4">
        <strong>Résumé de l'activité :</strong> <?= $total ?> colis enregistrés ce jour.
    </div>

    <!-- Tableau des Colis -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Heure</th>
                <th>Code Suivi</th>
                <th>Expéditeur</th>
                <th>Destinataire</th>
                <th>Route</th>
                <th>Poids</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($colis_du_jour) > 0): ?>
                <?php foreach ($colis_du_jour as $colis): ?>
                <tr>
                    <td><?= date('H:i', strtotime($colis['created_at'])) ?></td>
                    <td><strong><?= htmlspecialchars($colis['tracking_code']) ?></strong></td>
                    <td><?= htmlspecialchars($colis['expediteur']) ?></td>
                    <td><?= htmlspecialchars($colis['destinataire']) ?></td>
                    <td><?= htmlspecialchars($colis['ville_depart']) ?> &rarr; <?= htmlspecialchars($colis['ville_arrivee']) ?></td>
                    <td><?= htmlspecialchars($colis['poids']) ?> kg</td>
                    <td><?= htmlspecialchars($colis['statut']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-4">Aucun colis enregistré pour cette date.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pied de page -->
    <div class="row mt-5">
        <div class="col-6">
            <p><strong>Visa Agent :</strong></p>
            <br><br>
            <div class="border-bottom w-50"></div>
        </div>
        <div class="col-6 text-end">
            <p><strong>Visa Superviseur :</strong></p>
            <br><br>
            <div class="border-bottom w-50 ms-auto"></div>
        </div>
    </div>
</div>

</body>
</html>
