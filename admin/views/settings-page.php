<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET param for tab navigation, not form processing.
$really_optimize_active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'images';
$really_optimize_tabs = array(
	'images' => __( 'Image Optimization', 'really-optimize' ),
	'bulk'   => __( 'Bulk Optimize', 'really-optimize' ),
);
?>
<div class="wrap really-optimize-wrap">
	<h1><?php esc_html_e( 'Really Optimize', 'really-optimize' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $really_optimize_tabs as $really_optimize_slug => $really_optimize_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'really-optimize', 'tab' => $really_optimize_slug ), admin_url( 'options-general.php' ) ) ); ?>"
			   class="nav-tab <?php echo $really_optimize_active_tab === $really_optimize_slug ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $really_optimize_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<form method="post" action="">
		<?php wp_nonce_field( 'really_optimize_save', 'really_optimize_nonce' ); ?>
		<input type="hidden" name="really_optimize_tab" value="<?php echo esc_attr( $really_optimize_active_tab ); ?>" />

		<?php if ( 'images' === $really_optimize_active_tab ) :
			$really_optimize_cli_status = Really_Optimize_CLI::status();
			$really_optimize_cli_paths  = $settings['cli_paths'];
		?>
		<div class="really-tab-panel">

			<div class="really-card">
				<h2><?php esc_html_e( 'Compression', 'really-optimize' ); ?></h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Compression', 'really-optimize' ); ?></th>
						<td>
							<label class="really-toggle">
								<input type="checkbox" name="img_compress" value="1"
									<?php checked( $settings['img_compress'] ); ?> id="img_compress" />
								<span class="really-toggle__slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Compress images on upload to reduce file size.', 'really-optimize' ); ?></p>
						</td>
					</tr>
					<tr class="really-depends-on-compress">
						<th scope="row">
							<label for="img_quality"><?php esc_html_e( 'Quality', 'really-optimize' ); ?></label>
						</th>
						<td>
							<div class="really-quality-row">
								<input type="range" id="img_quality_range" min="1" max="100"
									value="<?php echo esc_attr( $settings['img_quality'] ); ?>"
									class="really-range" />
								<input type="number" name="img_quality" id="img_quality" min="1" max="100"
									value="<?php echo esc_attr( $settings['img_quality'] ); ?>"
									class="small-text" />
								<span>%</span>
							</div>
							<p class="description"><?php esc_html_e( 'Image quality for JPEG and WebP (1-100). Recommended: 80-90.', 'really-optimize' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="really-card">
				<h2><?php esc_html_e( 'Next-Gen Formats', 'really-optimize' ); ?></h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Convert to AVIF', 'really-optimize' ); ?></th>
						<td>
							<label class="really-toggle">
								<input type="checkbox" name="img_avif" value="1"
									<?php checked( $settings['img_avif'] ); ?> />
								<span class="really-toggle__slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Convert JPEG/PNG to AVIF on upload. Best compression (30-50% smaller than WebP). Takes priority over WebP if both enabled.', 'really-optimize' ); ?>
								<?php if ( ! function_exists( 'imageavif' ) ) : ?>
									<br><strong class="really-warning"><?php esc_html_e( 'AVIF not supported (requires PHP 8.1+ and GD with AVIF).', 'really-optimize' ); ?></strong>
								<?php else : ?>
									<br><span class="really-badge really-badge--ok"><?php esc_html_e( 'AVIF supported', 'really-optimize' ); ?></span>
								<?php endif; ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Convert to WebP', 'really-optimize' ); ?></th>
						<td>
							<label class="really-toggle">
								<input type="checkbox" name="img_webp" value="1"
									<?php checked( $settings['img_webp'] ); ?> />
								<span class="really-toggle__slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Automatically convert JPEG and PNG images to WebP on upload.', 'really-optimize' ); ?>
								<?php if ( ! function_exists( 'imagewebp' ) ) : ?>
									<br><strong class="really-warning"><?php esc_html_e( 'Your server does not support WebP (GD library missing imagewebp).', 'really-optimize' ); ?></strong>
								<?php endif; ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<div class="really-card">
				<h2><?php esc_html_e( 'Loading and Dimensions', 'really-optimize' ); ?></h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Lazy Load Images', 'really-optimize' ); ?></th>
						<td>
							<label class="really-toggle">
								<input type="checkbox" name="img_lazy_load" value="1"
									<?php checked( $settings['img_lazy_load'] ); ?> />
								<span class="really-toggle__slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Add loading="lazy" to all images in content.', 'really-optimize' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Add Width and Height', 'really-optimize' ); ?></th>
						<td>
							<label class="really-toggle">
								<input type="checkbox" name="img_add_dimensions" value="1"
									<?php checked( $settings['img_add_dimensions'] ); ?> />
								<span class="really-toggle__slider"></span>
							</label>
							<p class="description"><?php esc_html_e( 'Add width and height attributes to local images that are missing them. Prevents layout shift (CLS).', 'really-optimize' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Max Dimensions', 'really-optimize' ); ?></th>
						<td>
							<label>
								<?php esc_html_e( 'Width', 'really-optimize' ); ?>
								<input type="number" name="img_max_width" min="100" max="9999"
									value="<?php echo esc_attr( $settings['img_max_width'] ); ?>"
									class="small-text" /> px
							</label>
							&nbsp;&nbsp;
							<label>
								<?php esc_html_e( 'Height', 'really-optimize' ); ?>
								<input type="number" name="img_max_height" min="100" max="9999"
									value="<?php echo esc_attr( $settings['img_max_height'] ); ?>"
									class="small-text" /> px
							</label>
							<p class="description"><?php esc_html_e( 'Images exceeding these dimensions will be resized on upload.', 'really-optimize' ); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div class="really-card">
				<h2><?php esc_html_e( 'CLI Tools', 'really-optimize' ); ?></h2>

				<?php if ( ! Really_Optimize_CLI::exec_available() ) : ?>
					<p class="really-warning">
						<?php esc_html_e( 'PHP exec() is disabled on this server. CLI tools cannot be used. Falling back to GD/Imagick.', 'really-optimize' ); ?>
					</p>
				<?php else : ?>

				<table class="widefat really-tools-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tool', 'really-optimize' ); ?></th>
							<th><?php esc_html_e( 'Format', 'really-optimize' ); ?></th>
							<th><?php esc_html_e( 'Status', 'really-optimize' ); ?></th>
							<th><?php esc_html_e( 'Path detected', 'really-optimize' ); ?></th>
							<th><?php esc_html_e( 'Custom path (optional)', 'really-optimize' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $really_optimize_cli_status as $really_optimize_key => $really_optimize_tool ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $really_optimize_tool['label'] ); ?></strong></td>
							<td><?php echo esc_html( $really_optimize_tool['purpose'] ); ?></td>
							<td>
								<?php if ( $really_optimize_tool['available'] ) : ?>
									<span class="really-badge really-badge--ok"><?php esc_html_e( 'Found', 'really-optimize' ); ?></span>
								<?php else : ?>
									<span class="really-badge really-badge--missing"><?php esc_html_e( 'Not found', 'really-optimize' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<code><?php echo $really_optimize_tool['path'] ? esc_html( $really_optimize_tool['path'] ) : '-'; ?></code>
							</td>
							<td>
								<input type="text" name="cli_paths[<?php echo esc_attr( $really_optimize_key ); ?>]"
									value="<?php echo esc_attr( isset( $really_optimize_cli_paths[ $really_optimize_key ] ) ? $really_optimize_cli_paths[ $really_optimize_key ] : '' ); ?>"
									placeholder="/usr/bin/<?php echo esc_attr( $really_optimize_tool['label'] ); ?>"
									class="regular-text" />
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="description" style="margin-top:8px;">
					<?php esc_html_e( 'Leave custom path empty to use auto-detection.', 'really-optimize' ); ?>
				</p>

				<?php endif; ?>
			</div>

		</div>
		<?php endif; ?>

		<?php if ( 'bulk' === $really_optimize_active_tab ) :
			$really_optimize_bulk_total = Really_Optimize_Bulk::count_total();
			$really_optimize_bulk_done  = Really_Optimize_Bulk::count_done();
			$really_optimize_bulk_pct   = $really_optimize_bulk_total > 0 ? round( $really_optimize_bulk_done / $really_optimize_bulk_total * 100 ) : 0;
		?>
		<div class="really-tab-panel">

			<div class="really-card">
				<h2><?php esc_html_e( 'Bulk Image Optimization', 'really-optimize' ); ?></h2>

				<div class="really-bulk-stats">
					<div class="really-stat">
						<span class="really-stat__value" id="really-bulk-total"><?php echo (int) $really_optimize_bulk_total; ?></span>
						<span class="really-stat__label"><?php esc_html_e( 'Total images', 'really-optimize' ); ?></span>
					</div>
					<div class="really-stat">
						<span class="really-stat__value" id="really-bulk-done"><?php echo (int) $really_optimize_bulk_done; ?></span>
						<span class="really-stat__label"><?php esc_html_e( 'Optimized', 'really-optimize' ); ?></span>
					</div>
					<div class="really-stat">
						<span class="really-stat__value" id="really-bulk-remaining"><?php echo (int) max( 0, $really_optimize_bulk_total - $really_optimize_bulk_done ); ?></span>
						<span class="really-stat__label"><?php esc_html_e( 'Remaining', 'really-optimize' ); ?></span>
					</div>
				</div>

				<div class="really-progress-wrap">
					<div class="really-progress">
						<div class="really-progress__bar" id="really-progress-bar" style="width:<?php echo (int) $really_optimize_bulk_pct; ?>%"></div>
					</div>
					<span class="really-progress__pct" id="really-progress-pct"><?php echo (int) $really_optimize_bulk_pct; ?>%</span>
				</div>

				<div class="really-bulk-options">
					<label>
						<input type="checkbox" id="really-skip-done" checked />
						<?php esc_html_e( 'Skip already optimized images', 'really-optimize' ); ?>
					</label>
				</div>

				<div class="really-bulk-actions">
					<button type="button" class="button button-primary" id="really-bulk-start">
						<?php esc_html_e( 'Start Optimization', 'really-optimize' ); ?>
					</button>
					<button type="button" class="button" id="really-bulk-pause" style="display:none">
						<?php esc_html_e( 'Pause', 'really-optimize' ); ?>
					</button>
					<button type="button" class="button" id="really-bulk-reset">
						<?php esc_html_e( 'Reset Marks', 'really-optimize' ); ?>
					</button>
					<span class="really-bulk-status" id="really-bulk-status"></span>
				</div>
			</div>

			<div class="really-card">
				<h2><?php esc_html_e( 'Log', 'really-optimize' ); ?></h2>
				<div class="really-log" id="really-bulk-log">
					<p class="really-log__empty"><?php esc_html_e( 'Log will appear here during processing.', 'really-optimize' ); ?></p>
				</div>
			</div>

		</div>
		<?php endif; ?>

		<?php if ( 'bulk' !== $really_optimize_active_tab ) : ?>
			<?php submit_button( __( 'Save Settings', 'really-optimize' ) ); ?>
		<?php endif; ?>
	</form>
</div>
