<?php
/**
 * Plugin Name: TipJar at Checkout for Woo
 * Description: Adds an optional tip selection to the WooCommerce checkout page.
 * Version: 2.0.0
 * Author: vapvarun
 * Author URI: https://vapvarun.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tipjar-checkout-for-woo
 */

defined( 'ABSPATH' ) || exit;

const TIPJAR_CHECKOUT_TIP_SESSION_KEY = 'tipjar_checkout_tip';
const TIPJAR_CHECKOUT_LOG_TABLE       = 'tipjar_checkout_tips';

register_activation_hook( __FILE__, 'tipjar_checkout_install' );

add_action( 'plugins_loaded', 'tipjar_checkout_tip_bootstrap', 20 );

function tipjar_checkout_tip_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( is_admin() ) {
		require_once __DIR__ . '/admin/class-tipjar-checkout-admin.php';
		$admin = new TipJar_Checkout_Admin();
		$admin->init();
	}

	add_action( 'wp_enqueue_scripts', 'tipjar_checkout_tip_enqueue_assets' );
	add_action( 'woocommerce_checkout_before_customer_details', 'tipjar_checkout_tip_render_field', 25 );
	add_action( 'woocommerce_checkout_before_order_review', 'tipjar_checkout_tip_render_field', 5 );
	add_action( 'woocommerce_checkout_after_order_review', 'tipjar_checkout_tip_render_field', 25 );
	add_action( 'woocommerce_review_order_before_payment', 'tipjar_checkout_tip_render_field', 20 );
	add_action( 'woocommerce_cart_calculate_fees', 'tipjar_checkout_tip_apply_fee', 20 );
	add_action( 'woocommerce_checkout_create_order', 'tipjar_checkout_tip_save_meta', 20, 2 );
	add_action( 'woocommerce_thankyou', 'tipjar_checkout_tip_reset_session' );
	add_action( 'wc_ajax_tipjar_checkout_tip_set', 'tipjar_checkout_tip_handle_ajax' );
	add_action( 'wc_ajax_nopriv_tipjar_checkout_tip_set', 'tipjar_checkout_tip_handle_ajax' );
}

function tipjar_checkout_tip_enqueue_assets() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}

	$script_path = plugin_dir_path( __FILE__ ) . 'assets/js/tipjar-checkout.js';
	$style_path  = plugin_dir_path( __FILE__ ) . 'assets/css/tipjar-checkout.css';
	$script_url  = plugins_url( 'assets/js/tipjar-checkout.js', __FILE__ );
	$style_url   = plugins_url( 'assets/css/tipjar-checkout.css', __FILE__ );
	$version     = file_exists( $script_path ) ? filemtime( $script_path ) : '1.0.0';
	$style_ver   = file_exists( $style_path ) ? filemtime( $style_path ) : $version;

	wp_enqueue_script( 'tipjar-checkout', $script_url, array( 'jquery', 'wp-data' ), $version, true );
	tipjar_checkout_tip_attach_script_data( 'tipjar-checkout' );

	wp_set_script_translations( 'tipjar-checkout', 'tipjar-checkout-for-woo', plugin_dir_path( __FILE__ ) . 'languages' );

	if ( file_exists( $style_path ) ) {
		wp_enqueue_style( 'tipjar-checkout', $style_url, array(), $style_ver );
	}
}

function tipjar_checkout_tip_render_field() {
	static $rendered = false;

	if ( $rendered || 'no' === get_option( 'tipjar_checkout_enabled', 'yes' ) || ! tipjar_checkout_are_conditions_met() ) {
		return;
	}

	$rendered = true;

	$current_tip  = tipjar_checkout_tip_get_amount();
	$presets      = tipjar_checkout_tip_get_presets();
	$decimals     = wc_get_price_decimals();
	$value_attr   = $current_tip > 0 ? wc_format_decimal( $current_tip, $decimals ) : '';
	$title        = get_option( 'tipjar_checkout_title', __( 'Add a tip', 'tipjar-checkout-for-woo' ) );
	$description  = get_option( 'tipjar_checkout_description', __( 'Choose a tip amount for the team.', 'tipjar-checkout-for-woo' ) );
	$custom_label = get_option( 'tipjar_checkout_custom_label', __( 'Custom tip amount', 'tipjar-checkout-for-woo' ) );

	echo '<div class="woo-checkout-tip form-row form-row-wide">';
	echo '<h3 class="woo-checkout-tip__title">' . esc_html( $title ) . '</h3>';
	echo '<p class="woo-checkout-tip__description">' . esc_html( $description ) . '</p>';

	if ( ! empty( $presets ) ) {
		echo '<div class="woo-checkout-tip__quick-choice">';
		foreach ( $presets as $preset_value ) {
			$button_label = tipjar_checkout_tip_get_preset_label( $preset_value );
			$button_class = (float) $preset_value === (float) $current_tip ? 'button woo-checkout-tip__button is-active' : 'button woo-checkout-tip__button';
			echo '<button type="button" class="' . esc_attr( $button_class ) . '" data-tip="' . esc_attr( $preset_value ) . '">' . esc_html( $button_label ) . '</button>';
		}
		echo '</div>';
	}

	echo '<p class="woo-checkout-tip__custom">';
	echo '<label for="woo-checkout-tip-amount">' . esc_html( $custom_label ) . '</label>';
	echo '<input type="number" min="0" step="0.01" inputmode="decimal" id="woo-checkout-tip-amount" name="woo-checkout-tip-amount" value="' . esc_attr( $value_attr ) . '" placeholder="' . esc_attr__( 'Enter amount', 'tipjar-checkout-for-woo' ) . '">';
	echo '</p>';
	echo '</div>';
}

