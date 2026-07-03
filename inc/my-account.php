<?php
/**
 * WhiteKurti — My Account Dashboard Enhancement
 * Visual order tracker, order history with images, stats panel
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════════════
// 1. DASHBOARD OVERVIEW (replaces basic dashboard)
// ═══════════════════════════════════════════════════════════════

// Override dashboard content
add_action( 'woocommerce_account_dashboard', 'wk_account_dashboard_content', 1 );
function wk_account_dashboard_content() {
	$user    = wp_get_current_user();
	$user_id = $user->ID;
	if ( ! $user_id ) return;

	// Get stats
	$orders = wc_get_orders([
		'customer' => $user_id,
		'limit'    => -1,
		'status'   => ['wc-completed','wc-processing','wc-on-hold','wc-shipped'],
	]);
	$total_orders = count($orders);
	$total_spent  = array_sum(array_map(function($o) { return (float)$o->get_total(); }, $orders));
	$recent_orders= array_slice($orders, 0, 3);

	// Get wishlist count
	$wishlist     = get_user_meta($user_id, '_wk_wishlist', true) ?: [];
	$wishlist_count = count($wishlist);

	// Member since
	$member_since = human_time_diff( strtotime($user->user_registered), time() );
	?>
	<div class="wk-dashboard">

		<!-- Welcome banner -->
		<div class="wk-dashboard__welcome">
			<div class="wk-dashboard__welcome-text">
				<h2 class="wk-dashboard__name">Hi, <?php echo esc_html(explode(' ', $user->display_name)[0]); ?>! 👋</h2>
				<p class="wk-dashboard__member">Member for <?php echo esc_html($member_since); ?></p>
			</div>
			<a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>" class="wk-btn wk-btn--sm wk-btn--outline">Edit Profile</a>
		</div>

		<!-- Stats grid -->
		<div class="wk-dashboard__stats">
			<div class="wk-stat-card">
				<div class="wk-stat-card__icon">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
				</div>
				<div class="wk-stat-card__body">
					<span class="wk-stat-card__value"><?php echo absint($total_orders); ?></span>
					<span class="wk-stat-card__label">Total Orders</span>
				</div>
			</div>
			<div class="wk-stat-card">
				<div class="wk-stat-card__icon">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
				</div>
				<div class="wk-stat-card__body">
					<span class="wk-stat-card__value"><?php echo strip_tags(wc_price($total_spent)); ?></span>
					<span class="wk-stat-card__label">Total Spent</span>
				</div>
			</div>
			<div class="wk-stat-card">
				<div class="wk-stat-card__icon">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
				</div>
				<div class="wk-stat-card__body">
					<span class="wk-stat-card__value"><?php echo absint($wishlist_count); ?></span>
					<span class="wk-stat-card__label">Wishlist Items</span>
				</div>
			</div>
			<div class="wk-stat-card">
				<div class="wk-stat-card__icon">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
				</div>
				<div class="wk-stat-card__body">
					<?php
					$address = get_user_meta($user_id, 'shipping_city', true);
					echo '<span class="wk-stat-card__value" style="font-size:14px;">' . esc_html($address ?: '—') . '</span>';
					?>
					<span class="wk-stat-card__label">Default City</span>
				</div>
			</div>
		</div>

		<!-- Quick actions -->
		<div class="wk-dashboard__actions">
			<?php
			$quick_links = [
				['url' => wc_get_account_endpoint_url('orders'),       'icon' => 'M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z M3 6h18 M16 10a4 4 0 01-8 0', 'label' => 'My Orders'],
				['url' => wc_get_account_endpoint_url('edit-address'), 'icon' => 'M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z M12 10m-3 0a3 3 0 106 0 3 3 0', 'label' => 'Addresses'],
				['url' => home_url('/wishlist'),                        'icon' => 'M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z', 'label' => 'Wishlist'],
				['url' => wc_get_account_endpoint_url('edit-account'), 'icon' => 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2 M12 7a4 4 0 100 8 4 4 0 000-8z', 'label' => 'Account Details'],
			];
			foreach ($quick_links as $ql) :
			?>
			<a href="<?php echo esc_url($ql['url']); ?>" class="wk-dashboard__action-link">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
					<?php foreach (explode(' M', ltrim($ql['icon'],'M')) as $i => $path) : ?>
					<path d="M<?php echo esc_attr($path); ?>"/>
					<?php endforeach; ?>
				</svg>
				<span><?php echo esc_html($ql['label']); ?></span>
				<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
			</a>
			<?php endforeach; ?>
		</div>

		<!-- Recent orders -->
		<?php if ($recent_orders) : ?>
		<div class="wk-dashboard__recent">
			<div class="wk-dashboard__section-head">
				<h3 class="wk-dashboard__section-title">Recent Orders</h3>
				<a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="wk-dashboard__see-all">View all →</a>
			</div>
			<div class="wk-dashboard__order-list">
				<?php foreach ($recent_orders as $order) :
					$status      = $order->get_status();
					$items       = $order->get_items();
					$item_count  = $order->get_item_count();
					$date        = $order->get_date_created();
					$total       = $order->get_formatted_order_total();
					// Collect product images (up to 3)
					$imgs = [];
					foreach ($items as $item) {
						$pid = $item->get_product_id();
						$p   = wc_get_product($pid);
						if ($p && $p->get_image_id()) {
							$imgs[] = wp_get_attachment_image_url($p->get_image_id(),'woocommerce_thumbnail');
						}
						if (count($imgs) >= 3) break;
					}
				?>
				<div class="wk-recent-order">
					<div class="wk-recent-order__imgs">
						<?php foreach ($imgs as $img) : ?>
						<img src="<?php echo esc_url($img); ?>" alt="" class="wk-recent-order__img" loading="lazy" />
						<?php endforeach; ?>
						<?php if (count($items) > 3) : ?>
						<span class="wk-recent-order__more-items">+<?php echo count($items) - 3; ?></span>
						<?php endif; ?>
					</div>
					<div class="wk-recent-order__info">
						<div class="wk-recent-order__meta">
							<span class="wk-recent-order__num">Order #<?php echo $order->get_order_number(); ?></span>
							<span class="wk-recent-order__date"><?php echo $date ? $date->date_i18n('d M Y') : ''; ?></span>
						</div>
						<div class="wk-recent-order__bottom">
							<span class="wk-order-status wk-order-status--<?php echo esc_attr($status); ?>"><?php echo esc_html(wc_get_order_status_name($status)); ?></span>
							<span class="wk-recent-order__total"><?php echo $total; ?> · <?php echo $item_count; ?> item<?php echo $item_count>1?'s':''; ?></span>
						</div>
					</div>
					<a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="wk-recent-order__view" aria-label="View order #<?php echo $order->get_order_number(); ?>">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
					</a>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

// ═══════════════════════════════════════════════════════════════
// 2. VISUAL ORDER STATUS TRACKER
// ═══════════════════════════════════════════════════════════════
function wk_order_status_tracker( $order ) {
	if ( ! $order ) return;

	$status = $order->get_status();
	$steps  = [
		'pending'    => ['label' => 'Order Placed',   'icon' => '📋', 'statuses' => ['pending']],
		'processing' => ['label' => 'Confirmed',       'icon' => '✅', 'statuses' => ['processing','on-hold']],
		'shipped'    => ['label' => 'Shipped',         'icon' => '📦', 'statuses' => ['shipped']],
		'completed'  => ['label' => 'Delivered',       'icon' => '🎉', 'statuses' => ['completed']],
	];

	// Determine current step index
	$step_keys = array_keys($steps);
	$current   = 0;
	foreach ($steps as $key => $step) {
		if (in_array($status, $step['statuses'])) {
			$current = array_search($key, $step_keys);
			break;
		}
	}
	// If cancelled/refunded/failed — show special state
	$negative  = in_array($status, ['cancelled','refunded','failed']);
	?>
	<div class="wk-order-tracker" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo count($steps)-1; ?>" aria-valuenow="<?php echo $current; ?>">
		<?php if ($negative) : ?>
		<div class="wk-order-tracker__cancelled">
			<span>⚠️</span>
			<span>Order <?php echo esc_html(wc_get_order_status_name($status)); ?></span>
		</div>
		<?php else : ?>
		<div class="wk-order-tracker__steps">
			<?php foreach ($step_keys as $idx => $key) :
				$step     = $steps[$key];
				$is_done  = $idx < $current;
				$is_active= $idx === $current;
				$cls      = 'wk-ot-step' . ($is_done?' is-done':'') . ($is_active?' is-active':'');
			?>
			<div class="<?php echo $cls; ?>">
				<div class="wk-ot-step__bubble"><?php echo $is_done ? '✓' : esc_html($step['icon']); ?></div>
				<div class="wk-ot-step__label"><?php echo esc_html($step['label']); ?></div>
				<?php if ($idx < count($steps)-1) : ?>
				<div class="wk-ot-step__line <?php echo $is_done?'is-done':''; ?>"></div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
		// Show estimated delivery
		$date_created = $order->get_date_created();
		if ($date_created && $status !== 'completed') :
			$est_days = 5;
			$est_date = clone $date_created;
			$est_date->modify('+' . $est_days . ' days');
			if ($est_date > new WC_DateTime()) :
			?>
			<div class="wk-order-tracker__eta">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
				Estimated delivery: <strong><?php echo $est_date->date_i18n('D, d M Y'); ?></strong>
			</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}
add_action( 'woocommerce_view_order', 'wk_order_status_tracker', 5 );

// ═══════════════════════════════════════════════════════════════
// 3. ENHANCED ORDER HISTORY (product images in orders table)
// ═══════════════════════════════════════════════════════════════
add_filter( 'woocommerce_account_orders_columns', function($cols) {
	return [
		'order-items'   => '',
		'order-number'  => $cols['order-number']  ?? 'Order',
		'order-date'    => $cols['order-date']    ?? 'Date',
		'order-status'  => $cols['order-status']  ?? 'Status',
		'order-total'   => $cols['order-total']   ?? 'Total',
		'order-actions' => $cols['order-actions'] ?? 'Actions',
	];
}, 20);

add_action( 'woocommerce_my_account_my_orders_column_order-items', function($order) {
	$items = $order->get_items();
	$imgs  = [];
	foreach ($items as $item) {
		$pid = $item->get_product_id();
		$p   = wc_get_product($pid);
		if ($p) {
			$img_id = $p->get_image_id();
			$imgs[] = [
				'src'  => $img_id ? wp_get_attachment_image_url($img_id,'thumbnail') : wc_placeholder_img_src('thumbnail'),
				'name' => $p->get_name(),
			];
		}
		if (count($imgs) >= 3) break;
	}
	echo '<div class="wk-order-imgs">';
	foreach (array_slice($imgs,0,3) as $img) {
		echo '<img src="'.esc_url($img['src']).'" alt="'.esc_attr($img['name']).'" class="wk-order-thumb" loading="lazy" />';
	}
	$extra = count($order->get_items()) - 3;
	if ($extra > 0) echo '<span class="wk-order-extra-items">+'.absint($extra).'</span>';
	echo '</div>';
});

// ═══════════════════════════════════════════════════════════════
// 4. ENHANCED DASHBOARD TEMPLATE
// ═══════════════════════════════════════════════════════════════
// Override the basic dashboard.php via filter
add_filter( 'wc_get_template', function($template, $template_name) {
	if ($template_name === 'myaccount/dashboard.php') {
		$theme_tpl = get_theme_file_path('woocommerce/myaccount/dashboard.php');
		if (file_exists($theme_tpl)) return $theme_tpl;
	}
	return $template;
}, 10, 2);
