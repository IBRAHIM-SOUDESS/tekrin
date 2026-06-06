<?php
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Rediriger si l'onboarding est déjà complété
if (isset($_SESSION['onboarding_completed']) && $_SESSION['onboarding_completed']) {
    redirect('feed.php');
}

$user_id = $_SESSION['user_id'];
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];

// Récupérer les données actuelles
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Traitement des formulaires par étape
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1: // Profession
            $profession = sanitize($_POST['profession'] ?? '');
            $role = sanitize($_POST['role'] ?? 'technicien');
            if (!empty($profession)) {
                $stmt = $pdo->prepare("UPDATE users SET profession = ?, role = ? WHERE id = ?");
                $stmt->execute([$profession, $role, $user_id]);
                redirect('wizard.php?step=2');
            } else {
                $errors[] = "Veuillez renseigner votre profession.";
            }
            break;

        case 2: // Photo
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                if (in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                    $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $new_file_name = "profile_" . $user_id . "_" . time() . "." . $extension;
                    $upload_dir = 'assets/images/profiles/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_file_name)) {
                        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                        $stmt->execute([$new_file_name, $user_id]);
                        $_SESSION['user_avatar'] = $new_file_name;
                        redirect('wizard.php?step=3');
                    }
                } else {
                    $errors[] = "Format d'image non valide.";
                }
            } else {
                // Permettre de passer cette étape
                redirect('wizard.php?step=3');
            }
            break;

        case 3: // City & Region
            $region = sanitize($_POST['region'] ?? '');
            $city = sanitize($_POST['city'] ?? '');
            if (!empty($region) && !empty($city)) {
                $stmt = $pdo->prepare("UPDATE users SET region = ?, city = ? WHERE id = ?");
                $stmt->execute([$region, $city, $user_id]);
                redirect('wizard.php?step=4');
            } else {
                $errors[] = "Veuillez renseigner votre région et votre ville.";
            }
            break;

        case 4: // Phone & WhatsApp
            $phone = sanitize($_POST['phone_number'] ?? '');
            $whatsapp = sanitize($_POST['whatsapp_number'] ?? '');
            if (!empty($phone)) {
                $stmt = $pdo->prepare("UPDATE users SET phone_number = ?, whatsapp_number = ? WHERE id = ?");
                $stmt->execute([$phone, $whatsapp, $user_id]);
                redirect('wizard.php?step=5');
            } else {
                $errors[] = "Veuillez renseigner au moins un numéro de téléphone.";
            }
            break;

        case 5: // Description
            $bio = sanitize($_POST['bio'] ?? '');
            if (!empty($bio)) {
                $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
                $stmt->execute([$bio, $user_id]);
                redirect('wizard.php?step=6');
            } else {
                $errors[] = "Veuillez écrire une courte description de votre expertise.";
            }
            break;

        case 6: // Services
            $services = sanitize($_POST['services'] ?? '');
            if (!empty($services)) {
                $stmt = $pdo->prepare("UPDATE users SET services = ? WHERE id = ?");
                $stmt->execute([$services, $user_id]);
                redirect('wizard.php?step=7');
            } else {
                $errors[] = "Veuillez lister au moins un service.";
            }
            break;

        case 7: // Portfolio & Finalize
            if (isset($_FILES['portfolio_image']) && $_FILES['portfolio_image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                if (in_array($_FILES['portfolio_image']['type'], $allowed_types)) {
                    $extension = pathinfo($_FILES['portfolio_image']['name'], PATHINFO_EXTENSION);
                    $new_file_name = "portfolio_" . $user_id . "_" . time() . "." . $extension;
                    $upload_dir = 'assets/images/portfolio/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    if (move_uploaded_file($_FILES['portfolio_image']['tmp_name'], $upload_dir . $new_file_name)) {
                        $stmt = $pdo->prepare("INSERT INTO portfolio (user_id, image_path) VALUES (?, ?)");
                        $stmt->execute([$user_id, $new_file_name]);
                    }
                }
            }

            // L'onboarding est considéré comme fini
            $stmt = $pdo->prepare("UPDATE users SET onboarding_completed = 1 WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['onboarding_completed'] = 1;
            setFlashMessage("Configuration terminée ! Bienvenue sur votre fil d'actualité.", "success");
            redirect('feed.php');
            break;
    }
}

