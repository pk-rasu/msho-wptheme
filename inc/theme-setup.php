<?php
/**
 * WhiteKurti — Theme Auto-Setup
 * Creates demo pages, menus, products, and settings on theme activation.
 * This ensures the theme looks complete immediately after install.
 */
defined( 'ABSPATH' ) || exit;

/**
 * Run silently on admin init — creates all demo content automatically.
 */
function wk_auto_setup_handler() {
    // 1. Base WordPress Setup (Pages, Menus, Reading Settings)
    if ( ! get_option( 'wk_base_setup_complete' ) ) {
        wk_create_demo_pages();
        wk_create_demo_menus();
        wk_configure_reading_settings();
        update_option( 'wk_base_setup_complete', true );
    }

    // 2. WooCommerce Setup (Products, Categories, Pages)
    if ( class_exists( 'WooCommerce' ) && ! get_option( 'wk_woo_setup_complete' ) ) {
        WC_Install::create_pages();
        wk_create_demo_categories();
        wk_create_demo_products();
        flush_rewrite_rules();
        update_option( 'wk_woo_setup_complete', true );
    }
}
add_action( 'admin_init', 'wk_auto_setup_handler' );

/**
 * Create all required WordPress pages with demo content.
 */
function wk_create_demo_pages() {
    $pages = [
        'about' => [
            'title'    => 'About Us',
            'template' => 'template-about.php',
            'content'  => '<h2>Our Story</h2>
<p>Born from a passion for timeless Indian craftsmanship, WhiteKurti was founded with a singular vision: to create ethnic wear that transcends seasons and trends.</p>
<p>Every piece in our collection is a labor of love — from hand-selecting premium fabrics to working with skilled artisans who have honed their craft over generations. We believe that true luxury lies in simplicity, and our designs reflect this philosophy.</p>
<h2>Our Mission</h2>
<p>We are committed to making luxury ethnic wear accessible to every woman who appreciates quality, comfort, and elegance. Our collections blend traditional Indian silhouettes with contemporary aesthetics, creating pieces that are as versatile as they are beautiful.</p>
<h2>Sustainability</h2>
<p>We believe fashion should be kind to the planet. That is why we work with natural fabrics, employ ethical manufacturing practices, and are constantly working toward reducing our environmental footprint.</p>',
        ],
        'contact' => [
            'title'    => 'Contact Us',
            'template' => 'template-contact.php',
            'content'  => '',
        ],
        'returns' => [
            'title'    => 'Returns & Exchange',
            'template' => '',
            'content'  => '<h2>Return Policy</h2>
<p>We want you to love every purchase. If something doesn\'t work out, we make returns and exchanges easy.</p>
<h3>How to Return</h3>
<ul>
<li>Returns are accepted within <strong>7 days</strong> of delivery.</li>
<li>Items must be unworn, unwashed, and in original packaging with all tags attached.</li>
<li>To initiate a return, email us at <strong>support@whitekurti.com</strong> with your order number.</li>
</ul>
<h3>Exchanges</h3>
<p>We offer free exchanges for size-related issues. Simply reach out to us and we\'ll arrange a pickup.</p>
<h3>Refund Timeline</h3>
<p>Once we receive and inspect your return, your refund will be processed within <strong>5-7 business days</strong> to your original payment method.</p>',
        ],
        'shipping' => [
            'title'    => 'Shipping Policy',
            'template' => '',
            'content'  => '<h2>Shipping Information</h2>
<h3>Domestic Shipping (India)</h3>
<ul>
<li><strong>Free shipping</strong> on all orders above ₹999.</li>
<li>Standard delivery: 5-7 business days.</li>
<li>Express delivery: 2-3 business days (₹149 extra).</li>
</ul>
<h3>Cash on Delivery</h3>
<p>COD is available on all orders up to ₹5,000. A nominal fee of ₹49 applies.</p>
<h3>International Shipping</h3>
<p>We currently ship to select countries. International orders typically arrive within 10-15 business days. Customs duties may apply.</p>
<h3>Order Tracking</h3>
<p>Once your order ships, you\'ll receive a tracking link via email and SMS. You can also track your order from your account dashboard.</p>',
        ],
        'size-guide' => [
            'title'    => 'Size Guide',
            'template' => '',
            'content'  => '<h2>Find Your Perfect Fit</h2>
<p>Our garments are designed with comfort in mind. Use the guide below to find your ideal size.</p>
<table>
<thead><tr><th>Size</th><th>Bust (inches)</th><th>Waist (inches)</th><th>Hip (inches)</th></tr></thead>
<tbody>
<tr><td>XS</td><td>32</td><td>26</td><td>35</td></tr>
<tr><td>S</td><td>34</td><td>28</td><td>37</td></tr>
<tr><td>M</td><td>36</td><td>30</td><td>39</td></tr>
<tr><td>L</td><td>38</td><td>32</td><td>41</td></tr>
<tr><td>XL</td><td>40</td><td>34</td><td>43</td></tr>
<tr><td>XXL</td><td>42</td><td>36</td><td>45</td></tr>
<tr><td>3XL</td><td>44</td><td>38</td><td>47</td></tr>
</tbody>
</table>
<p><em>Tip: If you\'re between sizes, we recommend sizing up for a relaxed fit.</em></p>',
        ],
        'privacy-policy' => [
            'title'    => 'Privacy Policy',
            'template' => '',
            'content'  => '<h2>Privacy Policy</h2>
<p>Your privacy is important to us. This policy outlines how we collect, use, and protect your personal information.</p>
<h3>Information We Collect</h3>
<p>We collect information you provide directly: name, email, phone number, shipping address, and payment details when you place an order.</p>
<h3>How We Use Your Information</h3>
<ul>
<li>Process and fulfill your orders</li>
<li>Send order confirmations and shipping updates</li>
<li>Improve our website and customer experience</li>
<li>Send promotional communications (only with your consent)</li>
</ul>
<h3>Data Security</h3>
<p>We use industry-standard SSL encryption to protect your personal and payment information. We never store credit card details on our servers.</p>
<h3>Contact Us</h3>
<p>Questions about our privacy practices? Email us at <strong>privacy@whitekurti.com</strong>.</p>',
        ],
        'terms' => [
            'title'    => 'Terms & Conditions',
            'template' => '',
            'content'  => '<h2>Terms & Conditions</h2>
<p>By accessing and using the WhiteKurti website, you agree to the following terms.</p>
<h3>Orders & Payment</h3>
<p>All prices are listed in Indian Rupees (₹) and include applicable taxes. We reserve the right to cancel any order due to stock unavailability or pricing errors.</p>
<h3>Product Information</h3>
<p>We make every effort to display product colors accurately. However, slight variations may occur due to screen settings and photography lighting.</p>
<h3>Intellectual Property</h3>
<p>All content on this website — images, text, designs — is the property of WhiteKurti and is protected by copyright law.</p>
<h3>Limitation of Liability</h3>
<p>WhiteKurti shall not be liable for any indirect, incidental, or consequential damages arising from the use of our products or website.</p>',
        ],
        'sustainability' => [
            'title'    => 'Conscious Fashion',
            'template' => '',
            'content'  => '<h2>Our Commitment to Conscious Fashion</h2>
<p>At WhiteKurti, sustainability isn\'t a trend — it\'s a value woven into everything we do.</p>
<h3>Ethical Manufacturing</h3>
<p>We partner with artisan communities across India, ensuring fair wages, safe working conditions, and the preservation of traditional craft techniques.</p>
<h3>Natural Fabrics</h3>
<p>Over 80% of our collection uses natural, breathable fabrics: cotton, linen, silk, and modal. We actively minimize the use of synthetic materials.</p>
<h3>Minimal Waste</h3>
<p>Our packaging is 100% recyclable. We use eco-friendly dyes wherever possible and continuously work to reduce water usage in our production processes.</p>',
        ],
    ];

    foreach ( $pages as $slug => $page_data ) {
        // Skip if page already exists
        // WP 6.8+ compatible page existence check
        $existing_page = new WP_Query([
            'name'           => $slug,
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
            'fields'         => 'ids',
        ]);
        wp_reset_postdata();
        if ( ! empty( $existing_page->posts ) ) continue;

        $page_id = wp_insert_post( [
            'post_title'   => $page_data['title'],
            'post_name'    => $slug,
            'post_content' => $page_data['content'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );

        if ( ! is_wp_error( $page_id ) && ! empty( $page_data['template'] ) ) {
            update_post_meta( $page_id, '_wp_page_template', $page_data['template'] );
        }
    }

    // Create a static front page
    $front_q = new WP_Query(['name'=>'home','post_type'=>'page','post_status'=>'any','posts_per_page'=>1,'no_found_rows'=>true,'fields'=>'ids']);
    wp_reset_postdata();
    if ( empty( $front_q->posts ) ) {
        $front_id = wp_insert_post( [
            'post_title'   => 'Home',
            'post_name'    => 'home',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );
    }

    // Create a blog page
    $blog_q = new WP_Query(['name'=>'blog','post_type'=>'page','post_status'=>'any','posts_per_page'=>1,'no_found_rows'=>true,'fields'=>'ids']);
    wp_reset_postdata();
    if ( empty( $blog_q->posts ) ) {
        wp_insert_post( [
            'post_title'   => 'Blog',
            'post_name'    => 'blog',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );
    }
}

/**
 * Configure reading settings for a proper storefront.
 */
function wk_configure_reading_settings() {
    // WP 6.8+ compatible
    $fq = new WP_Query(['name'=>'home','post_type'=>'page','post_status'=>'publish','posts_per_page'=>1,'no_found_rows'=>true]); wp_reset_postdata();
    $front = !empty($fq->posts) ? $fq->posts[0] : null;
    $bq = new WP_Query(['name'=>'blog','post_type'=>'page','post_status'=>'publish','posts_per_page'=>1,'no_found_rows'=>true]); wp_reset_postdata();
    $blog = !empty($bq->posts) ? $bq->posts[0] : null;

    if ( $front ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $front->ID );
    }
    if ( $blog ) {
        update_option( 'page_for_posts', $blog->ID );
    }
}

/**
 * Create navigation menus with proper links.
 */
function wk_create_demo_menus() {
    // Primary / header menu — always rebuild with correct category URLs
    $primary_menu_name = 'Main Menu';
    $primary_menu      = wp_get_nav_menu_object( $primary_menu_name );
    $shop_url          = class_exists( 'WooCommerce' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url( '/shop' );

    // Build URL map using actual product category links
    $menu_items = [];

    // New Arrivals → shop sorted by date
    $menu_items['New Arrivals'] = $shop_url . '?orderby=date';

    // Each named category → its real /product-category/slug/ URL
    $cat_names = [ 'Kurta Sets', 'Suits', 'Dresses', 'Sarees', 'Co-ords' ];
    foreach ( $cat_names as $name ) {
        $slug = sanitize_title( $name );
        $term = get_term_by( 'slug', $slug, 'product_cat' );
        if ( $term && ! is_wp_error( $term ) ) {
            $cat_url = get_term_link( $term );
            $menu_items[ $term->name ] = is_wp_error( $cat_url ) ? $shop_url : $cat_url;
        } else {
            // Fallback: build URL from slug even if term doesn't exist yet
            $menu_items[ $name ] = home_url( '/product-category/' . $slug . '/' );
        }
    }

    if ( ! $primary_menu ) {
        $primary_menu_id = wp_create_nav_menu( $primary_menu_name );
    } else {
        $primary_menu_id = $primary_menu->term_id;
        // Clear existing items that have wrong URLs
        $existing = wp_get_nav_menu_items( $primary_menu_id );
        if ( $existing ) {
            $shop_base = trailingslashit( $shop_url );
            foreach ( $existing as $ei ) {
                // Only delete if URL is the bare shop URL (wrong URL from demo)
                if ( trailingslashit( $ei->url ) === $shop_base ) {
                    wp_delete_post( $ei->ID, true );
                }
            }
        }
    }

    // Add items with correct URLs
    $position = 0;
    foreach ( $menu_items as $title => $url ) {
        // Check if item with this title already exists
        $existing_items = wp_get_nav_menu_items( $primary_menu_id );
        $already_exists = false;
        if ( $existing_items ) {
            foreach ( $existing_items as $ei ) {
                if ( $ei->title === $title ) {
                    // Update URL if needed
                    update_post_meta( $ei->ID, '_menu_item_url', $url );
                    $already_exists = true;
                    break;
                }
            }
        }
        if ( ! $already_exists ) {
            wp_update_nav_menu_item( $primary_menu_id, 0, [
                'menu-item-title'    => $title,
                'menu-item-url'      => $url,
                'menu-item-status'   => 'publish',
                'menu-item-position' => $position,
                'menu-item-type'     => 'custom',
            ] );
        }
        $position++;
    }

    // Assign to theme location
    $locations = get_theme_mod( 'nav_menu_locations', [] );
    $locations['primary'] = $primary_menu_id;
    set_theme_mod( 'nav_menu_locations', $locations );

    // Footer menu (with real category URLs)
    $footer_menu_name = 'Footer Menu';
    $footer_menu      = wp_get_nav_menu_object( $footer_menu_name );
    if ( ! $footer_menu ) {
        $footer_menu_id  = wp_create_nav_menu( $footer_menu_name );
        $footer_items    = [ 'Kurta Sets', 'Suits', 'Dresses', 'Sarees', 'Co-ords', 'Plus Size' ];
        $position        = 0;
        foreach ( $footer_items as $item_name ) {
            $slug     = sanitize_title( $item_name );
            $term     = get_term_by( 'slug', $slug, 'product_cat' );
            $item_url = ( $term && ! is_wp_error($term) ) ? get_term_link( $term ) : home_url( '/product-category/' . $slug . '/' );
            if ( is_wp_error( $item_url ) ) $item_url = $shop_url;
            wp_update_nav_menu_item( $footer_menu_id, 0, [
                'menu-item-title'    => $item_name,
                'menu-item-url'      => $item_url,
                'menu-item-status'   => 'publish',
                'menu-item-position' => $position++,
                'menu-item-type'     => 'custom',
            ] );
        }
        $locations           = get_theme_mod( 'nav_menu_locations', [] );
        $locations['footer'] = $footer_menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );
    }
}

/**
 * Create WooCommerce product categories with the bundled images.
 */
function wk_create_demo_categories() {
    $categories = [
        'Kurta Sets'  => 'Elegant kurta sets for every occasion.',
        'Suits'       => 'Beautifully crafted suits in premium fabrics.',
        'Dresses'     => 'Effortless dresses for the modern woman.',
        'Sarees'      => 'Timeless sarees with contemporary appeal.',
        'Co-ords'     => 'Matching co-ord sets for a polished look.',
        'Lehengas'    => 'Statement lehengas for celebrations.',
        'Loungewear'  => 'Comfortable loungewear you can style anywhere.',
        'Plus Size'   => 'Inclusive sizing for every body type.',
    ];

    foreach ( $categories as $name => $desc ) {
        if ( ! term_exists( $name, 'product_cat' ) ) {
            wp_insert_term( $name, 'product_cat', [
                'description' => $desc,
                'slug'        => sanitize_title( $name ),
            ] );
        }
    }
}

/**
 * Create demo WooCommerce products using the bundled theme images.
 */
function wk_create_demo_products() {
    $img_dir = get_template_directory() . '/assets/images/';
    
    $products = [
        [
            'title' => 'Teal Embroidered Kurta Set',
            'price' => '1499',
            'sale'  => '1299',
            'cat'   => 'Kurta Sets',
            'img'   => 'product-1.png',
            'desc'  => 'A stunning teal blue cotton kurta with intricate white embroidery on the yoke and sleeves, paired with matching palazzo pants. Perfect for festive occasions and everyday elegance.',
        ],
        [
            'title' => 'Maroon Silk Kurta Set',
            'price' => '2499',
            'sale'  => '1799',
            'cat'   => 'Kurta Sets',
            'img'   => 'product-2.png',
            'desc'  => 'A wine red silk kurta set with golden zari embroidery. The rich fabric and detailed craftsmanship make this a perfect choice for weddings and celebrations.',
        ],
        [
            'title' => 'Pink Georgette Anarkali Suit',
            'price' => '2999',
            'sale'  => '2199',
            'cat'   => 'Suits',
            'img'   => 'product-3.png',
            'desc'  => 'A pastel pink georgette anarkali suit with delicate floral print and silver threadwork. Flowy, graceful, and perfect for special occasions.',
        ],
        [
            'title' => 'Mustard Block Print Kurta',
            'price' => '1999',
            'sale'  => '1499',
            'cat'   => 'Kurta Sets',
            'img'   => 'product-4.png',
            'desc'  => 'A mustard yellow cotton kurta with block-print pattern in indigo blue, paired with white palazzo pants. Effortlessly chic for daytime outings.',
        ],
    ];

    foreach ( $products as $p ) {
        // WP 6.8+ compatible: use WP_Query instead of deprecated get_page_by_title()
        $existing_q = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => 'any',
            'title'          => $p['title'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);
        $existing_id = ! empty( $existing_q->posts ) ? $existing_q->posts[0] : 0;
        wp_reset_postdata();

        if ( $existing_id ) {
            $product_id = $existing_id;
            // Update existing product content
            wp_update_post([
                'ID'           => $product_id,
                'post_content' => $p['desc'],
            ]);
        } else {
            $product_id = wp_insert_post( [
                'post_title'   => $p['title'],
                'post_content' => $p['desc'],
                'post_status'  => 'publish',
                'post_type'    => 'product',
            ] );
        }

        if ( is_wp_error( $product_id ) ) continue;

        // Set product data
        update_post_meta( $product_id, '_regular_price', $p['price'] );
        update_post_meta( $product_id, '_sale_price', $p['sale'] );
        update_post_meta( $product_id, '_price', $p['sale'] );
        update_post_meta( $product_id, '_stock_status', 'instock' );
        update_post_meta( $product_id, '_manage_stock', 'no' );
        update_post_meta( $product_id, '_visibility', 'visible' );
        update_post_meta( $product_id, 'total_sales', rand( 10, 200 ) );

        // Set product type
        wp_set_object_terms( $product_id, 'simple', 'product_type' );

        // Set category
        $cat_term = get_term_by( 'name', $p['cat'], 'product_cat' );
        if ( $cat_term ) {
            wp_set_object_terms( $product_id, $cat_term->term_id, 'product_cat' );
        }

        // Attach the bundled image as the product's featured image, and create a gallery
        if ( ! has_post_thumbnail( $product_id ) ) {
            $img_path = $img_dir . $p['img'];
            if ( file_exists( $img_path ) ) {
                $upload = wp_upload_bits( $p['img'], null, file_get_contents( $img_path ) );
                if ( ! $upload['error'] ) {
                    $attach_id = wp_insert_attachment( [
                        'post_mime_type' => 'image/png',
                        'post_title'     => sanitize_file_name( $p['img'] ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ], $upload['file'], $product_id );

                    if ( ! is_wp_error( $attach_id ) ) {
                        require_once ABSPATH . 'wp-admin/includes/image.php';
                        $meta = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                        wp_update_attachment_metadata( $attach_id, $meta );
                        set_post_thumbnail( $product_id, $attach_id );
                        
                        // Add dummy gallery images (just duplicating the main image for the demo so the grid is filled)
                        // In a real scenario, these would be separate images, but this populates the Libas-style grid.
                        $gallery_ids = [];
                        for($i=1; $i<=3; $i++) {
                            $gallery_ids[] = $attach_id; 
                        }
                        update_post_meta( $product_id, '_product_image_gallery', implode(',', $gallery_ids) );
                    }
                }
            }
        }
    }
}

// Removed manual setup tools. Everything is now fully automated via admin_init.

// ── Create demo categories and sample products on fresh install ────────────