function tipjar_checkout_tip_apply_fee( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	if ( 'no' === get_option( 'tipjar_checkout_enabled', 'yes' ) || ! tipjar_checkout_are_conditions_met() ) {
		return;
	}

	if ( ! $cart || $cart->is_empty() ) {
		return;
	}

	$tip_amount = tipjar_checkout_tip_get_amount();

	if ( $tip_amount <= 0 ) {
		return;
	}

	$label   = apply_filters( 'tipjar_checkout_tip_label', __( 'Tip', 'tipjar-checkout-for-woo' ) );
	$taxable = 'yes' === get_option( 'tipjar_checkout_taxable', 'no' );
	$taxable = (bool) apply_filters( 'tipjar_checkout_tip_taxable', $taxable );

	$cart->add_fee( $label, $tip_amount, $taxable );
}

function tipjar_checkout_tip_save_meta( $order, $data ) {
	$tip_amount = tipjar_checkout_tip_get_amount();

	if ( $tip_amount <= 0 ) {
		return;
	}

	$order->update_meta_data( '_tipjar_checkout_tip', wc_format_decimal( $tip_amount, wc_get_price_decimals() ) );

	global $wpdb;
	$table_name = $wpdb->prefix . TIPJAR_CHECKOUT_LOG_TABLE;

	$wpdb->insert(
		$table_name,
		array(
			'order_id'     => $order->get_id(),
			'tip_amount'   => $tip_amount,
			'date_created' => current_time( 'mysql' ),
		)
	);
}

function tipjar_checkout_tip_reset_session() {
	if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) && WC()->session ) {
		WC()->session->__unset( TIPJAR_CHECKOUT_TIP_SESSION_KEY );
	}
}

function tipjar_checkout_tip_handle_ajax() {
	check_ajax_referer( 'tipjar_checkout_tip_set', 'security' );

	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		wp_send_json_error( array( 'message' => __( 'WooCommerce session unavailable.', 'tipjar-checkout-for-woo' ) ) );
	}

	$raw_tip = isset( $_POST['tip'] ) ? sanitize_text_field( wp_unslash( $_POST['tip'] ) ) : 0;
	$tip     = '' === $raw_tip ? 0 : wc_format_decimal( $raw_tip );
	$tip     = is_numeric( $tip ) ? (float) $tip : 0;

	if ( $tip < 0 ) {
		$tip = 0;
	}

	WC()->session->set( TIPJAR_CHECKOUT_TIP_SESSION_KEY, $tip );

	if ( WC()->cart ) {
		WC()->cart->calculate_totals();
	}

	wp_send_json_success(
		array(
			'tip'       => $tip,
			'formatted' => wc_format_decimal( $tip, wc_get_price_decimals() ),
		)
	);
}

function tipjar_checkout_tip_get_amount() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return 0;
	}

	$tip = WC()->session->get( TIPJAR_CHECKOUT_TIP_SESSION_KEY, 0 );

	return is_numeric( $tip ) ? max( 0, (float) $tip ) : 0;
}

function tipjar_checkout_tip_get_presets() {
	$presets_str = get_option( 'tipjar_checkout_presets', '0, 2, 5, 10' );
	$presets_arr = array_map( 'trim', explode( ',', $presets_str ) );
	$presets     = apply_filters( 'tipjar_checkout_tip_presets', $presets_arr );

	if ( ! is_array( $presets ) ) {
		return array( 0 );
	}

	$sanitized = array();

	foreach ( $presets as $preset ) {
		if ( '' === $preset || null === $preset ) {
			continue;
		}

		$value = is_numeric( $preset ) ? (float) $preset : 0;

		if ( $value < 0 ) {
			continue;
		}

		$sanitized[] = $value;
	}

	if ( ! in_array( 0, $sanitized, true ) ) {
		array_unshift( $sanitized, 0.0 );
	}

	$sanitized = array_values( array_unique( $sanitized ) );

	sort( $sanitized, SORT_NUMERIC );

	return $sanitized;
}

