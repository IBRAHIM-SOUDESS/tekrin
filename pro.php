<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Récupération du professionnel par ID ou Username
$id = $_GET['id'] ?? null;
$username = $_GET['username'] ?? null;

if (!$id && !$username) {
    header('Location: index.php');
    exit;
}

try {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (fullname = ? OR id = ?)");
        $stmt->execute([$username, $username]);
    }
    
    $pro = $stmt->fetch();

    if (!$pro) {
        die("Profil non trouvé.");
    }

    $pro_id = $pro['id'];

    // Récupérer les services
    $stmt_services = $pdo->prepare("SELECT * FROM services WHERE user_id = ?");
    $stmt_services->execute([$pro_id]);
    $services_db = $stmt_services->fetchAll();

    // Récupérer le portfolio
    $stmt_portfolio = $pdo->prepare("SELECT * FROM portfolio WHERE user_id = ? ORDER BY created_at DESC");
    $stmt_portfolio->execute([$pro_id]);
    $portfolio = $stmt_portfolio->fetchAll();
    
    // Récupérer les publicités actives du pro
    $now = date('Y-m-d H:i:s');
    $stmt_ads = $pdo->prepare("SELECT * FROM ads WHERE user_id = ? AND is_active = 1 AND start_date <= ? AND end_date >= ? ORDER BY created_at DESC");
    $stmt_ads->execute([$pro_id, $now, $now]);
    $pro_ads = $stmt_ads->fetchAll();

    // Récupérer les publications
    $stmt_posts = $pdo->prepare("
        SELECT p.*, u.fullname, u.profile_picture, u.profession 
        FROM posts p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt_posts->execute([$pro_id]);
    $posts = $stmt_posts->fetchAll();

    // Récupérer les avis
    $stmt_reviews_list = $pdo->prepare("SELECT * FROM reviews WHERE pro_id = ? ORDER BY created_at DESC");
    $stmt_reviews_list->execute([$pro_id]);
    $reviews = $stmt_reviews_list->fetchAll();

    $stmt_reviews_stats = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM reviews WHERE pro_id = ?");
    $stmt_reviews_stats->execute([$pro_id]);
    $review_stats = $stmt_reviews_stats->fetch();
    $avg_rating = round($review_stats['avg_rating'] ?? 0, 1);
    $review_count = $review_stats['count'] ?? 0;

    // Vérifier si l'utilisateur suit le pro
    $is_following = false;
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $pro_id) {
        $stmt_follow = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt_follow->execute([$_SESSION['user_id'], $pro_id]);
        $is_following = $stmt_follow->fetch() !== false;
    }

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// SEO Tags
$page_title = $pro['fullname'] . " - " . ($pro['profession'] ?? 'Professionnel') . " à " . ($pro['city'] ?? 'Cameroun') . " | Tekrin";
$meta_desc = "Découvrez le profil de " . $pro['fullname'] . ", expert en " . ($pro['profession'] ?? 'son domaine') . ". Consultez ses réalisations, ses services et contactez-le directement sur Tekrin.";

