<?php

/**
 * FXUP Admin – Itinerary Change Log Viewer
 * Uses FXUP_Itinerary_Change_Logger to show client updates.
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

use FXUP_User_Portal\Helpers\Itinerary_Change_Logger;

class FXUP_Admin_Itinerary_Log
{

	public function __construct()
	{
		add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_action( 'admin_post_fxup_log_delete', [ $this, 'handle_delete_single' ] );
		add_action( 'admin_post_fxup_log_delete_all', [ $this, 'handle_delete_all' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
		add_filter( 'gform_incomplete_submissions_expiration_days', [ $this, 'change_incomplete_submissions_expiration_days' ] );
	}

	public function change_incomplete_submissions_expiration_days( $expiration_days )
	{
		$expiration_days = 180;
		return $expiration_days;
	}

	public function register_page()
	{
		add_management_page(
			'Itinerary Change Log',
			'Itinerary Log',
			'manage_options',
			'fxup-itinerary-log',
			[ $this, 'render_page' ]
		);
	}

	public function admin_notices()
	{
		if ( ! isset( $_GET['fxup_msg'] ) )
			return;

		$msg = sanitize_text_field( wp_unslash( $_GET['fxup_msg'] ) );
		if ( $msg === 'deleted' ) {
			echo '<div class="notice notice-success is-dismissible"><p>Log entry deleted.</p></div>';
		} elseif ( $msg === 'cleared' ) {
			echo '<div class="notice notice-success is-dismissible"><p>All log entries cleared.</p></div>';
		} elseif ( $msg === 'denied' ) {
			echo '<div class="notice notice-error is-dismissible"><p>Action denied.</p></div>';
		}
	}

	private function redirect_self( array $args = [] )
	{
		$url = add_query_arg( array_merge( [ 'page' => 'fxup-itinerary-log' ], $args ), admin_url( 'admin.php' ) );
		wp_safe_redirect( $url );
		exit;
	}

	/** POST handler: delete a single row */
	public function handle_delete_single()
	{
		if ( ! current_user_can( 'manage_options' ) )
			$this->redirect_self( [ 'fxup_msg' => 'denied' ] );

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		check_admin_referer( 'fxup_log_delete_' . $id );

		if ( $id > 0 ) {
			global $wpdb;
			$table = $wpdb->prefix . 'fxup_itinerary_log';
			$wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
		}
		$this->redirect_self( [ 'fxup_msg' => 'deleted' ] );
	}

	/** POST handler: delete all rows */
	public function handle_delete_all()
	{
		if ( ! current_user_can( 'manage_options' ) )
			$this->redirect_self( [ 'fxup_msg' => 'denied' ] );

		check_admin_referer( 'fxup_log_delete_all_action' );

		global $wpdb;
		$table = $wpdb->prefix . 'fxup_itinerary_log';
		$wpdb->query( "TRUNCATE TABLE {$table}" );

		$this->redirect_self( [ 'fxup_msg' => 'cleared' ] );
	}

	public function render_page()
	{
		global $wpdb;
		$table = $wpdb->prefix . 'fxup_itinerary_log';
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY updated_at DESC", ARRAY_A );

		echo '<div class="wrap"><h1>Client Itinerary Update Log</h1>';

		// Bulk delete
		echo '<form method="post" style="margin:10px 0;">';
		wp_nonce_field( 'fxup_delete_all_action' );
		echo '<input type="submit" name="fxup_delete_all" class="button button-secondary" value="Delete All Logs" onclick="return confirm(\'Are you sure you want to delete all logs?\');">';
		echo '</form>';

		if ( empty( $rows ) ) {
			echo '<p>No logs available.</p></div>';
			return;
		}

		echo '<table class="widefat striped" style="margin-top:15px;">';
		echo '<thead><tr>
				<th>ID</th>
				<th>Itinerary ID</th>
				<th>User ID</th>
				<th>Updated</th>
				<th>Changes</th>
				<th>JSON</th>
				<th>Actions</th>
			  </tr></thead><tbody>';

		$logger = new Itinerary_Change_Logger();
		foreach ( $rows as $r ) {
			$before = json_decode( $r['before_json'] ?: '[]', true );
			$after = json_decode( $r['after_json'] ?: '[]', true );

			$diff = $logger ? $logger->build_diff_summary( $before, $after ) : [ 'text' => 'No changes detected.', 'html' => '' ];

			$delete_url = wp_nonce_url(
				add_query_arg( [ 'fxup_delete' => $r['id'] ] ),
				'fxup_delete_' . $r['id']
			);

			echo '<tr>';
			echo '<td>' . esc_html( $r['id'] ) . '</td>';
			echo '<td>#' . esc_html( $r['itinerary_id'] ) . '</td>';
			echo '<td>#' . esc_html( $r['user_id'] ) . '</td>';
			echo '<td><code>' . esc_html( $r['updated_at'] ) . '</code></td>';
			echo '<td style="white-space:pre-line;">' . wp_kses_post( $diff['text'] ) . '</td>';
			echo '<td>';
			echo '<details><summary>Before</summary><pre style="max-height:300px;overflow:auto;background:#f9f9f9;border:1px solid #ddd;padding:8px;">' .
			esc_html( json_encode( $before, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) .
			'</pre></details>';
			echo '<details><summary>After</summary><pre style="max-height:300px;overflow:auto;background:#f9f9f9;border:1px solid #ddd;padding:8px;">' .
			esc_html( json_encode( $after, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) .
			'</pre></details>';
			echo '</td>';

			echo '<td>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Delete this log entry?\');" style="display:inline;">';
			wp_nonce_field( 'fxup_log_delete_' . (int) $r['id'] );
			echo '<input type="hidden" name="action" value="fxup_log_delete">';
			echo '<input type="hidden" name="id" value="' . (int) $r['id'] . '">';
			echo '<button type="submit" class="button-link-delete">Delete</button>';
			echo '</form>';
			echo '</td>';

			echo '</tr>';
		}

		echo '</tbody></table></div>';
	}
}

new FXUP_Admin_Itinerary_Log();
