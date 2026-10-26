
<?php 
session_start(); 
// Génération d'un jeton CSRF pour sécuriser le formulaire de survie
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETS KAMAL - Support Interne</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=4.1">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc !important; }
        
        /* Ajustement du header pour la page d'accueil */
        .top-nav { display: none !important; }
        .main-header {
            position: absolute !important;
            width: 100%;
            background: transparent !important;
            backdrop-filter: none !important;
            border-bottom: none !important;
            box-shadow: none !important;
            z-index: 100;
        }
        .nav-item { color: #1e293b !important; }
        .nav-item:hover { background: rgba(30, 41, 59, 0.05) !important; }
        .brand-name { color: #0f172a !important; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .aspect-16-9-container {
            width: 100%;
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: rgba(248, 250, 252, 0.85);
            backdrop-filter: blur(8px);
            min-height: calc(100vh - 80px);
            position: relative;
        }
        .aspect-16-9-content {
            position: relative;
            width: 100%;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 500px;
        }
        .hero-background {
            position: absolute;
            inset: 0;
            background-image: url('https://images.unsplash.com/photo-1531482615713-2afd69097998?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center 30%;
            background-repeat: no-repeat;
            z-index: 1;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, #f8fafc 0%, #f8fafc 25%, rgba(248, 250, 252, 0.7) 50%, transparent 100%);
            z-index: 2;
        }
        @media (max-width: 1023px) {
            .hero-overlay {
                background: linear-gradient(to bottom, rgba(248, 250, 252, 0.9) 0%, rgba(248, 250, 252, 0.7) 40%, transparent 100%);
            }
        }
        .hero-text-content {
            position: relative;
            z-index: 10;
            width: 100%;
            flex: 1;
            display: flex;
            align-items: center;
        }
        /* Marquage direct sur la blouse (simulation) */
        .shirt-logo {
            position: absolute;
            top: 35%;
            right: 25%;
            color: #1e3a8a; /* Bleu marine pour l'effet broderie */
            font-weight: 900;
            font-size: 13px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            z-index: 4;
            transform: rotate(-3deg);
            opacity: 0.8;
            text-shadow: 0.5px 0.5px 0px rgba(255, 255, 255, 0.2);
            pointer-events: none;
        }
        .shirt-logo span {
            display: block;
            font-size: 10px;
            font-weight: 700;
            margin-top: -4px;
            opacity: 0.8;
        }
    </style>
</head>
<body style="min-height: 100vh; display: flex; flex-direction: column;">

    <?php include 'header.php'; ?>

    <main class="aspect-16-9-container">
        
        <div class="aspect-16-9-content overflow-hidden">
            <!-- Image de fond complète -->
            <div class="hero-background"></div>
            <!-- Dégradé de fondu progressif -->
            <div class="hero-overlay"></div>
            
            <!-- Marquage direct sur la blouse -->
            <div class="shirt-logo hidden lg:block">
                ETS KAMAL
                <span>Technique</span>
            </div>
            
            <!-- Contenu texte superposé -->
            <div class="hero-text-content px-6 md:px-16 lg:px-32 opacity-0" style="animation: fadeIn 0.8s forwards;">
                <div class="max-w-2xl w-full text-center lg:text-left pt-32 lg:pt-0">
                    <h1 class="text-4xl md:text-6xl font-black text-slate-900 mb-8 tracking-tight leading-tight uppercase">
                        OPTIMISEZ VOTRE<br>OUTIL DE TRAVAIL.
                    </h1>
                    <p class="text-slate-500 text-base md:text-xl mb-10 leading-relaxed font-medium max-w-md mx-auto lg:mx-0">
                        Signalez vos incidents en un clic pour une intervention rapide par nos techniciens et techniciennes spécialisés.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-6 justify-center lg:justify-start">
                        <a href="ticket.php" 
                           class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-xl font-extrabold px-14 py-6 rounded-2xl shadow-2xl shadow-blue-500/40 transition-colors duration-300 transform hover:-translate-y-1 uppercase tracking-wide">
                            LANCER L'INTERVENTION
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </main>

</body>
</html>