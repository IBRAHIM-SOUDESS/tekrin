 <?php 
session_start(); 
// Redirection: un administrateur n'a pas à créer de tickets
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: admin.php');
    exit();
}
// Génération d'un jeton CSRF pour sécuriser le formulaire
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ETS KAMAL - Nouvelle Demande</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=4.1">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .checkmark { display: none; }
        .is-valid + .checkmark { display: inline-block; }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="max-w-4xl mx-auto my-12 px-4">
        
        <!-- Messages de statut -->
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'success'): ?>
                <div class="mb-8 bg-green-50 border border-green-200 rounded-3xl p-6 flex items-center gap-6 animate-in fade-in slide-in-from-top-4 duration-500">
                    <div class="w-16 h-16 bg-green-500 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-green-200">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-green-900 font-800 text-lg tracking-tight">Demande enregistrée !</h3>
                        <p class="text-green-700 text-sm">Votre ticket est le <span class="font-bold text-green-900">#<?php echo htmlspecialchars($_GET['id'] ?? ''); ?></span>. Conservez ce numéro pour votre suivi.</p>
                        <a href="track.php?search=<?php echo htmlspecialchars($_GET['id'] ?? ''); ?>" class="inline-block mt-2 text-green-800 font-bold text-xs uppercase tracking-wider hover:underline">Suivre l'avancement &rarr;</a>
                    </div>
                </div>
            <?php elseif ($_GET['status'] == 'error'): ?>
                <div class="mb-8 bg-red-50 border border-red-200 rounded-3xl p-6 flex items-center gap-6">
                    <div class="w-12 h-12 bg-red-500 text-white rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg>
                    </div>
                    <p class="text-red-700 font-medium">Une erreur est survenue lors de l'envoi. Veuillez réessayer.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="page-header" style="text-align: center; margin-bottom: 40px;">
            <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight uppercase">Nouvelle Demande d'Intervention</h1>
            <p class="text-slate-300 mt-2">Remplissez le formulaire ci-dessous pour signaler votre incident.</p>
        </div>

        <!-- Barre de progression de complétion -->
        <div class="w-full bg-gray-200 h-1.5 rounded-full mb-8 overflow-hidden">
            <div id="formProgress" class="bg-blue-600 h-full transition-all duration-500" style="width: 0%"></div>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
            <div class="mb-8 text-center">
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Déclarer un Incident</h2>
                <p class="text-slate-500 text-sm mt-1">Veuillez renseigner les détails pour une prise en charge rapide.</p>
            </div>

            <!-- Formulaire de demande -->
            <form id="ticketForm" action="submit.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div id="formErrors" class="hidden p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded"></div>

                    <!-- Groupe Identité -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative">
                            <label for="nom_employe" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Nom complet de l'émetteur</label>
                            <input type="text" id="nom_employe" name="nom_employe" placeholder="Ex : KAMAL Jean" required 
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none text-slate-700 text-base">
                        </div>
                        <div>
                            <label for="service" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Département concerné</label>
                            <select id="service" name="service" required 
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none text-slate-700 text-base">
                                <option value="Comptabilité">Comptabilité</option>
                                <option value="Ressources Humaines">Ressources Humaines</option>
                                <option value="Commercial">Commercial</option>
                                <option value="Logistique">Logistique</option>
                                <option value="Direction">Direction</option>
                            </select>
                        </div>
                    </div>

                    <!-- Groupe Incident -->
                    <div class="space-y-6 pt-4 border-t border-slate-100">
                        <div>
                            <label for="objet" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Intitulé de l'incident (Objet)</label>
                            <input type="text" id="objet" name="objet" placeholder="Ex : Panne imprimante réseau étage 2" required 
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none text-slate-700 text-base">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="categorie" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Type de demande</label>
                                <select id="categorie" name="categorie" required onchange="handleCategoryChange()"
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none text-slate-700 text-base">
                                    <option value="Matériel">Matériel (Hardware)</option>
                                    <option value="Logiciel métier">Logiciel / Application</option>
                                    <option value="Réseau / Internet">Réseau / Connectivité</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                            
                            <!-- Champs Conditionnels -->
                            <div id="dynamicField" class="hidden animate-in fade-in duration-300">
                                <!-- Contenu injecté par JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Matrice de Priorité -->
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Évaluation de la priorité</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="impact" class="block text-[10px] font-bold uppercase text-slate-500 mb-2">Impact de l'incident</label>
                                <select id="impact" name="impact" onchange="calculatePriority()"
                                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-base">
                                    <option value="utilisateur">Moi uniquement</option>
                                    <option value="département">Mon département</option>
                                    <option value="entreprise">Toute l'entreprise</option>
                                </select>
                            </div>
                            <div>
                                <label for="urgence_brute" class="block text-[10px] font-bold uppercase text-slate-500 mb-2">Urgence métier</label>
                                <select id="urgence_brute" name="urgence_brute" onchange="calculatePriority()"
                                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-base">
                                    <option value="informatif">Informatif / Mineur</option>
                                    <option value="gênant">Gênant (Travail ralenti)</option>
                                    <option value="bloquant">Bloquant (Travail impossible)</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 pt-2">
                            <span class="text-[10px] font-bold uppercase text-slate-400">Priorité calculée :</span>
                            <span id="priorityBadge" class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tighter bg-green-100 text-green-700 border border-green-200">Basse</span>
                            <input type="hidden" id="priorite_calculee" name="priorite_calculee" value="basse">
                        </div>
                    </div>

                    <div>
                        <label for="message" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Description détaillée</label>
                        <textarea id="message" name="message" rows="4" placeholder="Décrivez le contexte et les étapes pour reproduire le problème..." required
                                  class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all outline-none text-slate-700 text-base"></textarea>
                        <div id="messageCounter" class="text-[10px] text-slate-400 mt-2 text-right font-medium">0 / 500 caractères</div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="relative group">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Pièce jointe</label>
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-200 rounded-2xl cursor-pointer bg-slate-50 hover:bg-slate-100 hover:border-blue-300 transition-all group">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-2 text-slate-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    <p class="text-xs text-slate-500 group-hover:text-blue-600 transition-colors font-medium">Glisser-déposer ou cliquer</p>
                                </div>
                                <input type="file" name="piece_jointe" class="hidden" accept=".jpg,.jpeg,.png,.pdf">
                            </label>
                        </div>
                        <div class="flex items-center">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" id="confirmation" name="confirmation" required class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-slate-600 group-hover:text-slate-800 transition-colors font-medium">Je certifie l'exactitude des informations.</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-6">
                        <button type="submit" id="submitBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-2xl shadow-lg shadow-blue-200 transition-colors duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-3">
                            <span class="spinner hidden w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span>Soumettre l'incident</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Validation JavaScript côté client -->
    <script>
        // Configuration de la matrice de priorité ITSM
        const priorityMatrix = {
            'utilisateur': { 'informatif': 'basse', 'gênant': 'moyenne', 'bloquant': 'haute' },
            'département': { 'informatif': 'moyenne', 'gênant': 'haute', 'bloquant': 'critique' },
            'entreprise': { 'informatif': 'haute', 'gênant': 'critique', 'bloquant': 'critique' }
        };

        const badgeStyles = {
            'basse': 'bg-green-100 text-green-700 border-green-200',
            'moyenne': 'bg-yellow-100 text-yellow-700 border-yellow-200',
            'haute': 'bg-orange-100 text-orange-700 border-orange-200',
            'critique': 'bg-red-100 text-red-700 border-red-200 animate-pulse'
        };

        function calculatePriority() {
            const impact = document.getElementById('impact').value;
            const urgence = document.getElementById('urgence_brute').value;
            const priority = priorityMatrix[impact][urgence];
            
            const badge = document.getElementById('priorityBadge');
            badge.textContent = priority.charAt(0).toUpperCase() + priority.slice(1);
            badge.className = `px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tighter border ${badgeStyles[priority]}`;
            document.getElementById('priorite_calculee').value = priority;
            updateProgress();
        }

        function handleCategoryChange() {
            const category = document.getElementById('categorie').value;
            const container = document.getElementById('dynamicField');
            
            if (category === 'Matériel') {
                container.innerHTML = `
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Modèle / Type d'équipement</label>
                    <select name="type_equipement" class="w-full px-4 py-3 bg-blue-50 border border-blue-100 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                        <option value="PC">Poste de travail / PC</option>
                        <option value="Imprimante">Imprimante / Copieur</option>
                        <option value="Scanner">Scanner</option>
                        <option value="Téléphone">Téléphonie IP</option>
                    </select>
                `;
                container.classList.remove('hidden');
            } else if (category === 'Logiciel métier') {
                container.innerHTML = `
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Nom de l'application</label>
                    <input type="text" name="nom_logiciel" placeholder="Ex: Sage, SAP, Outlook..." class="w-full px-4 py-3 bg-blue-50 border border-blue-100 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-slate-700">
                `;
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
                container.innerHTML = '';
            }
            updateProgress();
        }

        // Gestion de la progression
        function updateProgress() {
            const fields = ['nom_employe', 'objet', 'message', 'confirmation'];
            let filled = 0;
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el.type === 'checkbox') { if (el.checked) filled++; }
                else if (el.value.trim().length > 0) filled++;
            });
            const progress = (filled / fields.length) * 100;
            document.getElementById('formProgress').style.width = progress + '%';
        }

        // Initialisation
        document.querySelectorAll('input, select, textarea').forEach(el => {
            el.addEventListener('input', updateProgress);
        });
        handleCategoryChange();
        updateProgress();

        // Validation finale
        const form = document.getElementById('ticketForm');
        const messageField = document.getElementById('message');
        const messageCounter = document.getElementById('messageCounter');
        const errorsBox = document.getElementById('formErrors');

        messageField.addEventListener('input', function () {
            const length = messageField.value.length;
            messageCounter.textContent = length + " / 500 caractères saisis";
        });

        form.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const objet = document.getElementById('objet').value.trim();
            const message = messageField.value.trim();
            const confirmation = document.getElementById('confirmation').checked;

            const errors = [];

            if (objet.length < 5) {
                errors.push("L'objet doit contenir au moins 5 caractères.");
            }

            if (message.length < 10) {
                errors.push("La description doit contenir au moins 10 caractères.");
            }

            if (!confirmation) {
                errors.push("Veuillez confirmer que les informations saisies sont exactes.");
            }

            if (errors.length > 0) {
                e.preventDefault();
                errorsBox.style.display = 'block';
                errorsBox.innerHTML = errors.join("<br>");
                errorsBox.scrollIntoView({ behavior: 'smooth' });
            } else {
                errorsBox.style.display = 'none';
                errorsBox.innerHTML = "";
                // Effet de chargement
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    </script>
</body>
</html>
