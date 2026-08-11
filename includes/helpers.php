<?php
/**
 * Helper functions for Musomo Quote.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default plugin settings.
 *
 * @return array
 */
function musomo_quote_default_settings() {
	return array_merge(
		array(
			'enabled'                  => true,
			'button_mode'              => 'add',
			'button_position'          => 'after_add_to_cart',
			'button_priority'          => 20,
			'cf7_form_id'              => 0,
			'delete_data_on_uninstall' => false,
			'debug_mode'               => false,
		),
		musomo_quote_appearance_defaults(),
		musomo_quote_text_defaults(),
		musomo_quote_restriction_defaults(),
		Musomo_Quote_Security::defaults(),
		array(
			'translations' => array(),
			'cf7_builder'  => Musomo_Quote_CF7_Builder::defaults(),
		)
	);
}

/**
 * Get plugin settings merged with defaults (request-cached).
 *
 * @param bool $force_refresh Bypass static cache.
 * @return array
 */
function musomo_quote_get_settings( $force_refresh = false ) {
	static $cache = null;

	if ( null !== $cache && ! $force_refresh ) {
		return $cache;
	}

	$stored = get_option( 'musomo_quote_settings', array() );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$cache = wp_parse_args( $stored, musomo_quote_default_settings() );

	return $cache;
}

/**
 * Whether WooCommerce is active.
 *
 * @return bool
 */
function musomo_quote_is_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

/**
 * Whether Contact Form 7 is active.
 *
 * @return bool
 */
function musomo_quote_is_cf7_active() {
	return defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7' );
}

/**
 * Whether Polylang is active.
 *
 * @return bool
 */
function musomo_quote_is_polylang_active() {
	if ( class_exists( 'Musomo_Quote_I18n' ) ) {
		return Musomo_Quote_I18n::is_polylang_active();
	}
	return function_exists( 'pll_current_language' );
}

/**
 * Whether the plugin is enabled in settings.
 *
 * @return bool
 */
function musomo_quote_is_enabled() {
	$settings = musomo_quote_get_settings();
	return ! empty( $settings['enabled'] );
}

/**
 * Resolve a WC_Product from ID or object.
 *
 * @param mixed $product Product object or ID.
 * @return WC_Product|false
 */
function musomo_quote_resolve_product( $product ) {
	if ( ! musomo_quote_is_woocommerce_active() || ! function_exists( 'wc_get_product' ) ) {
		return false;
	}

	if ( $product instanceof WC_Product ) {
		return $product;
	}

	if ( is_numeric( $product ) ) {
		$resolved = wc_get_product( absint( $product ) );
		return $resolved instanceof WC_Product ? $resolved : false;
	}

	return false;
}

/**
 * Whether the quote button should display for a product.
 *
 * Combines: plugin enabled, WooCommerce, button mode/selected, STEP 6 restrictions.
 *
 * @param mixed $product Product object or ID.
 * @return bool
 */
function musomo_quote_should_show_quote_button( $product ) {
	if ( ! musomo_quote_is_enabled() || ! musomo_quote_is_woocommerce_active() ) {
		return false;
	}

	$product = musomo_quote_resolve_product( $product );
	if ( ! $product ) {
		return false;
	}

	$settings = musomo_quote_get_settings();
	$mode     = isset( $settings['button_mode'] ) ? $settings['button_mode'] : 'add';
	$show     = true;

	if ( 'selected' === $mode ) {
		$show = ( 'yes' === $product->get_meta( '_musomo_quote_enabled' ) );
	}

	if ( $show && ! musomo_quote_product_is_allowed( $product ) ) {
		$show = false;
	}

	/**
	 * Filter whether the quote button is shown for a product.
	 *
	 * @param bool       $show    Whether to show.
	 * @param WC_Product $product Product object.
	 */
	return (bool) apply_filters( 'musomo_quote_show_button', $show, $product );
}

/**
 * Whether Add to Cart should be replaced by the quote button on single product.
 *
 * @param mixed $product Product object or ID.
 * @return bool
 */
function musomo_quote_should_replace_add_to_cart( $product ) {
	if ( ! musomo_quote_should_show_quote_button( $product ) ) {
		return false;
	}

	$settings = musomo_quote_get_settings();
	return ( 'replace' === ( isset( $settings['button_mode'] ) ? $settings['button_mode'] : '' ) );
}

/**
 * Default quote button label fallback (legacy locale helper).
 *
 * Prefer musomo_quote_get_text( 'quote_button_text' ).
 *
 * @return string
 */
function musomo_quote_default_button_label() {
	return musomo_quote_get_text( 'quote_button_text' );
}

