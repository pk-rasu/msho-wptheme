<?php
/**
 * WhiteKurti — woocommerce/single-product.php (PDP)
 * FIX BUG 4: removed manual the_post() call. Using standard WC loop pattern.
 * FIX BUG 6: get_header() / get_footer() with NO argument.
 */
defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WooCommerce' ) ) { wp_redirect( home_url() ); exit; }

get_header();
?>

<main id="wk-main" class="wk-pdp">
<div class="wk-container">

<?php
// Standard WooCommerce loop — FIX: let WC manage the loop
while ( have_posts() ) :
	the_post();

	global $product;
	// WooCommerce sets $product; if for any reason it's not a WC_Product, build it
	if ( ! ( $product instanceof WC_Product ) ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! $product ) break;

	$id          = $product->get_id();
	$title       = $product->get_name();
	$price       = (float) $product->get_price();
	$reg_price   = (float) $product->get_regular_price();
	$on_sale     = $product->is_on_sale() && $reg_price > 0;
	$pct         = $on_sale ? round( ( 1 - $price / $reg_price ) * 100 ) : 0;
	$desc        = $product->get_description();
	$short_desc  = $product->get_short_description();
	$rating      = $product->get_average_rating();
	$rev_count   = $product->get_review_count();
	$sku         = $product->get_sku();
	$attributes  = $product->get_attributes();
	$gallery_ids = $product->get_gallery_image_ids();
	$main_img_id = $product->get_image_id();
	$all_imgs    = array_values( array_filter( array_merge( $main_img_id ? [ $main_img_id ] : [], $gallery_ids ) ) );
