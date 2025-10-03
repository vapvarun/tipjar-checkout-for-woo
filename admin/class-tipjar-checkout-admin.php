<?php
/**
 * TipJar at Checkout for Woo Admin
 *
 * @package TipJar_Checkout_For_Woo
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * TipJar_Checkout_Admin Class.
 *
 * @since 2.0.0
 */
class TipJar_Checkout_Admin {

	/**
	 * Initialize the admin class.
	 */
	public function init() {
		// Register the settings page.
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );

		// Add the log page.
		require_once __DIR__ . '/class-tipjar-checkout-log.php';
		$log_page = new TipJar_Checkout_Log();
		$log_page->init();
	}

	/**
	 * Add the settings page to WooCommerce.
	 *
	 * @param array $settings The existing settings pages.
	 * @return array The modified settings pages.
	 */
	public function add_settings_page( $settings ) {
		$settings[] = include __DIR__ . '/settings/class-tipjar-checkout-settings.php';
		return $settings;
	}
}