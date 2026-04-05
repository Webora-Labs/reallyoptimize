<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Webora_Image_Optimizer_Images {

	public function __construct() {
		if ( Webora_Image_Optimizer_Settings::get( 'img_compress' ) || Webora_Image_Optimizer_Settings::get( 'img_webp' ) || Webora_Image_Optimizer_Settings::get( 'img_avif' ) ) {
			add_filter( 'wp_handle_upload', array( $this, 'process_on_upload' ) );
		}

		if ( Webora_Image_Optimizer_Settings::get( 'img_lazy_load' ) ) {
			add_filter( 'the_content',        array( $this, 'add_lazy_load' ) );
			add_filter( 'post_thumbnail_html', array( $this, 'add_lazy_load' ) );
		}

		if ( Webora_Image_Optimizer_Settings::get( 'img_add_dimensions' ) ) {
			add_filter( 'the_content',        array( $this, 'add_dimensions' ) );
			add_filter( 'post_thumbnail_html', array( $this, 'add_dimensions' ) );
		}
	}

	// -----------------------------------------------------------------------
	// Upload pipeline
	// -----------------------------------------------------------------------

	public function process_on_upload( $upload ) {
		$mime = isset( $upload['type'] ) ? $upload['type'] : '';

		if ( ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ), true ) ) {
			return $upload;
		}

		$file    = $upload['file'];
		$quality = (int) Webora_Image_Optimizer_Settings::get( 'img_quality' );

		// 1. Resize if needed (always uses WP image editor — no CLI needed).
		$this->resize_if_needed( $file, $mime );

		// 2. Convert to AVIF (CLI preferred, GD as fallback) — takes priority over WebP.
		if ( Webora_Image_Optimizer_Settings::get( 'img_avif' ) && in_array( $mime, array( 'image/jpeg', 'image/png' ), true ) ) {
			$avif = $this->convert_to_avif( $file, $mime, $quality );
			if ( $avif ) {
				wp_delete_file( $file );
				$upload['file'] = $avif;
				$upload['url']  = str_replace( basename( $file ), basename( $avif ), $upload['url'] );
				$upload['type'] = 'image/avif';
				return $upload;
			}
		}

		// 3. Convert to WebP (CLI preferred, GD as fallback).
		if ( Webora_Image_Optimizer_Settings::get( 'img_webp' ) && in_array( $mime, array( 'image/jpeg', 'image/png' ), true ) ) {
			$webp = $this->convert_to_webp( $file, $mime, $quality );
			if ( $webp ) {
				wp_delete_file( $file );
				$upload['file'] = $webp;
				$upload['url']  = str_replace( basename( $file ), basename( $webp ), $upload['url'] );
				$upload['type'] = 'image/webp';
				return $upload;
			}
		}

		// 4. Compress in-place (CLI preferred, GD/Imagick as fallback).
		if ( Webora_Image_Optimizer_Settings::get( 'img_compress' ) ) {
			$this->compress( $file, $mime, $quality );
		}

		return $upload;
	}

	// -----------------------------------------------------------------------
	// Resize (WP image editor — no CLI equivalent needed)
	// -----------------------------------------------------------------------

	private function resize_if_needed( $file, $mime ) {
		$max_w = (int) Webora_Image_Optimizer_Settings::get( 'img_max_width' );
		$max_h = (int) Webora_Image_Optimizer_Settings::get( 'img_max_height' );

		list( $width, $height ) = @getimagesize( $file );

		if ( ! $width || ! $height || ( $width <= $max_w && $height <= $max_h ) ) {
			return;
		}

		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return;
		}
		$editor->resize( $max_w, $max_h, false );
		$editor->save( $file );
	}

	// -----------------------------------------------------------------------
	// Compression  (CLI → GD/Imagick)
	// -----------------------------------------------------------------------

	private function compress( $file, $mime, $quality ) {
		$done = false;

		if ( 'image/jpeg' === $mime ) {
			$done = Webora_Image_Optimizer_CLI::compress_jpeg( $file, $quality );
		} elseif ( 'image/png' === $mime ) {
			$done = Webora_Image_Optimizer_CLI::compress_png( $file );
		}

		// Fallback: WordPress image editor (GD or Imagick).
		if ( ! $done ) {
			$editor = wp_get_image_editor( $file );
			if ( ! is_wp_error( $editor ) ) {
				$editor->set_quality( $quality );
				$editor->save( $file );
			}
		}
	}

	// -----------------------------------------------------------------------
	// WebP conversion  (CLI → GD)
	// -----------------------------------------------------------------------

	/**
	 * @return string|false  Path to new .webp file, or false on failure.
	 */
	private function convert_to_webp( $file, $mime, $quality ) {
		// Try cwebp first.
		$webp = Webora_Image_Optimizer_CLI::to_webp( $file, $quality );
		if ( $webp ) {
			return $webp;
		}

		// Fallback: PHP GD.
		return $this->gd_to_webp( $file, $mime, $quality );
	}

	private function gd_to_webp( $file, $mime, $quality ) {
		if ( ! function_exists( 'imagewebp' ) ) {
			return false;
		}

		if ( 'image/jpeg' === $mime ) {
			$image = @imagecreatefromjpeg( $file );
		} elseif ( 'image/png' === $mime ) {
			$image = @imagecreatefrompng( $file );
			if ( $image ) {
				imagepalettetotruecolor( $image );
				imagealphablending( $image, true );
				imagesavealpha( $image, true );
			}
		} else {
			return false;
		}

		if ( ! $image ) {
			return false;
		}

		$webp_file = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file );
		$result    = imagewebp( $image, $webp_file, $quality );
		imagedestroy( $image );

		return ( $result && file_exists( $webp_file ) ) ? $webp_file : false;
	}

	// -----------------------------------------------------------------------
	// Lazy load
	// -----------------------------------------------------------------------

	public function add_lazy_load( $content ) {
		if ( ! is_string( $content ) || false === strpos( $content, '<img' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/<img\s[^>]+>/i',
			function ( $matches ) {
				$tag = $matches[0];
				if ( false !== strpos( $tag, 'loading=' ) ) {
					return $tag;
				}
				return str_replace( '<img ', '<img loading="lazy" ', $tag );
			},
			$content
		);
	}

	// -----------------------------------------------------------------------
	// Add width/height dimensions to images (prevents CLS)
	// -----------------------------------------------------------------------

	public function add_dimensions( $content ) {
		if ( ! is_string( $content ) || false === strpos( $content, '<img' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/<img\s[^>]+>/i',
			function ( $matches ) {
				$tag = $matches[0];

				// Skip if both dimensions already present.
				$has_w = (bool) preg_match( '/\bwidth=/i', $tag );
				$has_h = (bool) preg_match( '/\bheight=/i', $tag );
				if ( $has_w && $has_h ) {
					return $tag;
				}

				// Extract src attribute.
				if ( ! preg_match( '/\bsrc=["\']([^"\']+)["\']/i', $tag, $m ) ) {
					return $tag;
				}

				// Only process local images (no external URLs).
				$src  = strtok( $m[1], '?' );
				$path = self::url_to_path( $src );
				if ( ! $path || ! file_exists( $path ) ) {
					return $tag;
				}

				$size = @getimagesize( $path );
				if ( ! $size || ! $size[0] || ! $size[1] ) {
					return $tag;
				}

				if ( ! $has_w ) {
					$tag = str_replace( '<img ', '<img width="' . (int) $size[0] . '" ', $tag );
				}
				if ( ! $has_h ) {
					$tag = str_replace( '<img ', '<img height="' . (int) $size[1] . '" ', $tag );
				}

				return $tag;
			},
			$content
		);
	}

	// -----------------------------------------------------------------------
	// URL → filesystem path
	// -----------------------------------------------------------------------

	private static function url_to_path( $url ) {
		$url = strtok( $url, '?' );

		if ( strpos( $url, '//' ) === 0 ) {
			$url = 'https:' . $url;
		}

		$content_url = content_url();
		if ( strpos( $url, $content_url ) === 0 ) {
			return WP_CONTENT_DIR . substr( $url, strlen( $content_url ) );
		}

		$site_url = site_url();
		if ( strpos( $url, $site_url ) === 0 ) {
			return ABSPATH . substr( $url, strlen( $site_url ) + 1 );
		}

		if ( strpos( $url, '/' ) === 0 ) {
			return untrailingslashit( ABSPATH ) . $url;
		}

		return null;
	}

	// -----------------------------------------------------------------------
	// AVIF conversion  (GD imageavif — PHP 8.1+ / GD 2.1+)
	// -----------------------------------------------------------------------

	private function convert_to_avif( $file, $mime, $quality ) {
		if ( ! function_exists( 'imageavif' ) ) {
			return false;
		}

		if ( 'image/jpeg' === $mime ) {
			$image = @imagecreatefromjpeg( $file );
		} elseif ( 'image/png' === $mime ) {
			$image = @imagecreatefrompng( $file );
			if ( $image ) {
				imagepalettetotruecolor( $image );
				imagealphablending( $image, true );
				imagesavealpha( $image, true );
			}
		} else {
			return false;
		}

		if ( ! $image ) {
			return false;
		}

		$avif_file = preg_replace( '/\.(jpe?g|png)$/i', '.avif', $file );
		$result    = imageavif( $image, $avif_file, $quality );
		imagedestroy( $image );

		return ( $result && file_exists( $avif_file ) ) ? $avif_file : false;
	}
}
