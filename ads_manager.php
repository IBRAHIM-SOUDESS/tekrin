<?php
require_once 'includes/auth.php';
if (!isLoggedIn()) redirect('login.php');
requireOnboarding();
$user_id = $_SESSION['user_id'];

// Gestion de l'ajout de publicité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_ad') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $link = sanitize($_POST['link']);
    $ad_type = sanitize($_POST['ad_type']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $image_url = '';
    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/uploads/ads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $extension = pathinfo($_FILES['ad_image']['name'], PATHINFO_EXTENSION);
        $new_name = 'ad_' . $user_id . '_' . time() . '.' . $extension;
        move_uploaded_file($_FILES['ad_image']['tmp_name'], $upload_dir . $new_name);
        $image_url = $upload_dir . $new_name;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO ads (user_id, title, description, image_url, link, ad_type, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $title, $description, $image_url, $link, $ad_type, $start_date, $end_date])) {
            setFlashMessage('Publicité créée avec succès !', 'success');
        } else {
            setFlashMessage('Erreur lors de la création de la publicité.', 'danger');
        }
    } catch (Exception $e) {
        setFlashMessage('Erreur : ' . $e->getMessage(), 'danger');
    }
    redirect('ads_manager.php');
}

// Gestion de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_ad' && isset($_POST['ad_id'])) {
    $ad_id = (int)$_POST['ad_id'];
    $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$ad_id, $user_id])) {
        setFlashMessage('Publicité supprimée !', 'success');
    }
    redirect('ads_manager.php');
}

