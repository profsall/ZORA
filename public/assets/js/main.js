/* ZORA — interaksi navigasi */
(function () {
    'use strict';

    function siapkanMenuHp() {
        var tombol = document.querySelector('.mobile-menu-toggle');
        var nav = document.querySelector('.main-nav');

        if (!tombol || !nav) return;

        tombol.setAttribute('aria-expanded', nav.classList.contains('open') ? 'true' : 'false');
        tombol.setAttribute('aria-label', 'Buka atau tutup navigasi');

        tombol.addEventListener('click', function () {
            var terbuka = nav.classList.toggle('open');
            tombol.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
        });

        document.addEventListener('click', function (kejadian) {
            if (!nav.contains(kejadian.target) && !tombol.contains(kejadian.target)) {
                nav.classList.remove('open');
                tombol.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (kejadian) {
            if (kejadian.key === 'Escape' && nav.classList.contains('open')) {
                nav.classList.remove('open');
                tombol.setAttribute('aria-expanded', 'false');
                tombol.focus();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', siapkanMenuHp);
})();
