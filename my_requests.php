<?php
require_once 'includes/auth.php';

$page_title = "Mes demandes de service - Tekrin";
require_once 'includes/header.php';

$client_id = $_SESSION['user_id'];

// Récupérer les demandes du client
$stmt = $pdo->prepare("
    SELECT r.*, u.fullname as pro_name, u.profile_picture as pro_pic, u.profession as pro_job
    FROM service_requests r
    JOIN users u ON r.pro_id = u.id
    WHERE r.client_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$client_id]);
$requests = $stmt->fetchAll();

$statuses = [
    'pending' => ['label' => 'En attente', 'class' => 'status-pending', 'icon' => 'clock'],
    'discussion' => ['label' => 'En discussion', 'class' => 'status-discussion', 'icon' => 'message-circle'],
    'accepted' => ['label' => 'Acceptée', 'class' => 'status-accepted', 'icon' => 'check-circle'],
    'declined' => ['label' => 'Refusée', 'class' => 'status-declined', 'icon' => 'x-circle'],
    'completed' => ['label' => 'Terminée', 'class' => 'status-completed', 'icon' => 'award'],
    'cancelled' => ['label' => 'Annulée', 'class' => 'status-cancelled', 'icon' => 'slash']
];
?>

<div class="app-layout">
    <div class="container">
        <div class="page-header-premium">
            <div class="header-text">
                <h1>Mes demandes de service</h1>
                <p>Suivez l'état de vos projets et échangez avec vos professionnels.</p>
            </div>
        </div>

        <div class="requests-filter-tabs">
            <button class="filter-tab active" data-status="all">Toutes</button>
            <button class="filter-tab" data-status="pending">En attente</button>
            <button class="filter-tab" data-status="accepted">En cours</button>
            <button class="filter-tab" data-status="completed">Terminées</button>
        </div>

        <div class="requests-grid-premium">
            <?php if (empty($requests)): ?>
                <div class="empty-state-card">
                    <div class="empty-icon"><i data-lucide="clipboard-list"></i></div>
                    <h3>Aucune demande pour le moment</h3>
                    <p>Vous n'avez pas encore envoyé de demande de service. Explorez nos professionnels pour commencer votre projet.</p>
                    <a href="discover.php" class="btn btn-primary">Découvrir des pros</a>
                </div>
            <?php else: ?>
                <?php foreach ($requests as $req): 
                    $status_info = $statuses[$req['status']];
                ?>
                    <div class="request-card-premium" data-status="<?php echo $req['status']; ?>">
                        <div class="req-card-header">
                            <div class="req-pro-info">
                                <img src="<?php echo get_user_avatar(['profile_picture' => $req['pro_pic']]); ?>" alt="">
                                <div>
                                    <h4 class="pro-name"><?php echo htmlspecialchars($req['pro_name']); ?></h4>
                                    <span class="pro-job"><?php echo htmlspecialchars($req['pro_job'] ?? 'Professionnel'); ?></span>
                                </div>
                            </div>
                            <div class="req-status-badge <?php echo $status_info['class']; ?>">
                                <i data-lucide="<?php echo $status_info['icon']; ?>"></i>
                                <span><?php echo $status_info['label']; ?></span>
                            </div>
                        </div>
                        
                        <div class="req-card-body">
                            <h3 class="project-title"><?php echo htmlspecialchars($req['project_title']); ?></h3>
                            <div class="req-meta-row">
                                <div class="meta-item">
                                    <i data-lucide="tag"></i>
                                    <span><?php echo htmlspecialchars($req['service_category']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i data-lucide="calendar"></i>
                                    <span><?php echo date('d M Y', strtotime($req['created_at'])); ?></span>
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
                            <span class="last-update">Mise à jour : <?php echo time_ago($req['updated_at']); ?></span>
                            <a href="request_details.php?id=<?php echo $req['id']; ?>" class="btn-view-details">
                                Voir les détails <i data-lucide="chevron-right"></i>
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

    .requests-filter-tabs { display: flex; gap: 12px; margin-bottom: 32px; overflow-x: auto; padding-bottom: 8px; }
    .filter-tab { background: white; border: 1px solid #E2E8F0; padding: 10px 24px; border-radius: 12px; font-size: 14px; font-weight: 700; color: #64748B; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
    .filter-tab:hover { background: #F8FAFC; color: var(--primary-blue); }
    .filter-tab.active { background: var(--primary-blue); color: white; border-color: var(--primary-blue); box-shadow: 0 4px 12px rgba(21, 101, 192, 0.2); }

    .requests-grid-premium { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 24px; }
    @media (max-width: 640px) { .requests-grid-premium { grid-template-columns: 1fr; } }

    .request-card-premium { background: white; border-radius: 24px; border: 1px solid #F1F5F9; padding: 24px; transition: all 0.3s ease; box-shadow: var(--shadow-sm); }
    .request-card-premium:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.05); border-color: var(--primary-blue); }

    .req-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
    .req-pro-info { display: flex; align-items: center; gap: 12px; }
    .req-pro-info img { width: 48px; height: 48px; border-radius: 14px; object-fit: cover; }
    .pro-name { font-size: 16px; font-weight: 800; color: var(--dark-bg); margin-bottom: 2px; }
    .pro-job { font-size: 13px; color: var(--text-muted); font-weight: 600; }

    .req-status-badge { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-pending { background: #FFF7ED; color: #F59E0B; }
    .status-discussion { background: #EEF2FF; color: #6366F1; }
    .status-accepted { background: #ECFDF5; color: #10B981; }
    .status-declined { background: #FEF2F2; color: #EF4444; }
    .status-completed { background: #F0FDF4; color: #16A34A; }
    .status-cancelled { background: #F8FAFC; color: #94A3B8; }
    .req-status-badge i { width: 14px; height: 14px; }

    .project-title { font-size: 18px; font-weight: 800; color: var(--dark-bg); margin-bottom: 16px; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    .req-meta-row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; }
    .meta-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748B; font-weight: 600; }
    .meta-item i { width: 16px; height: 16px; color: #94A3B8; }
    .meta-item.budget { color: var(--primary-blue); font-weight: 800; }

    .req-card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px solid #F8FAFC; }
    .last-update { font-size: 12px; color: var(--text-muted); font-weight: 500; }
    .btn-view-details { color: var(--primary-blue); font-size: 14px; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 4px; }
    .btn-view-details:hover { gap: 8px; }

    .empty-state-card { grid-column: 1 / -1; background: white; border-radius: 24px; padding: 60px; text-align: center; border: 2px dashed #E2E8F0; }
    .empty-icon { width: 80px; height: 80px; background: #F1F5F9; color: #94A3B8; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
    .empty-icon i { width: 40px; height: 40px; }
    .empty-state-card h3 { font-size: 20px; font-weight: 800; color: var(--dark-bg); margin-bottom: 12px; }
    .empty-state-card p { color: var(--text-muted); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.filter-tab');
        const cards = document.querySelectorAll('.request-card-premium');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const status = tab.dataset.status;
                cards.forEach(card => {
                    if (status === 'all' || card.dataset.status === status) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