require_once 'includes/header.php';
?>

<div class="app-container" style="background: var(--grad-soft); min-height: calc(100vh - 80px); display: flex; align-items: center;">
    <div class="container">
        <div class="auth-card" style="max-width: 600px; margin: 0 auto;" data-reveal>
            <div class="wizard-progress" style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span style="font-size: 14px; font-weight: 700; color: var(--primary-blue);">Étape <?php echo $step; ?> sur 7</span>
                    <span style="font-size: 14px; color: var(--text-muted);"><?php echo round(($step / 7) * 100); ?>% complété</span>
                </div>
                <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                    <div style="width: <?php echo ($step / 7) * 100; ?>%; height: 100%; background: var(--grad-primary); transition: width 0.3s ease;"></div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach($errors as $error) echo "<div>$error</div>"; ?>
                </div>
            <?php endif; ?>

            <form action="wizard.php?step=<?php echo $step; ?>" method="POST" enctype="multipart/form-data">
                
                <?php if ($step == 1): ?>
                    <h2 style="margin-bottom: 10px;">Quelle est votre profession ?</h2>
                    <p style="color: var(--text-muted); margin-bottom: 30px;">Dites-nous ce que vous faites pour que les clients puissent vous trouver.</p>
                    
                    <div class="form-group">
                        <label>Votre métier</label>
                        <input type="text" name="profession" placeholder="Ex: Électricien bâtiment, Menuisier..." required value="<?php echo htmlspecialchars($user['profession'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Type de profil</label>
                        <select name="role" required>
                            <option value="technicien" <?php echo ($user['role'] == 'technicien') ? 'selected' : ''; ?>>Technicien</option>
                            <option value="artisan" <?php echo ($user['role'] == 'artisan') ? 'selected' : ''; ?>>Artisan</option>
                            <option value="entreprise" <?php echo ($user['role'] == 'entreprise') ? 'selected' : ''; ?>>Entreprise</option>
                        </select>
                    </div>

                <?php elseif ($step == 2): ?>
                    <h2 style="margin-bottom: 10px;">Ajoutez une photo de profil</h2>
                    <p style="color: var(--text-muted); margin-bottom: 30px;">Un visage amical et professionnel inspire confiance à vos futurs clients.</p>
                    
                    <div class="profile-sidebar" style="margin-bottom: 30px;">
                        <div class="avatar-upload">
                            <div class="avatar-preview" id="preview-box">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="assets/images/profiles/<?php echo $user['profile_picture']; ?>" alt="Avatar">
                                <?php else: ?>
                                    <div class="avatar-placeholder"><i data-lucide="user" style="width: 48px; height: 48px;"></i></div>
                                <?php endif; ?>
                            </div>
                            <label for="profile_picture" class="upload-input">
                                <i data-lucide="camera" style="width: 20px;"></i>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" onchange="previewImage(this)">
                            </label>
                        </div>
                    </div>

                <?php elseif ($step == 3): ?>
                    <h2 style="margin-bottom: 10px;">Où êtes-vous situé ?</h2>
                    <p style="color: var(--text-muted); margin-bottom: 30px;">La géolocalisation aidera les clients proches de vous à vous contacter.</p>
                    
                    <div class="form-group">
                        <label>Région</label>
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
                        <label>Ville</label>
                        <select id="city" name="city" required>
                            <option value="">Sélectionnez d'abord une région</option>
                        </select>
                    </div>

                <?php elseif ($step == 4): ?>
                    <h2 style="margin-bottom: 10px;">Vos coordonnées</h2>
                    <p style="color: var(--text-muted); margin-bottom: 30px;">Comment vos clients peuvent-ils vous joindre ?</p>
                    
                    <div class="form-group">
                        <label>Numéro de téléphone principal</label>
                        <input type="tel" name="phone_number" placeholder="Ex: 6xx xxx xxx" required value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Numéro WhatsApp (Optionnel)</label>
                        <input type="tel" name="whatsapp_number" placeholder="Ex: 6xx xxx xxx" value="<?php echo htmlspecialchars($user['whatsapp_number'] ?? ''); ?>">
                    </div>

                <?php elseif ($step == 5): ?>
                    <h2 style="margin-bottom: 10px;">Présentez-vous</h2>
                    <p style="color: var(--text-muted); margin-bottom: 30px;">Décrivez brièvement votre expertise et ce qui vous différencie.</p>
                    
                    <div class="form-group">
                        <label>Description professionnelle</label>
                        <textarea name="bio" rows="5" placeholder="Ex: Je suis un technicien frigoriste certifié avec 8 ans d'expérience..." required><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                <?php elseif ($step == 6): ?>
                    <h2 style="margin-bottom: 10px;">Quels services offrez-vous ?</h2>
                    <p style="color: var(--text-muted); margin-bottom: 30px;">Listez vos prestations principales pour apparaître dans les recherches.</p>
                    
                    <div class="form-group">
                        <label>Services (Séparez par des virgules)</label>
                        <input type="text" name="services" placeholder="Ex: Réparation clim, Installation frigo, Entretien..." required value="<?php echo htmlspecialchars($user['services'] ?? ''); ?>">
                    </div>

                <?php elseif ($step == 7): ?>
                    <h2 style="margin-bottom: 10px;">Vos premières réalisations</h2>
                    <p style="color: var(--text-muted); margin-bottom: 30px;">Ajoutez une photo de votre meilleur travail pour commencer votre portfolio.</p>
                    
                    <div style="background: #f8fafc; padding: 40px; border-radius: 24px; text-align: center; border: 2px dashed #e2e8f0; margin-bottom: 30px;">
                        <div style="color: var(--primary-blue); margin-bottom: 20px;">
                            <i data-lucide="upload-cloud" style="width: 48px; height: 48px;"></i>
                        </div>
                        <h3 style="margin-bottom: 10px;">Ajouter une photo</h3>
                        <input type="file" name="portfolio_image" id="portfolio_image" accept="image/*" style="display: none;" onchange="previewPortfolio(this)">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('portfolio_image').click()">Choisir un fichier</button>
                        <div id="portfolio-preview" style="margin-top: 20px; display: none;"></div>
                    </div>
                    
                    <div style="background: rgba(16, 185, 129, 0.05); padding: 20px; border-radius: 15px; border: 1px solid rgba(16, 185, 129, 0.1); text-align: center;">
                        <p style="color: #065f46; font-size: 14px; font-weight: 600;">Vous pourrez en ajouter d'autres plus tard !</p>
                    </div>
                <?php endif; ?>

                <div style="display: flex; gap: 15px; margin-top: 40px;">
                    <?php if ($step > 1): ?>
                        <a href="wizard.php?step=<?php echo $step - 1; ?>" class="btn btn-outline" style="flex: 1;">Précédent</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-register" style="flex: 2; padding: 16px;">
                        <?php echo ($step == 7) ? "Terminer la configuration" : "Continuer"; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
    if (!regionSelect || !citySelect) return;
    
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

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewBox = document.getElementById('preview-box');
            previewBox.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewPortfolio(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewBox = document.getElementById('portfolio-preview');
            previewBox.style.display = 'block';
            previewBox.innerHTML = `<img src="${e.target.result}" style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 12px; box-shadow: var(--shadow-sm);">`;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('region')) updateCities();
    lucide.createIcons();
});
</script>

<?php require_once 'includes/footer.php'; ?>