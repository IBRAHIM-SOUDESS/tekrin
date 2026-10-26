<?php
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Bloquer l'accès si l'onboarding n'est pas fini
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
    redirect('profile.php');
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch portfolio images
$stmt_portfolio = $pdo->prepare("SELECT * FROM portfolio WHERE user_id = ? ORDER BY created_at DESC");
$stmt_portfolio->execute([$user_id]);
$portfolio = $stmt_portfolio->fetchAll();

// Fetch user posts
$stmt_posts = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt_posts->execute([$user_id]);
$user_posts = $stmt_posts->fetchAll();

// Fetch user ads
$stmt_ads = $pdo->prepare("SELECT * FROM ads WHERE user_id = ? ORDER BY created_at DESC");
$stmt_ads->execute([$user_id]);
$user_ads = $stmt_ads->fetchAll();

require_once 'includes/header.php';
?>

<style>
.main-content {
    padding-top: 0 !important;
}
</style>
<div class="app-layout">
    <div class="container">
        <div class="layout-wrapper">
            
            <main class="main-content">
                <!-- Profile Header -->
                <div class="profile-header-card">
                    <div class="cover-photo" id="cover-container">
                        <?php 
                        $cover = get_user_cover($user);
                        if ($cover): 
                        ?>
                            <img src="<?php echo $cover; ?>" alt="Couverture" id="cover-img">
                        <?php else: ?>
                            <div class="cover-placeholder" id="cover-img"></div>
                        <?php endif; ?>
                        <label for="quick-cover-upload" class="quick-camera-btn cover-camera">
                            <i data-lucide="camera"></i>
                            <input type="file" id="quick-cover-upload" accept="image/*" hidden onchange="quickUpload(this, 'upload_cover')">
                        </label>
                    </div>
                    <div class="header-body">
                        <div class="profile-meta">
                            <div class="avatar-container">
                                <div class="avatar-large" id="avatar-container">
                                    <?php 
                                    $avatar = get_user_avatar($user);
                                    if ($avatar): 
                                    ?>
                                        <img src="<?php echo $avatar; ?>" alt="Profil" id="avatar-img">
                                    <?php else: ?>
                                        <div class="avatar-initials-large" id="avatar-img"><?php echo strtoupper(substr($user['fullname'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                    <label for="quick-avatar-upload" class="quick-camera-btn avatar-camera">
                                        <i data-lucide="camera"></i>
                                        <input type="file" id="quick-avatar-upload" accept="image/*" hidden onchange="quickUpload(this, 'upload_avatar')">
                                    </label>
                                </div>
                            </div>
                            <div class="user-details">
                                <div class="name-row">
                                    <h1><?php echo htmlspecialchars($user['fullname']); ?></h1>
                                    <span class="verification-badge" title="Vérifié"><i data-lucide="check-circle"></i></span>
                                </div>
                                <p class="profession-text"><?php echo htmlspecialchars($user['profession'] ?? 'Profession non définie'); ?></p>
                                <div class="meta-info">
                                    <span class="info-item"><i data-lucide="map-pin"></i> <?php echo htmlspecialchars(($user['city'] ?? 'Ville non définie') . ', ' . ($user['region'] ?? '')); ?></span>
                                    <span class="info-item"><i data-lucide="star"></i> 4.8 (24 avis)</span>
                                </div>
                            </div>
                            <div class="header-actions">
                                <a href="pro.php?id=<?php echo $user_id; ?>" class="btn btn-outline" target="_blank" style="border-color: var(--primary-blue); color: var(--primary-blue);">
                                    <i data-lucide="eye"></i>
                                    <span>Voir profil public</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Tabs Navigation -->
                <div class="profile-tabs">
                    <button class="tab-btn active" data-tab="about">À propos</button>
                    <button class="tab-btn" data-tab="publications">Publications</button>
                    <button class="tab-btn" data-tab="services">Services</button>
                    <button class="tab-btn" data-tab="publicites">Publicités</button>
                    <button class="tab-btn" data-tab="reviews">Avis</button>
                    <button class="tab-btn" data-tab="photos">Photos</button>
                </div>

                <div class="tab-content-wrapper">
                    <!-- Left Column -->
                    <div class="tab-main-column">
                        <!-- About Tab -->
                        <div class="tab-pane active" id="about-pane">
                            <div class="card profile-card">
                                <h3>Description professionnelle</h3>
                                <p class="bio-text">
                                    <?php echo nl2br(htmlspecialchars($user['bio'] ?? 'Aucune description professionnelle renseignée pour le moment.')); ?>
                                </p>
                                <div class="contact-quick-info">
                                    <div class="info-row">
                                        <i data-lucide="calendar"></i>
                                        <span>Membre depuis <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <i data-lucide="mail"></i>
                                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <i data-lucide="phone"></i>
                                        <span><?php echo htmlspecialchars($user['phone_number'] ?? 'Non renseigné'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Publications Tab -->
                        <div class="tab-pane" id="publications-pane">
                            <?php if (empty($user_posts)): ?>
                                <div class="card" style="text-align: center; padding: 40px;">
                                    <p style="color: var(--text-muted);">Vous n'avez pas encore publié de contenu.</p>
                                    <a href="feed.php" class="btn btn-register" style="margin-top: 15px; display: inline-block;">Créer une publication</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($user_posts as $post): ?>
                                    <div class="card publication-card" style="margin-bottom: 20px;">
                                        <div class="pub-header" style="display: flex; gap: 12px; margin-bottom: 15px;">
                                            <div class="pub-avatar" style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden;">
                                                <?php if ($avatar): ?>
                                                    <img src="<?php echo $avatar; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <div style="width: 100%; height: 100%; background: var(--primary-blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;"><?php echo strtoupper(substr($user['fullname'], 0, 1)); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <h4 style="font-size: 15px; font-weight: 700; margin: 0;"><?php echo htmlspecialchars($user['fullname']); ?></h4>
                                                <p style="font-size: 12px; color: var(--text-muted); margin: 0;">
                                                    <?php 
                                                    $date = new DateTime($post['created_at']);
                                                    echo $date->format('d M Y à H:i');
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                        <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 15px; white-space: pre-wrap;">
                                            <?php echo htmlspecialchars($post['content']); ?>
                                        </p>
                                        <?php if (isset($post['image_path']) && !empty($post['image_path'])): ?>
                                            <div class="pub-image" style="border-radius: 12px; overflow: hidden; margin-bottom: 15px;">
                                                <img src="assets/uploads/posts/<?php echo $post['image_path']; ?>" style="width: 100%; max-height: 400px; object-fit: cover;">
                                            </div>
                                        <?php endif; ?>
                                        <div class="pub-footer" style="display: flex; gap: 20px; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                                            <span style="font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;"><i data-lucide="heart" style="width: 18px;"></i> 0</span>
                                            <span style="font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;"><i data-lucide="message-circle" style="width: 18px;"></i> 0</span>
                                            <span style="font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;"><i data-lucide="share-2" style="width: 18px;"></i> Partager</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Services Tab -->
                        <div class="tab-pane" id="services-pane">
                            <div class="card services-detail-card">
                                <h3>Détail des services</h3>
                                <div class="services-list-detail">
                                    <?php 
                                    $services = explode(',', $user['services'] ?? '');
                                    foreach ($services as $s): 
                                        if (empty(trim($s))) continue;
                                    ?>
                                        <div class="service-detail-item">
                                            <div class="service-icon"><i data-lucide="check"></i></div>
                                            <div class="service-info">
                                                <h4><?php echo htmlspecialchars(trim($s)); ?></h4>
                                                <p>Prestation professionnelle avec garantie de qualité. Prix variable selon la complexité.</p>
                                                <span class="price-tag">À partir de 15 000 FCFA</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Publicités Tab -->
                        <div class="tab-pane" id="publicites-pane">
                            <div class="card">
                                <div class="card-header-flex">
                                    <h3>Mes publicités</h3>
                                    <button id="createAdBtnProfile" class="btn btn-register" style="padding: 10px 20px;">
                                        <i data-lucide="plus" style="width:18px; height:18px;"></i> Créer une publicité
                                    </button>
                                </div>

                                <?php if (empty($user_ads)): ?>
                                    <div class="empty-state" style="text-align: center; padding:40px;">
                                        <i data-lucide="megaphone" style="width:64px; height:64px; color:#94A3B8; margin-bottom:16px;"></i>
                                        <p>Vous n'avez pas encore de publicité active.</p>
                                        <button id="createFirstAdBtnProfile" class="btn btn-register" style="margin-top:16px; display:inline-block;">Commencer</button>
                                    </div>
                                <?php else: ?>
                                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap:20px;">
                                        <?php foreach ($user_ads as $ad): 
                                            $is_active = (isset($ad['is_active']) && $ad['is_active'] && (isset($ad['start_date']) && time() >= strtotime($ad['start_date'])) && (isset($ad['end_date']) && time() <= strtotime($ad['end_date'])));
                                            $views = $ad['views'] ?? 0;
                                            $clicks = $ad['clicks'] ?? 0;
                                            $ctr = $views > 0 ? round(($clicks / $views) *100,2) : 0;
                                        ?>
                                            <div class="card" style="padding:0; overflow:hidden; border:2px solid <?php echo $is_active ? '#10B981' : '#E2E8F0'; ?>;">
                                                <?php if (isset($ad['image_url']) && !empty($ad['image_url'])): ?>
                                                    <div style="height:180px; overflow:hidden;">
                                                        <img src="<?php echo htmlspecialchars($ad['image_url']); ?>" style="width:100%; height:100%; object-fit:cover;" alt="">
                                                    </div>
                                                <?php endif; ?>
                                                <div style="padding:20px;">
                                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                                                        <h4 style="margin:0; font-weight:800; font-size:16px;"><?php echo htmlspecialchars($ad['title'] ?? 'Sans titre'); ?></h4>
                                                        <span style="padding:6px 12px; border-radius:8px; font-weight:700; font-size:12px; background:<?php echo $is_active ? '#ECFDF5' : '#F1F5F9'; ?>; color:<?php echo $is_active ? '#10B981' : '#64748B'; ?>;">
                                                            <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </div>
                                                    <p style="color:#64748B; margin:0 0 16px; font-size:13px; line-height:1.6;">
                                                        <?php echo htmlspecialchars(substr($ad['description'] ?? '', 0, 80)); ?>
                                                    </p>
                                                    <div style="display:flex; gap:16px; margin-bottom:16px;">
                                                        <div style="text-align:center;">
                                                            <div style="font-weight:800; color:#1E293B;"><?php echo number_format($views); ?></div>
                                                            <div style="font-size:12px; color:#94A3B8;">Vues</div>
                                                        </div>
                                                        <div style="text-align:center;">
                                                            <div style="font-weight:800; color:#1E293B;"><?php echo number_format($clicks); ?></div>
                                                            <div style="font-size:12px; color:#94A3B8;">Clics</div>
                                                        </div>
                                                        <div style="text-align:center;">
                                                            <div style="font-weight:800; color:#1E293B;"><?php echo $ctr; ?>%</div>
                                                            <div style="font-size:12px; color:#94A3B8;">Taux</div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Reviews Tab -->
                        <div class="tab-pane" id="reviews-pane">
                            <div class="reviews-summary-card">
                                <div class="rating-big">4.8</div>
                                <div class="rating-meta">
                                    <div class="stars">★★★★★</div>
                                    <span>Basé sur 24 avis clients</span>
                                </div>
                            </div>
                            <div class="empty-state">
                                <p>Le système d'avis sera bientôt disponible.</p>
                            </div>
                        </div>

                        <!-- Photos Tab -->
                        <div class="tab-pane" id="photos-pane">
                            <div class="card photos-card">
                                <div class="card-header-flex">
                                    <h3>Portfolio de réalisations</h3>
                                    <a href="portfolio.php" class="btn btn-outline" style="padding: 8px 16px;">
                                        <i data-lucide="plus" style="width:16px; height:16px;"></i> Ajouter une réalisation
                                    </a>
                                </div>
                                <div class="portfolio-grid-view">
                                    <?php if (empty($portfolio)): ?>
                                        <div class="empty-state" style="text-align:center; padding:40px; grid-column:1/-1;">
                                            <i data-lucide="images" style="width:64px; height:64px; color:#94A3B8; margin-bottom:16px;"></i>
                                            <p class="empty-text">Aucune photo dans le portfolio.</p>
                                            <a href="portfolio.php" class="btn btn-register" style="margin-top:12px;">Ajouter ma première réalisation</a>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($portfolio as $img): 
                                            $p_path = $img['image_path'] ?? '';
                                            // Si le chemin ne commence pas par assets/, on assume l'ancien dossier par défaut
                                            if ($p_path && strpos($p_path, 'assets/') !== 0) {
                                                $p_path = 'assets/uploads/portfolio/' . $p_path;
                                            }
                                            if ($p_path):
                                        ?>
                                            <div class="portfolio-grid-item" onclick="openLightbox('<?php echo $p_path; ?>')">
                                                <img src="<?php echo $p_path; ?>" alt="Réalisation">
                                                <?php if (isset($img['title']) && !empty($img['title'])): ?>
                                                    <div style="position:absolute; bottom:0; left:0; right:0; padding:10px; background:linear-gradient(transparent, rgba(0,0,0,0.7)); color:white; font-size:13px;">
                                                        <?php echo htmlspecialchars($img['title']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="tab-side-column">
                        <div class="card side-services-card">
                            <div class="card-header-flex">
                                <h3>Mes services</h3>
                                <a href="#" class="view-all">Voir tout</a>
                            </div>
                            <div class="side-services-list">
                                <?php 
                                $side_services = array_slice($services, 0, 3);
                                foreach ($side_services as $s): 
                                    if (empty(trim($s))) continue;
                                ?>
                                    <div class="side-service-item">
                                        <span class="service-name"><?php echo htmlspecialchars(trim($s)); ?></span>
                                        <span class="service-price">Dès 15k FCFA</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="btn btn-primary btn-block" style="margin-top: 20px; width: 100%;">Réserver un service</button>
                        </div>
                    </div>
                </div>
            </main>
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
                            <option value="sponsored">📢 Sponsorisé</option>
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

<style>
.layout-wrapper { grid-template-columns: 1fr; }
.main-content { max-width: 100% !important; }
.profile-header-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #E2E8F0; }
.cover-photo { height: 280px; background: var(--grad-primary); position: relative; }
.cover-photo img { width: 100%; height: 100%; object-fit: cover; }
.cover-placeholder { width: 100%; height: 100%; background: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%); opacity: 0.8; }
.header-body { padding: 0 30px 30px; position: relative; }
.profile-meta { display: flex; gap: 24px; margin-top: -90px; }
.avatar-container { position: relative; }
.avatar-large { width: 200px; height: 200px; border-radius: 50%; border: 6px solid white; background: #F1F5F9; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); position: relative; }
.avatar-large img { width: 100%; height: 100%; object-fit: cover; }
.avatar-initials-large { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 88px; font-weight: 800; color: var(--primary-blue); }
.user-details { flex: 1; padding-top: 95px; }
.name-row { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
.name-row h1 { font-size: 28px; font-weight: 800; color: #1E293B; }
.verification-badge { color: var(--primary-blue); }
.profession-text { font-size: 18px; color: #64748B; margin-bottom: 12px; }
.meta-info { display: flex; gap: 20px; }
.info-item { display: flex; align-items: center; gap: 6px; font-size: 14px; color: #94A3B8; }
.header-actions { padding-top: 95px; }
.profile-tabs { display: flex; gap: 30px; border-bottom: 1px solid #E2E8F0; padding: 0 10px; }
.tab-btn { background: none; border: none; padding: 16px 4px; font-size: 15px; font-weight: 600; color: #64748B; cursor: pointer; position: relative; transition: all 0.2s; }
.tab-btn:hover { color: var(--primary-blue); }
.tab-btn.active { color: var(--primary-blue); }
.tab-btn.active::after { content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 3px; background: var(--primary-blue); border-radius: 3px 3px 0 0; }
.tab-content-wrapper { display: grid; grid-template-columns: 1fr 300px; gap: 24px; }
.card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); border: 1px solid #E2E8F0; }
.card h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: #1E293B; }
.tab-pane { display: none; }
.tab-pane.active { display: block; animation: fadeIn 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.bio-text { line-height: 1.8; color: #475569; margin-bottom: 24px; }
.contact-quick-info { display: flex; flex-direction: column; gap: 12px; }
.info-row { display: flex; align-items: center; gap: 12px; color: #64748B; font-size: 14px; }
.info-row i { width: 18px; height: 18px; color: #94A3B8; }
.card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.view-all { font-size: 13px; color: var(--primary-blue); font-weight: 600; text-decoration: none; }
.side-services-list { display: flex; flex-direction: column; gap: 12px; }
.side-service-item { display: flex; justify-content: space-between; padding: 12px; background: #F8FAFC; border-radius: 10px; font-size: 14px; }
.service-name { font-weight: 600; color: #334155; }
.service-price { color: var(--primary-blue); font-weight: 700; }
.portfolio-grid-view { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; }
.portfolio-grid-item { aspect-ratio: 1; border-radius: 12px; overflow: hidden; cursor: pointer; border: 1px solid #E2E8F0; transition: transform 0.2s; }
.portfolio-grid-item img { width: 100%; height: 100%; object-fit: cover; }
.quick-camera-btn { position: absolute; background: white; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.15); border: 2px solid white; color: var(--primary-blue); transition: all 0.2s ease; z-index: 10; }
.avatar-camera { bottom: 5px; right: 5px; }
.cover-camera { bottom: 20px; right: 20px; }
.uploading { opacity: 0.5; pointer-events: none; }
.service-detail-item { display: flex; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
.service-icon { color: var(--primary-blue); margin-top: 4px; }
.price-tag { display: inline-block; background: #E0E7FF; color: var(--primary-blue); padding: 4px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; margin-top: 10px; }
@media (max-width: 992px) { .tab-content-wrapper { grid-template-columns: 1fr; } .profile-meta { flex-direction: column; align-items: center; text-align: center; margin-top: -100px; } .user-details { padding-top: 15px; } .header-actions { padding-top: 15px; margin-bottom: 20px; } .name-row { justify-content: center; } .meta-info { justify-content: center; } }
.modal-premium.active {
    display: flex !important;
    animation: fadeIn 0.2s ease;
}
.file-upload-premium:hover {
    border-color: var(--primary-blue);
    background: #EFF6FF;
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
    const tabs = document.querySelectorAll('.tab-btn');
    const panes = document.querySelectorAll('.tab-pane');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            panes.forEach(p => p.classList.remove('active'));
            document.getElementById(`${target}-pane`).classList.add('active');
        });
    });

    // Ad Modal Logic
    const createAdBtn = document.getElementById('createAdBtnProfile');
    const createFirstAdBtn = document.getElementById('createFirstAdBtnProfile');
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

    lucide.createIcons();
});

async function quickUpload(input, action) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const formData = new FormData();
    formData.append('action', action);
    formData.append('photo', file);

    const containerId = action === 'upload_avatar' ? 'avatar-container' : 'cover-container';
    const imgId = action === 'upload_avatar' ? 'avatar-img' : 'cover-img';
    const container = document.getElementById(containerId);
    container.classList.add('uploading');

    try {
        const response = await fetch('handle_profile_action.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            const imgElement = document.getElementById(imgId);
            if (imgElement.tagName === 'IMG') {
                imgElement.src = data.path + '?v=' + Date.now();
            } else {
                const newImg = document.createElement('img');
                newImg.id = imgId;
                newImg.src = data.path;
                newImg.style.width = '100%';
                newImg.style.height = '100%';
                newImg.style.objectFit = 'cover';
                imgElement.parentNode.replaceChild(newImg, imgElement);
            }
        } else {
            alert('Erreur: ' + data.message);
        }
    } catch (error) {
        console.error('Upload error:', error);
        alert('Une erreur est survenue.');
    } finally {
        container.classList.remove('uploading');
    }
}

function openLightbox(src) { window.open(src, '_blank'); }
</script>
<?php require_once 'includes/footer.php'; ?>
