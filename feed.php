<?php
require_once 'includes/auth.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Bloquer l'accès si l'onboarding n'est pas fini
requireOnboarding();

$user_id = $_SESSION['user_id'];

// Récupérer les infos de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    redirect('login.php');
}

// Gérer les filtres
$filter = isset($_GET['filter']) ?? 'all';

// Construire la requête selon le filtre
$sql_where = '';
$params = [];

if ($filter === 'following') {
    $sql_where = " WHERE (p.user_id = ? OR EXISTS (SELECT 1 FROM follows WHERE follower_id = ? AND following_id = p.user_id))";
    $params = array_merge($params, [$user_id, $user_id]);
} elseif ($filter === 'pros') {
    $sql_where = " WHERE u.role IN ('technicien', 'artisan', 'entreprise')";
}

// Ajouter les paramètres pour les champs is_liked, is_saved et is_following_author
// Le premier paramètre est pour is_following_author (1er SELECT)
$params = array_merge($params, [$user_id]);
// 2e paramètre pour is_liked
$params = array_merge($params, [$user_id]);
// 3e paramètre pour is_saved
$params = array_merge($params, [$user_id]);
// 4e paramètre pour le ORDER BY CASE
$params = array_merge($params, [$user_id]);

// Récupérer les publications (les nôtres et celles des autres)
$stmt_posts = $pdo->prepare("
    SELECT p.*, u.fullname, u.profile_picture, u.profession,
           (SELECT 1 FROM follows WHERE follower_id = ? AND following_id = p.user_id) AS is_following_author,
           (SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = p.id) AS is_liked,
           (SELECT 1 FROM post_saves WHERE user_id = ? AND post_id = p.id) AS is_saved
    FROM posts p 
    JOIN users u ON p.user_id = u.id 
    $sql_where
    ORDER BY 
        CASE WHEN (SELECT 1 FROM follows WHERE follower_id = ? AND following_id = p.user_id) = 1 THEN 0 ELSE 1 END,
        p.created_at DESC
");
$stmt_posts->execute($params);
$posts = $stmt_posts->fetchAll();

// Récupérer des suggestions de professionnels (exclure l'utilisateur actuel)
$stmt_sug = $pdo->prepare("SELECT u.id, u.fullname, u.profile_picture, u.profession, (SELECT 1 FROM follows WHERE follower_id = ? AND following_id = u.id) as is_following FROM users u WHERE u.id != ? AND u.onboarding_completed = 1 ORDER BY RAND() LIMIT 5");
$stmt_sug->execute([$user_id, $user_id]);
$suggestions = $stmt_sug->fetchAll();

// Récupérer les publicités actives
$now = date('Y-m-d H:i:s');
$stmt_ads = $pdo->prepare("SELECT ads.*, users.fullname, users.profile_picture, users.profession FROM ads JOIN users ON ads.user_id = users.id WHERE ads.is_active = 1 AND ads.start_date <= ? AND ads.end_date >= ? ORDER BY RAND() LIMIT 3");
$stmt_ads->execute([$now, $now]);
$ads = $stmt_ads->fetchAll();

// Récupérer les conversations de l'utilisateur
$stmt_conv = $pdo->prepare("
    SELECT c.*, 
        CASE 
            WHEN c.user_one = ? THEN u2.fullname 
            ELSE u1.fullname 
        END as other_name,
        CASE 
            WHEN c.user_one = ? THEN u2.profile_picture 
            ELSE u1.profile_picture 
        END as other_avatar,
        CASE 
            WHEN c.user_one = ? THEN u2.profession 
            ELSE u1.profession 
        END as other_profession,
        CASE 
            WHEN c.user_one = ? THEN c.user_two 
            ELSE c.user_one 
        END as other_id,
        (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT MAX(created_at) FROM messages WHERE conversation_id = c.id) as max_msg_time
    FROM conversations c
    JOIN users u1 ON c.user_one = u1.id
    JOIN users u2 ON c.user_two = u2.id
    WHERE c.user_one = ? OR c.user_two = ?
    ORDER BY 
        CASE WHEN (SELECT MAX(created_at) FROM messages WHERE conversation_id = c.id) IS NOT NULL THEN 0 ELSE 1 END ASC,
        (SELECT MAX(created_at) FROM messages WHERE conversation_id = c.id) DESC,
        c.created_at DESC
");
$stmt_conv->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt_conv->fetchAll();

require_once 'includes/header.php';
?>

<div class="app-layout">
    <div class="container" style="max-width: 1800px;">
        <div class="layout-wrapper" style="display: grid; grid-template-columns: 680px 380px; gap: 24px; align-items: start;">
            
            <!-- Column 1: News Feed with Discovery -->
            <main class="main-content">
                <!-- Section 1: Create Publication Card -->
                <div class="create-post-card">
                    <div class="create-post-header">
                        <?php 
                        $user_avatar = get_user_avatar($user);
                        if ($user_avatar): 
                        ?>
                            <div class="user-avatar-simple">
                                <img src="<?php echo $user_avatar; ?>">
                            </div>
                        <?php else: ?>
                            <div class="user-avatar-simple default-avatar">
                                <i data-lucide="user"></i>
                            </div>
                        <?php endif; ?>
                        <button class="create-post-input-trigger" onclick="openPostModal()">
                            Quoi de neuf, <?php echo explode(' ', htmlspecialchars($user['fullname']))[0]; ?> ?
                        </button>
                    </div>
                    <div class="create-post-actions">
                        <div class="create-post-btns-left">
                            <div class="post-type-btn" onclick="openPostModal()">
                                <i data-lucide="image" style="color:#10B981;"></i>
                                <span>Photo / Vidéo</span>
                            </div>
                            <div class="post-type-btn">
                                <i data-lucide="briefcase" style="color:#3B82F6;"></i>
                                <span>Service</span>
                            </div>
                            <div class="post-type-btn">
                                <i data-lucide="calendar" style="color:#F59E0B;"></i>
                                <span>Événement</span>
                            </div>
                            <div class="post-type-btn">
                                <i data-lucide="file-text" style="color:#6366F1;"></i>
                                <span>Fichier</span>
                            </div>
                            <div class="post-type-btn">
                                <i data-lucide="bar-chart-2" style="color:#EC4899;"></i>
                                <span>Sondage</span>
                            </div>
                        </div>
                        <button class="btn btn-register" onclick="openPostModal()" style="padding: 6px 16px; font-size: 12px; flex-shrink: 0;">Publier</button>
                    </div>
                </div>

                <!-- Section 2: Professional Stories -->
                <div class="stories-container">
                    <div class="story-item">
                        <div class="story-avatar-wrapper own-story">
                            <div class="story-avatar">
                                <?php if ($user['profile_picture']): ?>
                                    <img src="assets/uploads/profiles/<?php echo $user['profile_picture']; ?>">
                                <?php else: ?>
                                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f1f5f9; color:var(--primary-blue); font-weight:700;">
                                        <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="story-plus"><i data-lucide="plus" style="width:12px;"></i></div>
                        </div>
                        <span class="story-name">Votre story</span>
                    </div>

                    <?php 
                    // Simuler quelques stories
                    $dummy_stories = [
                        ['name' => 'Jean Tech', 'img' => 'https://i.pravatar.cc/150?u=1'],
                        ['name' => 'Marie Design', 'img' => 'https://i.pravatar.cc/150?u=2'],
                        ['name' => 'Paul Brico', 'img' => 'https://i.pravatar.cc/150?u=3'],
                        ['name' => 'Sarah Elec', 'img' => 'https://i.pravatar.cc/150?u=4'],
                        ['name' => 'Koffi Froid', 'img' => 'https://i.pravatar.cc/150?u=5'],
                        ['name' => 'Awa Menuis', 'img' => 'https://i.pravatar.cc/150?u=6'],
                    ];
                    foreach ($dummy_stories as $s): ?>
                    <div class="story-item">
                        <div class="story-avatar-wrapper">
                            <div class="story-avatar">
                                <img src="<?php echo $s['img']; ?>">
                            </div>
                        </div>
                        <span class="story-name"><?php echo $s['name']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Filtres du feed -->
                <div style="display: flex; gap: 12px; margin-bottom: 20px; padding: 0 4px;">
                    <a href="feed.php?filter=all" class="feed-filter-btn <?php echo ($filter === 'all' || !$filter) ? 'active' : ''; ?>" style="padding: 8px 16px; border-radius: 20px; font-weight: 600; text-decoration: none; transition: all 0.2s; <?php echo ($filter === 'all' || !$filter) ? 'background: var(--primary-blue); color: white;' : 'background: #F1F5F9; color: #64748B;'; ?>">
                        Tous
                    </a>
                    <a href="feed.php?filter=following" class="feed-filter-btn <?php echo $filter === 'following' ? 'active' : ''; ?>" style="padding: 8px 16px; border-radius: 20px; font-weight: 600; text-decoration: none; transition: all 0.2s; <?php echo $filter === 'following' ? 'background: var(--primary-blue); color: white;' : 'background: #F1F5F9; color: #64748B;'; ?>">
                        Suivis
                    </a>
                    <a href="feed.php?filter=pros" class="feed-filter-btn <?php echo $filter === 'pros' ? 'active' : ''; ?>" style="padding: 8px 16px; border-radius: 20px; font-weight: 600; text-decoration: none; transition: all 0.2s; <?php echo $filter === 'pros' ? 'background: var(--primary-blue); color: white;' : 'background: #F1F5F9; color: #64748B;'; ?>">
                        Professionnels
                    </a>
                </div>

                <!-- Section 3: Publications Feed -->
                <div class="feed-posts">
                    <?php if (empty($posts)): ?>
                        <div class="feed-post-card" style="padding: 40px; text-align: center;">
                            <div style="color: var(--primary-blue); margin-bottom: 15px;">
                                <i data-lucide="layout" style="width: 48px; height: 48px;"></i>
                            </div>
                            <h3>Aucune publication pour le moment</h3>
                            <p style="color: var(--text-muted); margin-top: 10px;">Soyez le premier à partager une réalisation avec la communauté !</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $ad_index = 0;
                        foreach ($posts as $post_index => $post): 
                            // Insert ad after every 3 posts
                            if ($post_index > 0 && $post_index % 3 === 0 && isset($ads[$ad_index])):
                                $ad = $ads[$ad_index];
                                $ad_index++;
                        ?>
                            <!-- Publicité -->
                            <article class="feed-post-card" style="border: 2px solid #FFD700; background: linear-gradient(to bottom, #FFFBEB, white);">
                                <header class="post-header">
                                    <div class="post-user-avatar">
                                        <?php 
                                        $ad_avatar = $ad['profile_picture'] ?? '';
                                        if ($ad_avatar && strpos($ad_avatar, 'assets/') !== 0) $ad_avatar = 'assets/uploads/profiles/' . $ad_avatar;
                                        if ($ad_avatar): 
                                        ?>
                                            <img src="<?php echo $ad_avatar; ?>" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f3f4f6; color:var(--primary-blue); font-weight:700;">
                                                <?php echo strtoupper(substr($ad['fullname'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-user-info">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <h4 class="post-user-name"><?php echo htmlspecialchars($ad['fullname']); ?></h4>
                                            <span style="background: #FFD700; color: #8B6914; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; display:flex; align-items:center; gap:4px;">
                                                <i data-lucide="trending-up" style="width:12px; height:12px;"></i> Publicité
                                            </span>
                                        </div>
                                        <div class="post-user-meta">
                                            <?php echo htmlspecialchars($ad['profession'] ?? 'Professionnel'); ?>
                                        </div>
                                    </div>
                                </header>
                                
                                <div class="post-body">
                                    <h3 style="font-size:18px; font-weight:800; margin-bottom:8px; color:#1e293b;"><?php echo htmlspecialchars($ad['title']); ?></h3>
                                    <?php if ($ad['description']): ?>
                                        <p class="post-text" style="margin-bottom:12px;"><?php echo htmlspecialchars($ad['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($ad['image_url']): ?>
                                        <div class="pub-image" style="border-radius:16px; overflow:hidden; margin-bottom:12px;">
                                            <img src="<?php echo htmlspecialchars($ad['image_url']); ?>" alt="Publicité" style="width:100%; display:block;">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($ad['link']): ?>
                                        <a href="<?php echo htmlspecialchars($ad['link']); ?>" target="_blank" onclick="trackAdClick(<?php echo $ad['id']; ?>)" style="display:inline-block; background:var(--primary-blue); color:white; padding:12px 24px; border-radius:12px; text-decoration:none; font-weight:700; margin-top:8px;">
                                            En savoir plus
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endif; ?>
                        
                            <article class="feed-post-card">
                                <header class="post-header">
                                    <div class="post-user-avatar">
                                        <?php 
                                        $post_avatar = $post['profile_picture'] ?? '';
                                        if ($post_avatar && strpos($post_avatar, 'assets/') !== 0) $post_avatar = 'assets/uploads/profiles/' . $post_avatar;
                                        if ($post_avatar): 
                                        ?>
                                            <img src="<?php echo $post_avatar; ?>" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f3f4f6; color:var(--primary-blue); font-weight:700;">
                                                <?php echo strtoupper(substr($post['fullname'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-user-info">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <h4 class="post-user-name"><?php echo htmlspecialchars($post['fullname']); ?></h4>
                                        <?php if ($post['type'] !== 'text' && $post['type'] !== 'image'): ?>
                                            <span class="post-badge badge-<?php echo $post['type']; ?>">
                                                <?php 
                                                switch($post['type']) {
                                                    case 'service': echo 'Service'; break;
                                                    case 'project': echo 'Réalisation'; break;
                                                    case 'event': echo 'Événement'; break;
                                                }
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-user-meta">
                                        <?php echo htmlspecialchars($post['profession'] ?? 'Professionnel'); ?> • 
                                        <?php 
                                        $date = new DateTime($post['created_at']);
                                        echo $date->format('d M à H:i');
                                        ?>
                                    </div>
                                </div>
                                <div class="post-options">
                                    <i data-lucide="more-horizontal"></i>
                                </div>
                            </header>
                            
                            <div class="post-body">
                                <p class="post-text"><?php echo htmlspecialchars($post['content']); ?></p>
                                
                                <?php 
                                $post_images = json_decode($post['images'] ?? '[]', true);
                                if (!empty($post_images)): 
                                    $count = count($post_images);
                                    $grid_class = $count > 4 ? 'grid-more' : 'grid-' . $count;
                                ?>
                                    <div class="post-gallery <?php echo $grid_class; ?>">
                                        <?php foreach (array_slice($post_images, 0, 4) as $index => $img): ?>
                                            <div class="gallery-item">
                                                <img src="assets/uploads/posts/<?php echo $img; ?>" alt="Publication">
                                                <?php if ($index === 3 && $count > 4): ?>
                                                    <div class="gallery-more-overlay">+<?php echo $count - 4; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-engagement">
                                <div class="engagement-item" id="like-count-<?php echo $post['id']; ?>">
                                    <i data-lucide="heart" style="width:16px; color:#F43F5E;"></i>
                                    <span><?php echo $post['like_count']; ?> j'aime</span>
                                </div>
                                <div class="engagement-item">
                                    <i data-lucide="message-circle" style="width:16px;"></i>
                                    <span><?php echo $post['comment_count']; ?> commentaires</span>
                                </div>
                                <div class="engagement-item" style="margin-left: auto;">
                                    <span><?php echo $post['share_count']; ?> partages</span>
                                </div>
                            </div>
                                
                                <div class="post-actions">
                                    <button class="action-btn like-btn" data-post-id="<?php echo $post['id']; ?>" data-is-liked="<?php echo $post['is_liked'] ? 'true' : 'false'; ?>" style="<?php echo $post['is_liked'] ? 'color: #F43F5E;' : ''; ?>">
                                        <i data-lucide="<?php echo $post['is_liked'] ? 'heart-fill' : 'heart'; ?>"></i>
                                        <span>J'aime</span>
                                    </button>
                                    <button class="action-btn comment-toggle-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i data-lucide="message-square"></i>
                                        <span>Commenter</span>
                                    </button>
                                    <button class="action-btn share-btn" data-post-id="<?php echo $post['id']; ?>" data-user-name="<?php echo htmlspecialchars($post['fullname']); ?>" data-post-content="<?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?>">
                                        <i data-lucide="share-2"></i>
                                        <span>Partager</span>
                                    </button>
                                    <button class="action-btn save-btn" data-post-id="<?php echo $post['id']; ?>" data-is-saved="<?php echo $post['is_saved'] ? 'true' : 'false'; ?>" style="<?php echo $post['is_saved'] ? 'color: var(--primary-blue);' : ''; ?>">
                                        <i data-lucide="<?php echo $post['is_saved'] ? 'bookmark-check' : 'bookmark'; ?>"></i>
                                        <span>Enregistrer</span>
                                    </button>
                                </div>
                                
                                <!-- Section des commentaires -->
                                <div class="comments-section" id="comments-section-<?php echo $post['id']; ?>" style="display: none; border-top: 1px solid #F1F5F9; padding: 20px 0 0 0; margin-top: 12px;">
                                    <!-- Liste des commentaires -->
                                    <div class="comments-list" id="comments-list-<?php echo $post['id']; ?>" style="margin-bottom: 16px; max-height: 400px; overflow-y: auto;"></div>
                                    
                                    <!-- Formulaire d'ajout de commentaire -->
                                    <div class="comment-form-wrapper" style="display: flex; gap: 12px; padding: 12px; background: #F8FAFC; border-radius: 16px;">
                                        <?php 
                                        $comment_avatar = $user['profile_picture'] ?? '';
                                        if ($comment_avatar && strpos($comment_avatar, 'assets/') !== 0) $comment_avatar = 'assets/uploads/profiles/' . $comment_avatar;
                                        ?>
                                        <div class="comment-form-avatar" style="width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; overflow: hidden; background: linear-gradient(135deg, #E0F2FE, #F1F5F9); display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                            <?php if ($comment_avatar): ?>
                                                <img src="<?php echo $comment_avatar; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <span style="color: var(--primary-blue); font-weight: 800; font-size: 16px;">
                                                    <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div style="flex: 1; display: flex; gap: 10px; align-items: flex-end;">
                                            <div style="flex: 1; background: white; border-radius: 24px; padding: 4px 16px; display: flex; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #F1F5F9;">
                                                <input type="text" class="comment-input" id="comment-input-<?php echo $post['id']; ?>" placeholder="Répondre à cette publication..." style="flex: 1; padding: 10px 0; border: none; font-size: 14px; outline: none; background: transparent;">
                                                <div style="display: flex; gap: 4px; margin-left: 8px;">
                                                    <button class="comment-attach-btn" type="button" title="Ajouter une photo" style="background: none; border: none; padding: 6px; border-radius: 50%; cursor: pointer; transition: background 0.2s;">
                                                        <i data-lucide="image" style="width: 18px; height: 18px; color: #64748B;"></i>
                                                    </button>
                                                    <button class="comment-emoji-btn" type="button" title="Ajouter un emoji" style="background: none; border: none; padding: 6px; border-radius: 50%; cursor: pointer; transition: background 0.2s;">
                                                        <i data-lucide="smile" style="width: 18px; height: 18px; color: #64748B;"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <button class="comment-submit-btn" data-post-id="<?php echo $post['id']; ?>" style="background: linear-gradient(135deg, var(--primary-blue), #1e3a8a); color: white; border: none; border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);">
                                                <i data-lucide="send" style="width: 20px; height: 20px;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Discovery Widgets in Feed -->
                    <div style="margin-top: 24px;">
                        <!-- Widget 1: Tendances -->
                        <div class="widget-card">
                            <h3 class="widget-title">Tendances pour vous</h3>
                            <div class="trend-item">
                                <span class="trend-name">#Électricité</span>
                                <span class="trend-count">1.2k publications</span>
                            </div>
                            <div class="trend-item">
                                <span class="trend-name">#Plomberie</span>
                                <span class="trend-count">850 publications</span>
                            </div>
                            <div class="trend-item">
                                <span class="trend-name">#Rénovation</span>
                                <span class="trend-count">640 publications</span>
                            </div>
                            <div class="trend-item">
                                <span class="trend-name">#Bricolage</span>
                                <span class="trend-count">420 publications</span>
                            </div>
                            <div class="trend-item">
                                <span class="trend-name">#Technologie</span>
                                <span class="trend-count">310 publications</span>
                            </div>
                        </div>

                        <!-- Widget 2: Suggestions -->
                        <div class="widget-card" style="margin-top: 20px;">
                            <h3 class="widget-title" style="font-size: 18px; margin-bottom: 16px;">Professionnels à découvrir</h3>
                            <?php foreach ($suggestions as $sug): ?>
                                <div class="suggestion-item" style="gap: 12px; padding: 12px; border-radius: 12px; margin-bottom: 8px;">
                                    <div class="suggestion-avatar" style="width: 48px; height: 48px; font-size: 18px;">
                                        <?php 
                                        $sug_avatar = $sug['profile_picture'] ?? '';
                                        if ($sug_avatar && strpos($sug_avatar, 'assets/') !== 0) $sug_avatar = 'assets/uploads/profiles/' . $sug_avatar;
                                        if ($sug_avatar): ?>
                                            <img src="<?php echo $sug_avatar; ?>">
                                        <?php else: ?>
                                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f1f5f9; color:var(--primary-blue); font-weight:700; font-size:18px;">
                                                <?php echo strtoupper(substr($sug['fullname'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="suggestion-info" style="flex: 1;">
                                        <h4 class="suggestion-name" style="font-size: 14px; font-weight: 700;"><?php echo htmlspecialchars($sug['fullname']); ?></h4>
                                        <p class="suggestion-profession" style="font-size: 12px; color: #64748B; margin-top: 2px;"><?php echo htmlspecialchars($sug['profession'] ?? 'Professionnel'); ?></p>
                                    </div>
                                    <div style="display:flex; flex-direction: column; gap: 6px;">
                                        <button id="follow-btn-<?php echo $sug['id']; ?>" onclick="toggleFollowFeed(document.getElementById('follow-btn-<?php echo $sug['id']; ?>'))" class="btn btn-follow-feed" data-user-id="<?php echo $sug['id']; ?>" data-is-following="<?php echo $sug['is_following'] ? 'true' : 'false'; ?>" style="padding: 8px 12px; font-size: 12px; font-weight: 600; border-radius: 8px; <?php echo $sug['is_following'] ? 'background:#E2E8F0; color:#475569;' : 'background:var(--primary-blue); color:white;'; ?>">
                                            <?php echo $sug['is_following'] ? '✓ Suivi' : 'Suivre'; ?>
                                        </button>
                                        <button class="btn btn-message-feed" onclick="startConversation(<?php echo $sug['id']; ?>)" data-user-id="<?php echo $sug['id']; ?>" style="background:#f0f9ff; border: 1px solid #bae6fd; border-radius:8px; padding:8px 12px; cursor:pointer; font-weight:600; font-size:12px; color:var(--primary-blue); display:flex; align-items:center; gap:4px;">
                                            <i data-lucide="message-circle" style="width:14px; height:14px;"></i>
                                            Message
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Widget 3: Événements -->
                        <div class="widget-card" style="margin-top: 20px;">
                            <h3 class="widget-title">Événements à venir</h3>
                            <div class="event-item">
                                <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?w=100&h=100&fit=crop" class="event-image">
                                <div class="event-info">
                                    <h5>Salon de l'Artisanat</h5>
                                    <div class="event-meta">
                                        <i data-lucide="calendar"></i>
                                        <span>15 Juin 2026</span>
                                    </div>
                                    <div class="event-meta">
                                        <i data-lucide="map-pin"></i>
                                        <span>Palais des Sports, Yaoundé</span>
                                    </div>
                                </div>
                            </div>
                            <div class="event-item">
                                <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?w=100&h=100&fit=crop" class="event-image">
                                <div class="event-info">
                                    <h5>Workshop Électricité</h5>
                                    <div class="event-meta">
                                        <i data-lucide="calendar"></i>
                                        <span>22 Juin 2026</span>
                                    </div>
                                    <div class="event-meta">
                                        <i data-lucide="map-pin"></i>
                                        <span>Hôtel Akwa Palace, Douala</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Column 4: Messages (permanent chat interface) -->
            <aside id="messagesColumn" style="height: calc(100vh - 100px); position: sticky; top: 80px; display: flex; flex-direction: column; background: white; border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid #E2E8F0; overflow: hidden;">
                
                <!-- Section 1: Header -->
                <div style="padding: 16px 20px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; font-weight: 700; color: #1E293B; margin: 0;">Messages</h3>
                    <div style="display: flex; gap: 12px;">
                        <button style="background: none; border: none; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.2s;" class="msg-header-btn" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='transparent'">
                            <i data-lucide="search" style="width: 20px; height: 20px; color: #64748B;"></i>
                        </button>
                        <button style="background: none; border: none; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.2s;" class="msg-header-btn" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='transparent'">
                            <i data-lucide="edit-3" style="width: 20px; height: 20px; color: #64748B;"></i>
                        </button>
                        <button style="background: none; border: none; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.2s;" class="msg-header-btn" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='transparent'">
                            <i data-lucide="more-vertical" style="width: 20px; height: 20px; color: #64748B;"></i>
                        </button>
                    </div>
                </div>

                <!-- Section 2: Tabs -->
                <div style="display: flex; border-bottom: 1px solid #E2E8F0;">
                    <button id="tabDiscussions" onclick="switchTab('discussions')" style="flex: 1; padding: 14px; border: none; background: white; cursor: pointer; font-size: 14px; font-weight: 600; color: var(--primary-blue); border-bottom: 2px solid var(--primary-blue); transition: all 0.2s;">
                        Discussions
                    </button>
                    <button id="tabDemandes" onclick="switchTab('demandes')" style="flex: 1; padding: 14px; border: none; background: white; cursor: pointer; font-size: 14px; font-weight: 600; color: #64748B; border-bottom: 2px solid transparent; transition: all 0.2s;">
                        Demandes
                    </button>
                </div>

                <!-- Section 3: Conversation List & Chat -->
                <div id="conversationsContainer" style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
                    
                    <!-- Conversation List Panel (by default) -->
                    <div id="convListPanel" style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">
                        <div id="conversationsList" style="flex: 1; overflow-y: auto; padding: 8px;">
                            <?php if (empty($conversations)): ?>
                                <div style="text-align: center; padding: 40px 20px; color: #64748B; margin: auto;">
                                    <i data-lucide="message-square" style="width: 48px; height: 48px; margin-bottom: 12px; color: #CBD5E1;"></i>
                                    <p style="font-size: 14px;">Aucune conversation pour le moment</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <div class="conversation-item" data-conv-id="<?php echo $conv['id']; ?>" data-other-id="<?php echo $conv['other_id']; ?>" data-other-name="<?php echo htmlspecialchars($conv['other_name']); ?>" data-other-avatar="<?php echo htmlspecialchars($conv['other_avatar'] ?? ''); ?>" style="display: flex; gap: 12px; padding: 12px; border-radius: 12px; cursor: pointer; margin-bottom: 4px; transition: background 0.2s;" onclick="selectConversation(<?php echo $conv['id']; ?>, <?php echo $conv['other_id']; ?>, '<?php echo addslashes(htmlspecialchars($conv['other_name'])); ?>', '<?php echo addslashes(htmlspecialchars($conv['other_avatar'] ?? '')); ?>')">
                                        <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: #F1F5F9;">
                                            <?php 
                                            $conv_avatar = $conv['other_avatar'] ?? '';
                                            if ($conv_avatar && strpos($conv_avatar, 'assets/') !== 0) $conv_avatar = 'assets/uploads/profiles/' . $conv_avatar;
                                            if ($conv_avatar): ?>
                                                <img src="<?php echo $conv_avatar; ?>" style="width:100%; height:100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color: var(--primary-blue); font-weight:700; font-size:18px;">
                                                    <?php echo strtoupper(substr($conv['other_name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="flex:1; min-width:0;">
                                            <div style="display:flex; justify-content: space-between; align-items: center;">
                                                <h4 style="font-size:14px; font-weight:700; color: #1E293B; margin:0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <?php echo htmlspecialchars($conv['other_name']); ?>
                                                </h4>
                                                <?php if ($conv['last_message_time']): ?>
                                                    <span style="font-size:11px; color: #94A3B8;">
                                                        <?php 
                                                        $msg_date = new DateTime($conv['last_message_time']);
                                                        $now = new DateTime();
                                                        $diff = $now->diff($msg_date);
                                                        if ($diff->days == 0):
                                                            echo $msg_date->format('H:i');
                                                        elseif ($diff->days == 1):
                                                            echo 'Hier';
                                                        else:
                                                            echo $msg_date->format('d/m');
                                                        endif;
                                                        ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p style="font-size:13px; color: #64748B; margin:4px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo htmlspecialchars($conv['last_message'] ?? 'Aucun message'); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Active Chat Area (hidden by default) -->
                    <div id="activeChatArea" style="display: none; flex-direction: column; height: 100%;">
                        <!-- Chat Header (with back button) -->
                        <div id="chatHeader" style="padding: 12px 16px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <button onclick="closeChatAndShowList()" style="background: none; border: none; cursor: pointer; padding: 6px; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='transparent'">
                                    <i data-lucide="arrow-left" style="width: 20px; height: 20px; color: #1E293B;"></i>
                                </button>
                                <div id="chatHeaderAvatar" style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #F1F5F9; flex-shrink: 0;">
                                    <img id="chatHeaderAvatarImg" src="" style="width:100%; height:100%; object-fit: cover; display:none;">
                                    <div id="chatHeaderAvatarPlaceholder" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--primary-blue); font-weight:700; font-size:16px;"></div>
                                </div>
                                <div>
                                    <h4 id="chatHeaderName" style="font-size: 14px; font-weight: 700; color: #1E293B; margin: 0;"></h4>
                                    <span style="font-size: 12px; color: #10B981; font-weight: 500;">En ligne</span>
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button style="background: none; border: none; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='transparent'">
                                    <i data-lucide="phone" style="width: 20px; height: 20px; color: #64748B;"></i>
                                </button>
                                <button style="background: none; border: none; cursor: pointer; padding: 8px; border-radius: 8px; transition: background 0.2s;" onmouseover="this.style.background='#F1F5F9'" onmouseout="this.style.background='transparent'">
                                    <i data-lucide="info" style="width: 20px; height: 20px; color: #64748B;"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Chat Messages -->
                        <div id="chatMessages" style="flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; background: #F8FAFC;">
                            <!-- Messages will be loaded here via JS -->
                        </div>

                        <!-- Message Composer -->
                        <div id="chatComposer" style="padding: 12px 16px; border-top: 1px solid #E2E8F0; background: white;">
                            <form id="chatForm" onsubmit="sendMessage(event)" style="display: flex; gap: 10px; align-items: flex-end;">
                                <button type="button" onclick="document.getElementById('composerAttachment').click()" style="background: #F1F5F9; border: none; border-radius: 12px; padding: 12px; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='#E2E8F0'" onmouseout="this.style.background='#F1F5F9'">
                                    <i data-lucide="paperclip" style="width: 20px; height: 20px; color: #64748B;"></i>
                                </button>
                                <input type="file" id="composerAttachment" style="display: none;">
                                <input type="text" id="composerInput" placeholder="Écrire un message..." style="flex: 1; padding: 12px 16px; border: 1px solid #E2E8F0; border-radius: 24px; font-size: 14px; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary-blue)'" onblur="this.style.borderColor='#E2E8F0'">
                                <button type="submit" style="background: linear-gradient(135deg, var(--primary-blue), #1e3a8a); color: white; border: none; border-radius: 50%; width: 44px; height: 44px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                    <i data-lucide="send" style="width: 20px; height: 20px;"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- Modal Création Post -->
<div id="postModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Créer une publication</h2>
            <span class="close-modal" onclick="closePostModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="postForm" action="handle_post.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="type" id="postType" value="text">
                <div class="post-form-header">
                    <div class="post-form-avatar">
                        <?php if ($avatar): ?>
                            <img src="<?php echo $avatar; ?>">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f3f4f6; color: var(--primary-blue); font-weight: 700;">
                                <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 15px;"><?php echo htmlspecialchars($user['fullname']); ?></h4>
                        <div id="postTypeBadge" style="font-size: 10px; background: #f1f5f9; color: var(--text-muted); display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 4px; margin-top: 2px;">
                            <i data-lucide="globe" style="width: 10px;"></i> Public
                        </div>
                    </div>
                </div>
                
                <textarea class="post-textarea" name="content" placeholder="De quoi souhaitez-vous discuter ?" required></textarea>
                
                <!-- Preview Container for Multiple Images -->
                <div id="image-preview-container" class="post-preview-grid" style="display: none; margin-bottom: 15px;"></div>

                <div class="post-actions-bar">
                    <label for="post-images" class="post-action-btn" title="Ajouter des photos">
                        <i data-lucide="image" style="color: #10B981;"></i>
                        <span>Photo</span>
                        <input type="file" id="post-images" name="images[]" accept="image/*" multiple hidden onchange="previewPostImages(this)">
                    </label>
                    <div class="post-action-btn" onclick="setPostType('service')" title="Mettre en avant un service">
                        <i data-lucide="briefcase" style="color: #3B82F6;"></i>
                        <span>Service</span>
                    </div>
                    <div class="post-action-btn" onclick="setPostType('project')" title="Partager une réalisation">
                        <i data-lucide="award" style="color: #7C4DFF;"></i>
                        <span>Projet</span>
                    </div>
                    <div class="post-action-btn" onclick="setPostType('event')" title="Annoncer un événement">
                        <i data-lucide="calendar" style="color: #F59E0B;"></i>
                        <span>Événement</span>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closePostModal()">Annuler</button>
            <button type="submit" form="postForm" class="btn btn-register">Publier</button>
        </div>
    </div>
</div>



<script>
let currentUserId = <?php echo $user_id; ?>;
let activeConvId = <?php echo !empty($conversations) ? $conversations[0]['id'] : 'null'; ?>;
let lastMessageId = 0;
let currentOtherUser = null;

// --- POST MODAL FUNCTIONS ---
function openPostModal() {
    document.getElementById('postModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closePostModal() {
    document.getElementById('postModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    // Reset form
    document.getElementById('postForm').reset();
    document.getElementById('image-preview-container').innerHTML = '';
    document.getElementById('image-preview-container').style.display = 'none';
    setPostType('text');
}

function setPostType(type) {
    document.getElementById('postType').value = type;
    const badge = document.getElementById('postTypeBadge');
    let label = 'Public';
    let icon = 'globe';

    switch(type) {
        case 'service': label = 'Service'; icon = 'briefcase'; break;
        case 'project': label = 'Réalisation'; icon = 'award'; break;
        case 'event': label = 'Événement'; icon = 'calendar'; break;
    }

    badge.innerHTML = `<i data-lucide="${icon}" style="width: 10px;"></i> ${label}`;
    lucide.createIcons();
}

function previewPostImages(input) {
    const container = document.getElementById('image-preview-container');
    container.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        container.style.display = 'grid';
        const filesCount = input.files.length;
        
        // Ajuster la grille selon le nombre d'images
        if (filesCount === 1) container.style.gridTemplateColumns = '1fr';
        else if (filesCount === 2) container.style.gridTemplateColumns = '1fr 1fr';
        else container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(120px, 1fr))';

        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.style.position = 'relative';
                div.style.aspectRatio = '1';
                div.style.borderRadius = '8px';
                div.style.overflow = 'hidden';
                
                div.innerHTML = `
                    <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">
                    <button type="button" onclick="removePreview(this, ${index})" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center;">&times;</button>
                `;
                container.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    } else {
        container.style.display = 'none';
    }
}

function removePreview(btn, index) {
    // Note: This doesn't actually remove from the file input (which is read-only)
    // but for a real implementation we'd use a DataTransfer object or state.
    // For now, let's just hide the preview and clear the input if everything is removed.
    btn.parentElement.remove();
    const container = document.getElementById('image-preview-container');
    if (container.children.length === 0) {
        container.style.display = 'none';
        document.getElementById('post-images').value = '';
    }
}

// Fermer la modal en cliquant à l'extérieur
window.onclick = function(event) {
    const modal = document.getElementById('postModal');
    if (event.target == modal) {
        closePostModal();
    }
}

// --- FOLLOW FUNCTION ---
function toggleFollowFeed(btn) {
    console.log('Toggle follow clicked!');
    const userId = btn.dataset.userId;
    const isFollowing = btn.dataset.isFollowing === 'true';
    console.log('Current state: userId=', userId, 'isFollowing=', isFollowing);
    
    fetch('api/toggle_follow.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `user_id=${userId}`
    })
    .then(response => {
        console.log('Response raw:', response);
        return response.text(); // First get text to debug
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            const data = JSON.parse(text);
            console.log('Data received:', data);
            if (data.success) {
                btn.dataset.isFollowing = data.is_following;
                btn.innerHTML = data.is_following ? '✓ Suivi' : 'Suivre';
                if (data.is_following) {
                    btn.style.background = '#E2E8F0';
                    btn.style.color = '#475569';
                } else {
                    btn.style.background = 'var(--primary-blue)';
                    btn.style.color = 'white';
                }
                lucide.createIcons();
            } else {
                alert('Erreur: ' + (data.error || 'Quelque chose s\'est mal passé'));
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            alert('Erreur JSON: ' + text);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur réseau: ' + error);
    });
}

// --- NEW INLINE CHAT FUNCTIONS ---
function switchTab(tab) {
    const tabDiscussions = document.getElementById('tabDiscussions');
    const tabDemandes = document.getElementById('tabDemandes');
    
    if (tab === 'discussions') {
        tabDiscussions.style.color = 'var(--primary-blue)';
        tabDiscussions.style.borderBottom = '2px solid var(--primary-blue)';
        tabDemandes.style.color = '#64748B';
        tabDemandes.style.borderBottom = '2px solid transparent';
    } else {
        tabDemandes.style.color = 'var(--primary-blue)';
        tabDemandes.style.borderBottom = '2px solid var(--primary-blue)';
        tabDiscussions.style.color = '#64748B';
        tabDiscussions.style.borderBottom = '2px solid transparent';
    }
}

function selectConversation(convId, otherUserId, otherName, otherAvatar) {
    // Update active conversation styles
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
        item.style.background = 'transparent';
    });
    const activeItem = document.querySelector(`.conversation-item[data-conv-id="${convId}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
        activeItem.style.background = '#F1F5F9';
    }
    
    // Hide list, show chat area
    document.getElementById('convListPanel').style.display = 'none';
    document.getElementById('activeChatArea').style.display = 'flex';
    
    // Update chat header
    document.getElementById('chatHeaderName').textContent = otherName;
    
    const avatarDiv = document.getElementById('chatHeaderAvatar');
    if (otherAvatar) {
        let avatarPath = otherAvatar;
        if (avatarPath && !avatarPath.startsWith('assets/')) {
            avatarPath = 'assets/uploads/profiles/' + avatarPath;
        }
        avatarDiv.innerHTML = `<img src="${avatarPath}" style="width:100%; height:100%; object-fit: cover;">`;
    } else {
        avatarDiv.innerHTML = `<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--primary-blue); font-weight:700; font-size:16px;">${otherName.charAt(0).toUpperCase()}</div>`;
    }
    
    // Load messages
    loadMessages(convId);
    
    activeConvId = convId;
    currentOtherUser = { id: otherUserId, fullname: otherName, avatar: otherAvatar };
}

function closeChatAndShowList() {
    // Hide chat, show list
    document.getElementById('activeChatArea').style.display = 'none';
    document.getElementById('convListPanel').style.display = 'flex';
    
    // Reset active styles
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
        item.style.background = 'transparent';
    });
    
    // Reset variables
    activeConvId = null;
    currentOtherUser = null;
}

function loadMessages(convId) {
    fetch(`api/get_messages.php?conversation_id=${convId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const messagesContainer = document.getElementById('chatMessages');
            messagesContainer.innerHTML = '';
            data.messages.forEach(msg => {
                appendMessage(msg);
                if (msg.id > lastMessageId) lastMessageId = msg.id;
            });
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    });
    
    markAsRead(convId);
}

function appendMessage(msg) {
    const messagesContainer = document.getElementById('chatMessages');
    const isMine = msg.sender_id == currentUserId;
    
    const div = document.createElement('div');
    div.className = 'message-item ' + (isMine ? 'sent' : 'received');
    div.style.display = 'flex';
    div.style.flexDirection = isMine ? 'row-reverse' : 'row';
    div.style.alignItems = 'flex-end';
    div.style.gap = '10px';
    div.style.marginBottom = '12px';
    
    let attachmentHtml = '';
    if (msg.attachment_path) {
        const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(msg.attachment_path);
        if (isImage) {
            attachmentHtml = `<a href="${msg.attachment_path}" target="_blank" style="max-width:240px; border-radius:16px; overflow:hidden; margin-bottom:8px; display:block;"><img src="${msg.attachment_path}" style="width:100%; display:block;"></a>`;
        } else {
            attachmentHtml = `<a href="${msg.attachment_path}" target="_blank" style="background:#E2E8F0; padding:8px 12px; border-radius:8px; display:inline-block; color:#1E293B; text-decoration:none;">📎 Fichier</a>`;
        }
    }
    
    const msgBubbleStyle = isMine 
        ? 'background:linear-gradient(135deg, var(--primary-blue), #1e3a8a); color:white; border-radius:20px 20px 4px 20px; padding:12px 16px; max-width:70%;'
        : 'background:#E2E8F0; color:#1E293B; border-radius:20px 20px 20px 4px; padding:12px 16px; max-width:70%;';
    
    div.innerHTML = `
        <div style="${msgBubbleStyle}">
            ${attachmentHtml}
            ${msg.message ? `<p style="margin:0; word-wrap:break-word;">${msg.message}</p>` : ''}
        </div>
    `;
    
    messagesContainer.appendChild(div);
}

function sendMessage(event) {
    event.preventDefault();
    if (!activeConvId) return;
    
    const input = document.getElementById('composerInput');
    const message = input.value.trim();
    if (!message) return;
    
    // Clear input immediately for better UX
    input.value = '';
    
    // Send message
    fetch('api/send_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `conversation_id=${activeConvId}&message=${encodeURIComponent(message)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload messages
            loadMessages(activeConvId);
        } else {
            alert('Erreur: ' + (data.error || 'Impossible d\'envoyer le message'));
        }
    });
}

function startConversation(userId) {
    fetch(`api/start_conversation.php?with=${userId}&ajax=1`)
    .then(response => response.json())
    .then(data => {
        console.log('Start conv response:', data);
        if (data.success) {
            // Refresh page to show new conversation
            window.location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de créer la conversation'));
        }
    })
    .catch(err => console.error('Error starting conv:', err));
}

function markAsRead(convId) {
    fetch('api/mark_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `conversation_id=${convId}`
    });
}

// Initialize: no active conversation selected

function trackAdClick(adId) {
    fetch('api/track_ad.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ad_id=${adId}&type=click`
    });
}

function trackAdView(adId) {
    fetch('api/track_ad.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ad_id=${adId}&type=view`
    });
}

// Gérer les likes des publications
document.addEventListener('click', function(e) {
    const likeBtn = e.target.closest('.like-btn');
    if (likeBtn) {
        const postId = likeBtn.dataset.postId;
        const isLiked = likeBtn.dataset.isLiked === 'true';
        
        fetch('api/toggle_like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `post_id=${postId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le bouton
                likeBtn.dataset.isLiked = data.is_liked ? 'true' : 'false';
                likeBtn.style.color = data.is_liked ? '#F43F5E' : '';
                
                // Mettre à jour l'icône
                const icon = likeBtn.querySelector('i[data-lucide]');
                if (icon) {
                    icon.setAttribute('data-lucide', data.is_liked ? 'heart-fill' : 'heart');
                    lucide.createIcons();
                }
                
                // Mettre à jour le compteur
                const countDiv = document.getElementById('like-count-' + postId);
                if (countDiv) {
                    const span = countDiv.querySelector('span');
                    if (span) {
                        span.textContent = data.like_count + ' j\'aime';
                    }
                }
            }
        });
    }
});

// Gérer les sauvegardes des publications
document.addEventListener('click', function(e) {
    const saveBtn = e.target.closest('.save-btn');
    if (saveBtn) {
        const postId = saveBtn.dataset.postId;
        const isSaved = saveBtn.dataset.isSaved === 'true';
        
        fetch('api/toggle_save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `post_id=${postId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le bouton
                saveBtn.dataset.isSaved = data.is_saved ? 'true' : 'false';
                saveBtn.style.color = data.is_saved ? 'var(--primary-blue)' : '';
                
                // Mettre à jour l'icône
                const icon = saveBtn.querySelector('i[data-lucide]');
                if (icon) {
                    icon.setAttribute('data-lucide', data.is_saved ? 'bookmark-check' : 'bookmark');
                    lucide.createIcons();
                }
            }
        });
    }
});

// Gérer l'affichage/masquage des commentaires
document.addEventListener('click', function(e) {
    const commentToggle = e.target.closest('.comment-toggle-btn');
    if (commentToggle) {
        const postId = commentToggle.dataset.postId;
        const commentsSection = document.getElementById('comments-section-' + postId);
        
        if (commentsSection) {
            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                loadComments(postId);
            } else {
                commentsSection.style.display = 'none';
            }
        }
    }
});

// Charger les commentaires d'une publication
function loadComments(postId) {
    const commentsList = document.getElementById('comments-list-' + postId);
    if (!commentsList) return;
    
    fetch('api/get_comments.php?post_id=' + postId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                commentsList.innerHTML = '';
                data.comments.forEach(comment => {
                    commentsList.innerHTML += renderComment(comment);
                });
                lucide.createIcons();
            }
        });
}

