<?php
require_once 'includes/auth.php';

$request_id = $_GET['id'] ?? null;
if (!$request_id) {
    header('Location: index.php');
    exit;
}

// Récupérer les détails de la demande
$stmt = $pdo->prepare("
    SELECT r.*, 
           c.fullname as client_name, c.profile_picture as client_pic, c.email as client_email, c.phone_number as client_phone, c.city as client_city,
           p.fullname as pro_name, p.profile_picture as pro_pic, p.profession as pro_job, p.phone_number as pro_phone
    FROM service_requests r
    JOIN users c ON r.client_id = c.id
    JOIN users p ON r.pro_id = p.id
    WHERE r.id = ? AND (r.client_id = ? OR r.pro_id = ?)
");
$stmt->execute([$request_id, $_SESSION['user_id'], $_SESSION['user_id']]);
$req = $stmt->fetch();

if (!$req) {
    die("Demande non trouvée ou accès non autorisé.");
}

$is_pro = ($_SESSION['user_id'] == $req['pro_id']);
$is_client = ($_SESSION['user_id'] == $req['client_id']);

// Récupérer les pièces jointes
$stmt_files = $pdo->prepare("SELECT * FROM request_attachments WHERE request_id = ?");
$stmt_files->execute([$request_id]);
$attachments = $stmt_files->fetchAll();

$page_title = "Détails de la demande - " . $req['project_title'];
require_once 'includes/header.php';

$statuses = [
    'pending' => ['label' => 'En attente', 'class' => 'status-pending', 'icon' => 'clock'],
    'discussion' => ['label' => 'En discussion', 'class' => 'status-discussion', 'icon' => 'message-circle'],
    'accepted' => ['label' => 'Acceptée', 'class' => 'status-accepted', 'icon' => 'check-circle'],
    'declined' => ['label' => 'Refusée', 'class' => 'status-declined', 'icon' => 'x-circle'],
    'completed' => ['label' => 'Terminée', 'class' => 'status-completed', 'icon' => 'award'],
    'cancelled' => ['label' => 'Annulée', 'class' => 'status-cancelled', 'icon' => 'slash']
];
$status_info = $statuses[$req['status']];
?>

<div class="app-layout">
    <div class="container">
        <div class="request-details-layout">
            <!-- Left Column: Request Content -->
            <div class="request-main-col">
                <div class="back-nav">
                    <a href="<?php echo $is_pro ? 'incoming_requests.php' : 'my_requests.php'; ?>" class="btn-back">
                        <i data-lucide="arrow-left"></i> Retour aux demandes
                    </a>
                </div>

                <div class="request-hero-card">
                    <div class="req-hero-header">
                        <span class="req-category-tag"><?php echo htmlspecialchars($req['service_category']); ?></span>
                        <div class="req-status-badge <?php echo $status_info['class']; ?>">
                            <i data-lucide="<?php echo $status_info['icon']; ?>"></i>
                            <span><?php echo $status_info['label']; ?></span>
                        </div>
                    </div>
                    <h1><?php echo htmlspecialchars($req['project_title']); ?></h1>
                    <div class="req-hero-meta">
                        <div class="meta-item"><i data-lucide="calendar"></i> Envoyée le <?php echo date('d M Y à H:i', strtotime($req['created_at'])); ?></div>
                        <div class="meta-item"><i data-lucide="map-pin"></i> <?php echo htmlspecialchars($req['location']); ?></div>
                    </div>
                </div>

                <div class="request-section">
                    <h3>Description du projet</h3>
                    <div class="description-text">
                        <?php echo nl2br(htmlspecialchars($req['description'])); ?>
                    </div>
                </div>

                <?php if (!empty($attachments)): ?>
                <div class="request-section">
                    <h3>Documents & Photos</h3>
                    <div class="attachments-grid">
                        <?php foreach ($attachments as $file): ?>
                            <a href="assets/uploads/requests/<?php echo $file['file_path']; ?>" target="_blank" class="attachment-item">
                                <?php if ($file['file_type'] == 'image'): ?>
                                    <img src="assets/uploads/requests/<?php echo $file['file_path']; ?>" alt="">
                                <?php else: ?>
                                    <div class="doc-icon"><i data-lucide="file-text"></i></div>
                                    <span class="file-name"><?php echo mb_strimwidth($file['file_path'], 0, 15, "..."); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="request-section">
                    <h3>Détails logistiques</h3>
                    <div class="details-grid-info">
                        <div class="detail-info-item">
                            <span class="label">Date souhaitée</span>
                            <span class="value"><?php echo $req['desired_date'] ? date('d M Y', strtotime($req['desired_date'])) : 'Non spécifiée'; ?></span>
                        </div>
                        <div class="detail-info-item">
                            <span class="label">Budget estimé</span>
                            <span class="value highlight"><?php echo $req['budget'] ? number_format($req['budget'], 0, ',', ' ') . ' FCFA' : 'Sur devis'; ?></span>
                        </div>
                        <div class="detail-info-item">
                            <span class="label">Préférence de contact</span>
                            <span class="value"><?php echo ucfirst($req['contact_preference']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sidebar Actions & Info -->
            <aside class="request-sidebar">
                <!-- User Info Card -->
                <div class="side-widget-premium user-info-widget">
                    <h4 class="widget-title"><?php echo $is_pro ? 'Client' : 'Professionnel'; ?></h4>
                    <div class="widget-user-flex">
                        <img src="<?php echo get_user_avatar(['profile_picture' => $is_pro ? $req['client_pic'] : $req['pro_pic']]); ?>" alt="">
                        <div>
                            <h4 class="user-name"><?php echo htmlspecialchars($is_pro ? ($req['client_name'] ?? '') : ($req['pro_name'] ?? '')); ?></h4>
                            <span class="user-meta"><?php echo htmlspecialchars($is_pro ? ($req['client_city'] ?? 'Cameroun') : ($req['pro_job'] ?? 'Professionnel')); ?></span>
                        </div>
                    </div>
                    <?php if ($req['status'] == 'accepted' || $req['status'] == 'discussion'): ?>
                        <div class="user-contact-actions">
                            <a href="https://wa.me/<?php echo str_replace([' ', '+'], '', $is_pro ? $req['client_phone'] : $req['pro_phone']); ?>" class="btn-sidebar-contact whatsapp">
                                <i data-lucide="message-circle"></i> WhatsApp
                            </a>
                            <a href="tel:<?php echo $is_pro ? $req['client_phone'] : $req['pro_phone']; ?>" class="btn-sidebar-contact phone">
                                <i data-lucide="phone"></i> Appeler
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions Card -->
                <div class="side-widget-premium actions-widget">
                    <h4 class="widget-title">Actions disponibles</h4>
                    <div class="actions-stack">
                        <!-- Quick Chat Action -->
                        <a href="api/start_conversation.php?with=<?php echo $is_pro ? $req['client_id'] : $req['pro_id']; ?>&request_id=<?php echo $request_id; ?>" class="btn-action-premium secondary">
                            <i data-lucide="message-square"></i> Discuter du projet
                        </a>

                        <?php if ($is_pro): ?>
                            <?php if ($req['status'] == 'pending'): ?>
                                <button onclick="updateStatus('accepted')" class="btn-action-premium primary">Accepter la demande</button>
                                <button onclick="updateStatus('discussion')" class="btn-action-premium secondary">Demander plus d'infos</button>
                                <button onclick="updateStatus('declined')" class="btn-action-premium danger">Refuser</button>
                            <?php elseif ($req['status'] == 'accepted' || $req['status'] == 'discussion'): ?>
                                <button onclick="updateStatus('completed')" class="btn-action-premium success">Marquer comme terminé</button>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($is_client && ($req['status'] == 'pending' || $req['status'] == 'discussion')): ?>
                            <button onclick="updateStatus('cancelled')" class="btn-action-premium danger">Annuler la demande</button>
                        <?php endif; ?>

                        <?php if ($is_client && $req['status'] == 'completed'): ?>
                            <button onclick="openReviewModal()" class="btn-action-premium primary">Laisser un avis client</button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="help-sidebar-card">
                    <i data-lucide="shield-check"></i>
                    <p>Vos échanges sont sécurisés par Tekrin Protect.</p>
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
    .request-details-layout { display: grid; grid-template-columns: 1fr 350px; gap: 40px; margin-top: 20px; }
    @media (max-width: 992px) { .request-details-layout { grid-template-columns: 1fr; } }

    .btn-back { display: flex; align-items: center; gap: 8px; color: var(--text-muted); text-decoration: none; font-weight: 700; font-size: 14px; margin-bottom: 24px; transition: color 0.2s; }
    .btn-back:hover { color: var(--primary-blue); }

    .request-hero-card { background: white; border-radius: 28px; padding: 40px; margin-bottom: 32px; box-shadow: var(--shadow-sm); border: 1px solid #F1F5F9; }
    .req-hero-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .req-category-tag { background: #EEF2FF; color: var(--primary-blue); padding: 6px 16px; border-radius: 50px; font-size: 13px; font-weight: 800; }
    .request-hero-card h1 { font-size: 32px; font-weight: 900; color: var(--dark-bg); margin-bottom: 16px; }
    .req-hero-meta { display: flex; gap: 24px; color: var(--text-muted); font-size: 14px; font-weight: 600; }
    .meta-item { display: flex; align-items: center; gap: 8px; }

    .request-section { background: white; border-radius: 24px; padding: 32px; margin-bottom: 32px; border: 1px solid #F1F5F9; }
    .request-section h3 { font-size: 18px; font-weight: 800; color: var(--dark-bg); margin-bottom: 20px; }
    .description-text { color: #475569; line-height: 1.8; font-size: 16px; }

    .attachments-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 16px; }
    .attachment-item { aspect-ratio: 1/1; border-radius: 16px; overflow: hidden; border: 2px solid #F1F5F9; display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; transition: border-color 0.2s; }
    .attachment-item:hover { border-color: var(--primary-blue); }
    .attachment-item img { width: 100%; height: 100%; object-fit: cover; }
    .doc-icon { color: var(--primary-blue); margin-bottom: 8px; }
    .file-name { font-size: 11px; font-weight: 700; color: var(--text-muted); padding: 0 8px; text-align: center; }

    .details-grid-info { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .detail-info-item { display: flex; flex-direction: column; gap: 4px; }
    .detail-info-item .label { font-size: 12px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .detail-info-item .value { font-size: 16px; font-weight: 700; color: var(--dark-bg); }
    .detail-info-item .value.highlight { color: var(--primary-blue); }

    .side-widget-premium { background: white; border-radius: 24px; padding: 28px; margin-bottom: 24px; border: 1px solid #F1F5F9; box-shadow: var(--shadow-sm); }
    .widget-title { font-size: 13px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; }
    .widget-user-flex { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
    .widget-user-flex img { width: 56px; height: 52px; border-radius: 16px; object-fit: cover; }
    .user-name { font-size: 17px; font-weight: 800; color: var(--dark-bg); }
    .user-meta { font-size: 13px; color: var(--text-muted); font-weight: 600; }

    .user-contact-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .btn-sidebar-contact { padding: 12px; border-radius: 12px; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; font-weight: 800; text-decoration: none; transition: transform 0.2s; }
    .btn-sidebar-contact.whatsapp { background: #25D366; color: white; }
    .btn-sidebar-contact.phone { background: #F1F5F9; color: var(--dark-bg); }
    .btn-sidebar-contact:hover { transform: translateY(-2px); }

    .actions-stack { display: flex; flex-direction: column; gap: 12px; }
    .btn-action-premium { width: 100%; padding: 14px; border-radius: 14px; border: none; font-weight: 800; font-size: 15px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
    .btn-action-premium.primary { background: var(--grad-primary); color: white; box-shadow: 0 8px 20px rgba(124, 77, 255, 0.2); }
    .btn-action-premium.secondary { background: #EEF2FF; color: var(--primary-blue); }
    .btn-action-premium.danger { background: #FEF2F2; color: #EF4444; }
    .btn-action-premium.success { background: #ECFDF5; color: #10B981; }
    .btn-action-premium:hover { transform: translateY(-2px); filter: brightness(1.05); }

    .help-sidebar-card { background: #F8FAFC; border-radius: 20px; padding: 20px; display: flex; align-items: center; gap: 12px; color: var(--text-muted); font-size: 13px; font-weight: 600; }
    .help-sidebar-card i { color: #10B981; }

    .req-status-badge { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-pending { background: #FFF7ED; color: #F59E0B; }
    .status-discussion { background: #EEF2FF; color: #6366F1; }
    .status-accepted { background: #ECFDF5; color: #10B981; }
    .status-declined { background: #FEF2F2; color: #EF4444; }
    .status-completed { background: #F0FDF4; color: #16A34A; }
    .status-cancelled { background: #F8FAFC; color: #94A3B8; }
</style>

<script>
function updateStatus(newStatus) {
    if (!confirm('Êtes-vous sûr de vouloir changer le statut de cette demande ?')) return;
    
    fetch('api/update_request_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            request_id: <?php echo $request_id; ?>,
            status: newStatus
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur : ' + data.message);
        }
    });
}
</script>

    <div id="reviewModal" class="modal-premium">
        <div class="modal-premium-content">
            <header class="modal-premium-header">
                <button class="btn-close-modal" onclick="closeReviewModal()">
                    <i data-lucide="x"></i>
                </button>
                <h2>Laisser un avis client</h2>
                <p>Partagez votre expérience avec <?php echo htmlspecialchars($req['pro_name']); ?>.</p>
            </header>
            
            <form action="api/submit_review.php" method="POST">
                <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                <input type="hidden" name="pro_id" value="<?php echo $req['pro_id']; ?>">
                
                <div class="modal-premium-body">
                    <div class="rating-selector-premium">
                        <label>Votre note</label>
                        <div class="star-rating">
                            <input type="radio" name="rating" value="5" id="star5"><label for="star5">⭐</label>
                            <input type="radio" name="rating" value="4" id="star4"><label for="star4">⭐</label>
                            <input type="radio" name="rating" value="3" id="star3"><label for="star3">⭐</label>
                            <input type="radio" name="rating" value="2" id="star2"><label for="star2">⭐</label>
                            <input type="radio" name="rating" value="1" id="star1"><label for="star1">⭐</label>
                        </div>
                    </div>
                    
                    <div class="form-group-premium form-group-full">
                        <label>Votre commentaire</label>
                        <textarea name="comment" class="form-control-premium" rows="4" placeholder="Qualité du travail, ponctualité, communication..." required></textarea>
                    </div>
                </div>
                
                <footer class="modal-premium-footer">
                    <button type="submit" class="btn-premium-submit">Publier l'avis</button>
                </footer>
            </form>
        </div>
    </div>

    <style>
        /* Star Rating Styles */
        .rating-selector-premium { text-align: center; margin-bottom: 24px; }
        .rating-selector-premium label { display: block; font-size: 14px; font-weight: 800; margin-bottom: 12px; }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 8px; font-size: 32px; }
        .star-rating input { display: none; }
        .star-rating label { cursor: pointer; filter: grayscale(1); transition: all 0.2s; }
        .star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label { filter: grayscale(0); transform: scale(1.2); }

        .modal-premium {
            display: none;
            position: fixed;
            z-index: 4000;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-premium-content {
            background: white;
            width: 100%;
            max-width: 550px;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .modal-premium-header { background: var(--grad-primary); padding: 24px; color: white; position: relative; }
        .modal-premium-header h2 { font-size: 20px; font-weight: 800; }
        .modal-premium-header p { font-size: 14px; opacity: 0.9; }
        .btn-close-modal { position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .modal-premium-body { padding: 24px; }
        .form-group-premium { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .form-group-premium label { font-size: 13px; font-weight: 800; color: var(--dark-bg); }
        .form-control-premium { background: #F8FAFC; border: 2px solid #F1F5F9; padding: 12px; border-radius: 12px; outline: none; }
        .modal-premium-footer { padding: 0 24px 24px; }
        .btn-premium-submit { width: 100%; background: var(--grad-primary); color: white; border: none; padding: 14px; border-radius: 12px; font-weight: 800; cursor: pointer; }
    </style>

    <script>
    function openReviewModal() {
        document.getElementById('reviewModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeReviewModal() {
        document.getElementById('reviewModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    </script>
<?php require_once 'includes/footer.php'; ?>
