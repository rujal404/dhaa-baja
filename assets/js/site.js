/* Dhaa Baja — Public site JS */

document.addEventListener('DOMContentLoaded', function () {
    // Add a subtle shadow to the sticky nav once the page has scrolled
    var nav = document.querySelector('nav');
    if (nav) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 20) {
                nav.classList.add('nav-scrolled');
            } else {
                nav.classList.remove('nav-scrolled');
            }
        });
    }

    // Smooth-scroll for in-page anchor links (e.g. events.php#event-3)
    document.querySelectorAll('a[href*="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var url = new URL(link.href, window.location.href);
            if (url.pathname === window.location.pathname && url.hash) {
                var target = document.querySelector(url.hash);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });
});
