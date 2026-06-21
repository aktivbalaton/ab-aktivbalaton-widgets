<?php
namespace AktivBalaton;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * AB Kategória Pillek Widget  v2.0.0
 * v2.0.0: Migrálva EventON-ról ab-esemenyek pluginra
 *
 * – Taxonómia: event_type → abe_kategoria
 * – CPT: ajde_events → abe_esemeny
 * – Dátum: evcal_srow (Unix ts) → abe_kezdo_datum (YYYY-MM-DD)
 * – URL param: ?event_type_id=X → ?kategoria=slug
 * – Ikon defaults: term_id alapú → slug alapú (stabil az installok között)
 * – Deep link: frontend.js olvassa a ?kategoria= paramétert és automatikusan szűr
 */
class Category_Pills_Widget extends Widget_Base {

    public function get_name(): string    { return 'ab_category_pills'; }
    public function get_title(): string   { return 'AB – Kategória Pillek'; }
    public function get_icon(): string    { return 'eicon-tags'; }
    public function get_categories(): array { return ['general']; }
    public function get_keywords(): array {
        return ['kategória', 'szűrő', 'pill', 'gomb', 'event', 'balaton'];
    }

    // ----------------------------------------------------------------
    //  ALAPÉRTELMEZETT IKONOK ÉS SZÍNEK – SLUG ALAPÚ (v2.0.0)
    //  (Stabil az installok között, nem term_id-függő)
    // ----------------------------------------------------------------
    private function get_defaults(): array {
        return [
            'szinhaz'     => ['icon' => '🎭', 'color' => 'purple'],
            'csaladi'     => ['icon' => '👨‍👩‍👧', 'color' => 'amber'],
            'gasztronomia'=> ['icon' => '🍷', 'color' => 'amber'],
            'gyerek'      => ['icon' => '🎠', 'color' => 'rose'],
            'kiallitas'   => ['icon' => '🖼️', 'color' => 'purple'],
            'kirandulas'  => ['icon' => '🥾', 'color' => 'green'],
            'koncert'     => ['icon' => '🎵', 'color' => 'blue'],
            'kultura'     => ['icon' => '🎨', 'color' => 'purple'],
            'mozi'        => ['icon' => '🎥', 'color' => 'orange'],
            'musical'     => ['icon' => '🎼', 'color' => 'blue'],
            'programok'   => ['icon' => '🗓️', 'color' => 'blue'],
            'sport'       => ['icon' => '🏊', 'color' => 'teal'],
            'szabadido'   => ['icon' => '🌊', 'color' => 'teal'],
            'szorakozas'  => ['icon' => '🎪', 'color' => 'orange'],
            'zene'        => ['icon' => '🎵', 'color' => 'blue'],
        ];
    }

