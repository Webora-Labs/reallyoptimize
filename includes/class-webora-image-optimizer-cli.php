<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects and runs CLI image optimization tools.
 *
 * Priority:
 *   JPEG  → mozjpeg (cjpeg) → jpegoptim → GD/Imagick fallback
 *   PNG   → pngquant → oxipng → optipng  → GD/Imagick fallback
 *   WebP  → cwebp → GD imagewebp() fallback
 */
class Webora_Image_Optimizer_CLI {

	/** Runtime cache: binary => path|null */
	private static $cache = array();

	/**
	 * All tools the plugin knows about.
	 * key => [ label, binary, purpose ]
	 */
	const TOOLS = array(
		'cwebp'     => array( 'label' => 'cwebp',     'binary' => 'cwebp',     'purpose' => 'WebP' ),
		'cjpeg'     => array( 'label' => 'mozjpeg',   'binary' => 'cjpeg',     'purpose' => 'JPEG' ),
		'jpegoptim' => array( 'label' => 'jpegoptim', 'binary' => 'jpegoptim', 'purpose' => 'JPEG' ),
		'pngquant'  => array( 'label' => 'pngquant',  'binary' => 'pngquant',  'purpose' => 'PNG'  ),
		'oxipng'    => array( 'label' => 'oxipng',    'binary' => 'oxipng',    'purpose' => 'PNG'  ),
		'optipng'   => array( 'label' => 'optipng',   'binary' => 'optipng',   'purpose' => 'PNG'  ),
	);

	// -----------------------------------------------------------------------
	// Detection
	// -----------------------------------------------------------------------

	/**
	 * Check whether PHP exec() is usable.
	 */
	public static function exec_available() {
		if ( ! function_exists( 'exec' ) ) {
			return false;
		}
		$disabled = array_map( 'trim', explode( ',', (string) ini_get( 'disable_functions' ) ) );
		return ! in_array( 'exec', $disabled, true );
	}

	/**
	 * Resolve full path to a binary, or null if not found.
	 * Results are cached per request. Custom paths from settings take priority.
	 *
	 * @param string $key  Key from self::TOOLS (same as binary name).
	 * @return string|null
	 */
	public static function find( $key ) {
		if ( array_key_exists( $key, self::$cache ) ) {
			return self::$cache[ $key ];
		}

		$path = null;

		// 1. Custom path configured in settings.
		$custom_paths = Webora_Image_Optimizer_Settings::get( 'cli_paths' );
		if ( ! empty( $custom_paths[ $key ] ) ) {
			$candidate = trim( $custom_paths[ $key ] );
			if ( $candidate && is_executable( $candidate ) ) {
				$path = $candidate;
			}
		}

		// 2. Auto-detect via `which`.
		if ( ! $path && self::exec_available() ) {
			$binary = isset( self::TOOLS[ $key ] ) ? self::TOOLS[ $key ]['binary'] : $key;
			exec( 'which ' . escapeshellarg( $binary ) . ' 2>/dev/null', $out, $code );
			if ( 0 === $code && ! empty( $out[0] ) ) {
				$path = trim( $out[0] );
			}
			unset( $out );
		}

		self::$cache[ $key ] = $path;
		return $path;
	}

	/**
	 * Return status array for all known tools (used in admin UI).
	 *
	 * @return array  [ key => [ label, purpose, available, path ] ]
	 */
	public static function status() {
		if ( ! self::exec_available() ) {
			$result = array();
			foreach ( self::TOOLS as $key => $info ) {
				$result[ $key ] = array(
					'label'     => $info['label'],
					'purpose'   => $info['purpose'],
					'available' => false,
					'path'      => '',
					'blocked'   => true,
				);
			}
			return $result;
		}

		$result = array();
		foreach ( self::TOOLS as $key => $info ) {
			$path             = self::find( $key );
			$result[ $key ]   = array(
				'label'     => $info['label'],
				'purpose'   => $info['purpose'],
				'available' => (bool) $path,
				'path'      => $path ?: '',
				'blocked'   => false,
			);
		}
		return $result;
	}

	// -----------------------------------------------------------------------
	// Compression
	// -----------------------------------------------------------------------

