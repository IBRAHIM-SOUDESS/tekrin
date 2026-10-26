<?php
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Bloquer l'accès si l'onboarding n'est pas fini
requireOnboarding();

$errors = [];
$success = false;
$user_id = $_SESSION['user_id'];
$current_section = $_GET['section'] ?? null;

// Récupérer les données actuelles de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Définition des cartes du Hub pour une maintenance facile
$settings_cards = [
    [
        'id' => 'personal',
        'title' => 'Informations personnelles',
        'desc' => 'Gérez vos coordonnées, ville et identité.',
        'icon' => 'user',
        'color' => '#1565C0'
    ],
    [
        'id' => 'pro',
        'title' => 'Profil professionnel',
        'desc' => 'Bio, profession et informations d\'entreprise.',
        'icon' => 'briefcase',
        'color' => '#6A1B9A'
    ],
    [
        'id' => 'services',
        'title' => 'Mes services',
        'desc' => 'Listez les services que vous offrez aux clients.',
        'icon' => 'layers',
        'color' => '#43A047'
    ],
    [
        'id' => 'portfolio',
        'title' => 'Mes réalisations',
        'desc' => 'Gérez vos photos de travaux et projets.',
        'icon' => 'image',
        'color' => '#FB8C00'
    ],
    [
        'id' => 'privacy',
        'title' => 'Confidentialité',
        'desc' => 'Visibilité du profil et gestion des données.',
        'icon' => 'lock',
        'color' => '#E53935'
    ],
    [
        'id' => 'notifications',
        'title' => 'Notifications',
        'desc' => 'Configurez vos alertes et préférences emails.',
        'icon' => 'bell',
        'color' => '#FDD835'
    ],
    [
        'id' => 'qrcode',
        'title' => 'Mon QR Code',
        'desc' => 'Téléchargez votre carte Tekrin professionnelle.',
        'icon' => 'qr-code',
        'color' => '#00ACC1'
    ],
    [
        'id' => 'security',
        'title' => 'Sécurité du compte',
        'desc' => 'Mot de passe et authentification.',
        'icon' => 'shield-check',
        'color' => '#546E7A'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname'] ?? '');
    $profession = sanitize($_POST['profession'] ?? '');
    $company_name = sanitize($_POST['company_name'] ?? '');
    $region = sanitize($_POST['region'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $phone_number = sanitize($_POST['phone_number'] ?? '');
    $whatsapp_number = sanitize($_POST['whatsapp_number'] ?? '');
    $bio = sanitize($_POST['bio'] ?? '');
    $services = sanitize($_POST['services'] ?? '');

    // Validation de base
    if (empty($fullname)) $errors[] = "Le nom complet est requis.";
    if (empty($profession)) $errors[] = "La profession est requise.";

    $current_section = $_GET['section'] ?? 'personal';
    
    if (empty($errors)) {
        try {
            if ($current_section === 'personal') {
                $stmt = $pdo->prepare("UPDATE users SET 
                    fullname = ?, phone_number = ?, region = ?, city = ?
                    WHERE id = ?");
                $stmt->execute([$fullname, $phone_number, $region, $city, $user_id]);
            } elseif ($current_section === 'pro') {
                $stmt = $pdo->prepare("UPDATE users SET 
                    profession = ?, company_name = ?, bio = ?, services = ?
                    WHERE id = ?");
                $stmt->execute([$profession, $company_name, $bio, $services, $user_id]);
            }
            
            if ($current_section === 'personal') {
                $_SESSION['user_fullname'] = $fullname;
            }
            setFlashMessage("Informations mises à jour avec succès !", "success");
            redirect("profile_edit.php?section=" . $current_section);
        } catch (PDOException $e) {
            $errors[] = "Erreur de mise à jour : " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="app-layout">
    <div class="container">
        <?php if (!$current_section): ?>
            <!-- Settings Hub View (Main Page) -->
            <div class="settings-hub">
                <div class="hub-header">
                    <h1>Paramètres du compte</h1>
                    <p>Gérez vos informations, votre profil professionnel et la sécurité de votre compte.</p>
                </div>

                <div class="hub-grid">
                    <?php foreach ($settings_cards as $card): ?>
                        <a href="?section=<?php echo $card['id']; ?>" class="hub-card">
                            <div class="hub-card-icon" style="background: <?php echo $card['color']; ?>15; color: <?php echo $card['color']; ?>;">
                                <i data-lucide="<?php echo $card['icon']; ?>"></i>
                            </div>
                            <div class="hub-card-content">
                                <h3><?php echo $card['title']; ?></h3>
                                <p><?php echo $card['desc']; ?></p>
                            </div>
                            <div class="hub-card-arrow">
                                <i data-lucide="chevron-right"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="hub-footer">
                    <div class="hub-help-card-horizontal">
                        <div class="help-text">
                            <h4>Besoin d'aide ?</h4>
                            <p>Notre équipe est là pour vous accompagner à chaque étape de votre expérience sur Tekrin.</p>
                        </div>
                        <button class="btn btn-primary">Accéder au centre d'aide</button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Specific Section View (Full Width) -->
            <div class="settings-section-container">
                <div class="section-top-nav">
                    <a href="profile_edit.php" class="btn-back-hub">
                        <i data-lucide="arrow-left"></i>
                        <span>Retour aux paramètres</span>
                    </a>
                </div>

                <main class="settings-full-content">
                    <?php if ($current_section === 'personal'): ?>
                        <div class="card settings-card premium-card">
                            <div class="settings-header">
                                <h2>Informations personnelles</h2>
                                <p>Gérez vos coordonnées et votre identité.</p>
                            </div>
                            <form action="profile_edit.php?section=personal" method="POST">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="fullname">Nom complet</label>
                                        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone_number">Téléphone</label>
                                        <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="region">Région</label>
                                        <select id="region" name="region" required onchange="updateCities()">
                                            <option value="">Sélectionnez une région</option>
                                            <?php
                                            $regions = ["Adamaoua", "Centre", "Est", "Extrême-Nord", "Littoral", "Nord", "Nord-Ouest", "Ouest", "Sud", "Sud-Ouest"];
                                            foreach ($regions as $r) {
                                                $selected = ($user['region'] == $r) ? 'selected' : '';
                                                echo "<option value=\"$r\" $selected>$r</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="city">Ville</label>
                                        <select id="city" name="city" required>
                                            <option value="">Sélectionnez une ville</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-register">Enregistrer les modifications</button>
                                </div>
                            </form>
                        </div>
                    <?php elseif ($current_section === 'pro'): ?>
                        <div class="card settings-card premium-card">
                            <div class="settings-header">
                                <h2>Profil professionnel</h2>
                                <p>Configurez votre vitrine pour attirer des clients.</p>
                            </div>
                            <form action="profile_edit.php?section=pro" method="POST">
                                <div class="form-group">
                                    <label for="profession">Profession / Métier</label>
                                    <input type="text" id="profession" name="profession" value="<?php echo htmlspecialchars($user['profession'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="company_name">Nom de l'entreprise (Optionnel)</label>
                                    <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="bio">Bio professionnelle</label>
                                    <textarea id="bio" name="bio" rows="5"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="services">Services (séparez par des virgules)</label>
                                    <input type="text" id="services" name="services" value="<?php echo htmlspecialchars($user['services'] ?? ''); ?>">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-register">Mettre à jour mon profil pro</button>
                                </div>
                            </form>
                        </div>
                    <?php elseif ($current_section === 'qrcode'): ?>
                    <!-- Page Header with Breadcrumb -->
                    <div class="settings-breadcrumb">
                        <span>Paramètres</span>
                        <i data-lucide="chevron-right"></i>
                        <span class="active">Mon QR Code</span>
                    </div>

                    <div class="settings-header-main">
                        <div class="header-icon-circle">
                            <i data-lucide="qr-code"></i>
                        </div>
                        <div class="header-text">
                            <h2>Mon QR Code</h2>
                            <p>Téléchargez et partagez votre QR Code pour développer votre visibilité et attirer plus de clients.</p>
                        </div>
                    </div>
                    
                    <div class="qrcode-premium-layout">
                        <!-- LEFT COLUMN: Card Preview -->
                        <div class="qrcode-column-left">
                            <div class="card premium-card preview-main-card">
                                <div class="card-header-flex">
                                    <h3>Aperçu de votre carte professionnelle</h3>
                                    <span class="badge-live">LIVE</span>
                                </div>
                                
                                <div class="pro-card-export-container">
                                    <!-- VERTICAL PROFESSIONAL CARD -->
                                    <div class="pro-card-preview-vertical" id="proCardToDownload">
                                        <!-- Decorative Shapes -->
                                        <div class="card-wave-decoration"></div>
                                        <div class="card-accent-shape-1"></div>
                                        <div class="card-accent-shape-2"></div>
                                        
                                        <div class="pro-card-vertical-inner">
                                            <!-- Top: Brand -->
                                            <div class="pro-card-brand-top">
                                                <div class="tekrin-logo-full">
                                                    <svg viewBox="0 0 100 100" width="32" height="32">
                                                        <path d="M 15 35 C 15 25, 25 20, 35 20 L 85 20 C 95 20, 95 35, 85 40 L 55 45 C 45 48, 40 55, 40 65 L 40 80 C 40 90, 25 90, 20 80 L 20 45 Z" fill="url(#vertical-card-grad)" />
                                                        <defs>
                                                            <linearGradient id="vertical-card-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                                <stop offset="0%" style="stop-color:#1565C0" />
                                                                <stop offset="100%" style="stop-color:#6A1B9A" />
                                                            </linearGradient>
                                                        </defs>
                                                    </svg>
                                                    <span>Tekrin</span>
                                                </div>
                                                <p class="brand-slogan">La plateforme qui connecte les professionnels et leurs clients.</p>
                                            </div>

                                            <!-- Center: User Info -->
                                            <div class="pro-card-user-center">
                                                <div class="pro-card-avatar-large">
                                                    <?php 
                                                    $avatar = get_user_avatar($user);
                                                    if ($avatar): ?>
                                                        <img src="<?php echo $avatar; ?>" crossorigin="anonymous">
                                                    <?php else: ?>
                                                        <div class="avatar-placeholder-large"><?php echo strtoupper(substr($user['fullname'], 0, 1)); ?></div>
                                                    <?php endif; ?>
                                                    <div class="verified-badge-card">
                                                        <i data-lucide="check-circle"></i>
                                                    </div>
                                                </div>
                                                <h3 class="pro-name-large"><?php echo htmlspecialchars($user['fullname']); ?></h3>
                                                <p class="pro-profession-large"><?php echo htmlspecialchars($user['profession'] ?? 'Professionnel'); ?></p>
                                                <p class="pro-location-large">
                                                    <i data-lucide="map-pin"></i>
                                                    <?php echo htmlspecialchars($user['city'] ?? ''); ?>, Cameroun
                                                </p>
                                            </div>

                                            <!-- Main: QR Code -->
                                            <div class="pro-card-qr-main">
                                                <?php 
                                                $profile_url = "https://" . $_SERVER['HTTP_HOST'] . "/tekrin/pro.php?id=" . $user_id;
                                                $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . urlencode($profile_url);
                                                ?>
                                                <div class="qr-code-wrapper">
                                                    <img src="<?php echo $qr_api_url; ?>" alt="QR Code" id="qrCodeImage" crossorigin="anonymous">
                                                </div>
                                            </div>

                                            <!-- Bottom: CTA -->
                                            <div class="pro-card-cta-bottom">
                                                <p>Scannez pour découvrir mon profil Tekrin</p>
                                                <span class="pro-url-text"><?php echo str_replace(['http://', 'https://'], '', $profile_url); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-info-footer-alert">
                                    <div class="alert-icon">
                                        <i data-lucide="info"></i>
                                    </div>
                                    <p>Votre QR Code est unique et lié à votre profil public. En le partageant, les clients pourront découvrir vos services et vous contacter.</p>
                                </div>
                            </div>

                            <div class="card tip-card">
                                <div class="tip-header">
                                    <i data-lucide="lightbulb"></i>
                                    <h4>Conseil</h4>
                                </div>
                                <p>Imprimez votre QR Code et placez-le sur vos cartes de visite, affiches, devantures, véhicules ou réseaux sociaux pour attirer plus de clients.</p>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: Stats & Actions -->
                        <div class="qrcode-column-right">
                            <!-- Card 1: Information -->
                            <div class="card settings-card-right">
                                <h3 class="right-card-title">Informations</h3>
                                <div class="info-list-premium">
                                    <div class="info-item-premium">
                                        <span class="label">Nom complet</span>
                                        <span class="value"><?php echo htmlspecialchars($user['fullname']); ?></span>
                                    </div>
                                    <div class="info-item-premium">
                                        <span class="label">Profession</span>
                                        <span class="value"><?php echo htmlspecialchars($user['profession'] ?? 'Artisan'); ?></span>
                                    </div>
                                    <div class="info-item-premium">
                                        <span class="label">Ville</span>
                                        <span class="value"><?php echo htmlspecialchars($user['city'] ?? 'Non précisé'); ?></span>
                                    </div>
                                    <div class="info-item-premium">
                                        <span class="label">Profil public</span>
                                        <span class="value link-value"><?php echo str_replace(['http://', 'https://'], '', $profile_url); ?></span>
                                    </div>
                                    <div class="info-item-premium">
                                        <span class="label">Statut</span>
                                        <span class="badge-status-pro">Vérifié</span>
                                    </div>
                                </div>
                                <button onclick="copyToClipboard('<?php echo $profile_url; ?>')" class="btn btn-outline btn-full-width">
                                    <i data-lucide="copy"></i> Copier le lien
                                </button>
                            </div>

                            <!-- Card 2: Download -->
                            <div class="card settings-card-right">
                                <h3 class="right-card-title">Télécharger ma carte</h3>
                                <p class="right-card-subtitle">Choisissez le format qui vous convient</p>
                                
                                <div class="download-grid-premium">
                                    <button onclick="exportCard('png')" class="download-item-card">
                                        <div class="dl-icon-box png-bg">
                                            <i data-lucide="image"></i>
                                        </div>
                                        <span>PNG</span>
                                    </button>
                                    <button onclick="exportCard('pdf')" class="download-item-card">
                                        <div class="dl-icon-box pdf-bg">
                                            <i data-lucide="file-text"></i>
                                        </div>
                                        <span>PDF</span>
                                    </button>
                                    <button onclick="exportCard('svg')" class="download-item-card">
                                        <div class="dl-icon-box svg-bg">
                                            <i data-lucide="code"></i>
                                        </div>
                                        <span>SVG</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Card 3: Share -->
                            <div class="card settings-card-right">
                                <h3 class="right-card-title">Partager mon profil</h3>
                                <p class="right-card-subtitle">Partagez votre profil en quelques clics</p>
                                
                                <div class="share-grid-premium">
                                    <a href="https://wa.me/?text=Découvrez mon profil professionnel sur Tekrin : <?php echo urlencode($profile_url); ?>" target="_blank" class="share-item-premium whatsapp">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                    </a>
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($profile_url); ?>" target="_blank" class="share-item-premium facebook">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    </a>
                                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($profile_url); ?>" target="_blank" class="share-item-premium linkedin">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v5.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.454C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0z"/></svg>
                                    </a>
                                    <button onclick="shareProfile()" class="share-item-premium other">
                                        <i data-lucide="more-horizontal"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card settings-card">
                        <div class="settings-header">
                            <h2>En construction</h2>
                            <p>Cette section sera bientôt disponible.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div> <!-- .settings-section-container -->
    <?php endif; ?>
</div> <!-- .container -->
</div> <!-- .app-layout -->

<style>
/* Settings Hub Design */
.settings-hub { max-width: 1000px; margin: 0 auto; }
.hub-header { margin-bottom: 48px; text-align: center; }
.hub-header h1 { font-size: 32px; font-weight: 800; color: #0F172A; margin-bottom: 12px; }
.hub-header p { color: #64748B; font-size: 17px; }

.hub-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; }
.hub-card { 
    background: white; 
    border: 1px solid #E2E8F0; 
    border-radius: 24px; 
    padding: 28px; 
    display: flex; 
    align-items: center; 
    gap: 24px; 
    text-decoration: none; 
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
}
.hub-card:hover { 
    border-color: var(--primary-blue); 
    transform: translateY(-4px); 
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08); 
}
.hub-card-icon { 
    width: 64px; 
    height: 64px; 
    border-radius: 18px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    flex-shrink: 0; 
}
.hub-card-icon i { width: 28px; height: 28px; }
.hub-card-content { flex: 1; }
.hub-card-content h3 { font-size: 18px; font-weight: 700; color: #1E293B; margin-bottom: 6px; }
.hub-card-content p { font-size: 14px; color: #64748B; line-height: 1.5; }
.hub-card-arrow { color: #CBD5E1; transition: transform 0.3s; }
.hub-card:hover .hub-card-arrow { transform: translateX(6px); color: var(--primary-blue); }

.hub-footer { margin-top: 48px; }
.hub-help-card-horizontal { 
    background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%); 
    border-radius: 28px; 
    padding: 40px; 
    color: white; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    gap: 40px; 
}
.hub-help-card-horizontal .help-text h4 { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
.hub-help-card-horizontal .help-text p { font-size: 16px; opacity: 0.8; line-height: 1.6; }
.hub-help-card-horizontal .btn-primary { white-space: nowrap; padding: 14px 28px; border-radius: 14px; }

/* Specific Section View Layout */
.settings-section-container { max-width: 1100px; margin: 0 auto; }
.section-top-nav { margin-bottom: 32px; }
.btn-back-hub { 
    display: inline-flex; 
    align-items: center; 
    gap: 12px; 
    text-decoration: none; 
    color: #64748B; 
    font-weight: 700; 
    font-size: 15px; 
    transition: all 0.2s; 
    padding: 10px 20px;
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
}
.btn-back-hub:hover { color: var(--primary-blue); border-color: var(--primary-blue); background: #F0F7FF; }
.btn-back-hub i { width: 20px; height: 20px; }

.settings-full-content { width: 100%; }
.settings-card { background: white; border-radius: 28px; padding: 48px; border: 1px solid #E2E8F0; box-shadow: var(--shadow-sm); }
.settings-header { margin-bottom: 40px; }
.settings-header h2 { font-size: 28px; font-weight: 800; color: var(--dark-bg); margin-bottom: 10px; }
.settings-header p { color: var(--text-muted); font-size: 16px; }

@media (max-width: 1024px) {
    .hub-grid { grid-template-columns: 1fr; }
    .hub-help-card-horizontal { flex-direction: column; text-align: center; padding: 32px; }
}

/* --- QR Code Premium Refined Design --- */
.settings-breadcrumb { display: flex; align-items: center; gap: 8px; margin-bottom: 24px; font-size: 13px; font-weight: 600; color: #94A3B8; }
.settings-breadcrumb i { width: 14px; height: 14px; }
.settings-breadcrumb .active { color: var(--primary-blue); }

.settings-header-main { display: flex; align-items: center; gap: 20px; margin-bottom: 40px; }
.header-icon-circle { width: 56px; height: 56px; background: #EEF2FF; color: var(--primary-blue); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1); }
.header-icon-circle i { width: 28px; height: 28px; }
.header-text h2 { font-size: 28px; font-weight: 800; color: var(--dark-bg); margin-bottom: 4px; }
.header-text p { color: var(--text-muted); font-size: 15px; }

.qrcode-premium-layout { display: grid; grid-template-columns: 1fr 380px; gap: 32px; align-items: start; }

/* Left Column */
.premium-card { background: white; border-radius: 24px; padding: 32px; border: 1px solid #E2E8F0; box-shadow: var(--shadow-sm); position: relative; }
.preview-main-card { padding: 40px; }
.card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
.card-header-flex h3 { font-size: 18px; font-weight: 800; color: var(--dark-bg); }
.badge-live { background: #DCFCE7; color: #166534; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 50px; letter-spacing: 1px; display: flex; align-items: center; gap: 4px; }
.badge-live::before { content: ''; width: 6px; height: 6px; background: #166534; border-radius: 50%; display: inline-block; animation: pulse 1.5s infinite; }

@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

/* VERTICAL CARD DESIGN */
.pro-card-export-container { background: #F8FAFC; padding: 60px; border-radius: 32px; border: 2px dashed #E2E8F0; display: flex; justify-content: center; margin-bottom: 32px; }
.pro-card-preview-vertical { 
    width: 380px; 
    height: 580px; 
    background: white; 
    border-radius: 28px; 
    box-shadow: 0 30px 60px rgba(0,0,0,0.12); 
    position: relative; 
    overflow: hidden; 
    border: 1px solid #F1F5F9;
}

/* Decorations */
.card-wave-decoration { 
    position: absolute; 
    bottom: -50px; 
    left: -50px; 
    right: -50px; 
    height: 250px; 
    background: linear-gradient(135deg, rgba(21, 101, 192, 0.95) 0%, rgba(106, 27, 154, 0.9) 100%); 
    border-radius: 50% 50% 0 0; 
    z-index: 1; 
}
.card-accent-shape-1 { position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(124, 77, 255, 0.05); border-radius: 50%; }
.card-accent-shape-2 { position: absolute; top: 150px; left: -30px; width: 60px; height: 60px; background: rgba(0, 188, 212, 0.05); border-radius: 50%; }

.pro-card-vertical-inner { position: relative; z-index: 2; height: 100%; padding: 40px; display: flex; flex-direction: column; align-items: center; text-align: center; }

.pro-card-brand-top { margin-bottom: 32px; width: 100%; }
.tekrin-logo-full { display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 800; font-size: 24px; color: #1E293B; margin-bottom: 8px; }
.brand-slogan { font-size: 11px; color: #64748B; font-weight: 500; max-width: 200px; margin: 0 auto; line-height: 1.4; }

.pro-card-user-center { margin-bottom: 32px; }
.pro-card-avatar-large { width: 100px; height: 100px; border-radius: 30px; margin: 0 auto 16px; position: relative; border: 4px solid white; box-shadow: 0 10px 25px rgba(0,0,0,0.1); background: #F8FAFC; overflow: hidden; }
.pro-card-avatar-large img { width: 100%; height: 100%; object-fit: cover; }
.avatar-placeholder-large { width: 100%; height: 100%; background: var(--grad-primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 800; }
.verified-badge-card { position: absolute; bottom: -5px; right: -5px; background: white; color: #10B981; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.verified-badge-card i { width: 20px; height: 20px; fill: currentColor; color: white; }

.pro-name-large { font-size: 22px; font-weight: 800; color: #1E293B; margin-bottom: 4px; }
.pro-profession-large { font-size: 14px; font-weight: 700; color: var(--primary-blue); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
.pro-location-large { font-size: 13px; color: #64748B; display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; }
.pro-location-large i { width: 14px; color: #94A3B8; }

.pro-card-qr-main { flex: 1; display: flex; align-items: center; justify-content: center; margin-bottom: 32px; }
.qr-code-wrapper { background: white; padding: 15px; border-radius: 24px; box-shadow: 0 15px 40px rgba(0,0,0,0.08); border: 1px solid #F1F5F9; width: 180px; height: 180px; }
.qr-code-wrapper img { width: 100%; height: 100%; }

.pro-card-cta-bottom { color: white; margin-top: auto; }
.pro-card-cta-bottom p { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; opacity: 0.9; }
.pro-url-text { font-size: 13px; font-weight: 600; opacity: 0.8; font-family: 'JetBrains Mono', monospace; }

.card-info-footer-alert { display: flex; gap: 16px; background: #F0F9FF; padding: 20px; border-radius: 16px; color: #0369A1; border: 1px solid #BAE6FD; }
.card-info-footer-alert .alert-icon { width: 44px; height: 44px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: var(--primary-blue); box-shadow: 0 4px 10px rgba(3, 105, 161, 0.1); }
.card-info-footer-alert p { font-size: 14px; line-height: 1.6; font-weight: 500; }

.tip-card { margin-top: 24px; background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%); color: white; padding: 32px; border: none; }
.tip-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; color: #F59E0B; }
.tip-header i { width: 20px; height: 20px; }
.tip-header h4 { font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
.tip-card p { font-size: 15px; line-height: 1.6; opacity: 0.8; }

/* Right Column Cards */
.settings-card-right { margin-bottom: 24px; padding: 28px; }
.right-card-title { font-size: 16px; font-weight: 800; color: var(--dark-bg); margin-bottom: 20px; }
.right-card-subtitle { font-size: 13px; color: var(--text-muted); margin: -15px 0 24px; }

.info-list-premium { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }
.info-item-premium { display: flex; flex-direction: column; gap: 4px; }
.info-item-premium .label { font-size: 11px; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.5px; }
.info-item-premium .value { font-size: 14px; font-weight: 600; color: #1E293B; }
.info-item-premium .link-value { color: var(--primary-blue); }
.badge-status-pro { display: inline-flex; width: fit-content; background: #DCFCE7; color: #166534; font-size: 11px; font-weight: 800; padding: 2px 10px; border-radius: 6px; }

.download-grid-premium { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.download-item-card { background: white; border: 1px solid #E2E8F0; border-radius: 16px; padding: 16px 10px; display: flex; flex-direction: column; align-items: center; gap: 10px; cursor: pointer; transition: all 0.2s; }
.download-item-card:hover { border-color: var(--primary-blue); background: #F8FAFC; transform: translateY(-3px); }
.dl-icon-box { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.dl-icon-box i { width: 20px; height: 20px; }
.png-bg { background: #EEF2FF; color: #4F46E5; }
.pdf-bg { background: #FFF1F2; color: #E11D48; }
.svg-bg { background: #F0FDF4; color: #16A34A; }
.download-item-card span { font-size: 12px; font-weight: 800; color: #475569; }

.share-grid-premium { display: flex; gap: 12px; }
.share-item-premium { flex: 1; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.2s; border: none; cursor: pointer; }
.share-item-premium:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
.share-item-premium.whatsapp { background: #25D366; }
.share-item-premium.facebook { background: #1877F2; }
.share-item-premium.linkedin { background: #0A66C2; }
.share-item-premium.other { background: #F1F5F9; color: #64748B; border: 1px solid #E2E8F0; }

.btn-full-width { width: 100%; justify-content: center; padding: 12px; border-radius: 12px; }

/* Sidebar Refinement */
.sidebar-help-card { margin: 40px 16px 20px; background: var(--grad-primary); border-radius: 20px; padding: 24px; color: white; }
.sidebar-help-card h4 { font-size: 16px; font-weight: 800; margin-bottom: 8px; }
.sidebar-help-card p { font-size: 13px; opacity: 0.9; margin-bottom: 16px; line-height: 1.5; }
.btn-white-sidebar { background: white; color: var(--primary-blue); width: 100%; border: none; padding: 10px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; }
.btn-white-sidebar:hover { transform: scale(1.02); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

@media (max-width: 1200px) {
    .qrcode-premium-layout { grid-template-columns: 1fr; }
    .qrcode-column-right { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
}

@media (max-width: 768px) {
    .pro-card-export-container { padding: 30px 15px; }
    .pro-card-preview-vertical { width: 100%; max-width: 340px; height: 520px; }
    .qrcode-column-right { grid-template-columns: 1fr; }
}

/* Rest of your existing styles */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 14px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 16px; border: 2px solid #E2E8F0; border-radius: 12px; font-size: 15px; transition: border-color 0.2s; }
.form-actions { margin-top: 30px; padding-top: 20px; border-top: 1px solid #F1F5F9; }
</style>

<!-- Export Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert("Lien copié dans le presse-papier !");
    });
}

async function exportCard(format) {
    const card = document.getElementById('proCardToDownload');
    const fileName = `carte-tekrin-<?php echo strtolower(str_replace(' ', '-', $user['fullname'])); ?>`;
    
    // Add a loading state to the button if possible
    const btn = event.currentTarget;
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i data-lucide="loader" class="spin"></i>';
    lucide.createIcons();

    try {
        if (format === 'png') {
            const canvas = await html2canvas(card, {
                scale: 3, // High quality
                useCORS: true,
                backgroundColor: null,
                logging: false,
                width: 380,
                height: 580
            });
            const link = document.createElement('a');
            link.download = `${fileName}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        } 
        else if (format === 'pdf') {
            const canvas = await html2canvas(card, {
                scale: 3,
                useCORS: true,
                width: 380,
                height: 580
            });
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            // Vertical card: 55mm x 85mm
            const pdf = new jsPDF('p', 'mm', [55, 85]);
            pdf.addImage(imgData, 'PNG', 0, 0, 55, 85);
            pdf.save(`${fileName}.pdf`);
        }
        else if (format === 'svg') {
            const dataUrl = await domtoimage.toSvg(card);
            const link = document.createElement('a');
            link.download = `${fileName}.svg`;
            link.href = dataUrl;
            link.click();
        }
    } catch (error) {
        console.error("Export failed:", error);
        alert("Une erreur est survenue lors de l'exportation. Veuillez réessayer.");
    } finally {
        btn.innerHTML = originalContent;
        lucide.createIcons();
    }
}

function shareProfile() {
    if (navigator.share) {
        navigator.share({
            title: 'Profil Tekrin de <?php echo addslashes($user['fullname']); ?>',
            text: 'Découvrez mes services professionnels sur Tekrin.',
            url: '<?php echo $profile_url; ?>'
        }).catch(console.error);
    } else {
        alert("Le partage n'est pas supporté sur ce navigateur. Utilisez les boutons directs ou copiez le lien.");
    }
}
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin { animation: spin 1s linear infinite; }
</style>

<style>
.card { background: white; border-radius: 20px; padding: 30px; border: 1px solid #E2E8F0; box-shadow: var(--shadow-sm); }
.welcome-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 14px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 16px; border: 2px solid #E2E8F0; border-radius: 12px; font-size: 15px; transition: border-color 0.2s; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary-blue); outline: none; }
</style>

<script>
const citiesByRegion = {
    "Adamaoua": ["Ngaoundéré", "Meiganga", "Tignère", "Banyo", "Tibati", "Bankim", "Dir", "Ngaoundal"],
    "Centre": ["Yaoundé", "Mbalmayo", "Obala", "Bafia", "Nany-Job", "Eseka", "Akonolinga", "Monatélé", "Ngoumou", "Soa"],
    "Est": ["Bertoua", "Abong-Mbang", "Batouri", "Yokadouma", "Garoua-Boulaï", "Moloundou", "Belabo"],
    "Extrême-Nord": ["Maroua", "Kousseri", "Yagoua", "Mokolo", "Mora", "Kaélé", "Bogo", "Waza"],
    "Littoral": ["Douala", "Edéa", "Nkongsamba", "Loum", "Mbanga", "Melong", "Manjo", "Yabassi", "Dibombari"],
    "Nord": ["Garoua", "Guider", "Figuil", "Poli", "Rey-Bouba", "Lagdo", "Pitoa"],
    "Nord-Ouest": ["Bamenda", "Kumbo", "Wum", "Fundong", "Bali", "Bafut", "Batibo", "Ndop"],
    "Ouest": ["Bafoussam", "Foumban", "Dschang", "Mbouda", "Baham", "Bangangté", "Bandjoun", "Bafang", "Foumbot", "Santchou"],
    "Sud": ["Ebolowa", "Kribi", "Sangmélima", "Ambam", "Lolodorf", "Mvomeka'a", "Campo"],
    "Sud-Ouest": ["Buea", "Limbe", "Tiko", "Kumba", "Mamfe", "Mutengene", "Muyuka", "Fontem", "Bangem"]
};

function updateCities() {
    const regionSelect = document.getElementById('region');
    const citySelect = document.getElementById('city');
    const selectedRegion = regionSelect.value;
    const currentCity = "<?php echo $user['city'] ?? ''; ?>";

    citySelect.innerHTML = '<option value="">-- Sélectionner une ville --</option>';

    if (selectedRegion && citiesByRegion[selectedRegion]) {
        citiesByRegion[selectedRegion].sort().forEach(city => {
            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            if (city === currentCity) option.selected = true;
            citySelect.appendChild(option);
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    updateCities();
    lucide.createIcons();
});
</script>

</div> <!-- .main-wrapper -->
<script src="assets/js/app.js"></script>
</body>
</html>
