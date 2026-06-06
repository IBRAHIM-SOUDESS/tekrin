<?php
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$errors = [];

// Gérer l'upload d'image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['portfolio_image'])) {
    if ($_FILES['portfolio_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['portfolio_image']['type'], $allowed_types)) {
            $errors[] = "Format d'image non autorisé (JPG, PNG uniquement).";
        } elseif ($_FILES['portfolio_image']['size'] > $max_size) {
            $errors[] = "L'image est trop lourde (max 5Mo).";
        } else {
            $extension = pathinfo($_FILES['portfolio_image']['name'], PATHINFO_EXTENSION);
            $new_file_name = "portfolio_" . $user_id . "_" . time() . "_" . uniqid() . "." . $extension;
            $upload_dir = 'assets/images/portfolio/';
            
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($_FILES['portfolio_image']['tmp_name'], $upload_dir . $new_file_name)) {
                $stmt = $pdo->prepare("INSERT INTO portfolio (user_id, image_path) VALUES (?, ?)");
                $stmt->execute([$user_id, $new_file_name]);
                setFlashMessage("Image ajoutée au portfolio !", "success");
                redirect('portfolio.php');
            }
        }
    }
}

// Gérer la suppression d'image
if (isset($_GET['delete'])) {
    $img_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_path FROM portfolio WHERE id = ? AND user_id = ?");
    $stmt->execute([$img_id, $user_id]);
    $img = $stmt->fetch();

    if ($img) {
        $file_path = 'assets/images/portfolio/' . $img['image_path'];
        if (file_exists($file_path)) unlink($file_path);
        
        $stmt = $pdo->prepare("DELETE FROM portfolio WHERE id = ?");
        $stmt->execute([$img_id]);
        setFlashMessage("Image supprimée.", "info");
        redirect('portfolio.php');
    }
}

// Récupérer le portfolio
$stmt = $pdo->prepare("SELECT * FROM portfolio WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$portfolio = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="app-container">
    <div class="container">
        <div class="dashboard-card">
            <div class="welcome-header">
                <div>
                    <h1>Mon Portfolio</h1>
                    <p style="color: var(--text-muted);">Montrez vos meilleures réalisations à vos clients.</p>
                </div>
                <a href="feed.php" class="btn btn-outline">Retour au fil d'actualité</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach($errors as $error) echo "<div>$error</div>"; ?>
                </div>
            <?php endif; ?>

            <!-- Upload Section -->
            <div style="background: #f8fafc; padding: 40px; border-radius: 24px; text-align: center; border: 2px dashed #e2e8f0; margin-bottom: 40px;">
                <form action="portfolio.php" method="POST" enctype="multipart/form-data" id="portfolioForm">
                    <div style="color: var(--primary-blue); margin-bottom: 20px;">
                        <i data-lucide="upload-cloud" style="width: 48px; height: 48px;"></i>
                    </div>
                    <h3 style="margin-bottom: 10px;">Ajouter une réalisation</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">Glissez-déposez une image ou cliquez pour parcourir</p>
                    <input type="file" name="portfolio_image" id="portfolio_image" accept="image/*" style="display: none;" onchange="document.getElementById('portfolioForm').submit()">
                    <button type="button" class="btn btn-register" onclick="document.getElementById('portfolio_image').click()">Choisir une image</button>
                </form>
            </div>

            <!-- Gallery Section -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                <?php if (empty($portfolio)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--text-muted);">
                        Votre portfolio est vide pour le moment.
                    </div>
                <?php else: ?>
                    <?php foreach ($portfolio as $item): ?>
                        <div style="position: relative; border-radius: 15px; overflow: hidden; aspect-ratio: 1; group" class="portfolio-card">
                            <img src="assets/images/portfolio/<?php echo $item['image_path']; ?>" alt="Réalisation" style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                                <a href="portfolio.php?delete=<?php echo $item['id']; ?>" style="color: white; background: #ef4444; padding: 10px; border-radius: 50%; display: flex;" onclick="return confirm('Supprimer cette image ?')">
                                    <i data-lucide="trash-2" style="width: 20px; height: 20px;"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>