<?php
namespace AktivBalaton;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * AB Kereső Widget  v1.0.0
 *
 * Önálló, bárhova helyezhető keresősáv. Beküldéskor az eseménylista
 * oldalra navigál ?kereses=... paraméterrel – sima GET <form>, így
 * JavaScript nélkül is működik és akadálymentes. A frontend.js
 * (ab-esemenyek) automatikusan beolvassa a ?kereses= paramétert és szűr.
 */
class Search_Widget extends Widget_Base {

    public function get_name(): string      { return 'ab_search'; }
    public function get_title(): string     { return 'AB – Kereső'; }
    public function get_icon(): string      { return 'eicon-search'; }
    public function get_categories(): array { return ['general']; }
    public function get_keywords(): array {
        return ['kereső', 'keresés', 'search', 'esemény', 'balaton'];
    }

    /** Alap cél-URL: a beállított eseménylista oldal, vagy fallback. */
    private function default_base_url(): string {
        $opt = get_option('abe_lista_oldal_url');
        return $opt ?: home_url('/aktivbalaton-events/');
    }

    // ----------------------------------------------------------------
    //  KONTROLOK
    // ----------------------------------------------------------------
    protected function register_controls(): void {

        $this->start_controls_section('section_content', [
            'label' => 'Tartalom',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('section_label', [
            'label'       => 'Kis felirat (a mező fölött)',
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'pl. Keresel valamit?',
        ]);

        $this->add_control('section_title', [
            'label'       => 'Cím',
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'pl. Találd meg a programod',
        ]);

        $this->add_control('placeholder', [
            'label'   => 'Mező segédszöveg (placeholder)',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Esemény neve, helyszín vagy kulcsszó…',
        ]);

        $this->add_control('button_text', [
            'label'   => 'Gomb szövege',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Keresés',
        ]);

        $this->add_control('show_button', [
            'label'        => 'Gomb megjelenítése',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Igen',
            'label_off'    => 'Nem',
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('base_url', [
            'label'       => 'Cél oldal (eseménylista)',
            'type'        => Controls_Manager::URL,
            'placeholder' => $this->default_base_url(),
            'default'     => ['url' => $this->default_base_url()],
            'description' => 'A kereső erre az oldalra visz ?kereses=... paraméterrel. '
                           . 'Ennek az oldalnak tartalmaznia kell az [abe_esemenyek_filter] szűrőt '
                           . '(a frontend.js automatikusan beolvas és szűr betöltéskor).',
        ]);

        $this->end_controls_section();

        // ── Design ──────────────────────────────────────────────────────────
        $this->start_controls_section('section_style', [
            'label' => 'Design',
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('align', [
            'label'   => 'Igazítás',
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => ['title' => 'Balra',  'icon' => 'eicon-text-align-left'],
                'center'     => ['title' => 'Közép',  'icon' => 'eicon-text-align-center'],
                'flex-end'   => ['title' => 'Jobbra', 'icon' => 'eicon-text-align-right'],
            ],
            'default'   => 'center',
            'selectors' => [
                '{{WRAPPER}} .ab-search-wrapper' => 'align-items: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('max_width', [
            'label'      => 'Max. szélesség',
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => [
                'px' => ['min' => 280, 'max' => 900],
                '%'  => ['min' => 30,  'max' => 100],
            ],
            'default'    => ['unit' => 'px', 'size' => 560],
            'selectors'  => [
                '{{WRAPPER}} .ab-search-form' => 'max-width: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    // ----------------------------------------------------------------
    //  RENDER
    // ----------------------------------------------------------------
    protected function render(): void {
        $s = $this->get_settings_for_display();

        $label       = $s['section_label'] ?? '';
        $title       = $s['section_title']  ?? '';
        $placeholder = $s['placeholder']    ?? 'Esemény neve, helyszín vagy kulcsszó…';
        $button_text = $s['button_text']    ?? 'Keresés';
        $show_button = ($s['show_button'] ?? 'yes') === 'yes';
        $base_url    = $s['base_url']['url'] ?? '';
        if (!$base_url) $base_url = $this->default_base_url();

        // Nagyító ikon (inline SVG – nincs Font Awesome függőség)
        $icon = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" '
              . 'stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
              . '<circle cx="11" cy="11" r="7"></circle>'
              . '<line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';
        ?>
        <div class="ab-search-wrapper">

            <?php if ($label) : ?>
                <div class="ab-search-section-label"><?php echo esc_html($label); ?></div>
            <?php endif; ?>

            <?php if ($title) : ?>
                <h2 class="ab-search-section-title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>

            <form class="ab-search-form" role="search" method="get"
                  action="<?php echo esc_url($base_url); ?>">
                <span class="ab-search-field-icon"><?php echo $icon; ?></span>
                <input type="search" name="kereses" class="ab-search-input"
                       placeholder="<?php echo esc_attr($placeholder); ?>"
                       aria-label="<?php echo esc_attr($placeholder); ?>" />
                <?php if ($show_button) : ?>
                    <button type="submit" class="ab-search-btn">
                        <?php echo $icon; ?>
                        <span><?php echo esc_html($button_text); ?></span>
                    </button>
                <?php endif; ?>
            </form>

        </div><!-- /.ab-search-wrapper -->
        <?php
    }
}
