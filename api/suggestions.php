<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$q = sanitize($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

// 1. Rechercher des professionnels
$stmt = $pdo->prepare("SELECT id, fullname, profession, profile_picture 
                       FROM users 
                       WHERE role = 'technicien' AND status = 'active' 
                       AND (fullname LIKE ? OR profession LIKE ?) 
                       LIMIT 3");
$searchTerm = "%$q%";
$stmt->execute([$searchTerm, $searchTerm]);
$pros = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pros as $pro) {
    $results[] = [
        'type' => 'pro',
        'id' => $pro['id'],
        'title' => $pro['fullname'],
        'subtitle' => $pro['profession'],
        'image' => get_user_avatar($pro),
        'url' => 'pro.php?id=' . $pro['id']
    ];
}

// 2. Rechercher des catégories (statique pour l'instant)
$categories = [
    ['name' => 'Électricité', 'slug' => 'electricite'],
    ['name' => 'Plomberie', 'slug' => 'plomberie'],
    ['name' => 'Menuiserie', 'slug' => 'menuiserie'],
    ['name' => 'Informatique', 'slug' => 'informatique'],
    ['name' => 'Mécanique', 'slug' => 'mecanique'],
    ['name' => 'Maçonnerie', 'slug' => 'maconnerie']
];

foreach ($categories as $cat) {
    if (stripos($cat['name'], $q) !== false) {
        $results[] = [
            'type' => 'category',
            'title' => $cat['name'],
            'url' => 'discover.php?category=' . $cat['slug']
        ];
    }
}

// 3. Rechercher des villes
$stmt = $pdo->prepare("SELECT DISTINCT city FROM users WHERE city LIKE ? AND city IS NOT NULL LIMIT 3");
$stmt->execute(["%$q%"]);
$cities = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($cities as $city) {
    $results[] = [
        'type' => 'city',
        'title' => $city,
        'url' => 'discover.php?city=' . urlencode($city)
    ];
}

header('Content-Type: application/json');
echo json_encode(array_slice($results, 0, 8));
