document.addEventListener("DOMContentLoaded", () => {
    const burger = document.getElementById("cm-burger");
    const mobileMenu = document.getElementById("cm-mobile-menu");
    const searchToggle = document.getElementById("cm-search-toggle");
    const searchBar = document.getElementById("cm-search-bar");

    if (burger && mobileMenu) {
        burger.addEventListener("click", () => {
            mobileMenu.classList.toggle("active");
        });
    }

    if (searchToggle && searchBar) {
        searchToggle.addEventListener("click", (e) => {
            e.preventDefault();
            searchBar.classList.toggle("active");
        });
    }
});

// ========================================
// TOP BAR - Bandeau défilant infini
// ========================================
document.addEventListener("DOMContentLoaded", () => {
    const topBar = document.querySelector(".top-bar");
    const track = document.querySelector(".top-bar-track");

    if (!track || !topBar) return;

    // Cloner le contenu original
    const originalContent = track.cloneNode(true);
    const clonedTrack = originalContent.cloneNode(true);
    clonedTrack.classList.add("cloned");

    // Ajouter le clone au conteneur
    topBar.appendChild(clonedTrack);

    // Calculer les dimensions
    const trackWidth = track.offsetWidth;
    const containerWidth = topBar.offsetWidth;

    // Définir la durée de l'animation (en secondes)
    const animationDuration = 20;

    // Position de départ et d'arrivée
    let position1 = 0;
    let position2 = trackWidth;

    // Variable pour gérer la pause
    let isPaused = false;

    // Fonction d'animation
    function animate() {
        if (!isPaused) {
            // Vitesse en pixels par frame
            const speed = trackWidth / (animationDuration * 60); // 60 fps

            // Déplacer les deux tracks
            position1 -= speed;
            position2 -= speed;

            // Reset quand le premier track est complètement sorti
            if (position1 <= -trackWidth) {
                position1 = trackWidth;
            }

            // Reset quand le deuxième track est complètement sorti
            if (position2 <= -trackWidth) {
                position2 = trackWidth;
            }

            // Appliquer les transformations
            track.style.transform = `translateX(${position1}px)`;
            clonedTrack.style.transform = `translateX(${position2}px)`;
        }

        requestAnimationFrame(animate);
    }

    // Démarrer l'animation
    animate();

    // Pause au hover (optionnel)
    topBar.addEventListener("mouseenter", () => {
        isPaused = true;
    });

    topBar.addEventListener("mouseleave", () => {
        isPaused = false;
    });
});

// Gestion des menus dropdown avec rotation du chevron
document.addEventListener("DOMContentLoaded", () => {
    const dropdowns = document.querySelectorAll(".main-menu .dropdown, .main-menu .menu-item-has-children");

    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector("a");

        if (toggle) {
            // Ajouter le chevron SVG si pas déjà présent
            if (!toggle.querySelector(".chevron-icon")) {
                const chevronSvg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                chevronSvg.setAttribute("class", "chevron-icon");
                chevronSvg.setAttribute("width", "12");
                chevronSvg.setAttribute("height", "12");
                chevronSvg.setAttribute("viewBox", "0 0 16 16");
                chevronSvg.setAttribute("fill", "none");

                const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
                path.setAttribute("d", "M3.5 5.75L8 10.25L12.5 5.75");
                path.setAttribute("stroke", "currentColor");
                path.setAttribute("stroke-width", "1.5");
                path.setAttribute("stroke-linecap", "round");
                path.setAttribute("stroke-linejoin", "round");

                chevronSvg.appendChild(path);
                toggle.appendChild(chevronSvg);
            }

            toggle.addEventListener("click", (e) => {
                e.preventDefault();

                // Fermer les autres dropdowns
                dropdowns.forEach(other => {
                    if (other !== dropdown) {
                        other.classList.remove("active");
                    }
                });

                // Toggle le dropdown actuel
                dropdown.classList.toggle("active");
            });
        }
    });

    // Fermer les dropdowns si on clique ailleurs
    document.addEventListener("click", (e) => {
        if (!e.target.closest(".dropdown") && !e.target.closest(".menu-item-has-children")) {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove("active");
            });
        }
    });
});

// Popup search

document.addEventListener('DOMContentLoaded', () => {
    const openBtn  = document.querySelector('.search-icon');
    const overlay  = document.getElementById('searchOverlay');
    const closeBtn = overlay.querySelector('.search-overlay__close');
    const backdrop = overlay.querySelector('.search-overlay__backdrop');
    const input    = overlay.querySelector('.search-field');

    function openSearch(e) {
        e.preventDefault();
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        setTimeout(() => input.focus(), 150);
    }

    function closeSearch() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
    }

    openBtn.addEventListener('click', openSearch);
    closeBtn.addEventListener('click', closeSearch);
    backdrop.addEventListener('click', closeSearch);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSearch();
    });
});
