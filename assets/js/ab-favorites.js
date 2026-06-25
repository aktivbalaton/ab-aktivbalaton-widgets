/**
 * AktívBalaton – Kedvencek (szív) – UNIVERZÁLIS, önálló logika  v1.1.0
 *
 * "Szerződés": bárhol megjelenő `.ab-card-save` elem + `data-event-id` attribútum
 * automatikusan működik – widget, naptár oldali kártya, vagy bármilyen jövőbeli kártya.
 *
 * - Esemény-delegáció a document-en → AJAX-szal utólag betöltött kártyák szíve is működik.
 * - localStorage kulcs: `abSavedEvents` (a meglévő tárolóval kompatibilis – nem vész el a már mentett).
 * - Vizuális állapot: `.ab-saved` osztály + ♡/♥ karakter + aria-pressed + title (a CSS már kezeli a színt).
 * - Billentyűzet: Enter/Space (a szív role="button" + tabindex="0").
 *
 * NEM hivatkozik semmilyen widget-specifikus konténerre (pl. .ab-events-grid) – csak a
 * `.ab-card-save` + `data-event-id` szerződésre támaszkodik.
 */
(function () {
    'use strict';

    var AB_SAVE_KEY = 'abSavedEvents';

    function getFavs() {
        try {
            var v = JSON.parse(localStorage.getItem(AB_SAVE_KEY) || '[]');
            return Array.isArray(v) ? v : [];
        } catch (e) { return []; }
    }
    function saveFavs(list) {
        try { localStorage.setItem(AB_SAVE_KEY, JSON.stringify(list)); } catch (e) {}
    }
    function isFav(id) { return getFavs().indexOf(id) !== -1; }
    function toggleFav(id) {
        var list = getFavs();
        var idx  = list.indexOf(id);
        var nowSaved;
        if (idx === -1) { list.push(id); nowSaved = true; }
        else { list.splice(idx, 1); nowSaved = false; }
        saveFavs(list);
        return nowSaved;
    }

    // ── Egy szív gomb vizuális állapotának beállítása ────────────────────
    function renderState(btn, isSaved) {
        btn.classList.toggle('ab-saved', isSaved);
        btn.textContent = isSaved ? '♥' : '♡';
        btn.title = isSaved ? 'Mentve – kattints az eltávolításhoz' : 'Esemény mentése';
        btn.setAttribute('aria-pressed', isSaved ? 'true' : 'false');
    }

    // ── Kezdő állapot beállítása minden (még nem inicializált) szívre ────
    function syncAll(root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('.ab-card-save:not([data-ab-fav-ready])').forEach(function (btn) {
            btn.dataset.abFavReady = '1';
            var id = btn.dataset.eventId;
            renderState(btn, !!id && isFav(id));
        });
    }

    // ── Toggle egy gombhoz (kattintás / billentyű) ───────────────────────
    function activate(btn) {
        var id = btn.dataset.eventId;
        if (!id) return;
        var nowSaved = toggleFav(id);
        renderState(btn, nowSaved);

        // Pop micro-interakció (a CSS .ab-pop animáció)
        btn.classList.remove('ab-pop');
        void btn.offsetWidth; // reflow → újraindítja az animációt
        btn.classList.add('ab-pop');

        // Kedvenceim oldalon: ha most lett TÖRÖLVE a kedvencekből, a kártya tűnjön el.
        if (!nowSaved) {
            var wrap = document.querySelector('[data-abe-kedvencek]');
            if (wrap) {
                var card = btn.closest ? btn.closest('.abe-grid-card') : null;
                if (card) {
                    card.remove();
                    if (!wrap.querySelectorAll('.abe-grid-card').length) {
                        var emptyEl = wrap.querySelector('.abe-kedvencek-empty');
                        if (emptyEl) emptyEl.style.display = 'block';
                    }
                }
            }
        }
    }

    // ── Delegált kattintás-figyelő ───────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.ab-card-save') : null;
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation(); // ne nyíljon meg a kártya modal / link
        activate(btn);
    });

    // ── Delegált billentyűzet-figyelő (Enter / Space) ────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ' && e.key !== 'Spacebar') return;
        var btn = e.target.closest ? e.target.closest('.ab-card-save') : null;
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        activate(btn);
    });

    // ── Kezdő szinkron oldalbetöltéskor ──────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { syncAll(document); });
    } else {
        syncAll(document);
    }

    // ── AJAX-szal utólag betöltött kártyák kezdő állapota ────────────────
    // (pl. naptár oldal Load More / nézetváltás) – a kattintás delegált, de a
    //  kezdő ♡/♥ megjelenítéshez az új elemeket is szinkronizálni kell.
    if ('MutationObserver' in window) {
        var mo = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                if (mutations[i].addedNodes && mutations[i].addedNodes.length) {
                    syncAll(document);
                    break;
                }
            }
        });
        var start = function () {
            if (document.body) mo.observe(document.body, { childList: true, subtree: true });
        };
        if (document.body) { start(); }
        else { document.addEventListener('DOMContentLoaded', start); }
    }

    // Más szkriptek számára elérhető (pl. AJAX után kézzel is hívható)
    window.abFavoritesSync = syncAll;

    // ── "Kedvenceim" oldal: a mentett események betöltése AJAX-szal ──
    // A [data-abe-kedvencek] konténer az ab-esemenyek [abe_kedvencek] shortcode-jából jön.
    // A tényleges kártyákat a szerver (abe_kedvencek_lista action) rendereli a navy
    // card-grid-template.php-vel; itt csak elküldjük a localStorage ID-ket és beillesztjük.
    function loadKedvencek() {
        var wrap = document.querySelector('[data-abe-kedvencek]');
        if (!wrap) return; // nem a Kedvenceim oldalon vagyunk

        var loadingEl = wrap.querySelector('.abe-kedvencek-loading');
        var emptyEl   = wrap.querySelector('.abe-kedvencek-empty');
        var listaEl   = wrap.querySelector('.abe-kedvencek-lista');

        function showEmpty() {
            if (loadingEl) loadingEl.style.display = 'none';
            if (emptyEl)   emptyEl.style.display = 'block';
        }

        // A nonce + ajaxurl az ab-esemenyek frontend.js abeAjax objektumából jön
        // (ugyanezen az oldalon betöltődik az [abe_kedvencek] shortcode miatt).
        if (typeof window.abeAjax === 'undefined') {
            console.warn('abeAjax nincs betöltve – a Kedvenceim oldal nem tud AJAX-olni');
            showEmpty();
            return;
        }

        var ids = getFavs();
        if (!ids.length) { showEmpty(); return; }

        var fd = new FormData();
        fd.append('action', 'abe_kedvencek_lista');
        fd.append('nonce', window.abeAjax.nonce);
        fd.append('ids', ids.join(','));

        fetch(window.abeAjax.ajaxurl, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (loadingEl) loadingEl.style.display = 'none';
                if (res && res.success && res.data && res.data.html && res.data.total > 0) {
                    listaEl.innerHTML = res.data.html;
                    syncAll(listaEl); // a frissen beillesztett kártyák szíveinek állapota
                } else {
                    showEmpty();
                }
            })
            .catch(function () {
                if (loadingEl) loadingEl.style.display = 'none';
                showEmpty();
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadKedvencek);
    } else {
        loadKedvencek();
    }

})();
