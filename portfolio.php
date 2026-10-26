<?php
require_once 'includes/auth.php';

if (!isLoggedIn()) redirect('login.php');
requireOnboarding();
$user_id = $_SESSION['user_id'];
$errors = [];

// Gérer l'ajout de réalisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['portfolio_image'])) {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    if ($_FILES['portfolio_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 10 * 1024 * 1024; // 10MB

        if (!in_array($_FILES['portfolio_image']['type'], $allowed_types)) {
            $errors[] = "Format d'image non autorisé (JPG, PNG uniquement).";
        } elseif ($_FILES['portfolio_image']['size'] > $max_size) {
            $errors[] = "L'image est trop lourde (max 10Mo).";
        } else {
            $extension = pathinfo($_FILES['portfolio_image']['name'], PATHINFO_EXTENSION);
            $new_file_name = "portfolio_" . $user_id . "_" . time() . "_" . uniqid() . "." . $extension;
            $upload_dir = 'assets/uploads/portfolio/';
            
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            if (move_uploaded_file($_FILES['portfolio_image']['tmp_name'], $upload_dir . $new_file_name)) {
                // Essayer d'insérer avec titre et description d'abord
                try {
                    $stmt = $pdo->prepare("INSERT INTO portfolio (user_id, image_path, title, description) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $new_file_name, $title, $description]);
                } catch (PDOException $e) {
                    // Si les colonnes n'existent pas encore, insérer sans
                    $stmt = $pdo->prepare("INSERT INTO portfolio (user_id, image_path) VALUES (?, ?)");
                    $stmt->execute([$user_id, $new_file_name]);
                }
                setFlashMessage("Réalisation ajoutée au portfolio !", "success");
                redirect('portfolio.php');
            }
        }
    }
}

// Gérer la suppression
if (isset($_GET['delete'])) {
    $img_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_path FROM portfolio WHERE id = ? AND user_id = ?");
    $stmt->execute([$img_id, $user_id]);
    $img = $stmt->fetch();

    if ($img) {
        $file_path = 'assets/uploads/portfolio/' . $img['image_path'];
        if (file_exists($file_path)) unlink($file_path);
        
        $stmt = $pdo->prepare("DELETE FROM portfolio WHERE id = ?");
        $stmt->execute([$img_id]);
        setFlashMessage("Réalisation supprimée.", "info");
        redirect('portfolio.php');
    }
}

