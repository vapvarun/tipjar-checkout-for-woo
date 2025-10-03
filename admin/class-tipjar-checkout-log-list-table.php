<?php
/**
 * TipJar at Checkout for Woo Log List Table.
 *
 * @package TipJar_Checkout_For_Woo
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class TipJar_Checkout_Log_List_Table.
 */
class TipJar_Checkout_Log_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Tip Log Entry', 'tipjar-checkout-for-woo' ),
				'plural'   => __( 'Tip Log Entries', 'tipjar-checkout-for-woo' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get the columns for the table.
	 *
	 * @return array The columns.
	 */
	public function get_columns() {
		return array(
			'order_id'     => __( 'Order', 'tipjar-checkout-for-woo' ),
			'tip_amount'   => __( 'Tip Amount', 'tipjar-checkout-for-woo' ),
			'date_created' => __( 'Date', 'tipjar-checkout-for-woo' ),
		);
	}

	/**
	 * Prepare the items for the table.
	 */
	public function prepare_items() {
		global $wpdb;

		$table_name = $wpdb->prefix . TIPJAR_CHECKOUT_LOG_TABLE;
		$per_page   = 20;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY date_created DESC", ARRAY_A );
	}

	/**
	 * Render a single column.
	 *
	 * @param array  $item The item being rendered.
	 * @param string $column_name The name of the column.
	 * @return string The column content.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'order_id':
				$order_url = admin_url( 'post.php?post=' . $item['order_id'] . '&action=edit' );
				return '<a href="' . esc_url( $order_url ) . '">' . esc_html( $item['order_id'] ) . '</a>';
			case 'tip_amount':
				return wc_price( $item['tip_amount'] );
			case 'date_created':
				return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['date_created'] ) );
			default:
				return '';
		}
	}
}