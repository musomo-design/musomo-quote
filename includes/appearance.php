<?php
/**
 * Appearance helpers for Musomo Quote.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default appearance settings (subset merged into musomo_quote_settings).
 *
 * @return array
 */
function musomo_quote_appearance_defaults() {
	return array(
		'appearance_style'     => 'theme',
		'btn_bg'               => '#014b43',
		'btn_text'             => '#ffffff',
		'btn_border'           => '#014b43',
		'btn_hover_bg'         => '#013730',
		'btn_hover_text'       => '#ffffff',
		'btn_radius'           => 4,
		'btn_height'           => 48,
		'btn_padding_x'        => 20,
		'btn_font_size'        => 16,
		'btn_font_weight'      => 600,
		'btn_width'            => 'auto',
		'modal_width'          => 900,
		'modal_radius'         => 12,
		'modal_bg'             => '#ffffff',
		'modal_text'           => '#1a1a1a',
		'modal_padding'        => 20,
		'overlay_color'        => '#000000',
		'overlay_opacity'      => 50,
		'close_size'           => 40,
		'close_color'          => '#1a1a1a',
		'close_bg'             => 'transparent',
		'close_radius'         => 8,
		'summary_show_image'   => true,
		'summary_show_sku'     => true,
		'summary_show_price'   => true,
		'summary_image_size'   => 96,
		'summary_layout'       => 'horizontal',
		'field_height'         => 44,
		'field_radius'         => 4,
		'field_border'         => '#e5e7eb',
		'field_focus'          => '#014b43',
		'submit_bg'            => '#014b43',
		'submit_text'          => '#ffffff',
		'submit_radius'        => 4,
	);
}

/**
 * Appearance setting keys.
 *
 * @return string[]
 */
function musomo_quote_appearance_keys() {
	return array_keys( musomo_quote_appearance_defaults() );
}

/**
 * Sanitize a color value (hex or transparent).
 *
 * @param mixed  $value   Raw value.
 * @param string $default Fallback.
 * @return string
 */
function musomo_quote_sanitize_color( $value, $default = '#000000' ) {
	$value = is_string( $value ) ? trim( $value ) : '';

	if ( 'transparent' === strtolower( $value ) ) {
		return 'transparent';
	}

	$hex = sanitize_hex_color( $value );
	if ( $hex ) {
		return strtolower( $hex );
	}

	// Allow 3-digit hex without #.
	if ( preg_match( '/^#?[a-f0-9]{3}$/i', $value ) ) {
		$value = ltrim( $value, '#' );
		$hex   = '#' . $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
		$hex   = sanitize_hex_color( $hex );
		if ( $hex ) {
			return strtolower( $hex );
		}
	}

	if ( preg_match( '/^#?[a-f0-9]{6}$/i', $value ) ) {
		$hex = sanitize_hex_color( '#' . ltrim( $value, '#' ) );
		if ( $hex ) {
			return strtolower( $hex );
		}
	}

	return $default;
}

/**
 * Clamp an integer between min and max.
 *
 * @param mixed $value Value.
 * @param int   $min   Min.
 * @param int   $max   Max.
 * @param int   $default Default.
 * @return int
 */
function musomo_quote_clamp_int( $value, $min, $max, $default ) {
	if ( ! is_numeric( $value ) ) {
		return (int) $default;
	}

	$value = (int) $value;
	if ( $value < $min ) {
		return (int) $min;
	}
	if ( $value > $max ) {
		return (int) $max;
	}

	return $value;
}

/**
 * Sanitize appearance fields from settings input.
 *
 * @param array $input Raw input.
 * @return array
 */
