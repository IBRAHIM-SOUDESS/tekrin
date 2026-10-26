<?php
/**
 * Header sophistiqué pour KAMAL Support Interne
 */
$current_page = basename($_SERVER['PHP_SELF']);

// Traduction des mois en français
$mois = [
    'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars', 
    'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin', 
    'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre', 
    'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'
];
$date_fr = date('d ') . $mois[date('F')] . date(' Y');
?>

<div class="top-nav">
    <div class="top-nav-content">
        <div class="top-nav-left">
            <span class="company-tag">ETS KAMAL</span>
            <span class="top-nav-divider"></span>
            <span class="top-nav-date"><?php echo $date_fr; ?></span>
        </div>
        <div class="top-nav-right">
            <?php if (isset($_SESSION['admin_logged_in'])): ?>
                <span class="session-info">Session Administrateur active</span>
            <?php else: ?>
                <span class="session-info">Portail sécurisé</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<header class="main-header">
    <div class="header-container">
        <div class="header-brand">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" width="28" height="28" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
            </div>
            <div class="brand-text">
                <h1 class="brand-name">ETS<span> KAMAL</span></h1>
                <p class="brand-slogan">OPTIMISEZ VOTRE OUTIL DE TRAVAIL.</p>
            </div>
        </div>
        
        <nav class="header-nav">
            <?php if (!isset($_SESSION['admin_logged_in'])): ?>
                <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Accueil
                </a>
                <a href="ticket.php" class="nav-item <?php echo ($current_page == 'ticket.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Nouvelle Demande
                </a>
                <a href="help.php" class="nav-item <?php echo ($current_page == 'help.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    Aide & Suivi
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['admin_logged_in'])): ?>
                <a href="admin.php" class="nav-item <?php echo ($current_page == 'admin.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Tableau de Bord
                </a>
                <a href="admin.php?action=logout" class="nav-item nav-item-logout">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Déconnexion
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-item nav-item-admin <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    ADMIN
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<script>
    // Version corrigée ✅ — bloque uniquement le pinch-zoom, pas le rendu 
    document.addEventListener('gesturestart', function(e) { 
        if (e.touches && e.touches.length > 1) { 
            e.preventDefault(); 
        } 
    });
</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    slate: {
                        900: '#0f172a',
                    }
                }
            }
        }
    }
</script>
