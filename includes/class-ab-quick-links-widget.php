<?php
namespace AktivBalaton;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

defined('ABSPATH') || exit;

/**
 * AB Gyors linkek Widget  v1.0.0
 *
 * Nagyobb CTA-gombok sora a SEO landing oldalakra (pl. „Balatoni programok ma",
 * „Hétvégi programok"). A gombok valódi <a href> linkek → a Google bejárja,
 * és belső linkként SEO-erőt adnak át a landing oldalaknak.
 *
 * A gombokat repeater-rel lehet szerkeszteni (felirat + URL + ikon + szín),
 * alapból 4 gomb előre kitöltve a javasolt landing slugokkal. Üres URL-ű gomb
 * nem renderelődik (nincs törött link).
 */
class Quick_Links_Widget extends Widget_Base {

    public function get_name(): string      { return 'ab_quick_links'; }
    public function get_title(): string     { return 'AB – Gyors linkek'; }
    public function get_icon(): string      { return 'eicon-flash'; }
    public function get_categories(): array { return ['general']; }
    public function get_keywords(): array {
        return ['gyors', 'linkek', 'gomb', 'cta', 'programok', 'ma', 'hétvége', 'balaton'];
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

        $repeater->add_control('button_url', [
            'label'       => 'Link (landing oldal)',
            'type'        => Controls_Manager::URL,
            'placeholder' => home_url('/balatoni-programok-ma/'),
            'default'     => ['url' => ''],
        ]);

        $repeater->add_control('button_icon', [
            'label'       => 'Ikon (emoji)',
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => '🌅',
        ]);

        $repeater->add_control('button_style', [
            'label'   => 'Stílus',
            'type'    => Controls_Manager::SELECT,
            'default' => 'outline',
            'options' => [
                'outline'   => 'Körvonalas (navy)',
                'primary'   => 'Elsődleges (narancs)',
                'secondary' => 'Másodlagos (navy kitöltött)',
            ],
        ]);

        $this->add_control('buttons', [
            'label'       => 'Gombok',
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'title_field' => '{{{ button_text }}}',
            'default'     => [
                [
                    'button_text'  => 'Programok ma',
                    'button_url'   => ['url' => home_url('/balatoni-programok-ma/')],
                    'button_icon'  => '🌅',
                    'button_style' => 'outline',
                ],
                [
                    'button_text'  => 'Hétvégi programok',
                    'button_url'   => ['url' => home_url('/hetvegi-programok-balaton/')],
                    'button_icon'  => '🎉',
                    'button_style' => 'outline',
                ],
                [
                    'button_text'  => 'Következő 7 nap',
                    'button_url'   => ['url' => home_url('/balatoni-programok-7-nap/')],
                    'button_icon'  => '📆',
                    'button_style' => 'outline',
                ],
                [
                    'button_text'  => 'Következő 30 nap',
                    'button_url'   => ['url' => home_url('/balatoni-programok-30-nap/')],
                    'button_icon'  => '🗓️',
                    'button_style' => 'outline',
                ],
            ],
        ]);

        $this->end_controls_section();

        // ── Design ───────────────────────────────────────────────────────────
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
                '{{WRAPPER}} .ab-quicklinks-row' => 'justify-content: {{VALUE}};',
                '{{WRAPPER}} .ab-quicklinks-wrapper' => 'text-align: {{VALUE}};',
            ],
        ]);

        $this->add_control('full_width_mobile', [
            'label'        => 'Teljes szélességű gombok mobilon',
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => 'Igen',
            'label_off'    => 'Nem',
            'return_value' => 'yes',
            'default'      => 'no',
        ]);

        $this->end_controls_section();
    }

    // ----------------------------------------------------------------
    //  RENDER
    // ----------------------------------------------------------------
    protected function render(): void {
        $s = $this->get_settings_for_display();

        $label   = $s['section_label'] ?? '';
        $title   = $s['section_title']  ?? '';
        $buttons = $s['buttons'] ?? [];
        $fw      = ($s['full_width_mobile'] ?? 'no') === 'yes';

        if (empty($buttons) || ! is_array($buttons)) {
            return;
        }

        $valid_styles = ['outline', 'primary', 'secondary'];

        $row_class = 'ab-quicklinks-row' . ($fw ? ' ab-quicklinks-row--fw' : '');
        ?>
        <div class="ab-quicklinks-wrapper">

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
                    $text  = $btn['button_text']  ?? '';
                    $icon  = $btn['button_icon']  ?? '';
                    $style = $btn['button_style'] ?? 'outline';
                    if (! in_array($style, $valid_styles, true)) {
                        $style = 'outline';
                    }

                    // Külső link célzás (Elementor URL kontroll: is_external / nofollow)
                    $target = ! empty($btn['button_url']['is_external']) ? ' target="_blank"' : '';
                    $rel    = ! empty($btn['button_url']['nofollow'])    ? ' rel="nofollow"'   : '';
                ?>
                    <a href="<?php echo esc_url($url); ?>"
                       class="ab-quicklink ab-quicklink--<?php echo esc_attr($style); ?>"<?php echo $target . $rel; ?>>
                        <?php if ($icon) : ?>
                            <span class="ab-quicklink-icon" aria-hidden="true"><?php echo esc_html($icon); ?></span>
                        <?php endif; ?>
                        <span class="ab-quicklink-label"><?php echo esc_html($text); ?></span>
                        <span class="ab-quicklink-arrow" aria-hidden="true">→</span>
                    </a>
                <?php endforeach; ?>

            </div><!-- /.ab-quicklinks-row -->

        </div><!-- /.ab-quicklinks-wrapper -->
        <?php
    }
}