function musomo_quote_sanitize_appearance( $input ) {
	$defaults = musomo_quote_appearance_defaults();
	$input    = is_array( $input ) ? $input : array();
	$out      = array();

	$styles = array( 'theme', 'musomo', 'custom' );
	$style  = isset( $input['appearance_style'] ) ? sanitize_key( $input['appearance_style'] ) : $defaults['appearance_style'];
	$out['appearance_style'] = in_array( $style, $styles, true ) ? $style : $defaults['appearance_style'];

	$out['btn_bg']         = musomo_quote_sanitize_color( isset( $input['btn_bg'] ) ? $input['btn_bg'] : '', $defaults['btn_bg'] );
	$out['btn_text']       = musomo_quote_sanitize_color( isset( $input['btn_text'] ) ? $input['btn_text'] : '', $defaults['btn_text'] );
	$out['btn_border']     = musomo_quote_sanitize_color( isset( $input['btn_border'] ) ? $input['btn_border'] : '', $defaults['btn_border'] );
	$out['btn_hover_bg']   = musomo_quote_sanitize_color( isset( $input['btn_hover_bg'] ) ? $input['btn_hover_bg'] : '', $defaults['btn_hover_bg'] );
	$out['btn_hover_text'] = musomo_quote_sanitize_color( isset( $input['btn_hover_text'] ) ? $input['btn_hover_text'] : '', $defaults['btn_hover_text'] );

	$out['btn_radius']      = musomo_quote_clamp_int( isset( $input['btn_radius'] ) ? $input['btn_radius'] : null, 0, 40, $defaults['btn_radius'] );
	$out['btn_height']      = musomo_quote_clamp_int( isset( $input['btn_height'] ) ? $input['btn_height'] : null, 32, 72, $defaults['btn_height'] );
	$out['btn_padding_x']   = musomo_quote_clamp_int( isset( $input['btn_padding_x'] ) ? $input['btn_padding_x'] : null, 8, 64, $defaults['btn_padding_x'] );
	$out['btn_font_size']   = musomo_quote_clamp_int( isset( $input['btn_font_size'] ) ? $input['btn_font_size'] : null, 12, 24, $defaults['btn_font_size'] );

	$weights = array( 400, 500, 600, 700 );
	$weight  = isset( $input['btn_font_weight'] ) ? absint( $input['btn_font_weight'] ) : $defaults['btn_font_weight'];
	$out['btn_font_weight'] = in_array( $weight, $weights, true ) ? $weight : $defaults['btn_font_weight'];

	$widths = array( 'auto', 'full' );
	$width  = isset( $input['btn_width'] ) ? sanitize_key( $input['btn_width'] ) : $defaults['btn_width'];
	$out['btn_width'] = in_array( $width, $widths, true ) ? $width : $defaults['btn_width'];

	$modal_widths = array( 600, 720, 800, 900, 1000 );
	$modal_width  = isset( $input['modal_width'] ) ? absint( $input['modal_width'] ) : $defaults['modal_width'];
	$out['modal_width'] = in_array( $modal_width, $modal_widths, true ) ? $modal_width : $defaults['modal_width'];

	$out['modal_radius']  = musomo_quote_clamp_int( isset( $input['modal_radius'] ) ? $input['modal_radius'] : null, 0, 30, $defaults['modal_radius'] );
	$out['modal_bg']      = musomo_quote_sanitize_color( isset( $input['modal_bg'] ) ? $input['modal_bg'] : '', $defaults['modal_bg'] );
	$out['modal_text']    = musomo_quote_sanitize_color( isset( $input['modal_text'] ) ? $input['modal_text'] : '', $defaults['modal_text'] );
	$out['modal_padding'] = musomo_quote_clamp_int( isset( $input['modal_padding'] ) ? $input['modal_padding'] : null, 16, 48, $defaults['modal_padding'] );

	$out['overlay_color']   = musomo_quote_sanitize_color( isset( $input['overlay_color'] ) ? $input['overlay_color'] : '', $defaults['overlay_color'] );
	$out['overlay_opacity'] = musomo_quote_clamp_int( isset( $input['overlay_opacity'] ) ? $input['overlay_opacity'] : null, 0, 90, $defaults['overlay_opacity'] );

	$close_sizes = array( 32, 36, 40, 44 );
	$close_size  = isset( $input['close_size'] ) ? absint( $input['close_size'] ) : $defaults['close_size'];
	$out['close_size'] = in_array( $close_size, $close_sizes, true ) ? $close_size : $defaults['close_size'];

	$out['close_color']  = musomo_quote_sanitize_color( isset( $input['close_color'] ) ? $input['close_color'] : '', $defaults['close_color'] );
	$out['close_bg']     = musomo_quote_sanitize_color( isset( $input['close_bg'] ) ? $input['close_bg'] : '', $defaults['close_bg'] );
	$out['close_radius'] = musomo_quote_clamp_int( isset( $input['close_radius'] ) ? $input['close_radius'] : null, 0, 24, $defaults['close_radius'] );

	$out['summary_show_image'] = ! empty( $input['summary_show_image'] );
	$out['summary_show_sku']   = ! empty( $input['summary_show_sku'] );
	$out['summary_show_price'] = ! empty( $input['summary_show_price'] );

	$image_sizes = array( 64, 80, 96, 120 );
	$image_size  = isset( $input['summary_image_size'] ) ? absint( $input['summary_image_size'] ) : $defaults['summary_image_size'];
	$out['summary_image_size'] = in_array( $image_size, $image_sizes, true ) ? $image_size : $defaults['summary_image_size'];

	$layouts = array( 'compact', 'horizontal', 'vertical' );
	$layout  = isset( $input['summary_layout'] ) ? sanitize_key( $input['summary_layout'] ) : $defaults['summary_layout'];
	$out['summary_layout'] = in_array( $layout, $layouts, true ) ? $layout : $defaults['summary_layout'];

	$field_heights = array( 40, 44, 48, 52 );
	$field_height  = isset( $input['field_height'] ) ? absint( $input['field_height'] ) : $defaults['field_height'];
	$out['field_height'] = in_array( $field_height, $field_heights, true ) ? $field_height : $defaults['field_height'];

	$out['field_radius'] = musomo_quote_clamp_int( isset( $input['field_radius'] ) ? $input['field_radius'] : null, 0, 16, $defaults['field_radius'] );
	$out['field_border'] = musomo_quote_sanitize_color( isset( $input['field_border'] ) ? $input['field_border'] : '', $defaults['field_border'] );
	$out['field_focus']  = musomo_quote_sanitize_color( isset( $input['field_focus'] ) ? $input['field_focus'] : '', $defaults['field_focus'] );
	$out['submit_bg']    = musomo_quote_sanitize_color( isset( $input['submit_bg'] ) ? $input['submit_bg'] : '', $defaults['submit_bg'] );
	$out['submit_text']  = musomo_quote_sanitize_color( isset( $input['submit_text'] ) ? $input['submit_text'] : '', $defaults['submit_text'] );
	$out['submit_radius'] = musomo_quote_clamp_int( isset( $input['submit_radius'] ) ? $input['submit_radius'] : null, 0, 24, $defaults['submit_radius'] );

	return $out;
}

