<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Really_Optimize {

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
		require_once REALLY_OPTIMIZE_DIR . 'includes/class-really-optimize-settings.php';
		require_once REALLY_OPTIMIZE_DIR . 'includes/class-really-optimize-cli.php';
		require_once REALLY_OPTIMIZE_DIR . 'includes/class-really-optimize-images.php';
		require_once REALLY_OPTIMIZE_DIR . 'includes/class-really-optimize-bulk.php';

		if ( is_admin() ) {
			require_once REALLY_OPTIMIZE_DIR . 'admin/class-really-optimize-admin.php';
		}
	}

	public function init_modules() {
		new Really_Optimize_Images();
	}

	private function hooks() {
		register_activation_hook( REALLY_OPTIMIZE_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( REALLY_OPTIMIZE_FILE, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init',           array( $this, 'init_modules' ) );
	}

	public function activate() {
		if ( ! get_option( 'really_optimize_settings' ) ) {
			add_option( 'really_optimize_settings', Really_Optimize_Settings::defaults() );
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
