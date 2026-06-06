<?php
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch portfolio images
$stmt_portfolio = $pdo->prepare("SELECT * FROM portfolio WHERE user_id = ? ORDER BY created_at DESC");
$stmt_portfolio->execute([$user_id]);
$portfolio = $stmt_portfolio->fetchAll();

require_once 'includes/header.php';
?>

<div class="app-layout">
    <div class="container">
        <div class="layout-wrapper">
            
            <main class="main-content">
                <!-- Profile Header -->
                <div class="profile-header-card">
                    <div class="cover-photo" id="cover-container">
                        <?php if (isset($user['cover_photo']) && $user['cover_photo']): ?>
                            <img src="<?php echo $user['cover_photo']; ?>" alt="Couverture" id="cover-img">
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
                                    $avatar = $user['profile_picture'] ?? '';
                                    if ($avatar && strpos($avatar, 'assets/') !== 0) $avatar = 'assets/uploads/profiles/' . $avatar;
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
                                <a href="profile_edit.php" class="btn btn-outline">
                                    <i data-lucide="edit-3"></i>
                                    <span>Modifier le profil</span>
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
                                        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Il y a 2 jours</p>
                                    </div>
                                </div>
                                <p style="font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 15px;">
                                    Nouvelle installation terminée aujourd'hui chez un client. Très satisfait du résultat final ! N'hésitez pas à me contacter pour vos projets de rénovation électrique.
                                </p>
                                <?php if (!empty($portfolio)): ?>
                                    <div class="pub-image" style="border-radius: 12px; overflow: hidden; margin-bottom: 15px;">
                                        <?php 
                                        $first_img = $portfolio[0]['image_path'];
                                        if (strpos($first_img, 'assets/') !== 0) $first_img = 'assets/uploads/portfolio/' . $first_img;
                                        ?>
                                        <img src="<?php echo $first_img; ?>" style="width: 100%; max-height: 400px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                <div class="pub-footer" style="display: flex; gap: 20px; border-top: 1px solid #f1f5f9; padding-top: 15px;">
                                    <span style="font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;"><i data-lucide="heart" style="width: 18px;"></i> 24</span>
                                    <span style="font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;"><i data-lucide="message-circle" style="width: 18px;"></i> 8</span>
                                    <span style="font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;"><i data-lucide="share-2" style="width: 18px;"></i> Partager</span>
                                </div>
                            </div>
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
                                <h3>Portfolio de réalisations</h3>
                                <div class="portfolio-grid-view">
                                    <?php if (empty($portfolio)): ?>
                                        <p class="empty-text">Aucune photo dans le portfolio.</p>
                                    <?php else: ?>
                                        <?php foreach ($portfolio as $img): 
                                            $p_path = $img['image_path'];
                                            if (strpos($p_path, 'assets/') !== 0) $p_path = 'assets/uploads/portfolio/' . $p_path;
                                        ?>
                                            <div class="portfolio-grid-item" onclick="openLightbox('<?php echo $p_path; ?>')">
                                                <img src="<?php echo $p_path; ?>" alt="Réalisation">
                                            </div>
                                        <?php endforeach; ?>
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

<style>
.profile-header-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #E2E8F0; }
.cover-photo { height: 200px; background: var(--grad-primary); position: relative; }
.cover-photo img { width: 100%; height: 100%; object-fit: cover; }
.cover-placeholder { width: 100%; height: 100%; background: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%); opacity: 0.8; }
.header-body { padding: 0 30px 30px; position: relative; }
.profile-meta { display: flex; gap: 24px; margin-top: -60px; }
.avatar-container { position: relative; }
.avatar-large { width: 150px; height: 150px; border-radius: 50%; border: 6px solid white; background: #F1F5F9; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); position: relative; }
.avatar-large img { width: 100%; height: 100%; object-fit: cover; }
.avatar-initials-large { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 64px; font-weight: 800; color: var(--primary-blue); }
.user-details { flex: 1; padding-top: 70px; }
.name-row { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
.name-row h1 { font-size: 28px; font-weight: 800; color: #1E293B; }
.verification-badge { color: var(--primary-blue); }
.profession-text { font-size: 18px; color: #64748B; margin-bottom: 12px; }
.meta-info { display: flex; gap: 20px; }
.info-item { display: flex; align-items: center; gap: 6px; font-size: 14px; color: #94A3B8; }
.header-actions { padding-top: 70px; }
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
@media (max-width: 992px) { .tab-content-wrapper { grid-template-columns: 1fr; } .profile-meta { flex-direction: column; align-items: center; text-align: center; margin-top: -75px; } .user-details { padding-top: 10px; } .header-actions { padding-top: 10px; margin-bottom: 20px; } .name-row { justify-content: center; } .meta-info { justify-content: center; } }
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
