<?php
/**
 * WooCommerce integration — quote button engine (STEP 2).
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Musomo_Quote_WooCommerce
 */
class Musomo_Quote_WooCommerce {

	/**
	 * Singleton instance.
	 *
	 * @var Musomo_Quote_WooCommerce|null
	 */
	private static $instance = null;

	/**
	 * Whether the quote button was already rendered on this request.
	 *
	 * @var bool
	 */
	private $button_rendered = false;

	/**
	 * Whether REPLACE keeps the product form and only hides the ATC button.
	 *
	 * @var bool
	 */
	private $hide_atc_button = false;

	/**
	 * Get singleton instance.
	 *
	 * @return Musomo_Quote_WooCommerce
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
		add_action( 'wp', array( $this, 'register_frontend_hooks' ) );

		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_product_option' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_option' ) );
	}

	/**
	 * Register single-product hooks after the query is available.
	 */
	public function register_frontend_hooks() {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		if ( ! musomo_quote_is_enabled() ) {
			return;
		}

		$product = $this->get_current_product();
		if ( ! $product || ! ( $product instanceof WC_Product ) ) {
			return;
		}

		// Decision is centralized: only replace ATC when quote is actually allowed.
		if ( ! musomo_quote_should_show_quote_button( $product ) ) {
			return;
		}

		if ( musomo_quote_should_replace_add_to_cart( $product ) ) {
			/*
			 * Variable/grouped need the form for attribute selection. Keep the
			 * template and hide only the native ATC button via body class + CSS.
			 * Simple/external: remove the whole add-to-cart template.
			 */
			if ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) {
				$this->hide_atc_button = true;
				add_filter( 'body_class', array( $this, 'body_class_hide_atc' ) );
			} else {
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
			}
		}

		$this->attach_button_hook( $product );
	}

	/**
	 * Body class so CSS can hide the native ATC button without wrapping markup.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class_hide_atc( $classes ) {
		if ( $this->hide_atc_button ) {
			$classes[] = 'musomo-quote-replace-atc';
		}
		return $classes;
	}

	/**
	 * Attach the quote button to the configured WooCommerce hook.
	 *
	 * @param WC_Product $product Current product.
	 */
	private function attach_button_hook( $product ) {
		$settings  = musomo_quote_get_settings();
		$position  = isset( $settings['button_position'] ) ? $settings['button_position'] : 'after_add_to_cart';
		$priority  = isset( $settings['button_priority'] ) ? absint( $settings['button_priority'] ) : 20;
		$replacing = musomo_quote_should_replace_add_to_cart( $product );

		/*
		 * In replace mode for simple/external products, before/after ATC hooks live
		 * inside the removed add-to-cart template — render at former ATC priority.
		 * Variable/grouped keep the form, so before/after ATC hooks still fire.
		 */
		$form_kept = $replacing && ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) );

		if ( $replacing && ! $form_kept && in_array( $position, array( 'before_add_to_cart', 'after_add_to_cart' ), true ) ) {
			add_action( 'woocommerce_single_product_summary', array( $this, 'render_quote_button' ), 30 );
			return;
		}

		switch ( $position ) {
			case 'before_add_to_cart':
				add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_quote_button' ), 10 );
				break;

			case 'after_add_to_cart':
				add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_quote_button' ), 10 );
				break;

			case 'after_title':
				add_action( 'woocommerce_single_product_summary', array( $this, 'render_quote_button' ), 6 );
				break;

			case 'after_price':
				add_action( 'woocommerce_single_product_summary', array( $this, 'render_quote_button' ), 11 );
				break;

			case 'after_summary':
				add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_quote_button' ), 5 );
				break;

			case 'custom':
				add_action( 'woocommerce_single_product_summary', array( $this, 'render_quote_button' ), $priority ? $priority : 25 );
				break;

			default:
				add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_quote_button' ), 10 );
				break;
		}
	}

	/**
	 * Output the quote button markup.
	 */
	public function render_quote_button() {
		if ( $this->button_rendered ) {
			return;
		}

		$product = $this->get_current_product();
		if ( ! $product || ! musomo_quote_should_show_quote_button( $product ) ) {
			return;
		}

		$data  = musomo_quote_get_product_data( $product );
		$label = musomo_quote_get_button_label( $product );

		$this->button_rendered = true;

		/**
		 * Fires before the quote button markup.
		 *
		 * @param WC_Product $product Product object.
		 */
		do_action( 'musomo_quote_before_button', $product );
		?>
		<div class="musomo-quote-action <?php echo esc_attr( musomo_quote_get_appearance_root_classes() ); ?>">
			<button
				type="button"
				class="musomo-quote-button"
				data-product-id="<?php echo esc_attr( (string) $data['id'] ); ?>"
				data-product-name="<?php echo esc_attr( $data['name'] ); ?>"
				data-product-sku="<?php echo esc_attr( $data['sku'] ); ?>"
				data-product-url="<?php echo esc_url( $data['url'] ); ?>"
				data-product-image="<?php echo esc_attr( $data['image'] ); ?>"
				data-product-price="<?php echo esc_attr( $data['price'] ); ?>"
				data-product-type="<?php echo esc_attr( $data['type'] ); ?>"
			>
				<?php echo esc_html( $label ); ?>
			</button>
		</div>
		<?php
		/**
		 * Fires after the quote button markup.
		 *
		 * @param WC_Product $product Product object.
		 */
		do_action( 'musomo_quote_after_button', $product );
	}

	/**
	 * Product Data → General: enable quote for this product (selected mode).
	 */
	public function render_product_option() {
		woocommerce_wp_checkbox(
			array(
				'id'          => '_musomo_quote_enabled',
				'label'       => __( 'Musomo Quote', 'musomo-quote' ),
				'description' => __( 'Enable quote request for this product', 'musomo-quote' ),
				'desc_tip'    => true,
			)
		);
	}

	/**
	 * Save product-level quote checkbox.
	 *
	 * @param int $post_id Product ID.
	 */
	public function save_product_option( $post_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce on product save.
		$value   = isset( $_POST['_musomo_quote_enabled'] ) ? 'yes' : 'no';
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : false;

		if ( $product instanceof WC_Product ) {
			$product->update_meta_data( '_musomo_quote_enabled', $value );
			$product->save();
			return;
		}

		update_post_meta( $post_id, '_musomo_quote_enabled', $value );
	}

	/**
	 * Resolve the current single product.
	 *
	 * @return WC_Product|false
	 */
	private function get_current_product() {
		global $product;

		if ( $product instanceof WC_Product ) {
			return $product;
		}

		$id = get_queried_object_id();
		if ( ! $id ) {
			return false;
		}

		$resolved = wc_get_product( $id );
		return $resolved instanceof WC_Product ? $resolved : false;
	}
}
