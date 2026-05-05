<?php
/**
 * Plugin Name: Webora Image Optimizer
 * Plugin URI:  https://github.com/Webora-Labs/reallyoptimize
 * Description: Image optimization plugin for WordPress — compress, convert to AVIF/WebP, lazy load, and bulk optimize.
 * Version:     1.0.0
 * Author:      Webora Labs
 * Author URI:  https://github.com/Webora-Labs
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: webora-image-optimizer
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WEBORA_IMAGE_OPTIMIZER_VERSION', '1.0.0' );
define( 'WEBORA_IMAGE_OPTIMIZER_FILE', __FILE__ );
define( 'WEBORA_IMAGE_OPTIMIZER_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEBORA_IMAGE_OPTIMIZER_URL', plugin_dir_url( __FILE__ ) );

require_once WEBORA_IMAGE_OPTIMIZER_DIR . 'includes/class-webora-image-optimizer.php';

function webora_image_optimizer() {
	return Webora_Image_Optimizer::instance();
}

webora_image_optimizer();
