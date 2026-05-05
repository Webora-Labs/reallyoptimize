<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Webora_Image_Optimizer_Settings {

	public static function defaults() {
		return array(
			// Images tab
			'img_compress'         => false,
			'img_quality'          => 85,
			'img_webp'             => false,
			'img_avif'             => false,
			'img_lazy_load'        => false,
			'img_add_dimensions'   => false,
			'img_max_width'        => 2560,
			'img_max_height'       => 2560,
		);
	}

	public static function get( $key = null ) {
		$settings = get_option( 'webora_image_optimizer_settings', self::defaults() );
		$settings = wp_parse_args( $settings, self::defaults() );

		if ( null !== $key ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
		}

		return $settings;
	}

	/**
	 * Fields grouped by tab. Only fields belonging to the active tab
	 * are updated on save — fields from other tabs are left untouched.
	 */
	private static function tab_fields() {
		return array(
			'images' => array(
				'bool' => array( 'img_compress', 'img_webp', 'img_avif', 'img_lazy_load', 'img_add_dimensions' ),
				'int'  => array(
					'img_quality'    => array( 1, 100 ),
					'img_max_width'  => array( 100, 9999 ),
					'img_max_height' => array( 100, 9999 ),
				),
			),
		);
	}

	public static function save( array $data ) {
		$current = self::get();
		$tab     = isset( $data['webora_image_optimizer_tab'] ) ? sanitize_key( $data['webora_image_optimizer_tab'] ) : '';
		$schema  = self::tab_fields();

		// If submitted tab is unknown, fall back to saving all tabs (safety net).
		$tabs_to_save = ( $tab && isset( $schema[ $tab ] ) ) ? array( $tab => $schema[ $tab ] ) : $schema;

		foreach ( $tabs_to_save as $fields ) {
			// Boolean fields.
			foreach ( $fields['bool'] ?? array() as $key ) {
				$current[ $key ] = ! empty( $data[ $key ] );
			}

			// Integer fields.
			foreach ( $fields['int'] ?? array() as $key => $range ) {
				if ( isset( $data[ $key ] ) ) {
					$val             = absint( $data[ $key ] );
					$current[ $key ] = min( $range[1], max( $range[0], $val ) );
				}
			}

			// Newline-separated text fields.
			foreach ( $fields['text'] ?? array() as $key ) {
				if ( isset( $data[ $key ] ) ) {
					$lines           = explode( "\n", str_replace( "\r", '', $data[ $key ] ) );
					$lines           = array_map( 'sanitize_text_field', $lines );
					$current[ $key ] = implode( "\n", array_filter( $lines ) );
				}
			}

			// Select fields (allowed values list).
			foreach ( $fields['select'] ?? array() as $key => $allowed ) {
				if ( isset( $data[ $key ] ) && in_array( $data[ $key ], $allowed, true ) ) {
					$current[ $key ] = $data[ $key ];
				}
			}
		}

		update_option( 'webora_image_optimizer_settings', $current );
	}
}