function tipjar_checkout_tip_get_preset_label( $amount ) {
	if ( (float) $amount === 0.0 ) {
		return esc_html__( 'No tip', 'tipjar-checkout-for-woo' );
	}

	return wp_strip_all_tags( wc_price( $amount ) );
}

function tipjar_checkout_tip_get_frontend_config() {
	$decimals       = wc_get_price_decimals();
	$current_tip    = tipjar_checkout_tip_get_amount();
	$preset_amounts = tipjar_checkout_tip_get_presets();
	$preset_options = array();

	foreach ( $preset_amounts as $preset ) {
		$preset_options[] = array(
			'value' => (float) $preset,
			'label' => wp_strip_all_tags( tipjar_checkout_tip_get_preset_label( $preset ) ),
		);
	}

	return array(
		'ajaxUrl'       => WC_AJAX::get_endpoint( 'tipjar_checkout_tip_set' ),
		'nonce'         => wp_create_nonce( 'tipjar_checkout_tip_set' ),
		'currentTip'    => $current_tip,
		'decimals'      => $decimals,
		'presetOptions' => $preset_options,
		'labels'        => array(
			'title'       => esc_html__( 'Add a tip', 'tipjar-checkout-for-woo' ),
			'description' => esc_html__( 'Choose a tip amount for the team.', 'tipjar-checkout-for-woo' ),
			'customLabel' => esc_html__( 'Custom tip amount', 'tipjar-checkout-for-woo' ),
			'placeholder' => esc_html__( 'Enter amount', 'tipjar-checkout-for-woo' ),
			'updateError' => esc_html__( 'Unable to update tip. Please try again.', 'tipjar-checkout-for-woo' ),
			'updating'    => esc_html__( 'Updating tip…', 'tipjar-checkout-for-woo' ),
		),
	);
}

function tipjar_checkout_tip_attach_script_data( $handle ) {
	$config = tipjar_checkout_tip_get_frontend_config();

	wp_add_inline_script(
		$handle,
		'window.tipjarCheckout = window.tipjarCheckout || ' . wp_json_encode( $config ) . ';',
		'before'
	);
}

/**
 * Create the custom database table on plugin activation.
 */
function tipjar_checkout_install() {
	global $wpdb;

	$table_name      = $wpdb->prefix . TIPJAR_CHECKOUT_LOG_TABLE;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
		tip_id BIGINT(20) NOT NULL AUTO_INCREMENT,
		order_id BIGINT(20) NOT NULL,
		tip_amount DECIMAL(10, 2) NOT NULL,
		date_created DATETIME NOT NULL,
		PRIMARY KEY (tip_id)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Check if the conditions to display the tip field are met.
 *
 * @return bool
 */
function tipjar_checkout_are_conditions_met() {
	$required_products   = get_option( 'tipjar_checkout_required_products', array() );
	$required_categories = get_option( 'tipjar_checkout_required_categories', array() );
	$min_total           = (float) get_option( 'tipjar_checkout_min_total', 0 );
	$max_total           = (float) get_option( 'tipjar_checkout_max_total', 0 );

	// If no conditions are set, always show the field.
	if ( empty( $required_products ) && empty( $required_categories ) && ! $min_total && ! $max_total ) {
		return true;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return false;
	}

	$cart       = WC()->cart;
	$cart_total = (float) $cart->get_cart_contents_total();

	// Check min/max totals.
	if ( $min_total > 0 && $cart_total < $min_total ) {
		return false;
	}
	if ( $max_total > 0 && $cart_total > $max_total ) {
		return false;
	}

	$cart_product_ids  = array();
	$cart_category_ids = array();

	foreach ( $cart->get_cart() as $cart_item ) {
		$cart_product_ids[] = $cart_item['product_id'];
		$product_categories = wc_get_product_term_ids( $cart_item['product_id'], 'product_cat' );
		if ( ! empty( $product_categories ) ) {
			$cart_category_ids = array_merge( $cart_category_ids, $product_categories );
		}
	}

	if ( ! empty( $required_products ) ) {
		if ( empty( array_intersect( (array) $required_products, $cart_product_ids ) ) ) {
			return false;
		}
	}

	if ( ! empty( $required_categories ) ) {
		if ( empty( array_intersect( (array) $required_categories, array_unique( $cart_category_ids ) ) ) ) {
			return false;
		}
	}

	return true;
}
