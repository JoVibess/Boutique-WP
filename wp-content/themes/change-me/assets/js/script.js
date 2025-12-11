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

document.addEventListener("DOMContentLoaded", () => {
    const track = document.querySelector(".top-bar-track");

    if (track) {
        // Dupliquer le contenu pour avoir un défilement infini sans coupure
        const content = track.innerHTML;
        track.innerHTML = content + content + content;
    }
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

