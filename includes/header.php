<?php
// Logique pour la sidebar
$current_page = basename($_SERVER['PHP_SELF']);
$hide_sidebar_pages = ['onboarding.php', 'wizard.php', 'login.php', 'register.php'];
// On ne montre la sidebar que si l'utilisateur est connecté ET a fini l'onboarding ET n'est pas sur une page de "passage"
$show_sidebar = isset($_SESSION['user_id']) && isOnboardingCompleted() && !in_array($current_page, $hide_sidebar_pages);

// Récupérer les informations de l'utilisateur si connecté et non déjà récupérées
if (isset($_SESSION['user_id']) && !isset($user)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TEKRIN - Le réseau des professionnels de proximité</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons (CDN for modern look) -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Tooltip styles -->
    <style>
        .tooltip {
            position: relative;
            display: inline-block;
        }
        .tooltip .tooltiptext {
            visibility: hidden;
            width: auto;
            background-color: #1E293B;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 6px 12px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .tooltip .tooltiptext::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: #1E293B transparent transparent transparent;
        }
        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Header au-dessus de tout -->
    <header class="header">
        <div class="header-container">
            <div class="header-left">
                <?php if (isset($_SESSION['user_id']) && $show_sidebar): ?>
                    <button class="mobile-menu-toggle" aria-label="Menu">
                        <i data-lucide="menu"></i>
                    </button>
                <?php endif; ?>
                
                <!-- Logo always visible (for logged-in and guests) -->
                <a href="<?php echo isset($_SESSION['user_id']) ? 'feed.php' : 'index.php'; ?>" class="logo-wrapper">
                    <div class="logo-symbol">
                        T
                    </div>
                    <span class="logo-text">Tekrin</span>
                </a>
                
                <!-- Search Bar on the left, next to logo -->
                <?php if (basename($_SERVER['PHP_SELF']) == 'pro.php' && isset($pro)): ?>
                    <!-- Professional Identity (keep it for pro page) -->
                <?php elseif (isset($_SESSION['user_id']) && isOnboardingCompleted()): ?>
                    <div class="search-wrapper" id="header-search-wrapper">
                        <i data-lucide="search" class="search-icon"></i>
                        <input type="text" placeholder="Rechercher un professionnel, un service..." class="search-input" id="header-search-input" autocomplete="off">
                    </div>
                <?php endif; ?>
            </div>

            <div class="header-center">
                <!-- Empty for now, keeps the layout balanced -->
            </div>

            <div class="header-right">
                <!-- Quick Navigation on the right -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <nav class="header-quick-nav-left">
                        <a href="feed.php" class="quick-link-mini tooltip <?php echo basename($_SERVER['PHP_SELF']) == 'feed.php' ? 'active' : ''; ?>">
                            <i data-lucide="home"></i>
                            <span class="tooltiptext">Accueil</span>
                        </a>
                        <a href="articles.php" class="quick-link-mini tooltip <?php echo basename($_SERVER['PHP_SELF']) == 'articles.php' ? 'active' : ''; ?>">
                            <i data-lucide="newspaper"></i>
                            <span class="tooltiptext">Articles</span>
                        </a>
                        <a href="#" class="quick-link-mini tooltip <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>" id="header-messages-btn">
                            <i data-lucide="message-square"></i>
                            <span class="tooltiptext">Messages</span>
                            <?php 
                            if (isset($_SESSION['user_id'])) {
                                $stmt_unread = $pdo->prepare("
                                    SELECT COUNT(*) 
                                    FROM messages m
                                    JOIN conversations c ON m.conversation_id = c.id
                                    WHERE (c.user_one = ? OR c.user_two = ?) 
                                    AND m.sender_id != ? 
                                    AND m.is_read = 0
                                ");
                                $stmt_unread->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
                                $total_unread = $stmt_unread->fetchColumn();
                                if ($total_unread > 0) {
                                    echo '<span class="quick-badge-mini" id="header-unread-count">' . $total_unread . '</span>';
                                }
                            }
                            ?>
                        </a>
                        <a href="#" class="quick-link-mini tooltip">
                            <i data-lucide="bell"></i>
                            <span class="tooltiptext">Notifications</span>
                            <span class="quick-badge-mini">4</span>
                        </a>
                    </nav>
                <?php endif; ?>
                
                <!-- User Profile or Guest Nav -->
                <?php if (isset($_SESSION['user_id']) && $user): ?>
                    <a href="profile.php" class="user-profile-simple" style="text-decoration: none;">
                        <div class="user-avatar-simple">
                            <?php 
                            $avatar = $user['profile_picture'] ?? '';
                            if ($avatar && strpos($avatar, 'assets/') !== 0) $avatar = 'assets/uploads/profiles/' . $avatar;
                            if ($avatar): 
                            ?>
                                <img src="<?php echo $avatar; ?>" alt="Avatar">
                            <?php else: ?>
                                <div class="avatar-initials"><?php echo strtoupper(substr($user['fullname'] ?? 'U', 0, 1)); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="user-meta-simple">
                            <h1 class="user-name-simple"><?php echo htmlspecialchars($user['fullname'] ?? ''); ?></h1>
                            <h2 class="user-role-simple"><?php echo htmlspecialchars($user['profession'] ?? $user['role'] ?? ''); ?></h2>
                        </div>
                    </a>
                <?php else: ?>
                    <nav class="guest-nav">
                        <a href="discover.php" class="btn btn-outline" style="padding: 8px 16px;">Découvrir</a>
                        <a href="articles.php" class="btn btn-outline" style="padding: 8px 16px;">Articles</a>
                        <a href="login.php" class="btn btn-login">Connexion</a>
                        <a href="register.php" class="btn btn-register">S'inscrire</a>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Search Overlay -->
    <div class="search-overlay" id="search-overlay">
        <div class="search-overlay-header">
            <div class="search-overlay-searchbar">
                <i data-lucide="search" class="search-overlay-icon"></i>
                <input type="text" id="search-overlay-input" placeholder="Rechercher un professionnel, un service, une ville..." autocomplete="off">
                <button class="search-overlay-close" id="search-overlay-close">
                    <i data-lucide="x"></i>
                </button>
            </div>
        </div>
        <div class="search-overlay-content">
            <div class="search-overlay-suggestions" id="search-overlay-suggestions">
                <div class="search-overlay-placeholder">
                <i data-lucide="search" style="width: 48px; height: 48px; color: #CBD5E1; margin-bottom: 16px;"></i>
                <h3>Commencez à taper pour rechercher</h3>
                <p>Trouvez des professionnels qualifiés près de chez vous</p>
                </div>
            </div>
        </div>
    </div>

    <div class="main-wrapper <?php echo $show_sidebar ? 'authenticated' : ''; ?>">
        <?php 
        if ($show_sidebar): 
        ?>
            <?php include 'includes/sidebar.php'; ?>
        <?php endif; ?>
        <main>
            <?php echo displayFlashMessage(); ?>