?>

	<?php
	// Output WC notices before content
	woocommerce_output_all_notices();
	woocommerce_breadcrumb();
	?>

	<div class="wk-pdp__layout">

		<!-- Gallery -->
		<div class="wk-pdp__gallery-wrap">
		<?php if ( ! empty( $all_imgs ) ) :
			$img_count = count( $all_imgs );
		?>

		<!-- Left: thumbnail column (desktop) -->
		<?php if ( $img_count > 1 ) : ?>
		<div class="wk-gallery-thumbs" id="wk-gallery-thumbs">
			<?php foreach ( $all_imgs as $ti => $tid ) :
				$turl = wp_get_attachment_image_url( $tid, 'woocommerce_thumbnail' );
			?>
			<button class="wk-gallery-thumb<?php echo $ti === 0 ? ' is-active' : ''; ?>"
			        data-index="<?php echo $ti; ?>"
			        type="button"
			        aria-label="Image <?php echo $ti + 1; ?>">
				<img src="<?php echo esc_url( $turl ); ?>" alt="" loading="lazy" />
			</button>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<!-- Right: main image + swipe track -->
		<div class="wk-gallery-main" id="wk-gallery-main">

			<!-- Swipe track — 1 slide per image, translateX on swipe -->
			<div class="wk-gallery-track" id="wk-gallery-track">
				<?php foreach ( $all_imgs as $i => $img_id ) :
					$full_src = wp_get_attachment_image_url( $img_id, 'full' );
				?>
				<div class="wk-gallery-slide" data-index="<?php echo $i; ?>">
					<?php echo wp_get_attachment_image( $img_id, 'wk-product-hero', false, [
						'class'         => 'wk-gallery-img',
						'loading'       => $i === 0 ? 'eager' : 'lazy',
						'fetchpriority' => $i === 0 ? 'high' : 'auto',
						'alt'           => esc_attr( $title . ( $i > 0 ? ' – view ' . ($i+1) : '' ) ),
						'data-full'     => esc_url( $full_src ),
						'draggable'     => 'false',
					] ); ?>
					<?php if ( $i === 0 && $on_sale ) : ?>
					<span class="wk-badge wk-badge--sale">−<?php echo $pct; ?>%</span>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
			</div>

			<!-- Image counter: "1 / 5" -->
			<?php if ( $img_count > 1 ) : ?>
			<span class="wk-gallery-counter" id="wk-gallery-counter" aria-live="polite">
				1 / <?php echo $img_count; ?>
			</span>
			<?php endif; ?>

			<!-- Arrow buttons -->
			<?php if ( $img_count > 1 ) : ?>
			<button class="wk-gallery-arrow wk-gallery-arrow--prev" id="wk-gallery-prev" type="button" aria-label="Previous image">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
			</button>
			<button class="wk-gallery-arrow wk-gallery-arrow--next" id="wk-gallery-next" type="button" aria-label="Next image">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
			</button>
			<?php endif; ?>

			<!-- Dot indicators (mobile) -->
			<?php if ( $img_count > 1 ) : ?>
			<div class="wk-gallery-dots" id="wk-gallery-dots">
				<?php for ( $d = 0; $d < $img_count; $d++ ) : ?>
				<button class="wk-gallery-dot<?php echo $d === 0 ? ' is-active' : ''; ?>"
				        data-index="<?php echo $d; ?>"
				        type="button"
				        aria-label="Go to image <?php echo $d + 1; ?>"></button>
				<?php endfor; ?>
			</div>
			<?php endif; ?>

			<!-- Expand icon (tap to fullscreen) -->
			<button class="wk-gallery-expand" id="wk-gallery-expand" type="button" aria-label="View fullscreen">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
			</button>

			<!-- Zoom panel (desktop hover, injected by JS) -->
			<div class="wk-zoom-panel" id="wk-zoom-panel" hidden>
				<img id="wk-zoom-img" src="" alt="" />
			</div>

		</div><!-- /.wk-gallery-main -->

		<?php else : ?>
		<!-- No images placeholder -->
		<div class="wk-gallery-main" style="aspect-ratio:3/4;display:flex;align-items:center;justify-content:center;background:#f5f5f0;border-radius:6px;">
			<span style="font-size:48px;color:#ccc;">👗</span>
		</div>
		<?php endif; ?>

		</div><!-- /.wk-pdp__gallery-wrap -->

		<!-- Product info -->
		<div class="wk-pdp__info" id="wk-pdp-info">

			<div class="wk-pdp__category">
				<?php echo wc_get_product_category_list( $id, ', ', '<span class="wk-eyebrow">', '</span>' ); ?>
			</div>

			<h1 class="wk-pdp__title"><?php echo esc_html( $title ); ?></h1>

			<?php if ( $rating > 0 ) : ?>
			<div class="wk-pdp__rating">
				<?php echo wc_get_rating_html( $rating, $rev_count ); ?>
				<a href="#wk-reviews" class="wk-pdp__review-count">
					<?php printf( _n( '%s review', '%s reviews', $rev_count, 'whitekurti' ), absint( $rev_count ) ); ?>
				</a>
			</div>
			<?php endif; ?>

			<div class="wk-pdp__price">
				<span class="wk-price"><?php echo wk_format_price( $price ); ?></span>
				<?php if ( $on_sale ) : ?>
				<span class="wk-price-was"><?php echo wk_format_price( $reg_price ); ?></span>
				<span class="wk-price-save">(<?php echo $pct; ?>% OFF)</span>
				<?php endif; ?>
			</div>
			<div class="wk-pdp__taxes">Inclusive of all taxes</div>

			<!-- ══ SIZE SELECTOR + BUY NOW + ADD TO CART ══
			     Order: Sizes → Buy Now → Add to Cart → Short Description -->

			<?php
			// ── Size & Variation Selectors (shown first, before buttons) ──
			$is_variable = $product->get_type() === 'variable';
			$is_purchasable = $product->is_purchasable() && $product->is_in_stock();

			if ( $is_variable ) :
				// Get available attributes for variation selection
				$variation_attrs = $product->get_variation_attributes();
			?>
			<div class="wk-pdp__variations" id="wk-variations-wrap">
				<?php foreach ( $variation_attrs as $attr_name => $options ) :
					$attr_label = wc_attribute_label( $attr_name );
					$is_size    = in_array( strtolower(str_replace('pa_','',$attr_name)), ['size','sizes','s','m','l'] );
					$is_color   = in_array( strtolower(str_replace('pa_','',$attr_name)), ['color','colour','rang'] );
				?>
				<div class="wk-variation-group" data-attr="<?php echo esc_attr($attr_name); ?>">
					<div class="wk-variation-group__head">
						<span class="wk-variation-group__label"><?php echo esc_html($attr_label); ?></span>
						<span class="wk-variation-group__selected" id="wk-var-selected-<?php echo esc_attr($attr_name); ?>"></span>
						<?php if ( $is_size ) :
							$sg = get_page_by_path('size-guide');
						?>
						<?php if ( $sg ) : ?>
						<a href="<?php echo esc_url( get_permalink($sg->ID) ); ?>"
						   class="wk-variation-group__size-guide"
						   target="_blank">Size Guide</a>
						<?php endif; ?>
						<?php endif; ?>
					</div>
					<div class="wk-variation-group__options <?php echo $is_color ? 'wk-variation-group__options--color' : ''; ?>">
						<?php foreach ( $options as $option ) :
							$clean = esc_attr($option);
							if ( $is_color ) :
								// Color swatches
								$hex = function_exists('wk_swatches_name_to_hex') ? wk_swatches_name_to_hex($option) : '';
							?>
							<button type="button"
							        class="wk-var-opt wk-var-opt--color"
							        data-value="<?php echo $clean; ?>"
							        data-attr="<?php echo esc_attr($attr_name); ?>"
							        title="<?php echo esc_attr($option); ?>"
							        <?php echo $hex ? 'style="background:'.esc_attr($hex).'"' : ''; ?>>
								<span class="sr-only"><?php echo esc_html($option); ?></span>
							</button>
							<?php else : ?>
							<!-- Size pill -->
							<button type="button"
							        class="wk-var-opt wk-var-opt--size"
							        data-value="<?php echo $clean; ?>"
							        data-attr="<?php echo esc_attr($attr_name); ?>">
								<?php echo esc_html(strtoupper($option)); ?>
							</button>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endforeach; ?>
				<div class="wk-variation-notice" id="wk-variation-notice" hidden>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
					Please select all options before adding to cart
				</div>
			</div>
			<?php endif; // is_variable ?>

			<!-- ── Buy Now button (first / most prominent) ── -->
			<?php if ( $is_purchasable ) : ?>
			<button type="button"
			        class="wk-btn wk-btn--full wk-btn--primary wk-btn--buy-now"
			        id="wk-buy-now"
			        data-product-id="<?php echo absint($id); ?>"
			        data-product-type="<?php echo esc_attr($product->get_type()); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
				<?php esc_html_e('Buy Now', 'whitekurti'); ?>
			</button>
			<?php endif; ?>

			<!-- ── Standard WC Add to Cart form (hidden, powers AJAX + variation logic) ── -->
			<!-- WC native form: visually hidden off-screen, used only for JS variation sync + ATC click -->
			<div class="wk-pdp__wc-form" id="wk-wc-form" tabindex="-1">
				<?php woocommerce_template_single_add_to_cart(); ?>
			</div>

			<!-- ── Visible Add to Cart button (triggers the hidden WC form) ── -->
			<?php if ( $is_purchasable ) : ?>
			<button type="button"
			        class="wk-btn wk-btn--full wk-btn--outline wk-btn--atc"
			        id="wk-atc-btn"
			        data-product-id="<?php echo absint($id); ?>"
			        data-product-type="<?php echo esc_attr($product->get_type()); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.99 1.61h9.72a2 2 0 001.99-1.61L23 6H6"/></svg>
				<?php echo esc_html( get_theme_mod('wk_text_add_to_cart', 'Add to Cart') ); ?>
			</button>
			<?php elseif ( ! $product->is_in_stock() ) : ?>
			<div class="wk-out-of-stock">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
				Out of Stock
			</div>
			<?php endif; ?>

			<!-- ── Short description (after buttons) ── -->
			<?php if ( $short_desc ) : ?>
			<div class="wk-pdp__short-desc"><?php echo wp_kses_post( $short_desc ); ?></div>
			<?php endif; ?>

			<!-- Trust badges -->
			<div class="wk-pdp__trust">
				<div class="wk-pdp__trust-item"><?php wk_icon( 'truck', 15 ); ?><span><?php echo esc_html( get_theme_mod('wk_pdp_trust1', 'FREE Delivery on All Orders') ); ?></span></div>
				<div class="wk-pdp__trust-item"><?php wk_icon( 'return', 15 ); ?><span><?php echo esc_html( get_theme_mod('wk_pdp_trust2', '5-Day Easy Returns') ); ?></span></div>
				<div class="wk-pdp__trust-item"><?php wk_icon( 'shield', 15 ); ?><span><?php echo esc_html( get_theme_mod('wk_pdp_trust3', 'Secure checkout') ); ?></span></div>
				<div class="wk-pdp__trust-item"><?php wk_icon( 'pin', 15 ); ?><span><?php echo esc_html( get_theme_mod('wk_pdp_trust4', 'COD available') ); ?></span></div>
			</div>

			<!-- Pincode checker -->
			<div class="wk-pdp__pincode">
				<div class="wk-pdp__pincode-form">
					<input type="text" id="wk-pincode-input" maxlength="6" pattern="[0-9]{6}"
						placeholder="<?php echo esc_attr( get_theme_mod('wk_pdp_pincode_placeholder', 'Enter pincode') ); ?>"
						class="wk-input" inputmode="numeric" autocomplete="postal-code" />
					<button class="wk-btn wk-btn--sm" id="wk-pincode-check" type="button">
						<?php echo esc_html( get_theme_mod('wk_pdp_pincode_btn', 'Check') ); ?>
					</button>
				</div>
				<div id="wk-pincode-result" class="wk-pdp__pincode-result" aria-live="polite"></div>
			</div>

			<!-- Accordions -->
			<div class="wk-pdp__accordions">

				<?php if ( $desc ) : ?>
				<details class="wk-accordion">
					<summary class="wk-accordion__head"><span><?php echo esc_html( get_theme_mod('wk_pdp_acc_details', 'Product Details') ); ?></span><?php wk_icon( 'chev-d', 16 ); ?></summary>
					<div class="wk-accordion__body"><?php echo wp_kses_post( wpautop( wptexturize( $desc ) ) ); ?></div>
				</details>
				<?php endif; ?>

				<?php foreach ( $attributes as $attr ) :
					if ( ! $attr->get_visible() ) continue;
					$values = $attr->is_taxonomy()
						? implode( ', ', wp_list_pluck( wc_get_product_terms( $id, $attr->get_name(), [ 'fields' => 'all' ] ), 'name' ) )
						: implode( ', ', $attr->get_options() );
				?>
				<details class="wk-accordion">
					<summary class="wk-accordion__head"><span><?php echo esc_html( wc_attribute_label( $attr->get_name() ) ); ?></span><?php wk_icon( 'chev-d', 16 ); ?></summary>
					<div class="wk-accordion__body"><?php echo esc_html( $values ); ?></div>
				</details>
				<?php endforeach; ?>

				<details class="wk-accordion">
					<summary class="wk-accordion__head"><span><?php echo esc_html( get_theme_mod('wk_pdp_acc_size', 'Size & Fit') ); ?></span><?php wk_icon( 'chev-d', 16 ); ?></summary>
					<div class="wk-accordion__body">
						<p><?php echo esc_html( get_theme_mod('wk_pdp_acc_size_body', 'Model is 5\'6" and wears size S. Refer to our size guide for exact measurements.') ); ?></p>
						<?php $sg = get_page_by_path( 'size-guide' ); if ( $sg ) : ?>
						<a href="<?php echo esc_url( get_permalink( $sg->ID ) ); ?>" class="wk-link-underline"><?php esc_html_e( 'View size guide', 'whitekurti' ); ?></a>
						<?php endif; ?>
					</div>
				</details>

				<details class="wk-accordion">
					<summary class="wk-accordion__head"><span><?php echo esc_html( get_theme_mod('wk_pdp_acc_shipping', 'Shipping & Returns') ); ?></span><?php wk_icon( 'chev-d', 16 ); ?></summary>
					<div class="wk-accordion__body">
						<p><?php echo esc_html( get_theme_mod('wk_pdp_acc_shipping_body', 'Free Delivery on all orders. Delivered in 3–5 business days. Free 5-day returns with free pickup.') ); ?></p>
					</div>
				</details>

			</div><!-- /.wk-pdp__accordions -->

			<?php if ( $sku ) : ?>
			<p class="wk-pdp__sku"><?php esc_html_e( 'SKU', 'whitekurti' ); ?>: <?php echo esc_html( $sku ); ?></p>
			<?php endif; ?>

		</div><!-- /.wk-pdp__info -->
	</div><!-- /.wk-pdp__layout -->

	<!-- Reviews — Beautiful Custom Section -->
	<div id="wk-reviews" class="wk-pdp__reviews">
		<?php echo wk_render_reviews_section( $id ); ?>

		<!-- ══ USER REVIEW FORM ══ -->
		<div class="wk-write-review" id="wk-write-review">
			<h3 class="wk-write-review__title">Write a Review</h3>
			<form class="wk-review-form" id="wk-user-review-form" enctype="multipart/form-data">
				<?php wp_nonce_field( 'wk-nonce', 'wk_review_form_nonce' ); ?>
				<input type="hidden" name="wk_product_id" value="<?php echo absint($id); ?>" />
				<input type="hidden" name="wk_user_rating" value="0" />

				<!-- Star Rating -->
				<div class="wk-review-form__field">
					<label class="wk-review-form__label">Your Rating <span style="color:red">*</span></label>
					<div class="wk-review-star-row" role="group" aria-label="Star rating">
						<?php for($s=1;$s<=5;$s++): ?>
						<button type="button" class="wk-review-star-btn" data-val="<?php echo $s; ?>" aria-label="<?php echo $s; ?> star" aria-pressed="false">★</button>
						<?php endfor; ?>
						<span class="wk-review-star-label"></span>
					</div>
				</div>

				<!-- Name + City -->
				<div class="wk-review-form__row">
					<div class="wk-review-form__field">
						<label class="wk-review-form__label" for="wk-rv-name">Your Name <span style="color:red">*</span></label>
						<input type="text" class="wk-review-form__input" id="wk-rv-name"
							name="wk_reviewer_name" placeholder="e.g. Priya Sharma"
							maxlength="80" required />
					</div>
					<div class="wk-review-form__field">
						<label class="wk-review-form__label" for="wk-rv-city">City</label>
						<input type="text" class="wk-review-form__input" id="wk-rv-city"
							name="wk_reviewer_city" placeholder="e.g. Mumbai"
							maxlength="60" />
					</div>
				</div>

				<!-- Review Text -->
				<div class="wk-review-form__field">
					<label class="wk-review-form__label" for="wk-rv-text">Your Review <span style="color:red">*</span></label>
					<textarea class="wk-review-form__textarea" id="wk-rv-text"
						name="wk_user_review_text"
						placeholder="Tell others about your experience — fit, fabric quality, delivery..."
						minlength="10" maxlength="1000" required></textarea>
				</div>

				<!-- Photo Upload -->
				<div class="wk-review-form__field">
					<label class="wk-review-form__label">Add Photos <span style="color:var(--ink-mute,#aaa);font-weight:400">(optional, max 3)</span></label>
					<div class="wk-review-photo-upload">
						<label class="wk-review-photo-add" title="Add photo" tabindex="0">
							<span aria-hidden="true">+</span>
							<input type="file" name="wk_review_photos[]" accept="image/*" multiple />
						</label>
					</div>
				</div>

				<!-- Submit -->
				<button type="submit" class="wk-review-form__submit">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
					Submit Review
				</button>
				<div class="wk-review-form__msg" role="alert"></div>
			</form>
		</div>
	</div>

	<!-- ═══ RELATED / RECOMMENDED PRODUCTS ═══ -->
	<?php wk_render_pdp_recommended( $id, $product ); ?>

<?php do_action( 'woocommerce_after_single_product' ); ?>

<?php endwhile; // end WC loop ?>

</div><!-- /.wk-container -->
</main>

<!-- Sticky ATC (mobile) -->
<?php if ( isset( $product ) && $product instanceof WC_Product ) : ?>
<div class="wk-sticky-atc" id="wk-sticky-atc" aria-hidden="true" aria-label="<?php esc_attr_e( 'Quick add to cart', 'whitekurti' ); ?>">
	<div class="wk-sticky-atc__price">
		<span class="wk-price"><?php echo wk_format_price( $product->get_price() ); ?></span>
		<?php if ( isset($on_sale) && $on_sale && isset($reg_price) ) : ?>
		<span class="wk-price-was wk-price-was--sm"><?php echo wk_format_price( $reg_price ); ?></span>
		<?php endif; ?>
	</div>
	<button class="wk-btn wk-sticky-atc__btn" id="wk-sticky-atc-btn"
		type="button" data-product-id="<?php echo isset($id) ? absint( $id ) : 0; ?>">
		<?php echo esc_html( get_theme_mod('wk_text_add_to_cart', 'Add to Cart') ); ?>
	</button>
</div>
<?php endif; ?>

<?php get_footer();
?>
