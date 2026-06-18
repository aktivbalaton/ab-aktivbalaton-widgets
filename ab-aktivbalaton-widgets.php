<?php
/**
 * Plugin Name:  AktívBalaton – Widgets Pack
 * Plugin URI:   https://aktivbalaton.hu
 * Description:  Négy Elementor widget: Hero Statisztikák, Esemény Grid, Kategória Pillek, Kereső.
 *               Önálló plugin – nem függ a sablontól, child theme nem szükséges.
 * Version:      2.2.2
 * Author:       AktívBalaton
 * Author URI:   https://aktivbalaton.hu
 * Text Domain:  ab-widgets
 * Requires PHP: 8.0
 * Requires at least: 6.0
 * Elementor tested up to: 3.21
 */

defined('ABSPATH') || exit;

define('AB_WIDGETS_VERSION', '2.2.2');
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

        $wm->register(new \AktivBalaton\Hero_Stats_Widget());
        $wm->register(new \AktivBalaton\Events_Grid_Widget());
        $wm->register(new \AktivBalaton\Category_Pills_Widget());
        $wm->register(new \AktivBalaton\Search_Widget());
    });

    // CSS + JS betöltése frontenden
    add_action('elementor/frontend/after_enqueue_styles', 'ab_widgets_enqueue_assets');
});

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
