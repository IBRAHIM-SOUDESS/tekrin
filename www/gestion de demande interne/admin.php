<?php
/**
 * Page d'administration améliorée avec affichage par cartes pour la lisibilité
 */
session_start();

// 1. Vérification de l'authentification
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

// Migration automatique : Ajout de la colonne technicien_assigne si elle n'existe pas
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM demandes LIKE 'technicien_assigne'");
if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($conn, "ALTER TABLE demandes ADD COLUMN technicien_assigne VARCHAR(100) DEFAULT NULL AFTER priorite_calculee");
}

// Migration automatique : Mise à jour de l'ENUM statut
mysqli_query($conn, "ALTER TABLE demandes MODIFY COLUMN statut ENUM('en cours', 'terminé', 'annulé') DEFAULT 'en cours'");

// 2. Configuration : Liste des techniciens (peut être mis en BDD plus tard)
$techniciens = ['Koffi ADO', 'Amina OUATTARA', 'Oumar TRAORE', 'Fatou DIALLO'];

// 3. Gestion des actions (Assigner / Terminer / Supprimer / Déconnexion)

// Déconnexion
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Assigner un technicien
if (isset($_POST['action']) && $_POST['action'] == 'assigner' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $technicien = htmlspecialchars(trim($_POST['technicien']), ENT_QUOTES, 'UTF-8');
    
    $stmt = mysqli_prepare($conn, "UPDATE demandes SET technicien_assigne = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $technicien, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Log d'audit
        $details = "Ticket assigné à : " . $technicien;
        $log_sql = "INSERT INTO logs_audit (ticket_id, action, details) VALUES (?, 'Assignation', ?)";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        mysqli_stmt_bind_param($log_stmt, "is", $id, $details);
        mysqli_stmt_execute($log_stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_stmt_close($log_stmt);
        header("Location: admin.php?filter=" . ($_POST['filter'] ?? 'en cours') . "&msg=assigned");
        exit();
    }
}

// Terminer une demande avec note de résolution
if (isset($_POST['action']) && $_POST['action'] == 'terminer' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $note = htmlspecialchars(trim($_POST['note_resolution']), ENT_QUOTES, 'UTF-8');
    
    $stmt = mysqli_prepare($conn, "UPDATE demandes SET statut = 'terminé', date_cloture = NOW(), note_resolution = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $note, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Récupérer le technicien assigné pour le log
        $check_stmt = mysqli_prepare($conn, "SELECT technicien_assigne FROM demandes WHERE id = ?");
        mysqli_stmt_bind_param($check_stmt, "i", $id);
        mysqli_stmt_execute($check_stmt);
        $res = mysqli_stmt_get_result($check_stmt);
        $row_tech = mysqli_fetch_assoc($res);
        $tech_name = $row_tech['technicien_assigne'] ?? 'Non assigné';
        mysqli_stmt_close($check_stmt);

        // Log d'audit amélioré
        $details = "Ticket clôturé par le technicien ($tech_name). Note : " . $note;
        $log_sql = "INSERT INTO logs_audit (ticket_id, action, details) VALUES (?, 'Clôture', ?)";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        mysqli_stmt_bind_param($log_stmt, "is", $id, $details);
        mysqli_stmt_execute($log_stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_stmt_close($log_stmt);
        header("Location: admin.php?filter=" . ($_POST['filter'] ?? 'en cours') . "&msg=updated");
        exit();
    }
}

// Annuler une demande
if (isset($_GET['action']) && $_GET['action'] == 'annuler' && isset($_GET['id'])) {
    if ($_SESSION['user_role'] !== 'admin') {
        header("Location: admin.php?msg=error_auth");
        exit();
    }
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "UPDATE demandes SET statut = 'annulé', date_cloture = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        // Log d'audit
        $log_sql = "INSERT INTO logs_audit (ticket_id, action, details) VALUES (?, 'Annulation', 'Ticket annulé par l\'administration')";
        $log_stmt = mysqli_prepare($conn, $log_sql);
        mysqli_stmt_bind_param($log_stmt, "i", $id);
        mysqli_stmt_execute($log_stmt);
        
        mysqli_stmt_close($stmt);
        mysqli_stmt_close($log_stmt);
        header("Location: admin.php?filter=" . ($_GET['filter'] ?? 'en cours') . "&msg=cancelled");
        exit();
    }
}

// Supprimer une demande
if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id'])) {
    if ($_SESSION['user_role'] !== 'admin') {
        header("Location: admin.php?msg=error_auth");
        exit();
    }
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM demandes WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: admin.php?filter=" . ($_GET['filter'] ?? 'en cours') . "&msg=deleted");
        exit();
    }
}