// Récupérer mes publicités
$stmt = $pdo->prepare("SELECT * FROM ads WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_ads = $stmt->fetchAll();

// Calculer les stats globales
$total_ads = count($my_ads);
$total_views = 0;
$total_clicks = 0;
foreach ($my_ads as $ad) {
    $total_views += $ad['views'];
    $total_clicks += $ad['clicks'];
}
$avg_ctr = $total_views > 0 ? round(($total_clicks / $total_views) * 100, 2) : 0;

require_once 'includes/header.php';
?>

<div class="app-layout">
    <div class="container" style="max-width: 1400px;">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <div>
                <h1 style="font-weight:800; font-size:32px; margin-bottom:8px;">Gestion des publicités</h1>
                <p style="color:#64748B;">Créez, gérez et suivez vos campagnes publicitaires</p>
            </div>
            <button class="btn btn-register" id="createAdBtn" style="display:flex; align-items:center; gap:8px; padding:12px 24px; font-weight:700; border-radius:12px;">
                <i data-lucide="plus"></i>
                Créer une publicité
            </button>
        </div>

        <!-- Stats Cards -->
        <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:20px; margin-bottom:30px;">
            <div class="card" style="padding:24px;">
                <div style="display:flex; align-items:center; gap:16px;">
                    <div style="background:#EFF6FF; padding:16px; border-radius:16px;">
                        <i data-lucide="megaphone" style="width:32px; height:32px; color:#1565C0;"></i>
                    </div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#64748B; margin-bottom:4px;">Total publicités</div>
                        <div style="font-size:28px; font-weight:800; color:#1E293B;"><?php echo $total_ads; ?></div>
                    </div>
                </div>
            </div>
            
            <div class="card" style="padding:24px;">
                <div style="display:flex; align-items:center; gap:16px;">
                    <div style="background:#ECFDF5; padding:16px; border-radius:16px;">
                        <i data-lucide="eye" style="width:32px; height:32px; color:#10B981;"></i>
                    </div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#64748B; margin-bottom:4px;">Vues totales</div>
                        <div style="font-size:28px; font-weight:800; color:#1E293B;"><?php echo number_format($total_views); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="card" style="padding:24px;">
                <div style="display:flex; align-items:center; gap:16px;">
                    <div style="background:#FFFBEB; padding:16px; border-radius:16px;">
                        <i data-lucide="mouse-pointer-2" style="width:32px; height:32px; color:#D97706;"></i>
                    </div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#64748B; margin-bottom:4px;">Clics totaux</div>
                        <div style="font-size:28px; font-weight:800; color:#1E293B;"><?php echo number_format($total_clicks); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="card" style="padding:24px;">
                <div style="display:flex; align-items:center; gap:16px;">
                    <div style="background:#FDF4FF; padding:16px; border-radius:16px;">
                        <i data-lucide="trending-up" style="width:32px; height:32px; color:#9333EA;"></i>
                    </div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#64748B; margin-bottom:4px;">Taux de clic (CTR)</div>
                        <div style="font-size:28px; font-weight:800; color:#1E293B;"><?php echo $avg_ctr; ?>%</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Créer Publicité -->
        <div id="createAdModal" class="modal-premium" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
            <div class="modal-premium-content" style="width:100%; max-width:700px; background:white; border-radius:24px; overflow:hidden;">
                <div class="modal-premium-header" style="background:linear-gradient(135deg, #1565C0, #6A1B9A); padding:24px 30px; color:white;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h2 style="font-weight:800; margin-bottom:4px;">Créer une nouvelle publicité</h2>
                            <p style="opacity:0.9; font-size:14px;">Configurez votre campagne en quelques étapes</p>
                        </div>
                        <button class="btn-close-modal" id="closeAdModal" style="background:rgba(255,255,255,0.2); border:none; color:white; width:40px; height:40px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>
                
                <div class="modal-premium-body" style="padding:30px;">
                    <form id="createAdForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_ad">
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div style="grid-column:span 2;">
                                <label style="display:block; font-weight:800; margin-bottom:8px; color:#1E293B;">Titre de la publicité</label>
                                <input type="text" name="title" required placeholder="Ex: Nouveau service !" style="width:100%; padding:14px 16px; border:2px solid #E2E8F0; border-radius:12px; font-size:15px; transition:all 0.2s;">
                            </div>
                            
                            <div style="grid-column:span 2;">
                                <label style="display:block; font-weight:800; margin-bottom:8px; color:#1E293B;">Description</label>
                                <textarea name="description" placeholder="Décrivez votre offre..." style="width:100%; padding:14px 16px; border:2px solid #E2E8F0; border-radius:12px; font-size:15px; min-height:100px; resize:vertical;"></textarea>
                            </div>
                            
                            <div>
                                <label style="display:block; font-weight:800; margin-bottom:8px; color:#1E293B;">Type de campagne</label>
                                <select name="ad_type" style="width:100%; padding:14px 16px; border:2px solid #E2E8F0; border-radius:12px; font-size:15px; background:white;">
                                    <option value="sponsored">📢 Sponsorisée</option>
                                    <option value="promotion">🎉 Promotion</option>
                                    <option value="event">📅 Événement</option>
                                </select>
                            </div>
                            
                            <div>
                                <label style="display:block; font-weight:800; margin-bottom:8px; color:#1E293B;">Lien (optionnel)</label>
                                <input type="url" name="link" placeholder="https://..." style="width:100%; padding:14px 16px; border:2px solid #E2E8F0; border-radius:12px; font-size:15px;">
                            </div>
                            
                            <div style="grid-column:span 2;">
                                <label style="display:block; font-weight:800; margin-bottom:8px; color:#1E293B;">Image</label>
                                <div class="file-upload-premium" id="adImageUpload" style="border:2px dashed #CBD5E1; border-radius:16px; padding:40px; text-align:center; cursor:pointer; transition:all 0.2s;">
                                    <i data-lucide="image" style="width:48px; height:48px; color:#94A3B8; margin-bottom:12px;"></i>
                                    <div style="font-weight:700; color:#475569;">Cliquez pour télécharger ou glissez-déposez</div>
                                    <div style="font-size:13px; color:#94A3B8; margin-top:4px;">PNG, JPG ou GIF (max 5MB)</div>
                                    <input type="file" name="ad_image" accept="image/*" id="adImageInput" style="display:none;">
                                </div>
                                <div id="adImagePreview" style="display:none; margin-top:12px;">
                                    <img id="adImagePreviewImg" style="width:100%; max-height:300px; object-fit:cover; border-radius:12px;">
                                </div>
                            </div>
                            
                            <div>
                                <label style="display:block; font-weight:800; margin-bottom:8px; color:#1E293B;">Date de début</label>
                                <input type="datetime-local" name="start_date" required style="width:100%; padding:14px 16px; border:2px solid #E2E8F0; border-radius:12px; font-size:15px;">
                            </div>
                            
                            <div>
                                <label style="display:block; font-weight:800; margin-bottom:8px; color:#1E293B;">Date de fin</label>
                                <input type="datetime-local" name="end_date" required style="width:100%; padding:14px 16px; border:2px solid #E2E8F0; border-radius:12px; font-size:15px;">
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="modal-premium-footer" style="padding:20px 30px; border-top:1px solid #E2E8F0; display:flex; gap:12px;">
                    <button type="button" class="btn btn-outline" id="cancelAdBtn" style="flex:1; border-radius:12px; padding:14px; font-weight:700;">Annuler</button>
                    <button type="submit" form="createAdForm" class="btn btn-register" style="flex:1; border-radius:12px; padding:14px; font-weight:700;">
                        <i data-lucide="rocket" style="width:18px; height:18px;"></i>
                        Lancer la campagne
                    </button>
                </div>
            </div>
        </div>

        <!-- Liste des publicités -->
        <div class="card" style="padding:0;">
            <div style="padding:20px 24px; border-bottom:1px solid #E2E8F0; display:flex; justify-content:space-between; align-items:center;">
                <h2 style="font-weight:800; font-size:18px;">Mes campagnes</h2>
                <div style="display:flex; gap:10px;">
                    <select style="padding:10px 14px; border:1px solid #E2E8F0; border-radius:10px; font-size:14px;">
                        <option>Toutes</option>
                        <option>Actives</option>
                        <option>Terminées</option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($my_ads)): ?>
                <div style="text-align:center; padding:80px 20px;">
                    <div style="background:#F8FAFC; width:100px; height:100px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                        <i data-lucide="megaphone" style="width:50px; height:50px; color:#94A3B8;"></i>
                    </div>
                    <h3 style="font-weight:800; font-size:20px; margin-bottom:8px;">Aucune campagne pour le moment</h3>
                    <p style="color:#64748B; max-width:400px; margin:0 auto 24px;">Lancez votre première publicité pour augmenter votre visibilité sur Tekrin !</p>
                    <button class="btn btn-register" id="createFirstAdBtn" style="border-radius:12px; padding:12px 24px; font-weight:700;">
                        <i data-lucide="plus"></i>
                        Créer ma première publicité
                    </button>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="background:#F8FAFC;">
                                <th style="text-align:left; padding:16px 24px; font-weight:800; color:#64748B; font-size:13px; text-transform:uppercase;">Campagne</th>
                                <th style="text-align:left; padding:16px 24px; font-weight:800; color:#64748B; font-size:13px; text-transform:uppercase;">Type</th>
                                <th style="text-align:left; padding:16px 24px; font-weight:800; color:#64748B; font-size:13px; text-transform:uppercase;">Période</th>
                                <th style="text-align:center; padding:16px 24px; font-weight:800; color:#64748B; font-size:13px; text-transform:uppercase;">Vues</th>
                                <th style="text-align:center; padding:16px 24px; font-weight:800; color:#64748B; font-size:13px; text-transform:uppercase;">Clics</th>
                                <th style="text-align:center; padding:16px 24px; font-weight:800; color:#64748B; font-size:13px; text-transform:uppercase;">CTR</th>
                                <th style="text-align:center; padding:16px 24px; font-weight:800; color:#64748B; font-size:13px; text-transform:uppercase;">Statut</th>
                                <th style="text-align:right; padding:16px 24px; font-weight:800; color:#64748B; font-size:13px; text-transform:uppercase;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_ads as $ad): 
                                $ad_ctr = $ad['views'] > 0 ? round(($ad['clicks'] / $ad['views']) * 100, 2) : 0;
                                $is_active = $ad['is_active'] && strtotime($ad['start_date']) <= time() && strtotime($ad['end_date']) >= time();
                            ?>
                                <tr style="border-bottom:1px solid #F1F5F9;">
                                    <td style="padding:20px 24px;">
                                        <div style="display:flex; align-items:center; gap:14px;">
                                            <?php if ($ad['image_url']): ?>
                                                <img src="<?php echo htmlspecialchars($ad['image_url']); ?>" style="width:56px; height:56px; border-radius:12px; object-fit:cover;">
                                            <?php else: ?>
                                                <div style="width:56px; height:56px; border-radius:12px; background:#F1F5F9; display:flex; align-items:center; justify-content:center;">
                                                    <i data-lucide="image" style="width:24px; height:24px; color:#94A3B8;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div style="font-weight:800; color:#1E293B; margin-bottom:2px;"><?php echo htmlspecialchars($ad['title']); ?></div>
                                                <div style="font-size:13px; color:#64748B;"><?php echo htmlspecialchars(substr($ad['description'] ?? '', 0, 40)); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:20px 24px;">
                                        <span style="display:inline-flex; align-items:center; gap:6px; background:#EFF6FF; color:#1565C0; padding:6px 12px; border-radius:8px; font-weight:700; font-size:13px;">
                                            <?php 
                                            switch($ad['ad_type']) {
                                                case 'sponsored': echo '📢 Sponsorisé'; break;
                                                case 'promotion': echo '🎉 Promotion'; break;
                                                case 'event': echo '📅 Événement'; break;
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td style="padding:20px 24px; font-size:14px; color:#475569;">
                                        <div style="font-weight:700;"><?php echo date('d/m/Y', strtotime($ad['start_date'])); ?></div>
                                        <div style="color:#94A3B8; font-size:13px;">→ <?php echo date('d/m/Y', strtotime($ad['end_date'])); ?></div>
                                    </td>
                                    <td style="padding:20px 24px; text-align:center; font-weight:800; color:#1E293B;"><?php echo number_format($ad['views']); ?></td>
                                    <td style="padding:20px 24px; text-align:center; font-weight:800; color:#10B981;"><?php echo number_format($ad['clicks']); ?></td>
                                    <td style="padding:20px 24px; text-align:center;">
                                        <span style="background:<?php echo $ad_ctr >= 2 ? '#ECFDF5' : '#FFFBEB'; ?>; color:<?php echo $ad_ctr >= 2 ? '#10B981' : '#D97706'; ?>; padding:6px 12px; border-radius:8px; font-weight:800; font-size:13px;"><?php echo $ad_ctr; ?>%</span>
                                    </td>
                                    <td style="padding:20px 24px; text-align:center;">
                                        <?php if ($is_active): ?>
                                            <span style="background:#ECFDF5; color:#10B981; padding:6px 12px; border-radius:8px; font-weight:800; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                                                <span style="width:8px; height:8px; background:#10B981; border-radius:50%; display:inline-block;"></span>
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span style="background:#F1F5F9; color:#64748B; padding:6px 12px; border-radius:8px; font-weight:800; font-size:13px; display:inline-flex; align-items:center; gap:6px;">
                                                <span style="width:8px; height:8px; background:#94A3B8; border-radius:50%; display:inline-block;"></span>
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:20px 24px; text-align:right;">
                                        <div style="display:flex; justify-content:flex-end; gap:8px;">
                                            <button class="btn-action-outline" title="Dupliquer" style="background:#F8FAFC; border:none; padding:10px; border-radius:10px; cursor:pointer; color:#64748B; transition:all 0.2s;">
                                                <i data-lucide="copy"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette campagne ?')">
                                                <input type="hidden" name="action" value="delete_ad">
                                                <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                                <button type="submit" class="btn-action-outline" title="Supprimer" style="background:#FEF2F2; border:none; padding:10px; border-radius:10px; cursor:pointer; color:#EF4444; transition:all 0.2s;">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.modal-premium.active {
    display: flex !important;
    animation: fadeIn 0.2s ease;
}
.file-upload-premium:hover {
    border-color: var(--primary-blue);
    background: #EFF6FF;
}
.btn-action-outline:hover {
    transform: translateY(-2px);
}
input:focus, textarea:focus, select:focus {
    outline:none;
    border-color:var(--primary-blue);
    box-shadow:0 0 0 4px rgba(21, 101, 192, 0.1);
}
@keyframes fadeIn {
    from { opacity:0; }
    to { opacity:1; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    
    const createAdBtn = document.getElementById('createAdBtn');
    const createFirstAdBtn = document.getElementById('createFirstAdBtn');
    const createAdModal = document.getElementById('createAdModal');
    const closeAdModal = document.getElementById('closeAdModal');
    const cancelAdBtn = document.getElementById('cancelAdBtn');
    const adImageUpload = document.getElementById('adImageUpload');
    const adImageInput = document.getElementById('adImageInput');
    const adImagePreview = document.getElementById('adImagePreview');
    const adImagePreviewImg = document.getElementById('adImagePreviewImg');
    
    const openModal = () => {
        createAdModal.style.display = 'flex';
        createAdModal.classList.add('active');
    };
    
    const closeModal = () => {
        createAdModal.style.display = 'none';
        createAdModal.classList.remove('active');
    };
    
    createAdBtn?.addEventListener('click', openModal);
    createFirstAdBtn?.addEventListener('click', openModal);
    closeAdModal?.addEventListener('click', closeModal);
    cancelAdBtn?.addEventListener('click', closeModal);
    
    createAdModal?.addEventListener('click', (e) => {
        if (e.target === createAdModal) closeModal();
    });
    
    adImageUpload?.addEventListener('click', () => adImageInput.click());
    adImageInput?.addEventListener('change', (e) => {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                adImagePreviewImg.src = e.target.result;
                adImagePreview.style.display = 'block';
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>