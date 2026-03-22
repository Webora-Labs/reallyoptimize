<?php
/**
 * Plugin Name: Really Optimize
 * Plugin URI:  https://wordpress.org/plugins/really-optimize
 * Description: Image optimization plugin for WordPress — compress, convert to AVIF/WebP, lazy load, and bulk optimize.
 * Version:     1.0.0
 * Author:      Really Optimize
 * Author URI:  https://wordpress.org/plugins/really-optimize
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: really-optimize
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REALLY_OPTIMIZE_VERSION', '1.0.0' );
define( 'REALLY_OPTIMIZE_FILE', __FILE__ );
define( 'REALLY_OPTIMIZE_DIR', plugin_dir_path( __FILE__ ) );
define( 'REALLY_OPTIMIZE_URL', plugin_dir_url( __FILE__ ) );

require_once REALLY_OPTIMIZE_DIR . 'includes/class-really-optimize.php';

function really_optimize() {
	return Really_Optimize::instance();
}

really_optimize();
