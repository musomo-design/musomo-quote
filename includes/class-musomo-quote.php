<?php
/**
 * Main plugin bootstrap.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Musomo_Quote
 */
final class Musomo_Quote {

	/**
	 * Singleton instance.
	 *
	 * @var Musomo_Quote|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Musomo_Quote
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 */
	private function includes() {
		require_once MUSOMO_QUOTE_PATH . 'includes/class-musomo-quote-admin.php';
		require_once MUSOMO_QUOTE_PATH . 'includes/class-musomo-quote-frontend.php';
		require_once MUSOMO_QUOTE_PATH . 'includes/class-musomo-quote-cf7.php';
	}

	/**
	 * Register core hooks.
	 */
	private function init_hooks() {
		register_activation_hook( MUSOMO_QUOTE_FILE, array( $this, 'activate' ) );
		add_action( 'before_woocommerce_init', array( $this, 'declare_woocommerce_feature_compatibility' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
	}

	/**
	 * Declare compatibility with WooCommerce features via official FeaturesUtil API.
	 *
	 * Must run on before_woocommerce_init.
	 * Musomo Quote does not read/write orders, so it is HPOS-compatible.
	 * It does not alter cart/checkout, so Cart & Checkout Blocks are also compatible.
	 */
	public function declare_woocommerce_feature_compatibility() {
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			MUSOMO_QUOTE_FILE,
			true
		);

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'cart_checkout_blocks',
			MUSOMO_QUOTE_FILE,
			true
		);
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		if ( false === get_option( 'musomo_quote_settings', false ) ) {
			add_option( 'musomo_quote_settings', musomo_quote_default_settings() );
		}
	}

	/**
	 * Load bundled translations for the admin/user locale.
	 *
	 * Bundled .mo files in /languages are required for ZIP installs and for
	 * locales not yet served as WordPress.org language packs.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'musomo-quote',
			false,
			dirname( MUSOMO_QUOTE_BASENAME ) . '/languages'
		);
	}

	/**
	 * Initialize plugin components.
	 */
	public function init() {
		if ( is_admin() ) {
			Musomo_Quote_Admin::instance();
		}

		Musomo_Quote_Frontend::instance();

		if ( musomo_quote_is_woocommerce_active() ) {
			require_once MUSOMO_QUOTE_PATH . 'includes/class-musomo-quote-woocommerce.php';
			Musomo_Quote_WooCommerce::instance();
		}

		Musomo_Quote_CF7::instance();
		Musomo_Quote_Security::instance();
	}
}
