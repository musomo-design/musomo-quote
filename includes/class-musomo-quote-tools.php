<?php
/**
 * Tools: diagnostics, export / import, reset.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Musomo_Quote_Tools
 */
class Musomo_Quote_Tools {

	const MAX_IMPORT_BYTES = 1048576; // 1 MB.

	const TRANSIENT_PREFIX = 'mq_import_preview_';

	/**
	 * Singleton.
	 *
	 * @var Musomo_Quote_Tools|null
	 */
	private static $instance = null;

	/**
	 * @return Musomo_Quote_Tools
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — admin handlers only.
	 */
	private function __construct() {
		add_action( 'admin_post_musomo_quote_export_settings', array( $this, 'handle_export' ) );
		add_action( 'admin_post_musomo_quote_import_preview', array( $this, 'handle_import_preview' ) );
		add_action( 'admin_post_musomo_quote_import_confirm', array( $this, 'handle_import_confirm' ) );
		add_action( 'admin_post_musomo_quote_import_cancel', array( $this, 'handle_import_cancel' ) );
		add_action( 'admin_post_musomo_quote_reset_settings', array( $this, 'handle_reset' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	/**
	 * Capability required for tools actions.
	 *
	 * @return string
	 */
	public static function capability() {
		return 'manage_options';
	}

	/**
	 * Tools page URL.
	 *
	 * @param array $args Extra query args.
	 * @return string
	 */
	public static function tools_url( $args = array() ) {
		$url = admin_url( 'admin.php?page=musomo-quote-tools' );
		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}
		return $url;
	}

	/**
	 * Redirect helper with notice.
	 *
	 * @param string $notice Notice code.
	 * @param array  $extra  Extra query args.
	 */
	private function redirect_notice( $notice, $extra = array() ) {
		$args = array_merge(
			array( 'mq_notice' => sanitize_key( $notice ) ),
			$extra
		);
		wp_safe_redirect( self::tools_url( $args ) );
		exit;
	}

	/**
	 * Verify capability + nonce.
	 *
	 * @param string $action Nonce action.
	 * @param string $field  Nonce field name.
	 */
	private function verify_request( $action, $field = '_wpnonce' ) {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You do not have permission for this operation.', 'musomo-quote' ), 403 );
		}
		check_admin_referer( $action, $field );
	}

	/**
	 * Environment diagnostics (read-only, no secrets).
	 *
	 * @return array
	 */
	public static function get_environment_info() {
		$wc_active  = musomo_quote_is_woocommerce_active();
		$cf7_active = musomo_quote_is_cf7_active();
		$pll_active = musomo_quote_is_polylang_active();

		$wc_version = $wc_active && defined( 'WC_VERSION' ) ? WC_VERSION : '';
		$cf7_version = '';
		if ( $cf7_active ) {
			if ( defined( 'WPCF7_VERSION' ) ) {
				$cf7_version = WPCF7_VERSION;
			}
		}

		$pll_version = '';
		if ( $pll_active ) {
			if ( defined( 'POLYLANG_VERSION' ) ) {
				$pll_version = POLYLANG_VERSION;
			} elseif ( defined( 'POLYLANG_BASENAME' ) && function_exists( 'get_plugin_data' ) ) {
				// Soft: leave empty if constant missing.
				$pll_version = '';
			}
		}

		$settings = musomo_quote_get_settings();
		$mode     = isset( $settings['button_mode'] ) ? strtoupper( (string) $settings['button_mode'] ) : 'ADD';

		return array(
			'wordpress'     => array(
				'version'  => get_bloginfo( 'version' ),
				'language' => get_locale(),
				'timezone' => wp_timezone_string(),
			),
			'php'           => array(
				'version'             => PHP_VERSION,
				'memory_limit'        => (string) ini_get( 'memory_limit' ),
				'max_execution_time'  => (string) ini_get( 'max_execution_time' ),
				'upload_max_filesize' => (string) ini_get( 'upload_max_filesize' ),
			),
			'woocommerce'   => array(
				'active'  => $wc_active,
				'version' => $wc_version,
			),
			'cf7'           => array(
				'active'  => $cf7_active,
				'version' => $cf7_version,
			),
			'polylang'      => array(
				'active'  => $pll_active,
				'version' => $pll_version,
				'languages' => $pll_active && class_exists( 'Musomo_Quote_I18n' )
					? Musomo_Quote_I18n::get_language_count()
					: 0,
			),
			'musomo_quote'  => array(
				'version' => MUSOMO_QUOTE_VERSION,
				'enabled' => ! empty( $settings['enabled'] ),
				'mode'    => $mode,
			),
		);
	}

	/**
	 * Configuration status summary for Tools UI.
	 *
	 * @return array
	 */
	public static function get_configuration_status() {
		$settings = musomo_quote_get_settings();

		$form_id    = isset( $settings['cf7_form_id'] ) ? absint( $settings['cf7_form_id'] ) : 0;
		$form_label = __( 'Not configured', 'musomo-quote' );
		if ( $form_id ) {
			$title = class_exists( 'Musomo_Quote_CF7' ) ? Musomo_Quote_CF7::get_form_title( $form_id ) : '';
			if ( '' !== $title ) {
				/* translators: 1: form title, 2: form ID */
				$form_label = sprintf( __( '%1$s (#%2$d)', 'musomo-quote' ), $title, $form_id );
			} else {
				/* translators: %d: form ID */
				$form_label = sprintf( __( 'ID %d (not available)', 'musomo-quote' ), $form_id );
			}
		}

		$style = isset( $settings['appearance_style'] ) ? $settings['appearance_style'] : 'theme';
		if ( 'custom' === $style ) {
			$appearance = __( 'Custom', 'musomo-quote' );
		} elseif ( 'musomo' === $style ) {
			$appearance = __( 'Musomo', 'musomo-quote' );
		} else {
			$appearance = __( 'Inherit from theme', 'musomo-quote' );
		}

		$security_on = ! empty( $settings['security_honeypot_enabled'] )
			|| ! empty( $settings['security_time_trap_enabled'] )
			|| ! empty( $settings['security_content_filter_enabled'] )
			|| ! empty( $settings['security_rate_limit_enabled'] );

		return array(
			'frontend'           => ! empty( $settings['enabled'] ),
			'woocommerce'        => musomo_quote_is_woocommerce_active(),
			'cf7'                => musomo_quote_is_cf7_active(),
			'cf7_form_label'     => $form_label,
			'cf7_form_id'        => $form_id,
			'appearance'         => $appearance,
			'restrictions'       => musomo_quote_has_active_restrictions( $settings ),
			'security'           => $security_on,
			'honeypot'           => ! empty( $settings['security_honeypot_enabled'] ),
			'time_trap'          => ! empty( $settings['security_time_trap_enabled'] ),
			'content_filter'     => ! empty( $settings['security_content_filter_enabled'] ),
			'rate_protection'    => ! empty( $settings['security_rate_limit_enabled'] ),
			'polylang'           => musomo_quote_is_polylang_active(),
			'polylang_languages' => musomo_quote_is_polylang_active() && class_exists( 'Musomo_Quote_I18n' )
				? Musomo_Quote_I18n::get_language_count()
				: 0,
		);
	}

	/**
	 * System checks with OK / WARNING / ERROR.
	 *
	 * @return array[] List of { status, message }.
	 */
	public static function get_system_checks() {
		$checks   = array();
		$settings = musomo_quote_get_settings();

		if ( ! musomo_quote_is_woocommerce_active() ) {
			$checks[] = array(
				'status'  => 'error',
				'message' => __( 'WooCommerce is not active.', 'musomo-quote' ),
			);
		} else {
			$checks[] = array(
				'status'  => 'ok',
				'message' => __( 'WooCommerce is available.', 'musomo-quote' ),
			);
		}

		if ( ! musomo_quote_is_cf7_active() ) {
			$checks[] = array(
				'status'  => 'warning',
				'message' => __( 'Contact Form 7 is not active.', 'musomo-quote' ),
			);
		} else {
			$checks[] = array(
				'status'  => 'ok',
				'message' => __( 'Contact Form 7 is available.', 'musomo-quote' ),
			);
		}

		$linked = musomo_quote_get_linked_cf7_form_ids();
		if ( empty( $linked ) ) {
			$checks[] = array(
				'status'  => 'warning',
				'message' => __( 'No CF7 form linked.', 'musomo-quote' ),
			);
		} else {
			$checks[] = array(
				'status'  => 'ok',
				'message' => __( 'At least one CF7 form is linked.', 'musomo-quote' ),
			);
		}

		if ( empty( $settings['enabled'] ) ) {
			$checks[] = array(
				'status'  => 'warning',
				'message' => __( 'Frontend plugin disabled.', 'musomo-quote' ),
			);
		} else {
			$checks[] = array(
				'status'  => 'ok',
				'message' => __( 'Frontend plugin active.', 'musomo-quote' ),
			);
		}

		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			$checks[] = array(
				'status'  => 'error',
				'message' => __( 'PHP version below requirements (7.4+).', 'musomo-quote' ),
			);
		} elseif ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			$checks[] = array(
				'status'  => 'warning',
				'message' => __( 'PHP 7.4 detected: works, but PHP 8+ is recommended.', 'musomo-quote' ),
			);
		} else {
			$checks[] = array(
				'status'  => 'ok',
				'message' => __( 'PHP version suitable.', 'musomo-quote' ),
			);
		}

