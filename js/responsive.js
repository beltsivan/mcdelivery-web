document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('navToggle');
    var nav = document.querySelector('nav');
    var accountDropdown = document.querySelector('.account-dropdown');

    if (!toggle || !nav) return;

    // Hamburger toggle
    toggle.addEventListener('click', function () {
        nav.classList.toggle('is-open');
        toggle.classList.toggle('is-open');
    });

    // Close nav when clicking outside
    document.addEventListener('click', function (e) {
        if (!nav.contains(e.target) && !toggle.contains(e.target)) {
            nav.classList.remove('is-open');
            toggle.classList.remove('is-open');
        }
    });

    // My Account dropdown on mobile (click instead of hover)
    if (accountDropdown) {
        var dropbtn = accountDropdown.querySelector('.dropbtn');
        if (dropbtn) {
            dropbtn.addEventListener('click', function (e) {
                if (window.innerWidth <= 1024) {
                    e.preventDefault();
                    accountDropdown.classList.toggle('is-open');
                }
            });
        }
    }

    // Close nav when a nav link is clicked (except account dropdown toggle)
    nav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024) {
                nav.classList.remove('is-open');
                toggle.classList.remove('is-open');
            }
        });
    });

    // Recalculate sticky offsets on resize
    function adjustStickyOffsets() {
        var header = document.querySelector('header');
        var headerH = header ? header.offsetHeight : 70;
        var subNav = document.querySelector('.sub-nav-wrapper');
        var sideBag = document.getElementById('sideBag');

        if (subNav) {
            subNav.style.top = headerH + 'px';
        }
        if (sideBag) {
            sideBag.style.top = headerH + 'px';
            sideBag.style.height = 'calc(100vh - ' + headerH + 'px)';
        }
    }

    adjustStickyOffsets();
    window.addEventListener('resize', adjustStickyOffsets);
});
