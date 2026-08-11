<?php
/**
 * Frontend text settings helpers for Musomo Quote.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default configurable frontend texts.
 *
 * @return array<string,string>
 */
function musomo_quote_text_defaults() {
	return array(
		'quote_button_text'        => 'Request a quote',
		'modal_title'              => 'Request a quote',
		'label_sku'                => 'SKU:',
		'label_price'              => 'Price:',
		'label_quantity'           => 'Quantity:',
		'form_not_configured_text' => 'Quote form is not configured.',
		'cf7_not_available_text'   => 'Contact Form 7 is not available.',
		'close_aria_label'         => 'Close quote form',
	);
}

/**
 * Text setting keys.
 *
 * @return string[]
 */
function musomo_quote_text_keys() {
	return array_keys( musomo_quote_text_defaults() );
}

/**
 * Sanitize text settings from admin input (global / fallback texts).
 *
 * @param array $input Raw input.
 * @return array<string,string>
 */
function musomo_quote_sanitize_texts( $input ) {
	$defaults = musomo_quote_text_defaults();
	$input    = is_array( $input ) ? $input : array();
	$out      = array();

	foreach ( $defaults as $key => $default ) {
		if ( ! array_key_exists( $key, $input ) ) {
			$out[ $key ] = $default;
			continue;
		}

		$raw = wp_unslash( (string) $input[ $key ] );
		if ( in_array( $key, array( 'form_not_configured_text', 'cf7_not_available_text' ), true ) ) {
			$value = sanitize_textarea_field( $raw );
		} else {
			$value = sanitize_text_field( $raw );
		}
		$out[ $key ] = ( '' !== $value ) ? $value : $default;
	}

	return $out;
}

/**
 * Get a single setting value with request-level settings cache.
 *
 * @param string $key     Setting key.
 * @param mixed  $default Optional fallback if key missing after merge.
 * @return mixed
 */
function musomo_quote_get_setting( $key, $default = null ) {
	$settings = musomo_quote_get_settings();

	if ( array_key_exists( $key, $settings ) ) {
		return $settings[ $key ];
	}

	return $default;
}

/**
 * Get a configurable frontend text (language-aware with fallback).
 *
 * @param string $key Text key.
 * @return string
 */
function musomo_quote_get_text( $key ) {
	$defaults = musomo_quote_text_defaults();
	$key      = (string) $key;

	if ( ! array_key_exists( $key, $defaults ) ) {
		return '';
	}

	$language = '';
	if ( class_exists( 'Musomo_Quote_I18n' ) ) {
		$language = Musomo_Quote_I18n::get_current_language();
		$value    = Musomo_Quote_I18n::resolve_text( $key, $language );
	} else {
		$value = musomo_quote_get_setting( $key, $defaults[ $key ] );
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( '' === $value ) {
			$value = $defaults[ $key ];
		}
	}

	/**
	 * Filter a Musomo Quote frontend text string (legacy).
	 *
	 * @param string $value Text value.
	 * @param string $key   Text key.
	 */
	$value = (string) apply_filters( 'musomo_quote_get_text', $value, $key );

	/**
	 * Filter a Musomo Quote frontend text string (language-aware).
	 *
	 * @param string $value    Text value.
	 * @param string $key      Text key.
	 * @param string $language Language slug (empty if none / admin global).
	 */
	return (string) apply_filters( 'musomo_quote_text', $value, $key, $language );
}

/**
 * Unique list of CF7 form IDs linked to Musomo Quote (global + translations).
 *
 * @return int[]
 */
function musomo_quote_get_linked_cf7_form_ids() {
	if ( class_exists( 'Musomo_Quote_I18n' ) ) {
		return Musomo_Quote_I18n::get_linked_cf7_form_ids();
	}

	$settings = musomo_quote_get_settings();
	$id       = isset( $settings['cf7_form_id'] ) ? absint( $settings['cf7_form_id'] ) : 0;
	return $id > 0 ? array( $id ) : array();
}
