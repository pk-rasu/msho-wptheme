<?php
/**
 * WhiteKurti — Interactive Hero Section v3 "RANG"
 * Full-bleed Indian luxury fashion hero with:
 * - Rich jewel-tone gradient slides
 * - Animated mandala/paisley ornamental SVG backgrounds
 * - Floating embroidery-motif particles
 * - Touch ripple with golden shimmer
 * - Gyroscope + mouse parallax
 * - Smooth crossfade transitions with text entrance animations
 * - Mobile-first, swipe-enabled
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function wk_render_interactive_hero() {
    $shop_url   = class_exists('WooCommerce') ? get_permalink(wc_get_page_id('shop')) : home_url('/shop');
    $sets_url   = home_url('/product-category/kurta-sets/');
    $new_url    = $shop_url . '?orderby=date';
    $fest_url   = home_url('/product-category/suits/');

    $slides = [
        [
            'eyebrow'   => 'New Collection 2025',
            'heading'   => "Elegance in\nEvery Thread",
            'subhead'   => 'Handcrafted kurtis for the modern Indian woman',
            'cta_label' => 'Shop New Arrivals',
            'cta_url'   => $new_url,
            'badge'     => 'FREE Delivery',
            'theme'     => 'maroon',  // deep maroon / burgundy
            'pattern'   => 'mandala',
        ],
        [
            'eyebrow'   => 'Festival Collection',
            'heading'   => "Celebrate in\nTimeless Style",
            'subhead'   => 'Exclusive festive kurtis — free delivery on all orders',
            'cta_label' => 'Explore Festive Wear',
            'cta_url'   => $fest_url,
            'badge'     => '13% OFF',
            'theme'     => 'teal',    // deep teal / peacock
            'pattern'   => 'paisley',
        ],
        [
            'eyebrow'   => 'Everyday Elegance',
            'heading'   => "From Pooja to\nBoardroom",
            'subhead'   => 'Premium cotton & silk kurtis for every occasion',
            'cta_label' => 'Shop Kurta Sets',
            'cta_url'   => $sets_url,
            'badge'     => 'New In',
            'theme'     => 'indigo',  // deep indigo / royal blue
            'pattern'   => 'jali',
        ],
    ];

    $total = count($slides);
    ?>
    <section class="wk-hero" id="wk-hero" aria-label="Featured collections" role="banner" tabindex="0">

        <canvas class="wk-hero__particle-canvas" id="wk-hero-canvas" aria-hidden="true"></canvas>

        <div class="wk-hero__track" id="wk-hero-track">
        <?php foreach ($slides as $idx => $s) :
            $is_active = $idx === 0;
        ?>
            <div class="wk-hero__slide wk-hero__slide--<?php echo esc_attr($s['theme']); ?><?php echo $is_active ? ' is-active' : ''; ?>"
                 data-index="<?php echo $idx; ?>"
                 aria-hidden="<?php echo $is_active ? 'false' : 'true'; ?>">

                <!-- Ornamental background pattern -->
                <div class="wk-hero__ornament" aria-hidden="true">
                    <?php if ($s['pattern'] === 'mandala') : ?>
                    <svg class="wk-hero__mandala" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                        <g opacity=".13" fill="none" stroke="white" stroke-width="1">
                        <circle cx="300" cy="300" r="260"/><circle cx="300" cy="300" r="220"/><circle cx="300" cy="300" r="180"/>
                        <circle cx="300" cy="300" r="140"/><circle cx="300" cy="300" r="100"/><circle cx="300" cy="300" r="60"/>
                        <?php for($p=0;$p<16;$p++):
                            $a = $p * 22.5 * M_PI / 180;
                            $x1 = 300 + 60*cos($a); $y1 = 300 + 60*sin($a);
                            $x2 = 300 + 260*cos($a); $y2 = 300 + 260*sin($a);
                            $px = 300 + 160*cos($a); $py = 300 + 160*sin($a);
                        ?>
                        <line x1="<?php echo round($x1,1); ?>" y1="<?php echo round($y1,1); ?>" x2="<?php echo round($x2,1); ?>" y2="<?php echo round($y2,1); ?>"/>
                        <ellipse cx="<?php echo round($px,1); ?>" cy="<?php echo round($py,1); ?>" rx="12" ry="28" transform="rotate(<?php echo $p*22.5; ?> <?php echo round($px,1); ?> <?php echo round($py,1); ?>)"/>
                        <?php endfor; ?>
                        <?php for($p=0;$p<8;$p++):
                            $a = $p * 45 * M_PI / 180;
                            $px = 300 + 220*cos($a); $py = 300 + 220*sin($a);
                        ?>
                        <circle cx="<?php echo round($px,1); ?>" cy="<?php echo round($py,1); ?>" r="16"/>
                        <?php endfor; ?>
                        <circle cx="300" cy="300" r="28" fill="white" opacity=".08"/>
                        <circle cx="300" cy="300" r="14" fill="white" opacity=".12"/>
                        </g>
                    </svg>
                    <?php elseif ($s['pattern'] === 'paisley') : ?>
                    <svg class="wk-hero__mandala" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                        <g opacity=".11" fill="none" stroke="white" stroke-width="1.2">
                        <?php for($p=0;$p<6;$p++):
                            $a = $p * 60; $r = 220;
                            $cx = 300 + $r*cos($a*M_PI/180); $cy = 300 + $r*sin($a*M_PI/180);
                        ?>
                        <path d="M<?php echo round($cx,1); ?> <?php echo round($cy,1); ?> C<?php echo round($cx+40,1); ?> <?php echo round($cy-80,1); ?>, <?php echo round($cx+80,1); ?> <?php echo round($cy-40,1); ?>, <?php echo round($cx+60,1); ?> <?php echo round($cy+30,1); ?> S<?php echo round($cx-20,1); ?> <?php echo round($cy+70,1); ?>, <?php echo round($cx,1); ?> <?php echo round($cy,1); ?>Z" transform="rotate(<?php echo $a; ?> <?php echo round($cx,1); ?> <?php echo round($cy,1); ?>)"/>
                        <?php endfor; ?>
                        <circle cx="300" cy="300" r="160" stroke-dasharray="12 8"/><circle cx="300" cy="300" r="100" stroke-dasharray="8 6"/>
                        <circle cx="300" cy="300" r="50"/><circle cx="300" cy="300" r="20" fill="white" opacity=".1"/>
                        <?php for($p=0;$p<12;$p++):
                            $a = $p*30*M_PI/180; $px = 300+100*cos($a); $py = 300+100*sin($a);
                        ?><circle cx="<?php echo round($px,1); ?>" cy="<?php echo round($py,1); ?>" r="5" fill="white" opacity=".2"/><?php endfor; ?>
                        </g>
                    </svg>
                    <?php else: /* jali lattice */ ?>
                    <svg class="wk-hero__mandala" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg">
                        <g opacity=".09" stroke="white" stroke-width="1" fill="none">
                        <?php for($row=0;$row<8;$row++): for($col=0;$col<8;$col++):
                            $cx = 37.5+$col*75; $cy = 37.5+$row*75;
                        ?>
                        <rect x="<?php echo $cx-28; ?>" y="<?php echo $cy-28; ?>" width="56" height="56" rx="4" transform="rotate(45 <?php echo $cx; ?> <?php echo $cy; ?>)"/>
                        <circle cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="8"/>
                        <?php endfor; endfor; ?>
                        <circle cx="300" cy="300" r="250" stroke-width="2" stroke-dasharray="20 10"/>
                        </g>
                    </svg>
                    <?php endif; ?>
                </div>

                <!-- Floating embroidery motifs -->
                <div class="wk-hero__floats" aria-hidden="true">
                    <span class="wk-hero__float wk-hero__float--1">✦</span>
                    <span class="wk-hero__float wk-hero__float--2">◈</span>
                    <span class="wk-hero__float wk-hero__float--3">❋</span>
                    <span class="wk-hero__float wk-hero__float--4">✦</span>
                    <span class="wk-hero__float wk-hero__float--5">◆</span>
                </div>

                <!-- Gold border accent line -->
                <div class="wk-hero__gold-line" aria-hidden="true"></div>

                <!-- Content -->
                <div class="wk-hero__content" id="wk-hero-content-<?php echo $idx; ?>">

                    <p class="wk-hero__eyebrow">
                        <span class="wk-hero__eyebrow-line"></span>
                        <?php echo esc_html($s['eyebrow']); ?>
                    </p>

                    <h1 class="wk-hero__heading">
                        <?php foreach (explode("\n", $s['heading']) as $li => $line) : ?>
                        <span class="wk-hero__heading-line" style="--line-i:<?php echo $li; ?>"><?php echo esc_html($line); ?></span>
                        <?php endforeach; ?>
                    </h1>

                    <p class="wk-hero__sub"><?php echo esc_html($s['subhead']); ?></p>

                    <div class="wk-hero__actions">
                        <a href="<?php echo esc_url($s['cta_url']); ?>" class="wk-hero__cta">
                            <?php echo esc_html($s['cta_label']); ?>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="13 6 19 12 13 18"/></svg>
                        </a>
                        <span class="wk-hero__badge">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?php echo esc_html($s['badge']); ?>
                        </span>
                    </div>
                </div>

                <!-- Right decorative fabric swatch pattern -->
                <div class="wk-hero__right-deco" aria-hidden="true">
                    <div class="wk-hero__right-deco-inner">
                        <div class="wk-hero__fabric-pattern"></div>
                    </div>
                </div>

            </div><!-- /.wk-hero__slide -->
        <?php endforeach; ?>
        </div><!-- /.wk-hero__track -->

        <!-- Navigation arrows -->
        <button class="wk-hero__nav wk-hero__nav--prev" id="wk-hero-prev" type="button" aria-label="Previous slide">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button class="wk-hero__nav wk-hero__nav--next" id="wk-hero-next" type="button" aria-label="Next slide">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>

        <!-- Dot indicators -->
        <div class="wk-hero__dots" role="tablist" aria-label="Slide indicators">
            <?php for($d=0;$d<$total;$d++): ?>
            <button class="wk-hero__dot<?php echo $d===0?' is-active':''; ?>"
                    type="button" role="tab"
                    data-index="<?php echo $d; ?>"
                    aria-label="Slide <?php echo $d+1; ?>"
                    aria-selected="<?php echo $d===0?'true':'false'; ?>">
            </button>
            <?php endfor; ?>
        </div>

        <!-- Slide counter -->
        <span class="wk-hero__counter" id="wk-hero-counter" aria-live="polite">
            <span id="wk-hero-cur">1</span>/<span><?php echo $total; ?></span>
        </span>

    </section><!-- /.wk-hero -->
    <?php
}