    // ----------------------------------------------------------------
    //  KONTROLOK
    // ----------------------------------------------------------------
    protected function register_controls(): void {

        $this->start_controls_section('section_content', [
            'label' => 'Tartalom',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('section_title', [
            'label'   => 'Szekció cím',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Milyen programot keresel?',
        ]);

        $this->add_control('section_label', [
            'label'   => 'Kis felirat (szekció fölött)',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Böngéssz kategóriánként',
        ]);

        $this->add_control('base_url', [
            'label'       => 'Alap URL (eseménylista oldal)',
            'type'        => Controls_Manager::URL,
            'placeholder' => home_url('/aktivbalaton-events/'),
            'default'     => ['url' => home_url('/aktivbalaton-events/')],
            'description' => 'A pill gombok erre az oldalra mutatnak ?kategoria=SLUG paraméterrel. A frontend.js automatikusan szűr betöltéskor.',
        ]);

        $this->add_control('show_count', [
            'label'        => 'Eseményszám megjelenítése',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Igen',
            'label_off'    => 'Nem',
            'return_value' => 'yes',
            'default'      => 'no',
            'description'  => 'Jövőbeli események száma az adott kategóriában (1 órás cache).',
        ]);

        $this->add_control('hide_empty', [
            'label'        => 'Üres kategóriák elrejtése',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Igen',
            'label_off'    => 'Nem',
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('icon_overrides', [
            'label'       => 'Egyéni ikonok (JSON)',
            'type'        => Controls_Manager::TEXTAREA,
            'default'     => '',
            'rows'        => 4,
            'description' => 'Opcionális. Slug alapú felülírás. Pl.: {"szinhaz":"🎸","gasztronomia":"🍴"}',
        ]);

        $this->end_controls_section();

        // Design
        $this->start_controls_section('section_style', [
            'label' => 'Design',
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('pill_size', [
            'label'   => 'Pill méret',
            'type'    => Controls_Manager::SELECT,
            'default' => 'md',
            'options' => [
                'sm' => 'Kicsi',
                'md' => 'Közepes',
                'lg' => 'Nagy',
            ],
        ]);

        $this->add_control('show_icons', [
            'label'        => 'Ikonok megjelenítése',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Igen',
            'label_off'    => 'Nem',
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();
    }

    // ----------------------------------------------------------------
    //  RENDER
    // ----------------------------------------------------------------
    protected function render(): void {
        $s = $this->get_settings_for_display();

        $title       = esc_html($s['section_title']  ?? 'Milyen programot keresel?');
        $label       = esc_html($s['section_label']  ?? 'Böngéssz kategóriánként');
        $base_url    = esc_url($s['base_url']['url'] ?? home_url('/aktivbalaton-events/'));
        $show_count  = ($s['show_count']  ?? 'no')  === 'yes';
        $hide_empty  = ($s['hide_empty']  ?? 'yes') === 'yes';
        $show_icons  = ($s['show_icons']  ?? 'yes') === 'yes';
        $pill_size   = $s['pill_size']    ?? 'md';

        // Ikon felülírások JSON-ból (slug-alapú)
        $icon_overrides = [];
        $raw_json = trim($s['icon_overrides'] ?? '');
        if ($raw_json) {
            $decoded = json_decode($raw_json, true);
            if (is_array($decoded)) $icon_overrides = $decoded;
        }

        // Kategóriák lekérése az abe_kategoria taxonómiából
        $terms = get_terms([
            'taxonomy'   => 'abe_kategoria',
            'hide_empty' => $hide_empty,
            'orderby'    => 'name',
        ]);

        if (empty($terms) || is_wp_error($terms)) {
            echo '<p class="ab-pills-empty">Nincsenek elérhető kategóriák.</p>';
            return;
        }

        $defaults    = $this->get_defaults();
        $color_cycle = ['blue', 'amber', 'green', 'purple', 'teal', 'orange', 'rose'];
        $cycle_i     = 0;

        ?>
        <div class="ab-category-pills-wrapper">

            <?php if ($label) : ?>
                <div class="ab-pills-section-label"><?php echo $label; ?></div>
            <?php endif; ?>

            <?php if ($title) : ?>
                <h2 class="ab-pills-section-title"><?php echo $title; ?></h2>
            <?php endif; ?>

            <div class="ab-pills-grid ab-pills-<?php echo esc_attr($pill_size); ?>">

                <?php foreach ($terms as $term) :
                    $slug    = $term->slug;
                    $def     = $defaults[$slug] ?? null;
                    $icon    = $icon_overrides[$slug] ?? ($def['icon']  ?? '🗓️');
                    $color   = $def['color'] ?? $color_cycle[$cycle_i % count($color_cycle)];
                    $cycle_i++;

                    // URL: ?kategoria=slug – a frontend.js olvassa és szűr betöltéskor
                    $href = esc_url(add_query_arg('kategoria', $slug, $base_url));

                    // Eseményszám (opcionális, 1 órás cache).
                    // KEZDŐÉRTÉK: a JELENLEGI hónap hátralévő része (MA → hónap utolsó napja),
                    // mert a lista nézet alapból az aktuális hónapot mutatja. A frontend.js
                    // a hónapléptetéskor AJAX-szal felülírja ezt az épp látott hónap számára.
                    $count_html = '';
                    if ($show_count) {
                        $ma_d      = date('Y-m-d');
                        $ho_utolso = date('Y-m-t'); // jelenlegi hónap utolsó napja
                        // A cache-kulcs tartalmazza a mai dátumot → naponta automatikusan frissül.
                        $cnt_key = 'abe_cat_count_' . $term->term_id . '_' . $ma_d;
                        $cnt = get_transient($cnt_key);
                        if ($cnt === false) {
                            $cnt_q = get_posts([
                                'post_type'      => 'abe_esemeny',
                                'post_status'    => 'publish',
                                'posts_per_page' => -1,
                                'fields'         => 'ids',
                                'meta_query'     => [[
                                    'key'     => 'abe_kezdo_datum',
                                    'value'   => [$ma_d, $ho_utolso],
                                    'compare' => 'BETWEEN',
                                    'type'    => 'DATE',
                                ]],
                                'tax_query' => [[
                                    'taxonomy' => 'abe_kategoria',
                                    'field'    => 'term_id',
                                    'terms'    => $term->term_id,
                                ]],
                            ]);
                            wp_reset_postdata();
                            $cnt = count($cnt_q);
                            set_transient($cnt_key, $cnt, HOUR_IN_SECONDS);
                        }
                        if ((int)$cnt > 0) {
                            $count_html = '<span class="ab-pill-count">' . (int)$cnt . '</span>';
                        }
                    }
                ?>

                    <a href="<?php echo $href; ?>"
                       class="ab-pill ab-pill-<?php echo esc_attr($color); ?>"
                       data-term-id="<?php echo esc_attr($term->term_id); ?>"
                       title="<?php echo esc_attr($term->name); ?> programok">
                        <?php if ($show_icons) : ?>
                            <span class="ab-pill-icon" aria-hidden="true"><?php echo esc_html($icon); ?></span>
                        <?php endif; ?>
                        <span class="ab-pill-label"><?php echo esc_html($term->name); ?></span>
                        <?php echo $count_html; ?>
                    </a>

                <?php endforeach; ?>

            </div><!-- /.ab-pills-grid -->

        </div><!-- /.ab-category-pills-wrapper -->
        <?php
    }
}