$og_image_path = get_user_avatar($pro);
$og_image = $og_image_path ? (strpos($og_image_path, 'http') === 0 ? $og_image_path : 'http://' . $_SERVER['HTTP_HOST'] . '/tekrin/' . $og_image_path) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $meta_desc; ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $meta_desc; ?>">
    <meta property="og:image" content="<?php echo $og_image; ?>">
    <meta property="og:type" content="profile">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        :root {
            --primary-blue: #1565C0;
            --grad-primary: linear-gradient(135deg, #1565C0 0%, #7C4DFF 100%);
            --white: #ffffff;
            --dark-bg: #0F172A;
            --text-muted: #64748B;
            --shadow-premium: 0 10px 30px -5px rgba(0, 0, 0, 0.1), 0 4px 15px -5px rgba(0, 0, 0, 0.05);
        }

        html { scroll-behavior: smooth; }

        .public-profile-page { background: #F1F5F9; min-height: 100vh; padding-bottom: 0; font-family: 'Inter', sans-serif; }
        
        /* Hero Section */
        .pro-hero-wrapper { background: white; margin-bottom: 0; box-shadow: var(--shadow-sm); }
        .pro-hero-container { max-width: 1200px; margin: 0 auto; position: relative; }
        
        .pro-banner { 
            height: 400px; 
            <?php $cover = get_user_cover($pro); ?>
            background: <?php echo $cover ? "url('".$cover."')" : "var(--grad-primary)"; ?>;
            background-size: cover;
            background-position: center;
            border-radius: 0 0 24px 24px;
        }
        
        .pro-hero-main { padding: 0 40px 40px; margin-top: -100px; display: flex; gap: 32px; align-items: flex-end; }
        
        .pro-avatar-huge {
            width: 240px;
            height: 240px;
            border-radius: 30px;
            border: 8px solid white;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            background: #F1F5F9;
            flex-shrink: 0;
            position: relative;
        }
        .pro-avatar-huge img { width: 100%; height: 100%; object-fit: cover; }
        .verification-badge-large { position: absolute; bottom: 10px; right: 10px; background: white; color: var(--primary-blue); border-radius: 50%; padding: 4px; display: flex; box-shadow: var(--shadow-md); }

        .pro-hero-info { flex: 1; margin-bottom: 10px; }
        .pro-hero-name { font-size: 32px; font-weight: 800; color: var(--dark-bg); margin-bottom: 6px; display: flex; align-items: center; gap: 12px; }
        .pro-hero-profession { font-size: 18px; color: var(--primary-blue); font-weight: 700; margin-bottom: 12px; display: block; }
        
        .pro-hero-meta { display: flex; gap: 24px; color: var(--text-muted); font-size: 14px; margin-bottom: 20px; flex-wrap: wrap; }
        .pro-meta-item { display: flex; align-items: center; gap: 8px; }
        
        .pro-hero-rating {
            background: #FFF7ED;
            color: #F59E0B;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }

        .pro-hero-actions { display: flex; gap: 12px; align-items: center; }
        .btn-contact { padding: 14px 32px; border-radius: 14px; font-weight: 700; display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .btn-request-service { background: var(--grad-primary); color: white; border: none; box-shadow: 0 4px 15px rgba(124, 77, 255, 0.3); transition: all 0.3s; }
        .btn-request-service:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(124, 77, 255, 0.4); filter: brightness(1.1); }
        .btn-message-pro { background: white; color: var(--primary-blue); border: 2px solid var(--primary-blue); transition: all 0.3s; }
        .btn-message-pro:hover { background: #EEF2FF; transform: translateY(-2px); }
        .btn-whatsapp { background: #25D366; color: white; border: none; }
        .btn-action-outline { width: 52px; height: 52px; border-radius: 14px; border: 2px solid #E2E8F0; display: flex; align-items: center; justify-content: center; color: #64748B; cursor: pointer; transition: all 0.2s; background: white; }
        .btn-action-outline:hover { border-color: var(--primary-blue); color: var(--primary-blue); }

        /* Navigation Tabs Sticky */
        .pro-nav-tabs-sticky { 
            position: sticky; 
            top: 0; 
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(10px); 
            z-index: 1000; 
            border-bottom: 1px solid #F1F5F9; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .pro-nav-tabs { max-width: 1200px; margin: 0 auto; padding: 0 40px; }
        .pro-tabs-container { display: flex; gap: 40px; }
        .pro-tab-btn { background: none; border: none; padding: 20px 0; font-size: 15px; font-weight: 700; color: #64748B; cursor: pointer; position: relative; text-decoration: none; }
        .pro-tab-btn.active { color: var(--primary-blue); }
        .pro-tab-btn.active::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: var(--primary-blue); border-radius: 4px 4px 0 0; }

        /* Main Content Layout */
        .pro-layout-grid { max-width: 1200px; margin: 32px auto; display: grid; grid-template-columns: 1fr 420px; gap: 32px; padding: 0 20px; }
        
        .pro-section-card { background: white; border-radius: 24px; padding: 32px; margin-bottom: 32px; box-shadow: var(--shadow-premium); scroll-margin-top: 100px; }
        .pro-section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .pro-section-header i { color: var(--primary-blue); width: 24px; }
        .pro-section-header h3 { font-size: 20px; font-weight: 800; color: var(--dark-bg); }

        /* About Grid */
        .about-stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 24px; padding-top: 24px; border-top: 1px solid #F1F5F9; }
        .about-stat-item { display: flex; flex-direction: column; gap: 4px; }
        .stat-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .stat-value { font-size: 15px; font-weight: 700; color: var(--dark-bg); }

        /* Service Cards Premium Grid */
        .services-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 20px; 
            margin-bottom: 24px;
        }
        .service-pro-card { 
            display: flex; 
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 30px 20px; 
            border-radius: 24px; 
            background: white;
            border: 1px solid #F1F5F9; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            transition: all 0.3s ease; 
            height: 100%;
        }
        .service-pro-card:hover { 
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
            border-color: var(--primary-blue);
        }
        .service-pro-icon { 
            width: 64px; 
            height: 64px; 
            border-radius: 50%; 
            background: #EFF6FF; 
            color: var(--primary-blue); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-bottom: 20px;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }
        .service-pro-card:hover .service-pro-icon {
            background: var(--primary-blue);
            color: white;
        }
        .service-pro-info { 
            flex: 1; 
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .service-pro-info h4 { 
            font-size: 16px; 
            font-weight: 800; 
            margin-bottom: 10px; 
            color: var(--dark-bg);
            line-height: 1.3;
        }
        .service-pro-info p { 
            font-size: 13px; 
            color: var(--text-muted); 
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .service-pro-price { 
            width: 100%;
            padding-top: 15px;
            border-top: 1px solid #F1F5F9;
            margin-top: auto;
        }
        .price-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 4px; display: block; }
        .price-amount { font-size: 17px; font-weight: 800; color: var(--primary-blue); }

        /* Portfolio Premium Gallery */
        .portfolio-wrapper {
            position: relative;
            margin: 0 -10px;
        }
        .portfolio-pro-grid { 
            display: flex; 
            gap: 16px; 
            overflow-x: auto; 
            padding: 10px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }
        .portfolio-pro-grid::-webkit-scrollbar { display: none; } /* Masquer scrollbar */
        
        .portfolio-pro-item { 
            flex: 0 0 calc(16.66% - 14px); /* 6 images sur desktop */
            aspect-ratio: 1/1; 
            border-radius: 20px; 
            overflow: hidden; 
            cursor: pointer; 
            position: relative; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .portfolio-pro-item:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .portfolio-pro-item img { width: 100%; height: 100%; object-fit: cover; }
        .portfolio-overlay { 
            position: absolute; 
            inset: 0; 
            background: rgba(21, 101, 192, 0.2); 
            opacity: 0; 
            transition: opacity 0.3s; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
        }
        .portfolio-pro-item:hover .portfolio-overlay { opacity: 1; }

        /* Lightbox Premium with Navigation */
        .lightbox { 
            display: none; 
            position: fixed; 
            z-index: 3000; 
            inset: 0; 
            background: rgba(15, 23, 42, 0.98); 
            justify-content: center; 
            align-items: center; 
            backdrop-filter: blur(12px); 
        }
        .lightbox-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }
        .lightbox-content { 
            max-width: 90%; 
            max-height: 85vh; 
            border-radius: 16px; 
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); 
            object-fit: contain;
        }
        .lightbox-close { position: absolute; top: 30px; right: 40px; color: white; cursor: pointer; transition: 0.3s; z-index: 3010; }
        .lightbox-close:hover { transform: scale(1.1); color: #EF4444; }
        
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            backdrop-filter: blur(4px);
            z-index: 3010;
        }
        .lightbox-nav:hover { background: var(--primary-blue); border-color: var(--primary-blue); }
        .lightbox-prev { left: 40px; }
        .lightbox-next { right: 40px; }

        @media (max-width: 1024px) {
            .portfolio-pro-item { flex: 0 0 calc(25% - 12px); } /* 4 images sur tablette */
            .lightbox-nav { width: 48px; height: 48px; }
            .lightbox-prev { left: 10px; }
            .lightbox-next { right: 10px; }
        }

        @media (max-width: 640px) {
            .portfolio-pro-item { flex: 0 0 calc(45% - 10px); } /* ~2 images sur mobile */
            .portfolio-pro-grid { padding-bottom: 20px; }
        }

        /* --- PROFESSIONAL GALLERY REDESIGN --- */
        #publications-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }

        .pro-gallery-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #F1F5F9;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 480px; /* Fixed identical height for all cards */
            transition: all 0.3s ease;
        }

        .pro-gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
            border-color: var(--primary-blue);
        }

        /* Image Area - Fixed Size */
        .card-image-area {
            height: 280px; /* Fixed height for the image container */
            width: 100%;
            background: #F8FAFC;
            overflow: hidden;
            position: relative;
        }

        .adaptive-grid {
            display: grid;
            width: 100%;
            height: 100%;
            gap: 2px;
        }

        .adaptive-grid img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }

        /* Grid Variations */
        .grid-1 { grid-template-columns: 1fr; }
        .grid-2 { grid-template-columns: 1fr 1fr; }
        .grid-3 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }
        .grid-3 .img-item:first-child { grid-row: span 2; }
        .grid-4 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }

        .img-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: 800;
            pointer-events: none;
        }

        /* Text Area - Fixed Content */
        .card-text-area {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--dark-bg);
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-description {
            font-size: 14px;
            color: #64748B;
            line-height: 1.5;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Limit to 3 lines */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #F1F5F9;
        }

        .card-date {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .card-more-link {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary-blue);
            text-decoration: none;
            cursor: pointer;
        }

        @media (max-width: 1024px) {
            #publications-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            #publications-container {
                grid-template-columns: 1fr;
            }
            .pro-gallery-card {
                height: auto;
                min-height: 450px;
            }
        }

        /* Hide old post styles if they conflict */
        .feed-post-card { display: none; }
        .pro-gallery-card.active-display { display: flex; }


        .pro-layout-grid {
            display: grid;
            grid-template-columns: 1fr 380px; /* Strong 2-column layout */
            gap: 40px;
            margin-top: 40px;
            align-items: start;
        }

        .pro-sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
            position: sticky;
            top: 100px; /* Sidebar stays visible while scrolling */
        }

        .widget-premium {
            background: white;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            border: 1px solid #F1F5F9;
        }

        .widget-title-small {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* --- STATS GRID --- */
        .mini-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        .mini-stat-card {
            background: #F8FAFC;
            padding: 16px;
            border-radius: 16px;
            text-align: center;
        }

        .mini-stat-value {
            display: block;
            font-size: 18px;
            font-weight: 900;
            color: var(--primary-blue);
        }

        .mini-stat-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        /* Adjust Main Column for richness */
        .pro-section-card {
            background: white;
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 32px;
            border: 1px solid #F1F5F9;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        }

        
        .contact-list { display: flex; flex-direction: column; gap: 20px; }
        .contact-item { display: flex; align-items: center; gap: 16px; }
        .contact-icon { width: 48px; height: 48px; border-radius: 12px; background: #F1F5F9; color: var(--primary-blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .contact-icon i { width: 22px; height: 22px; }
        .contact-data { display: flex; flex-direction: column; }
        .contact-label { font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 2px; }
        .contact-value { font-size: 16px; font-weight: 700; color: var(--dark-bg); }

        .badge-pro-item { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; padding: 12px; border-radius: 12px; background: #F8FAFC; border: 1px solid #F1F5F9; }
        .badge-pro-icon { font-size: 20px; }
        .badge-pro-text { font-size: 13px; font-weight: 700; color: var(--dark-bg); }

        /* Lightbox */
        .lightbox { display: none; position: fixed; z-index: 3000; inset: 0; background: rgba(15, 23, 42, 0.95); justify-content: center; align-items: center; padding: 40px; backdrop-filter: blur(8px); }
        .lightbox-content { max-width: 90%; max-height: 90%; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .lightbox-close { position: absolute; top: 30px; right: 40px; color: white; font-size: 48px; cursor: pointer; font-weight: 200; }

        @media (max-width: 1024px) {
            .pro-layout-grid { grid-template-columns: 1fr; }
            .pro-hero-main { flex-direction: column; align-items: center; text-align: center; margin-top: -100px; padding: 0 20px 32px; }
            .pro-hero-meta { justify-content: center; }
            .pro-hero-actions { width: 100%; justify-content: center; }
            .pro-nav-tabs { padding: 0 20px; overflow-x: auto; }
            .pro-tabs-container { gap: 24px; }
            .portfolio-pro-grid { grid-template-columns: repeat(2, 1fr); }
            .services-grid { grid-template-columns: repeat(2, 1fr); }
            #publications-container { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .services-grid { grid-template-columns: 1fr; }
        }

        /* Review Styles */
        .reviews-container-flex {
            display: flex;
            gap: 40px;
            margin-top: 24px;
        }

        /* Modal Service Request */
        .modal-premium {
            display: none;
            position: fixed;
            z-index: 4000;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-premium-content {
            background: white;
            width: 100%;
            max-width: 650px;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            animation: modalSlideUp 0.3s ease-out;
        }
        @keyframes modalSlideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-premium-header {
            background: var(--grad-primary);
            padding: 30px;
            color: white;
            position: relative;
        }
        .modal-premium-header h2 { font-size: 24px; font-weight: 800; margin-bottom: 8px; }
        .modal-premium-header p { opacity: 0.9; font-size: 15px; }
        .btn-close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-close-modal:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
        
        .modal-premium-body { padding: 30px; max-height: 70vh; overflow-y: auto; }
        .form-grid-premium { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group-full { grid-column: span 2; }
        .form-group-premium { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .form-group-premium label { font-size: 13px; font-weight: 800; color: var(--dark-bg); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control-premium {
            background: #F8FAFC;
            border: 2px solid #F1F5F9;
            padding: 14px 18px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            color: var(--dark-bg);
            transition: all 0.2s;
            outline: none;
        }
        .form-control-premium:focus { border-color: var(--primary-blue); background: white; box-shadow: 0 0 0 4px rgba(21, 101, 192, 0.1); }
        
        .file-upload-premium {
            border: 2px dashed #E2E8F0;
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .file-upload-premium:hover { border-color: var(--primary-blue); background: #F8FAFC; }
        .file-upload-premium i { width: 32px; height: 32px; color: var(--text-muted); margin-bottom: 8px; }
        .file-upload-premium span { display: block; font-size: 14px; font-weight: 700; color: #64748B; }
        
        .modal-premium-footer { padding: 20px 30px 30px; display: flex; gap: 15px; }
        .btn-premium-submit { flex: 1; background: var(--grad-primary); color: white; border: none; padding: 16px; border-radius: 14px; font-weight: 800; font-size: 16px; cursor: pointer; box-shadow: 0 10px 20px rgba(124, 77, 255, 0.2); transition: all 0.3s; }
        .btn-premium-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(124, 77, 255, 0.3); }

        /* Left Area: Summary */
        .reviews-summary-box {
            flex: 0 0 280px;
            background: #F8FAFC;
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            height: fit-content;
        }

        .overall-rating-big {
            font-size: 48px;
            font-weight: 900;
            color: var(--dark-bg);
            line-height: 1;
            margin-bottom: 8px;
        }

        .overall-stars-big {
            color: #FBBF24;
            font-size: 20px;
            margin-bottom: 8px;
        }

        .total-reviews-label {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 24px;
        }

        .rating-distribution {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .dist-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #64748B;
        }

        .dist-bar-bg {
            flex: 1;
            height: 6px;
            background: #E2E8F0;
            border-radius: 10px;
            overflow: hidden;
        }

        .dist-bar-fill {
            height: 100%;
            background: #FBBF24;
            border-radius: 10px;
        }

        /* Right Area: Grid */
        .reviews-grid-area {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .review-pro-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #F1F5F9;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            height: 240px; /* Fixed height for alignment */
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .review-pro-card:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
        }

        .review-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .reviewer-pro-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .reviewer-pro-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--grad-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
        }

        .reviewer-pro-name {
            font-size: 15px;
            font-weight: 800;
            color: var(--dark-bg);
            margin-bottom: 2px;
        }

        .review-pro-stars {
            color: #FBBF24;
            font-size: 14px;
            display: flex;
            gap: 2px;
        }

        .review-pro-text {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 3; /* Strictly 3 lines */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .review-pro-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid #F8FAFC;
        }

        .review-pro-date {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .trust-badge-mini {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #10B981; /* Success Green */
            background: #ECFDF5;
            padding: 4px 8px;
            border-radius: 50px;
        }

        @media (max-width: 1200px) {
            .reviews-container-flex { flex-direction: column; }
            .reviews-summary-box { flex: 1; width: 100%; display: flex; align-items: center; justify-content: space-around; text-align: left; }
            .rating-distribution { width: 300px; }
        }

        @media (max-width: 768px) {
            .reviews-grid-area { grid-template-columns: 1fr; }
            .reviews-summary-box { flex-direction: column; text-align: center; }
            .rating-distribution { width: 100%; }
            .review-pro-card { height: auto; min-height: 220px; }
        }
    </style>
</head>
<body class="public-profile-page">

    <?php echo displayFlashMessage(); ?>

    <!-- Header / Banner Section -->
    <header class="pro-hero-wrapper">
        <div class="pro-hero-container">
            <div class="pro-banner"></div>
            <div class="pro-hero-main">
                <div class="pro-avatar-huge">
                    <?php 
                    $avatar = get_user_avatar($pro);
                    if ($avatar): ?>
                        <img src="<?php echo $avatar; ?>" alt="<?php echo htmlspecialchars($pro['fullname']); ?>">
                    <?php else: ?>
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f1f5f9; color:var(--primary-blue); font-size:64px; font-weight:800;">
                            <?php echo strtoupper(substr($pro['fullname'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="verification-badge-large">
                        <i data-lucide="check-circle" fill="currentColor" style="color:white; fill:var(--primary-blue);"></i>
                    </div>
                </div>
                
                <div class="pro-hero-info">
                    <span class="pro-hero-profession"><?php echo htmlspecialchars($pro['profession'] ?? 'Artisan Professionnel'); ?></span>
                    <h1 class="pro-hero-name">
                        <?php echo htmlspecialchars($pro['fullname']); ?>
                    </h1>
                    
                    <div class="pro-hero-meta">
                        <div class="pro-meta-item">
                            <i data-lucide="map-pin" style="width:16px;"></i>
                            <span><?php echo htmlspecialchars($pro['city'] ?? 'Cameroun'); ?></span>
                        </div>
                        <div class="pro-meta-item">
                            <i data-lucide="calendar" style="width:16px;"></i>
                            <span>Membre depuis <?php echo date('M Y', strtotime($pro['created_at'])); ?></span>
                        </div>
                        <div class="pro-hero-rating">
                            <i data-lucide="star" fill="currentColor" style="width:18px;"></i>
                            <span><?php echo $avg_rating; ?> (<?php echo $review_count; ?> avis)</span>
                        </div>
                    </div>

                    <div class="pro-hero-actions">
                        <button class="btn btn-contact btn-request-service" onclick="openRequestModal()">
                            <i data-lucide="send"></i> Demander un service
                        </button>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $pro_id): ?>
                            <button class="btn btn-contact" id="follow-pro-btn" data-user-id="<?php echo $pro_id; ?>" data-is-following="<?php echo $is_following ? 'true' : 'false'; ?>" onclick="toggleFollowPro(this)" style="background: <?php echo $is_following ? '#E2E8F0' : 'var(--primary-blue)'; ?>; color: <?php echo $is_following ? '#475569' : 'white'; ?>; border-radius: 14px; font-weight: 800;">
                                <i data-lucide="<?php echo $is_following ? 'check' : 'plus'; ?>"></i> 
                                <?php echo $is_following ? 'Suivi' : 'Suivre'; ?>
                            </button>
                            <button class="btn btn-contact btn-message-pro" onclick="openChatWithPro(<?php echo $pro_id; ?>)">
                                <i data-lucide="message-square"></i> Contacter
                            </button>
                        <?php else: ?>
                            <a href="api/start_conversation.php?with=<?php echo $pro['id']; ?>" class="btn btn-contact btn-message-pro">
                                <i data-lucide="message-square"></i> Contacter
                            </a>
                        <?php endif; ?>
                        <a href="tel:<?php echo $pro['phone_number'] ?? ''; ?>" class="btn btn-register btn-contact">
                            <i data-lucide="phone"></i>
                        </a>
                        <a href="https://wa.me/<?php echo str_replace([' ', '+'], '', $pro['whatsapp_number'] ?? $pro['phone_number'] ?? ''); ?>" class="btn btn-whatsapp btn-contact">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            WhatsApp
                        </a>
                        <button class="btn-action-outline" onclick="shareProfile()" title="Partager">
                            <i data-lucide="share-2"></i>
                        </button>
                        <button class="btn-action-outline" onclick="toggleSave(this)" title="Enregistrer">
                            <i data-lucide="bookmark"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Profile Tabs (Sticky) -->
            <div class="pro-nav-tabs-sticky">
                <nav class="pro-nav-tabs">
                    <div class="pro-tabs-container">
                        <a href="#about" class="pro-tab-btn active">À propos</a>
                        <a href="#services" class="pro-tab-btn">Services</a>
                        <a href="#realisations" class="pro-tab-btn">Réalisations</a>
                        <a href="#publicites" class="pro-tab-btn">Publicités</a>
                        <a href="#publications" class="pro-tab-btn">Publications</a>
                        <a href="#avis" class="pro-tab-btn">Avis</a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <div class="pro-layout-grid">
        <!-- Main Column -->
        <main class="pro-main-col">
            
            <!-- Section: À propos -->
            <section id="about" class="pro-section-card">
                <div class="pro-section-header">
                    <i data-lucide="user"></i>
                    <h3>À propos du professionnel</h3>
                </div>
                <p style="color: #475569; line-height: 1.8; font-size: 16px; margin-bottom: 24px;">
                    <?php echo nl2br(htmlspecialchars($pro['bio'] ?? "Ce professionnel n'a pas encore rédigé sa présentation détaillée.")); ?>
                </p>
                
                <?php if ($pro['work_philosophy']): ?>
                <div style="background: #F8FAFC; padding: 24px; border-radius: 16px; border-left: 4px solid var(--primary-blue);">
                    <h4 style="font-size: 14px; font-weight: 800; text-transform: uppercase; color: var(--primary-blue); margin-bottom: 8px;">Ma philosophie</h4>
                    <p style="font-size: 15px; color: #475569; font-style: italic;">"<?php echo htmlspecialchars($pro['work_philosophy']); ?>"</p>
                </div>
                <?php endif; ?>

                <div class="about-stats-grid">
                    <div class="about-stat-item">
                        <span class="stat-label">Expérience</span>
                        <span class="stat-value"><?php echo $pro['experience_years'] ?? '5+'; ?> ans d'expertise</span>
                    </div>
                    <div class="about-stat-item">
                        <span class="stat-label">Disponibilité</span>
                        <span class="stat-value"><?php echo htmlspecialchars($pro['availability_status'] ?? 'Disponible immédiatement'); ?></span>
                    </div>
                    <div class="about-stat-item">
                        <span class="stat-label">Réactivité</span>
                        <span class="stat-value"><?php echo htmlspecialchars($pro['response_speed'] ?? 'Réponse en < 1h'); ?></span>
                    </div>
                    <div class="about-stat-item">
                        <span class="stat-label">Zone d'intervention</span>
                        <span class="stat-value"><?php echo htmlspecialchars($pro['service_area'] ?? $pro['city'] . ' et environs'); ?></span>
                    </div>
                </div>
            </section>

            <!-- Section: Services -->
            <section id="services" class="pro-section-card">
                <div class="pro-section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i data-lucide="briefcase"></i>
                        <h3>Services</h3>
                    </div>
                    <?php if (count($services_db) > 4): ?>
                        <a href="javascript:void(0)" onclick="loadAllServices()" id="view-all-services-link" style="color: var(--primary-blue); font-weight: 700; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                            Voir tous
                            <i data-lucide="chevron-right" style="width: 16px;"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="services-grid" id="services-container">
                    <?php 
                    if (!empty($services_db)):
                        $initial_services = array_slice($services_db, 0, 4);
                        foreach ($initial_services as $s): ?>
                            <div class="service-pro-card">
                                <div class="service-pro-icon"><i data-lucide="check-circle"></i></div>
                                <div class="service-pro-info">
                                    <h4><?php echo htmlspecialchars($s['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($s['description']); ?></p>
                                    <div class="service-pro-price">
                                        <span class="price-label">À partir de</span>
                                        <span class="price-amount"><?php echo number_format($s['price_starting'], 0, ',', ' '); ?> FCFA</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    else:
                        $old_services = explode(',', $pro['services'] ?? '');
                        foreach ($old_services as $s): if (empty(trim($s))) continue; ?>
                            <div class="service-pro-card">
                                <div class="service-pro-icon"><i data-lucide="settings"></i></div>
                                <div class="service-pro-info">
                                    <h4><?php echo htmlspecialchars(trim($s)); ?></h4>
                                    <p>Prestation professionnelle de haute qualité avec garantie de satisfaction.</p>
                                    <div class="service-pro-price">
                                        <span class="price-label">À partir de</span>
                                        <span class="price-amount">Sur devis</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach;
                    endif; ?>
                </div>
            </section>

            <!-- Section: Réalisations -->
            <section id="realisations" class="pro-section-card">
                <div class="pro-section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i data-lucide="image"></i>
                        <h3>Réalisations</h3>
                    </div>
                    <?php if (count($portfolio) > 6): ?>
                        <a href="javascript:void(0)" onclick="scrollPortfolio(1)" style="color: var(--primary-blue); font-weight: 700; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                            Voir toutes
                            <i data-lucide="chevron-right" style="width: 16px;"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($portfolio)): ?>
                    <div style="text-align: center; padding: 40px; background: #F8FAFC; border-radius: 20px;">
                        <p style="color: var(--text-muted);">Aucune réalisation photo pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div class="portfolio-wrapper">
                        <div class="portfolio-pro-grid" id="portfolio-grid">
                            <?php foreach ($portfolio as $index => $item): 
                                $p_path = $item['image_path'] ?? '';
                                // Si le chemin ne commence pas par assets/, on assume l'ancien dossier par défaut
                                if ($p_path && strpos($p_path, 'assets/') !== 0) {
                                    $p_path = 'assets/uploads/portfolio/' . $p_path;
                                }
                            ?>
                                <div class="portfolio-pro-item" onclick="openLightbox(<?php echo $index; ?>)" style="position: relative;">
                                    <img src="<?php echo $p_path; ?>" alt="<?php echo htmlspecialchars($item['title'] ?? 'Réalisation'); ?>" loading="lazy">
                                    <div class="portfolio-overlay" style="display: flex; flex-direction: column; gap: 8px; align-items: center; justify-content: center; text-align: center; padding: 12px;">
                                        <?php if ($item['title']): ?>
                                            <h4 style="color: white; font-weight: 800; font-size: 16px; margin: 0;"><?php echo htmlspecialchars($item['title']); ?></h4>
                                        <?php endif; ?>
                                        <i data-lucide="maximize-2"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Section: Publicités -->
            <section id="publicites" class="pro-section-card">
                <div class="pro-section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i data-lucide="trending-up"></i>
                        <h3>Publicités</h3>
                    </div>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $pro_id): ?>
                        <a href="ads_manager.php" style="color: var(--primary-blue); font-weight: 700; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                            Gérer mes publicités
                            <i data-lucide="settings" style="width: 16px;"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($pro_ads)): ?>
                    <div style="text-align: center; padding: 40px; background: #F8FAFC; border-radius: 20px;">
                        <i data-lucide="megaphone" style="width: 48px; height: 48px; color: #94A3B8; margin-bottom: 12px;"></i>
                        <p style="color: var(--text-muted);">Aucune publicité active pour le moment.</p>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $pro_id): ?>
                            <a href="ads_manager.php" class="btn btn-register" style="margin-top: 12px;">Créer ma première publicité</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">
                        <?php foreach ($pro_ads as $ad): 
                            // Track view on display
                            $pdo->prepare("UPDATE ads SET views = views + 1 WHERE id = ?")->execute([$ad['id']]);
                        ?>
                            <div class="ad-card" style="background: white; border-radius: 20px; border: 2px solid #FFD700; overflow: hidden; box-shadow: 0 4px 15px rgba(255, 215, 0, 0.1);">
                                <div style="background: linear-gradient(135deg, #FFFBEB, #FFF8DC); padding: 10px 20px; display: flex; align-items: center; gap: 8px;">
                                    <i data-lucide="trending-up" style="color: #D97706; width: 16px; height: 16px;"></i>
                                    <span style="font-weight: 800; color: #B45309; font-size: 12px; text-transform: uppercase;">Publicité</span>
                                </div>
                                
                                <?php if ($ad['image_url']): ?>
                                    <div style="height: 200px; overflow: hidden;">
                                        <img src="<?php echo htmlspecialchars($ad['image_url']); ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>" style="width:100%; height:100%; object-fit: cover;">
                                    </div>
                                <?php endif; ?>
                                
                                <div style="padding: 24px;">
                                    <h4 style="font-weight: 900; color: #1E293B; margin-bottom: 10px; font-size: 18px;">
                                        <?php echo htmlspecialchars($ad['title']); ?>
                                    </h4>
                                    <?php if ($ad['description']): ?>
                                        <p style="color: #64748B; margin-bottom: 20px; line-height: 1.6;">
                                            <?php echo htmlspecialchars($ad['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($ad['link']): ?>
                                        <a href="<?php echo htmlspecialchars($ad['link']); ?>" target="_blank" onclick="trackAdClick(<?php echo $ad['id']; ?>)" class="btn btn-register" style="width:100%; display: flex; align-items: center; justify-content: center; gap:8px;">
                                            En savoir plus
                                            <i data-lucide="arrow-right" style="width:16px; height:16px;"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Section: Publications -->
            <section id="publications" style="margin-bottom: 32px; scroll-margin-top: 100px;">
                <div class="pro-section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding: 0 10px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i data-lucide="layout"></i>
                        <h3>Publications</h3>
                    </div>
                    <?php if (count($posts) > 2): ?>
                        <a href="javascript:void(0)" onclick="loadAllPublications()" id="view-all-posts-link" style="color: var(--primary-blue); font-weight: 700; font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                            Voir toutes
                            <i data-lucide="chevron-right" style="width: 16px;"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <div id="publications-container">
                    <?php if (empty($posts)): ?>
                        <div class="pro-gallery-card active-display" style="text-align: center; padding: 40px; height: auto;">
                            <p style="color: var(--text-muted);">Ce professionnel n'a pas encore publié de contenu.</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $initial_posts = array_slice($posts, 0, 2); // Display exactly 2 publications initially
                        foreach ($initial_posts as $post): 
                            $post_images = json_decode($post['images'] ?? '[]', true);
                            $img_count = count($post_images);
                            
                            // Extract title and description from content
                            $content = htmlspecialchars($post['content']);
                            $lines = explode("\n", $content);
                            $title = $lines[0];
                            $description = count($lines) > 1 ? implode(" ", array_slice($lines, 1)) : $content;
                        ?>
                            <article class="pro-gallery-card active-display">
                                <!-- Fixed Size Image Area -->
                                <div class="card-image-area">
                                    <?php if (!empty($post_images)): ?>
                                        <div class="adaptive-grid grid-<?php echo min($img_count, 4); ?>">
                                            <?php foreach (array_slice($post_images, 0, 4) as $idx => $img): ?>
                                                <div class="img-item" style="position: relative; height: 100%;">
                                                    <img src="assets/uploads/posts/<?php echo $img; ?>" 
                                                         onclick="openPostLightbox(<?php echo htmlspecialchars(json_encode($post_images)); ?>, <?php echo $idx; ?>)"
                                                         loading="lazy">
                                                    <?php if ($idx === 3 && $img_count > 4): ?>
                                                        <div class="img-overlay">
                                                            +<?php echo $img_count - 4; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #CBD5E1;">
                                            <i data-lucide="file-text" style="width: 48px; height: 48px;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Fixed Content Area -->
                                <div class="card-text-area">
                                    <div class="card-body-top">
                                        <h4 class="card-title"><?php echo $title; ?></h4>
                                        <p class="card-description"><?php echo $description; ?></p>
                                    </div>
                                    <div class="card-footer">
                                        <span class="card-date"><?php echo date('d M Y', strtotime($post['created_at'])); ?></span>
                                        <a href="javascript:void(0)" class="card-more-link" onclick="openFullPost(event, <?php echo $post['id']; ?>)">Voir plus</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (count($posts) > 2): ?>
                    <div id="view-all-posts-link" style="text-align: center; margin-top: 30px;">
                        <button class="btn btn-outline" onclick="loadAllPublications()" style="padding: 12px 40px; border-radius: 50px; font-weight: 700;">
                            Voir toutes les réalisations (<?php echo count($posts); ?>)
                        </button>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Section: Avis -->
            <section id="avis" class="pro-section-card">
                <div class="pro-section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i data-lucide="star"></i>
                        <h3>Avis clients</h3>
                    </div>
                    <?php if (count($reviews) > 4): ?>
                        <a href="javascript:void(0)" class="card-more-link" style="display: flex; align-items: center; gap: 4px;">
                            Voir tous <i data-lucide="chevron-right" style="width: 16px;"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="reviews-grid-area">
                    <?php if (empty($reviews)): ?>
                        <div style="grid-column: span 2; text-align: center; padding: 40px; background: #F8FAFC; border-radius: 20px;">
                            <p style="color: var(--text-muted);">Aucun avis pour le moment.</p>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $pro_id): ?>
                                <button class="btn btn-register" style="margin-top: 15px;" onclick="openReviewModal()">Laisser le premier avis</button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php 
                        $display_reviews = array_slice($reviews, 0, 4); // Display 4 reviews (2x2)
                        foreach ($display_reviews as $rev): 
                            $is_verified = true;
                            $via_tekrin = true;
                        ?>
                            <div class="review-pro-card">
                                <div class="review-card-header">
                                    <div class="reviewer-pro-info">
                                        <div class="reviewer-pro-avatar">
                                            <?php echo strtoupper(substr($rev['author_name'] ?? 'C', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="reviewer-pro-name"><?php echo htmlspecialchars($rev['author_name'] ?? 'Anonyme'); ?></div>
                                            <div class="review-pro-stars">
                                                <?php for($i=1; $i<=5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($is_verified): ?>
                                        <div class="trust-badge-mini" title="Client vérifié par Tekrin">
                                            <i data-lucide="shield-check" style="width: 12px;"></i>
                                            Vérifié
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="review-pro-text"><?php echo htmlspecialchars($rev['comment']); ?></p>
                                
                                <div class="review-pro-footer">
                                    <span class="review-pro-date"><?php echo date('d M Y', strtotime($rev['created_at'])); ?></span>
                                    <?php if ($via_tekrin): ?>
                                        <span style="font-size: 10px; color: #10B981; font-weight: 800; text-transform: uppercase;">Réalisé via Tekrin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $pro_id): ?>
                    <div style="text-align: center; margin-top: 30px;">
                        <button class="btn btn-outline" onclick="openReviewModal()" style="padding: 12px 40px; border-radius: 50px; font-weight: 700;">
                            Laisser un avis client
                        </button>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <!-- Sidebar Widgets -->
        <aside class="pro-sidebar">
            
            <!-- Quick Stats Widget -->
            <div class="widget-premium">
                <h4 class="widget-title-small">Performances</h4>
                <div class="mini-stats-grid">
                    <div class="mini-stat-card">
                        <span class="mini-stat-value"><?php echo $review_count; ?>+</span>
                        <span class="mini-stat-label">Clients</span>
                    </div>
                    <div class="mini-stat-card">
                        <span class="mini-stat-value"><?php echo count($portfolio); ?></span>
                        <span class="mini-stat-label">Projets</span>
                    </div>
                    <div class="mini-stat-card">
                        <span class="mini-stat-value"><?php echo $pro['experience_years'] ?? '5'; ?>+</span>
                        <span class="mini-stat-label">Expérience</span>
                    </div>
                    <div class="mini-stat-card">
                        <span class="mini-stat-value">100%</span>
                        <span class="mini-stat-label">Fiabilité</span>
                    </div>
                </div>
            </div>

            <!-- Review Summary Widget (Moved from bottom) -->
            <div class="widget-premium">
                <h4 class="widget-title-small">Note globale</h4>
                <div style="text-align: center; margin-bottom: 20px;">
                    <div class="overall-rating-big" style="font-size: 42px;"><?php echo number_format($avg_rating, 1); ?></div>
                    <div class="overall-stars-big">
                        <?php for($i=1; $i<=5; $i++) echo $i <= round($avg_rating) ? '★' : '☆'; ?>
                    </div>
                </div>
                <div class="rating-distribution">
                    <?php 
                    for ($i=5; $i>=1; $i--): 
                        $count_star = 0;
                        foreach($reviews as $r) if($r['rating'] == $i) $count_star++;
                        $percent = $review_count > 0 ? round(($count_star / $review_count) * 100) : 0;
                    ?>
                    <div class="dist-row">
                        <span style="width: 12px;"><?php echo $i; ?></span>
                        <div class="dist-bar-bg"><div class="dist-bar-fill" style="width: <?php echo $percent; ?>%;"></div></div>
                        <span style="width: 35px; text-align: right;"><?php echo $percent; ?>%</span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Contact Widget -->
            <div class="widget-premium">
                <h4 class="widget-title-small">Coordonnées & Infos</h4>
                <div class="contact-list">
                    <div class="contact-item">
                        <div class="contact-icon"><i data-lucide="phone" style="width:18px;"></i></div>
                        <div class="contact-data">
                            <span class="contact-label">Téléphone</span>
                            <span class="contact-value"><?php echo htmlspecialchars($pro['phone_number'] ?? 'Non renseigné'); ?></span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon"><i data-lucide="mail" style="width:18px;"></i></div>
                        <div class="contact-data">
                            <span class="contact-label">Email</span>
                            <span class="contact-value"><?php echo htmlspecialchars($pro['email'] ?? 'Contact via Tekrin'); ?></span>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon"><i data-lucide="clock" style="width:18px;"></i></div>
                        <div class="contact-data">
                            <span class="contact-label">Disponibilité</span>
                            <span class="contact-value"><?php echo htmlspecialchars($pro['availability_status'] ?? 'Lun - Sam, 8h-18h'); ?></span>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 12px;">
                    <a href="tel:<?php echo $pro['phone_number'] ?? ''; ?>" class="btn btn-register" style="width: 100%; text-align: center; justify-content: center;">
                        <i data-lucide="phone" style="width:18px; margin-right: 8px;"></i> Appeler
                    </a>
                    <a href="https://wa.me/<?php echo str_replace([' ', '+'], '', $pro['whatsapp_number'] ?? $pro['phone_number'] ?? ''); ?>" class="btn btn-whatsapp" style="width: 100%; text-align: center; justify-content: center; background: #25D366; color: white; border: none;">
                        <i data-lucide="message-circle" style="width:18px; margin-right: 8px;"></i> WhatsApp
                    </a>
                </div>
            </div>

            <!-- Badges Widget -->
            <div class="widget-premium">
                <h4 class="widget-title-small">Récompenses & Badges</h4>
                <div class="badge-pro-item">
                    <span class="badge-pro-icon">🛡️</span>
                    <span class="badge-pro-text">Professionnel Vérifié</span>
                </div>
                <div class="badge-pro-item">
                    <span class="badge-pro-icon">⚡</span>
                    <span class="badge-pro-text">Super Réactif</span>
                </div>
            </div>
        </aside>
    </div>

    <!-- Modals -->
    <!-- Request Service Modal -->
    <div id="requestModal" class="modal-premium">
        <div class="modal-premium-content">
            <header class="modal-premium-header">
                <button class="btn-close-modal" onclick="closeRequestModal()">
                    <i data-lucide="x"></i>
                </button>
                <h2>Demander un service</h2>
                <p>Décrivez votre projet à <?php echo htmlspecialchars($pro['fullname']); ?> pour recevoir une proposition.</p>
            </header>
            
            <form action="api/submit_request.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="pro_id" value="<?php echo $pro['id']; ?>">
                
                <div class="modal-premium-body">
                    <div class="form-grid-premium">
                        <div class="form-group-premium">
                            <label>Catégorie de service</label>
                            <select name="service_category" class="form-control-premium" required>
                                <option value="">Sélectionner...</option>
                                <option value="Électricité">Électricité</option>
                                <option value="Plomberie">Plomberie</option>
                                <option value="Menuiserie">Menuiserie</option>
                                <option value="Informatique">Informatique</option>
                                <option value="Mécanique">Mécanique</option>
                                <option value="Maçonnerie">Maçonnerie</option>
                                <option value="Peinture">Peinture</option>
                                <option value="Climatisation">Climatisation</option>
                                <option value="Autres">Autres</option>
                            </select>
                        </div>
                        
                        <div class="form-group-premium">
                            <label>Titre du projet</label>
                            <input type="text" name="project_title" class="form-control-premium" placeholder="Ex: Réparation tableau électrique" required>
                        </div>
                        
                        <div class="form-group-premium form-group-full">
                            <label>Description détaillée</label>
                            <textarea name="description" class="form-control-premium" rows="4" placeholder="Décrivez votre besoin avec précision..." required></textarea>
                        </div>
                        
                        <div class="form-group-premium">
                            <label>Budget estimé (FCFA)</label>
                            <input type="number" name="budget" class="form-control-premium" placeholder="Facultatif">
                        </div>
                        
                        <div class="form-group-premium">
                            <label>Date souhaitée</label>
                            <input type="date" name="desired_date" class="form-control-premium">
                        </div>
                        
                        <div class="form-group-premium">
                            <label>Localisation</label>
                            <input type="text" name="location" class="form-control-premium" placeholder="Ville, Quartier" required>
                        </div>
                        
                        <div class="form-group-premium">
                            <label>Préférence de contact</label>
                            <select name="contact_preference" class="form-control-premium">
                                <option value="whatsapp">WhatsApp</option>
                                <option value="phone">Appel téléphonique</option>
                                <option value="email">Email</option>
                            </select>
                        </div>
                        
                        <div class="form-group-premium form-group-full">
                            <label>Photos ou documents</label>
                            <div class="file-upload-premium" onclick="document.getElementById('request_files').click()">
                                <i data-lucide="upload-cloud"></i>
                                <span>Cliquez pour ajouter des fichiers</span>
                                <input type="file" id="request_files" name="attachments[]" multiple style="display:none">
                            </div>
                        </div>
                    </div>
                </div>
                
                <footer class="modal-premium-footer">
                    <button type="submit" class="btn-premium-submit">Envoyer la demande</button>
                </footer>
            </form>
        </div>
    </div>

    <div id="lightbox" class="lightbox">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <div class="lightbox-container">
            <button class="lightbox-nav lightbox-prev" onclick="changeImage(-1)">
                <i data-lucide="chevron-left"></i>
            </button>
            <div style="display: flex; flex-direction: column; align-items: center; gap: 16px;">
                <img class="lightbox-content" id="lightbox-img">
                <div style="text-align: center; color: white; max-width: 80%;">
                    <h3 id="lightbox-title" style="font-weight: 900; font-size: 24px; margin: 0 0 8px 0;"></h3>
                    <p id="lightbox-desc" style="color: rgba(255, 255, 255, 0.8; margin: 0; line-height: 1.6;"></p>
                </div>
            </div>
            <button class="lightbox-nav lightbox-next" onclick="changeImage(1)">
                <i data-lucide="chevron-right"></i>
            </button>
        </div>
    </div>

    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Donner votre avis</h2>
                <span class="close-modal" onclick="closeReviewModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="reviewForm" action="handle_review.php" method="POST">
                    <input type="hidden" name="pro_id" value="<?php echo $pro_id; ?>">
                    <div class="rating-input">
                        <?php for($i=5; $i>=1; $i--): ?>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                            <label for="star<?php echo $i; ?>"><i data-lucide="star"></i></label>
                        <?php endfor; ?>
                    </div>
                    <div class="form-group">
                        <label>Nom complet</label>
                        <input type="text" name="author_name" required value="<?php echo $_SESSION['user_fullname'] ?? ''; ?>">
                    </div>
                    <div class="form-group" style="margin-top: 16px;">
                        <label>Votre commentaire</label>
                        <textarea name="comment" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeReviewModal()">Annuler</button>
                <button type="submit" form="reviewForm" class="btn btn-register">Publier l'avis</button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // Modal Service Request Functions
        function openRequestModal() {
            document.getElementById('requestModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeRequestModal() {
            document.getElementById('requestModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal on background click
        window.onclick = function(event) {
            const modal = document.getElementById('requestModal');
            if (event.target == modal) {
                closeRequestModal();
            }
        }

        // Navigation par onglets avec scroll fluide et mise à jour de l'état actif
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.pro-tab-btn');

        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= (sectionTop - 150)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

        // Scroll fluide lors du clic sur un onglet
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetSection = document.querySelector(targetId);
                
                if (targetSection) {
                    window.scrollTo({
                        top: targetSection.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Données du portfolio pour la lightbox
        const portfolioImages = <?php 
            $js_images = [];
            foreach ($portfolio as $item) {
                $p_path = $item['image_path'] ?? '';
                if ($p_path && strpos($p_path, 'assets/') !== 0) $p_path = 'assets/uploads/portfolio/' . $p_path;
                $js_images[] = [
                    'src' => $p_path,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? ''
                ];
            }
            echo json_encode($js_images); 
        ?>;
        let currentImageIndex = 0;

        function openLightbox(index) {
            currentImageIndex = index;
            updateLightboxImage();
            document.getElementById('lightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            lucide.createIcons();
        }

        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function changeImage(step) {
            currentImageIndex += step;
            if (currentImageIndex >= portfolioImages.length) currentImageIndex = 0;
            if (currentImageIndex < 0) currentImageIndex = portfolioImages.length - 1;
            updateLightboxImage();
        }

        function updateLightboxImage() {
            const img = document.getElementById('lightbox-img');
            const titleEl = document.getElementById('lightbox-title');
            const descEl = document.getElementById('lightbox-desc');
            
            img.style.opacity = '0';
            setTimeout(() => {
                img.src = portfolioImages[currentImageIndex].src;
                img.style.opacity = '1';
                
                if (titleEl) {
                    titleEl.textContent = portfolioImages[currentImageIndex].title;
                    titleEl.style.display = portfolioImages[currentImageIndex].title ? 'block' : 'none';
                }
                if (descEl) {
                    descEl.textContent = portfolioImages[currentImageIndex].description;
                    descEl.style.display = portfolioImages[currentImageIndex].description ? 'block' : 'none';
                }
            }, 150);
        }

        function scrollPortfolio(direction) {
            const grid = document.getElementById('portfolio-grid');
            const scrollAmount = grid.clientWidth * 0.8;
            grid.scrollBy({
                left: direction * scrollAmount,
                behavior: 'smooth'
            });
        }

        // Fermer la lightbox au clic sur le fond (mais pas sur l'image ou les boutons)
        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('lightbox-container')) {
                closeLightbox();
            }
        });

        // Navigation au clavier
        document.addEventListener('keydown', function(e) {
            if (document.getElementById('lightbox').style.display === 'flex') {
                if (e.key === 'ArrowLeft') changeImage(-1);
                if (e.key === 'ArrowRight') changeImage(1);
                if (e.key === 'Escape') closeLightbox();
            }
        });

        function openReviewModal() {
            document.getElementById('reviewModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function toggleSave(btn) {
            btn.classList.toggle('active');
            const icon = btn.querySelector('i');
            if (btn.classList.contains('active')) {
                btn.style.background = 'var(--primary-blue)';
                btn.style.color = 'white';
                icon.style.fill = 'currentColor';
            } else {
                btn.style.background = 'white';
                btn.style.color = '#64748B';
                icon.style.fill = 'none';
            }
        }

        function shareProfile() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($pro['fullname']); ?> sur Tekrin',
                    url: window.location.href
                });
            } else {
                alert('Lien copié !');
                navigator.clipboard.writeText(window.location.href);
            }
        }

        // Fonction pour ouvrir un post complet (modal ou lightbox dédiée)
         function openFullPost(event, postId) {
             // Pour l'instant on ouvre la lightbox de la première image
             const card = event.target.closest('.pro-gallery-card');
            const img = card.querySelector('.adaptive-grid img');
            if (img) img.click();
        }

        // Charger toutes les publications sur la même page avec le nouveau style
        function loadAllPublications() {
            const container = document.getElementById('publications-container');
            const link = document.getElementById('view-all-posts-link');
            
            <?php 
            $remaining_posts = array_slice($posts, 2); // Start loading from the 3rd post (index 2)
            $posts_html = "";
            foreach ($remaining_posts as $post) {
                $post_images = json_decode($post['images'] ?? '[]', true);
                $img_count = count($post_images);
                $content = htmlspecialchars($post['content']);
                $lines = explode("\n", $content);
                $title = $lines[0];
                $description = count($lines) > 1 ? implode(" ", array_slice($lines, 1)) : $content;
                $date = date('d M Y', strtotime($post['created_at']));

                $images_grid_html = "";
                if (!empty($post_images)) {
                    $grid_class = "grid-" . min($img_count, 4);
                    $images_grid_html = '<div class="adaptive-grid '.$grid_class.'">';
                    foreach (array_slice($post_images, 0, 4) as $idx => $img) {
                        $overlay_html = ($idx === 3 && $img_count > 4) ? '<div class="img-overlay">+'.($img_count - 4).'</div>' : '';
                        $images_grid_html .= '
                            <div class="img-item" style="position: relative; height: 100%;">
                                <img src="assets/uploads/posts/'.$img.'" onclick=\'openPostLightbox('.json_encode($post_images).', '.$idx.')\' loading="lazy">
                                '.$overlay_html.'
                            </div>';
                    }
                    $images_grid_html .= '</div>';
                } else {
                    $images_grid_html = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #CBD5E1;"><i data-lucide="file-text" style="width: 48px; height: 48px;"></i></div>';
                }

                $posts_html .= '
                    <article class="pro-gallery-card active-display" style="opacity: 0; transform: translateY(20px); transition: all 0.5s ease;">
                        <div class="card-image-area">'.$images_grid_html.'</div>
                        <div class="card-text-area">
                            <div class="card-body-top">
                                <h4 class="card-title">'.$title.'</h4>
                                <p class="card-description">'.$description.'</p>
                            </div>
                            <div class="card-footer">
                                <span class="card-date">'.$date.'</span>
                                <a href="javascript:void(0)" class="card-more-link" onclick="openFullPost(event, '.$post['id'].')">Voir plus</a>
                            </div>
                        </div>
                    </article>';
            }
            ?>

            const html = `<?php echo str_replace(["\r", "\n"], '', addslashes($posts_html)); ?>`;
            container.insertAdjacentHTML('beforeend', html);
            
            setTimeout(() => {
                const newPosts = container.querySelectorAll('.pro-gallery-card[style*="opacity: 0"]');
                newPosts.forEach(post => {
                    post.style.opacity = '1';
                    post.style.transform = 'translateY(0)';
                });
                lucide.createIcons();
            }, 50);

            if (link) link.style.display = 'none';
        }

        function togglePostText(btn) {
            const text = btn.previousElementSibling;
            if (text.classList.contains('collapsed')) {
                text.classList.remove('collapsed');
                btn.innerText = 'Voir moins';
            } else {
                text.classList.add('collapsed');
                btn.innerText = 'Voir plus';
                // Scroll optionnel pour revenir au début du post
                text.closest('article').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Nouvelle fonction pour la lightbox des posts
        function openPostLightbox(images, index) {
            // On peut réutiliser le système de lightbox du portfolio
            // Mais on doit mettre à jour les données temporairement
            const originalImages = [...portfolioImages];
            const originalIndex = currentImageIndex;
            
            // On remplace les images par celles du post
            const postImages = images.map(img => 'assets/uploads/posts/' + img);
            
            // On affiche
            portfolioImages.length = 0;
            portfolioImages.push(...postImages);
            openLightbox(index);
            
            // On restaure quand on ferme
            const closeBtn = document.querySelector('.lightbox-close');
            const oldClose = closeBtn.onclick;
            closeBtn.onclick = function() {
                closeLightbox();
                portfolioImages.length = 0;
                portfolioImages.push(...originalImages);
                currentImageIndex = originalIndex;
                closeBtn.onclick = oldClose;
            };
        }

        // Charger tous les services sur la même page
        function loadAllServices() {
            const container = document.getElementById('services-container');
            const link = document.getElementById('view-all-services-link');
            
            <?php 
            $remaining_services = array_slice($services_db, 4);
            $services_html = "";
            foreach ($remaining_services as $s) {
                $services_html .= '
                    <div class="service-pro-card" style="opacity: 0; transform: translateY(10px); transition: all 0.4s ease;">
                        <div class="service-pro-icon"><i data-lucide="check-circle"></i></div>
                        <div class="service-pro-info">
                            <h4>'.htmlspecialchars($s['name']).'</h4>
                            <p>'.htmlspecialchars($s['description']).'</p>
                            <div class="service-pro-price">
                                <span class="price-label">À partir de</span>
                                <span class="price-amount">'.number_format($s['price_starting'], 0, ',', ' ').' FCFA</span>
                            </div>
                        </div>
                    </div>';
            }
            ?>

            const html = `<?php echo str_replace(["\r", "\n"], '', addslashes($services_html)); ?>`;
            container.insertAdjacentHTML('beforeend', html);
            
            setTimeout(() => {
                const newServices = container.querySelectorAll('.service-pro-card[style*="opacity: 0"]');
                newServices.forEach(s => {
                    s.style.opacity = '1';
                    s.style.transform = 'translateY(0)';
                });
                lucide.createIcons();
            }, 50);

            if (link) link.style.display = 'none';
        }
<!-- Popup Chat (Facebook-like) -->
<div class="chat-popup" id="chatPopup" style="display: none; position: fixed; bottom: 20px; right: 20px; width: 420px; height: 560px; background: white; border-radius: 20px; box-shadow: 0 15px 50px rgba(0,0,0,0.2); z-index: 1000; flex-direction: column; overflow: hidden;">
    <div class="chat-popup-header" style="background: linear-gradient(135deg, #1565c0, #1e3a8a); color: white; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
        <div class="chat-user-info" style="display: flex; align-items: center; gap: 14px;">
            <img src="" alt="" id="chatAvatar" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 2px solid white;">
            <div>
                <h3 id="chatName" style="margin:0; font-size:16px; font-weight:800;"></h3>
                <small id="chatJob" style="opacity:0.9; font-size:12px;"></small>
            </div>
        </div>
        <button class="close-chat" id="closeChat" style="background: rgba(255,255,255,0.15); border: none; color: white; cursor: pointer; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <i data-lucide="x" style="width: 22px; height: 22px;"></i>
        </button>
    </div>
    <div class="chat-popup-messages" id="chatMessages" style="flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:12px; background: #f8fafc;"></div>
    <div class="chat-popup-composer" style="padding: 16px; border-top:1px solid #e2e8f0; background: white;">
        <form id="chatForm">
            <input type="hidden" id="chatConvId">
            <div style="display: flex; align-items: flex-end; gap:10px;">
                <button type="button" class="btn-attach" onclick="document.getElementById('popupAttachment').click()" style="background: #f1f5f9; border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px; cursor:pointer; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="paperclip" style="width:20px; height:20px; color:#64748b;"></i>
                </button>
                <input type="file" id="popupAttachment" name="attachment" style="display:none">
                <textarea id="popupMessageInput" placeholder="Tapez votre message ici..." required style="flex:1; border:2px solid #e2e8f0; border-radius: 16px; padding: 12px 16px; resize:none; outline:none; font-size:16px; max-height: 120px; min-height: 48px;"></textarea>
                <button type="submit" class="btn-send" style="background: linear-gradient(135deg, #1565c0, #1e3a8a); color:white; border:none; border-radius:12px; padding:12px 16px; cursor:pointer; font-weight:700; display:flex; align-items:center; gap:6px; font-size:14px;">
                    <i data-lucide="send" style="width:20px; height:20px;"></i>
                    Envoyer
                </button>
            </div>
        </form>
    </div>
</div>

    <script>
        let currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
        let activeConvId = null;
        let lastMessageId = 0;
        let currentOtherUser = null;
        
        function trackAdClick(adId) {
            fetch('api/track_ad.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ad_id=' + adId + '&type=click'
            });
        }

        function toggleFollowPro(btn) {
    console.log('Toggle follow pro clicked!');
    const userId = btn.dataset.userId;
    const isFollowing = btn.dataset.isFollowing === 'true';
    console.log('Current state: userId=', userId, 'isFollowing=', isFollowing);
    
    fetch('api/toggle_follow.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'user_id=' + userId
    })
    .then(response => response.text()) // First get text to debug
    .then(text => {
        console.log('Response text:', text);
        try {
            const data = JSON.parse(text);
            console.log('Data:', data);
            if (data.success) {
                btn.dataset.isFollowing = data.is_following;
                btn.innerHTML = '<i data-lucide="' + (data.is_following ? 'check' : 'plus') + '"></i> ' + (data.is_following ? 'Suivi' : 'Suivre');
                btn.style.background = data.is_following ? '#E2E8F0' : 'var(--primary-blue)';
                btn.style.color = data.is_following ? '#475569' : 'white';
                lucide.createIcons();
            } else {
                alert('Erreur: ' + (data.error || 'Erreur'));
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            alert('Erreur: ' + text);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur réseau');
    });
}

        function openChatWithPro(userId) {
            fetch('api/start_conversation.php?with=' + userId + '&ajax=1')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadChatData(data.conv_id, data.other_user_id);
                }
            });
        }

        function loadChatData(convId, otherUserId) {
            fetch('api/get_user.php?id=' + otherUserId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentOtherUser = data.user;
                    document.getElementById('chatAvatar').src = data.user.avatar || '';
                    document.getElementById('chatName').textContent = data.user.fullname;
                    document.getElementById('chatJob').textContent = data.user.profession || 'Professionnel';
                }
            });
            fetch('api/get_messages.php?conversation_id=' + convId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('chatMessages');
                    container.innerHTML = '';
                    data.messages.forEach(msg => {
                        appendPopupMessage(msg);
                        if (msg.id > lastMessageId) lastMessageId = msg.id;
                    });
                    container.scrollTop = container.scrollHeight;
                }
            });
            markAsRead(convId);
            document.getElementById('chatConvId').value = convId;
            activeConvId = convId;
            document.getElementById('chatPopup').style.display = 'flex';
            lucide.createIcons();
        }

        function appendPopupMessage(msg) {
            const container = document.getElementById('chatMessages');
            const isMine = msg.sender_id == currentUserId;
            const div = document.createElement('div');
            div.className = 'message-item ' + (isMine ? 'sent' : 'received');
            div.style.display = 'flex';
            div.style.flexDirection = isMine ? 'row-reverse' : 'row';
            div.style.alignItems = 'flex-end';
            div.style.gap = '10px';
            
            let attachmentHtml = '';
            if (msg.attachment) {
                const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(msg.attachment);
                if (isImage) {
                    attachmentHtml = '<a href="' + msg.attachment + '" target="_blank" style="max-width:240px; border-radius:16px; overflow:hidden; margin-bottom:8px; display:block;"><img src="' + msg.attachment + '" style="width:100%; display:block;"></a>';
                } else {
                    attachmentHtml = '<a href="' + msg.attachment + '" target="_blank" style="display:flex; align-items:center; gap:10px; padding:12px 16px; background:' + (isMine ? 'rgba(255,255,255,0.25)' : '#fff') + '; border-radius:12px; color:' + (isMine ? 'white' : '#1e293b') + '; text-decoration:none; margin-bottom:8px;"><i data-lucide="file" style="width:20px; height:20px;"></i><span style="font-size:14px; font-weight:600;">Télécharger le fichier</span></a>';
                }
            }
            const date = new Date(msg.created_at);
            const timeStr = date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});
            
            div.innerHTML = '<div class="message-bubble" style="background:' + (isMine ? 'linear-gradient(135deg, #1565c0, #1e3a8a)' : '#e2e8f0') + '; color:' + (isMine ? 'white' : '#1e293b') + '; padding:14px 18px; border-radius:' + (isMine ? '20px 20px 4px 20px' : '4px 20px 20px 20px') + '; max-width:75%; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">' +
                attachmentHtml +
                (msg.message ? '<p style="margin:' + (msg.attachment ? '8px 0 0' : '0') + '; font-size:16px; line-height:1.5; font-weight:500;">' + msg.message + '</p>' : '') +
                '<span class="msg-time" style="font-size:11px; opacity:0.8; font-weight:700; text-align:right; display:block; margin-top:8px;">' + timeStr + '</span>' +
            '</div>';
            container.appendChild(div);
            lucide.createIcons();
        }

        function markAsRead(convId) {
            fetch('api/mark_read.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'conversation_id=' + convId
            });
        }

        function sendPopupMessage(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('chatForm'));
            formData.append('conversation_id', activeConvId);
            fetch('api/send_message.php', {method: 'POST', body: formData})
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    appendPopupMessage(data.message);
                    document.getElementById('popupMessageInput').value = '';
                    document.getElementById('popupAttachment').value = '';
                    const container = document.getElementById('chatMessages');
                    container.scrollTop = container.scrollHeight;
                }
            });
        }

        function closeChat() {
            document.getElementById('chatPopup').style.display = 'none';
            activeConvId = null;
        }

        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            document.getElementById('closeChat').addEventListener('click', closeChat);
            document.getElementById('chatForm').addEventListener('submit', sendPopupMessage);
            const messageInput = document.getElementById('popupMessageInput');
            if (messageInput) {
                messageInput.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            }
        });
    </script>
</body>
</html>
