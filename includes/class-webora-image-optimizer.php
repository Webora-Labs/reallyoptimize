<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Webora_Image_Optimizer {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	private function includes() {
		require_once WEBORA_IMAGE_OPTIMIZER_DIR . 'includes/class-webora-image-optimizer-settings.php';
		require_once WEBORA_IMAGE_OPTIMIZER_DIR . 'includes/class-webora-image-optimizer-cli.php';
		require_once WEBORA_IMAGE_OPTIMIZER_DIR . 'includes/class-webora-image-optimizer-images.php';
		require_once WEBORA_IMAGE_OPTIMIZER_DIR . 'includes/class-webora-image-optimizer-bulk.php';

		if ( is_admin() ) {
			require_once WEBORA_IMAGE_OPTIMIZER_DIR . 'admin/class-webora-image-optimizer-admin.php';
		}
	}

	public function init_modules() {
		new Webora_Image_Optimizer_Images();
	}

	private function hooks() {
		register_activation_hook( WEBORA_IMAGE_OPTIMIZER_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( WEBORA_IMAGE_OPTIMIZER_FILE, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init',           array( $this, 'init_modules' ) );
	}

	public function activate() {
		if ( ! get_option( 'webora_image_optimizer_settings' ) ) {
			add_option( 'webora_image_optimizer_settings', Webora_Image_Optimizer_Settings::defaults() );
		}
	}

	public function deactivate() {
		// Nothing to clean up for images-only mode.
	}

	/**
	 * Textdomain loading is handled automatically by WordPress 4.6+ for
	 * plugins hosted on WordPress.org. No manual call needed.
	 */
	public function load_textdomain() {
		// Intentionally empty — WordPress auto-loads translations for wp.org plugins.
	}
}
