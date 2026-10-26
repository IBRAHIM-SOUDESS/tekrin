<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$page_title = "Découvrir les articles";

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

try {
    $where = ['status = ?'];
    $params = ['published'];
    
    if ($search) {
        $where[] = "(title LIKE ? OR excerpt LIKE ? OR content LIKE ?";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if ($category) {
        $where[] = "category = ?";
        $params[] = $category;
    }
    
    $where_clause = implode(' AND ', $where);
    
    $count_sql = "SELECT COUNT(*) as total FROM articles WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total = $count_stmt->fetch()['total'];
    $total_pages = ceil($total / $per_page);
    
    $sql = "SELECT a.*, u.fullname as author_name, u.profile_picture as author_avatar 
            FROM articles a 
            LEFT JOIN users u ON a.author_id = u.id 
            WHERE $where_clause 
            ORDER BY a.is_featured DESC, a.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$per_page, $offset]));
    $articles = $stmt->fetchAll();
    
    $categories_sql = "SELECT DISTINCT category FROM articles WHERE status = 'published' ORDER BY category";
    $categories_stmt = $pdo->query($categories_sql);
    $categories = $categories_stmt->fetchAll();
    
    $featured_sql = "SELECT a.*, u.fullname as author_name 
                     FROM articles a 
                     LEFT JOIN users u ON a.author_id = u.id 
                     WHERE a.is_featured = 1 AND a.status = 'published' 
                     ORDER BY a.created_at DESC LIMIT 3";
    $featured_stmt = $pdo->query($featured_sql);
    $featured = $featured_stmt->fetchAll();
    
    $popular_sql = "SELECT a.*, u.fullname as author_name 
                    FROM articles a 
                    LEFT JOIN users u ON a.author_id = u.id 
                    WHERE a.status = 'published' 
                    ORDER BY a.views DESC LIMIT 5";
    $popular_stmt = $pdo->query($popular_sql);
    $popular = $popular_stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}
?>
<?php require 'includes/header.php'; ?>