/**
 * Quote button label (filterable).
 *
 * @param mixed $product Product object or ID.
 * @return string
 */
function musomo_quote_get_button_label( $product = null ) {
	$product = musomo_quote_resolve_product( $product );
	$label   = musomo_quote_get_text( 'quote_button_text' );

	/**
	 * Filter the quote button label.
	 *
	 * @param string          $label   Button label.
	 * @param WC_Product|null $product Product object or null.
	 */
	return (string) apply_filters( 'musomo_quote_button_label', $label, $product );
}

/**
 * Normalize a candidate product image value to a pure absolute http(s) URL.
 *
 * Rejects HTML, attachment IDs, srcset tails, placeholders, relatives without host.
 *
 * @param mixed $url Raw value.
 * @return string Absolute URL or empty string.
 */
function musomo_quote_normalize_image_url( $url ) {
	if ( is_array( $url ) ) {
		$url = reset( $url );
	}

	$url = is_string( $url ) || is_numeric( $url ) ? (string) $url : '';
	$url = trim( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

	if ( '' === $url ) {
		return '';
	}

	// Numeric attachment IDs are not valid email src values.
	if ( ctype_digit( $url ) ) {
		return '';
	}

	// Accidental HTML <img> wrapper → extract src.
	if ( false !== stripos( $url, '<img' ) ) {
		if ( preg_match( '/\bsrc\s*=\s*([\"\'])(.*?)\1/i', $url, $m ) ) {
			$url = trim( $m[2] );
		} else {
			return '';
		}
	}

	// Strip wrapping quotes.
	$url = trim( $url, " \t\n\r\0\x0B\"'" );

	// srcset / "url 800w" → first URL token.
	if ( preg_match( '#^(https?:)?//\S+#i', $url, $m ) ) {
		$url = $m[0];
	}

	// Protocol-relative.
	if ( 0 === strpos( $url, '//' ) ) {
		$scheme = ( is_ssl() || 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ) ) ? 'https:' : 'http:';
		$url    = $scheme . $url;
	}

	// Site-relative path.
	if ( $url && '/' === $url[0] && 0 !== strpos( $url, '//' ) ) {
		$url = home_url( $url );
	}

	$url = esc_url_raw( $url );
	if ( ! $url || ! preg_match( '#^https?://#i', $url ) ) {
		return '';
	}

	// Prefer HTTPS for same-host URLs when the site runs on HTTPS.
	$site_is_https = is_ssl() || 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME );
	if ( $site_is_https && 0 === stripos( $url, 'http://' ) ) {
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$url_host  = wp_parse_url( $url, PHP_URL_HOST );
		if ( $home_host && $url_host && strtolower( (string) $home_host ) === strtolower( (string) $url_host ) ) {
			$url = 'https://' . substr( $url, 7 );
			$url = esc_url_raw( $url );
		}
	}

	if ( ! $url || ! preg_match( '#^https?://#i', $url ) ) {
		return '';
	}

	return $url;
}

/**
 * Resolve a public absolute product image URL suitable for CF7 / email.
 *
 * Output is a pure URL only (never HTML, ID, srcset, or Woo placeholder).
 *
 * @param mixed  $product Product object or ID (simple, variable, or variation).
 * @param string $size    Preferred image size.
 * @return string Absolute http(s) URL or empty.
 */
function musomo_quote_get_product_image_url( $product, $size = 'medium_large' ) {
	$product = musomo_quote_resolve_product( $product );
	if ( ! $product ) {
		return '';
	}

	$image_id = (int) $product->get_image_id();

	// Variation without own image → parent featured image.
	if ( ! $image_id && method_exists( $product, 'get_parent_id' ) ) {
		$parent_id = (int) $product->get_parent_id();
		if ( $parent_id && function_exists( 'wc_get_product' ) ) {
			$parent = wc_get_product( $parent_id );
			if ( $parent ) {
				$image_id = (int) $parent->get_image_id();
			}
		}
	}

	if ( ! $image_id ) {
		return '';
	}

	$url = '';

	// Prefer mid size (~300–768px), then full.
	$candidates = array( $size, 'medium_large', 'large', 'woocommerce_single', 'medium', 'full' );
	$candidates = array_values( array_unique( $candidates ) );

	foreach ( $candidates as $candidate ) {
		$try = wp_get_attachment_image_url( $image_id, $candidate );
		if ( is_string( $try ) && '' !== $try ) {
			$url = $try;
			break;
		}
		$src = wp_get_attachment_image_src( $image_id, $candidate );
		if ( is_array( $src ) && ! empty( $src[0] ) && is_string( $src[0] ) ) {
			$url = $src[0];
			break;
		}
	}

	if ( '' === $url ) {
		$try = wp_get_attachment_url( $image_id );
		if ( is_string( $try ) && '' !== $try ) {
			$url = $try;
		}
	}

	return musomo_quote_normalize_image_url( $url );
}

