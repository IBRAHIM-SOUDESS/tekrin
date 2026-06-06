<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="logo-wrapper">
            <div class="logo-symbol">
                <svg viewBox="0 0 100 100" class="logo-svg" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="tekrin-sidebar-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#1565C0;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#6A1B9A;stop-opacity:1" />
                        </linearGradient>
                        <mask id="bubble-mask-sidebar">
                            <rect width="100" height="100" fill="white" />
                            <circle cx="72" cy="72" r="16" fill="black" />
                            <path d="M 58 72 L 72 86 L 72 72 Z" fill="black" />
                        </mask>
                    </defs>
                    <g mask="url(#bubble-mask-sidebar)">
                        <path d="M 15 35 C 15 25, 25 20, 35 20 L 85 20 C 95 20, 95 35, 85 40 L 55 45 C 45 48, 40 55, 40 65 L 40 80 C 40 90, 25 90, 20 80 L 20 45 Z" fill="url(#tekrin-sidebar-grad)" />
                    </g>
                </svg>
            </div>
            <span class="logo-text">Tekrin</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group">
            <div class="nav-label">Menu Principal</div>
            <a href="feed.php" class="nav-item <?php echo $current_page == 'feed.php' ? 'active' : ''; ?>">
                <i data-lucide="home"></i>
                <span>Accueil</span>
            </a>
            <a href="#" class="nav-item <?php echo $current_page == 'search.php' ? 'active' : ''; ?>">
                <i data-lucide="search"></i>
                <span>Recherche</span>
            </a>
            <a href="#" class="nav-item <?php echo $current_page == 'services.php' ? 'active' : ''; ?>">
                <i data-lucide="briefcase"></i>
                <span>Services</span>
            </a>
            <a href="#" class="nav-item <?php echo $current_page == 'publications.php' ? 'active' : ''; ?>">
                <i data-lucide="layout"></i>
                <span>Publications</span>
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Personnel</div>
            <a href="#" class="nav-item <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
                <i data-lucide="message-square"></i>
                <span>Messages</span>
            </a>
            <a href="#" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                <div class="nav-item-content">
                    <i data-lucide="bell"></i>
                    <span>Notifications</span>
                </div>
                <span class="sidebar-badge">4</span>
            </a>
            <a href="#" class="nav-item <?php echo $current_page == 'requests.php' ? 'active' : ''; ?>">
                <i data-lucide="clipboard-list"></i>
                <span>Mes demandes</span>
            </a>
            <a href="#" class="nav-item <?php echo $current_page == 'favorites.php' ? 'active' : ''; ?>">
                <i data-lucide="heart"></i>
                <span>Favoris</span>
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Compte</div>
            <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                <i data-lucide="user"></i>
                <span>Mon Profil</span>
            </a>
            <a href="profile_edit.php" class="nav-item <?php echo $current_page == 'profile_edit.php' ? 'active' : ''; ?>">
                <i data-lucide="settings"></i>
                <span>Paramètres</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-actions">
        <button class="btn btn-create-pub">
            <i data-lucide="plus"></i>
            <span>Créer une publication</span>
        </button>

        <div class="sidebar-separator"></div>

        <div class="dark-mode-toggle">
            <div class="toggle-info">
                <i data-lucide="moon"></i>
                <span>Mode sombre</span>
            </div>
            <label class="switch">
                <input type="checkbox" id="dark-mode-switch">
                <span class="slider round"></span>
            </label>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="premium-card">
            <div class="premium-content">
                <h4>Passez à Tekrin Premium</h4>
                <ul class="premium-benefits">
                    <li><i data-lucide="check"></i> Plus de visibilité</li>
                    <li><i data-lucide="check"></i> Badges exclusifs</li>
                    <li><i data-lucide="check"></i> Statistiques avancées</li>
                    <li><i data-lucide="check"></i> Support prioritaire</li>
                </ul>
                <button class="btn btn-premium">Découvrir</button>
            </div>
        </div>
    </div>
</aside>