// Récupérer le portfolio
$stmt = $pdo->prepare("SELECT * FROM portfolio WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$portfolio = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="app-layout">
    <div class="container" style="max-width: 1400px;">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:40px;">
            <div>
                <h1 style="font-weight:800; font-size:36px; margin-bottom:8px;">Mon Portfolio</h1>
                <p style="color:#64748B; font-size:16px;">Montrez vos meilleures réalisations à vos clients potentiels.</p>
            </div>
            <div style="display:flex; gap:12px;">
                <a href="feed.php" class="btn btn-outline" style="border-radius:12px; padding:12px 24px; font-weight:700;">
                    <i data-lucide="arrow-left" style="width:18px; height:18px;"></i>
                    Retour au fil
                </a>
                <button id="addPortfolioBtn" class="btn btn-register" style="border-radius:12px; padding:12px 24px; font-weight:700; display:flex; gap:8px;">
                    <i data-lucide="plus" style="width:18px; height:18px;"></i>
                    Ajouter une réalisation
                </button>
            </div>
        </div>

        <!-- Modal Ajouter Réalisation -->
        <div id="addPortfolioModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
            <div style="width:100%; max-width:600px; background:white; border-radius:24px; overflow:hidden;">
                <div style="background:linear-gradient(135deg, #1565C0, #6A1B9A); padding:24px 30px; color:white;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h2 style="font-weight:800; margin-bottom:4px;">Ajouter une réalisation</h2>
                            <p style="opacity:0.9; font-size:14px;">Ajoutez un titre et une description pour mieux présenter votre travail.</p>
                        </div>
                        <button id="closePortfolioModal" style="background:rgba(255,255,255,0.2); border:none; color:white; width:40px; height:40px; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>
                
                <div style="padding:30px;">
                    <form id="portfolioForm" method="POST" enctype="multipart/form-data">
                        <div style="margin-bottom:20px;">
                            <label style="display:block; font-weight:800; margin-bottom:8px; color:#1E293B;">Titre de la réalisation</label>
                            <input type="text" name="title" placeholder="Ex: Site web pour un restaurant" style="width:100%; padding:14px 16px; border:2px solid #E2E8F0; border-radius:12px; font-size:15px;">
                        </div>
                        
                        <div style="margin-bottom:20px;">
                            <label style="display:block; font-weight:800; margin-bottom:8px; color:#1E293B;">Description</label>
                            <textarea name="description" placeholder="Décrivez cette réalisation, les défis rencontrés, les outils utilisés..." style="width:100%; padding:14px 16px; border:2px solid #E2E8F0; border-radius:12px; font-size:15px; min-height:100px; resize:vertical;"></textarea>
                        </div>
                        
                        <div style="margin-bottom:24px;">
                            <label style="display:block; font-weight:800; margin-bottom:8px; color:#1E293B;">Image de la réalisation</label>
                            <div id="portfolioUploadArea" style="border:2px dashed #CBD5E1; border-radius:16px; padding:40px; text-align:center; cursor:pointer; transition:all 0.2s;">
                                <i data-lucide="image" style="width:48px; height:48px; color:#94A3B8; margin-bottom:12px;"></i>
                                <div style="font-weight:700; color:#475569; margin-bottom:4px;">Cliquez pour télécharger</div>
                                <div style="font-size:13px; color:#94A3B8;">PNG, JPG ou GIF (max 10MB)</div>
                                <input type="file" name="portfolio_image" accept="image/*" id="portfolio_image" style="display:none;">
                            </div>
                            <div id="portfolioPreview" style="display:none; margin-top:16px;">
                                <img id="portfolioPreviewImg" style="width:100%; max-height:300px; object-fit:cover; border-radius:12px;">
                            </div>
                        </div>
                    </form>
                </div>
                
                <div style="padding:20px 30px; border-top:1px solid #E2E8F0; display:flex; gap:12px;">
                    <button type="button" id="cancelPortfolioBtn" class="btn btn-outline" style="flex:1; border-radius:12px; padding:14px; font-weight:700;">Annuler</button>
                    <button type="submit" form="portfolioForm" class="btn btn-register" style="flex:1; border-radius:12px; padding:14px; font-weight:700;">
                        <i data-lucide="upload" style="width:18px; height:18px;"></i>
                        Ajouter la réalisation
                    </button>
                </div>
            </div>
        </div>

        <!-- Lightbox pour les images -->
        <div id="portfolioLightbox" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:10000; align-items:center; justify-content:center;">
            <button id="closeLightbox" style="position:absolute; top:30px; right:30px; background:rgba(255,255,255,0.1); border:none; color:white; width:50px; height:50px; border-radius:50%; cursor:pointer;">
                <i data-lucide="x" style="width:24px; height:24px;"></i>
            </button>
            <div style="max-width:90vw; max-height:90vh;">
                <img id="lightboxImg" style="max-width:100%; max-height:80vh; border-radius:16px;">
                <div id="lightboxInfo" style="text-align:center; color:white; margin-top:20px;">
                    <h3 id="lightboxTitle" style="font-size:24px; font-weight:800; margin-bottom:8px;"></h3>
                    <p id="lightboxDesc" style="opacity:0.8; font-size:16px;"></p>
                </div>
            </div>
        </div>

        <!-- Galerie -->
        <?php if (empty($portfolio)): ?>
            <div class="card" style="text-align:center; padding:80px 20px;">
                <div style="background:#F8FAFC; width:120px; height:120px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px;">
                    <i data-lucide="images" style="width:60px; height:60px; color:#94A3B8;"></i>
                </div>
                <h2 style="font-weight:800; font-size:24px; margin-bottom:12px;">Votre portfolio est vide</h2>
                <p style="color:#64748B; max-width:500px; margin:0 auto 30px;">Ajoutez vos meilleures réalisations pour montrer votre expertise et attirer plus de clients.</p>
                <button id="addFirstPortfolioBtn" class="btn btn-register" style="border-radius:12px; padding:12px 28px; font-weight:700;">
                    <i data-lucide="plus" style="width:18px; height:18px;"></i>
                    Ajouter ma première réalisation
                </button>
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:24px;">
                <?php foreach ($portfolio as $item): 
                    $img_path = $item['image_path'] ?? '';
                    if ($img_path && strpos($img_path, 'assets/') !== 0) $img_path = 'assets/uploads/portfolio/' . $img_path;
                ?>
                    <div class="card" style="padding:0; overflow:hidden; cursor:pointer; transition:all 0.3s;" onclick='openLightbox(<?php echo json_encode([
                        "image" => $img_path,
                        "title" => htmlspecialchars($item["title"] ?? "Sans titre"),
                        "description" => htmlspecialchars($item["description"] ?? "")
                    ]); ?>)'>
                        <div style="aspect-ratio: 4/3; overflow:hidden;">
                            <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($item['title'] ?? 'Réalisation'); ?>" style="width:100%; height:100%; object-fit:cover; transition:transform 0.3s;" class="portfolio-img">
                        </div>
                        <div style="padding:20px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                                <div style="flex:1;">
                                    <h3 style="font-weight:800; color:#1E293B; margin-bottom:6px;"><?php echo htmlspecialchars($item['title'] ?? 'Sans titre'); ?></h3>
                                    <?php if ($item['description']): ?>
                                        <p style="color:#64748B; font-size:14px; line-height:1.5;"><?php echo htmlspecialchars(mb_substr($item['description'], 0, 100)); ?><?php echo mb_strlen($item['description']) > 100 ? '...' : ''; ?></p>
                                    <?php endif; ?>
                                </div>
                                <a href="portfolio.php?delete=<?php echo $item['id']; ?>" onclick="event.stopPropagation(); return confirm('Êtes-vous sûr de vouloir supprimer cette réalisation ?');" style="background:#FEF2F2; color:#EF4444; border:none; padding:10px; border-radius:10px; cursor:pointer;">
                                    <i data-lucide="trash-2" style="width:18px; height:18px;"></i>
                                </a>
                            </div>
                            <div style="margin-top:12px; color:#94A3B8; font-size:13px;">
                                <i data-lucide="clock" style="width:14px; height:14px; display:inline; margin-right:6px;"></i>
                                <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.portfolio-img:hover { transform: scale(1.05); }
