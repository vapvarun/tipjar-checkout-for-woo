<?php
/**
 * TipJar at Checkout for Woo Log Page
 *
 * @package TipJar_Checkout_For_Woo
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * TipJar_Checkout_Log Class.
 *
 * @since 2.0.0
 */
class TipJar_Checkout_Log {

	/**
	 * Instance of the log list table.
	 *
	 * @var TipJar_Checkout_Log_List_Table
	 */
	private $log_list_table;

	/**
	 * Initialize the log page.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_log_page' ) );
		add_action( 'admin_init', array( $this, 'export_tips_csv' ) );
	}

	/**
	 * Add the log page to the admin menu.
	 */
	public function add_log_page() {
		$hook = add_submenu_page(
			'woocommerce',
			esc_html__( 'Tip Log', 'tipjar-checkout-for-woo' ),
			esc_html__( 'Tip Log', 'tipjar-checkout-for-woo' ),
			'manage_woocommerce',
			'tipjar-checkout-log',
			array( $this, 'render_log_page' )
		);

		add_action( "load-{$hook}", array( $this, 'load_list_table' ) );
	}

	/**
	 * Load the list table.
	 */
	public function load_list_table() {
		require_once __DIR__ . '/class-tipjar-checkout-log-list-table.php';
		$this->log_list_table = new TipJar_Checkout_Log_List_Table();
	}

	/**
	 * Render the log page.
	 */
	public function render_log_page() {
		$this->log_list_table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Tip Log', 'tipjar-checkout-for-woo' ); ?></h1>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'export_tips' ), 'export_tips_nonce', 'export_tips_nonce' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Export to CSV', 'tipjar-checkout-for-woo' ); ?>
			</a>
			<p><?php esc_html_e( 'A list of all tips collected through the checkout.', 'tipjar-checkout-for-woo' ); ?></p>
			<form method="post">
				<input type="hidden" name="page" value="tipjar-checkout-log" />
				<?php
				$this->log_list_table->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Export tips to a CSV file.
	 */
	public function export_tips_csv() {
		if ( ! isset( $_GET['action'] ) || 'export_tips' !== $_GET['action'] ) {
			return;
		}

		if ( ! isset( $_GET['export_tips_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['export_tips_nonce'] ), 'export_tips_nonce' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'tipjar-checkout-for-woo' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export tips.', 'tipjar-checkout-for-woo' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . TIPJAR_CHECKOUT_LOG_TABLE;
		$data       = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY date_created DESC", ARRAY_A );

		if ( empty( $data ) ) {
			return;
		}

		$filename = 'tipjar-export-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		// Add column headers.
		fputcsv( $output, array( 'Order ID', 'Tip Amount', 'Date' ) );

		// Add data rows.
		foreach ( $data as $row ) {
			fputcsv( $output, array( $row['order_id'], $row['tip_amount'], $row['date_created'] ) );
		}

		fclose( $output );
		exit;
	}
}