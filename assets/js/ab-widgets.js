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
            });
        });
    }

    // DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSaveButtons);
    } else {
        initSaveButtons();
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
            initSaveButtons
        );
        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/ab_hero_stats.default',
            initSaveButtons
        );
    }

})();
