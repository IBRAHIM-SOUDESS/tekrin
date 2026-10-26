<?php
require_once 'includes/auth.php';

$page_title = "Demandes reçues - Tekrin";
require_once 'includes/header.php';

$pro_id = $_SESSION['user_id'];

// Récupérer les demandes pour le professionnel
$stmt = $pdo->prepare("
    SELECT r.*, u.fullname as client_name, u.profile_picture as client_pic, u.city as client_city
    FROM service_requests r
    JOIN users u ON r.client_id = u.id
    WHERE r.pro_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$pro_id]);
$requests = $stmt->fetchAll();

// Statistiques pour le dashboard pro
$stats = [
    'pending' => 0,
    'discussion' => 0,
    'accepted' => 0,
    'completed' => 0
];
foreach ($requests as $r) {
    if (isset($stats[$r['status']])) {
        $stats[$r['status']]++;
    }
}

$statuses = [
    'pending' => ['label' => 'Nouvelle', 'class' => 'status-pending', 'icon' => 'plus-circle'],
    'discussion' => ['label' => 'Discussion', 'class' => 'status-discussion', 'icon' => 'message-circle'],
    'accepted' => ['label' => 'En cours', 'class' => 'status-accepted', 'icon' => 'play-circle'],
    'declined' => ['label' => 'Refusée', 'class' => 'status-declined', 'icon' => 'x-circle'],
    'completed' => ['label' => 'Terminée', 'class' => 'status-completed', 'icon' => 'award'],
    'cancelled' => ['label' => 'Annulée', 'class' => 'status-cancelled', 'icon' => 'slash']
];
?>