// 3. Récupération des statistiques
// Normalisation rétrocompatible des anciennes valeurs d'urgence
@mysqli_query($conn, "UPDATE demandes SET urgence='basse' WHERE urgence='informatif'");
@mysqli_query($conn, "UPDATE demandes SET urgence='moyenne' WHERE urgence IN ('gênant','genant')");
@mysqli_query($conn, "UPDATE demandes SET urgence='haute' WHERE urgence='bloquant'");

// Préparation du préfixe SQL pour les stats selon le rôle
$stats_where = ($_SESSION['user_role'] === 'technicien') ? " AND technicien_assigne = '" . mysqli_real_escape_string($conn, $_SESSION['user_name']) . "'" : "";

$stat_en_cours = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM demandes WHERE statut = 'en cours' $stats_where"))['total'];
$stat_termines = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM demandes WHERE statut = 'terminé' $stats_where"))['total'];
// Répartition des urgences (tickets en cours)
$stat_basse = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM demandes WHERE statut = 'en cours' AND (urgence='basse' OR urgence='informatif') $stats_where"))['total'];
$stat_moyenne = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM demandes WHERE statut = 'en cours' AND (urgence='moyenne' OR urgence IN ('gênant','genant')) $stats_where"))['total'];
$stat_haute = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM demandes WHERE statut = 'en cours' AND (urgence='haute' OR urgence='bloquant') $stats_where"))['total'];

// Calcul du SLA (Délai moyen de résolution en heures)
$sla_query = "SELECT AVG(TIMESTAMPDIFF(MINUTE, date_creation, date_cloture)) as avg_minutes FROM demandes WHERE statut = 'terminé' AND date_cloture IS NOT NULL";
$sla_result = mysqli_fetch_assoc(mysqli_query($conn, $sla_query));
$avg_minutes = isset($sla_result['avg_minutes']) ? (float)$sla_result['avg_minutes'] : 0.0;
$total_minutes = (int) round($avg_minutes);
$sla_h = intdiv($total_minutes, 60);
$sla_m = $total_minutes % 60;
$stat_sla_text = ($total_minutes > 0) ? ($sla_h . 'h ' . str_pad((string)$sla_m, 2, '0', STR_PAD_LEFT) . 'm') : '0h 00m';

// Données pour le graphique par catégorie
// Données pour le graphique par catégorie (filtrées par statut actif)
$cat_filter = isset($_GET['filter']) ? $_GET['filter'] : 'en cours';
$cat_labels = [];
$cat_data = [];
$cat_sql = "SELECT categorie, COUNT(*) as count FROM demandes WHERE statut = ? GROUP BY categorie";
$cat_stmt = mysqli_prepare($conn, $cat_sql);
mysqli_stmt_bind_param($cat_stmt, "s", $cat_filter);
mysqli_stmt_execute($cat_stmt);
$cat_res = mysqli_stmt_get_result($cat_stmt);
while($row = mysqli_fetch_assoc($cat_res)) {
    $cat_labels[] = $row['categorie'];
    $cat_data[] = (int)$row['count'];
}
mysqli_stmt_close($cat_stmt);

// 4. Gestion des filtres avancés
$filter_statut = isset($_GET['filter']) ? $_GET['filter'] : 'en cours';
$filter_service = isset($_GET['service']) ? $_GET['service'] : '';
$filter_urgence = isset($_GET['urgence']) ? $_GET['urgence'] : '';

// Si c'est un technicien, on FORCE l'affichage de ses tickets uniquement
$filter_technicien = isset($_GET['technicien']) ? $_GET['technicien'] : '';
if ($_SESSION['user_role'] === 'technicien') {
    $filter_technicien = $_SESSION['user_name'];
}

$filter_search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT * FROM demandes WHERE 1=1";
$params = [];
$types = "";

