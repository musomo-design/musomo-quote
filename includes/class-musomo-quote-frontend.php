<?php
/**
 * Frontend bootstrap for Musomo Quote.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Musomo_Quote_Frontend
 */
class Musomo_Quote_Frontend {

	/**
	 * Singleton instance.
	 *
	 * @var Musomo_Quote_Frontend|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Musomo_Quote_Frontend
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_modal' ), 20 );
	}

	/**
	 * Whether quote frontend assets/modal should load on this request.
	 *
	 * @return bool
	 */
	private function should_load() {
		if ( ! musomo_quote_is_enabled() ) {
			return false;
		}

		if ( ! musomo_quote_is_woocommerce_active() ) {
			return false;
		}

		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return false;
		}

		$product_id = get_queried_object_id();
		if ( ! $product_id || ! musomo_quote_should_show_quote_button( $product_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Enqueue frontend assets only on product pages where the quote button shows.
	 */
	public function enqueue_assets() {
		if ( ! $this->should_load() ) {
			return;
		}

		wp_enqueue_style(
			'musomo-quote',
			MUSOMO_QUOTE_URL . 'public/css/musomo-quote.css',
			array(),
			MUSOMO_QUOTE_VERSION
		);

		wp_add_inline_style( 'musomo-quote', musomo_quote_get_appearance_inline_css() );

		Musomo_Quote_CF7::enqueue_assets();

		wp_enqueue_script(
			'musomo-quote',
			MUSOMO_QUOTE_URL . 'public/js/musomo-quote.js',
			array(),
			MUSOMO_QUOTE_VERSION,
			true
		);

		wp_localize_script(
			'musomo-quote',
			'musomoQuote',
			array(
				'version' => MUSOMO_QUOTE_VERSION,
				'i18n'    => array(
					'skuLabel'      => musomo_quote_get_text( 'label_sku' ),
					'priceLabel'    => musomo_quote_get_text( 'label_price' ),
					'quantityLabel' => musomo_quote_get_text( 'label_quantity' ),
					'close'         => musomo_quote_get_text( 'close_aria_label' ),
				),
				'fields'  => array(
					'id'       => array( 'musomo_product_id', 'product-id' ),
					'name'     => array( 'musomo_product_name', 'product-name' ),
					'sku'      => array( 'musomo_product_sku', 'product-sku' ),
					'url'      => array( 'musomo_product_url', 'product-url' ),
					'image'    => array( 'musomo_product_image', 'product-image' ),
					'price'    => array( 'musomo_product_price', 'product-price' ),
					'type'     => array( 'musomo_product_type', 'product-type' ),
					'quantity' => array( 'musomo_quantity' ),
				),
			)
		);
	}

	/**
	 * Print modal markup in footer when needed.
	 */
	public function render_modal() {
		if ( ! $this->should_load() ) {
			return;
		}

		$modal_title = musomo_quote_get_text( 'modal_title' );
		$cf7_active  = musomo_quote_is_cf7_active();
		$form_id     = Musomo_Quote_CF7::get_selected_form_id();

		include MUSOMO_QUOTE_PATH . 'public/views/modal.php';
	}
}
