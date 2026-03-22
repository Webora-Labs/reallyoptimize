<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Really_Optimize_Admin {

	public function __construct() {
		add_action( 'admin_menu',               array( $this, 'add_menu' ) );
		add_action( 'admin_init',               array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts',    array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_really_bulk_run',   array( $this, 'ajax_bulk_run' ) );
		add_action( 'wp_ajax_really_bulk_reset', array( $this, 'ajax_bulk_reset' ) );
	}

	public function add_menu() {
		add_options_page(
			__( 'Really Optimize', 'really-optimize' ),
			__( 'Really Optimize', 'really-optimize' ),
			'manage_options',
			'really-optimize',
			array( $this, 'render_page' )
		);
	}

	public function handle_save() {
		if (
			! isset( $_POST['really_optimize_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['really_optimize_nonce'] ) ), 'really_optimize_save' )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		Really_Optimize_Settings::save( $_POST );

		do_action( 'really_optimize_settings_saved' );

		add_settings_error(
			'really_optimize',
			'saved',
			__( 'Settings saved.', 'really-optimize' ),
			'updated'
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'settings_page_really-optimize' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'really-optimize-admin',
			REALLY_OPTIMIZE_URL . 'assets/css/admin.css',
			array(),
			REALLY_OPTIMIZE_VERSION
		);

		wp_enqueue_script(
			'really-optimize-admin',
			REALLY_OPTIMIZE_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			REALLY_OPTIMIZE_VERSION,
			true
		);

		wp_localize_script( 'really-optimize-admin', 'reallyOptimize', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'really_bulk' ),
			'i18n'      => array(
				'starting'   => __( 'Starting…', 'really-optimize' ),
				'processing' => __( 'Processing…', 'really-optimize' ),
				'done'       => __( 'All done!', 'really-optimize' ),
				'error'      => __( 'Error. Check console.', 'really-optimize' ),
				'paused'     => __( 'Paused.', 'really-optimize' ),
				'resetDone'  => __( 'Reset complete.', 'really-optimize' ),
			),
		) );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		settings_errors( 'really_optimize' );
		$settings = Really_Optimize_Settings::get();
		require_once REALLY_OPTIMIZE_DIR . 'admin/views/settings-page.php';
	}

	// -----------------------------------------------------------------------
	// AJAX: process one batch
	// -----------------------------------------------------------------------

	public function ajax_bulk_run() {
		check_ajax_referer( 'really_bulk', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$offset    = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$skip_done = ! empty( $_POST['skip_done'] );

		$result = Really_Optimize_Bulk::process_batch( $offset, $skip_done );

		wp_send_json_success( $result );
	}

	// -----------------------------------------------------------------------
	// AJAX: reset optimization marks
	// -----------------------------------------------------------------------

	public function ajax_bulk_reset() {
		check_ajax_referer( 'really_bulk', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		Really_Optimize_Bulk::reset();

		wp_send_json_success( array(
			'total' => Really_Optimize_Bulk::count_total(),
			'done'  => 0,
		) );
	}
}

new Really_Optimize_Admin();