		$has_error   = false;
		$has_warning = false;
		foreach ( $checks as $c ) {
			if ( 'error' === $c['status'] ) {
				$has_error = true;
			}
			if ( 'warning' === $c['status'] ) {
				$has_warning = true;
			}
		}

		if ( ! $has_error && ! $has_warning ) {
			array_unshift(
				$checks,
				array(
					'status'  => 'ok',
					'message' => __( 'Everything configured correctly.', 'musomo-quote' ),
				)
			);
		}

		return $checks;
	}

	/**
	 * Plain-text system info for clipboard.
	 *
	 * @return string
	 */
	public static function get_system_info_text() {
		$env  = self::get_environment_info();
		$cfg  = self::get_configuration_status();
		$lines = array();

		$lines[] = 'Musomo Quote System Info';
		$lines[] = '------------------------';
		$lines[] = '';
		$lines[] = 'Musomo Quote: ' . $env['musomo_quote']['version'];
		$lines[] = 'WordPress: ' . $env['wordpress']['version'];
		$lines[] = 'Locale: ' . $env['wordpress']['language'];
		$lines[] = 'Timezone: ' . $env['wordpress']['timezone'];
		$lines[] = 'PHP: ' . $env['php']['version'];
		$lines[] = 'PHP memory_limit: ' . $env['php']['memory_limit'];
		$lines[] = 'PHP max_execution_time: ' . $env['php']['max_execution_time'];
		$lines[] = 'PHP upload_max_filesize: ' . $env['php']['upload_max_filesize'];
		$lines[] = 'WooCommerce: ' . ( $env['woocommerce']['active'] ? $env['woocommerce']['version'] : 'Not active' );
		$lines[] = 'Contact Form 7: ' . ( $env['cf7']['active'] ? ( $env['cf7']['version'] ? $env['cf7']['version'] : 'Active' ) : 'Not active' );
		$lines[] = 'Polylang: ' . ( $env['polylang']['active']
			? ( $env['polylang']['version'] ? $env['polylang']['version'] : 'Active' ) . ' (' . (int) $env['polylang']['languages'] . ' languages)'
			: 'Not active' );
		$lines[] = '';
		$lines[] = 'Plugin enabled: ' . ( $env['musomo_quote']['enabled'] ? 'Yes' : 'No' );
		$lines[] = 'Mode: ' . $env['musomo_quote']['mode'];
		$lines[] = 'CF7 form: ' . $cfg['cf7_form_label'];
		$lines[] = 'Appearance: ' . $cfg['appearance'];
		$lines[] = 'Restrictions: ' . ( $cfg['restrictions'] ? 'Active' : 'None' );
		$lines[] = '';
		$lines[] = 'Security:';
		$lines[] = 'Honeypot: ' . ( $cfg['honeypot'] ? 'ON' : 'OFF' );
		$lines[] = 'Time trap: ' . ( $cfg['time_trap'] ? 'ON' : 'OFF' );
		$lines[] = 'Content filter: ' . ( $cfg['content_filter'] ? 'ON' : 'OFF' );
		$lines[] = 'Rate limit: ' . ( $cfg['rate_protection'] ? 'ON' : 'OFF' );

		return implode( "\n", $lines );
	}

	/**
	 * Build export payload (settings only).
	 *
	 * @return array
	 */
	public static function build_export_payload() {
		$settings = musomo_quote_get_settings( true );
		// Strip any accidental non-settings keys.
		unset( $settings['_mq_settings_screen'] );

		return array(
			'plugin'      => 'musomo-quote',
			'version'     => MUSOMO_QUOTE_VERSION,
			'exported_at' => gmdate( 'c' ),
			'settings'    => $settings,
		);
	}

	/**
	 * Export handler.
	 */
	public function handle_export() {
		$this->verify_request( 'musomo_quote_export_settings' );

		$payload  = self::build_export_payload();
		$filename = 'musomo-quote-settings-' . gmdate( 'Y-m-d' ) . '.json';
		$json     = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			$this->redirect_notice( 'export_error' );
		}

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $json ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw JSON download.
		echo $json;
		exit;
	}

	/**
	 * Preview transient key for current user.
	 *
	 * @return string
	 */
	private function preview_transient_key() {
		return self::TRANSIENT_PREFIX . get_current_user_id();
	}

	/**
	 * Parse and validate uploaded JSON file.
	 *
	 * @return array{ok:bool,error?:string,payload?:array,warnings?:string[],sanitized?:array,groups?:string[]}
	 */
	public static function parse_import_file( $file ) {
		$result = array(
			'ok'       => false,
			'warnings' => array(),
			'groups'   => array(),
		);

		if ( empty( $file ) || ! is_array( $file ) || empty( $file['tmp_name'] ) ) {
			$result['error'] = __( 'No file selected.', 'musomo-quote' );
			return $result;
		}

		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			$result['error'] = __( 'Error uploading the file.', 'musomo-quote' );
			return $result;
		}

		$size = isset( $file['size'] ) ? (int) $file['size'] : 0;
		if ( $size <= 0 || $size > self::MAX_IMPORT_BYTES ) {
			$result['error'] = __( 'File too large or empty (1 MB limit).', 'musomo-quote' );
			return $result;
		}

		$tmp = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) {
			$result['error'] = __( 'Error uploading the file.', 'musomo-quote' );
			return $result;
		}

		$real_size = filesize( $tmp );
		if ( false === $real_size || $real_size <= 0 || $real_size > self::MAX_IMPORT_BYTES ) {
			$result['error'] = __( 'File too large or empty (1 MB limit).', 'musomo-quote' );
			return $result;
		}

		$name = isset( $file['name'] ) ? (string) $file['name'] : '';
		$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( 'json' !== $ext ) {
			$result['error'] = __( 'Only .json files are accepted.', 'musomo-quote' );
			return $result;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local upload tmp.
		$raw = file_get_contents( $file['tmp_name'] );
		if ( false === $raw || '' === $raw ) {
			$result['error'] = __( 'Unable to read the file.', 'musomo-quote' );
			return $result;
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
			$result['error'] = __( 'Invalid JSON.', 'musomo-quote' );
			return $result;
		}

		if ( ! isset( $data['plugin'] ) || 'musomo-quote' !== $data['plugin'] ) {
			$result['error'] = __( 'The file does not belong to Musomo Quote.', 'musomo-quote' );
			return $result;
		}

		if ( ! isset( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			$result['error'] = __( 'Missing or invalid settings in the file.', 'musomo-quote' );
			return $result;
		}

		$export_version = isset( $data['version'] ) ? (string) $data['version'] : '';
		if ( $export_version && version_compare( $export_version, MUSOMO_QUOTE_VERSION, '>' ) ) {
			$result['warnings'][] = __( 'The file comes from a future plugin version. Import will proceed cautiously.', 'musomo-quote' );
		}

		$prepared = self::sanitize_imported_settings( $data['settings'] );

		$result['ok']         = true;
		$result['payload']    = $data;
		$result['sanitized']  = $prepared['settings'];
		$result['warnings']   = array_merge( $result['warnings'], $prepared['warnings'] );
		$result['groups']     = $prepared['groups'];
		$result['filename']   = sanitize_file_name( $name );
		$result['version']    = $export_version ? $export_version : __( 'unknown', 'musomo-quote' );

		return $result;
	}

	/**
	 * Sanitize imported settings and merge-ready subset.
	 *
	 * Strategy: IMPORT merges valid imported keys onto current settings.
	 * Category IDs that do not exist on this site are filtered out.
	 * Unknown keys are ignored.
	 *
	 * @param array $imported Raw settings from JSON.
	 * @return array{settings:array,warnings:string[],groups:string[]}
	 */
	public static function sanitize_imported_settings( $imported ) {
		$imported = is_array( $imported ) ? $imported : array();
		$out      = array();
		$warnings = array();
		$groups   = array();

		// —— General ——
		$allowed_modes = array( 'add', 'replace', 'selected' );
		$allowed_positions = array(
			'before_add_to_cart',
			'after_add_to_cart',
			'after_summary',
			'after_title',
			'after_price',
			'custom',
		);

		if ( array_key_exists( 'enabled', $imported ) ) {
			$out['enabled'] = ! empty( $imported['enabled'] );
			$groups['general'] = true;
		}
		if ( isset( $imported['button_mode'] ) && in_array( $imported['button_mode'], $allowed_modes, true ) ) {
			$out['button_mode'] = $imported['button_mode'];
			$groups['general']  = true;
		}
		if ( isset( $imported['button_position'] ) && in_array( $imported['button_position'], $allowed_positions, true ) ) {
			$out['button_position'] = $imported['button_position'];
			$groups['general']      = true;
		}
		if ( isset( $imported['button_priority'] ) ) {
			$out['button_priority'] = absint( $imported['button_priority'] );
			$groups['general']      = true;
		}
		if ( isset( $imported['cf7_form_id'] ) ) {
			$out['cf7_form_id'] = absint( $imported['cf7_form_id'] );
			$groups['general']  = true;
		}
		if ( array_key_exists( 'delete_data_on_uninstall', $imported ) ) {
			$out['delete_data_on_uninstall'] = ! empty( $imported['delete_data_on_uninstall'] );
			$groups['general']               = true;
		}
		if ( array_key_exists( 'debug_mode', $imported ) ) {
			$out['debug_mode'] = ! empty( $imported['debug_mode'] );
			$groups['general'] = true;
		}

		// —— Appearance ——
		$has_appearance = false;
		foreach ( musomo_quote_appearance_keys() as $key ) {
			if ( array_key_exists( $key, $imported ) ) {
				$has_appearance = true;
				break;
			}
		}
		if ( $has_appearance ) {
			$appearance = musomo_quote_sanitize_appearance( $imported );
			foreach ( musomo_quote_appearance_keys() as $key ) {
				if ( array_key_exists( $key, $imported ) ) {
					$out[ $key ] = $appearance[ $key ];
				}
			}
			$groups['appearance'] = true;
		}

		// —— Texts ——
		$has_texts = false;
		foreach ( musomo_quote_text_keys() as $key ) {
			if ( array_key_exists( $key, $imported ) ) {
				$has_texts = true;
				break;
			}
		}
		if ( $has_texts ) {
			$texts = musomo_quote_sanitize_texts( $imported );
			foreach ( musomo_quote_text_keys() as $key ) {
				if ( array_key_exists( $key, $imported ) ) {
					$out[ $key ] = $texts[ $key ];
				}
			}
			$groups['texts'] = true;
		}

		// —— Translations ——
		if ( isset( $imported['translations'] ) && is_array( $imported['translations'] ) ) {
			$out['translations'] = Musomo_Quote_I18n::sanitize_translations(
				array( 'translations' => $imported['translations'] ),
				array( 'translations' => array() )
			);
			$groups['translations'] = true;

			if ( ! musomo_quote_is_polylang_active() && ! empty( $out['translations'] ) ) {
				$warnings[] = __( 'Polylang is not active, but the file contains translations. They will be saved and used when Polylang is available.', 'musomo-quote' );
			}
		}

		// —— Restrictions ——
		$has_restrictions = false;
		foreach ( musomo_quote_restriction_keys() as $key ) {
			if ( array_key_exists( $key, $imported ) ) {
				$has_restrictions = true;
				break;
			}
		}
		if ( $has_restrictions ) {
			$restrictions = musomo_quote_sanitize_restrictions( $imported );

			// Filter category IDs that do not exist on this site.
			if ( array_key_exists( 'restriction_category_ids', $imported ) ) {
				$raw_ids = isset( $restrictions['restriction_category_ids'] ) ? $restrictions['restriction_category_ids'] : array();
				$valid   = array();
				$dropped = 0;
				foreach ( $raw_ids as $term_id ) {
					$term_id = absint( $term_id );
					if ( ! $term_id ) {
						continue;
					}
					$term = get_term( $term_id, 'product_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						$valid[] = $term_id;
					} else {
						$dropped++;
					}
				}
				$restrictions['restriction_category_ids'] = array_values( array_unique( $valid ) );
				if ( $dropped > 0 ) {
					/* translators: %d: number of invalid category IDs removed */
					$warnings[] = sprintf( __( '%d category IDs not available on this site were removed.', 'musomo-quote' ), $dropped );
				}
			}

			foreach ( musomo_quote_restriction_keys() as $key ) {
				if ( array_key_exists( $key, $imported ) ) {
					$out[ $key ] = $restrictions[ $key ];
				}
			}
			$groups['restrictions'] = true;
		}

		// —— Security ——
		$has_security = false;
		foreach ( Musomo_Quote_Security::keys() as $key ) {
			if ( array_key_exists( $key, $imported ) ) {
				$has_security = true;
				break;
			}
		}
		if ( $has_security ) {
			$security = Musomo_Quote_Security::sanitize( $imported );
			foreach ( Musomo_Quote_Security::keys() as $key ) {
				if ( array_key_exists( $key, $imported ) ) {
					$out[ $key ] = $security[ $key ];
				}
			}
			$groups['security'] = true;
		}

		// —— CF7 Builder ——
		if ( isset( $imported['cf7_builder'] ) && is_array( $imported['cf7_builder'] ) ) {
			// normalize fills missing keys; sanitize whitelists/sanitizes values.
			$normalized         = Musomo_Quote_CF7_Builder::normalize( $imported['cf7_builder'] );
			$out['cf7_builder'] = Musomo_Quote_CF7_Builder::sanitize( array( 'cf7_builder' => $normalized ) );
			$groups['cf7_builder'] = true;
		}

		// CF7 ID warnings (do not strip — keep robust runtime behaviour).
		$cf7_ids_to_check = array();
		if ( isset( $out['cf7_form_id'] ) && absint( $out['cf7_form_id'] ) > 0 ) {
			$cf7_ids_to_check[] = absint( $out['cf7_form_id'] );
		}
		if ( ! empty( $out['translations'] ) && is_array( $out['translations'] ) ) {
			foreach ( $out['translations'] as $row ) {
				if ( ! empty( $row['cf7_form_id'] ) ) {
					$cf7_ids_to_check[] = absint( $row['cf7_form_id'] );
				}
			}
		}
		$cf7_ids_to_check = array_unique( $cf7_ids_to_check );
		foreach ( $cf7_ids_to_check as $fid ) {
			if ( ! self::cf7_form_exists( $fid ) ) {
				/* translators: %d: CF7 form ID */
				$warnings[] = sprintf( __( 'Contact Form 7 form #%d is not available on this site.', 'musomo-quote' ), $fid );
			}
		}

		$group_labels = array();
		$map          = array(
			'general'       => 'General',
			'appearance'    => 'Appearance',
			'texts'         => 'Texts',
			'translations'  => 'Translations',
			'restrictions'  => 'Restrictions',
			'security'      => 'Security',
			'cf7_builder'   => 'CF7 Builder',
		);
		foreach ( $map as $key => $label ) {
			if ( ! empty( $groups[ $key ] ) ) {
				$group_labels[] = $label;
			}
		}

		return array(
			'settings' => $out,
			'warnings' => $warnings,
			'groups'   => $group_labels,
		);
	}

	/**
	 * Whether a CF7 form post exists (publish preferred; any wpcf7 counts as available).
	 *
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	private static function cf7_form_exists( $form_id ) {
		$form_id = absint( $form_id );
		if ( ! $form_id || ! musomo_quote_is_cf7_active() ) {
			return false;
		}
		$post = get_post( $form_id );
		return $post && 'wpcf7_contact_form' === $post->post_type;
	}

	/**
	 * Handle import preview (step 1).
	 */
	public function handle_import_preview() {
		$this->verify_request( 'musomo_quote_import_preview' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated in parse_import_file.
		$file = isset( $_FILES['mq_import_file'] ) ? $_FILES['mq_import_file'] : null;
		$parsed = self::parse_import_file( $file );

		if ( empty( $parsed['ok'] ) ) {
			$this->redirect_notice(
				'import_invalid',
				array( 'mq_msg' => rawurlencode( isset( $parsed['error'] ) ? $parsed['error'] : '' ) )
			);
		}

		$token = wp_generate_password( 20, false, false );
		set_transient(
			$this->preview_transient_key(),
			array(
				'token'      => $token,
				'sanitized'  => $parsed['sanitized'],
				'warnings'   => $parsed['warnings'],
				'groups'     => $parsed['groups'],
				'filename'   => $parsed['filename'],
				'version'    => $parsed['version'],
				'created_at' => time(),
			),
			15 * MINUTE_IN_SECONDS
		);

		$this->redirect_notice(
			'import_preview',
			array( 'mq_import_token' => $token )
		);
	}

	/**
	 * Confirm import (step 2) — merge sanitized onto current.
	 */
	public function handle_import_confirm() {
		$this->verify_request( 'musomo_quote_import_confirm' );

		$token = isset( $_POST['mq_import_token'] ) ? sanitize_text_field( wp_unslash( $_POST['mq_import_token'] ) ) : '';
		$data  = get_transient( $this->preview_transient_key() );

		if ( ! is_array( $data ) || empty( $data['token'] ) || ! hash_equals( (string) $data['token'], $token ) ) {
			$this->redirect_notice( 'import_expired' );
		}

		$current  = musomo_quote_get_settings( true );
		$merged   = array_merge( $current, $data['sanitized'] );
		$defaults = musomo_quote_default_settings();
		$merged   = wp_parse_args( $merged, $defaults );

		update_option( 'musomo_quote_settings', $merged );
		musomo_quote_get_settings( true ); // refresh cache if any later call.

		delete_transient( $this->preview_transient_key() );

		$notice = ! empty( $data['warnings'] ) ? 'import_ok_warnings' : 'import_ok';
		$this->redirect_notice( $notice );
	}

	/**
	 * Cancel pending import preview.
	 */
	public function handle_import_cancel() {
		$this->verify_request( 'musomo_quote_import_cancel' );
		delete_transient( $this->preview_transient_key() );
		$this->redirect_notice( 'import_cancelled' );
	}

	/**
	 * Full settings reset to current defaults.
	 */
	public function handle_reset() {
		$this->verify_request( 'musomo_quote_reset_settings' );

		$confirm = isset( $_POST['mq_reset_confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['mq_reset_confirm'] ) ) : '';
		if ( 'RESET' !== $confirm ) {
			$this->redirect_notice( 'reset_confirm_required' );
		}

		update_option( 'musomo_quote_settings', musomo_quote_default_settings() );
		musomo_quote_get_settings( true );
		delete_transient( $this->preview_transient_key() );

		$this->redirect_notice( 'reset_ok' );
	}

	/**
	 * Get pending import preview for current user (if token matches).
	 *
	 * @param string $token Token from query.
	 * @return array|null
	 */
	public static function get_pending_preview( $token ) {
		$key  = self::TRANSIENT_PREFIX . get_current_user_id();
		$data = get_transient( $key );
		if ( ! is_array( $data ) || empty( $data['token'] ) || ! hash_equals( (string) $data['token'], (string) $token ) ) {
			return null;
		}
		return $data;
	}

	/**
	 * Admin notices for tools operations.
	 */
	public function render_notices() {
		if ( ! current_user_can( self::capability() ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'musomo-quote-tools' !== $page ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice = isset( $_GET['mq_notice'] ) ? sanitize_key( wp_unslash( $_GET['mq_notice'] ) ) : '';
		if ( ! $notice ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = isset( $_GET['mq_msg'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['mq_msg'] ) ) ) : '';

		$map = array(
			'import_ok'              => array( 'success', __( 'Configuration imported successfully.', 'musomo-quote' ) ),
			'import_ok_warnings'     => array( 'warning', __( 'Import completed with warnings. Check diagnostics and CF7 forms.', 'musomo-quote' ) ),
			'import_invalid'         => array( 'error', $msg ? $msg : __( 'Invalid file.', 'musomo-quote' ) ),
			'import_expired'         => array( 'error', __( 'Import preview expired. Upload the file again.', 'musomo-quote' ) ),
			'import_cancelled'       => array( 'info', __( 'Import cancelled.', 'musomo-quote' ) ),
			'reset_ok'               => array( 'success', __( 'Settings restored.', 'musomo-quote' ) ),
			'reset_confirm_required' => array( 'error', __( 'To confirm reset, type RESET in the confirmation field.', 'musomo-quote' ) ),
			'export_error'           => array( 'error', __( 'Unable to generate export.', 'musomo-quote' ) ),
		);

		if ( ! isset( $map[ $notice ] ) ) {
			return;
		}

		list( $class, $text ) = $map[ $notice ];
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $text )
		);
	}
}
