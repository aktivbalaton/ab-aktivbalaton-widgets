/**
 * AktívBalaton – Widgets Pack JS  v1.0.0
 *
 * Minimális JavaScript – csak a 🔖 mentés gomb toggling kezelése.
 * A carousel JS az eredeti ab-events-carousel.js-ben van, ahhoz nem nyúlunk.
 */
(function () {
    'use strict';

    function initSaveButtons() {
        document.querySelectorAll('.ab-card-save').forEach(function (btn) {
            if (btn.dataset.abInit) return; // ne dupla-init
            btn.dataset.abInit = '1';

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var saved = btn.dataset.saved === '1';
                btn.dataset.saved  = saved ? '0' : '1';
                btn.textContent    = saved ? '🔖' : '🔖';
                btn.style.background = saved ? '' : '#FEE2E2';
                btn.title = saved ? 'Mentés' : 'Mentve';

                // Pop micro-interakció
                btn.classList.remove('ab-pop');
                void btn.offsetWidth; // reflow → újraindítja az animációt
                btn.classList.add('ab-pop');
            });
        });
    }

    // ── Scroll-in lépcsőzetes belépő (IntersectionObserver) ──────────────
    function initGridReveal() {
        var reduce = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        document.querySelectorAll('.ab-events-grid-wrapper').forEach(function (wrap) {
            if (wrap.dataset.abReveal) return;
            wrap.dataset.abReveal = '1';

            var cards = wrap.querySelectorAll('.ab-event-card');
            if (!cards.length) return;

            // JS nélkül / régi böngészőn / mozgáscsökkentésnél: marad látható
            if (reduce || !('IntersectionObserver' in window)) return;

            wrap.classList.add('ab-anim-ready');

            var io = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    cards.forEach(function (card, i) {
                        card.style.transitionDelay = (i * 70) + 'ms';
                        card.classList.add('ab-in');
                    });
                    obs.disconnect();
                });
            }, { threshold: 0.15 });

            io.observe(wrap);
        });
    }

    // DOM ready
    function abInit() { initSaveButtons(); initGridReveal(); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', abInit);
    } else {
        abInit();
    }

    // Aktív pill kiemelés URL paraméter alapján (?kategoria=slug)
    function highlightActivePill() {
        var params = new URLSearchParams(window.location.search);
        var aktiv = params.get('kategoria');
        if (!aktiv) return;

        document.querySelectorAll('.ab-pill').forEach(function(pill) {
            var href = pill.getAttribute('href') || '';
            var pillParam = new URLSearchParams(href.split('?')[1] || '').get('kategoria');
            if (pillParam === aktiv) {
                pill.classList.add('ab-pill--active');
            } else {
                pill.classList.remove('ab-pill--active');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', highlightActivePill);
    } else {
        highlightActivePill();
    }

    // Elementor frontend újrarenderelés után (szerkesztőben)
    if (window.elementorFrontend) {
        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/ab_events_grid.default',
            function () { initSaveButtons(); initGridReveal(); }
        );
        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/ab_hero_stats.default',
            initSaveButtons
        );
    }

})();
