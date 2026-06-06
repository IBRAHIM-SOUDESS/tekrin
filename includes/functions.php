<?php
/**
 * Sécurise les données d'entrée pour éviter les attaques XSS
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirige vers une page spécifique
 * @param string $page
 */
function redirect($page) {
    header("Location: " . $page);
    exit();
}

/**
 * Affiche un message d'alerte flash (si présent en session)
 * @param string $type (success, danger, info)
 * @return string
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return "<div class='alert alert-{$type}'>{$message}</div>";
    }
    return "";
}

/**
 * Calcule le pourcentage de complétion du profil
 * @param array $user
 * @return int
 */
function calculateProfileCompletion($user, $portfolioCount = 0) {
    $totalFields = 9; // Photo, Profession, Bio, Region, Ville, Téléphone, WhatsApp, Services, Portfolio
    $completedFields = 0;

    if (!empty($user['profile_picture'])) $completedFields++;
    if (!empty($user['profession'])) $completedFields++;
    if (!empty($user['bio'])) $completedFields++;
    if (!empty($user['region'])) $completedFields++;
    if (!empty($user['city'])) $completedFields++;
    if (!empty($user['phone_number'])) $completedFields++;
    if (!empty($user['whatsapp_number'])) $completedFields++;
    if (!empty($user['services'])) $completedFields++;
    if ($portfolioCount > 0) $completedFields++;

    return round(($completedFields / $totalFields) * 100);
}

/**
 * Définit un message flash
 * @param string $message
 * @param string $type
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}
?>