#portfolioUploadArea:hover { border-color: var(--primary-blue); background: #EFF6FF; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    
    // Modal
    const addBtn = document.getElementById('addPortfolioBtn');
    const addFirstBtn = document.getElementById('addFirstPortfolioBtn');
    const modal = document.getElementById('addPortfolioModal');
    const closeBtn = document.getElementById('closePortfolioModal');
    const cancelBtn = document.getElementById('cancelPortfolioBtn');
    const uploadArea = document.getElementById('portfolioUploadArea');
    const fileInput = document.getElementById('portfolio_image');
    const preview = document.getElementById('portfolioPreview');
    const previewImg = document.getElementById('portfolioPreviewImg');
    
    const openModal = () => { modal.style.display = 'flex'; };
    const closeModalFunc = () => { modal.style.display = 'none'; };
    
    addBtn?.addEventListener('click', openModal);
    addFirstBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModalFunc);
    cancelBtn?.addEventListener('click', closeModalFunc);
    
    modal?.addEventListener('click', (e) => { if (e.target === modal) closeModalFunc(); });
    
    // Upload preview
    uploadArea?.addEventListener('click', () => fileInput.click());
    fileInput?.addEventListener('change', (e) => {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    
    // Lightbox
    window.openLightbox = (data) => {
        const lightbox = document.getElementById('portfolioLightbox');
        const img = document.getElementById('lightboxImg');
        const title = document.getElementById('lightboxTitle');
        const desc = document.getElementById('lightboxDesc');
        
        img.src = data.image;
        title.textContent = data.title;
        desc.textContent = data.description;
        lightbox.style.display = 'flex';
    };
    
    document.getElementById('closeLightbox')?.addEventListener('click', () => {
        document.getElementById('portfolioLightbox').style.display = 'none';
    });
    
    document.getElementById('portfolioLightbox')?.addEventListener('click', (e) => {
        if (e.target.id === 'portfolioLightbox') {
            document.getElementById('portfolioLightbox').style.display = 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>