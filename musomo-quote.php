<?php
/**
 * Plugin Name:       Musomo Quote
 * Plugin URI:        https://musomo.com
 * Description:       Request a quote button and modal for WooCommerce, powered by Contact Form 7.
 * Version:           2.0.0-rc1
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Musomo
 * Author URI:        https://musomo.com
 * Text Domain:       musomo-quote
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.0
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

define( 'MUSOMO_QUOTE_VERSION', '2.0.0-rc1' );
define( 'MUSOMO_QUOTE_FILE', __FILE__ );
define( 'MUSOMO_QUOTE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MUSOMO_QUOTE_URL', plugin_dir_url( __FILE__ ) );
define( 'MUSOMO_QUOTE_BASENAME', plugin_basename( __FILE__ ) );

require_once MUSOMO_QUOTE_PATH . 'includes/appearance.php';
require_once MUSOMO_QUOTE_PATH . 'includes/texts.php';
require_once MUSOMO_QUOTE_PATH . 'includes/class-musomo-quote-i18n.php';
require_once MUSOMO_QUOTE_PATH . 'includes/restrictions.php';
require_once MUSOMO_QUOTE_PATH . 'includes/class-musomo-quote-security.php';
require_once MUSOMO_QUOTE_PATH . 'includes/class-musomo-quote-cf7-builder.php';
require_once MUSOMO_QUOTE_PATH . 'includes/helpers.php';
require_once MUSOMO_QUOTE_PATH . 'includes/class-musomo-quote-tools.php';
require_once MUSOMO_QUOTE_PATH . 'includes/class-musomo-quote.php';

/**
 * Returns the main plugin instance.
 *
 * @return Musomo_Quote
 */
function musomo_quote() {
	return Musomo_Quote::instance();
}

musomo_quote();
