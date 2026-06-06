<?php
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$errors = [];
$success = false;
$user_id = $_SESSION['user_id'];

// Récupérer les données actuelles de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

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

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET 
                fullname = ?, profession = ?, company_name = ?, region = ?, city = ?, 
                phone_number = ?, whatsapp_number = ?, bio = ?, services = ?
                WHERE id = ?");
            
            if ($stmt->execute([
                $fullname, $profession, $company_name, $region, $city, 
                $phone_number, $whatsapp_number, $bio, $services, 
                $user_id
            ])) {
                $_SESSION['user_fullname'] = $fullname;
                setFlashMessage("Informations mises à jour avec succès !", "success");
                header("Location: profile.php");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de mise à jour : " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="app-layout">
    <div class="container">
        <div class="layout-wrapper">
            <main class="main-content">
                <div class="card profile-edit-card">
                    <div class="welcome-header">
                        <div>
                            <h1>Modifier mon profil</h1>
                            <p style="color: var(--text-muted);">Personnalisez votre identité visuelle et vos informations.</p>
                        </div>
                        <a href="profile.php" class="btn btn-outline">Voir mon profil</a>
                    </div>

                    <form action="profile_edit.php" method="POST">
                        <!-- Form Fields -->
                        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="fullname">Nom complet</label>
                                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="profession">Profession</label>
                                <input type="text" id="profession" name="profession" value="<?php echo htmlspecialchars($user['profession'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="company_name">Entreprise (Optionnel)</label>
                                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
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
                            <div class="form-group">
                                <label for="phone_number">Téléphone</label>
                                <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label for="bio">Bio professionnelle</label>
                            <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="services">Services (séparez par des virgules)</label>
                            <input type="text" id="services" name="services" value="<?php echo htmlspecialchars($user['services'] ?? ''); ?>">
                        </div>

                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit" class="btn btn-register btn-lg" style="flex: 2;">
                                Enregistrer les changements
                            </button>
                            <a href="logout.php" class="btn btn-outline btn-lg logout-btn-danger" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; color: #EF4444; border-color: #EF4444;">
                                <i data-lucide="log-out" style="width: 18px;"></i> Déconnexion
                            </a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>

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

<?php require_once 'includes/footer.php'; ?>