<div class="app-layout">
    <div class="container">
        <div class="page-header-premium">
            <div class="header-text">
                <h1>Demandes de service reçues</h1>
                <p>Gérez vos opportunités et développez votre activité sur Tekrin.</p>
            </div>
        </div>

        <div class="pro-stats-row">
            <div class="pro-stat-card">
                <div class="stat-icon pending"><i data-lucide="clock"></i></div>
                <div class="stat-info">
                    <span class="stat-count"><?php echo $stats['pending']; ?></span>
                    <span class="stat-label">En attente</span>
                </div>
            </div>
            <div class="pro-stat-card">
                <div class="stat-icon discussion"><i data-lucide="message-square"></i></div>
                <div class="stat-info">
                    <span class="stat-count"><?php echo $stats['discussion']; ?></span>
                    <span class="stat-label">En discussion</span>
                </div>
            </div>
            <div class="pro-stat-card">
                <div class="stat-icon accepted"><i data-lucide="check-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-count"><?php echo $stats['accepted']; ?></span>
                    <span class="stat-label">Acceptées</span>
                </div>
            </div>
            <div class="pro-stat-card">
                <div class="stat-icon completed"><i data-lucide="award"></i></div>
                <div class="stat-info">
                    <span class="stat-count"><?php echo $stats['completed']; ?></span>
                    <span class="stat-label">Terminées</span>
                </div>
            </div>
        </div>

        <div class="requests-grid-premium">
            <?php if (empty($requests)): ?>
                <div class="empty-state-card">
                    <div class="empty-icon"><i data-lucide="inbox"></i></div>
                    <h3>Aucune demande reçue</h3>
                    <p>Optimisez votre profil et vos services pour attirer plus de clients.</p>
                    <a href="profile_edit.php" class="btn btn-primary">Améliorer mon profil</a>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $req): 
                    $status_info = $statuses[$req['status']];
                ?>
                    <div class="request-card-premium pro-version">
                        <div class="req-card-header">
                            <div class="req-client-info">
                                <div class="client-avatar-wrapper">
                                    <img src="<?php echo get_user_avatar(['profile_picture' => $req['client_pic']]); ?>" alt="">
                                    <?php if ($req['status'] == 'pending'): ?>
                                        <span class="new-indicator"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="client-name"><?php echo htmlspecialchars($req['client_name']); ?></h4>
                                    <span class="client-meta"><?php echo htmlspecialchars($req['client_city'] ?? 'Cameroun'); ?> • <?php echo time_ago($req['created_at']); ?></span>
                                </div>
                            </div>
                            <div class="req-status-badge <?php echo $status_info['class']; ?>">
                                <i data-lucide="<?php echo $status_info['icon']; ?>"></i>
                                <span><?php echo $status_info['label']; ?></span>
                            </div>
                        </div>
                        
                        <div class="req-card-body">
                            <h3 class="project-title"><?php echo htmlspecialchars($req['project_title']); ?></h3>
                            <p class="project-desc-short"><?php echo mb_strimwidth(htmlspecialchars($req['description']), 0, 120, "..."); ?></p>
                            <div class="req-meta-row">
                                <div class="meta-item">
                                    <i data-lucide="tag"></i>
                                    <span><?php echo htmlspecialchars($req['service_category']); ?></span>
                                </div>
                                <?php if ($req['budget']): ?>
                                    <div class="meta-item budget">
                                        <i data-lucide="dollar-sign"></i>
                                        <span><?php echo number_format($req['budget'], 0, ',', ' '); ?> FCFA</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="req-card-footer">
                            <div class="action-buttons-quick">
                                <?php if ($req['status'] == 'pending'): ?>
                                    <button class="btn-quick-action accept" title="Accepter"><i data-lucide="check"></i></button>
                                    <button class="btn-quick-action decline" title="Refuser"><i data-lucide="x"></i></button>
                                <?php endif; ?>
                            </div>
                            <a href="request_details.php?id=<?php echo $req['id']; ?>" class="btn-view-details">
                                Gérer la demande <i data-lucide="arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .page-header-premium { margin-bottom: 32px; }
    .page-header-premium h1 { font-size: 28px; font-weight: 800; color: var(--dark-bg); margin-bottom: 8px; }
    .page-header-premium p { color: var(--text-muted); font-size: 16px; }

    .pro-stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
    .pro-stat-card { background: white; border-radius: 20px; padding: 24px; display: flex; align-items: center; gap: 16px; border: 1px solid #F1F5F9; box-shadow: var(--shadow-sm); }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .stat-icon.pending { background: #FFF7ED; color: #F59E0B; }
    .stat-icon.discussion { background: #EEF2FF; color: #6366F1; }
    .stat-icon.accepted { background: #ECFDF5; color: #10B981; }
    .stat-icon.completed { background: #F0FDF4; color: #16A34A; }
    .stat-count { display: block; font-size: 20px; font-weight: 900; color: var(--dark-bg); }
    .stat-label { font-size: 13px; color: var(--text-muted); font-weight: 600; }

    .requests-grid-premium { display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 24px; }
    @media (max-width: 640px) { .requests-grid-premium { grid-template-columns: 1fr; } .pro-stats-row { grid-template-columns: 1fr 1fr; } }

    .request-card-premium { background: white; border-radius: 24px; border: 1px solid #F1F5F9; padding: 24px; transition: all 0.3s ease; box-shadow: var(--shadow-sm); }
    .request-card-premium:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.05); border-color: var(--primary-blue); }

    .req-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
    .req-client-info { display: flex; align-items: center; gap: 12px; }
    .client-avatar-wrapper { position: relative; }
    .client-avatar-wrapper img { width: 52px; height: 52px; border-radius: 16px; object-fit: cover; }
    .new-indicator { position: absolute; top: -4px; right: -4px; width: 12px; height: 12px; background: #EF4444; border: 2px solid white; border-radius: 50%; }
    .client-name { font-size: 16px; font-weight: 800; color: var(--dark-bg); margin-bottom: 2px; }
    .client-meta { font-size: 12px; color: var(--text-muted); font-weight: 600; }

    .req-status-badge { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-pending { background: #FFF7ED; color: #F59E0B; }
    .status-discussion { background: #EEF2FF; color: #6366F1; }
    .status-accepted { background: #ECFDF5; color: #10B981; }
    .status-declined { background: #FEF2F2; color: #EF4444; }
    .status-completed { background: #F0FDF4; color: #16A34A; }
    .status-cancelled { background: #F8FAFC; color: #94A3B8; }

    .project-title { font-size: 18px; font-weight: 800; color: var(--dark-bg); margin-bottom: 8px; }
    .project-desc-short { font-size: 14px; color: #475569; line-height: 1.5; margin-bottom: 16px; }
    .req-meta-row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; }
    .meta-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748B; font-weight: 600; }
    .meta-item i { width: 16px; height: 16px; color: #94A3B8; }
    .meta-item.budget { color: var(--primary-blue); font-weight: 800; }

    .req-card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid #F8FAFC; }
    .action-buttons-quick { display: flex; gap: 8px; }
    .btn-quick-action { width: 36px; height: 36px; border-radius: 10px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; }
    .btn-quick-action.accept { background: #ECFDF5; color: #10B981; }
    .btn-quick-action.accept:hover { background: #10B981; color: white; }
    .btn-quick-action.decline { background: #FEF2F2; color: #EF4444; }
    .btn-quick-action.decline:hover { background: #EF4444; color: white; }
    .btn-view-details { color: var(--primary-blue); font-size: 14px; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 4px; }
    .btn-view-details:hover { gap: 8px; }

    .empty-state-card { grid-column: 1 / -1; background: white; border-radius: 24px; padding: 60px; text-align: center; border: 2px dashed #E2E8F0; }
    .empty-icon { width: 80px; height: 80px; background: #F1F5F9; color: #94A3B8; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
    .empty-icon i { width: 40px; height: 40px; }
    .empty-state-card h3 { font-size: 20px; font-weight: 800; color: var(--dark-bg); margin-bottom: 12px; }
    .empty-state-card p { color: var(--text-muted); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; }
</style>

<?php require_once 'includes/footer.php'; ?>