if ($filter_statut) {
    $sql .= " AND statut = ?";
    $params[] = $filter_statut;
    $types .= "s";
}
if ($filter_service) {
    $sql .= " AND service = ?";
    $params[] = $filter_service;
    $types .= "s";
}
if ($filter_technicien) {
    $sql .= " AND technicien_assigne = ?";
    $params[] = $filter_technicien;
    $types .= "s";
}
if ($filter_urgence) {
    if ($filter_urgence === 'haute') {
        $sql .= " AND (urgence = ? OR urgence = 'bloquant')";
        $params[] = $filter_urgence;
        $types .= "s";
    } elseif ($filter_urgence === 'moyenne') {
        $sql .= " AND (urgence = ? OR urgence IN ('gênant','genant'))";
        $params[] = $filter_urgence;
        $types .= "s";
    } elseif ($filter_urgence === 'basse') {
        $sql .= " AND (urgence = ? OR urgence = 'informatif')";
        $params[] = $filter_urgence;
        $types .= "s";
    } else {
        $sql .= " AND urgence = ?";
        $params[] = $filter_urgence;
        $types .= "s";
    }
}
if ($filter_search) {
    $sql .= " AND (objet LIKE ? OR message LIKE ? OR nom_employe LIKE ?)";
    $search_val = "%$filter_search%";
    $params[] = $search_val;
    $params[] = $search_val;
    $params[] = $search_val;
    $types .= "sss";
}

