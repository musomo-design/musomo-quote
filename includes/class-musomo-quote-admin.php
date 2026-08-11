<?php
/**
 * Admin area for Musomo Quote.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Musomo_Quote_Admin
 */
class Musomo_Quote_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var Musomo_Quote_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Musomo_Quote_Admin
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
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect_legacy_cf7_builder' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'dependency_notices' ) );
		Musomo_Quote_Tools::instance();
	}

	/**
	 * Register admin menu and submenus.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Musomo Quote', 'musomo-quote' ),
			__( 'Mu Quote', 'musomo-quote' ),
			'manage_options',
			'musomo-quote',
			array( $this, 'render_dashboard' ),
			'dashicons-email-alt',
			56
		);

		add_submenu_page(
			'musomo-quote',
			__( 'Dashboard', 'musomo-quote' ),
			__( 'Dashboard', 'musomo-quote' ),
			'manage_options',
			'musomo-quote',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'musomo-quote',
			__( 'Settings', 'musomo-quote' ),
			__( 'Settings', 'musomo-quote' ),
			'manage_options',
			'musomo-quote-settings',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'musomo-quote',
			__( 'Appearance', 'musomo-quote' ),
			__( 'Appearance', 'musomo-quote' ),
			'manage_options',
			'musomo-quote-appearance',
			array( $this, 'render_appearance' )
		);

		add_submenu_page(
			'musomo-quote',
			__( 'Texts & translations', 'musomo-quote' ),
			__( 'Texts & translations', 'musomo-quote' ),
			'manage_options',
			'musomo-quote-translations',
			array( $this, 'render_translations' )
		);

		add_submenu_page(
			'musomo-quote',
			__( 'Restrictions', 'musomo-quote' ),
			__( 'Restrictions', 'musomo-quote' ),
			'manage_options',
			'musomo-quote-restrictions',
			array( $this, 'render_restrictions' )
		);

		add_submenu_page(
			'musomo-quote',
			__( 'CF7 Template', 'musomo-quote' ),
			__( 'CF7 Template', 'musomo-quote' ),
			'manage_options',
			'musomo-quote-cf7-builder',
			array( $this, 'render_cf7_builder' )
		);

		add_submenu_page(
			'musomo-quote',
			__( 'Tools', 'musomo-quote' ),
			__( 'Tools', 'musomo-quote' ),
			'manage_options',
			'musomo-quote-tools',
			array( $this, 'render_tools' )
		);
	}

	/**
	 * Register settings via Options API.
	 */
	public function register_settings() {
		register_setting(
			'musomo_quote_settings_group',
			'musomo_quote_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => musomo_quote_default_settings(),
			)
		);
	}

	/**
	 * Sanitize settings array.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults = musomo_quote_default_settings();
		$current  = musomo_quote_get_settings( true );
		$input    = is_array( $input ) ? $input : array();
		$screen   = isset( $input['_mq_settings_screen'] ) ? sanitize_key( $input['_mq_settings_screen'] ) : 'general';

		$sanitized = $current;

		if ( 'appearance' === $screen ) {
			if ( ! empty( $input['reset_appearance'] ) ) {
				foreach ( musomo_quote_appearance_defaults() as $key => $value ) {
					$sanitized[ $key ] = $value;
				}
				return wp_parse_args( $sanitized, $defaults );
			}

			$appearance = musomo_quote_sanitize_appearance( $input );
			foreach ( $appearance as $key => $value ) {
				$sanitized[ $key ] = $value;
			}

			return wp_parse_args( $sanitized, $defaults );
		}

		if ( 'texts' === $screen ) {
			if ( ! empty( $input['reset_translations'] ) ) {
				$sanitized['translations'] = array();
				return wp_parse_args( $sanitized, $defaults );
			}

			if ( ! empty( $input['reset_texts'] ) ) {
				foreach ( musomo_quote_text_defaults() as $key => $value ) {
					$sanitized[ $key ] = $value;
				}
				return wp_parse_args( $sanitized, $defaults );
			}

			$texts = musomo_quote_sanitize_texts( $input );
			foreach ( $texts as $key => $value ) {
				$sanitized[ $key ] = $value;
			}

			if ( Musomo_Quote_I18n::is_polylang_active() ) {
				$sanitized['translations'] = Musomo_Quote_I18n::sanitize_translations( $input, $current );
			}

			return wp_parse_args( $sanitized, $defaults );
		}

		if ( 'cf7_builder' === $screen ) {
			if ( ! empty( $input['reset_cf7_builder'] ) ) {
				$sanitized['cf7_builder'] = Musomo_Quote_CF7_Builder::defaults();
				return wp_parse_args( $sanitized, $defaults );
			}

			$sanitized['cf7_builder'] = Musomo_Quote_CF7_Builder::sanitize( $input );
			return wp_parse_args( $sanitized, $defaults );
		}

		if ( 'restrictions' === $screen ) {
			if ( ! empty( $input['reset_restrictions'] ) ) {
				foreach ( musomo_quote_restriction_defaults() as $key => $value ) {
					$sanitized[ $key ] = $value;
				}
				return wp_parse_args( $sanitized, $defaults );
			}

			if ( ! empty( $input['reset_security'] ) ) {
				foreach ( Musomo_Quote_Security::defaults() as $key => $value ) {
					$sanitized[ $key ] = $value;
				}
				return wp_parse_args( $sanitized, $defaults );
			}

			$restrictions = musomo_quote_sanitize_restrictions( $input );

			// Category select is disabled when mode=all and not posted — keep stored IDs.
			if (
				'all' === $restrictions['restriction_category_mode']
				&& ! isset( $input['restriction_category_ids'] )
				&& isset( $current['restriction_category_ids'] )
				&& is_array( $current['restriction_category_ids'] )
			) {
				$restrictions['restriction_category_ids'] = array_values(
					array_filter( array_map( 'absint', $current['restriction_category_ids'] ) )
				);
			}

			foreach ( $restrictions as $key => $value ) {
				$sanitized[ $key ] = $value;
			}

			$security = Musomo_Quote_Security::sanitize( $input );
			foreach ( $security as $key => $value ) {
				$sanitized[ $key ] = $value;
			}

			return wp_parse_args( $sanitized, $defaults );
		}

		$allowed_modes = array( 'add', 'replace', 'selected' );
		$allowed_positions = array(
			'before_add_to_cart',
			'after_add_to_cart',
			'after_summary',
			'after_title',
			'after_price',
			'custom',
		);

		$sanitized['enabled'] = ! empty( $input['enabled'] );

		if ( isset( $input['button_mode'] ) && in_array( $input['button_mode'], $allowed_modes, true ) ) {
			$sanitized['button_mode'] = $input['button_mode'];
		}

		if ( isset( $input['button_position'] ) && in_array( $input['button_position'], $allowed_positions, true ) ) {
			$sanitized['button_position'] = $input['button_position'];
		}

		if ( isset( $input['button_priority'] ) ) {
			$sanitized['button_priority'] = absint( $input['button_priority'] );
		}

		if ( isset( $input['cf7_form_id'] ) ) {
			$sanitized['cf7_form_id'] = absint( $input['cf7_form_id'] );
		}

		$sanitized['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] );
		$sanitized['debug_mode']               = ! empty( $input['debug_mode'] );

		return wp_parse_args( $sanitized, $defaults );
	}

	/**
	 * Enqueue admin assets only on Musomo Quote screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'musomo-quote' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'musomo-quote-admin',
			MUSOMO_QUOTE_URL . 'admin/assets/admin.css',
			array(),
			MUSOMO_QUOTE_VERSION
		);

		wp_enqueue_script(
			'musomo-quote-admin',
			MUSOMO_QUOTE_URL . 'admin/assets/admin.js',
			array(),
			MUSOMO_QUOTE_VERSION,
			true
		);

		wp_localize_script(
			'musomo-quote-admin',
			'musomoQuoteAdmin',
			array(
				'i18n' => array(
					'copied'           => __( 'Copied!', 'musomo-quote' ),
					'copyFailed'       => __( 'Copy failed', 'musomo-quote' ),
					'resetTypeConfirm' => __( 'Type RESET to confirm the reset.', 'musomo-quote' ),
					'resetConfirm'     => __( 'This will restore all Musomo Quote settings. Continue?', 'musomo-quote' ),
				),
			)
		);

		// CF7 Template builder page.
		if ( false !== strpos( $hook, 'musomo-quote-cf7-builder' ) ) {
			wp_enqueue_script(
				'musomo-quote-cf7-builder',
				MUSOMO_QUOTE_URL . 'admin/assets/cf7-builder.js',
				array(),
				MUSOMO_QUOTE_VERSION,
				true
			);
			wp_localize_script(
				'musomo-quote-cf7-builder',
				'musomoQuoteCf7Builder',
				Musomo_Quote_CF7_Builder::js_payload()
			);
		}

		if ( false !== strpos( $hook, 'musomo-quote-appearance' ) ) {
			wp_enqueue_style(
				'musomo-quote',
				MUSOMO_QUOTE_URL . 'public/css/musomo-quote.css',
				array( 'musomo-quote-admin' ),
				MUSOMO_QUOTE_VERSION
			);

			wp_add_inline_style( 'musomo-quote', musomo_quote_get_appearance_inline_css() );

			wp_enqueue_script(
				'musomo-quote-appearance',
				MUSOMO_QUOTE_URL . 'admin/assets/appearance.js',
				array(),
				MUSOMO_QUOTE_VERSION,
				true
			);

			wp_localize_script(
				'musomo-quote-appearance',
				'musomoQuoteAppearance',
				array(
					'defaults' => musomo_quote_appearance_defaults(),
					'presets'  => array(
						'theme'  => musomo_quote_get_resolved_appearance( array_merge( musomo_quote_get_settings(), array( 'appearance_style' => 'theme' ) ) ),
						'musomo' => musomo_quote_get_resolved_appearance( array_merge( musomo_quote_get_settings(), array( 'appearance_style' => 'musomo' ) ) ),
					),
				)
			);
		}
	}

	/**
	 * Show notices if required dependencies are missing.
	 */
	public function dependency_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || strpos( $screen->id, 'musomo-quote' ) === false ) {
			return;
		}

		if ( ! musomo_quote_is_woocommerce_active() ) {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Musomo Quote requires WooCommerce. Install and activate WooCommerce to use product features.', 'musomo-quote' );
			echo '</p></div>';
		}

		if ( ! musomo_quote_is_cf7_active() ) {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__( 'Musomo Quote requires Contact Form 7. Install and activate Contact Form 7 to use quote forms.', 'musomo-quote' );
			echo '</p></div>';
		}
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = musomo_quote_get_settings();
		include MUSOMO_QUOTE_PATH . 'admin/views/dashboard.php';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = musomo_quote_get_settings();
		include MUSOMO_QUOTE_PATH . 'admin/views/settings-general.php';
	}

	/**
	 * Render restrictions page.
	 */
	public function render_restrictions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = musomo_quote_get_settings();
		include MUSOMO_QUOTE_PATH . 'admin/views/settings-restrictions.php';
	}

	/**
	 * Render texts & translations page.
	 */
	public function render_translations() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = musomo_quote_get_settings();
		include MUSOMO_QUOTE_PATH . 'admin/views/settings-translations.php';
	}

	/**
	 * Render appearance page.
	 */
	public function render_appearance() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = musomo_quote_get_settings();
		include MUSOMO_QUOTE_PATH . 'admin/views/settings-appearance.php';
	}

	/**
	 * Redirect legacy Tools → Template CF7 URL to the main Template CF7 page.
	 */
	public function maybe_redirect_legacy_cf7_builder() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		if ( 'musomo-quote-tools' === $page && 'cf7' === $tab ) {
			wp_safe_redirect( admin_url( 'admin.php?page=musomo-quote-cf7-builder' ) );
			exit;
		}
	}

	/**
	 * Render CF7 template builder page.
	 */
	public function render_cf7_builder() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page_title = __( 'CF7 Template', 'musomo-quote' );
		include MUSOMO_QUOTE_PATH . 'admin/views/partial-header.php';
		include MUSOMO_QUOTE_PATH . 'admin/views/settings-cf7-builder.php';
		include MUSOMO_QUOTE_PATH . 'admin/views/partial-footer.php';
	}

	/**
	 * Render tools page.
	 */
	public function render_tools() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include MUSOMO_QUOTE_PATH . 'admin/views/settings-tools.php';
	}

	/**
	 * Render placeholder for pages not yet implemented.
	 */
	public function render_placeholder() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include MUSOMO_QUOTE_PATH . 'admin/views/placeholder.php';
	}
}
