<?php
namespace AktivBalaton;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

/**
 * AB Esemény Grid Widget  v3.0.0
 * v3.0.0: Migrálva EventON-ról ab-esemenyek pluginra
 *
 * location_filter értékei:
 *   'balatoni'       → kizárja abe_esemeny_tipus = "balatoni-videk" eseményeket
 *   'balatoni-videk' → csak abe_esemeny_tipus = "balatoni-videk" eseményeket
 *   'all'            → minden esemény
 */
class Events_Grid_Widget extends Widget_Base {

    public function get_name(): string      { return 'ab_events_grid'; }
    public function get_title(): string     { return 'AB – Esemény Grid'; }
    public function get_icon(): string      { return 'eicon-posts-grid'; }
    public function get_categories(): array { return ['general']; }
    public function get_keywords(): array   { return ['esemény', 'grid', 'kártya', 'event', 'balaton']; }

    // ═══════════════════════════════════════════════════════════════
    //  KONTROLOK
    // ═══════════════════════════════════════════════════════════════
    protected function register_controls(): void {

        // ── 1. TARTALOM ──────────────────────────────────────────────
        $this->start_controls_section('sec_content', [
            'label' => '📋 Tartalom',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('section_title', ['label' => 'Szekció cím', 'type' => Controls_Manager::TEXT, 'default' => 'Esemény ajánló']);
        $this->add_control('section_label', ['label' => 'Kis felirat (szekció fölött)', 'type' => Controls_Manager::TEXT, 'default' => 'Közelgő programok']);
        $this->add_control('btn_label', ['label' => 'Kártya gomb felirata', 'type' => Controls_Manager::TEXT, 'default' => 'Részletek']);
        $this->add_control('show_view_all', ['label' => '"Összes esemény" link megjelenítése', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Igen', 'label_off' => 'Nem', 'return_value' => 'yes', 'default' => 'yes']);
        $this->add_control('view_all_text', ['label' => '"Összes esemény" szöveg', 'type' => Controls_Manager::TEXT, 'default' => 'Összes esemény →', 'condition' => ['show_view_all' => 'yes']]);
        $this->add_control('view_all_url', ['label' => '"Összes esemény" URL', 'type' => Controls_Manager::URL, 'default' => ['url' => home_url('/naptar/')], 'condition' => ['show_view_all' => 'yes']]);

        $this->end_controls_section();

        // ── 2. LEKÉRDEZÉS ───────────────────────────────────────────
        $this->start_controls_section('sec_query', [
            'label' => '🔍 Lekérdezés',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('grid_layout', [
            'label'   => 'Grid elrendezés',
            'type'    => Controls_Manager::SELECT,
            'default' => 'asymmetric',
            'options' => ['asymmetric' => 'Aszimmetrikus (1 nagy + kisebb kártyák)', 'equal_2' => 'Egyenlő – 2 oszlop', 'equal_3' => 'Egyenlő – 3 oszlop', 'equal_4' => 'Egyenlő – 4 oszlop'],
        ]);

        $this->add_control('posts_count', ['label' => 'Megjelenített kártyák száma', 'type' => Controls_Manager::NUMBER, 'default' => 5, 'min' => 2, 'max' => 12]);
        $this->add_control('weeks_ahead', ['label' => 'Előrenézési időtáv (hét)', 'type' => Controls_Manager::NUMBER, 'default' => 4, 'min' => 1, 'max' => 52]);

        $this->add_control('filter_category', [
            'label'       => 'Kategória szűrő (abe_kategoria)',
            'type'        => Controls_Manager::SELECT2,
            'multiple'    => true,
            'options'     => $this->get_category_options(),
            'default'     => [],
            'description' => 'Ha üresen hagyod, minden kategória megjelenik.',
        ]);

        $this->add_control('location_filter', [
            'label'       => '🗺️ Helyszín szűrő',
            'type'        => Controls_Manager::SELECT,
            'default'     => 'balatoni',
            'options'     => [
                'balatoni'       => 'Csak balatoni (főoldal)',
                'balatoni-videk' => 'Csak Balaton-vidék (vidéki oldal)',
                'all'            => 'Összes esemény',
            ],
            'description' => 'Balatoni: kizárja a Balaton-vidéki eseményeket. Balaton-vidék: csak vidéki eseményeket mutat. Összes: mindent mutat.',
        ]);

        $this->add_control('only_with_image', ['label' => 'Csak képes események mutatása', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Igen', 'label_off' => 'Nem', 'return_value' => 'yes', 'default' => 'no']);

        $this->end_controls_section();

        // ── 3. MEGJELENÍTETT ELEMEK ──────────────────────────────────
        $this->start_controls_section('sec_elements', [
            'label' => '🔘 Megjelenített elemek',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_excerpt_featured', ['label' => 'Leírás – kiemelt kártyán', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Igen', 'label_off' => 'Nem', 'return_value' => 'yes', 'default' => 'yes']);
        $this->add_control('excerpt_words_featured', ['label' => 'Leírás hossza (szó) – kiemelt', 'type' => Controls_Manager::NUMBER, 'default' => 18, 'min' => 5, 'max' => 50, 'condition' => ['show_excerpt_featured' => 'yes']]);
        $this->add_control('show_excerpt_small', ['label' => 'Leírás – kis kártyákon', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Igen', 'label_off' => 'Nem', 'return_value' => 'yes', 'default' => 'no']);
        $this->add_control('excerpt_words_small', ['label' => 'Leírás hossza (szó) – kis kártyák', 'type' => Controls_Manager::NUMBER, 'default' => 10, 'min' => 5, 'max' => 30, 'condition' => ['show_excerpt_small' => 'yes']]);
        $this->add_control('show_time', ['label' => 'Időpont megjelenítése', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Igen', 'label_off' => 'Nem', 'return_value' => 'yes', 'default' => 'yes']);
        $this->add_control('show_location', ['label' => 'Helyszín megjelenítése', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Igen', 'label_off' => 'Nem', 'return_value' => 'yes', 'default' => 'yes']);
        $this->add_control('show_type_badge', ['label' => 'Típus badge megjelenítése', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Igen', 'label_off' => 'Nem', 'return_value' => 'yes', 'default' => 'yes']);
        $this->add_control('show_date_badge', ['label' => 'Dátum badge megjelenítése', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Igen', 'label_off' => 'Nem', 'return_value' => 'yes', 'default' => 'yes']);
        $this->add_control('show_save_btn', ['label' => '"Mentés" gomb megjelenítése', 'type' => Controls_Manager::SWITCHER, 'label_on' => 'Igen', 'label_off' => 'Nem', 'return_value' => 'yes', 'default' => 'yes']);

        $this->end_controls_section();

        // ── 4. KÉPEK ─────────────────────────────────────────────────
        $this->start_controls_section('sec_images', ['label' => '🖼️ Képek', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('img_radius', ['label' => 'Képsarkok kerekítése (px)', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => ['px' => ['min' => 0, 'max' => 32]], 'default' => ['unit' => 'px', 'size' => 0], 'selectors' => ['{{WRAPPER}} .ab-card-image' => 'border-radius: {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} 0 0;', '{{WRAPPER}} .ab-card-image img' => 'border-radius: {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} 0 0;', '{{WRAPPER}} .ab-card-image-placeholder' => 'border-radius: {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} 0 0;']]);
        $this->add_control('featured_height_mode', ['label' => 'Kiemelt kártya képmagassága', 'type' => Controls_Manager::SELECT, 'default' => 'auto', 'options' => ['auto' => 'Automatikus – kitölti a kis kártyák magasságát', 'fixed' => 'Rögzített – px értékkel adható meg']]);
        $this->add_control('img_height_featured', ['label' => 'Képmagasság – kiemelt kártya (px)', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => ['px' => ['min' => 160, 'max' => 600]], 'default' => ['unit' => 'px', 'size' => 320], 'condition' => ['featured_height_mode' => 'fixed'], 'selectors' => ['{{WRAPPER}} .ab-event-card--featured .ab-card-image' => 'height: {{SIZE}}{{UNIT}}; aspect-ratio: unset; flex: none;']]);
        $this->add_control('img_height_small', ['label' => 'Képmagasság – kis kártyák (px)', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => ['px' => ['min' => 80, 'max' => 400]], 'default' => ['unit' => 'px', 'size' => 180], 'selectors' => ['{{WRAPPER}} .ab-event-card:not(.ab-event-card--featured) .ab-card-image' => 'height: {{SIZE}}{{UNIT}}; aspect-ratio: unset;']]);
        $this->add_control('img_fit_mode', ['label' => 'Képillesztés módja', 'type' => Controls_Manager::SELECT, 'default' => 'backdrop', 'options' => ['cover' => 'Cover – levágja a kilógó részeket', 'backdrop' => 'Blurred backdrop – teljes kép + elmosott háttér', 'white' => 'Fehér háttér – letisztult, semleges']]);
        $this->add_control('backdrop_style', ['label' => 'Backdrop stílusa', 'type' => Controls_Manager::SELECT, 'default' => 'frosted', 'condition' => ['img_fit_mode' => 'backdrop'], 'options' => ['frosted' => 'Frosted glass – világos, levegős', 'dark' => 'Sötét – filmplakát hatás']]);
        $this->add_control('img_position', ['label' => 'Kép fókuszpont', 'type' => Controls_Manager::SELECT, 'default' => 'center center', 'condition' => ['img_fit_mode' => 'cover'], 'options' => ['center center' => 'Közép', 'center top' => 'Felső', 'center bottom' => 'Alsó', 'left center' => 'Bal', 'right center' => 'Jobb'], 'selectors' => ['{{WRAPPER}} .ab-card-image img' => 'object-position: {{VALUE}};']]);
        $this->add_control('overlay_opacity', ['label' => 'Overlay sötétség (0–100)', 'type' => Controls_Manager::SLIDER, 'range' => ['px' => ['min' => 0, 'max' => 100]], 'default' => ['size' => 50], 'selectors' => ['{{WRAPPER}} .ab-card-overlay' => 'background: linear-gradient(to bottom, transparent 20%, rgba(5,20,40,calc({{SIZE}} * 0.01)) 100%);']]);
        $this->end_controls_section();

        // ── 5. KÁRTYA DESIGN ──────────────────────────────────────────
        $this->start_controls_section('sec_card_style', ['label' => '🎨 Kártya design', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('card_radius', ['label' => 'Kártya sarkok kerekítése (px)', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => ['px' => ['min' => 0, 'max' => 32]], 'default' => ['unit' => 'px', 'size' => 16], 'selectors' => ['{{WRAPPER}} .ab-event-card' => 'border-radius: {{SIZE}}{{UNIT}};']]);
        $this->add_control('card_bg_color', ['label' => 'Kártya háttérszín', 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => ['{{WRAPPER}} .ab-event-card' => 'background-color: {{VALUE}};']]);
        $this->add_control('card_shadow', ['label' => 'Kártya árnyék', 'type' => Controls_Manager::SELECT, 'default' => 'soft', 'options' => ['none' => 'Nincs', 'soft' => 'Finom', 'medium' => 'Közepes', 'strong' => 'Erős']]);
        $this->add_control('accent_color', ['label' => 'Akcentus szín', 'type' => Controls_Manager::COLOR, 'default' => '#1B2D3F', 'selectors' => ['{{WRAPPER}} .ab-events-grid-wrapper' => '--ab-grid-accent: {{VALUE}};']]);
        $this->add_control('cta_color', ['label' => 'CTA gomb szín', 'type' => Controls_Manager::COLOR, 'default' => '#E8943A', 'selectors' => ['{{WRAPPER}} .ab-card-cta' => 'color: {{VALUE}};']]);
        $this->add_control('location_color', ['label' => 'Helyszín szöveg szín', 'type' => Controls_Manager::COLOR, 'default' => '#1B2D3F', 'selectors' => ['{{WRAPPER}} .ab-card-loc' => 'color: {{VALUE}};']]);
        $this->add_control('today_badge_color', ['label' => '"Ma" badge háttérszín', 'type' => Controls_Manager::COLOR, 'default' => '#EF4444', 'selectors' => ['{{WRAPPER}} .ab-badge-today' => 'background-color: {{VALUE}};']]);
        $this->add_control('divider_color', ['label' => 'Kártya footer elválasztó szín', 'type' => Controls_Manager::COLOR, 'default' => '#F0F5FA', 'selectors' => ['{{WRAPPER}} .ab-card-footer' => 'border-top-color: {{VALUE}};']]);
        $this->end_controls_section();

        // ── 6. TIPOGRÁFIA ─────────────────────────────────────────────
        $this->start_controls_section('sec_typography', ['label' => '✍️ Tipográfia', 'tab' => Controls_Manager::TAB_STYLE]);
        $this->add_control('title_color', ['label' => 'Cím szín', 'type' => Controls_Manager::COLOR, 'default' => '#1C2B3A', 'selectors' => ['{{WRAPPER}} .ab-card-title a' => 'color: {{VALUE}};']]);
        $this->add_control('title_color_hover', ['label' => 'Cím hover szín', 'type' => Controls_Manager::COLOR, 'default' => '#E8943A', 'selectors' => ['{{WRAPPER}} .ab-card-title a:hover' => 'color: {{VALUE}};']]);
        $this->add_control('title_size_featured', ['label' => 'Cím betűméret – kiemelt kártya (px)', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => ['px' => ['min' => 16, 'max' => 42]], 'default' => ['unit' => 'px', 'size' => 24], 'selectors' => ['{{WRAPPER}} .ab-event-card--featured .ab-card-title' => 'font-size: {{SIZE}}{{UNIT}};']]);
        $this->add_control('title_size_small', ['label' => 'Cím betűméret – kis kártyák (px)', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => ['px' => ['min' => 12, 'max' => 28]], 'default' => ['unit' => 'px', 'size' => 18], 'selectors' => ['{{WRAPPER}} .ab-event-card:not(.ab-event-card--featured) .ab-card-title' => 'font-size: {{SIZE}}{{UNIT}};']]);
        $this->add_group_control(Group_Control_Typography::get_type(), ['name' => 'title_typography', 'label' => 'Cím betűtípus & stílus', 'selector' => '{{WRAPPER}} .ab-card-title']);
        $this->add_control('meta_size', ['label' => 'Meta szöveg méret (px)', 'type' => Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => ['px' => ['min' => 10, 'max' => 18]], 'default' => ['unit' => 'px', 'size' => 12], 'selectors' => ['{{WRAPPER}} .ab-card-time, {{WRAPPER}} .ab-card-loc' => 'font-size: {{SIZE}}{{UNIT}};']]);
        $this->add_control('meta_color', ['label' => 'Meta szöveg szín – időpont', 'type' => Controls_Manager::COLOR, 'default' => '#8BA0B5', 'selectors' => ['{{WRAPPER}} .ab-card-time' => 'color: {{VALUE}};']]);
        $this->add_control('excerpt_color', ['label' => 'Leírás szöveg szín', 'type' => Controls_Manager::COLOR, 'default' => '#4A6080', 'selectors' => ['{{WRAPPER}} .ab-card-excerpt' => 'color: {{VALUE}};']]);
        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════
    //  SEGÉDFÜGGVÉNYEK
    // ═══════════════════════════════════════════════════════════════

    private function get_category_options(): array {
        $terms = get_terms(['taxonomy' => 'abe_kategoria', 'hide_empty' => true]);
        if (is_wp_error($terms) || empty($terms)) return [];
        $out = [];
        foreach ($terms as $t) { $out[$t->slug] = $t->name; }
        return $out;
    }

    private function shadow_css(string $level): string {
        return match($level) { 'none' => 'none', 'medium' => '0 6px 28px rgba(12,74,110,0.14)', 'strong' => '0 14px 48px rgba(12,74,110,0.22)', default => '0 2px 16px rgba(12,74,110,0.09)' };
    }

    private function grid_class(string $layout): string {
        return match($layout) { 'equal_2' => 'ab-grid-equal ab-grid-col-2', 'equal_3' => 'ab-grid-equal ab-grid-col-3', 'equal_4' => 'ab-grid-equal ab-grid-col-4', default => 'ab-grid-asymmetric' };
    }

    /**
     * Események lekérése – abe_esemeny CPT, abe_kezdo_datum, abe_kategoria / abe_esemeny_tipus taxonómia.
     */
    private function fetch_events(int $count, int $weeks, array $cat_slugs, bool $only_img, string $location_filter): array {
        $today = date('Y-m-d');
        $until = date('Y-m-d', strtotime('+' . ($weeks * 7) . ' days'));

        $base_meta = [
            'relation' => 'AND',
            ['key' => 'abe_kezdo_datum', 'value' => $today, 'compare' => '>=', 'type' => 'DATE'],
            ['key' => 'abe_kezdo_datum', 'value' => $until, 'compare' => '<=', 'type' => 'DATE'],
        ];

        $base_args = [
            'post_type'      => 'abe_esemeny',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'orderby'        => 'meta_value',
            'meta_key'       => 'abe_kezdo_datum',
            'order'          => 'ASC',
            'meta_query'     => $base_meta,
        ];

        // ── Kategória szűrő (abe_kategoria slug alapján) ──────────────
        $tax_query = [];
        if (!empty($cat_slugs)) {
            $tax_query[] = [
                'taxonomy' => 'abe_kategoria',
                'field'    => 'slug',
                'terms'    => array_values($cat_slugs),
                'operator' => 'IN',
            ];
        }

        // ── Helyszín szűrő (abe_esemeny_tipus) ────────────────────────
        if ($location_filter === 'balatoni-videk') {
            $tax_query[] = [
                'taxonomy' => 'abe_esemeny_tipus',
                'field'    => 'slug',
                'terms'    => ['balatoni-videk'],
                'operator' => 'IN',
            ];
        } elseif ($location_filter === 'balatoni') {
            $tax_query[] = [
                'taxonomy' => 'abe_esemeny_tipus',
                'field'    => 'slug',
                'terms'    => ['balatoni-videk'],
                'operator' => 'NOT IN',
            ];
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $base_args['tax_query'] = $tax_query;
        }

        if ($only_img) {
            $args               = $base_args;
            $args['meta_query'] = array_merge($base_meta, [['key' => '_thumbnail_id', 'compare' => 'EXISTS']]);
            $result = get_posts($args);
            wp_reset_postdata();
            return $result;
        }

        $img_args               = $base_args;
        $img_args['meta_query'] = array_merge($base_meta, [['key' => '_thumbnail_id', 'compare' => 'EXISTS']]);
        $primary = get_posts($img_args);
        wp_reset_postdata();

        if (count($primary) < $count) {
            $all      = get_posts($base_args);
            wp_reset_postdata();
            $have_ids = array_map(fn($e) => $e->ID, $primary);
            $extras   = array_values(array_filter($all, fn($e) => !in_array($e->ID, $have_ids)));
            $primary  = array_merge($primary, array_slice($extras, 0, $count - count($primary)));
        }

        return array_slice($primary, 0, $count);
    }

    private function img_url(int $id): string { return get_the_post_thumbnail_url($id, 'large') ?: ''; }

    /** Kategória szín az abe_kat_szin term meta-ból */
    private function event_color(int $id, string $fb): string {
        $terms = get_the_terms($id, 'abe_kategoria');
        if ($terms && !is_wp_error($terms)) {
            if (function_exists('abe_get_kategoria_szin')) return abe_get_kategoria_szin($terms[0]->term_id);
            $c = get_term_meta($terms[0]->term_id, 'abe_kat_szin', true);
            if ($c) return $c;
        }
        return $fb;
    }

    /** Helyszín neve – abe_helyszin_id lookup */
    private function location(int $id): string {
        $helyszin_id = (int) get_post_meta($id, 'abe_helyszin_id', true);
        if (!$helyszin_id) return '';
        $h = get_post($helyszin_id);
        if (!$h || 'publish' !== $h->post_status) return '';
        $varos = get_post_meta($helyszin_id, 'abe_h_varos', true);
        return $varos ? $h->post_title . ' – ' . $varos : $h->post_title;
    }

    /** Első abe_kategoria neve */
    private function event_type(int $id): string {
        $terms = get_the_terms($id, 'abe_kategoria');
        return ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
    }

    private function parse_date(int $ts): array {
        $m = ['jan','feb','már','ápr','máj','jún','júl','aug','szep','okt','nov','dec'];
        $d = date('Y-m-d', $ts);
        if ($d === date('Y-m-d'))                      return ['day'=>'MA',     'month'=>'', 'is_today'=>true,  'time'=>date('H:i',$ts)];
        if ($d === date('Y-m-d', strtotime('+1 day'))) return ['day'=>'HOLNAP', 'month'=>'', 'is_today'=>false, 'time'=>date('H:i',$ts)];
        return ['day'=>date('j',$ts), 'month'=>$m[(int)date('n',$ts)-1], 'is_today'=>false, 'time'=>date('H:i',$ts)];
    }

    // ═══════════════════════════════════════════════════════════════
    //  RENDER
    // ═══════════════════════════════════════════════════════════════
    protected function render(): void {
        $s = $this->get_settings_for_display();

        $title           = esc_html($s['section_title']  ?? 'Esemény ajánló');
        $lbl             = esc_html($s['section_label']  ?? 'Közelgő programok');
        $btn_lbl         = esc_html($s['btn_label']      ?? 'Részletek');
        $show_all        = ($s['show_view_all']   ?? 'yes') === 'yes';
        $all_txt         = esc_html($s['view_all_text']  ?? 'Összes esemény →');
        $all_url         = esc_url($s['view_all_url']['url'] ?? home_url('/naptar/'));
        $layout          = $s['grid_layout']      ?? 'asymmetric';
        $count           = max(2, (int)($s['posts_count']  ?? 5));
        $weeks           = max(1, (int)($s['weeks_ahead']  ?? 4));
        $cat_slugs       = array_filter((array)($s['filter_category'] ?? []));
        $only_img        = ($s['only_with_image'] ?? 'no') === 'yes';
        $location_filter = $s['location_filter']  ?? 'balatoni';
        $show_exc_f      = ($s['show_excerpt_featured'] ?? 'yes') === 'yes';
        $exc_w_f         = (int)($s['excerpt_words_featured'] ?? 18);
        $show_exc_s      = ($s['show_excerpt_small'] ?? 'no') === 'yes';
        $exc_w_s         = (int)($s['excerpt_words_small'] ?? 10);
        $show_time       = ($s['show_time']       ?? 'yes') === 'yes';
        $show_loc        = ($s['show_location']   ?? 'yes') === 'yes';
        $show_tb         = ($s['show_type_badge'] ?? 'yes') === 'yes';
        $show_db         = ($s['show_date_badge'] ?? 'yes') === 'yes';
        $show_save       = ($s['show_save_btn']   ?? 'yes') === 'yes';
        $accent          = esc_attr($s['accent_color'] ?? '#1A6EA3');
        $shadow          = $this->shadow_css($s['card_shadow'] ?? 'soft');
        $grid_cls        = $this->grid_class($layout);
        $feat_auto       = ($s['featured_height_mode'] ?? 'auto') === 'auto';
        $img_fit         = $s['img_fit_mode']     ?? 'backdrop';
        $bd_style        = $s['backdrop_style']   ?? 'frosted';

        $events = $this->fetch_events($count, $weeks, $cat_slugs, $only_img, $location_filter);

        if (empty($events)) { ?>
            <div class="ab-grid-empty"><span>📅</span><p>Jelenleg nincs közelgő esemény a megadott feltételekkel.</p></div>
            <?php return;
        }
        ?>

        <div class="ab-events-grid-wrapper"
             style="--ab-grid-accent:<?php echo $accent; ?>; --ab-card-shadow:<?php echo esc_attr($shadow); ?>;">

            <div class="ab-grid-header">
                <div>
                    <?php if ($lbl)   echo '<div class="ab-grid-section-label">'  . $lbl   . '</div>'; ?>
                    <?php if ($title) echo '<h2 class="ab-grid-section-title">'   . $title . '</h2>'; ?>
                </div>
                <?php if ($show_all && $all_url) : ?>
                    <a href="<?php echo $all_url; ?>" class="ab-grid-view-all"><?php echo $all_txt; ?></a>
                <?php endif; ?>
            </div>

            <div class="ab-events-grid <?php echo esc_attr($grid_cls); ?><?php echo $feat_auto ? ' ab-feat-auto' : ''; ?><?php echo $img_fit === 'backdrop' ? ' ab-fit-backdrop' : ''; ?><?php echo $img_fit === 'white' ? ' ab-fit-white' : ''; ?><?php echo ($img_fit === 'backdrop' && $bd_style === 'dark') ? ' ab-backdrop-dark' : ''; ?>">

            <?php foreach ($events as $i => $event) :
                $kezdo_d = get_post_meta($event->ID, 'abe_kezdo_datum', true);
                $kezdo_i = get_post_meta($event->ID, 'abe_kezdo_ido',   true) ?: '00:00';
                $ts      = $kezdo_d ? strtotime($kezdo_d . ' ' . $kezdo_i) : 0;
                $img     = $this->img_url($event->ID);
                $color   = $this->event_color($event->ID, $accent);
                $loc     = $this->location($event->ID);
                $type    = $this->event_type($event->ID);
                $date    = $this->parse_date($ts);
                $url     = esc_url(get_permalink($event->ID));
                $is_feat = ($i === 0 && $layout === 'asymmetric');
                $raw_exc = $event->post_excerpt ?: wp_trim_words(strip_tags($event->post_content), 30);
                $exc_f   = wp_trim_words($raw_exc, $exc_w_f);
                $exc_s   = wp_trim_words($raw_exc, $exc_w_s);
                $card_cls = $is_feat ? 'ab-event-card ab-event-card--featured' : 'ab-event-card';
            ?>

                <article class="<?php echo $card_cls; ?>" style="--event-color:<?php echo esc_attr($color); ?>;">

                    <a href="<?php echo $url; ?>" class="ab-card-image" tabindex="-1" aria-hidden="true">
                        <?php if ($img) : ?>
                            <?php if ($img_fit === 'backdrop') : ?>
                                <div class="ab-img-backdrop" style="background-image:url('<?php echo esc_url($img); ?>');"></div>
                                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($event->post_title); ?>" class="ab-img-main" loading="<?php echo $i < 2 ? 'eager' : 'lazy'; ?>">
                            <?php else : ?>
                                <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($event->post_title); ?>" loading="<?php echo $i < 2 ? 'eager' : 'lazy'; ?>">
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="ab-card-image-placeholder"><?php echo $is_feat ? '🎭' : '📅'; ?></div>
                        <?php endif; ?>

                        <div class="ab-card-overlay">
                            <div class="ab-overlay-left">
                                <?php if ($show_tb && $type) : ?><span class="ab-badge ab-badge-type"><?php echo esc_html($type); ?></span><?php endif; ?>
                                <?php if ($date['is_today']) : ?><span class="ab-badge ab-badge-today">🔴 Ma</span><?php endif; ?>
                            </div>
                            <?php if ($show_db) : ?>
                                <div class="ab-date-badge">
                                    <span class="ab-date-day <?php echo empty($date['month']) ? 'ab-date-day--text' : ''; ?>"><?php echo esc_html($date['day']); ?></span>
                                    <?php if ($date['month']) : ?><span class="ab-date-month"><?php echo esc_html($date['month']); ?></span><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>

                    <div class="ab-card-body">
                        <?php if ($show_time || $show_loc) : ?>
                            <div class="ab-card-meta">
                                <?php if ($show_time && !empty($date['time']) && $date['time'] !== '00:00') : ?><span class="ab-card-time">🕐 <?php echo esc_html($date['time']); ?></span><?php endif; ?>
                                <?php if ($show_loc && $loc) : ?><span class="ab-card-loc">📍 <?php echo esc_html($loc); ?></span><?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <h3 class="ab-card-title"><a href="<?php echo $url; ?>"><?php echo esc_html($event->post_title); ?></a></h3>

                        <?php if ($is_feat && $show_exc_f && $exc_f) : ?>
                            <p class="ab-card-excerpt"><?php echo esc_html($exc_f); ?></p>
                        <?php elseif (!$is_feat && $show_exc_s && $exc_s) : ?>
                            <p class="ab-card-excerpt"><?php echo esc_html($exc_s); ?></p>
                        <?php endif; ?>

                        <div class="ab-card-footer">
                            <a href="<?php echo $url; ?>" class="ab-card-cta"><?php echo $btn_lbl; ?><span class="ab-cta-arrow">→</span></a>
                            <?php if ($show_save) : ?><span class="ab-card-save" title="Mentés" role="button">🔖</span><?php endif; ?>
                        </div>
                    </div>

                </article>

            <?php endforeach; ?>

            </div>

        </div>

    <?php }
}