/**
 * Build product data payload for the quote button / future modal.
 *
 * @param mixed $product Product object or ID.
 * @return array
 */
function musomo_quote_get_product_data( $product ) {
	$product = musomo_quote_resolve_product( $product );
	if ( ! $product ) {
		return array(
			'id'    => 0,
			'name'  => '',
			'sku'   => '',
			'url'   => '',
			'image' => '',
			'price' => '',
			'type'  => '',
		);
	}

	// Absolute public URL for CF7 mail / email clients — never attachment ID or relative path.
	$image = musomo_quote_get_product_image_url( $product );

	$data = array(
		'id'    => $product->get_id(),
		'name'  => $product->get_name(),
		'sku'   => (string) $product->get_sku(),
		'url'   => get_permalink( $product->get_id() ),
		'image' => $image ? $image : '',
		'price' => (string) $product->get_price(),
		'type'  => $product->get_type(),
	);

	/**
	 * Filter product data passed to the frontend quote button.
	 *
	 * @param array      $data    Product data.
	 * @param WC_Product $product Product object.
	 */
	return (array) apply_filters( 'musomo_quote_product_data', $data, $product );
}

/**
 * Count published products with quote enabled (selected mode).
 *
 * @return int
 */
function musomo_quote_count_selected_products() {
	if ( ! musomo_quote_is_woocommerce_active() ) {
		return 0;
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_key'               => '_musomo_quote_enabled',
			'meta_value'             => 'yes',
		)
	);

	return (int) $query->found_posts;
}

/**
 * Dashboard label for products with quote button.
 *
 * Avoids heavy product scans when restrictions are active.
 *
 * @return string
 */
function musomo_quote_get_quote_products_status_label() {
	if ( ! musomo_quote_is_woocommerce_active() ) {
		return __( 'N/A', 'musomo-quote' );
	}

	$settings = musomo_quote_get_settings();
	$mode     = isset( $settings['button_mode'] ) ? $settings['button_mode'] : 'add';

	if ( 'selected' === $mode ) {
		return (string) musomo_quote_count_selected_products();
	}

	if ( musomo_quote_has_active_restrictions( $settings ) ) {
		return __( 'With restrictions', 'musomo-quote' );
	}

	return __( 'Global', 'musomo-quote' );
}

/**
 * Dashboard label for the linked CF7 form.
 *
 * @return string
 */
function musomo_quote_get_cf7_status_label() {
	$settings = musomo_quote_get_settings();
	$form_id  = isset( $settings['cf7_form_id'] ) ? absint( $settings['cf7_form_id'] ) : 0;
	$linked   = musomo_quote_get_linked_cf7_form_ids();

	if ( ! $form_id && empty( $linked ) ) {
		return __( 'Not linked', 'musomo-quote' );
	}

	$label = '';

	if ( $form_id ) {
		if ( ! musomo_quote_is_cf7_active() ) {
			/* translators: %d: Contact Form 7 form ID */
			$label = sprintf( __( 'ID %d (CF7 not active)', 'musomo-quote' ), $form_id );
		} elseif ( class_exists( 'Musomo_Quote_CF7' ) ) {
			$title = Musomo_Quote_CF7::get_form_title( $form_id );
			$label = ( '' !== $title ) ? $title : __( 'Not linked', 'musomo-quote' );
		} else {
			$post = get_post( $form_id );
			if ( $post && 'wpcf7_contact_form' === $post->post_type ) {
				$title = get_the_title( $post );
				$label = ( is_string( $title ) && '' !== $title ) ? $title : __( 'Not linked', 'musomo-quote' );
			} else {
				$label = __( 'Not linked', 'musomo-quote' );
			}
		}
	} else {
		/* translators: %d: number of language-linked CF7 forms */
		$label = sprintf( __( '%d linked forms (languages only)', 'musomo-quote' ), count( $linked ) );
	}

	if (
		class_exists( 'Musomo_Quote_I18n' )
		&& Musomo_Quote_I18n::is_polylang_active()
		&& $form_id
	) {
		$overrides = Musomo_Quote_I18n::count_cf7_translation_overrides();
		if ( $overrides > 0 ) {
			/* translators: 1: global form label, 2: number of language CF7 overrides */
			$label = sprintf(
				__( '%1$s — 1 global + %2$d translations', 'musomo-quote' ),
				$label,
				$overrides
			);
		}
	}

	return $label;
}
