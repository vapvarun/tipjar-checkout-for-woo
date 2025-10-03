<?php
/**
 * TipJar at Checkout for Woo Log List Table
 *
 * @package TipJar_Checkout_For_Woo
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * TipJar_Checkout_Log_List_Table Class.
 *
 * @since 2.0.0
 */
class TipJar_Checkout_Log_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => esc_html__( 'Tip Log Entry', 'tipjar-checkout-for-woo' ),
				'plural'   => esc_html__( 'Tip Log Entries', 'tipjar-checkout-for-woo' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get the columns for the table.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'order_id'     => esc_html__( 'Order', 'tipjar-checkout-for-woo' ),
			'tip_amount'   => esc_html__( 'Tip Amount', 'tipjar-checkout-for-woo' ),
			'date_created' => esc_html__( 'Date', 'tipjar-checkout-for-woo' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'order_id'     => array( 'order_id', false ),
			'tip_amount'   => array( 'tip_amount', false ),
			'date_created' => array( 'date_created', true ), // True for default sorting.
		);
	}

	/**
	 * Prepare the items for the table.
	 */
	public function prepare_items() {
		global $wpdb;

		$table_name = $wpdb->prefix . TIPJAR_CHECKOUT_LOG_TABLE;
		$per_page   = 20;
		$columns    = $this->get_columns();
		$hidden     = array();
		$sortable   = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Orderby.
		$orderby = ( ! empty( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $sortable ), true ) ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'date_created';
		// Order.
		$order = ( ! empty( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), array( 'ASC', 'DESC' ), true ) ) ? strtoupper( $_REQUEST['order'] ) : 'DESC';

		$total_items = $wpdb->get_var( "SELECT COUNT(tip_id) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$per_page,
				$offset
			),
			ARRAY_A
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
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
				$order = wc_get_order( $item['order_id'] );
				if ( $order ) {
					$order_url = $order->get_edit_order_url();
					return '<a href="' . esc_url( $order_url ) . '">' . esc_html( $order->get_order_number() ) . '</a>';
				}
				return esc_html( $item['order_id'] );
			case 'tip_amount':
				return wc_price( $item['tip_amount'] );
			case 'date_created':
				return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['date_created'] ) );
			default:
				return '';
		}
	}

	/**
	 * Message to be displayed when there are no items.
	 */
	public function no_items() {
		esc_html_e( 'No tips found.', 'tipjar-checkout-for-woo' );
	}
}