$sql .= " ORDER BY date_creation DESC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$resultat = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>ETS KAMAL - Tableau de Bord</title>
    <link rel="stylesheet" href="style.css?v=4.1">
    <style>
        .chart-wrapper {
            position: relative;
            height: 250px;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            overflow: hidden;
        }
        #categoryChart {
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container" style="margin-top: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="color: #ffffff; text-shadow: 0 2px 4px rgba(0,0,0,0.3); margin-bottom: 5px;">
                    <?php echo ($_SESSION['user_role'] == 'admin') ? 'Tableau de Bord' : 'Mes Interventions'; ?>
                </h1>
                <p style="color: #cbd5e1; font-size: 0.9em;">
                    Bienvenue, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> 
                    (<?php echo ($_SESSION['user_role'] == 'admin') ? 'Administrateur' : 'Technicien Support'; ?>)
                </p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <a href="export_csv.php" class="btn btn-secondary" style="padding: 5px 15px; font-size: 0.8em;">Exporter Excel (CSV)</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Messages de confirmation -->
        <?php if(isset($_GET['msg'])): ?>
            <?php if($_GET['msg'] == 'updated'): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">La demande a été marquée comme terminée.</div>
            <?php elseif($_GET['msg'] == 'cancelled'): ?>
                <div class="alert alert-success" style="margin-bottom: 20px; background-color: #fff7ed; color: #9a3412; border-color: #ffedd5;">La demande a été annulée.</div>
            <?php elseif($_GET['msg'] == 'assigned'): ?>
                <div class="alert alert-success" style="margin-bottom: 20px; background-color: #e3f2fd; color: #1976d2; border-color: #bbdefb;">Le technicien a été assigné avec succès.</div>
            <?php elseif($_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-success" style="margin-bottom: 20px; background-color: #fce4ec; color: #c2185b;">La demande a été supprimée définitivement.</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-container">
            <div class="stat-card">
                <span class="stat-number"><?php echo $stat_en_cours; ?></span>
                <span class="stat-label">En cours</span>
            </div>
            <div class="stat-card" style="border-top: 4px solid #22c55e;">
                <span class="stat-number" style="color: #22c55e;"><?php echo $stat_basse; ?></span>
                <span class="stat-label">Urgence Basse</span>
            </div>
            <div class="stat-card" style="border-top: 4px solid #f1c40f;">
                <span class="stat-number" style="color: #f1c40f;"><?php echo $stat_moyenne; ?></span>
                <span class="stat-label">Urgence Moyenne</span>
            </div>
            <div class="stat-card" style="border-top: 4px solid #e74c3c;">
                <span class="stat-number" style="color: #e74c3c;"><?php echo $stat_haute; ?></span>
                <span class="stat-label">Urgence Haute</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stat_termines; ?></span>
                <span class="stat-label">Terminés</span>
            </div>
        </div>

        <?php if ($_SESSION['user_role'] == 'admin'): ?>
            <div class="layout-columns" style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 30px;">
                <!-- Graphique : Catégories -->
                <div class="info-panel">
                    <div class="info-title">Répartition globale par Catégorie</div>
                    <div class="chart-wrapper"><canvas id="categoryChart"></canvas></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filtres Avancés -->
        <div class="info-panel">
            <div class="info-title">Filtres Avancés</div>
            <form action="admin.php" method="GET" style="display: grid; gap: 15px;">
                <input type="hidden" name="filter" value="<?php echo $filter_statut; ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <select name="service" style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; background: white; font-weight: 500;">
                        <option value="">Tous les services</option>
                        <option value="Comptabilité" <?php echo $filter_service == 'Comptabilité' ? 'selected' : ''; ?>>Comptabilité</option>
                        <option value="Ressources Humaines" <?php echo $filter_service == 'Ressources Humaines' ? 'selected' : ''; ?>>RH</option>
                        <option value="Commercial" <?php echo $filter_service == 'Commercial' ? 'selected' : ''; ?>>Commercial</option>
                        <option value="Logistique" <?php echo $filter_service == 'Logistique' ? 'selected' : ''; ?>>Logistique</option>
                        <option value="Direction" <?php echo $filter_service == 'Direction' ? 'selected' : ''; ?>>Direction</option>
                    </select>
                    <input type="hidden" name="technicien" value="<?php echo htmlspecialchars($filter_technicien); ?>">
                    <select name="urgence" style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; background: white; font-weight: 500;">
                        <option value="">Toutes urgences</option>
                        <option value="basse" <?php echo $filter_urgence == 'basse' ? 'selected' : ''; ?>>Basse</option>
                        <option value="moyenne" <?php echo $filter_urgence == 'moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                        <option value="haute" <?php echo $filter_urgence == 'haute' ? 'selected' : ''; ?>>Haute</option>
                    </select>
                    <input type="text" name="search" placeholder="Rechercher (Nom, Objet...)" value="<?php echo htmlspecialchars($filter_search); ?>" style="padding: 12px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 14px; background: white; font-weight: 500;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="admin.php?filter=<?php echo $filter_statut; ?>" class="btn btn-secondary" style="padding: 10px 25px; border-radius: 10px; font-size: 0.85em;">Réinitialiser</a>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 40px; border-radius: 10px; font-size: 0.85em; background: #1f3c88; width: auto;">Appliquer les filtres</button>
                </div>
            </form>
        </div>

        <!-- Filtres (Onglets) -->
        <div class="admin-filters">
            <a href="admin.php?filter=en cours<?php echo $filter_service ? '&service='.$filter_service : ''; ?><?php echo $filter_urgence ? '&urgence='.$filter_urgence : ''; ?><?php echo $filter_technicien ? '&technicien='.$filter_technicien : ''; ?><?php echo $filter_search ? '&search='.$filter_search : ''; ?>" 
               class="filter-link <?php echo $filter_statut == 'en cours' ? 'active' : ''; ?>">
                En cours
            </a>
            <a href="admin.php?filter=terminé<?php echo $filter_service ? '&service='.$filter_service : ''; ?><?php echo $filter_urgence ? '&urgence='.$filter_urgence : ''; ?><?php echo $filter_technicien ? '&technicien='.$filter_technicien : ''; ?><?php echo $filter_search ? '&search='.$filter_search : ''; ?>" 
                class="filter-link <?php echo $filter_statut == 'terminé' ? 'active' : ''; ?>">
                    Historique
                </a>
                <a href="admin.php?filter=annulé<?php echo $filter_service ? '&service='.$filter_service : ''; ?><?php echo $filter_urgence ? '&urgence='.$filter_urgence : ''; ?><?php echo $filter_technicien ? '&technicien='.$filter_technicien : ''; ?><?php echo $filter_search ? '&search='.$filter_search : ''; ?>" 
                class="filter-link <?php echo $filter_statut == 'annulé' ? 'active' : ''; ?>">
                    Annulés
                </a>
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <span style="font-size: 0.75em; font-weight: 700; color: #64748b; margin: 0 10px; text-transform: uppercase;">Vue Technicien :</span>
                    <?php foreach($techniciens as $tech): ?>
                        <a href="admin.php?filter=<?php echo $filter_statut; ?>&technicien=<?php echo urlencode($tech); ?>" 
                        style="font-size: 0.8em; padding: 5px 12px; border-radius: 8px; text-decoration: none; transition: background-color 0.2s; 
                        <?php echo $filter_technicien == $tech ? 'background: #1f3c88; color: white;' : 'background: white; color: #1f3c88; border: 1px solid #e2e8f0;'; ?>">
                            <?php echo explode(' ', $tech)[0]; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <!-- Affichage des demandes sous forme de CARTES pour une meilleure lecture -->
        <div class="tickets-list">
            <?php if (mysqli_num_rows($resultat) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($resultat)): ?>
                    <div class="ticket-card">
                        <div class="ticket-header">
                            <div class="ticket-info">
                                <span class="ticket-meta">Reçu le <?php echo date('d/m/Y à H:i', strtotime($row['date_creation'])); ?></span>
                                <?php if ($row['statut'] == 'terminé' && !empty($row['date_cloture'])): ?>
                                    <span class="ticket-meta">Clôturé le <?php echo date('d/m/Y à H:i', strtotime($row['date_cloture'])); ?></span>
                                <?php endif; ?>
                                <h3 style="color: #2c3e50;"><?php echo htmlspecialchars($row['objet']); ?></h3>
                                <div style="margin: 5px 0;">
                                    <span style="background: #e1f5fe; color: #0288d1; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold;">
                                        👤 <?php echo htmlspecialchars($row['nom_employe']); ?> (<?php echo htmlspecialchars($row['service']); ?>)
                                    </span>
                                </div>
                                <div>
                                    <span style="font-size: 0.9em; color: #34495e;">Catégorie : <strong><?php echo htmlspecialchars($row['categorie']); ?></strong></span>
                                    <span class="badge badge-<?php echo $row['priorite_calculee']; ?>" style="margin-left: 10px;">
                                        <?php echo $row['priorite_calculee']; ?>
                                    </span>
                                </div>
                                <?php if(!empty($row['technicien_assigne'])): ?>
                                    <div style="margin-top: 5px;">
                                        <span style="background: #fdf2f2; color: #9b1c1c; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: bold; border: 1px solid #f8d7da;">
                                            🛠️ Technicien : <?php echo htmlspecialchars($row['technicien_assigne']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <?php if(!empty($row['type_equipement'])): ?>
                                    <div style="margin-top: 5px; font-size: 0.85em; color: #64748b;">
                                        Équipement : <strong><?php echo htmlspecialchars($row['type_equipement']); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <?php if(!empty($row['nom_logiciel'])): ?>
                                    <div style="margin-top: 5px; font-size: 0.85em; color: #64748b;">
                                        Logiciel : <strong><?php echo htmlspecialchars($row['nom_logiciel']); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <?php if(!empty($row['num_serie'])): ?>
                                    <div style="margin-top: 5px; font-size: 0.85em; color: #1f3c88; font-weight: 600;">
                                        N° Série / Modèle : <?php echo htmlspecialchars($row['num_serie']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if(!empty($row['piece_jointe'])): ?>
                                    <div style="margin-top: 8px;">
                                        <a href="uploads/<?php echo $row['piece_jointe']; ?>" target="_blank" style="font-size: 0.85em; display: flex; align-items: center; gap: 5px; color: #1f3c88; font-weight: 600;">
                                            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                            Voir la pièce jointe
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="ticket-body">
                            <p style="font-weight: 600; margin-bottom: 5px; color: #2c3e50; font-size: 0.85em; text-transform: uppercase;">Message :</p>
                            <?php
                                $fullMessage = $row['message'];
                                $wrappedMessage = wordwrap($fullMessage, 95, "\n", true);
                                echo nl2br(htmlspecialchars($wrappedMessage));
                            ?>
                        </div>

                        <div class="ticket-footer">
                            <?php if ($row['statut'] == 'en cours'): ?>
                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                    <!-- Assignation du technicien (Admin uniquement) -->
                                    <form action="admin.php" method="POST" style="width: 100%; background: #eff6ff; padding: 12px; border-radius: 12px; border: 1px solid #dbeafe; margin-bottom: 10px;">
                                        <input type="hidden" name="action" value="assigner">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="filter" value="<?php echo $filter_statut; ?>">
                                        
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <label style="font-size: 0.75em; font-weight: 700; color: #1e40af; text-transform: uppercase; white-space: nowrap;">Assigner à :</label>
                                            <select name="technicien" required style="flex: 1; padding: 6px; border: 1px solid #bfdbfe; border-radius: 6px; font-size: 0.85em;">
                                                <option value="">-- Choisir un technicien --</option>
                                                <?php foreach ($techniciens as $tech): ?>
                                                    <option value="<?php echo $tech; ?>" <?php echo ($row['technicien_assigne'] == $tech) ? 'selected' : ''; ?>>
                                                        <?php echo $tech; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-primary" style="padding: 6px 15px; font-size: 0.8em; background: #2563eb;">Mettre à jour</button>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <form action="admin.php" method="POST" style="width: 100%; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 10px;">
                                    <input type="hidden" name="action" value="terminer">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="filter" value="<?php echo $filter_statut; ?>">
                                    
                                    <label style="display: block; font-size: 0.75em; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">Note de résolution :</label>
                                    <textarea name="note_resolution" required placeholder="Décrivez ce qui a été fait pour résoudre l'incident..." 
                                              style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85em; margin-bottom: 10px; min-height: 60px;"></textarea>
                                    
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" class="btn btn-success" style="flex: 2;">Clôturer le ticket</button>
                                        <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                            <a href="admin.php?action=annuler&id=<?php echo $row['id']; ?>&filter=<?php echo $filter_statut; ?>" 
                                               class="btn btn-secondary" style="flex: 1; text-align: center; background: #94a3b8; color: white;"
                                               onclick="return confirm('Annuler cette demande ? (Elle sera déplacée dans les annulés)')">Annuler</a>
                                            <a href="admin.php?action=supprimer&id=<?php echo $row['id']; ?>&filter=<?php echo $filter_statut; ?>" 
                                            class="btn btn-danger" style="flex: 1; text-align: center;"
                                            onclick="return confirm('Supprimer définitivement cette demande ?')">Supprimer</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            <?php elseif ($row['statut'] == 'annulé'): ?>
                                <div style="background: #fff7ed; border-left: 4px solid #f97316; padding: 12px; margin-top: 10px; border-radius: 0 8px 8px 0;">
                                    <p style="font-size: 0.75em; font-weight: 700; color: #9a3412; text-transform: uppercase; margin-bottom: 4px;">⚠️ Demande annulée :</p>
                                    <p style="font-size: 0.85em; color: #c2410c;">Cette demande a été annulée par l'administration.</p>
                                </div>
                                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                    <div style="margin-top: 10px; text-align: right;">
                                        <a href="admin.php?action=supprimer&id=<?php echo $row['id']; ?>&filter=<?php echo $filter_statut; ?>" 
                                        class="btn btn-danger" style="font-size: 0.8em; padding: 5px 15px;"
                                        onclick="return confirm('Supprimer définitivement cette demande annulée ?')">Supprimer</a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if(!empty($row['note_resolution'])): ?>
                                    <div style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 12px; margin-top: 10px; border-radius: 0 8px 8px 0;">
                                        <p style="font-size: 0.75em; font-weight: 700; color: #166534; text-transform: uppercase; margin-bottom: 4px;">✅ Solution appliquée :</p>
                                        <p style="font-size: 0.85em; color: #15803d;"><?php echo nl2br(htmlspecialchars($row['note_resolution'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
                                        <div style="margin-top: 10px; text-align: right;">
                                            <a href="admin.php?action=supprimer&id=<?php echo $row['id']; ?>&filter=<?php echo $filter_statut; ?>" 
                                            class="btn btn-danger" style="font-size: 0.8em; padding: 5px 15px;"
                                            onclick="return confirm('Supprimer définitivement cette demande ?')">Supprimer des archives</a>
                                        </div>
                                    <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert" style="background: #f8f9fa; color: #95a5a6; border: 1px dashed #ddd;">
                    Aucune demande trouvée dans cette catégorie.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($_SESSION['user_role'] == 'admin'): ?>
    <!-- Chart.js uniquement pour l'admin -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuration du graphique par catégorie
            const ctx = document.getElementById('categoryChart');
            if (ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode($cat_labels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($cat_data); ?>,
                            backgroundColor: [
                                '#1f3c88', '#3498db', '#e74c3c', '#2ecc71', '#f1c40f', '#9b59b6'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12, font: { size: 11 } }
                            }
                        }
                    }
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
<?php 
mysqli_stmt_close($stmt);
mysqli_close($conn); 
?>
