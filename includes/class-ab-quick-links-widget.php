<?php
namespace AktivBalaton;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

defined('ABSPATH') || exit;

/**
 * AB Gyors linkek Widget  v2.0.0
 *
 * CTA gombsor / csempe a SEO landing oldalakra (pl. „Balatoni programok ma").
 * Valódi <a href> linkek → Google bejárja, belső linkként SEO-erőt ad.
 *
 * v2.0.0: teljes átdolgozás – natív ikon (Font Awesome + SVG), két elrendezés
 *   (gombsor / csempe), per-gomb alszöveg és egyedi szín, reszponzív oszlopszám,
 *   hover-animáció, teljes Elementor stílus-kontrollok (tipográfia, színek
 *   Normál/Hover, keret, lekerekítés, padding, árnyék, ikonméret/szín).
 */
class Quick_Links_Widget extends Widget_Base {

    public function get_name(): string      { return 'ab_quick_links'; }
    public function get_title(): string     { return 'AB – Gyors linkek'; }
    public function get_icon(): string      { return 'eicon-flash'; }
    public function get_categories(): array { return ['general']; }
    public function get_keywords(): array {
        return ['gyors', 'linkek', 'gomb', 'cta', 'csempe', 'programok', 'balaton'];
    }

    // ════════════════════════════════════════════════════════════════════════
    //  KONTROLOK
    // ════════════════════════════════════════════════════════════════════════
    protected function register_controls(): void {

        // ── TARTALOM ─────────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => 'Tartalom',
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('layout', [
            'label'   => 'Elrendezés',
            'type'    => Controls_Manager::SELECT,
            'default' => 'tile',
            'options' => [
                'tile' => 'Csempe (ikon felül + alszöveg)',
                'row'  => 'Gombsor (egy sorban)',
            ],
        ]);

        $this->add_control('section_label', [
            'label'       => 'Kis felirat (a gombok fölött)',
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'pl. Népszerű keresések',
        ]);

        $this->add_control('section_title', [
            'label'       => 'Cím',
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'pl. Mikor jössz a Balatonra?',
        ]);

        // ── Gombok repeater ──────────────────────────────────────────────────
        $repeater = new Repeater();

        $repeater->add_control('button_text', [
            'label'   => 'Gomb felirata',
            'type'    => Controls_Manager::TEXT,
            'default' => 'Gomb',
        ]);

        $repeater->add_control('button_desc', [
            'label'       => 'Alszöveg (opcionális)',
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'pl. Nézd meg a mai eseményeket',
        ]);

        $repeater->add_control('button_url', [
            'label'       => 'Link (landing oldal)',
            'type'        => Controls_Manager::URL,
            'placeholder' => home_url('/balatoni-programok-ma/'),
            'default'     => ['url' => ''],
        ]);

        $repeater->add_control('btn_icon', [
            'label'   => 'Ikon',
            'type'    => Controls_Manager::ICONS,
            'default' => ['value' => 'fas fa-calendar-day', 'library' => 'fa-solid'],
        ]);

        // Per-gomb egyedi szín (opcionális, üres = a globális stílus érvényes)
        $repeater->add_control('c_heading', [
            'label'     => 'Egyedi színek (opcionális)',
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        $repeater->add_control('c_bg', [
            'label'     => 'Háttér',
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .ab-quicklink{{CURRENT_ITEM}}' => 'background-color: {{VALUE}};'],
        ]);
        $repeater->add_control('c_text', [
            'label'     => 'Szöveg / ikon',
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .ab-quicklink{{CURRENT_ITEM}}' => 'color: {{VALUE}};'],
        ]);
        $repeater->add_control('c_border', [
            'label'     => 'Keret',
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .ab-quicklink{{CURRENT_ITEM}}' => 'border-color: {{VALUE}};'],
        ]);
        $repeater->add_control('c_bg_hover', [
            'label'     => 'Háttér (hover)',
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .ab-quicklink{{CURRENT_ITEM}}:hover' => 'background-color: {{VALUE}}; border-color: {{VALUE}};'],
        ]);
        $repeater->add_control('c_text_hover', [
            'label'     => 'Szöveg / ikon (hover)',
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .ab-quicklink{{CURRENT_ITEM}}:hover' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('buttons', [
            'label'       => 'Gombok',
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'title_field' => '{{{ button_text }}}',
            'default'     => [
                [
                    'button_text' => 'Programok ma',
                    'button_desc' => 'A mai balatoni események',
                    'button_url'  => ['url' => home_url('/balatoni-programok-ma/')],
                    'btn_icon'    => ['value' => 'fas fa-sun', 'library' => 'fa-solid'],
                ],
                [
                    'button_text' => 'Hétvégi programok',
                    'button_desc' => 'Péntektől vasárnapig',
                    'button_url'  => ['url' => home_url('/hetvegi-programok-balaton/')],
                    'btn_icon'    => ['value' => 'fas fa-umbrella-beach', 'library' => 'fa-solid'],
                ],
                [
                    'button_text' => 'Következő 7 nap',
                    'button_desc' => 'A közelgő egy hét',
                    'button_url'  => ['url' => home_url('/balatoni-programok-7-nap/')],
                    'btn_icon'    => ['value' => 'fas fa-calendar-week', 'library' => 'fa-solid'],
                ],
                [
                    'button_text' => 'Következő 30 nap',
                    'button_desc' => 'Az elkövetkező hónap',
                    'button_url'  => ['url' => home_url('/balatoni-programok-30-nap/')],
                    'btn_icon'    => ['value' => 'fas fa-calendar-days', 'library' => 'fa-solid'],
                ],
            ],
        ]);

        $this->end_controls_section();

        // ── STÍLUS: ELRENDEZÉS ───────────────────────────────────────────────
        $this->start_controls_section('section_layout_style', [
            'label' => 'Elrendezés',
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('columns', [
            'label'          => 'Oszlopok száma (csempe)',
            'type'           => Controls_Manager::SLIDER,
            'range'          => ['px' => ['min' => 1, 'max' => 6, 'step' => 1]],
            'default'        => ['size' => 4],
            'tablet_default' => ['size' => 2],
            'mobile_default' => ['size' => 1],
            'selectors'      => [
                '{{WRAPPER}} .ab-quicklinks--tile .ab-quicklinks-row' => 'grid-template-columns: repeat({{SIZE}}, 1fr);',
            ],
            'condition'      => ['layout' => 'tile'],
        ]);

        $this->add_responsive_control('item_gap', [
            'label'     => 'Térköz a gombok közt',
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 60]],
            'default'   => ['size' => 16, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .ab-quicklinks-row' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('align', [
            'label'     => 'Igazítás (gombsor)',
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => ['title' => 'Balra',  'icon' => 'eicon-text-align-left'],
                'center'     => ['title' => 'Közép',  'icon' => 'eicon-text-align-center'],
                'flex-end'   => ['title' => 'Jobbra', 'icon' => 'eicon-text-align-right'],
            ],
            'default'   => 'center',
            'selectors' => ['{{WRAPPER}} .ab-quicklinks--row .ab-quicklinks-row' => 'justify-content: {{VALUE}};'],
            'condition' => ['layout' => 'row'],
        ]);

        $this->add_control('full_width_mobile', [
            'label'        => 'Teljes szélességű gombok mobilon',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Igen',
            'label_off'    => 'Nem',
            'return_value' => 'yes',
            'default'      => 'no',
            'condition'    => ['layout' => 'row'],
        ]);

        $this->add_control('hover_anim', [
            'label'   => 'Hover animáció',
            'type'    => Controls_Manager::SELECT,
            'default' => 'lift',
            'options' => [
                'none'  => 'Nincs',
                'lift'  => 'Emelés',
                'zoom'  => 'Nagyítás',
                'arrow' => 'Csak nyíl (gombsor)',
            ],
        ]);

        $this->end_controls_section();

        // ── STÍLUS: FEJLÉC ───────────────────────────────────────────────────
        $this->start_controls_section('section_heading_style', [
            'label' => 'Fejléc (felirat + cím)',
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('heading_align', [
            'label'     => 'Igazítás',
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'left'   => ['title' => 'Balra',  'icon' => 'eicon-text-align-left'],
                'center' => ['title' => 'Közép',  'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => 'Jobbra', 'icon' => 'eicon-text-align-right'],
            ],
            'default'   => 'center',
            'selectors' => ['{{WRAPPER}} .ab-quicklinks-wrapper' => 'text-align: {{VALUE}};'],
        ]);

        $this->add_control('label_color', [
            'label'     => 'Kis felirat színe',
            'type'      => Controls_Manager::COLOR,
            'default'   => '#1A6EA3',
            'selectors' => ['{{WRAPPER}} .ab-quicklinks-section-label' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'label_typography',
            'selector' => '{{WRAPPER}} .ab-quicklinks-section-label',
        ]);

        $this->add_control('title_color', [
            'label'     => 'Cím színe',
            'type'      => Controls_Manager::COLOR,
            'default'   => '#1C2B3A',
            'selectors' => ['{{WRAPPER}} .ab-quicklinks-section-title' => 'color: {{VALUE}};'],
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'selector' => '{{WRAPPER}} .ab-quicklinks-section-title',
        ]);

        $this->end_controls_section();

        // ── STÍLUS: GOMB / CSEMPE DOBOZ ──────────────────────────────────────
        $this->start_controls_section('section_box_style', [
            'label' => 'Gomb / Csempe',
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('box_padding', [
            'label'      => 'Belső margó (padding)',
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => 18, 'right' => 22, 'bottom' => 18, 'left' => 22, 'unit' => 'px', 'isLinked' => false],
            'selectors'  => ['{{WRAPPER}} .ab-quicklink' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('box_radius', [
            'label'      => 'Lekerekítés',
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'default'    => ['top' => 12, 'right' => 12, 'bottom' => 12, 'left' => 12, 'unit' => 'px', 'isLinked' => true],
            'selectors'  => ['{{WRAPPER}} .ab-quicklink' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('icon_gap', [
            'label'     => 'Ikon–szöveg távolság',
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 40]],
            'default'   => ['size' => 10, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .ab-quicklink' => 'gap: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'           => 'box_border',
            'selector'       => '{{WRAPPER}} .ab-quicklink',
            'fields_options' => [
                'border' => ['default' => 'solid'],
                'width'  => ['default' => ['top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px', 'isLinked' => true]],
                'color'  => ['default' => '#1B2D3F'],
            ],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'box_shadow',
            'selector' => '{{WRAPPER}} .ab-quicklink',
        ]);

        // Háttér + szöveg Normál / Hover tabokkal
        $this->start_controls_tabs('box_color_tabs');

        $this->start_controls_tab('box_tab_normal', ['label' => 'Normál']);
        $this->add_control('box_bg', [
            'label'     => 'Háttér',
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(0,0,0,0)',
            'selectors' => ['{{WRAPPER}} .ab-quicklink' => 'background-color: {{VALUE}};'],
        ]);
        $this->add_control('box_text', [
            'label'     => 'Szöveg / ikon',
            'type'      => Controls_Manager::COLOR,
            'default'   => '#1B2D3F',
            'selectors' => ['{{WRAPPER}} .ab-quicklink' => 'color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('box_tab_hover', ['label' => 'Hover']);
        $this->add_control('box_bg_hover', [
            'label'     => 'Háttér',
            'type'      => Controls_Manager::COLOR,
            'default'   => '#E8943A',
            'selectors' => ['{{WRAPPER}} .ab-quicklink:hover' => 'background-color: {{VALUE}};'],
        ]);
        $this->add_control('box_text_hover', [
            'label'     => 'Szöveg / ikon',
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .ab-quicklink:hover' => 'color: {{VALUE}};'],
        ]);
        $this->add_control('box_border_hover', [
            'label'     => 'Keret',
            'type'      => Controls_Manager::COLOR,
            'default'   => '#E8943A',
            'selectors' => ['{{WRAPPER}} .ab-quicklink:hover' => 'border-color: {{VALUE}};'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // ── STÍLUS: IKON ─────────────────────────────────────────────────────
        $this->start_controls_section('section_icon_style', [
            'label' => 'Ikon',
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('icon_size', [
            'label'     => 'Ikonméret',
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 12, 'max' => 80]],
            'default'   => ['size' => 30, 'unit' => 'px'],
            'selectors' => [
                '{{WRAPPER}} .ab-quicklink-icon'     => 'font-size: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .ab-quicklink-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('icon_color_custom', [
            'label'       => 'Ikon önálló színe (opcionális)',
            'type'        => Controls_Manager::COLOR,
            'description' => 'Ha üres, az ikon a szöveg színét veszi fel.',
            'selectors'   => [
                '{{WRAPPER}} .ab-quicklink-icon'     => 'color: {{VALUE}};',
                '{{WRAPPER}} .ab-quicklink-icon svg' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();

        // ── STÍLUS: SZÖVEG (felirat + alszöveg) ──────────────────────────────
        $this->start_controls_section('section_text_style', [
            'label' => 'Szöveg',
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'btn_label_typography',
            'label'    => 'Felirat betűtípus',
            'selector' => '{{WRAPPER}} .ab-quicklink-label',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'btn_desc_typography',
            'label'    => 'Alszöveg betűtípus',
            'selector' => '{{WRAPPER}} .ab-quicklink-desc',
        ]);

        $this->add_control('desc_color', [
            'label'     => 'Alszöveg színe (opcionális)',
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .ab-quicklink-desc' => 'color: {{VALUE}};'],
        ]);

        $this->end_controls_section();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  RENDER
    // ════════════════════════════════════════════════════════════════════════
    protected function render(): void {
        $s = $this->get_settings_for_display();

        $label   = $s['section_label'] ?? '';
        $title   = $s['section_title'] ?? '';
        $buttons = $s['buttons'] ?? [];
        $layout  = in_array(($s['layout'] ?? 'tile'), ['tile', 'row'], true) ? $s['layout'] : 'tile';
        $anim    = in_array(($s['hover_anim'] ?? 'lift'), ['none', 'lift', 'zoom', 'arrow'], true) ? $s['hover_anim'] : 'lift';
        $fw      = ($s['full_width_mobile'] ?? 'no') === 'yes';

        if (empty($buttons) || ! is_array($buttons)) {
            return;
        }

        $wrap_class = 'ab-quicklinks-wrapper ab-quicklinks--' . $layout . ' ab-quicklinks--anim-' . $anim;
        $row_class  = 'ab-quicklinks-row' . ($layout === 'row' && $fw ? ' ab-quicklinks-row--fw' : '');
        ?>
        <div class="<?php echo esc_attr($wrap_class); ?>">

            <?php if ($label) : ?>
                <div class="ab-quicklinks-section-label"><?php echo esc_html($label); ?></div>
            <?php endif; ?>

            <?php if ($title) : ?>
                <h2 class="ab-quicklinks-section-title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>

            <div class="<?php echo esc_attr($row_class); ?>">

                <?php foreach ($buttons as $btn) :
                    $url = $btn['button_url']['url'] ?? '';
                    if (! $url) {
                        continue; // üres URL → nincs törött link
                    }
                    $text = $btn['button_text'] ?? '';
                    $desc = $btn['button_desc'] ?? '';
                    $iid  = $btn['_id'] ?? '';

                    $target = ! empty($btn['button_url']['is_external']) ? ' target="_blank"' : '';
                    $rel    = ! empty($btn['button_url']['nofollow'])    ? ' rel="nofollow"'   : '';

                    // Natív ikon (Font Awesome / SVG)
                    $icon_html = '';
                    if (! empty($btn['btn_icon']['value'])) {
                        ob_start();
                        Icons_Manager::render_icon($btn['btn_icon'], ['aria-hidden' => 'true']);
                        $icon_html = ob_get_clean();
                    }

                    $aria = $text ? '' : ' aria-label="' . esc_attr(wp_strip_all_tags($desc) ?: $url) . '"';
                    $item_class = 'ab-quicklink' . ($iid ? ' elementor-repeater-item-' . $iid : '');
                ?>
                    <a href="<?php echo esc_url($url); ?>"
                       class="<?php echo esc_attr($item_class); ?>"<?php echo $target . $rel . $aria; ?>>
                        <?php if ($icon_html) : ?>
                            <span class="ab-quicklink-icon"><?php echo $icon_html; ?></span>
                        <?php endif; ?>
                        <span class="ab-quicklink-text">
                            <?php if ($text) : ?>
                                <span class="ab-quicklink-label"><?php echo esc_html($text); ?></span>
                            <?php endif; ?>
                            <?php if ($desc) : ?>
                                <span class="ab-quicklink-desc"><?php echo esc_html($desc); ?></span>
                            <?php endif; ?>
                        </span>
                        <?php if ($layout === 'row') : ?>
                            <span class="ab-quicklink-arrow" aria-hidden="true">→</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>

            </div><!-- /.ab-quicklinks-row -->

        </div><!-- /.ab-quicklinks-wrapper -->
        <?php
    }
}
