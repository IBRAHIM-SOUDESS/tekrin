<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>
    
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content" data-reveal>
                <h1>Le réseau des <span class="highlight">professionnels</span> de <span class="highlight">proximité</span></h1>
                <p class="description">
                    Créez votre profil et partagez vos réalisations. <br>
                    Suivez les meilleurs techniciens près de chez vous.
                </p>
                <div class="hero-btns">
                    <a href="register.php" class="btn btn-register btn-lg">Créer mon profil</a>
                    <a href="#" class="btn btn-outline btn-lg" style="background: white;">Trouver un professionnel</a>
                </div>
            </div>
            
            <div class="mockup-wrapper" data-reveal>
                <!-- Floating Elements -->
                <div class="glass-card floating-geo" style="background: rgba(255, 255, 255, 0.8); right: -160px;">
                    <p style="font-size: 13px; font-weight: 800; color: var(--primary-blue); display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i data-lucide="check-circle" style="width: 18px;"></i> Profil Vérifié
                    </p>
                </div>

                <div class="glass-card floating-visibility" style="bottom: -40px;">
                    <p style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i data-lucide="trending-up" style="width: 18px;"></i> Plus de visibilité
                    </p>
                </div>

                <!-- Smartphone Mockup -->
                <div class="smartphone">
                    <div class="screen">
                        <div class="app-header">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=200" alt="Moussa Diop" class="app-profile-img">
                        </div>
                        <div class="app-body">
                            <h3 class="app-name">Moussa Diop <i data-lucide="check-circle" style="width: 18px; color: #10B981; vertical-align: middle;"></i></h3>
                            <p class="app-profession">Électricien Expert</p>
                            <div class="app-rating">
                                <i data-lucide="star" style="width: 12px; fill: currentColor;"></i>
                                <i data-lucide="star" style="width: 12px; fill: currentColor;"></i>
                                <i data-lucide="star" style="width: 12px; fill: currentColor;"></i>
                                <i data-lucide="star" style="width: 12px; fill: currentColor;"></i>
                                <i data-lucide="star" style="width: 12px; fill: currentColor;"></i>
                                <span style="color: var(--text-muted); font-size: 11px; margin-left: 4px;">(4.9)</span>
                            </div>

                            <!-- QR Code Preview Inside Phone -->
                            <div class="app-qr-preview">
                                <i data-lucide="qr-code" style="width: 60px; height: 60px; color: var(--dark-bg);"></i>
                                <p style="font-size: 10px; font-weight: 700; margin-top: 5px; color: var(--text-muted);">MON SCAN TEKRIN</p>
                            </div>
                            
                            <div class="app-portfolio">
                                <img src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&q=80&w=150" alt="Work 1" class="portfolio-item">
                                <img src="https://images.unsplash.com/photo-1581092160562-40aa08e78837?auto=format&fit=crop&q=80&w=150" alt="Work 2" class="portfolio-item">
                                <img src="https://images.unsplash.com/photo-1517646287270-a5a9ca602e5c?auto=format&fit=crop&q=80&w=150" alt="Work 3" class="portfolio-item">
                            </div>

                            <div class="app-actions">
                                <a href="#" class="btn btn-whatsapp"><i data-lucide="message-circle" style="width: 14px; margin-right: 4px;"></i> WhatsApp</a>
                                <a href="#" class="btn btn-call"><i data-lucide="phone" style="width: 14px; margin-right: 4px;"></i> Appeler</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Features Section -->
<section class="section" style="padding: 120px 0;">
    <div class="container">
        <div class="section-title" data-reveal style="margin-bottom: 80px;">
            <h2 style="font-size: 42px; color: var(--dark-bg);">Ce que TEKRIN vous offre</h2>
            <p style="color: var(--text-muted); font-size: 18px; max-width: 600px; margin: 20px auto 0;">Une suite d'outils numériques conçus pour propulser votre activité locale.</p>
        </div>
        
        <div class="trust-grid">
            <!-- Feature 1: Professional Profiles -->
            <div class="trust-card" data-reveal>
                <div class="trust-icon">
                    <i data-lucide="user-check"></i>
                </div>
                <h3>Profils Professionnels</h3>
                <p>Créez une identité numérique complète et mettez en avant vos compétences et réalisations.</p>
            </div>
            
            <!-- Feature 2: Unique QR Codes -->
            <div class="trust-card" data-reveal>
                <div class="trust-icon">
                    <i data-lucide="qr-code"></i>
                </div>
                <h3>Codes QR Uniques</h3>
                <p>Partagez votre profil instantanément. Vos clients accèdent à vos services en un scan.</p>
            </div>
            
            <!-- Feature 3: Geolocation -->
            <div class="trust-card" data-reveal>
                <div class="trust-icon">
                    <i data-lucide="navigation"></i>
                </div>
                <h3>Géolocalisation</h3>
                <p>Soyez visible par les clients à proximité et développez votre activité localement.</p>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA Section -->
<section class="container" data-reveal style="margin-bottom: 120px;">
    <div class="cta-banner">
        <h2>Prêt à booster votre visibilité ?</h2>
        <p>Rejoignez TEKRIN dès aujourd'hui et connectez-vous avec plus de clients.</p>
        <a href="register.php" class="btn btn-white btn-lg" style="border-radius: 50px; font-weight: 800;">Créer mon profil gratuit</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>