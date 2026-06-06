// TEKRIN - Application JavaScript principal
// Version: 2.0 (Premium SaaS)

document.addEventListener('DOMContentLoaded', () => {
    console.log('TEKRIN v2.0 - Plateforme chargée');

    // 1. Scroll Reveal Animation
    const revealElements = document.querySelectorAll('[data-reveal]');
    
    const revealOnScroll = () => {
        const windowHeight = window.innerHeight;
        revealElements.forEach(el => {
            const elementTop = el.getBoundingClientRect().top;
            const elementVisible = 150;
            
            if (elementTop < windowHeight - elementVisible) {
                el.classList.add('revealed');
            }
        });
    };

    // Initial check
    revealOnScroll();
    
    // Add scroll listener
    window.addEventListener('scroll', revealOnScroll);

    // 2. Sticky Header Shadow on Scroll
    const header = document.querySelector('.header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
            header.style.padding = '10px 0';
        } else {
            header.style.boxShadow = '0 1px 0 rgba(0,0,0,0.05)';
            header.style.padding = '0';
        }
    });

    // 3. Flash Message Auto-hide
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'all 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // 5. Sidebar Mobile Toggle
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainWrapper = document.querySelector('.main-wrapper');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('active');
            
            // Toggle menu icon
            const icon = menuToggle.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.setAttribute('data-lucide', 'x');
            } else {
                icon.setAttribute('data-lucide', 'menu');
            }
            lucide.createIcons();
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                const icon = menuToggle.querySelector('i');
                icon.setAttribute('data-lucide', 'menu');
                lucide.createIcons();
            }
        });
    }

    // 6. Dark Mode Toggle
    const darkModeSwitch = document.getElementById('dark-mode-switch');
    if (darkModeSwitch) {
        // Check for saved preference
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        darkModeSwitch.checked = isDarkMode;
        if (isDarkMode) document.body.classList.add('dark-mode');

    // 7. Sidebar "Créer une publication" interaction
    const createPubBtn = document.querySelector('.btn-create-pub');
    if (createPubBtn) {
        createPubBtn.addEventListener('click', () => {
            const currentPath = window.location.pathname;
            if (currentPath.includes('feed.php')) {
                const postInput = document.querySelector('.feed-main button');
                if (postInput) {
                    postInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    postInput.style.borderColor = 'var(--primary-blue)';
                    postInput.style.boxShadow = '0 0 0 4px rgba(21, 101, 192, 0.1)';
                    setTimeout(() => {
                        postInput.style.borderColor = '#e5e7eb';
                        postInput.style.boxShadow = 'none';
                    }, 2000);
                }
            } else {
                window.location.href = 'feed.php';
            }
        });
    }
});