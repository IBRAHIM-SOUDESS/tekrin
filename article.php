<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $sql = "SELECT a.*, u.fullname as author_name, u.profile_picture as author_avatar 
            FROM articles a 
            LEFT JOIN users u ON a.author_id = u.id 
            WHERE a.id = ? AND a.status = 'published'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header('Location: articles.php');
        exit;
    }
    
    $update_views = $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
    $update_views->execute([$article_id]);
    
    $related_sql = "SELECT a.*, u.fullname as author_name 
                    FROM articles a 
                    LEFT JOIN users u ON a.author_id = u.id 
                    WHERE a.id != ? AND a.status = 'published' 
                    AND (a.category = ? OR a.tags LIKE ?) 
                    ORDER BY a.views DESC LIMIT 4";
    $tag_param = '%' . ($article['tags'] ? explode(',', $article['tags'])[0] : '') . '%';
    $related_stmt = $pdo->prepare($related_sql);
    $related_stmt->execute([$article_id, $article['category'], $tag_param]);
    $related = $related_stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Erreur: " . $e->getMessage());
}

$page_title = $article['title'];
?>
<?php require 'includes/header.php'; ?>

<div class="app-layout">
    <div class="container">
        <div class="layout-wrapper">
            <?php if (isLoggedIn()): ?>
                <?php require 'includes/sidebar.php'; ?>
            <?php endif; ?>
            <main class="main-content" style="max-width: 800px; margin: 0 auto;">
                <a href="articles.php" style="display: inline-flex; align-items: center; gap: 8px; color: #667eea; text-decoration: none; margin-bottom: 24px; font-weight: 500;">
                    <i data-lucide="arrow-left"></i>
                    Retour aux articles
                </a>
                
                <article style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    <?php if ($article['cover_image']): ?>
                        <div style="height: 350px; overflow: hidden;">
                            <img src="<?= htmlspecialchars($article['cover_image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    
                    <div style="padding: 40px;">
                        <span style="display: inline-block; padding: 6px 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 0.875rem; font-weight: 600; border-radius: 20px; margin-bottom: 16px;">
                            <?= htmlspecialchars($article['category']) ?>
                        </span>
                        
                        <h1 style="font-size: 2.25rem; font-weight: 800; color: #1a202c; margin-bottom: 16px; line-height: 1.3;">
                            <?= htmlspecialchars($article['title']) ?>
                        </h1>
                        
                        <div style="display: flex; align-items: center; gap: 16px; padding-bottom: 24px; border-bottom: 1px solid #f0f0f0; margin-bottom: 32px;">
                            <?php if ($article['author_avatar']): ?>
                                <img src="<?= htmlspecialchars($article['author_avatar']) ?>" alt="" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">
                                    <?= strtoupper(substr($article['author_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight: 600; color: #2d3748;"><?= htmlspecialchars($article['author_name']) ?></div>
                                <div style="color: #718096; font-size: 0.875rem;">
                                    <i data-lucide="calendar" style="width: 14px; height: 14px; display: inline-block; margin-right: 4px;"></i>
                                    <?= date('d/m/Y à H:i', strtotime($article['created_at'])) ?>
                                    <span style="margin: 0 8px;">•</span>
                                    <i data-lucide="eye" style="width: 14px; height: 14px; display: inline-block; margin-right: 4px;"></i>
                                    <?= $article['views'] + 1 ?> vues
                                </div>
                            </div>
                        </div>
                        
                        <div style="font-size: 1.125rem; line-height: 1.8; color: #2d3748;">
                            <?= $article['content'] ?>
                        </div>
                        
                        <?php if ($article['tags']): ?>
                            <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid #f0f0f0;">
                                <div style="font-weight: 600; color: #2d3748; margin-bottom: 12px;">Tags</div>
                                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                    <?php foreach (explode(',', $article['tags']) as $tag): ?>
                                        <a href="articles.php?search=<?= urlencode(trim($tag)) ?>" style="padding: 6px 14px; background: #f7fafc; border-radius: 20px; color: #4a5568; text-decoration: none; font-size: 0.875rem;">
                                            <?= htmlspecialchars(trim($tag)) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
                
                <?php if (count($related) > 0): ?>
                    <section style="margin-top: 48px;">
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: #1a202c; margin-bottom: 24px;">Articles liés</h2>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
                            <?php foreach ($related as $rel): ?>
                                <a href="article.php?id=<?= $rel['id'] ?>" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06); text-decoration: none; color: inherit; transition: all 0.3s;">
                                    <?php if ($rel['cover_image']): ?>
                                        <div style="height: 180px; overflow: hidden;">
                                            <img src="<?= htmlspecialchars($rel['cover_image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    <?php else: ?>
                                        <div style="height: 180px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                            <i data-lucide="file-text" style="width: 48px; height: 48px; color: white;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div style="padding: 20px;">
                                        <span style="display: inline-block; padding: 4px 12px; background: #f0f0f0; color: #667eea; font-size: 0.75rem; font-weight: 600; border-radius: 20px; margin-bottom: 12px;">
                                            <?= htmlspecialchars($rel['category']) ?>
                                        </span>
                                        <h3 style="font-size: 1.125rem; font-weight: 600; color: #1a202c; margin-bottom: 8px; line-height: 1.4;">
                                            <?= htmlspecialchars($rel['title']) ?>
                                        </h3>
                                        <p style="color: #718096; font-size: 0.9rem; line-height: 1.6; margin-bottom: 12px;">
                                            <?= htmlspecialchars(substr($rel['excerpt'] ?: strip_tags($rel['content']), 0, 120)) ?>...
                                        </p>
                                        <div style="color: #a0aec0; font-size: 0.875rem;">
                                            <?= date('d/m/Y', strtotime($rel['created_at'])) ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
