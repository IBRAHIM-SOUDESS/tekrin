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
 * Vérifie si l'onboarding est complété
 * @return bool
 */
function isOnboardingCompleted() {
    // Les clients n'ont pas besoin de passer par le Wizard de configuration pro
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client') {
        return true;
    }
    return isset($_SESSION['onboarding_completed']) && $_SESSION['onboarding_completed'] == 1;
}

/**
 * Redirige vers l'onboarding si non complété
 */
function requireOnboarding() {
    if (isLoggedIn() && !isOnboardingCompleted()) {
        redirect('wizard.php?step=1');
    }
}

/**
 * Redirige vers une page spécifique
 * @param string $page
 */
function redirect($page) {
    header("Location: $page");
    exit();
}

/**
 * Récupère le chemin de l'avatar utilisateur
 * @param array $user
 * @return string
 */
function get_user_avatar($user) {
    $avatar = $user['profile_picture'] ?? '';
    if (empty($avatar)) return '';
    
    if (strpos($avatar, 'assets/') === 0) {
        return $avatar;
    }
    return 'assets/uploads/profiles/' . $avatar;
}

/**
 * Récupère le chemin de la couverture utilisateur
 * @param array $user
 * @return string
 */
function get_user_cover($user) {
    $cover = $user['cover_image'] ?? '';
    if (empty($cover)) return '';
    
    if (strpos($cover, 'assets/') === 0) {
        return $cover;
    }
    return 'assets/uploads/covers/' . $cover;
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

/**
 * Retourne le temps écoulé depuis une date donnée
 * @param string $timestamp
 * @return string
 */
function time_ago($timestamp) {
    if (!$timestamp) return "Il y a un instant";
    
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes      = round($seconds / 60);           // value 60 is seconds  
    $hours        = round($seconds / 3600);         // value 3600 is 60 minutes * 60 sec  
    $days         = round($seconds / 86400);        // value 86400 is 24 hours * 60 minutes * 60 sec  
    $weeks        = round($seconds / 604800);       // value 604800 is 7 days * 24 hours * 60 minutes * 60 sec  
    $months       = round($seconds / 2629440);      // value 2629440 is ((365+365+365+365+366)/5/12)*24*60*60  
    $years        = round($seconds / 31553280);     // value 31553280 is ((365+365+365+365+366)/5)*24*60*60  
    
    if ($seconds <= 60) {
        return "À l'instant";
    } else if ($minutes <= 60) {
        if ($minutes == 1) {
            return "Il y a 1 min";
        } else {
            return "Il y a $minutes mins";
        }
    } else if ($hours <= 24) {
        if ($hours == 1) {
            return "Il y a 1h";
        } else {
            return "Il y a $hours h";
        }
    } else if ($days <= 7) {
        if ($days == 1) {
            return "Hier";
        } else {
            return "Il y a $days jours";
        }
    } else if ($weeks <= 4.3) { // 4.3 == 52/12
        if ($weeks == 1) {
            return "Il y a 1 semaine";
        } else {
            return "Il y a $weeks semaines";
        }
    } else if ($months <= 12) {
        if ($months == 1) {
            return "Il y a 1 mois";
        } else {
            return "Il y a $months mois";
        }
    } else {
        if ($years == 1) {
            return "Il y a 1 an";
        } else {
            return "Il y a $years ans";
        }
    }
}
?>