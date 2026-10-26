// script.js

document.addEventListener('DOMContentLoaded', function() {
    const burgerMenu = document.querySelector('.burger-menu');
    const mainNav = document.querySelector('.main-nav');
    const navLinks = document.querySelectorAll('.main-nav a');
    const devisForm = document.getElementById('devisForm');

    // ====================================
    // 1. GESTION DU MENU RESPONSIVE (BURGER)
    // ====================================

    function toggleMenu() {
        mainNav.classList.toggle('open'); 
        burgerMenu.classList.toggle('active');
    }

    // Ouvre/ferme le menu au clic sur le burger
    burgerMenu.addEventListener('click', toggleMenu);

    // Ferme le menu quand un lien est cliqué (pour naviguer vers une ancre)
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            // Un petit délai pour permettre le défilement fluide avant de fermer
            setTimeout(() => {
                if (mainNav.classList.contains('open')) {
                    toggleMenu();
                }
            }, 300); 
        });
    });

    // ====================================
    // 2. VALIDATION DU FORMULAIRE DE CONTACT
    // ====================================
    
    function validateForm(event) {
        // Empêche l'envoi du formulaire par défaut
        event.preventDefault(); 

        let isValid = true;
        const formElements = ['nom', 'email', 'sujet', 'message'];
        
        // Fonction utilitaire pour afficher/masquer les erreurs
        const setError = (id, message) => {
            const errorElement = document.getElementById(`error-${id}`);
            if (errorElement) {
                errorElement.textContent = message;
            }
        };

        // 1. Validation de chaque champ requis
        formElements.forEach(id => {
            const input = document.getElementById(id);
            if (input.value.trim() === '') {
                setError(id, `Ce champ est obligatoire.`);
                isValid = false;
            } else {
                setError(id, ''); // Efface l'erreur si le champ est rempli
            }
        });
        
        // 2. Validation spécifique de l'email
        const emailInput = document.getElementById('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailInput.value.trim() !== '' && !emailRegex.test(emailInput.value.trim())) {
            setError('email', 'Veuillez entrer une adresse e-mail valide.');
            isValid = false;
        }

        // 3. Affichage du statut
        const statusDiv = document.getElementById('form-status');
        
        if (isValid) {
            // Ici, vous enverriez normalement les données à un serveur (PHP, Node, etc.)
            // Puisque nous sommes en frontend (HTML/CSS/JS simple), nous simulons le succès.
            
            statusDiv.textContent = '✅ Votre demande a été envoyée avec succès ! Nous vous recontacterons bientôt.';
            statusDiv.className = 'status-message success';
            
            // Efface le formulaire après l'envoi réussi
            devisForm.reset(); 
            
        } else {
            statusDiv.textContent = '❌ Veuillez corriger les erreurs dans le formulaire.';
            statusDiv.className = 'status-message'; // On peut ajouter une classe 'error' si besoin
        }
    }
    
    // Ajout de l'écouteur d'événement sur la soumission du formulaire
    if (devisForm) {
        devisForm.addEventListener('submit', validateForm);
    }
});