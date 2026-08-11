<?php
/**
 * Product visibility restrictions for Musomo Quote.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default restriction settings.
 *
 * @return array
 */
function musomo_quote_restriction_defaults() {
	return array(
		'restriction_category_mode'  => 'all',
		'restriction_category_ids'   => array(),
		'restriction_stock_mode'     => 'all',
		'restriction_price_mode'     => 'all',
		'restriction_product_types'  => array(),
		'restriction_user_mode'      => 'all',
	);
}

/**
 * Restriction setting keys.
 *
 * @return string[]
 */
function musomo_quote_restriction_keys() {
	return array_keys( musomo_quote_restriction_defaults() );
}

/**
 * Sanitize restriction settings from admin input.
 *
 * @param array $input Raw input.
 * @return array
 */
function musomo_quote_sanitize_restrictions( $input ) {
	$defaults = musomo_quote_restriction_defaults();
	$input    = is_array( $input ) ? $input : array();
	$out      = $defaults;

	$category_modes = array( 'all', 'include', 'exclude' );
	$mode           = isset( $input['restriction_category_mode'] ) ? sanitize_key( $input['restriction_category_mode'] ) : 'all';
	$out['restriction_category_mode'] = in_array( $mode, $category_modes, true ) ? $mode : 'all';

	$ids = array();
	if ( isset( $input['restriction_category_ids'] ) && is_array( $input['restriction_category_ids'] ) ) {
		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $input['restriction_category_ids'] )
				)
			)
		);
	}
	$out['restriction_category_ids'] = $ids;

	$stock_modes = array( 'all', 'instock', 'outofstock' );
	$stock       = isset( $input['restriction_stock_mode'] ) ? sanitize_key( $input['restriction_stock_mode'] ) : 'all';
	$out['restriction_stock_mode'] = in_array( $stock, $stock_modes, true ) ? $stock : 'all';

	$price_modes = array( 'all', 'no_price', 'with_price' );
	$price       = isset( $input['restriction_price_mode'] ) ? sanitize_key( $input['restriction_price_mode'] ) : 'all';
	$out['restriction_price_mode'] = in_array( $price, $price_modes, true ) ? $price : 'all';

	$allowed_types = array( 'simple', 'variable', 'grouped', 'external' );
	$types         = array();
	if ( isset( $input['restriction_product_types'] ) && is_array( $input['restriction_product_types'] ) ) {
		foreach ( $input['restriction_product_types'] as $type ) {
			$type = sanitize_key( $type );
			if ( in_array( $type, $allowed_types, true ) ) {
				$types[] = $type;
			}
		}
		$types = array_values( array_unique( $types ) );
	}
	$out['restriction_product_types'] = $types;

	$user_modes = array( 'all', 'logged_in', 'logged_out' );
	$user       = isset( $input['restriction_user_mode'] ) ? sanitize_key( $input['restriction_user_mode'] ) : 'all';
	$out['restriction_user_mode'] = in_array( $user, $user_modes, true ) ? $user : 'all';

	return $out;
}

/**
 * Whether any non-default restriction is active.
 *
 * @param array|null $settings Optional settings.
 * @return bool
 */
function musomo_quote_has_active_restrictions( $settings = null ) {
	$settings = is_array( $settings ) ? $settings : musomo_quote_get_settings();

	$cat_mode = isset( $settings['restriction_category_mode'] ) ? $settings['restriction_category_mode'] : 'all';
	if ( 'all' !== $cat_mode ) {
		return true;
	}

	$stock = isset( $settings['restriction_stock_mode'] ) ? $settings['restriction_stock_mode'] : 'all';
	if ( 'all' !== $stock ) {
		return true;
	}

	$price = isset( $settings['restriction_price_mode'] ) ? $settings['restriction_price_mode'] : 'all';
	if ( 'all' !== $price ) {
		return true;
	}

	$types = isset( $settings['restriction_product_types'] ) ? $settings['restriction_product_types'] : array();
	if ( is_array( $types ) && ! empty( $types ) ) {
		return true;
	}

	$user = isset( $settings['restriction_user_mode'] ) ? $settings['restriction_user_mode'] : 'all';
	if ( 'all' !== $user ) {
		return true;
	}

	return false;
}

/**
 * Category restriction match.
 *
 * Compares product_cat terms assigned to the product only (no parent→child inheritance).
 *
 * @param WC_Product $product  Product.
 * @param array      $settings Settings.
 * @return bool
 */
