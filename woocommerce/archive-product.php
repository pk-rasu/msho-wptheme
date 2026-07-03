<?php
/**
 * WhiteKurti — woocommerce/archive-product.php (Shop / PLP)
 * FIX BUG 6: get_header() / get_footer() with NO argument.
 */
defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WooCommerce' ) ) { wp_redirect( home_url() ); exit; }

get_header();
$filter_style = get_theme_mod( 'wk_filter_style', 'drawer' );
$columns      = max( 1, (int) get_theme_mod( 'wk_card_columns', 2 ) );
?>

<main id="wk-main" class="wk-shop wk-shop--<?php echo esc_attr( $filter_style ); ?>">

	<!-- Shop header -->
	<div class="wk-shop-head">
		<div class="wk-container">
			<?php woocommerce_breadcrumb(); ?>
			<div class="wk-shop-head__inner">
				<div class="wk-shop-head__title">
					<?php if ( is_search() ) : ?>
						<h1><?php printf( esc_html__( 'Search: &ldquo;%s&rdquo;', 'whitekurti' ), get_search_query() ); ?></h1>
					<?php elseif ( is_product_category() ) : ?>
						<h1><?php single_cat_title(); ?></h1>
						<?php $desc = term_description(); if ( $desc ) echo '<p class="wk-cat-desc">' . wp_kses_post( $desc ) . '</p>'; ?>
					<?php else : ?>
						<h1><?php woocommerce_page_title(); ?></h1>
					<?php endif; ?>
					<!-- Result count shown in controls area instead -->
				</div>
				<div class="wk-shop-head__controls">
					<span class="wk-shop-result-count"><?php woocommerce_result_count(); ?></span>
					<?php if ( $filter_style !== 'sidebar' ) : ?>
					<button class="wk-btn wk-btn--outline wk-btn--sm" id="wk-filter-toggle" type="button"
						aria-expanded="false" aria-controls="wk-filter-drawer">
						<?php wk_icon( 'filter', 14 ); ?> <?php echo esc_html( get_theme_mod('wk_shop_filter_btn', 'Filter') ); ?>
					</button>
					<?php endif; ?>
					<?php woocommerce_catalog_ordering(); ?>
				</div>
			</div>

			<?php if ( $filter_style === 'topbar' ) : ?>
			<div class="wk-topbar-filters" role="navigation" aria-label="<?php esc_attr_e( 'Category filters', 'whitekurti' ); ?>">
				<?php
				$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 12 ] );
				if ( ! is_wp_error( $cats ) ) :
					foreach ( $cats as $cat ) :
						$link_url = get_term_link( $cat );
						if ( is_wp_error( $link_url ) ) continue;
						$is_active = is_product_category( $cat->slug );
				?>
				<a href="<?php echo esc_url( $link_url ); ?>"
					class="wk-topbar-filter-link wk-chip<?php echo $is_active ? ' active' : ''; ?>"
					aria-current="<?php echo $is_active ? 'page' : 'false'; ?>">
					<?php echo esc_html( $cat->name ); ?>
				</a>
				<?php endforeach; endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</div><!-- /.wk-shop-head -->

	<div class="wk-container">
		<div class="wk-shop-layout<?php echo $filter_style === 'sidebar' ? ' wk-shop-layout--sidebar' : ''; ?>">

			<?php if ( $filter_style === 'sidebar' ) : ?>
			<aside class="wk-shop-sidebar" aria-label="<?php esc_attr_e( 'Product filters', 'whitekurti' ); ?>">
				<?php
				dynamic_sidebar( 'shop-sidebar' );
				// Inline category filter
				$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 20 ] );
				if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) : ?>
				<div class="wk-filter-group">
					<h3 class="wk-filter-group__title"><?php echo esc_html( get_theme_mod('wk_shop_cat_title', 'Category') ); ?></h3>
					<ul class="wk-filter-list">
						<?php foreach ( $cats as $cat ) :
							$cat_link = get_term_link( $cat );
							if ( is_wp_error( $cat_link ) ) continue;
						?>
						<li<?php echo is_product_category( $cat->slug ) ? ' class="active"' : ''; ?>>
							<a href="<?php echo esc_url( $cat_link ); ?>">
								<?php echo esc_html( $cat->name ); ?>
								<span>(<?php echo absint( $cat->count ); ?>)</span>
							</a>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>
			</aside>
			<?php endif; ?>

			<!-- Products grid -->
			<div class="wk-shop-products">
				<?php
				// Output WC notices (error/success/info messages)
				woocommerce_output_all_notices();
				?>
				<?php if ( woocommerce_product_loop() ) : ?>
				<?php
				// Configure WC loop (required for proper loop settings + plugin compat)
				wc_set_loop_prop( 'columns', $columns );
				wc_set_loop_prop( 'name', is_product_category() ? 'category' : 'main' );
				do_action( 'woocommerce_before_shop_loop' );
				?>
				<div class="wk-products-grid wk-products-grid--<?php echo absint( $columns ); ?>col">
					<?php
					while ( have_posts() ) :
						the_post();
						$product = wc_get_product( get_the_ID() );
						if ( $product ) wk_product_card( $product, [ 'layout' => 'editorial' ] );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
				<?php do_action( 'woocommerce_after_shop_loop' ); ?>
				<div class="wk-pagination">
					<?php woocommerce_pagination(); ?>
				</div>
				<?php else : ?>
				<div class="wk-empty-state">
					<p class="wk-eyebrow"><?php echo esc_html( get_theme_mod('wk_shop_empty_eyebrow', 'Nothing here yet') ); ?></p>
					<h2 class="wk-empty-state__title">
						<?php echo esc_html( get_theme_mod('wk_shop_empty_title', 'No products found') ); ?>
					</h2>
					<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="wk-btn">
						<?php echo esc_html( get_theme_mod('wk_shop_empty_btn', 'Browse all') ); ?>
					</a>
				</div>
				<?php endif; ?>
			</div>

		</div>
	</div>