<div class="app-layout">
    <div class="container">
        <div class="layout-wrapper">
            <?php if (isLoggedIn()): ?>
                <?php require 'includes/sidebar.php'; ?>
            <?php endif; ?>
            <main class="main-content">
                <div class="discover-hero">
                    <h1>Découvrir nos articles</h1>
                    <p>Conseils, astuces et guides pour vos projets de bricolage et de maintenance</p>
                </div>
                
                <div class="discover-search">
                    <div class="search-box">
                        <i data-lucide="search"></i>
                        <input type="text" id="searchInput" placeholder="Rechercher un article..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary" onclick="performSearch()">Rechercher</button>
                    </div>
                </div>
                
                <div class="discover-filters">
                    <div class="filters-grid">
                        <div class="filter-group">
                        <label>Catégorie</label>
                        <select id="categoryFilter" onchange="applyFilters()">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category == $cat['category'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    </div>
                </div>
                
                <?php if ($featured && count($featured) > 0): ?>
                    <section class="discover-section">
                        <h2 class="section-title">À la une</h2>
                        <div class="featured-grid">
                            <?php foreach ($featured as $article): ?>
                                <div class="featured-article">
                                    <div class="featured-image">
                                        <?php if ($article['cover_image']): ?>
                                            <img src="<?= htmlspecialchars($article['cover_image']) ?>" alt="">
                                        <?php else: ?>
                                            <div class="no-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                                <i data-lucide="newspaper" style="width: 64px; height: 64px; color: white;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="featured-badge">À la une</div>
                                    </div>
                                    <div class="featured-content">
                                        <span class="article-category"><?= htmlspecialchars($article['category']) ?></span>
                                        <h3 class="featured-title"><?= htmlspecialchars($article['title']) ?></h3>
                                        <p class="article-excerpt"><?= htmlspecialchars(substr($article['excerpt'] ?: strip_tags($article['content']), 0, 200)) ?>...</p>
                                        <div class="article-meta">
                                            <span><?= htmlspecialchars($article['author_name']) ?></span>
                                            <span>•</span>
                                            <span><?= date('d/m/Y', strtotime($article['created_at'])) ?></span>
                                            <span>•</span>
                                            <span><i data-lucide="eye" style="width: 16px; height: 16px;"></i> <?= $article['views'] ?> vues</span>
                                        </div>
                                        <a href="article.php?id=<?= $article['id'] ?>" class="btn btn-primary" style="display: inline-block; margin-top: 16px;">Lire l'article</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <section class="discover-section">
                    <h2 class="section-title">Tous les articles</h2>
                    <div class="articles-grid">
                        <?php if (count($articles) > 0): ?>
                            <?php foreach ($articles as $article): ?>
                                <div class="article-card">
                                    <div class="article-image">
                                        <?php if ($article['cover_image']): ?>
                                            <img src="<?= htmlspecialchars($article['cover_image']) ?>" alt="">
                                        <?php else: ?>
                                            <div class="no-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                                <i data-lucide="file-text" style="width: 32px; height: 32px; color: white;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="article-content">
                                        <span class="article-category"><?= htmlspecialchars($article['category']) ?></span>
                                        <h3 class="article-title"><?= htmlspecialchars($article['title']) ?></h3>
                                        <p class="article-excerpt"><?= htmlspecialchars(substr($article['excerpt'] ?: strip_tags($article['content']), 0, 150)) ?>...</p>
                                        <div class="article-meta">
                                            <span><?= htmlspecialchars($article['author_name']) ?></span>
                                            <span>•</span>
                                            <span><?= date('d/m/Y', strtotime($article['created_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                                <i data-lucide="search-x" style="width: 64px; height: 64px; color: #cbd5e0; margin-bottom: 16px;"></i>
                                <h3 style="font-size: 1.25rem; color: #2d3748; margin-bottom: 8px;">Aucun article trouvé</h3>
                                <p style="color: #718096;">Essayez de modifier vos filtres</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination" style="margin-top: 40px; display: flex; justify-content: center; gap: 8px;">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn" style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #2d3748;">← Précédent</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="btn btn-primary" style="padding: 8px 16px;"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="btn" style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #2d3748;"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn" style="padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #2d3748;">Suivant →</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </main>
            
            <?php if (count($popular) > 0): ?>
                <aside class="discover-sidebar">
                    <div class="discover-widget">
                        <h3>Articles populaires</h3>
                        <div class="popular-list">
                            <?php foreach ($popular as $article): ?>
                                <a href="article.php?id=<?= $article['id'] ?>" class="popular-item">
                                    <div class="popular-info">
                                        <h4><?= htmlspecialchars($article['title']) ?></h4>
                                        <span class="popular-meta"><?= $article['views'] ?> vues</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="discover-widget">
                        <h3>Catégories</h3>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <?php foreach ($categories as $cat): ?>
                                <a href="?category=<?= urlencode($cat['category']) ?>" style="padding: 4px 12px; background: #f7fafc; border-radius: 20px; color: #2d3748; text-decoration: none; font-size: 0.875rem;">
                                    <?= htmlspecialchars($cat['category']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function performSearch() {
    const search = document.getElementById('searchInput').value;
    const params = new URLSearchParams(window.location.search);
    if (search) params.set('search', search);
    else params.delete('search');
    params.delete('page');
    window.location.search = params.toString();
}

function applyFilters() {
    const category = document.getElementById('categoryFilter').value;
    const params = new URLSearchParams(window.location.search);
    if (category) params.set('category', category);
    else params.delete('category');
    params.delete('page');
    window.location.search = params.toString();
}

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') performSearch();
});
</script>

<style>
.discover-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 48px 32px;
    border-radius: 16px;
    color: white;
    margin-bottom: 32px;
}

.discover-hero h1 {
    font-size: 2rem;
    margin-bottom: 8px;
}

.discover-hero p {
    opacity: 0.9;
    font-size: 1.125rem;
}

.articles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}

.article-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s;
    cursor: pointer;
}

.article-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.article-image {
    height: 200px;
    overflow: hidden;
    background: #f7fafc;
}

.article-image img,
.article-image .no-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.article-content {
    padding: 20px;
}

.article-category {
    display: inline-block;
    padding: 4px 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 20px;
    margin-bottom: 12px;
}

.article-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 8px;
    line-height: 1.4;
}

.article-excerpt {
    color: #718096;
    font-size: 0.9rem;
    margin-bottom: 16px;
    line-height: 1.6;
}

.article-meta {
    display: flex;
    gap: 8px;
    color: #a0aec0;
    font-size: 0.875rem;
    align-items: center;
}

.featured-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
}

.featured-article {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
}

.featured-image {
    height: 250px;
    position: relative;
    overflow: hidden;
}

.featured-image img,
.featured-image .no-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.featured-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    background: #ffd700;
    color: #1a202c;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
}

.featured-content {
    padding: 24px;
    flex: 1;
}

.featured-title {
    font-size: 1.375rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 12px;
}

.popular-item {
    display: flex;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.popular-item:hover {
    color: #667eea;
}

.popular-info h4 {
    font-size: 0.95rem;
    color: #2d3748;
    margin-bottom: 4px;
    line-height: 1.4;
}

.popular-meta {
    font-size: 0.8rem;
    color: #a0aec0;
}
</style>

<?php require 'includes/footer.php'; ?>
