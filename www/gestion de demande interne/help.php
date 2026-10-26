<?php
/**
 * Centre d'Aide et Suivi de Ticket
 */
session_start();
require_once 'db.php';

// Un admin ne peut pas accéder à cette page, il est redirigé
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: admin.php');
    exit();
}

$search_result = null;
$error = null;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = htmlspecialchars(trim($_GET['search']), ENT_QUOTES, 'UTF-8');
    
    // On cherche par ID de ticket ou par nom exact de l'employé
    $sql = "SELECT * FROM demandes WHERE id = ? OR nom_employe LIKE ? ORDER BY date_creation DESC";
    $stmt = mysqli_prepare($conn, $sql);
    $search_param = "%$search%";
    mysqli_stmt_bind_param($stmt, "is", $search, $search_param);
    mysqli_stmt_execute($stmt);
    $search_result = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>ETS KAMAL - Aide & Suivi</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=4.1">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="help-page">

    <?php include 'header.php'; ?>

    <main class="help-main">
        
        <div class="text-center mb-16">
            <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight uppercase">Suivi & Centre d'Aide</h1>
            <p class="text-slate-300 mt-2">Consultez l'état de vos demandes ou accédez à nos guides techniques.</p>
        </div>

        <!-- Section Suivi de Ticket (Intégrée) -->
        <section class="mb-20">
            <div class="help-search-box">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2.5" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-800 text-slate-900">Suivre mon ticket</h2>
                        <p class="text-slate-500 text-sm font-medium">Entrez votre numéro de ticket ou votre nom</p>
                    </div>
                </div>

                <form action="help.php" method="GET" class="flex flex-col md:flex-row gap-4 mb-8">
                    <div class="relative flex-1">
                        <input type="text" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                               placeholder="Ex: 145 ou KAMAL Jean" required
                               class="w-full pl-6 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium text-base">
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-10 py-4 rounded-2xl shadow-lg shadow-blue-200 transition-colors duration-300">
                        Rechercher
                    </button>
                    <?php if (isset($_GET['search']) && $search_result && mysqli_num_rows($search_result) > 0): ?>
                        <button type="button" onclick="downloadPagePDF('Suivi de Ticket - ETS KAMAL', '.space-y-6')" class="no-print bg-slate-800 hover:bg-slate-900 text-white font-bold px-6 py-4 rounded-2xl shadow-lg transition-colors duration-300 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            PDF
                        </button>
                    <?php endif; ?>
                </form>

                <!-- Résultats du suivi -->
                <?php if (isset($_GET['search'])): ?>
                    <div class="space-y-6 mt-10 pt-10 border-t border-slate-50">
                        <?php if ($search_result && mysqli_num_rows($search_result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($search_result)): ?>
                                <div class="bg-slate-50 rounded-3xl p-6 md:p-8 border border-slate-100 hover:bg-white hover:shadow-xl transition-all duration-300">
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                                        <div>
                                            <span class="text-[10px] font-800 uppercase tracking-widest text-blue-500 bg-blue-50 px-3 py-1 rounded-full mb-2 inline-block">Ticket #<?php echo $row['id']; ?></span>
                                            <h3 class="text-xl font-700 text-slate-800"><?php echo htmlspecialchars($row['objet']); ?></h3>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <?php if($row['statut'] == 'en cours'): ?>
                                                <span class="flex items-center gap-2 px-4 py-2 bg-yellow-50 text-yellow-700 rounded-xl text-sm font-bold border border-yellow-100">
                                                    <span class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></span>
                                                    En cours
                                                </span>
                                            <?php elseif($row['statut'] == 'annulé'): ?>
                                                <span class="flex items-center gap-2 px-4 py-2 bg-orange-50 text-orange-700 rounded-xl text-sm font-bold border border-orange-100">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    Annulé
                                                </span>
                                            <?php else: ?>
                                                <span class="flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 rounded-xl text-sm font-bold border border-green-100">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    Terminé
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-sm">
                                        <div class="flex justify-between p-3 bg-white rounded-xl border border-slate-50">
                                            <span class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Date</span>
                                            <span class="text-slate-700 font-bold"><?php echo date('d/m/Y', strtotime($row['date_creation'])); ?></span>
                                        </div>
                                        <div class="flex justify-between p-3 bg-white rounded-xl border border-slate-50">
                                            <span class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Priorité</span>
                                            <span class="font-bold <?php echo $row['priorite_calculee'] == 'critique' ? 'text-red-600' : 'text-blue-600'; ?> uppercase text-[10px]"><?php echo $row['priorite_calculee']; ?></span>
                                        </div>
                                    </div>

                                    <?php if($row['statut'] == 'terminé' && !empty($row['note_resolution'])): ?>
                                        <div class="bg-blue-600 text-white rounded-2xl p-6 shadow-lg shadow-blue-200">
                                            <h4 class="text-[10px] font-800 uppercase tracking-widest mb-2 opacity-80">Solution du support :</h4>
                                            <p class="leading-relaxed font-medium"><?php echo nl2br(htmlspecialchars($row['note_resolution'])); ?></p>
                                        </div>
                                    <?php elseif($row['statut'] == 'annulé'): ?>
                                        <div class="bg-orange-600 text-white rounded-2xl p-6 shadow-lg shadow-orange-200">
                                            <h4 class="text-[10px] font-800 uppercase tracking-widest mb-2 opacity-80">Statut :</h4>
                                            <p class="leading-relaxed font-medium">Cette demande a été annulée par l'administration.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-white rounded-2xl p-6 border border-slate-100 text-center">
                                            <p class="text-slate-500 text-sm italic italic">Votre demande est en cours d'analyse par nos techniciens.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="bg-red-50 border border-red-100 rounded-3xl p-8 text-center">
                                <p class="text-red-600 font-bold">Aucun ticket trouvé pour "<?php echo htmlspecialchars($search); ?>"</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Section des trois palets (Aide & Raccourcis) -->
        <section class="help-cards-grid">
            <!-- Palet 1: Guide -->
            <div class="bg-white p-8 rounded-[32px] shadow-lg border border-slate-50 hover:shadow-2xl transition-all group">
                <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <h3 class="text-xl font-800 text-slate-900 mb-3">Guide d'utilisation</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-6">Consultez notre documentation complète pour apprendre à utiliser toutes les fonctionnalités du portail.</p>
                <a href="guide.php" class="text-blue-600 font-bold text-xs uppercase tracking-wider flex items-center gap-2 hover:gap-3 transition-all">
                    Voir le guide <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 8l4 4m0 0l-4 4m4-4H3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>

            <!-- Palet 2: Nouveau Ticket -->
            <div class="bg-white p-8 rounded-[32px] shadow-lg border border-slate-50 hover:shadow-2xl transition-all group">
                <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <h3 class="text-xl font-800 text-slate-900 mb-3">Nouveau Ticket</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-6">Vous avez un problème technique ? Créez une nouvelle demande d'intervention en quelques secondes.</p>
                <a href="ticket.php" class="text-emerald-600 font-bold text-xs uppercase tracking-wider flex items-center gap-2 hover:gap-3 transition-all">
                    Ouvrir un ticket <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 8l4 4m0 0l-4 4m4-4H3" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>

            <!-- Palet 3: Contact Support -->
            <div class="bg-white p-8 rounded-[32px] shadow-lg border border-slate-50 hover:shadow-2xl transition-all group">
                <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <h3 class="text-xl font-800 text-slate-900 mb-3">Besoin d'aide ?</h3>
                <p class="text-slate-500 text-sm leading-relaxed mb-6">Notre équipe technique est disponible pour répondre à vos questions par email ou via le bureau IT.</p>
                <a href="mailto:support@etskamal.com" class="text-amber-600 font-bold text-xs uppercase tracking-wider hover:underline flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    support@etskamal.com
                </a>
            </div>
        </section>

        <div class="mt-20 text-center">
            <a href="index.php" class="inline-flex items-center gap-2 text-blue-600 font-bold hover:underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Retour à l'accueil
            </a>
        </div>
    </main>

    <footer class="py-12 border-t border-slate-100 text-center">
        <p class="text-gray-400 text-xs font-medium">© <?php echo date('Y'); ?> ETS KAMAL — Portail de Support Interne</p>
    </footer>

    <div id="pdfHeader" class="pdf-header">ETS KAMAL - Support Interne</div>
    <style>
        .pdf-header { display: none; max-width: 900px; margin: 0 auto; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; align-items: center; justify-content: center; color: #0f172a; font-size: 16px; font-weight: 800; letter-spacing: .02em; }
        body.pdf-exporting .pdf-header { display: flex; }
        body.pdf-exporting .no-print { display: none !important; }
        body.pdf-exporting .top-nav, body.pdf-exporting .main-header { display: none !important; }
        body.pdf-exporting .help-main { padding-top: 20px; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" defer></script>
    <script>
        function downloadPagePDF(title, rootSelector) {
            if (typeof html2pdf === 'undefined') { 
                console.error('html2pdf is not loaded');
                window.print(); 
                return; 
            }
            
            var header = document.getElementById('pdfHeader');
            if (header && title) header.textContent = title;
            
            document.body.classList.add('pdf-exporting');
            
            var source = rootSelector ? document.querySelector(rootSelector) : document.body;
            if (!source) source = document.body;

            var opt = {
                margin: [10, 10, 10, 10],
                filename: (title ? title.toLowerCase().replace(/\s+/g, '_') : 'export') + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2, 
                    useCORS: true, 
                    logging: false,
                    letterRendering: true,
                    allowTaint: true
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };

            html2pdf().set(opt).from(source).save().then(function() {
                document.body.classList.remove('pdf-exporting');
            }).catch(function(err) {
                console.error('PDF Generation Error:', err);
                document.body.classList.remove('pdf-exporting');
                window.print();
            });
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>