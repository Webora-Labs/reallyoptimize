<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Webora_Image_Optimizer_Bulk {

	const META_KEY  = '_webora_optimized';
	const BATCH     = 5;
	const MIME_TYPES = array( 'image/jpeg', 'image/png' );

	// -----------------------------------------------------------------------
	// Counts
	// -----------------------------------------------------------------------

	public static function count_total() {
		$q = new WP_Query( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => self::MIME_TYPES,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );
		return (int) $q->found_posts;
	}

	public static function count_done() {
		global $wpdb;
		$key = self::META_KEY;
		// Count attachments of accepted mime types that have the meta key.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time count, not suitable for caching.
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT p.ID)
			   FROM {$wpdb->posts} p
			   JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
			  WHERE p.post_type   = 'attachment'
			    AND p.post_status = 'inherit'
			    AND p.post_mime_type IN ('image/jpeg','image/png')
			    AND pm.meta_key   = %s",
			$key
		) );
	}

	// -----------------------------------------------------------------------
	// Batch processing
	// -----------------------------------------------------------------------

	/**
	 * Process one batch of images.
	 *
	 * @param int  $offset       How many images to skip (already processed).
	 * @param bool $skip_done    Skip images already marked as optimized.
	 * @return array {
	 *   int    $processed   Images processed in this batch.
	 *   int    $total       Total images in library.
	 *   int    $done        Total images marked as optimized.
	 *   bool   $finished    True when no more images remain.
	 *   array  $log         Per-image result messages.
	 * }
	 */
	public static function process_batch( $offset, $skip_done = true ) {
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => self::MIME_TYPES,
			'posts_per_page' => self::BATCH,
			'offset'         => $offset,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);

		if ( $skip_done ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to skip already-optimized attachments.
			$args['meta_query'] = array(
				array(
					'key'     => self::META_KEY,
					'compare' => 'NOT EXISTS',
				),
			);
			// When skipping done images we don't use a numeric offset —
			// always query from the beginning of remaining images.
			$args['offset'] = 0;
		}

		$query = new WP_Query( $args );
		$ids   = $query->posts;

		$log = array();
		foreach ( $ids as $id ) {
			$log[] = self::process_attachment( $id );
		}

		$total    = self::count_total();
		$done     = self::count_done();
		$finished = empty( $ids ) || ( $skip_done && $done >= $total );

		return array(
			'processed' => count( $ids ),
			'total'     => $total,
			'done'      => $done,
			'finished'  => $finished,
			'log'       => $log,
		);
	}

	// -----------------------------------------------------------------------
	// Single attachment
	// -----------------------------------------------------------------------

	private static function process_attachment( $attachment_id ) {
		$file = get_attached_file( $attachment_id );

		if ( ! $file || ! file_exists( $file ) ) {
			return array(
				'id'      => $attachment_id,
				'file'    => basename( (string) $file ),
				'status'  => 'error',
				'message' => 'File not found',
			);
		}

		$mime    = get_post_mime_type( $attachment_id );
		$quality = (int) Webora_Image_Optimizer_Settings::get( 'img_quality' );
		$do_webp = (bool) Webora_Image_Optimizer_Settings::get( 'img_webp' );
		$size_before = filesize( $file );

		// --- WebP conversion ------------------------------------------------
		if ( $do_webp && in_array( $mime, array( 'image/jpeg', 'image/png' ), true ) ) {
			$webp = self::gd_to_webp( $file, $mime, $quality );

			if ( $webp ) {
				update_attached_file( $attachment_id, $webp );
				wp_update_post( array(
					'ID'             => $attachment_id,
					'post_mime_type' => 'image/webp',
				) );
				update_post_meta( $attachment_id, self::META_KEY, time() );

				return array(
					'id'      => $attachment_id,
					'file'    => basename( $file ),
					'status'  => 'webp',
					'message' => sprintf( 'Converted to WebP (%s)', basename( $webp ) ),
				);
			}
		}

		// --- Compression via GD/Imagick -------------------------------------
		$done   = false;
		$editor = wp_get_image_editor( $file );
		if ( ! is_wp_error( $editor ) ) {
			$editor->set_quality( $quality );
			$editor->save( $file );
			$done = true;
		}

		$size_after = filesize( $file );
		$saved      = $size_before - $size_after;
		$saved_pct  = $size_before > 0 ? round( $saved / $size_before * 100, 1 ) : 0;

		update_post_meta( $attachment_id, self::META_KEY, time() );

		return array(
			'id'      => $attachment_id,
			'file'    => basename( $file ),
			'status'  => $done ? 'ok' : 'error',
			'message' => $done
				? sprintf( 'Saved %s (%s%%)', size_format( max( 0, $saved ) ), $saved_pct )
				: 'Image editor unavailable',
		);
	}

	// -----------------------------------------------------------------------
	// Reset optimization marks
	// -----------------------------------------------------------------------

	public static function reset() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Bulk cleanup of plugin's own meta.
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => self::META_KEY ), array( '%s' ) );
	}

	// -----------------------------------------------------------------------
	// GD WebP fallback
	// -----------------------------------------------------------------------

	private static function gd_to_webp( $file, $mime, $quality ) {
		if ( ! function_exists( 'imagewebp' ) ) {
			return false;
		}
		if ( 'image/jpeg' === $mime ) {
			$img = @imagecreatefromjpeg( $file );
		} else {
			$img = @imagecreatefrompng( $file );
			if ( $img ) {
				imagepalettetotruecolor( $img );
				imagealphablending( $img, true );
				imagesavealpha( $img, true );
			}
		}
		if ( ! $img ) {
			return false;
		}
		$out = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file );
		$ok  = imagewebp( $img, $out, $quality );
		imagedestroy( $img );
		return ( $ok && file_exists( $out ) ) ? $out : false;
	}
}