/**
 * Resolved appearance values for the active preset.
 *
 * @param array|null $settings Optional settings.
 * @return array
 */
function musomo_quote_get_resolved_appearance( $settings = null ) {
	$settings = is_array( $settings ) ? $settings : musomo_quote_get_settings();
	$defaults = musomo_quote_appearance_defaults();
	$style    = isset( $settings['appearance_style'] ) ? $settings['appearance_style'] : 'theme';

	if ( 'theme' === $style ) {
		$resolved                       = $defaults;
		$resolved['appearance_style']   = 'theme';
		$resolved['btn_bg']             = 'transparent';
		$resolved['btn_text']           = 'inherit';
		$resolved['btn_border']         = 'currentColor';
		$resolved['btn_hover_bg']       = 'rgba(0,0,0,0.06)';
		$resolved['btn_hover_text']     = 'inherit';
		$resolved['btn_font_weight']    = 600;
		$resolved['submit_bg']          = '#111111';
		$resolved['submit_text']        = '#ffffff';
		$resolved['field_focus']        = '#111111';
		return $resolved;
	}

	if ( 'musomo' === $style ) {
		$resolved                     = $defaults;
		$resolved['appearance_style'] = 'musomo';
		return $resolved;
	}

	// Custom: merge stored values over defaults.
	$custom = array();
	foreach ( musomo_quote_appearance_keys() as $key ) {
		$custom[ $key ] = array_key_exists( $key, $settings ) ? $settings[ $key ] : $defaults[ $key ];
	}
	$custom['appearance_style'] = 'custom';

	return wp_parse_args( musomo_quote_sanitize_appearance( $custom ), $defaults );
}

/**
 * Convert hex + opacity percent to rgba().
 *
 * @param string $hex     Hex color.
 * @param int    $opacity 0–100.
 * @return string
 */