// Afficher un commentaire
function renderComment(comment) {
    let avatarHtml = '';
    if (comment.profile_picture) {
        let avatarPath = comment.profile_picture;
        if (!avatarPath.startsWith('assets/')) {
            avatarPath = 'assets/uploads/profiles/' + avatarPath;
        }
        avatarHtml = `<img src="${avatarPath}" style="width: 100%; height: 100%; object-fit: cover;">`;
    } else {
        avatarHtml = `<span style="color: var(--primary-blue); font-weight: 800; font-size: 14px;">${comment.fullname.charAt(0).toUpperCase()}</span>`;
    }
    
    const date = new Date(comment.created_at);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    let formattedDate;
    if (diffMins < 1) formattedDate = 'À l\'instant';
    else if (diffMins < 60) formattedDate = `Il y a ${diffMins} min`;
    else if (diffHours < 24) formattedDate = `Il y a ${diffHours}h`;
    else formattedDate = `Il y a ${diffDays}j`;
    
    return `
        <div class="comment-item" style="display: flex; gap: 12px; margin-bottom: 16px; padding: 8px; border-radius: 12px; transition: background 0.2s;">
            <div class="comment-avatar" style="width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; overflow: hidden; background: linear-gradient(135deg, #E0F2FE, #F1F5F9); display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                ${avatarHtml}
            </div>
            <div class="comment-content" style="flex: 1;">
                <div class="comment-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <span class="comment-author" style="font-weight: 700; color: #1E293B; font-size: 14px;">${comment.fullname}</span>
                    <span style="color: #CBD5E1;">•</span>
                    <span class="comment-date" style="font-size: 12px; color: #94A3B8;">${formattedDate}</span>
                </div>
                <div class="comment-text" style="color: #475569; font-size: 14px; line-height: 1.6; background: #F8FAFC; padding: 10px 14px; border-radius: 14px; border-top-left-radius: 4px; display: inline-block; max-width: 100%; word-wrap: break-word;">${comment.content}</div>
                <div class="comment-actions" style="display: flex; gap: 12px; margin-top: 6px; padding-left: 4px;">
                    <button class="comment-like-btn" style="background: none; border: none; padding: 4px 8px; cursor: pointer; font-size: 12px; color: #64748B; display: flex; align-items: center; gap: 4px; border-radius: 8px; transition: all 0.2s;">
                        <i data-lucide="heart" style="width: 14px; height: 14px;"></i>
                        <span>Répondre</span>
                    </button>
                    <button class="comment-like-btn" style="background: none; border: none; padding: 4px 8px; cursor: pointer; font-size: 12px; color: #64748B; display: flex; align-items: center; gap: 4px; border-radius: 8px; transition: all 0.2s;">
                        <i data-lucide="thumbs-up" style="width: 14px; height: 14px;"></i>
                        <span>J'aime</span>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Ajouter un commentaire
document.addEventListener('click', function(e) {
    const submitBtn = e.target.closest('.comment-submit-btn');
    if (submitBtn) {
        const postId = submitBtn.dataset.postId;
        const commentInput = document.getElementById('comment-input-' + postId);
        
        if (!commentInput) return;
        
        const content = commentInput.value.trim();
        if (!content) return;
        
        fetch('api/add_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `post_id=${postId}&content=${encodeURIComponent(content)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                commentInput.value = '';
                loadComments(postId);
                
                // Mettre à jour le compteur de commentaires
                const engagementDiv = document.getElementById('like-count-' + postId).parentElement;
                const commentSpan = engagementDiv.querySelector('.engagement-item:nth-child(2) span');
                if (commentSpan && data.comment_count) {
                    commentSpan.textContent = data.comment_count + ' commentaires';
                }
            }
        });
    }
});

