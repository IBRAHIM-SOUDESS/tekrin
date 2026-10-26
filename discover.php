<?php
require_once 'includes/auth.php';

$page_title = "Découvrir des professionnels - Tekrin";

// Récupérer les paramètres de recherche
$q = sanitize($_GET['q'] ?? '');
$city = sanitize($_GET['city'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'relevance');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Construction de la requête
$me = $_SESSION['user_id'] ?? null;

$query = "SELECT u.*, 
          (SELECT AVG(rating) FROM reviews WHERE pro_id = u.id) as avg_rating,
          (SELECT COUNT(*) FROM reviews WHERE pro_id = u.id) as reviews_count,
          (SELECT 1 FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following
          FROM users u 
          WHERE u.role = 'technicien' AND u.status = 'active'";

$params = [];
if ($me) $params[] = $me;

if (!empty($q)) {
    $query .= " AND (u.fullname LIKE ? OR u.profession LIKE ? OR u.services LIKE ? OR u.bio LIKE ?)";
    $searchTerm = "%$q%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($city)) {
    $query .= " AND u.city = ?";
    $params[] = $city;
}

if (!empty($category)) {
    $query .= " AND u.category = ?";
    $params[] = $category;
}

// Tri
switch ($sort) {
    case 'rating':
        $query .= " ORDER BY avg_rating DESC, reviews_count DESC";
        break;
    case 'newest':
        $query .= " ORDER BY u.id DESC";
        break;
    default:
        $query .= " ORDER BY u.id DESC"; // TODO: Implement better relevance
        break;
}

// Compte total pour la pagination
// Reconstruire la requête COUNT depuis zéro pour éviter les bugs de paramètres
$count_query = "SELECT COUNT(*) FROM users u WHERE u.role = 'technicien' AND u.status = 'active'";
$count_params = [];

if (!empty($q)) {
    $count_query .= " AND (u.fullname LIKE ? OR u.profession LIKE ? OR u.services LIKE ? OR u.bio LIKE ?)";
    $searchTerm = "%$q%";
    $count_params = array_merge($count_params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($city)) {
    $count_query .= " AND u.city = ?";
    $count_params[] = $city;
}

if (!empty($category)) {
    $count_query .= " AND u.category = ?";
    $count_params[] = $category;
}

$stmt_count = $pdo->prepare($count_query);
$stmt_count->execute($count_params);
$total_results = $stmt_count->fetchColumn();
$total_pages = ceil($total_results / $limit);

// Exécution finale avec limite
$query .= " LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pros = $stmt->fetchAll();

// Statistiques réelles (simulées pour l'exemple mais structurées)
$stats = [
    ['count' => '12 500', 'label' => 'Professionnels', 'icon' => 'users'],
    ['count' => '48', 'label' => 'Villes', 'icon' => 'map-pin'],
    ['count' => '35', 'label' => 'Catégories', 'icon' => 'layout'],
    ['count' => '5 200', 'label' => 'Avis Clients', 'icon' => 'star']
];

// Professionnels populaires (simulés)
$popular_pros = array_slice($pros, 0, 3); 

// Catégories populaires
$categories = [
    ['name' => 'Électricité', 'icon' => 'zap', 'slug' => 'electricite'],
    ['name' => 'Plomberie', 'icon' => 'droplets', 'slug' => 'plomberie'],
    ['name' => 'Menuiserie', 'icon' => 'hammer', 'slug' => 'menuiserie'],
    ['name' => 'Informatique', 'icon' => 'monitor', 'slug' => 'informatique'],
    ['name' => 'Mécanique', 'icon' => 'settings', 'slug' => 'mecanique'],
    ['name' => 'Maçonnerie', 'icon' => 'layers', 'slug' => 'maconnerie'],
    ['name' => 'Peinture', 'icon' => 'palette', 'slug' => 'peinture'],
    ['name' => 'Climatisation', 'icon' => 'snowflake', 'slug' => 'climatisation']
];

require_once 'includes/header.php';
?>

<div class="discover-page">
    <!-- Header Discovery Section -->
    <div class="discover-header-section">
        <div class="container">
            <div class="header-main-flex">
                <div class="header-text-content">
                    <h1>Découvrez des professionnels qualifiés</h1>
                    <p>Trouvez rapidement le professionnel idéal pour votre projet.</p>
                </div>
                
                <form action="discover.php" method="GET" class="premium-search-box">
                    <div class="search-input-wrapper">
                        <i data-lucide="search" class="input-icon"></i>
                        <input type="text" name="q" placeholder="Nom, profession, service..." value="<?php echo htmlspecialchars($q); ?>">
                    </div>
                    <div class="search-divider"></div>
                    <div class="search-input-wrapper city-wrapper">
                        <i data-lucide="map-pin" class="input-icon"></i>
                        <input type="text" name="city" placeholder="Ville" value="<?php echo htmlspecialchars($city); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-search-premium">
                        <span>Rechercher</span>
                    </button>
                </form>
            </div>

            <!-- Statistics Bar -->
            <div class="statistics-bar-card">
                <div class="stat-item">
                    <div class="stat-icon"><i data-lucide="users"></i></div>
                    <div class="stat-info">
                        <span class="stat-count">12 500</span>
                        <span class="stat-label">Professionnels</span>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-icon"><i data-lucide="map-pin"></i></div>
                    <div class="stat-info">
                        <span class="stat-count">48</span>
                        <span class="stat-label">Villes</span>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-icon"><i data-lucide="layout"></i></div>
                    <div class="stat-info">
                        <span class="stat-count">35</span>
                        <span class="stat-label">Catégories</span>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-icon"><i data-lucide="star"></i></div>
                    <div class="stat-info">
                        <span class="stat-count">5 200</span>
                        <span class="stat-label">Avis Clients</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section (Horizontal Scroll / Grid) -->
    <div class="categories-nav-section">
        <div class="container">
            <div class="categories-grid-nav">
                <a href="discover.php" class="category-card-premium <?php echo empty($category) ? 'active' : ''; ?>">
                    <span class="cat-icon">✨</span>
                    <span class="cat-name">Tous</span>
                </a>
                <a href="?category=electricite" class="category-card-premium <?php echo $category === 'electricite' ? 'active' : ''; ?>">
                    <span class="cat-icon">⚡</span>
                    <span class="cat-name">Électricité</span>
                </a>
                <a href="?category=plomberie" class="category-card-premium <?php echo $category === 'plomberie' ? 'active' : ''; ?>">
                    <span class="cat-icon">🚰</span>
                    <span class="cat-name">Plomberie</span>
                </a>
                <a href="?category=menuiserie" class="category-card-premium <?php echo $category === 'menuiserie' ? 'active' : ''; ?>">
                    <span class="cat-icon">🪚</span>
                    <span class="cat-name">Menuiserie</span>
                </a>
                <a href="?category=informatique" class="category-card-premium <?php echo $category === 'informatique' ? 'active' : ''; ?>">
                    <span class="cat-icon">💻</span>
                    <span class="cat-name">Informatique</span>
                </a>
                <a href="?category=mecanique" class="category-card-premium <?php echo $category === 'mecanique' ? 'active' : ''; ?>">
                    <span class="cat-icon">🔧</span>
                    <span class="cat-name">Mécanique</span>
                </a>
                <a href="?category=maconnerie" class="category-card-premium <?php echo $category === 'maconnerie' ? 'active' : ''; ?>">
                    <span class="cat-icon">🏗</span>
                    <span class="cat-name">Maçonnerie</span>
                </a>
                <a href="?category=peinture" class="category-card-premium <?php echo $category === 'peinture' ? 'active' : ''; ?>">
                    <span class="cat-icon">🎨</span>
                    <span class="cat-name">Peinture</span>
                </a>
                <a href="?category=climatisation" class="category-card-premium <?php echo $category === 'climatisation' ? 'active' : ''; ?>">
                    <span class="cat-icon">❄</span>
                    <span class="cat-name">Climatisation</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Trust & Benefits Bar -->
    <div class="trust-benefits-bar">
        <div class="container">
            <div class="benefits-flex">
                <div class="benefit-item">
                    <i data-lucide="shield-check"></i>
                    <span>Paiements sécurisés</span>
                </div>
                <div class="benefit-item">
                    <i data-lucide="clock"></i>
                    <span>Intervention rapide</span>
                </div>
                <div class="benefit-item">
                    <i data-lucide="award"></i>
                    <span>Qualité garantie</span>
                </div>
                <div class="benefit-item">
                    <i data-lucide="message-square"></i>
                    <span>Support 24/7</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container discover-main-layout">
        <!-- LEFT SIDE: Filters -->
        <aside class="discover-filters-sidebar">
            <div class="sticky-filter-card">
                <div class="filter-card-header">
                    <h3>Filtres</h3>
                    <button class="btn-reset-filters" onclick="window.location.href='discover.php'">Tout effacer</button>
                </div>

                <div class="filter-group">
                    <label>Catégories</label>
                    <select class="premium-select" onchange="window.location.href='?category=' + this.value">
                        <option value="">Toutes les catégories</option>
                        <option value="electricite" <?php echo $category === 'electricite' ? 'selected' : ''; ?>>Électricité</option>
                        <option value="plomberie" <?php echo $category === 'plomberie' ? 'selected' : ''; ?>>Plomberie</option>
                        <option value="menuiserie" <?php echo $category === 'menuiserie' ? 'selected' : ''; ?>>Menuiserie</option>
                        <option value="informatique" <?php echo $category === 'informatique' ? 'selected' : ''; ?>>Informatique</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Ville</label>
                    <div class="filter-search-wrapper">
                        <i data-lucide="map-pin"></i>
                        <input type="text" placeholder="Rechercher une ville..." value="<?php echo htmlspecialchars($city); ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <label>Disponibilité</label>
                    <div class="checkbox-list">
                        <label class="premium-checkbox">
                            <input type="checkbox">
                            <span class="checkmark"></span>
                            <span class="label-text">Disponible maintenant</span>
                        </label>
                        <label class="premium-checkbox">
                            <input type="checkbox">
                            <span class="checkmark"></span>
                            <span class="label-text">Disponible ce weekend</span>
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <label>Note minimale</label>
                    <div class="rating-filter">
                        <label class="premium-radio">
                            <input type="radio" name="min_rating">
                            <span class="radio-mark"></span>
                            <span class="label-text">4.5+ ⭐⭐⭐⭐⭐</span>
                        </label>
                        <label class="premium-radio">
                            <input type="radio" name="min_rating">
                            <span class="radio-mark"></span>
                            <span class="label-text">4.0+ ⭐⭐⭐⭐</span>
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <label>Expérience</label>
                    <div class="checkbox-list">
                        <label class="premium-checkbox">
                            <input type="checkbox">
                            <span class="checkmark"></span>
                            <span class="label-text">Junior (1-3 ans)</span>
                        </label>
                        <label class="premium-checkbox">
                            <input type="checkbox">
                            <span class="checkmark"></span>
                            <span class="label-text">Intermédiaire (3-7 ans)</span>
                        </label>
                        <label class="premium-checkbox">
                            <input type="checkbox">
                            <span class="checkmark"></span>
                            <span class="label-text">Expert (7+ ans)</span>
                        </label>
                    </div>
                </div>

                <div class="filter-group">
                    <label>Prix / Budget</label>
                    <div class="price-range-inputs">
                        <input type="number" placeholder="Min">
                        <span>-</span>
                        <input type="number" placeholder="Max">
                    </div>
                </div>

                <div class="filter-group">
                    <label>Statut Profil</label>
                    <div class="checkbox-list">
                        <label class="premium-checkbox">
                            <input type="checkbox" checked>
                            <span class="checkmark"></span>
                            <span class="label-text">✓ Profil vérifié</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Popular Widget in Sidebar -->
            <div class="popular-pros-widget-card">
                <div class="widget-header">
                    <h4>Professionnels populaires</h4>
                    <i data-lucide="trending-up"></i>
                </div>
                <div class="popular-list">
                    <?php foreach ($popular_pros as $pop): ?>
                        <div class="pop-item">
                            <img src="<?php echo get_user_avatar($pop); ?>" alt="">
                            <div class="pop-details">
                                <span class="pop-name"><?php echo htmlspecialchars($pop['fullname']); ?></span>
                                <span class="pop-job"><?php echo htmlspecialchars($pop['profession'] ?? 'Expert'); ?></span>
                                <div class="pop-rating">
                                    <i data-lucide="star"></i>
                                    <span><?php echo number_format($pop['avg_rating'] ?? 5.0, 1); ?></span>
                                </div>
                            </div>
                            <a href="pro.php?id=<?php echo $pop['id']; ?>" class="pop-btn"><i data-lucide="chevron-right"></i></a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- New Widget: Trending Skills -->
            <div class="sidebar-widget-premium trending-skills-card">
                <div class="widget-header">
                    <h4>Compétences en demande</h4>
                    <i data-lucide="zap"></i>
                </div>
                <div class="skills-tags-cloud">
                    <span class="skill-tag">Installation Solaire</span>
                    <span class="skill-tag">Domotique</span>
                    <span class="skill-tag">Réparation iPhone</span>
                    <span class="skill-tag">Plomberie d'urgence</span>
                    <span class="skill-tag">Peinture 3D</span>
                    <span class="skill-tag">Maintenance Groupe</span>
                </div>
            </div>

            <!-- New Widget: Help Card -->
            <div class="sidebar-help-card">
                <div class="help-card-content">
                    <div class="help-icon-circle">
                        <i data-lucide="help-circle"></i>
                    </div>
                    <h4>Besoin d'aide ?</h4>
                    <p>Nos conseillers vous aident à trouver le meilleur pro.</p>
                    <a href="contact.php" class="btn-help-link">Contacter le support</a>
                </div>
            </div>
        </aside>

        <!-- RIGHT SIDE: Professionals Grid -->
        <main class="discover-results-area">
            <!-- New Section: Featured Banner -->
            <div class="featured-promo-banner">
                <div class="promo-content">
                    <span class="promo-badge">Nouveau</span>
                    <h2>Garantie Tekrin Protect</h2>
                    <p>Tous vos travaux sont couverts jusqu'à 1 000 000 FCFA avec nos pros vérifiés.</p>
                </div>
                <div class="promo-action">
                    <a href="protect.php" class="btn-promo-outline">En savoir plus</a>
                </div>
            </div>

            <?php if (empty($pros)): ?>
                <div class="premium-empty-state">
                    <div class="empty-illustration">
                        <i data-lucide="search-x"></i>
                    </div>
                    <h2>Aucun professionnel trouvé</h2>
                    <p>Nous n'avons pas trouvé de résultats pour vos critères. Essayez d'élargir votre recherche ou de changer de ville.</p>
                    <a href="discover.php" class="btn btn-primary">Voir tous les professionnels</a>
                </div>
            <?php else: ?>
                <div class="professionals-premium-grid">
                    <?php foreach ($pros as $pro): ?>
                        <div class="pro-marketplace-card">
                            <!-- Cover Section -->
                            <div class="pro-card-cover-premium">
                                <?php 
                                // Image de réalisation pour la couverture
                                $cover_img = "https://images.unsplash.com/photo-1581094794329-c8112a89af12?auto=format&fit=crop&q=80&w=600"; 
                                ?>
                                <img src="<?php echo $cover_img; ?>" class="cover-image" alt="Réalisation">
                                <div class="cover-overlay"></div>
                                <div class="pro-card-rating-badge">
                                    <i data-lucide="star"></i>
                                    <span><?php echo number_format($pro['avg_rating'] ?? 5.0, 1); ?></span>
                                    <small>(<?php echo $pro['reviews_count']; ?>)</small>
                                </div>
                            </div>

                            <!-- Content Section -->
                            <div class="pro-card-body-premium">
                                <div class="pro-card-avatar-row">
                                    <div class="avatar-container-premium">
                                        <?php 
                                        $avatar = get_user_avatar($pro);
                                        if ($avatar): ?>
                                            <img src="<?php echo $avatar; ?>" alt="">
                                        <?php else: ?>
                                            <div class="avatar-initials"><?php echo strtoupper(substr($pro['fullname'], 0, 1)); ?></div>
                                        <?php endif; ?>
                                        <div class="verified-icon-mini" title="Profil vérifié">
                                            <i data-lucide="check"></i>
                                        </div>
                                    </div>
                                    <div class="pro-badges-row">
                                        <span class="pro-badge-mini verified">✓ Vérifié</span>
                                        <span class="pro-badge-mini response">⚡ Réponse rapide</span>
                                        <span class="pro-badge-mini top">🏆 Top professionnel</span>
                                    </div>
                                </div>

                                <div class="pro-main-details">
                                    <h3 class="pro-name-title"><?php echo htmlspecialchars($pro['fullname']); ?></h3>
                                    <p class="pro-job-title"><?php echo htmlspecialchars($pro['profession'] ?? 'Professionnel'); ?></p>
                                    
                                    <div class="pro-location-meta">
                                        <i data-lucide="map-pin"></i>
                                        <span><?php echo htmlspecialchars($pro['city'] ?? 'Cameroun'); ?></span>
                                    </div>

                                    <p class="pro-description-text">
                                        <?php 
                                        $bio = $pro['bio'] ?? 'Professionnel qualifié et rigoureux disponible pour tous vos travaux et projets techniques.';
                                        echo mb_strimwidth(htmlspecialchars($bio), 0, 100, "..."); 
                                        ?>
                                    </p>
                                </div>

                                <div class="pro-card-footer-premium">
                                    <div class="pro-card-actions-flex">
                                        <a href="pro.php?id=<?php echo $pro['id']; ?>" class="btn btn-view-profile-premium">
                                            <span>Voir le profil</span>
                                        </a>
                                        <button class="btn-follow-pro <?php echo $pro['is_following'] ? 'following' : ''; ?>" data-user-id="<?php echo $pro['id']; ?>" data-is-following="<?php echo $pro['is_following'] ? 'true' : 'false'; ?>">
                                            <?php echo $pro['is_following'] ? 'Suivi' : 'Suivre'; ?>
                                        </button>
                                        <a href="api/start_conversation.php?with=<?php echo $pro['id']; ?>" class="btn-quick-message" title="Envoyer un message">
                                            <i data-lucide="message-square"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination Premium -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container-premium">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&q=<?php echo urlencode($q); ?>" class="pagination-arrow"><i data-lucide="chevron-left"></i></a>
                        <?php endif; ?>

                        <div class="pagination-pages">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&q=<?php echo urlencode($q); ?>" class="pagination-page <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&q=<?php echo urlencode($q); ?>" class="pagination-arrow"><i data-lucide="chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- New Bottom Section: How it works (Small) -->
            <div class="discovery-how-it-works">
                <div class="how-header">
                    <h3>Comment ça marche ?</h3>
                    <p>3 étapes simples pour réaliser vos projets</p>
                </div>
                <div class="how-steps">
                    <div class="how-step">
                        <div class="step-num">1</div>
                        <div class="step-text">
                            <strong>Recherchez</strong>
                            <span>Trouvez le pro idéal</span>
                        </div>
                    </div>
                    <div class="how-step">
                        <div class="step-num">2</div>
                        <div class="step-text">
                            <strong>Comparez</strong>
                            <span>Consultez les avis</span>
                        </div>
                    </div>
                    <div class="how-step">
                        <div class="step-num">3</div>
                        <div class="step-text">
                            <strong>Réservez</strong>
                            <span>Lancez votre projet</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* CSS Refactor for Discover Premium Marketplace */
.discover-page { background: #F8FAFC; min-height: 100vh; padding-bottom: 80px; }

/* Header Section */
.discover-header-section { 
    background: white; 
    padding: 40px 0 32px; 
    border-bottom: 1px solid #E2E8F0;
    background-image: radial-gradient(#1565C0 0.5px, transparent 0.5px);
    background-size: 24px 24px;
    background-color: #ffffff;
}
.header-main-flex { display: flex; justify-content: space-between; align-items: flex-end; gap: 40px; margin-bottom: 32px; position: relative; z-index: 2; }
.header-text-content h1 { font-size: 32px; font-weight: 900; color: #0F172A; margin-bottom: 8px; letter-spacing: -0.5px; }
.header-text-content p { color: #64748B; font-size: 17px; font-weight: 500; }

.premium-search-box { 
    flex: 1; 
    max-width: 700px; 
    background: white; 
    padding: 8px; 
    border-radius: 20px; 
    display: flex; 
    align-items: center; 
    border: 1px solid #E2E8F0;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}
.search-input-wrapper { flex: 1; display: flex; align-items: center; padding: 0 16px; gap: 12px; }
.search-input-wrapper.city-wrapper { flex: 0.6; }
.input-icon { color: #94A3B8; width: 20px; }
.search-input-wrapper input { border: none; background: transparent; padding: 12px 0; width: 100%; font-size: 15px; font-weight: 600; color: #1E293B; outline: none; }
.search-divider { width: 1px; height: 30px; background: #CBD5E1; }
.btn-search-premium { padding: 12px 28px; border-radius: 14px; font-weight: 800; font-size: 14px; box-shadow: 0 4px 12px rgba(21, 101, 192, 0.2); }

/* Statistics Bar Card */
.statistics-bar-card { 
    background: white; 
    border: 1px solid #E2E8F0; 
    border-radius: 24px; 
    padding: 24px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center;
    box-shadow: var(--shadow-sm);
}
.stat-item { display: flex; align-items: center; gap: 16px; flex: 1; justify-content: center; }
.stat-divider { width: 1px; height: 40px; background: #F1F5F9; }
.stat-icon { width: 48px; height: 48px; background: #EEF2FF; color: var(--primary-blue); border-radius: 14px; display: flex; align-items: center; justify-content: center; }
.stat-icon i { width: 24px; height: 24px; }
.stat-count { display: block; font-size: 20px; font-weight: 900; color: #1E293B; }
.stat-label { display: block; font-size: 13px; color: #64748B; font-weight: 600; }

/* Categories Nav */
.categories-nav-section { background: white; padding: 16px 0; border-bottom: 1px solid #E2E8F0; position: sticky; top: 0; z-index: 100; }
.categories-grid-nav { display: flex; gap: 12px; overflow-x: auto; scrollbar-width: none; padding: 4px 0; }
.categories-grid-nav::-webkit-scrollbar { display: none; }

.category-card-premium { 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    padding: 10px 20px; 
    background: #F8FAFC; 
    border: 1px solid #E2E8F0; 
    border-radius: 16px; 
    text-decoration: none; 
    color: #475569; 
    font-size: 14px; 
    font-weight: 700; 
    white-space: nowrap; 
    transition: all 0.2s; 
}
.category-card-premium:hover { background: #F1F5F9; border-color: #CBD5E1; color: #1E293B; }
.category-card-premium.active { background: var(--primary-blue); color: white; border-color: var(--primary-blue); box-shadow: 0 4px 12px rgba(21, 101, 192, 0.2); }
.cat-icon { font-size: 18px; }

/* Trust Benefits Bar */
.trust-benefits-bar { background: #F1F5F9; padding: 12px 0; border-bottom: 1px solid #E2E8F0; }
.benefits-flex { display: flex; justify-content: center; gap: 48px; }
.benefit-item { display: flex; align-items: center; gap: 10px; color: #64748B; font-size: 13px; font-weight: 700; }
.benefit-item i { width: 16px; height: 16px; color: var(--primary-blue); }

/* Main Layout */
.discover-main-layout { display: grid; grid-template-columns: 300px 1fr; gap: 40px; margin-top: 40px; }

/* Filters Sidebar */
.discover-filters-sidebar { position: sticky; top: 100px; height: fit-content; display: flex; flex-direction: column; gap: 24px; }
.sticky-filter-card { background: white; border-radius: 24px; border: 1px solid #E2E8F0; padding: 24px; box-shadow: var(--shadow-sm); }
.filter-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.filter-card-header h3 { font-size: 18px; font-weight: 800; color: #1E293B; }
.btn-reset-filters { background: none; border: none; color: var(--primary-blue); font-size: 13px; font-weight: 700; cursor: pointer; }

.filter-group { margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #F1F5F9; }
.filter-group:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.filter-group label { display: block; font-size: 13px; font-weight: 800; color: #1E293B; margin-bottom: 16px; }

.premium-select { width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 14px; font-weight: 600; color: #1E293B; outline: none; }

.filter-search-wrapper { position: relative; display: flex; align-items: center; }
.filter-search-wrapper i { position: absolute; left: 12px; width: 16px; color: #94A3B8; }
.filter-search-wrapper input { width: 100%; padding: 12px 12px 12px 36px; border-radius: 12px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 14px; outline: none; }

.checkbox-list { display: flex; flex-direction: column; gap: 12px; }
.premium-checkbox, .premium-radio { display: flex; align-items: center; gap: 12px; cursor: pointer; }
.premium-checkbox input, .premium-radio input { display: none; }
.checkmark, .radio-mark { width: 20px; height: 20px; border: 2px solid #E2E8F0; border-radius: 6px; position: relative; transition: all 0.2s; }
.radio-mark { border-radius: 50%; }
.premium-checkbox input:checked + .checkmark, .premium-radio input:checked + .radio-mark { background: var(--primary-blue); border-color: var(--primary-blue); }
.premium-checkbox input:checked + .checkmark::after { content: '✓'; color: white; font-size: 12px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
.premium-radio input:checked + .radio-mark::after { content: ''; width: 8px; height: 8px; background: white; border-radius: 50%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }

.price-range-inputs { display: flex; align-items: center; gap: 10px; }
.price-range-inputs input { width: 100%; padding: 10px; border-radius: 10px; border: 1px solid #E2E8F0; background: #F8FAFC; font-size: 13px; outline: none; }

/* Popular Widget */
.popular-pros-widget-card { background: white; border-radius: 24px; border: 1px solid #E2E8F0; padding: 24px; box-shadow: var(--shadow-sm); }
.widget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.widget-header h4 { font-size: 15px; font-weight: 800; color: #1E293B; }
.widget-header i { color: var(--primary-blue); width: 18px; }

.pop-item { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; padding: 8px; border-radius: 16px; transition: background 0.2s; }
.pop-item:last-child { margin-bottom: 0; }
.pop-item:hover { background: #F8FAFC; }
.pop-item img { width: 44px; height: 44px; border-radius: 12px; object-fit: cover; }
.pop-details { flex: 1; min-width: 0; }
.pop-name { display: block; font-size: 14px; font-weight: 700; color: #1E293B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pop-job { display: block; font-size: 12px; color: #64748B; margin-bottom: 2px; }
.pop-rating { display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700; color: #F59E0B; }
.pop-rating i { width: 12px; height: 12px; fill: #F59E0B; }
.pop-btn { width: 32px; height: 32px; border-radius: 10px; background: #F1F5F9; display: flex; align-items: center; justify-content: center; color: #1E293B; transition: all 0.2s; }
.pop-btn:hover { background: var(--primary-blue); color: white; }

/* New Sidebar Widgets */
.sidebar-widget-premium { background: white; border-radius: 24px; border: 1px solid #E2E8F0; padding: 24px; box-shadow: var(--shadow-sm); }
.trending-skills-card { background: linear-gradient(to bottom right, #ffffff, #F8FAFC); }
.skills-tags-cloud { display: flex; flex-wrap: wrap; gap: 8px; }
.skill-tag { background: #F1F5F9; color: #475569; padding: 6px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; transition: all 0.2s; cursor: pointer; border: 1px solid #E2E8F0; }
.skill-tag:hover { background: #EEF2FF; color: var(--primary-blue); border-color: #CBD5E1; transform: translateY(-2px); }

.sidebar-help-card { background: var(--grad-primary); border-radius: 24px; padding: 24px; color: white; position: relative; overflow: hidden; }
.sidebar-help-card::before { content: ''; position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; }
.help-card-content { position: relative; z-index: 2; text-align: center; }
.help-icon-circle { width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
.help-icon-circle i { width: 24px; height: 24px; }
.sidebar-help-card h4 { font-size: 18px; font-weight: 800; margin-bottom: 8px; }
.sidebar-help-card p { font-size: 13px; opacity: 0.9; margin-bottom: 20px; line-height: 1.4; }
.btn-help-link { display: inline-block; background: white; color: var(--primary-blue); padding: 10px 20px; border-radius: 12px; text-decoration: none; font-size: 13px; font-weight: 800; transition: all 0.2s; }
.btn-help-link:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

/* Featured Promo Banner */
.featured-promo-banner { 
    background: #0F172A; 
    border-radius: 28px; 
    padding: 32px 40px; 
    margin-bottom: 40px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    color: white; 
    background-image: linear-gradient(45deg, rgba(21, 101, 192, 0.2) 0%, transparent 100%);
    position: relative;
    overflow: hidden;
}
.featured-promo-banner::after { content: '🛡️'; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 120px; opacity: 0.05; pointer-events: none; }
.promo-content h2 { font-size: 24px; font-weight: 900; margin-bottom: 8px; }
.promo-content p { font-size: 15px; opacity: 0.8; max-width: 500px; }
.promo-badge { background: var(--primary-blue); color: white; padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 900; text-transform: uppercase; margin-bottom: 12px; display: inline-block; letter-spacing: 1px; }
.btn-promo-outline { border: 2px solid rgba(255,255,255,0.2); color: white; padding: 12px 28px; border-radius: 14px; text-decoration: none; font-weight: 800; font-size: 14px; transition: all 0.2s; }
.btn-promo-outline:hover { background: white; color: #0F172A; border-color: white; }

/* How it works section */
.discovery-how-it-works { background: white; border-radius: 28px; border: 1px solid #E2E8F0; padding: 32px; margin-top: 60px; }
.how-header { text-align: center; margin-bottom: 32px; }
.how-header h3 { font-size: 20px; font-weight: 800; color: #1E293B; margin-bottom: 4px; }
.how-header p { font-size: 14px; color: #64748B; }
.how-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
.how-step { display: flex; align-items: center; gap: 16px; padding: 16px; background: #F8FAFC; border-radius: 20px; border: 1px solid #F1F5F9; }
.step-num { width: 36px; height: 36px; background: var(--primary-blue); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0; }
.step-text { display: flex; flex-direction: column; }
.step-text strong { font-size: 15px; color: #1E293B; }
.step-text span { font-size: 12px; color: #64748B; }

/* Professionals Grid */
.professionals-premium-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }

/* Pro Card */
.pro-marketplace-card { background: white; border-radius: 28px; border: 1px solid #E2E8F0; overflow: hidden; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; position: relative; }
.pro-marketplace-card:hover { transform: translateY(-10px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1); border-color: #CBD5E1; }

.pro-card-cover-premium { height: 160px; position: relative; overflow: hidden; }
.cover-image { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
.pro-marketplace-card:hover .cover-image { transform: scale(1.1); }
.cover-overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%); }

.pro-card-rating-badge { position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); padding: 6px 12px; border-radius: 50px; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.pro-card-rating-badge i { width: 14px; height: 14px; fill: #F59E0B; color: #F59E0B; }
.pro-card-rating-badge span { font-size: 14px; font-weight: 800; color: #1E293B; }
.pro-card-rating-badge small { font-size: 11px; color: #64748B; font-weight: 600; }

.pro-card-body-premium { padding: 0 24px 24px; flex: 1; display: flex; flex-direction: column; }
.pro-card-avatar-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: -35px; margin-bottom: 20px; position: relative; z-index: 5; }

.avatar-container-premium { width: 70px; height: 70px; border-radius: 22px; border: 4px solid white; background: #F1F5F9; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.1); position: relative; }
.avatar-container-premium img { width: 100%; height: 100%; object-fit: cover; }
.avatar-initials { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; color: var(--primary-blue); }
.verified-icon-mini { position: absolute; bottom: -4px; right: -4px; background: #10B981; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.verified-icon-mini i { width: 12px; height: 12px; stroke-width: 4; }

.pro-badges-row { display: flex; flex-direction: column; gap: 4px; align-items: flex-end; }
.pro-badge-mini { font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
.pro-badge-mini.verified { background: #ECFDF5; color: #10B981; border: 1px solid #D1FAE5; }
.pro-badge-mini.response { background: #FFF7ED; color: #F59E0B; border: 1px solid #FFEDD5; }
.pro-badge-mini.top { background: #EEF2FF; color: #6366F1; border: 1px solid #E0E7FF; }

.pro-name-title { font-size: 18px; font-weight: 900; color: #0F172A; margin-bottom: 2px; }
.pro-job-title { font-size: 14px; font-weight: 700; color: var(--primary-blue); margin-bottom: 12px; }

.pro-location-meta { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748B; font-weight: 600; margin-bottom: 16px; }
.pro-location-meta i { width: 15px; height: 15px; color: #94A3B8; }

.pro-description-text { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 24px; flex: 1; }

.pro-card-footer-premium { border-top: 1px solid #F1F5F9; padding-top: 20px; }
.pro-card-actions-flex { display: flex; gap: 12px; align-items: center; }
.btn-view-profile-premium { flex: 1; background: var(--grad-primary); color: white; border-radius: 14px; padding: 14px; font-weight: 800; display: flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none; font-size: 14px; box-shadow: 0 4px 12px rgba(21, 101, 192, 0.2); }
.btn-view-profile-premium:hover { transform: scale(1.02); filter: brightness(1.1); }
.btn-quick-message { width: 48px; height: 48px; border-radius: 14px; background: #F1F5F9; color: var(--primary-blue); display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s; border: 1px solid #E2E8F0; }
.btn-quick-message:hover { background: var(--primary-blue); color: white; transform: scale(1.05); }
.btn-quick-message i { width: 20px; height: 20px; }

/* Pagination */
.pagination-container-premium { margin-top: 60px; display: flex; align-items: center; justify-content: center; gap: 12px; }
.pagination-page, .pagination-arrow { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 14px; font-weight: 800; color: #64748B; background: white; border: 1px solid #E2E8F0; transition: all 0.2s; }
.pagination-page.active { background: var(--primary-blue); color: white; border-color: var(--primary-blue); box-shadow: 0 4px 12px rgba(21, 101, 192, 0.2); }
.pagination-page:hover:not(.active), .pagination-arrow:hover { background: #F1F5F9; border-color: #CBD5E1; color: #1E293B; }

/* Responsive */
@media (max-width: 1200px) {
    .professionals-premium-grid { grid-template-columns: repeat(2, 1fr); }
    .statistics-bar-card { flex-wrap: wrap; gap: 20px; }
    .stat-item { flex: 1 1 40%; }
    .stat-divider { display: none; }
}

@media (max-width: 992px) {
    .discover-main-layout { grid-template-columns: 1fr; }
    .discover-filters-sidebar { display: none; }
    .header-main-flex { flex-direction: column; align-items: flex-start; gap: 30px; }
}

@media (max-width: 640px) {
    .professionals-premium-grid { grid-template-columns: 1fr; }
    .premium-search-box { flex-direction: column; padding: 12px; border-radius: 24px; }
    .search-divider { display: none; }
    .search-input-wrapper { width: 100%; border-bottom: 1px solid #E2E8F0; padding: 8px 0; }
    .btn-search-premium { width: 100%; margin-top: 10px; }
}

.btn-follow-pro {
    padding: 8px 16px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 13px;
    background: #EEF2FF;
    color: var(--primary-blue);
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-follow-pro:hover {
    background: var(--primary-blue);
    color: white;
}
.btn-follow-pro.following {
    background: var(--primary-blue);
    color: white;
}
.btn-follow-pro.following:hover {
    opacity: 0.9;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();

    // Handle follow/unfollow
    document.querySelectorAll('.btn-follow-pro').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const userId = btn.dataset.userId;
            const isFollowing = btn.dataset.isFollowing === 'true';
            
            try {
                const response = await fetch('api/toggle_follow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                const data = await response.json();
                
                if (data.success) {
                    btn.dataset.isFollowing = data.following ? 'true' : 'false';
                    btn.classList.toggle('following', data.following);
                    btn.textContent = data.following ? 'Suivi' : 'Suivre';
                }
            } catch (err) {
                console.error('Error:', err);
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