	/**
	 * Compress a JPEG in-place with mozjpeg or jpegoptim.
	 * Returns true on success, false when no CLI tool is available.
	 *
	 * @param string $file    Absolute path to the JPEG file.
	 * @param int    $quality 1–100.
	 * @return bool
	 */
	public static function compress_jpeg( $file, $quality ) {
		// --- mozjpeg (cjpeg) ------------------------------------------------
		$cjpeg = self::find( 'cjpeg' );
		if ( $cjpeg ) {
			$tmp = $file . '.wio_tmp.jpg';
			$cmd = sprintf(
				'%s -quality %d -optimize -outfile %s %s 2>/dev/null',
				escapeshellarg( $cjpeg ),
				(int) $quality,
				escapeshellarg( $tmp ),
				escapeshellarg( $file )
			);
			exec( $cmd, $out, $code );
			unset( $out );

			if ( 0 === $code && file_exists( $tmp ) && filesize( $tmp ) > 0 ) {
				copy( $tmp, $file );
				wp_delete_file( $tmp );
				return true;
			}
			wp_delete_file( $tmp );
		}

		// --- jpegoptim -------------------------------------------------------
		$jpegoptim = self::find( 'jpegoptim' );
		if ( $jpegoptim ) {
			$cmd = sprintf(
				'%s --max=%d --strip-all --overwrite %s 2>/dev/null',
				escapeshellarg( $jpegoptim ),
				(int) $quality,
				escapeshellarg( $file )
			);
			exec( $cmd, $out, $code );
			unset( $out );
			return 0 === $code;
		}

		return false;
	}

	/**
	 * Compress a PNG in-place with pngquant, oxipng, or optipng.
	 * Returns true on success, false when no CLI tool is available.
	 *
	 * @param string $file Absolute path to the PNG file.
	 * @return bool
	 */
	public static function compress_png( $file ) {
		// --- pngquant (lossy – best ratio) -----------------------------------
		$pngquant = self::find( 'pngquant' );
		if ( $pngquant ) {
			$tmp = preg_replace( '/\.png$/i', '-fs8.png', $file );
			$cmd = sprintf(
				'%s --quality=65-90 --force --skip-if-larger --output %s %s 2>/dev/null',
				escapeshellarg( $pngquant ),
				escapeshellarg( $tmp ),
				escapeshellarg( $file )
			);
			exec( $cmd, $out, $code );
			unset( $out );
			// pngquant exit 98 = file was already smaller, still OK.
			if ( in_array( $code, array( 0, 98 ), true ) && file_exists( $tmp ) ) {
				copy( $tmp, $file );
				wp_delete_file( $tmp );
				return true;
			}
			wp_delete_file( $tmp );
		}

		// --- oxipng (lossless) -----------------------------------------------
		$oxipng = self::find( 'oxipng' );
		if ( $oxipng ) {
			$cmd = sprintf(
				'%s --opt 2 --strip safe %s 2>/dev/null',
				escapeshellarg( $oxipng ),
				escapeshellarg( $file )
			);
			exec( $cmd, $out, $code );
			unset( $out );
			return 0 === $code;
		}

		// --- optipng (lossless) ---------------------------------------------
		$optipng = self::find( 'optipng' );
		if ( $optipng ) {
			$cmd = sprintf(
				'%s -o2 -strip all %s 2>/dev/null',
				escapeshellarg( $optipng ),
				escapeshellarg( $file )
			);
			exec( $cmd, $out, $code );
			unset( $out );
			return 0 === $code;
		}

		return false;
	}

	/**
	 * Convert a JPEG or PNG to WebP using cwebp.
	 * Returns the new .webp file path on success, false otherwise.
	 *
	 * @param string $file    Source file path.
	 * @param int    $quality 1–100.
	 * @return string|false
	 */
	public static function to_webp( $file, $quality ) {
		$cwebp = self::find( 'cwebp' );
		if ( ! $cwebp ) {
			return false;
		}

		$webp = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $file );
		$cmd  = sprintf(
			'%s -q %d -m 6 %s -o %s 2>/dev/null',
			escapeshellarg( $cwebp ),
			(int) $quality,
			escapeshellarg( $file ),
			escapeshellarg( $webp )
		);
		exec( $cmd, $out, $code );
		unset( $out );

		return ( 0 === $code && file_exists( $webp ) && filesize( $webp ) > 0 ) ? $webp : false;
	}
}
