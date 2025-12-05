// Disable automatic scroll restoration
if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}

// --- Force reload always to Home ---
window.addEventListener("load", () => {
    // Remove any hash from URL
    if (window.location.hash) {
        history.replaceState(null, null, window.location.pathname + window.location.search);
    }

    // Scroll to top (Home) after Home section is loaded
    setTimeout(() => {
        const home = document.getElementById("home");
        if (home) home.scrollIntoView({ behavior: "auto" });
    }, 50); // short delay ensures Home.html is loaded
});

// --- Smooth scroll for nav links ---
function attachNavScroll() {
    document.querySelectorAll('nav ul li a').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const targetId = link.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
                history.replaceState(null, null, window.location.pathname + window.location.search + '#' + targetId);
            }
        });
    });
}

// --- Logo click scrolls to Home ---
function attachLogoScroll() {
    const logoWrapper = document.getElementById('logo');
    if (logoWrapper) {
        logoWrapper.addEventListener('click', e => {
            e.preventDefault();
            const home = document.getElementById('home');
            if (home) {
                home.scrollIntoView({ behavior: 'smooth' });
                history.replaceState(null, null, window.location.pathname + window.location.search);
            }
        });
    }
}

// --- Scroll indicator ---
window.onscroll = function () {
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    const indicator = document.getElementById("scroll-indicator");
    if (indicator) indicator.style.width = scrolled + "%";
};

// --- Initialize after DOM is ready ---
document.addEventListener("DOMContentLoaded", () => {
    attachNavScroll();
    attachLogoScroll();
});
