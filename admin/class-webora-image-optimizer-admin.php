<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Webora_Image_Optimizer_Admin {

	public function __construct() {
		add_action( 'admin_menu',               array( $this, 'add_menu' ) );
		add_action( 'admin_init',               array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts',    array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_webora_bulk_run',   array( $this, 'ajax_bulk_run' ) );
		add_action( 'wp_ajax_webora_bulk_reset', array( $this, 'ajax_bulk_reset' ) );
	}

	public function add_menu() {
		add_options_page(
			__( 'Webora Image Optimizer', 'webora-image-optimizer' ),
			__( 'Webora Image Optimizer', 'webora-image-optimizer' ),
			'manage_options',
			'webora-image-optimizer',
			array( $this, 'render_page' )
		);
	}

	public function handle_save() {
		if (
			! isset( $_POST['webora_image_optimizer_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['webora_image_optimizer_nonce'] ) ), 'webora_image_optimizer_save' )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		Webora_Image_Optimizer_Settings::save( $_POST );

		do_action( 'webora_image_optimizer_settings_saved' );

		add_settings_error(
			'webora_image_optimizer',
			'saved',
			__( 'Settings saved.', 'webora-image-optimizer' ),
			'updated'
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'settings_page_webora-image-optimizer' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'webora-image-optimizer-admin',
			WEBORA_IMAGE_OPTIMIZER_URL . 'assets/css/admin.css',
			array(),
			WEBORA_IMAGE_OPTIMIZER_VERSION
		);

		wp_enqueue_script(
			'webora-image-optimizer-admin',
			WEBORA_IMAGE_OPTIMIZER_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			WEBORA_IMAGE_OPTIMIZER_VERSION,
			true
		);

		wp_localize_script( 'webora-image-optimizer-admin', 'weboraImageOptimizer', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'webora_bulk' ),
			'i18n'      => array(
				'starting'   => __( 'Starting…', 'webora-image-optimizer' ),
				'processing' => __( 'Processing…', 'webora-image-optimizer' ),
				'done'       => __( 'All done!', 'webora-image-optimizer' ),
				'error'      => __( 'Error. Check console.', 'webora-image-optimizer' ),
				'paused'     => __( 'Paused.', 'webora-image-optimizer' ),
				'resetDone'  => __( 'Reset complete.', 'webora-image-optimizer' ),
			),
		) );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		settings_errors( 'webora_image_optimizer' );
		$settings = Webora_Image_Optimizer_Settings::get();
		require_once WEBORA_IMAGE_OPTIMIZER_DIR . 'admin/views/settings-page.php';
	}

	// -----------------------------------------------------------------------
	// AJAX: process one batch
	// -----------------------------------------------------------------------

	public function ajax_bulk_run() {
		check_ajax_referer( 'webora_bulk', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		$offset    = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$skip_done = ! empty( $_POST['skip_done'] );

		$result = Webora_Image_Optimizer_Bulk::process_batch( $offset, $skip_done );

		wp_send_json_success( $result );
	}

	// -----------------------------------------------------------------------
	// AJAX: reset optimization marks
	// -----------------------------------------------------------------------

	public function ajax_bulk_reset() {
		check_ajax_referer( 'webora_bulk', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Forbidden', 403 );
		}

		Webora_Image_Optimizer_Bulk::reset();

		wp_send_json_success( array(
			'total' => Webora_Image_Optimizer_Bulk::count_total(),
			'done'  => 0,
		) );
	}
}

new Webora_Image_Optimizer_Admin();
