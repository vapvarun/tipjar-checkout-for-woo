<?php
/**
 * TipJar at Checkout for Woo Settings.
 *
 * @package TipJar_Checkout_For_Woo
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Settings_Page' ) ) {
	include_once WC()->plugin_path() . '/includes/admin/settings/class-wc-settings-page.php';
}

/**
 * Class TipJar_Checkout_Settings.
 */
class TipJar_Checkout_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'tipjar_checkout';
		$this->label = __( 'TipJar Checkout', 'tipjar-checkout-for-woo' );

		parent::__construct();
	}

	/**
	 * Get the settings array.
	 *
	 * @return array The settings array.
	 */
	public function get_settings() {
		$settings = array(
			array(
				'title' => __( 'TipJar Checkout Settings', 'tipjar-checkout-for-woo' ),
				'type'  => 'title',
				'id'    => 'tipjar_checkout_options',
			),
			array(
				'title'   => __( 'Enable TipJar', 'tipjar-checkout-for-woo' ),
				'desc'    => __( 'Enable or disable the tip functionality on the checkout page.', 'tipjar-checkout-for-woo' ),
				'id'      => 'tipjar_checkout_enabled',
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Title', 'tipjar-checkout-for-woo' ),
				'desc'    => __( 'The title to display above the tip selection.', 'tipjar-checkout-for-woo' ),
				'id'      => 'tipjar_checkout_title',
				'type'    => 'text',
				'default' => __( 'Add a tip', 'tipjar-checkout-for-woo' ),
			),
			array(
				'title'   => __( 'Description', 'tipjar-checkout-for-woo' ),
				'desc'    => __( 'The description to display below the title.', 'tipjar-checkout-for-woo' ),
				'id'      => 'tipjar_checkout_description',
				'type'    => 'textarea',
				'default' => __( 'Choose a tip amount for the team.', 'tipjar-checkout-for-woo' ),
			),
			array(
				'title'   => __( 'Preset Amounts', 'tipjar-checkout-for-woo' ),
				'desc'    => __( 'Enter preset tip amounts, separated by commas.', 'tipjar-checkout-for-woo' ),
				'id'      => 'tipjar_checkout_presets',
				'type'    => 'text',
				'default' => '0, 2, 5, 10',
			),
			array(
				'title'   => __( 'Custom Amount Label', 'tipjar-checkout-for-woo' ),
				'desc'    => __( 'The label for the custom tip amount input field.', 'tipjar-checkout-for-woo' ),
				'id'      => 'tipjar_checkout_custom_label',
				'type'    => 'text',
				'default' => __( 'Custom tip amount', 'tipjar-checkout-for-woo' ),
			),
			array(
				'title'   => __( 'Taxable', 'tipjar-checkout-for-woo' ),
				'desc'    => __( 'Whether the tip is taxable.', 'tipjar-checkout-for-woo' ),
				'id'      => 'tipjar_checkout_taxable',
				'type'    => 'checkbox',
				'default' => 'no',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'tipjar_checkout_options',
			),
		);

		return apply_filters( 'wc_settings_tab_tipjar_checkout_settings', $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();
		WC_Admin_Settings::save_fields( $settings );
	}
}

return new TipJar_Checkout_Settings();