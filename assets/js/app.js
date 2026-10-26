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

        darkModeSwitch.addEventListener('change', () => {
            const isDark = darkModeSwitch.checked;
            document.body.classList.toggle('dark-mode', isDark);
            localStorage.setItem('darkMode', isDark);
        });
    }

    // 7. Sidebar "Créer une publication" interaction
    const createPubBtn = document.querySelector('.btn-create-pub');
    if (createPubBtn) {
        createPubBtn.addEventListener('click', () => {
            const currentPath = window.location.pathname;
            if (currentPath.includes('feed.php')) {
                if (typeof openPostModal === 'function') {
                    openPostModal();
                }
            } else {
                window.location.href = 'feed.php';
            }
        });
    }

    // 8. Global Intelligent Search & Overlay
    const headerSearchInput = document.getElementById('header-search-input');
    const searchOverlay = document.getElementById('search-overlay');
    const searchOverlayInput = document.getElementById('search-overlay-input');
    const searchOverlayClose = document.getElementById('search-overlay-close');
    const searchOverlaySuggestions = document.getElementById('search-overlay-suggestions');

    if (headerSearchInput && searchOverlay && searchOverlayInput && searchOverlayClose && searchOverlaySuggestions) {
        let debounceTimer;

        // Open overlay when clicking header search
        headerSearchInput.addEventListener('click', () => {
            openSearchOverlay();
        });
        headerSearchInput.addEventListener('focus', () => {
            openSearchOverlay();
        });

        function openSearchOverlay() {
            searchOverlay.classList.add('active');
            setTimeout(() => {
                searchOverlayInput.focus();
            }, 100);
            if (headerSearchInput.value) {
                searchOverlayInput.value = headerSearchInput.value;
                performSearch(searchOverlayInput.value);
            }
        }

        function closeSearchOverlay() {
            searchOverlay.classList.remove('active');
            searchOverlayInput.value = '';
            searchOverlaySuggestions.innerHTML = `
                <div class="search-overlay-placeholder">
                    <i data-lucide="search" style="width: 48px; height: 48px; color: #CBD5E1; margin-bottom: 16px;"></i>
                    <h3>Commencez à taper pour rechercher</h3>
                    <p>Trouvez des professionnels qualifiés près de chez vous</p>
                </div>
            `;
            lucide.createIcons();
        }

        searchOverlayClose.addEventListener('click', closeSearchOverlay);
        searchOverlay.addEventListener('click', (e) => {
            if (e.target === searchOverlay) {
                closeSearchOverlay();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && searchOverlay.classList.contains('active')) {
                closeSearchOverlay();
            }
        });

        // Search logic in overlay
        searchOverlayInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const query = searchOverlayInput.value.trim();

            if (query.length < 2) {
                searchOverlaySuggestions.innerHTML = `
                    <div class="search-overlay-placeholder">
                        <i data-lucide="search" style="width: 48px; height: 48px; color: #CBD5E1; margin-bottom: 16px;"></i>
                        <h3>Commencez à taper pour rechercher</h3>
                        <p>Trouvez des professionnels qualifiés près de chez vous</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }

            debounceTimer = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        function performSearch(query) {
            fetch(`api/search_professionals.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.results.length > 0) {
                        renderOverlayResults(data.results, query);
                    } else {
                        renderOverlayNoResults(query);
                    }
                })
                .catch(() => {
                    renderOverlayNoResults(query);
                });
        }

        function renderOverlayResults(items, query) {
            searchOverlaySuggestions.innerHTML = '';
            items.forEach(pro => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                
                let avatarHtml = '';
                if (pro.avatar) {
                    avatarHtml = `<img src="${pro.avatar}" alt="${pro.fullname}">`;
                } else {
                    avatarHtml = `<div class="search-result-avatar-placeholder">${pro.fullname.charAt(0).toUpperCase()}</div>`;
                }

                div.innerHTML = `
                    <div class="search-result-avatar">
                        ${avatarHtml}
                    </div>
                    <div class="search-result-info">
                        <div class="search-result-name">${pro.fullname}</div>
                        <div class="search-result-details">
                            <i data-lucide="briefcase"></i>
                            <span>${pro.profession || 'Professionnel'}</span>
                            ${pro.city ? `
                                <span style="margin: 0 4px;">•</span>
                                <i data-lucide="map-pin"></i>
                                <span>${pro.city}</span>
                            ` : ''}
                        </div>
                    </div>
                    <div class="search-result-actions">
                        <a href="pro.php?id=${pro.id}" class="search-result-btn view" title="Voir le profil">
                            <i data-lucide="user"></i>
                        </a>
                        <a href="api/start_conversation.php?with=${pro.id}" class="search-result-btn contact" title="Contacter">
                            <i data-lucide="message-square"></i>
                        </a>
                    </div>
                `;

                // Click sur la carte pour voir le profil
                div.querySelector('.search-result-avatar').addEventListener('click', () => {
                    window.location.href = `pro.php?id=${pro.id}`;
                });
                div.querySelector('.search-result-info').addEventListener('click', () => {
                    window.location.href = `pro.php?id=${pro.id}`;
                });

                searchOverlaySuggestions.appendChild(div);
            });

            // Add divider and view all button
            const divider = document.createElement('div');
            divider.className = 'search-result-divider';
            searchOverlaySuggestions.appendChild(divider);

            const viewAll = document.createElement('a');
            viewAll.href = `discover.php?q=${encodeURIComponent(query)}`;
            viewAll.className = 'search-result-view-all';
            viewAll.innerHTML = `
                <i data-lucide="search"></i>
                <span>Voir tous les résultats pour "${query}"</span>
            `;
            searchOverlaySuggestions.appendChild(viewAll);

            lucide.createIcons();
        }

        function renderOverlayNoResults(query) {
            searchOverlaySuggestions.innerHTML = `
                <div class="search-overlay-placeholder">
                    <i data-lucide="search-x" style="width: 48px; height: 48px; color: #CBD5E1; margin-bottom: 16px;"></i>
                    <h3>Aucun professionnel trouvé</h3>
                    <p>Nous n'avons pas trouvé de résultats pour "${query}"</p>
                </div>
                <div class="search-result-divider"></div>
                <a href="discover.php" class="search-result-view-all">
                    <i data-lucide="layout"></i>
                    <span>Découvrir tous les professionnels</span>
                </a>
            `;
            lucide.createIcons();
        }

        // Redirect on Enter
        searchOverlayInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                window.location.href = `discover.php?q=${encodeURIComponent(searchOverlayInput.value)}`;
            }
        });
    }
});
