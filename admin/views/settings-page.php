<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET param for tab navigation, not form processing.
$webora_image_optimizer_active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'images';
$webora_image_optimizer_tabs = array(
	'images' => __( 'Image Optimization', 'webora-image-optimizer' ),
	'bulk'   => __( 'Bulk Optimize', 'webora-image-optimizer' ),
);
?>
<div class="wrap webora-optimize-wrap">
	<h1><?php esc_html_e( 'Webora Image Optimizer', 'webora-image-optimizer' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $webora_image_optimizer_tabs as $webora_image_optimizer_slug => $webora_image_optimizer_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'webora-image-optimizer', 'tab' => $webora_image_optimizer_slug ), admin_url( 'options-general.php' ) ) ); ?>"
			   class="nav-tab <?php echo $webora_image_optimizer_active_tab === $webora_image_optimizer_slug ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $webora_image_optimizer_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="">
		<?php wp_nonce_field( 'webora_image_optimizer_save', 'webora_image_optimizer_nonce' ); ?>
		<input type="hidden" name="webora_image_optimizer_tab" value="<?php echo esc_attr( $webora_image_optimizer_active_tab ); ?>" />

		<?php if ( 'images' === $webora_image_optimizer_active_tab ) :
			$webora_image_optimizer_cli_status = Webora_Image_Optimizer_CLI::status();
			$webora_image_optimizer_cli_paths  = $settings['cli_paths'];
		?>
		<div class="webora-tab-panel">

			<div class="webora-card">
				<h2><?php esc_html_e( 'Compression', 'webora-image-optimizer' ); ?></h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Compression', 'webora-image-optimizer' ); ?></th>
						<td>
							<label class="webora-toggle">
								<input type="checkbox" name="img_compress" value="1"
									<?php checked( $settings['img_compress'] ); ?> id="img_compress" />
								<span class="webora-toggle__slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Compress images on upload to reduce file size.', 'webora-image-optimizer' ); ?></p>
						</td>
					</tr>
					<tr class="webora-depends-on-compress">
						<th scope="row">
							<label for="img_quality"><?php esc_html_e( 'Quality', 'webora-image-optimizer' ); ?></label>
						</th>
						<td>
							<div class="webora-quality-row">
								<input type="range" id="img_quality_range" min="1" max="100"
									value="<?php echo esc_attr( $settings['img_quality'] ); ?>"
									class="webora-range" />
								<input type="number" name="img_quality" id="img_quality" min="1" max="100"
									value="<?php echo esc_attr( $settings['img_quality'] ); ?>"
									class="small-text" />
								<span>%</span>
							</div>
							<p class="description"><?php esc_html_e( 'Image quality for JPEG and WebP (1-100). Recommended: 80-90.', 'webora-image-optimizer' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="webora-card">
				<h2><?php esc_html_e( 'Next-Gen Formats', 'webora-image-optimizer' ); ?></h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Convert to AVIF', 'webora-image-optimizer' ); ?></th>
						<td>
							<label class="webora-toggle">
								<input type="checkbox" name="img_avif" value="1"
									<?php checked( $settings['img_avif'] ); ?> />
								<span class="webora-toggle__slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Convert JPEG/PNG to AVIF on upload. Best compression (30-50% smaller than WebP). Takes priority over WebP if both enabled.', 'webora-image-optimizer' ); ?>
								<?php if ( ! function_exists( 'imageavif' ) ) : ?>
									<br><strong class="webora-warning"><?php esc_html_e( 'AVIF not supported (requires PHP 8.1+ and GD with AVIF).', 'webora-image-optimizer' ); ?></strong>
								<?php else : ?>
									<br><span class="webora-badge webora-badge--ok"><?php esc_html_e( 'AVIF supported', 'webora-image-optimizer' ); ?></span>
								<?php endif; ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Convert to WebP', 'webora-image-optimizer' ); ?></th>
						<td>
							<label class="webora-toggle">
								<input type="checkbox" name="img_webp" value="1"
									<?php checked( $settings['img_webp'] ); ?> />
								<span class="webora-toggle__slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Automatically convert JPEG and PNG images to WebP on upload.', 'webora-image-optimizer' ); ?>
								<?php if ( ! function_exists( 'imagewebp' ) ) : ?>
									<br><strong class="webora-warning"><?php esc_html_e( 'Your server does not support WebP (GD library missing imagewebp).', 'webora-image-optimizer' ); ?></strong>
								<?php endif; ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="webora-card">
				<h2><?php esc_html_e( 'Loading and Dimensions', 'webora-image-optimizer' ); ?></h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Lazy Load Images', 'webora-image-optimizer' ); ?></th>
						<td>
							<label class="webora-toggle">
								<input type="checkbox" name="img_lazy_load" value="1"
									<?php checked( $settings['img_lazy_load'] ); ?> />
								<span class="webora-toggle__slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Add loading="lazy" to all images in content.', 'webora-image-optimizer' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Add Width and Height', 'webora-image-optimizer' ); ?></th>
						<td>
							<label class="webora-toggle">
								<input type="checkbox" name="img_add_dimensions" value="1"
									<?php checked( $settings['img_add_dimensions'] ); ?> />
								<span class="webora-toggle__slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Add width and height attributes to local images that are missing them. Prevents layout shift (CLS).', 'webora-image-optimizer' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Max Dimensions', 'webora-image-optimizer' ); ?></th>
						<td>
							<label>
								<?php esc_html_e( 'Width', 'webora-image-optimizer' ); ?>
								<input type="number" name="img_max_width" min="100" max="9999"
									value="<?php echo esc_attr( $settings['img_max_width'] ); ?>"
									class="small-text" /> px
							</label>
							&nbsp;&nbsp;
							<label>
								<?php esc_html_e( 'Height', 'webora-image-optimizer' ); ?>
								<input type="number" name="img_max_height" min="100" max="9999"
									value="<?php echo esc_attr( $settings['img_max_height'] ); ?>"
									class="small-text" /> px
							</label>
							<p class="description"><?php esc_html_e( 'Images exceeding these dimensions will be resized on upload.', 'webora-image-optimizer' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="webora-card">
				<h2><?php esc_html_e( 'CLI Tools', 'webora-image-optimizer' ); ?></h2>

				<?php if ( ! Webora_Image_Optimizer_CLI::exec_available() ) : ?>
					<p class="webora-warning">
						<?php esc_html_e( 'PHP exec() is disabled on this server. CLI tools cannot be used. Falling back to GD/Imagick.', 'webora-image-optimizer' ); ?>
					</p>
				<?php else : ?>

				<table class="widefat webora-tools-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tool', 'webora-image-optimizer' ); ?></th>
							<th><?php esc_html_e( 'Format', 'webora-image-optimizer' ); ?></th>
							<th><?php esc_html_e( 'Status', 'webora-image-optimizer' ); ?></th>
							<th><?php esc_html_e( 'Path detected', 'webora-image-optimizer' ); ?></th>
							<th><?php esc_html_e( 'Custom path (optional)', 'webora-image-optimizer' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $webora_image_optimizer_cli_status as $webora_image_optimizer_key => $webora_image_optimizer_tool ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $webora_image_optimizer_tool['label'] ); ?></strong></td>
							<td><?php echo esc_html( $webora_image_optimizer_tool['purpose'] ); ?></td>
							<td>
								<?php if ( $webora_image_optimizer_tool['available'] ) : ?>
									<span class="webora-badge webora-badge--ok"><?php esc_html_e( 'Found', 'webora-image-optimizer' ); ?></span>
								<?php else : ?>
									<span class="webora-badge webora-badge--missing"><?php esc_html_e( 'Not found', 'webora-image-optimizer' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<code><?php echo $webora_image_optimizer_tool['path'] ? esc_html( $webora_image_optimizer_tool['path'] ) : '-'; ?></code>
							</td>
							<td>
								<input type="text" name="cli_paths[<?php echo esc_attr( $webora_image_optimizer_key ); ?>]"
									value="<?php echo esc_attr( isset( $webora_image_optimizer_cli_paths[ $webora_image_optimizer_key ] ) ? $webora_image_optimizer_cli_paths[ $webora_image_optimizer_key ] : '' ); ?>"
									placeholder="/usr/bin/<?php echo esc_attr( $webora_image_optimizer_tool['label'] ); ?>"
									class="regular-text" />
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="description" style="margin-top:8px;">
					<?php esc_html_e( 'Leave custom path empty to use auto-detection.', 'webora-image-optimizer' ); ?>
				</p>

				<?php endif; ?>
			</div>

		</div>
		<?php endif; ?>

		<?php if ( 'bulk' === $webora_image_optimizer_active_tab ) :
			$webora_image_optimizer_bulk_total = Webora_Image_Optimizer_Bulk::count_total();
			$webora_image_optimizer_bulk_done  = Webora_Image_Optimizer_Bulk::count_done();
			$webora_image_optimizer_bulk_pct   = $webora_image_optimizer_bulk_total > 0 ? round( $webora_image_optimizer_bulk_done / $webora_image_optimizer_bulk_total * 100 ) : 0;
		?>
		<div class="webora-tab-panel">

			<div class="webora-card">
				<h2><?php esc_html_e( 'Bulk Image Optimization', 'webora-image-optimizer' ); ?></h2>

				<div class="webora-bulk-stats">
					<div class="webora-stat">
						<span class="webora-stat__value" id="webora-bulk-total"><?php echo (int) $webora_image_optimizer_bulk_total; ?></span>
						<span class="webora-stat__label"><?php esc_html_e( 'Total images', 'webora-image-optimizer' ); ?></span>
					</div>
					<div class="webora-stat">
						<span class="webora-stat__value" id="webora-bulk-done"><?php echo (int) $webora_image_optimizer_bulk_done; ?></span>
						<span class="webora-stat__label"><?php esc_html_e( 'Optimized', 'webora-image-optimizer' ); ?></span>
					</div>
					<div class="webora-stat">
						<span class="webora-stat__value" id="webora-bulk-remaining"><?php echo (int) max( 0, $webora_image_optimizer_bulk_total - $webora_image_optimizer_bulk_done ); ?></span>
						<span class="webora-stat__label"><?php esc_html_e( 'Remaining', 'webora-image-optimizer' ); ?></span>
					</div>
				</div>

				<div class="webora-progress-wrap">
					<div class="webora-progress">
						<div class="webora-progress__bar" id="webora-progress-bar" style="width:<?php echo (int) $webora_image_optimizer_bulk_pct; ?>%"></div>
					</div>
					<span class="webora-progress__pct" id="webora-progress-pct"><?php echo (int) $webora_image_optimizer_bulk_pct; ?>%</span>
				</div>

				<div class="webora-bulk-options">
					<label>
						<input type="checkbox" id="webora-skip-done" checked />
						<?php esc_html_e( 'Skip already optimized images', 'webora-image-optimizer' ); ?>
					</label>
				</div>

				<div class="webora-bulk-actions">
					<button type="button" class="button button-primary" id="webora-bulk-start">
						<?php esc_html_e( 'Start Optimization', 'webora-image-optimizer' ); ?>
					</button>
					<button type="button" class="button" id="webora-bulk-pause" style="display:none">
						<?php esc_html_e( 'Pause', 'webora-image-optimizer' ); ?>
					</button>
					<button type="button" class="button" id="webora-bulk-reset">
						<?php esc_html_e( 'Reset Marks', 'webora-image-optimizer' ); ?>
					</button>
					<span class="webora-bulk-status" id="webora-bulk-status"></span>
				</div>
			</div>

			<div class="webora-card">
				<h2><?php esc_html_e( 'Log', 'webora-image-optimizer' ); ?></h2>
				<div class="webora-log" id="webora-bulk-log">
					<p class="webora-log__empty"><?php esc_html_e( 'Log will appear here during processing.', 'webora-image-optimizer' ); ?></p>
				</div>
			</div>

		</div>
		<?php endif; ?>

		<?php if ( 'bulk' !== $webora_image_optimizer_active_tab ) : ?>
			<?php submit_button( __( 'Save Settings', 'webora-image-optimizer' ) ); ?>
		<?php endif; ?>
	</form>
</div>
