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
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php include 'includes/sidebar.php'; ?>
    <?php endif; ?>

    <div class="main-wrapper <?php echo isset($_SESSION['user_id']) ? 'authenticated' : ''; ?>">
        <header class="header">
            <div class="header-container">
                <div class="header-left">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="mobile-menu-toggle" aria-label="Menu">
                            <i data-lucide="menu"></i>
                        </button>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'feed.php'): ?>
                            <div class="search-wrapper">
                                <i data-lucide="search" class="search-icon"></i>
                                <input type="text" placeholder="Rechercher sur Tekrin..." class="search-input">
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="index.php" class="logo-wrapper">
                            <div class="logo-symbol">
                                <svg viewBox="0 0 100 100" class="logo-svg" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="tekrin-header-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" style="stop-color:#1565C0;stop-opacity:1" />
                                            <stop offset="100%" style="stop-color:#6A1B9A;stop-opacity:1" />
                                        </linearGradient>
                                        <mask id="bubble-mask-header">
                                            <rect width="100" height="100" fill="white" />
                                            <circle cx="72" cy="72" r="16" fill="black" />
                                            <path d="M 58 72 L 72 86 L 72 72 Z" fill="black" />
                                        </mask>
                                    </defs>
                                    <g mask="url(#bubble-mask-header)">
                                        <path d="M 15 35 C 15 25, 25 20, 35 20 L 85 20 C 95 20, 95 35, 85 40 L 55 45 C 45 48, 40 55, 40 65 L 40 80 C 40 90, 25 90, 20 80 L 20 45 Z" fill="url(#tekrin-header-grad)" />
                                    </g>
                                </svg>
                            </div>
                            <span class="logo-text">Tekrin</span>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="header-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <nav class="header-quick-nav">
                            <a href="feed.php" class="quick-link <?php echo basename($_SERVER['PHP_SELF']) == 'feed.php' ? 'active' : ''; ?>">
                                <i data-lucide="home"></i>
                                <span>Accueil</span>
                            </a>
                            <a href="#" class="quick-link">
                                <i data-lucide="briefcase"></i>
                                <span>Services</span>
                            </a>
                            <a href="#" class="quick-link">
                                <i data-lucide="message-square"></i>
                                <span>Messages</span>
                            </a>
                            <a href="#" class="quick-link">
                                <i data-lucide="bell"></i>
                                <span>Notifications</span>
                                <span class="quick-badge">4</span>
                            </a>
                        </nav>
                    <?php endif; ?>
                </div>

                <div class="header-right">
                    <?php if (isset($_SESSION['user_id'])): 
                        if (!isset($user)) {
                            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $user = $stmt->fetch();
                        }
                    ?>
                        <div class="user-profile-simple">
                            <div class="user-avatar-simple">
                                <?php 
                                $avatar = $user['profile_picture'] ?? '';
                                if ($avatar && strpos($avatar, 'assets/') !== 0) $avatar = 'assets/uploads/profiles/' . $avatar;
                                if ($avatar): 
                                ?>
                                    <img src="<?php echo $avatar; ?>" alt="Avatar">
                                <?php else: ?>
                                    <div class="avatar-initials"><?php echo strtoupper(substr($user['fullname'], 0, 1)); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="user-meta-simple">
                                <h1 class="user-name-simple"><?php echo htmlspecialchars($user['fullname']); ?></h1>
                                <h2 class="user-role-simple"><?php echo htmlspecialchars($user['profession'] ?? $user['role']); ?></h2>
                            </div>
                        </div>
                    <?php else: ?>
                        <nav class="guest-nav">
                            <a href="login.php" class="btn btn-login">Connexion</a>
                            <a href="register.php" class="btn btn-register">S'inscrire</a>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        <main>
            <?php echo displayFlashMessage(); ?>