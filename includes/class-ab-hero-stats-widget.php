<?php
namespace AktivBalaton;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * AB Hero Statisztikák Widget
 *
 * Dinamikusan lekéri az EventON adatbázisból:
 *  – Jövőbeli események száma
 *  – Aktív kategóriák száma (event_type taxonómia)
 *  – Egyedi helyszínek száma (evcal_location meta)
 *
 * Minden adat 1 órás WordPress tranziensben van cachelve,
 * hogy ne terhelje az adatbázist minden oldalletöltéskor.
 */
class Hero_Stats_Widget extends Widget_Base {

    public function get_name(): string    { return 'ab_hero_stats'; }
    public function get_title(): string   { return 'AB – Hero Statisztikák'; }
    public function get_icon(): string    { return 'eicon-counter'; }
    public function get_categories(): array { return ['general']; }
    public function get_keywords(): array {
        return ['statisztika', 'számláló', 'hero', 'balaton', 'eventon'];
    }

    // ----------------------------------------------------------------
    //  ELEMENTOR KONTROLOK
    // ----------------------------------------------------------------
    protected function register_controls(): void {

        // --- Statisztikák szekció ---
        $this->start_controls_section('section_stats', [
            'label' => 'Statisztikák',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('time_range', [
            'label'       => 'Időtáv',
            'type'        => Controls_Manager::SELECT,
            'default'     => 'year',
            'options'     => [
                'week'  => 'Aktuális hét (hétfőtől vasárnapig)',
                'month' => 'Aktuális hónap',
                'year'  => 'Aktuális év',
            ],
            'description' => 'Az esemény- és helyszínszámláló erre az időtávra vonatkozik. A kategóriaszám mindig teljes.',
        ]);

        $this->add_control('stat1_label', [
            'label'   => '1. statisztika – felirat',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Esemény idén',
        ]);

        $this->add_control('stat2_label', [
            'label'   => '2. statisztika – felirat',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Kategória',
        ]);

        $this->add_control('stat3_label', [
            'label'   => '3. statisztika – felirat',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Helyszín a parton',
        ]);

        $this->add_control('suffix', [
            'label'       => 'Szám utáni jel',
            'type'        => Controls_Manager::TEXT,
            'default'     => '+',
            'description' => 'pl. "+" → 340+',
        ]);

        $this->add_control('number_size', [
            'label'      => 'Szám betűmérete (px)',
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 20, 'max' => 96]],
            'default'    => ['unit' => 'px', 'size' => 42],
            'selectors'  => [
                '{{WRAPPER}} .ab-stat-num' => 'font-size: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('number_weight', [
            'label'   => 'Szám vastagság',
            'type'    => Controls_Manager::SELECT,
            'default' => '700',
            'options' => [
                '400' => 'Normal',
                '600' => 'Semi-bold',
                '700' => 'Bold',
                '800' => 'Extra-bold',
                '900' => 'Black',
            ],
            'selectors' => [
                '{{WRAPPER}} .ab-stat-num' => 'font-weight: {{VALUE}};',
            ],
        ]);

        $this->add_control('label_size', [
            'label'      => 'Felirat betűmérete (px)',
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 10, 'max' => 24]],
            'default'    => ['unit' => 'px', 'size' => 13],
            'selectors'  => [
                '{{WRAPPER}} .ab-stat-label' => 'font-size: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('cache_hours', [
            'label'       => 'Cache időtartam (óra)',
            'type'        => Controls_Manager::NUMBER,
            'default'     => 1,
            'min'         => 0,
            'max'         => 24,
            'description' => '0 = nincs cache (fejlesztéshez). Élesben 1–6 óra ajánlott.',
        ]);

        $this->end_controls_section();

        // --- Design szekció ---
        $this->start_controls_section('section_style', [
            'label' => 'Design',
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('number_color', [
            'label'     => 'Szám színe',
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .ab-stat-num' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('label_color', [
            'label'     => 'Felirat színe',
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(255,255,255,0.65)',
            'selectors' => ['{{WRAPPER}} .ab-stat-label' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('divider_color', [
            'label'     => 'Elválasztó színe',
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(255,255,255,0.25)',
            'selectors' => ['{{WRAPPER}} .ab-stat-divider' => 'background: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    // ----------------------------------------------------------------
    //  ADATLEKÉRÉS
    // ----------------------------------------------------------------

    /** Jövőbeli események száma – abe_esemeny CPT, abe_kezdo_datum meta
     *  Mindig $today-től számol (valódi jövőbeli), az időtáv a végdátumot határozza meg.
     */
    private function count_future_events(int $cache_h, string $range = 'year'): int {
        $today = date('Y-m-d');
        // v2: cache kulcs napi felbontású hogy ne maradjanak benn régi adatok
        $key = 'abe2_stat_events_' . $range . '_' . date('Ymd');
        if ($cache_h > 0) {
            $cached = get_transient($key);
            if ($cached !== false) return (int) $cached;
        }

        switch ($range) {
            case 'week':
                $d_end = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'month':
                $d_end = date('Y-m-t');
                break;
            default: // year
                $d_end = date('Y-12-31');
                break;
        }

        $q = new \WP_Query([
            'post_type'      => 'abe_esemeny',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                ['key' => 'abe_kezdo_datum', 'value' => $today, 'compare' => '>=', 'type' => 'DATE'],
                ['key' => 'abe_kezdo_datum', 'value' => $d_end,  'compare' => '<=', 'type' => 'DATE'],
            ],
        ]);
        $count = (int) $q->found_posts;
        wp_reset_postdata();

        if ($cache_h > 0) set_transient($key, $count, $cache_h * HOUR_IN_SECONDS);
        return $count;
    }

    /** Aktív abe_kategoria száma */
    private function count_categories(int $cache_h): int {
        $key = 'abe2_stat_categories';
        if ($cache_h > 0) {
            $cached = get_transient($key);
            if ($cached !== false) return (int) $cached;
        }

        $terms = get_terms(['taxonomy' => 'abe_kategoria', 'hide_empty' => true, 'fields' => 'ids']);
        $count = is_wp_error($terms) ? 0 : count($terms);

        if ($cache_h > 0) set_transient($key, $count, $cache_h * HOUR_IN_SECONDS);
        return $count;
    }

    /** Egyedi helyszínek száma – abe_helyszin_id alapján */
    private function count_locations(int $cache_h, string $custom_key = '', string $range = 'year'): int {
        $cache_key = 'abe2_stat_locations_' . $range . '_' . date('Ymd');
        if ($cache_h > 0) {
            $cached = get_transient($cache_key);
            if ($cached !== false) return (int) $cached;
        }

        $today = date('Y-m-d');
        switch ($range) {
            case 'week':
                $d_from = $today;
                $d_end  = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'month':
                $d_from = $today;
                $d_end  = date('Y-m-t');
                break;
            default:
                $d_from = $today;
                $d_end  = date('Y-12-31');
                break;
        }

        global $wpdb;
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.meta_value)
                 FROM {$wpdb->postmeta} AS pm
                 INNER JOIN {$wpdb->postmeta} AS dt ON dt.post_id = pm.post_id
                 INNER JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
                 WHERE pm.meta_key    = 'abe_helyszin_id'
                   AND pm.meta_value != ''
                   AND pm.meta_value != '0'
                   AND dt.meta_key    = 'abe_kezdo_datum'
                   AND dt.meta_value >= %s
                   AND dt.meta_value <= %s
                   AND p.post_type    = 'abe_esemeny'
                   AND p.post_status  = 'publish'",
                $d_from,
                $d_end
            )
        );

        if ($cache_h > 0) set_transient($cache_key, $count, $cache_h * HOUR_IN_SECONDS);
        return $count;
    }

    // ----------------------------------------------------------------
    //  RENDER
    // ----------------------------------------------------------------
    protected function render(): void {
        $s = $this->get_settings_for_display();

        $cache_h    = (int) ($s['cache_hours']   ?? 1);
        $l1         = esc_html($s['stat1_label'] ?? 'Esemény idén');
        $l2         = esc_html($s['stat2_label'] ?? 'Kategória');
        $l3         = esc_html($s['stat3_label'] ?? 'Helyszín a parton');
        $time_range = $s['time_range'] ?? 'year';

        $n1 = $this->count_future_events($cache_h, $time_range);
        $n2 = $this->count_categories($cache_h);
        $n3 = $this->count_locations($cache_h, '', $time_range);
        ?>

        <div class="ab-hero-stats">
            <div class="ab-stat">
                <span class="ab-stat-num"><?php echo (int) $n1; ?></span>
                <span class="ab-stat-label"><?php echo $l1; ?></span>
            </div>
            <div class="ab-stat-divider"></div>
            <div class="ab-stat">
                <span class="ab-stat-num"><?php echo (int) $n2; ?></span>
                <span class="ab-stat-label"><?php echo $l2; ?></span>
            </div>
            <div class="ab-stat-divider"></div>
            <div class="ab-stat">
                <span class="ab-stat-num"><?php echo (int) $n3; ?></span>
                <span class="ab-stat-label"><?php echo $l3; ?></span>
            </div>
        </div>

        <?php
    }
}
