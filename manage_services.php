<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer les infos de l'utilisateur
$user_id = $_SESSION['user_id'];
$stmt_user = $pdo->prepare("SELECT fullname, profession, role FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Seuls les techniciens, artisans et entreprises peuvent gérer des services
$allowed_roles = ['technicien', 'artisan', 'entreprise'];
if (!in_array($user['role'], $allowed_roles)) {
    setFlashMessage("Seuls les professionnels peuvent gérer des services.", 'error');
    redirect('feed.php');
}

// Bloquer l'accès si l'onboarding n'est pas fini
requireOnboarding();

// Récupérer les services de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM services WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$services = $stmt->fetchAll();

$current_page = 'manage_services.php';
require_once 'includes/header.php';
?>

<div class="app-layout">
    <div class="container">
        <div class="layout-wrapper" style="grid-template-columns: 1fr;">
            <main class="main-content">
                <div style="max-width: 800px; margin: 0 auto;">
                    <div class="pro-section-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                            <div>
                                <h2 style="font-size: 24px; font-weight: 800; color: var(--dark-bg);">Gérer mes services</h2>
                                <p style="color: var(--text-muted); margin-top: 5px;">Ajoutez ou modifiez les prestations que vous proposez à vos clients.</p>
                            </div>
                            <button class="btn btn-register" onclick="openAddServiceModal()">
                                <i data-lucide="plus" style="width: 18px; margin-right: 8px;"></i>
                                Ajouter un service
                            </button>
                        </div>

                        <?php echo displayFlashMessage(); ?>

                        <div class="services-management-list">
                            <?php if (empty($services)): ?>
                                <div style="text-align: center; padding: 60px 20px; background: #F8FAFC; border-radius: 20px; border: 2px dashed #E2E8F0;">
                                    <div style="width: 64px; height: 64px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: var(--primary-blue); box-shadow: var(--shadow-sm);">
                                        <i data-lucide="briefcase" style="width: 32px; height: 32px;"></i>
                                    </div>
                                    <h3 style="font-size: 18px; font-weight: 700; color: var(--dark-bg);">Aucun service enregistré</h3>
                                    <p style="color: var(--text-muted); margin: 10px 0 25px;">Commencez par ajouter votre premier service pour attirer des clients.</p>
                                    <button class="btn btn-outline" onclick="openAddServiceModal()">Ajouter mon premier service</button>
                                </div>
                            <?php else: ?>
                                <div style="display: grid; gap: 16px;">
                                    <?php foreach ($services as $service): ?>
                                        <div class="service-pro-card" style="background: white; border: 1px solid #F1F5F9; transition: none; hover: none;">
                                            <div class="service-pro-icon"><i data-lucide="check-circle"></i></div>
                                            <div class="service-pro-info">
                                                <h4 style="font-size: 17px; font-weight: 800; color: var(--dark-bg);"><?php echo htmlspecialchars($service['name']); ?></h4>
                                                <p style="font-size: 14px; color: var(--text-muted);"><?php echo htmlspecialchars($service['description']); ?></p>
                                                <div style="margin-top: 10px; display: flex; gap: 15px;">
                                                    <span style="font-size: 13px; font-weight: 700; color: var(--primary-blue);">
                                                        <?php echo number_format($service['price_starting'], 0, ',', ' '); ?> FCFA
                                                    </span>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 10px;">
                                                <button class="btn-action-outline" style="width: 40px; height: 40px;" onclick="openEditServiceModal(<?php echo htmlspecialchars(json_encode($service)); ?>)" title="Modifier">
                                                    <i data-lucide="edit-2" style="width: 18px;"></i>
                                                </button>
                                                <button class="btn-action-outline" style="width: 40px; height: 40px; color: #EF4444;" onclick="confirmDeleteService(<?php echo $service['id']; ?>)" title="Supprimer">
                                                    <i data-lucide="trash-2" style="width: 18px;"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Modal: Ajouter / Modifier Service -->
<div id="serviceModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2 id="modalTitle">Ajouter un service</h2>
            <span class="close-modal" onclick="closeServiceModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="serviceForm" action="handle_service.php" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="service_id" id="serviceId" value="">
                
                <div class="form-group">
                    <label>Nom du service</label>
                    <input type="text" name="name" id="serviceName" placeholder="Ex: Installation de climatisation" required>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label>Description courte</label>
                    <textarea name="description" id="serviceDesc" rows="3" placeholder="Décrivez brièvement ce que comprend ce service..." required></textarea>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label>Prix de départ (FCFA)</label>
                    <input type="number" name="price_starting" id="servicePrice" placeholder="Ex: 25000" required>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top: 5px;">Le prix affiché sera "À partir de X FCFA".</p>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeServiceModal()">Annuler</button>
            <button type="submit" form="serviceForm" class="btn btn-register">Enregistrer le service</button>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();

    function openAddServiceModal() {
        document.getElementById('formAction').value = 'add';
        document.getElementById('modalTitle').innerText = 'Ajouter un service';
        document.getElementById('serviceForm').reset();
        document.getElementById('serviceModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function openEditServiceModal(service) {
        document.getElementById('formAction').value = 'edit';
        document.getElementById('serviceId').value = service.id;
        document.getElementById('modalTitle').innerText = 'Modifier le service';
        document.getElementById('serviceName').value = service.name;
        document.getElementById('serviceDesc').value = service.description;
        document.getElementById('servicePrice').value = service.price_starting;
        document.getElementById('serviceModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeServiceModal() {
        document.getElementById('serviceModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function confirmDeleteService(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce service ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'handle_service.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'service_id';
            idInput.value = id;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Fermer la modal en cliquant à l'extérieur
    window.onclick = function(event) {
        const modal = document.getElementById('serviceModal');
        if (event.target == modal) {
            closeServiceModal();
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>
