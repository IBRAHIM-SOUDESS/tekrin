<?php
// index.php - Interface Client pour le suivi
require_once 'config.php';

$colis = null;
$error = '';
$search_code = '';

    if (isset($_GET['tracking_code']) && !empty($_GET['tracking_code'])) {
        $search_code = trim($_GET['tracking_code']);
        
        // Requête sécurisée pour trouver le colis
        try {
            $stmt = $pdo->prepare("SELECT * FROM colis WHERE tracking_code = :code");
            $stmt->execute([':code' => $search_code]);
            $colis = $stmt->fetch();
            
            if ($colis) {
                // Récupérer l'historique
                $stmtHist = $pdo->prepare("SELECT * FROM historique_colis WHERE colis_id = ? ORDER BY date_heure DESC");
                $stmtHist->execute([$colis['id']]);
                $historique = $stmtHist->fetchAll();
            } else {
                $error = "Aucun colis trouvé avec le code : " . htmlspecialchars($search_code);
            }
        } catch (PDOException $e) {
            $error = "Une erreur est survenue lors de la recherche.";
        }
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de Colis - LIVRA-CAM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>

    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">LIVRA-CAM</h1>
            <p class="lead">Votre partenaire logistique de confiance au Cameroun.</p>
            <p>Suivez votre expédition en temps réel.</p>
        </div>
    </div>

    <div class="container" style="max-width: 800px;">
        <!-- Barre de recherche -->
        <div class="card p-4 mb-4">
            <form action="" method="GET" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="tracking_code" class="form-control form-control-lg" placeholder="Entrez votre code de suivi (ex: LIV-A1B2C3D4)" value="<?= htmlspecialchars($search_code) ?>" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-lg w-100">Rechercher</button>
                </div>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-warning text-center">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($colis): ?>
            <div class="tracking-result">
                <h3 class="text-center mb-4">Détails du Colis</h3>
                
                <div class="text-center mb-4">
                    <span class="status-badge status-<?= str_replace(' ', '-', $colis['statut']) ?> fs-5 px-4 py-2">
                        <?= htmlspecialchars($colis['statut']) ?>
                    </span>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h5 class="text-muted">Informations</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Code :</strong> <?= htmlspecialchars($colis['tracking_code']) ?></li>
                            <li class="list-group-item"><strong>Type :</strong> <?= htmlspecialchars($colis['type_colis']) ?></li>
                            <li class="list-group-item"><strong>Poids :</strong> <?= htmlspecialchars($colis['poids']) ?> kg</li>
                            <li class="list-group-item"><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($colis['created_at'])) ?></li>
                            <li class="list-group-item text-center bg-light">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($colis['tracking_code']) ?>" alt="QR Code" title="Scanner pour suivre">
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h5 class="text-muted">Trajet</h5>
                        <div class="alert alert-light border">
                            <p class="mb-1"><strong>De :</strong> <?= htmlspecialchars($colis['ville_depart']) ?></p>
                            <p class="mb-1 text-muted small">Expéditeur : <?= htmlspecialchars($colis['expediteur']) ?></p>
                            <hr class="my-2">
                            <p class="mb-1"><strong>Vers :</strong> <?= htmlspecialchars($colis['ville_arrivee']) ?></p>
                            <p class="mb-1 text-muted small">Destinataire : <?= htmlspecialchars($colis['destinataire']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Timeline Historique -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="text-muted mb-3">Historique du Parcours</h5>
                        <?php if (isset($historique) && count($historique) > 0): ?>
                            <ul class="timeline">
                                <?php foreach ($historique as $etape): ?>
                                <li class="timeline-item">
                                    <span class="timeline-date"><?= date('d/m/Y H:i', strtotime($etape['date_heure'])) ?></span>
                                    <div class="timeline-status"><?= htmlspecialchars($etape['statut']) ?></div>
                                    <div class="timeline-desc"><?= htmlspecialchars($etape['description']) ?></div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted fst-italic">Aucun historique détaillé disponible pour ce colis.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-outline-secondary">Nouvelle recherche</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-5 text-muted">
            <small>&copy; <?= date('Y') ?> LIVRA-CAM - Logistique Cameroun. <a href="admin.php">Accès Admin</a></small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