function musomo_quote_hex_to_rgba( $hex, $opacity ) {
	$hex = musomo_quote_sanitize_color( $hex, '#000000' );
	if ( 'transparent' === $hex ) {
		return 'transparent';
	}

	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	$a = max( 0, min( 100, (int) $opacity ) ) / 100;

	return sprintf( 'rgba(%d,%d,%d,%s)', $r, $g, $b, rtrim( rtrim( number_format( $a, 2, '.', '' ), '0' ), '.' ) );
}

/**
 * Build CSS custom properties for frontend/admin preview.
 *
 * @param array|null $settings Optional settings.
 * @return array<string,string>
 */
function musomo_quote_get_appearance_css_vars( $settings = null ) {
	$a = musomo_quote_get_resolved_appearance( $settings );

	$btn_width = ( 'full' === $a['btn_width'] ) ? '100%' : 'auto';

	return array(
		'--mq-btn-bg'            => (string) $a['btn_bg'],
		'--mq-btn-color'         => (string) $a['btn_text'],
		'--mq-btn-border'        => (string) $a['btn_border'],
		'--mq-btn-hover-bg'      => (string) $a['btn_hover_bg'],
		'--mq-btn-hover-color'   => (string) $a['btn_hover_text'],
		'--mq-btn-radius'        => (int) $a['btn_radius'] . 'px',
		'--mq-btn-height'        => (int) $a['btn_height'] . 'px',
		'--mq-btn-padding-x'     => (int) $a['btn_padding_x'] . 'px',
		'--mq-btn-font-size'     => (int) $a['btn_font_size'] . 'px',
		'--mq-btn-font-weight'   => (string) (int) $a['btn_font_weight'],
		'--mq-btn-width'         => $btn_width,
		'--mq-modal-width'       => (int) $a['modal_width'] . 'px',
		'--mq-modal-radius'      => (int) $a['modal_radius'] . 'px',
		'--mq-modal-bg'          => (string) $a['modal_bg'],
		'--mq-modal-text'        => (string) $a['modal_text'],
		'--mq-modal-padding'     => (int) $a['modal_padding'] . 'px',
		'--mq-overlay-bg'        => musomo_quote_hex_to_rgba( $a['overlay_color'], $a['overlay_opacity'] ),
		'--mq-close-size'        => (int) $a['close_size'] . 'px',
		'--mq-close-color'       => (string) $a['close_color'],
		'--mq-close-bg'          => (string) $a['close_bg'],
		'--mq-close-radius'      => (int) $a['close_radius'] . 'px',
		'--mq-summary-image'     => (int) $a['summary_image_size'] . 'px',
		'--mq-field-height'      => (int) $a['field_height'] . 'px',
		'--mq-field-radius'      => (int) $a['field_radius'] . 'px',
		'--mq-field-border'      => (string) $a['field_border'],
		'--mq-field-focus'       => (string) $a['field_focus'],
		'--mq-submit-bg'         => (string) $a['submit_bg'],
		'--mq-submit-color'      => (string) $a['submit_text'],
		'--mq-submit-radius'     => (int) $a['submit_radius'] . 'px',
	);
}

/**
 * Inline CSS block applying appearance variables.
 *
 * @param array|null $settings Optional settings.
 * @return string
 */
function musomo_quote_get_appearance_inline_css( $settings = null ) {
	$vars  = musomo_quote_get_appearance_css_vars( $settings );
	$lines = array();

	foreach ( $vars as $name => $value ) {
		$lines[] = $name . ':' . $value . ';';
	}

	return '.musomo-quote-root{' . implode( '', $lines ) . '}';
}

/**
 * Appearance-related HTML classes for root elements.
 *
 * @param array|null $settings Optional settings.
 * @return string
 */
function musomo_quote_get_appearance_root_classes( $settings = null ) {
	$a       = musomo_quote_get_resolved_appearance( $settings );
	$classes = array(
		'musomo-quote-root',
		'musomo-quote-root--' . sanitize_html_class( $a['appearance_style'] ),
		'musomo-quote-summary--' . sanitize_html_class( $a['summary_layout'] ),
	);

	if ( empty( $a['summary_show_image'] ) ) {
		$classes[] = 'musomo-quote-hide-image';
	}
	if ( empty( $a['summary_show_sku'] ) ) {
		$classes[] = 'musomo-quote-hide-sku';
	}
	if ( empty( $a['summary_show_price'] ) ) {
		$classes[] = 'musomo-quote-hide-price';
	}

	return implode( ' ', $classes );
}