// Envoyer un commentaire avec la touche Entrée
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.classList.contains('comment-input')) {
        const postId = e.target.id.replace('comment-input-', '');
        const submitBtn = document.querySelector('.comment-submit-btn[data-post-id="' + postId + '"]');
        if (submitBtn) {
            submitBtn.click();
        }
    }
});

// Gérer le partage
document.addEventListener('click', function(e) {
    const shareBtn = e.target.closest('.share-btn');
    if (shareBtn) {
        const postId = shareBtn.dataset.postId;
        const userName = shareBtn.dataset.userName;
        const postContent = shareBtn.dataset.postContent;
        
        // Partager avec l'API navigator.share si disponible
        if (navigator.share) {
            navigator.share({
                title: 'Publication de ' + userName,
                text: postContent + '...',
                url: window.location.href
            }).catch(err => {
                console.log('Erreur de partage:', err);
                fallbackShare(postId, userName, postContent);
            });
        } else {
            fallbackShare(postId, userName, postContent);
        }
    }
});

// Fallback pour le partage (copier lien + incrémenter compteur)
function fallbackShare(postId, userName, postContent) {
    const shareUrl = window.location.href;
    
    // Créer un input temporaire pour copier le lien
    const tempInput = document.createElement('input');
    tempInput.value = shareUrl;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    
    // Incrémenter le compteur côté client et envoyer à l'API
    fetch('api/toggle_share.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `post_id=${postId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour le compteur
            const engagementDiv = document.getElementById('like-count-' + postId).parentElement;
            const shareSpan = engagementDiv.querySelector('.engagement-item:nth-child(3) span');
            if (shareSpan) {
                shareSpan.textContent = data.share_count + ' partages';
            }
            
            // Afficher une confirmation
            alert('Lien copié ! ' + data.share_count + ' partages');
        }
    });
}
</script>
<?php require_once 'includes/footer.php'; ?>