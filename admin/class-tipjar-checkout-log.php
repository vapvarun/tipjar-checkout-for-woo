<?php
/**
 * TipJar at Checkout for Woo Log.
 *
 * @package TipJar_Checkout_For_Woo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class TipJar_Checkout_Log.
 */
class TipJar_Checkout_Log {

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
		add_submenu_page(
			'woocommerce',
			__( 'Tip Log', 'tipjar-checkout-for-woo' ),
			__( 'Tip Log', 'tipjar-checkout-for-woo' ),
			'manage_woocommerce',
			'tipjar-checkout-log',
			array( $this, 'render_log_page' )
		);
	}

	/**
	 * Render the log page.
	 */
	public function render_log_page() {
		require_once __DIR__ . '/class-tipjar-checkout-log-list-table.php';

		$log_list_table = new TipJar_Checkout_Log_List_Table();
		$log_list_table->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html__( 'Tip Log', 'tipjar-checkout-for-woo' ); ?></h1>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'export_tips' ), 'export_tips_nonce', 'export_tips_nonce' ) ); ?>" class="page-title-action">
				<?php echo esc_html__( 'Export to CSV', 'tipjar-checkout-for-woo' ); ?>
			</a>
			<p><?php echo esc_html__( 'A list of all tips collected through the checkout.', 'tipjar-checkout-for-woo' ); ?></p>
			<form method="post">
				<?php
				$log_list_table->display();
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

		$filename = 'tipjar-export-' . date( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, array( 'Order ID', 'Tip Amount', 'Date' ) );

		foreach ( $data as $row ) {
			fputcsv( $output, array( $row['order_id'], $row['tip_amount'], $row['date_created'] ) );
		}

		fclose( $output );
		exit;
	}
}