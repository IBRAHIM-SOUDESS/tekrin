<?php
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Rediriger vers l'onboarding si ce n'est pas fait
if (!isset($_SESSION['onboarding_completed']) || !$_SESSION['onboarding_completed']) {
    redirect('onboarding.php');
}

$user_id = $_SESSION['user_id'];

// Récupérer les infos de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

require_once 'includes/header.php';
?>

<div class="app-layout">
    <div class="container">
        <div class="layout-wrapper">
            
            <main class="main-content">
                <div style="max-width: 700px; margin: 0 auto;">
                    
                    <!-- Centre: Fil d'actualité -->
                    <div class="feed-main">
                        <!-- Create Post Placeholder -->
                        <div style="background: white; border-radius: 12px; padding: 15px; margin-bottom: 20px; box-shadow: var(--shadow-sm); border: 1px solid #e5e7eb;">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <div style="width: 45px; height: 45px; border-radius: 50%; overflow: hidden; background: #f3f4f6; flex-shrink: 0;">
                                    <?php 
                                    $avatar = $user['profile_picture'] ?? '';
                                    if ($avatar && strpos($avatar, 'assets/') !== 0) $avatar = 'assets/uploads/profiles/' . $avatar;
                                    if ($avatar): 
                                    ?>
                                        <img src="<?php echo $avatar; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f3f4f6; color: var(--primary-blue); font-weight: 700;">
                                            <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button style="flex: 1; text-align: left; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 30px; padding: 12px 20px; color: #6b7280; cursor: pointer; font-size: 14px; font-weight: 500;">
                                    Partagez une réalisation ou une actualité...
                                </button>
                            </div>
                        </div>

                        <!-- Welcome Post -->
                        <div style="background: white; border-radius: 12px; overflow: hidden; margin-bottom: 20px; box-shadow: var(--shadow-sm); border: 1px solid #e5e7eb;">
                            <div style="padding: 15px;">
                                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                                    <div style="width: 40px; height: 40px; border-radius: 5px; background: var(--grad-primary); display: flex; align-items: center; justify-content: center; color: white;">
                                        <i data-lucide="info" style="width: 20px;"></i>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 14px;">Équipe TEKRIN</h4>
                                        <p style="font-size: 12px; color: var(--text-muted);">À l'instant</p>
                                    </div>
                                </div>
                                <p style="font-size: 15px; line-height: 1.6; margin-bottom: 15px;">
                                    Bienvenue sur votre nouveau fil d'actualité professionnel ! Ici, vous pourrez bientôt :
                                </p>
                                <ul style="margin-left: 20px; margin-bottom: 15px; font-size: 14px; line-height: 1.8;">
                                    <li>Voir les réalisations d'autres professionnels</li>
                                    <li>Découvrir des opportunités dans votre ville</li>
                                    <li>Échanger avec la communauté Tekrin</li>
                                </ul>
                                <div style="background: var(--grad-soft); padding: 20px; border-radius: 12px; text-align: center;">
                                    <h3 style="color: var(--primary-blue); margin-bottom: 10px;">Votre profil est maintenant en ligne !</h3>
                                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px;">Assurez-vous d'avoir ajouté vos meilleures photos dans votre portfolio.</p>
                                    <a href="profile.php" class="btn btn-register" style="padding: 10px 20px; font-size: 13px;">Accéder à mon profil</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});
</script>
<script src="assets/js/app.js"></script>
</body>
</html>