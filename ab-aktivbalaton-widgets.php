<?php
/**
 * Plugin Name:  AktívBalaton – Widgets Pack
 * Plugin URI:   https://aktivbalaton.hu
 * Description:  Öt Elementor widget: Hero Statisztikák, Esemény Grid, Kategória Pillek, Kereső, Gyors linkek.
 *               Önálló plugin – nem függ a sablontól, child theme nem szükséges.
 * Version:      2.12.0
 * Author:       AktívBalaton
 * Author URI:   https://aktivbalaton.hu
 * Text Domain:  ab-widgets
 * Requires PHP: 8.0
 * Requires at least: 6.0
 * Elementor tested up to: 3.21
 */

defined('ABSPATH') || exit;

define('AB_WIDGETS_VERSION', '2.12.0');
define('AB_WIDGETS_PATH',    plugin_dir_path(__FILE__));
define('AB_WIDGETS_URL',     plugin_dir_url(__FILE__));

add_action('plugins_loaded', function () {

    // Elementor nem aktív
    if (!did_action('elementor/loaded')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning"><p>'
               . '<strong>AktívBalaton Widgets:</strong> Az Elementor plugin szükséges a működéshez.'
               . '</p></div>';
        });
        return;
    }

    // ab-esemenyek plugin nem aktív – figyelmeztetés
    // (ABE_VERSION-t az Events plugin a betöltéskor definiálja – megbízható már plugins_loaded-on,
    //  ellentétben a post_type_exists-szel, ami csak init után igaz → korábban téves riasztás)
    if (!defined('ABE_VERSION')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning"><p>'
               . '<strong>AktívBalaton Widgets:</strong> Az AktivBalaton Events plugin nem aktív – a widgetek üres állapotot jelenítenek meg.'
               . '</p></div>';
        });
    }

    // Widgetek regisztrálása
    add_action('elementor/widgets/register', function ($wm) {
        require_once AB_WIDGETS_PATH . 'includes/class-ab-hero-stats-widget.php';
        require_once AB_WIDGETS_PATH . 'includes/class-ab-events-grid-widget.php';
        require_once AB_WIDGETS_PATH . 'includes/class-ab-category-pills-widget.php';
        require_once AB_WIDGETS_PATH . 'includes/class-ab-search-widget.php';
        require_once AB_WIDGETS_PATH . 'includes/class-ab-quick-links-widget.php';

        $wm->register(new \AktivBalaton\Hero_Stats_Widget());
        $wm->register(new \AktivBalaton\Events_Grid_Widget());
        $wm->register(new \AktivBalaton\Category_Pills_Widget());
        $wm->register(new \AktivBalaton\Search_Widget());
        $wm->register(new \AktivBalaton\Quick_Links_Widget());
    });

    // CSS + JS betöltése frontenden
    add_action('elementor/frontend/after_enqueue_styles', 'ab_widgets_enqueue_assets');
});

// Kedvencek (szív) JS – UNIVERZÁLIS betöltés: MINDEN frontend oldalon, az Elementortól
// függetlenül, mert a .ab-card-save szívek máshol is megjelenhetnek (pl. naptár oldal).
// A fájl kicsi, és ha nincs .ab-card-save a lapon, nem csinál semmit.
add_action('wp_enqueue_scripts', 'ab_widgets_enqueue_favorites');
function ab_widgets_enqueue_favorites(): void {
    wp_enqueue_script(
        'ab-favorites',
        AB_WIDGETS_URL . 'assets/js/ab-favorites.js',
        [],
        AB_WIDGETS_VERSION,
        true
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// LiteSpeed Cache kompatibilitás
// A LiteSpeed "Load JS Deferred/Delayed" + "JS Combine" rossz sorrendben
// futtathatja az Elementor (elementorFrontendConfig) és a Contact Form 7 (wpcf7)
// szkriptjeit → "is not defined" hiba → az egész oldal JS-e elhal (fehér oldal).
// Itt kizárjuk ezeket a késleltetésből, hogy a LiteSpeed beállítások módosítása
// NÉLKÜL is helyes maradjon a betöltési sorrend. (Ha a LiteSpeed nincs telepítve,
// a szűrő egyszerűen nem fut le – ártalmatlan.)
// ─────────────────────────────────────────────────────────────────────────────
add_filter('litespeed_optm_js_defer_exc', 'ab_widgets_litespeed_js_defer_exc');
function ab_widgets_litespeed_js_defer_exc($excludes) {
    if (!is_array($excludes)) {
        $excludes = [];
    }
    $add = [
        'elementorFrontendConfig',         // Elementor inline config ("elementorFrontendConfig is not defined")
        'elementor-frontend',              // Elementor frontend.js
        'wpcf7',                           // Contact Form 7 inline
        'contact-form-7',                  // Contact Form 7 script src
        'contact-form-7-js-translations',  // CF7 fordítás-inline (wp.i18n hívás)
        'wp-i18n',                         // WordPress i18n
        '/wp-includes/js/dist/',           // WP core wp.* libek (i18n, hooks, dom-ready…) – "wp is not defined" fix
    ];
    foreach ($add as $item) {
        if (!in_array($item, $excludes, true)) {
            $excludes[] = $item;
        }
    }
    return $excludes;
}

function ab_widgets_enqueue_assets(): void {
    wp_enqueue_style(
        'ab-widgets',
        AB_WIDGETS_URL . 'assets/css/ab-widgets.css',
        [],
        AB_WIDGETS_VERSION
    );
    wp_enqueue_script(
        'ab-widgets',
        AB_WIDGETS_URL . 'assets/js/ab-widgets.js',
        [],
        AB_WIDGETS_VERSION,
        true
    );
}