function musomo_quote_product_matches_category_restriction( $product, $settings ) {
	$mode = isset( $settings['restriction_category_mode'] ) ? $settings['restriction_category_mode'] : 'all';
	if ( 'all' === $mode ) {
		return true;
	}

	$selected = isset( $settings['restriction_category_ids'] ) && is_array( $settings['restriction_category_ids'] )
		? array_map( 'absint', $settings['restriction_category_ids'] )
		: array();
	$selected = array_filter( $selected );

	$product_cats = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $product_cats ) ) {
		$product_cats = array();
	}
	$product_cats = array_map( 'absint', $product_cats );

	$overlap = array_intersect( $product_cats, $selected );

	if ( 'include' === $mode ) {
		if ( empty( $selected ) ) {
			return false;
		}
		return ! empty( $overlap );
	}

	if ( 'exclude' === $mode ) {
		if ( empty( $selected ) ) {
			return true;
		}
		return empty( $overlap );
	}

	return true;
}

/**
 * Stock restriction match.
 *
 * @param WC_Product $product  Product.
 * @param array      $settings Settings.
 * @return bool
 */
function musomo_quote_product_matches_stock_restriction( $product, $settings ) {
	$mode = isset( $settings['restriction_stock_mode'] ) ? $settings['restriction_stock_mode'] : 'all';
	if ( 'all' === $mode ) {
		return true;
	}

	$in_stock = $product->is_in_stock();

	if ( 'instock' === $mode ) {
		return (bool) $in_stock;
	}

	if ( 'outofstock' === $mode ) {
		return ! $in_stock;
	}

	return true;
}

/**
 * Price restriction match.
 *
 * Empty string = no price. "0" counts as with price.
 *
 * @param WC_Product $product  Product.
 * @param array      $settings Settings.
 * @return bool
 */
function musomo_quote_product_matches_price_restriction( $product, $settings ) {
	$mode = isset( $settings['restriction_price_mode'] ) ? $settings['restriction_price_mode'] : 'all';
	if ( 'all' === $mode ) {
		return true;
	}

	$price = $product->get_price();
	// Distinguish empty price from zero.
	$has_price = ( null !== $price && '' !== $price );

	if ( 'no_price' === $mode ) {
		return ! $has_price;
	}

	if ( 'with_price' === $mode ) {
		return $has_price;
	}

	return true;
}

/**
 * Product type restriction match.
 *
 * Empty selection = all types allowed.
 *
 * @param WC_Product $product  Product.
 * @param array      $settings Settings.
 * @return bool
 */
function musomo_quote_product_matches_type_restriction( $product, $settings ) {
	$types = isset( $settings['restriction_product_types'] ) && is_array( $settings['restriction_product_types'] )
		? $settings['restriction_product_types']
		: array();

	if ( empty( $types ) ) {
		return true;
	}

	return in_array( $product->get_type(), $types, true );
}

/**
 * User restriction match.
 *
 * @param array $settings Settings.
 * @return bool
 */
function musomo_quote_user_matches_restriction( $settings ) {
	$mode = isset( $settings['restriction_user_mode'] ) ? $settings['restriction_user_mode'] : 'all';
	if ( 'all' === $mode ) {
		return true;
	}

	$logged_in = is_user_logged_in();

	if ( 'logged_in' === $mode ) {
		return $logged_in;
	}

	if ( 'logged_out' === $mode ) {
		return ! $logged_in;
	}

	return true;
}

/**
 * Whether STEP 6 restrictions allow quote for a product (AND logic).
 *
 * @param mixed $product Product object or ID.
 * @return bool
 */
function musomo_quote_product_is_allowed( $product ) {
	$product = musomo_quote_resolve_product( $product );
	if ( ! $product ) {
		return false;
	}

	$settings = musomo_quote_get_settings();

	$allowed = true;

	if ( ! musomo_quote_product_matches_category_restriction( $product, $settings ) ) {
		$allowed = false;
	} elseif ( ! musomo_quote_product_matches_stock_restriction( $product, $settings ) ) {
		$allowed = false;
	} elseif ( ! musomo_quote_product_matches_price_restriction( $product, $settings ) ) {
		$allowed = false;
	} elseif ( ! musomo_quote_product_matches_type_restriction( $product, $settings ) ) {
		$allowed = false;
	} elseif ( ! musomo_quote_user_matches_restriction( $settings ) ) {
		$allowed = false;
	}

	/**
	 * Filter whether restrictions allow quote for a product.
	 *
	 * @param bool       $allowed  Allowed by restrictions.
	 * @param WC_Product $product  Product object.
	 * @param array      $settings Plugin settings.
	 */
	return (bool) apply_filters( 'musomo_quote_product_is_allowed', $allowed, $product, $settings );
}