</main>

<?php /* Filter drawer for drawer + topbar modes */ ?>
<?php if ( $filter_style !== 'sidebar' ) : ?>
<div id="wk-filter-drawer" class="wk-filter-drawer" aria-hidden="true">
	<div class="wk-overlay__backdrop" id="wk-filter-backdrop"></div>
	<div class="wk-filter-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Filter products', 'whitekurti' ); ?>">
		<div class="wk-filter-drawer__head">
			<span><?php echo esc_html( get_theme_mod('wk_shop_filter_drawer_title', 'Filter & Sort') ); ?></span>
			<button class="wk-icon-btn" id="wk-filter-close" type="button" aria-label="<?php esc_attr_e( 'Close filters', 'whitekurti' ); ?>">
				<?php wk_icon( 'close', 20 ); ?>
			</button>
		</div>
		<div class="wk-filter-drawer__body">
			<?php
			$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 20 ] );
			if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) : ?>
			<div class="wk-filter-group">
				<h3 class="wk-filter-group__title"><?php echo esc_html( get_theme_mod('wk_shop_cat_title', 'Category') ); ?></h3>
				<ul class="wk-filter-list">
					<?php foreach ( $cats as $cat ) :
						$cat_link = get_term_link( $cat );
						if ( is_wp_error( $cat_link ) ) continue;
					?>
					<li<?php echo is_product_category( $cat->slug ) ? ' class="active"' : ''; ?>>
						<a href="<?php echo esc_url( $cat_link ); ?>">
							<?php echo esc_html( $cat->name ); ?> <span>(<?php echo absint( $cat->count ); ?>)</span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif;
			dynamic_sidebar( 'shop-sidebar' );
			?>
		</div>
		<div class="wk-filter-drawer__foot">
			<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"
				class="wk-btn wk-btn--outline"><?php echo esc_html( get_theme_mod('wk_shop_clear_btn', 'Clear All') ); ?></a>
			<button class="wk-btn" id="wk-filter-apply" type="button">
				<?php echo esc_html( get_theme_mod('wk_shop_apply_btn', 'Apply') ); ?>
			</button>
		</div>
	</div>
</div>
<?php endif; ?>

<?php get_footer();
