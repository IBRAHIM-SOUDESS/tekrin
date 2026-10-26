<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <nav class="sidebar-nav">
        <div class="nav-group">
            <div class="nav-label">Menu Principal</div>
            <a href="discover.php" class="nav-item tooltip <?php echo $current_page == 'discover.php' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <i data-lucide="search"></i>
                    <span>Découvrir</span>
                </div>
                <span class="tooltiptext">Trouver des professionnels</span>
            </a>
            <?php 
            $allowed_roles = ['technicien', 'artisan', 'entreprise'];
            if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $allowed_roles)): 
            ?>
            <a href="incoming_requests.php" class="nav-item tooltip <?php echo $current_page == 'incoming_requests.php' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <i data-lucide="inbox"></i>
                    <span>Demandes reçues</span>
                </div>
                <span class="tooltiptext">Voir les demandes</span>
            </a>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'client'): ?>
            <a href="my_requests.php" class="nav-item tooltip <?php echo $current_page == 'my_requests.php' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <i data-lucide="clipboard-list"></i>
                    <span>Mes demandes</span>
                </div>
                <span class="tooltiptext">Voir vos demandes</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="nav-group">
            <div class="nav-label">Compte</div>
            <a href="profile.php" class="nav-item tooltip <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <i data-lucide="user"></i>
                    <span>Mon Profil</span>
                </div>
                <span class="tooltiptext">Voir votre profil</span>
            </a>
            <a href="profile_edit.php" class="nav-item tooltip <?php echo $current_page == 'profile_edit.php' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <i data-lucide="settings"></i>
                    <span>Paramètres</span>
                </div>
                <span class="tooltiptext">Modifier vos paramètres</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-actions">
        <button class="btn btn-create-pub" onclick="openPostModal()">
            <i data-lucide="plus"></i>
            <span>Créer une publication</span>
        </button>
    </div>
</aside>
