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
			array(
				'title' => __( 'Conditional Display', 'tipjar-checkout-for-woo' ),
				'type'  => 'title',
				'id'    => 'tipjar_checkout_conditional_options',
				'desc'  => __( 'Only show the tip field if the following conditions are met. Leave all conditions empty to show for all orders.', 'tipjar-checkout-for-woo' ),
			),
			array(
				'title'             => __( 'Required Products', 'tipjar-checkout-for-woo' ),
				'desc'              => __( 'Only show the tip field if these products are in the cart.', 'tipjar-checkout-for-woo' ),
				'id'                => 'tipjar_checkout_required_products',
				'type'              => 'multiselect',
				'class'             => 'wc-product-search',
				'options'           => $this->get_products_for_options(),
				'custom_attributes' => array(
					'data-placeholder' => __( 'Search for products…', 'tipjar-checkout-for-woo' ),
					'data-multiple'    => 'true',
				),
			),
			array(
				'title'             => __( 'Required Categories', 'tipjar-checkout-for-woo' ),
				'desc'              => __( 'Only show the tip field if products from these categories are in the cart.', 'tipjar-checkout-for-woo' ),
				'id'                => 'tipjar_checkout_required_categories',
				'type'              => 'multiselect',
				'class'             => 'wc-category-search',
				'options'           => $this->get_categories_for_options(),
				'custom_attributes' => array(
					'data-placeholder' => __( 'Search for categories…', 'tipjar-checkout-for-woo' ),
					'data-multiple'    => 'true',
				),
			),
			array(
				'title'             => __( 'Minimum Cart Total', 'tipjar-checkout-for-woo' ),
				'desc'              => __( 'The minimum cart total required to show the tip field.', 'tipjar-checkout-for-woo' ),
				'id'                => 'tipjar_checkout_min_total',
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '0.01',
					'min'  => '0',
				),
			),
			array(
				'title'             => __( 'Maximum Cart Total', 'tipjar-checkout-for-woo' ),
				'desc'              => __( 'The maximum cart total allowed to show the tip field.', 'tipjar-checkout-for-woo' ),
				'id'                => 'tipjar_checkout_max_total',
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '0.01',
					'min'  => '0',
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'tipjar_checkout_conditional_options',
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

	/**
	 * Get saved products for the multiselect option.
	 *
	 * @return array
	 */
	private function get_products_for_options() {
		$product_ids = get_option( 'tipjar_checkout_required_products', array() );
		$products    = array();
		if ( ! empty( $product_ids ) && is_array( $product_ids ) ) {
			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( is_object( $product ) ) {
					$products[ $product_id ] = wp_strip_all_tags( $product->get_formatted_name() );
				}
			}
		}
		return $products;
	}

	/**
	 * Get saved categories for the multiselect option.
	 *
	 * @return array
	 */
	private function get_categories_for_options() {
		$category_ids = get_option( 'tipjar_checkout_required_categories', array() );
		$categories   = array();
		if ( ! empty( $category_ids ) && is_array( $category_ids ) ) {
			foreach ( $category_ids as $category_id ) {
				$term = get_term( $category_id, 'product_cat' );
				if ( ! is_wp_error( $term ) && $term ) {
					$categories[ $term->term_id ] = $term->name;
				}
			}
		}
		return $categories;
	}
}

return new TipJar_Checkout_Settings();