<?php
/**
 * Security and antispam for Musomo Quote (CF7-linked form only).
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Musomo_Quote_Security
 */
class Musomo_Quote_Security {

	const HONEYPOT_NAME   = 'mq_hp_website';
	const TIME_FIELD_NAME = 'mq_opened_at';

	/**
	 * Singleton instance.
	 *
	 * @var Musomo_Quote_Security|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return Musomo_Quote_Security
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
		if ( ! musomo_quote_is_cf7_active() ) {
			return;
		}

		add_filter( 'wpcf7_form_elements', array( $this, 'inject_form_fields' ), 20 );
		add_filter( 'wpcf7_spam', array( $this, 'filter_spam' ), 20, 2 );
		add_action( 'wpcf7_submit', array( $this, 'on_submit' ), 10, 2 );
	}

	/**
	 * Default security settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'security_honeypot_enabled'       => true,
			'security_time_trap_enabled'      => true,
			'security_min_submit_seconds'     => 3,
			'security_content_filter_enabled' => true,
			'security_blocked_patterns'       => self::default_patterns_string(),
			'security_rate_limit_enabled'     => true,
			'security_rate_limit_count'       => 5,
			'security_rate_limit_window'      => 10,
		);
	}

	/**
	 * Default blocked patterns as newline string.
	 *
	 * @return string
	 */
	public static function default_patterns_string() {
		return implode(
			"\n",
			array(
				'http://',
				'https://',
				'.pro',
				'.xyz',
				'business register',
				'seo service',
				'crypto',
				'telegram',
			)
		);
	}

	/**
	 * Security setting keys.
	 *
	 * @return string[]
	 */
	public static function keys() {
		return array_keys( self::defaults() );
	}

	/**
	 * Sanitize security settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$out      = array();

		$out['security_honeypot_enabled']       = ! empty( $input['security_honeypot_enabled'] );
		$out['security_time_trap_enabled']      = ! empty( $input['security_time_trap_enabled'] );
		$out['security_content_filter_enabled'] = ! empty( $input['security_content_filter_enabled'] );
		$out['security_rate_limit_enabled']     = ! empty( $input['security_rate_limit_enabled'] );

		$seconds = isset( $input['security_min_submit_seconds'] ) ? absint( $input['security_min_submit_seconds'] ) : $defaults['security_min_submit_seconds'];
		$out['security_min_submit_seconds'] = max( 1, min( 15, $seconds ) );

		$count = isset( $input['security_rate_limit_count'] ) ? absint( $input['security_rate_limit_count'] ) : $defaults['security_rate_limit_count'];
		$out['security_rate_limit_count'] = max( 1, min( 50, $count ) );

		$window = isset( $input['security_rate_limit_window'] ) ? absint( $input['security_rate_limit_window'] ) : $defaults['security_rate_limit_window'];
		$out['security_rate_limit_window'] = max( 1, min( 1440, $window ) );

		$raw = isset( $input['security_blocked_patterns'] ) ? (string) $input['security_blocked_patterns'] : $defaults['security_blocked_patterns'];
		$raw = sanitize_textarea_field( wp_unslash( $raw ) );
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$clean = array();
		if ( is_array( $lines ) ) {
			foreach ( $lines as $line ) {
				$line = trim( (string) $line );
				if ( '' !== $line ) {
					$clean[] = sanitize_text_field( $line );
				}
			}
		}
		$out['security_blocked_patterns'] = implode( "\n", $clean );

		return $out;
	}

	/**
	 * Whether a CF7 contact form is linked to Musomo Quote
	 * (global form or any language override).
	 *
	 * @param mixed $contact_form CF7 form object or ID.
	 * @return bool
	 */
	public function is_linked_form( $contact_form ) {
		$id = 0;
		if ( is_object( $contact_form ) && method_exists( $contact_form, 'id' ) ) {
			$id = (int) $contact_form->id();
		} elseif ( is_numeric( $contact_form ) ) {
			$id = absint( $contact_form );
		}

		if ( ! $id ) {
			return false;
		}

		$linked = musomo_quote_get_linked_cf7_form_ids();
		return in_array( $id, $linked, true );
	}

	/**
	 * @deprecated Use musomo_quote_get_linked_cf7_form_ids() / is_linked_form().
	 *
	 * @return int First linked form ID or 0.
	 */
	public function get_linked_form_id() {
		$ids = musomo_quote_get_linked_cf7_form_ids();
		return ! empty( $ids ) ? (int) $ids[0] : 0;
	}

	/**
	 * Inject honeypot + time field into linked CF7 form only.
	 *
	 * @param string $elements Form HTML.
	 * @return string
	 */
	public function inject_form_fields( $elements ) {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return $elements;
		}

		$form = WPCF7_ContactForm::get_current();
		if ( ! $form || ! $this->is_linked_form( $form ) ) {
			return $elements;
		}

		$settings = musomo_quote_get_settings();
		$html     = '';

		if ( ! empty( $settings['security_honeypot_enabled'] ) ) {
			$html .= sprintf(
				'<div class="musomo-quote-honeypot" aria-hidden="true">' .
				'<label for="%1$s">%2$s</label>' .
				'<input type="text" name="%1$s" id="%1$s" value="" autocomplete="off" tabindex="-1" />' .
				'</div>',
				esc_attr( self::HONEYPOT_NAME ),
				esc_html__( 'Website', 'musomo-quote' )
			);
		}

		if ( ! empty( $settings['security_time_trap_enabled'] ) ) {
			$html .= sprintf(
				'<input type="hidden" name="%1$s" class="musomo-quote-opened-at" value="" autocomplete="off" />',
				esc_attr( self::TIME_FIELD_NAME )
			);
		}

		return $elements . $html;
	}

	/**
	 * Mark linked-form submissions as spam when protections fail.
	 *
	 * @param bool                  $spam       Current spam flag.
	 * @param WPCF7_Submission|null $submission Submission.
	 * @return bool
	 */
	public function filter_spam( $spam, $submission = null ) {
		if ( $spam ) {
			return $spam;
		}

		if ( ! $submission || ! is_object( $submission ) || ! method_exists( $submission, 'get_contact_form' ) ) {
			return $spam;
		}

		$contact_form = $submission->get_contact_form();
		if ( ! $this->is_linked_form( $contact_form ) ) {
			return $spam;
		}

		$settings = musomo_quote_get_settings();
		$posted   = method_exists( $submission, 'get_posted_data' ) ? $submission->get_posted_data() : array();
		if ( ! is_array( $posted ) ) {
			$posted = array();
		}

		if ( ! empty( $settings['security_rate_limit_enabled'] ) && $this->is_rate_limited() ) {
			return true;
		}

		if ( ! empty( $settings['security_honeypot_enabled'] ) ) {
			$hp = isset( $_POST[ self::HONEYPOT_NAME ] ) ? wp_unslash( $_POST[ self::HONEYPOT_NAME ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( is_string( $hp ) && '' !== trim( $hp ) ) {
				return true;
			}
		}

		if ( ! empty( $settings['security_time_trap_enabled'] ) ) {
			$min    = isset( $settings['security_min_submit_seconds'] ) ? absint( $settings['security_min_submit_seconds'] ) : 3;
			$opened = isset( $_POST[ self::TIME_FIELD_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::TIME_FIELD_NAME ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// Soft trap: only enforce when a valid client timestamp is present (avoids false positives if field is missing).
			if ( '' !== $opened && ctype_digit( $opened ) ) {
				$opened_ts = (int) $opened;
				$now       = time();
				if ( $opened_ts > $now + 60 || ( $now - $opened_ts ) < $min ) {
					return true;
				}
			}
		}

		if ( ! empty( $settings['security_content_filter_enabled'] ) && $this->content_matches_blocked_patterns( $posted, $settings ) ) {
			return true;
		}

		return $spam;
	}

	/**
	 * Count submission attempts for the linked form only.
	 *
	 * @param WPCF7_ContactForm $contact_form Form.
	 * @param array             $result       Result.
	 */
	public function on_submit( $contact_form, $result = array() ) {
		if ( ! $this->is_linked_form( $contact_form ) ) {
			return;
		}

		$settings = musomo_quote_get_settings();
		if ( empty( $settings['security_rate_limit_enabled'] ) ) {
			return;
		}

		$this->increment_rate_counter();
	}

	/**
	 * Whether posted user content matches a blocked pattern.
	 *
	 * Excludes musomo_product_*, musomo_quantity, legacy product-* aliases, honeypot/time fields.
	 *
	 * @param array $posted   Posted data.
	 * @param array $settings Settings.
	 * @return bool
	 */
	private function content_matches_blocked_patterns( $posted, $settings ) {
		$patterns = $this->get_patterns_list( $settings );
		if ( empty( $patterns ) ) {
			return false;
		}

		$skip_prefixes = array(
			'musomo_product_',
			'product-',
			'product_',
		);
		$skip_exact    = array(
			'musomo_quantity',
			'quantity',
			self::HONEYPOT_NAME,
			self::TIME_FIELD_NAME,
			'_wpcf7',
			'_wpcf7_version',
			'_wpcf7_locale',
			'_wpcf7_unit_tag',
			'_wpcf7_container_post',
			'_wpcf7_posted_data_hash',
		);

		foreach ( $posted as $name => $value ) {
			$name = (string) $name;

			if ( in_array( $name, $skip_exact, true ) ) {
				continue;
			}

			if ( 0 === strpos( $name, '_wpcf7' ) ) {
				continue;
			}

			$skip = false;
			foreach ( $skip_prefixes as $prefix ) {
				if ( 0 === strpos( $name, $prefix ) ) {
					$skip = true;
					break;
				}
			}
			if ( $skip ) {
				continue;
			}

			// Skip email-like field names.
			if ( false !== stripos( $name, 'email' ) || false !== stripos( $name, 'mail' ) ) {
				continue;
			}

			$text = is_array( $value ) ? implode( ' ', $value ) : (string) $value;
			$text = trim( $text );
			if ( '' === $text ) {
				continue;
			}

			$haystack = strtolower( $text );
			foreach ( $patterns as $pattern ) {
				$needle = strtolower( $pattern );
				if ( '' !== $needle && $this->text_contains_pattern( $haystack, $needle ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Match a blocked pattern against haystack without TLD false positives.
	 *
	 * Patterns like ".pro" / ".xyz" match as domain suffixes (e.g. spam.pro),
	 * not as substrings inside words like "my.project" or "file.protocol".
	 * Other patterns keep simple case-insensitive substring matching.
	 *
	 * @param string $haystack Lowercased text.
	 * @param string $needle   Lowercased pattern.
	 * @return bool
	 */
	private function text_contains_pattern( $haystack, $needle ) {
		$haystack = (string) $haystack;
		$needle   = (string) $needle;

		// TLD-style patterns: ".tld" where tld is alphanumeric.
		if ( preg_match( '/^\.[a-z0-9]+$/i', $needle ) ) {
			$escaped = preg_quote( $needle, '/' );
			return (bool) preg_match( '/[a-z0-9]' . $escaped . '(?=[^a-z0-9]|$)/i', $haystack );
		}

		return false !== strpos( $haystack, $needle );
	}

	/**
	 * Parse blocked patterns into a list.
	 *
	 * @param array $settings Settings.
	 * @return string[]
	 */
	private function get_patterns_list( $settings ) {
		$raw = isset( $settings['security_blocked_patterns'] ) ? (string) $settings['security_blocked_patterns'] : '';
		if ( '' === trim( $raw ) ) {
			$raw = self::default_patterns_string();
		}
		$lines = preg_split( '/\r\n|\r|\n/', $raw );
		$out   = array();
		if ( is_array( $lines ) ) {
			foreach ( $lines as $line ) {
				$line = trim( (string) $line );
				if ( '' !== $line ) {
					$out[] = $line;
				}
			}
		}
		return $out;
	}

	/**
	 * Client IP for rate limiting (hashed later). Prefer REMOTE_ADDR.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
		$ip = sanitize_text_field( $ip );
		return $ip ? $ip : 'unknown';
	}

	/**
	 * Transient key for rate limit.
	 *
	 * @return string
	 */
	private function get_rate_transient_key() {
		$ip   = $this->get_client_ip();
		$hash = hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) );
		// Shared across all Musomo-linked CF7 forms (not per-form).
		return 'mq_rl_' . substr( $hash, 0, 32 );
	}

	/**
	 * Whether current client is rate limited for the linked form.
	 *
	 * @return bool
	 */
	private function is_rate_limited() {
		$settings = musomo_quote_get_settings();
		$max      = isset( $settings['security_rate_limit_count'] ) ? absint( $settings['security_rate_limit_count'] ) : 5;
		$count    = $this->get_rate_count( $this->get_rate_transient_key() );
		return $count >= max( 1, $max );
	}

	/**
	 * Read current rate-limit counter (supports legacy int transients).
	 *
	 * @param string $key Transient key.
	 * @return int
	 */
	private function get_rate_count( $key ) {
		$data = get_transient( $key );
		if ( is_array( $data ) && isset( $data['count'] ) ) {
			return absint( $data['count'] );
		}
		return absint( $data );
	}

	/**
	 * Increment rate counter after a linked-form submission attempt.
	 *
	 * Uses a fixed window: TTL does not slide on every submit.
	 */
	private function increment_rate_counter() {
		$settings = musomo_quote_get_settings();
		$window   = isset( $settings['security_rate_limit_window'] ) ? absint( $settings['security_rate_limit_window'] ) : 10;
		$ttl      = max( 60, $window * MINUTE_IN_SECONDS );
		$key      = $this->get_rate_transient_key();
		$data     = get_transient( $key );
		$now      = time();

		if ( is_array( $data ) && isset( $data['count'], $data['expires'] ) ) {
			$expires   = absint( $data['expires'] );
			$remaining = $expires - $now;
			if ( $remaining < 1 ) {
				set_transient(
					$key,
					array(
						'count'   => 1,
						'expires' => $now + $ttl,
					),
					$ttl
				);
				return;
			}

			set_transient(
				$key,
				array(
					'count'   => absint( $data['count'] ) + 1,
					'expires' => $expires,
				),
				$remaining
			);
			return;
		}

		// Legacy integer transient or empty.
		$count = is_numeric( $data ) ? absint( $data ) + 1 : 1;
		set_transient(
			$key,
			array(
				'count'   => $count,
				'expires' => $now + $ttl,
			),
			$ttl
		);
	}

	/**
	 * Detect Turnstile / external CAPTCHA status (informational).
	 *
	 * @return string Key: managed|not_detected|cf7_missing
	 */
	public static function detect_turnstile_status() {
		if ( ! musomo_quote_is_cf7_active() ) {
			return 'cf7_missing';
		}

		// Simple Cloudflare Turnstile plugin and similar.
		if ( defined( 'WPCF7_TURNSTILE' ) || class_exists( 'CFTurnstile' ) || function_exists( 'cfturnstile_check' ) ) {
			return 'managed';
		}

		$key_opts = array( 'cfturnstile_key', 'cfturnstile_site_key', 'turnstile_site_key', 'wpcf7_turnstile_sitekey' );
		foreach ( $key_opts as $opt ) {
			$val = get_option( $opt, '' );
			if ( is_string( $val ) && '' !== trim( $val ) ) {
				return 'managed';
			}
		}

		// Linked form contains turnstile markup/shortcode.
		$linked_ids = musomo_quote_get_linked_cf7_form_ids();
		foreach ( $linked_ids as $form_id ) {
			$form_id = absint( $form_id );
			if ( ! $form_id ) {
				continue;
			}

			$post = get_post( $form_id );
			if ( $post && ! empty( $post->post_content ) ) {
				$content = (string) $post->post_content;
				if (
					false !== stripos( $content, 'turnstile' )
					|| false !== stripos( $content, 'cf-turnstile' )
				) {
					return 'managed';
				}
			}

			if ( function_exists( 'wpcf7_contact_form' ) ) {
				$cf = wpcf7_contact_form( $form_id );
				if ( $cf && method_exists( $cf, 'prop' ) ) {
					$form_prop = (string) $cf->prop( 'form' );
					if (
						false !== stripos( $form_prop, 'turnstile' )
						|| false !== stripos( $form_prop, 'cf-turnstile' )
					) {
						return 'managed';
					}
				}
			}
		}

		return 'not_detected';
	}

	/**
	 * Human label for Turnstile status.
	 *
	 * @return string
	 */
	public static function get_turnstile_status_label() {
		$status = self::detect_turnstile_status();
		if ( 'managed' === $status ) {
			return __( 'Managed by Contact Form 7 / external integration', 'musomo-quote' );
		}
		if ( 'cf7_missing' === $status ) {
			return __( 'CF7 not available', 'musomo-quote' );
		}
		return __( 'Not detected automatically', 'musomo-quote' );
	}

	/**
	 * Antispam aggregate status: active|partial|off.
	 *
	 * @return string
	 */
	public static function get_antispam_status_key() {
		$s = musomo_quote_get_settings();
		$flags = array(
			! empty( $s['security_honeypot_enabled'] ),
			! empty( $s['security_time_trap_enabled'] ),
			! empty( $s['security_content_filter_enabled'] ),
			! empty( $s['security_rate_limit_enabled'] ),
		);
		$on = count( array_filter( $flags ) );
		if ( 0 === $on ) {
			return 'off';
		}
		if ( $on >= 3 ) {
			return 'active';
		}
		return 'partial';
	}

	/**
	 * Human label for antispam status.
	 *
	 * @return string
	 */
	public static function get_antispam_status_label() {
		$key = self::get_antispam_status_key();
		if ( 'active' === $key ) {
			return __( 'Active', 'musomo-quote' );
		}
		if ( 'partial' === $key ) {
			return __( 'Partial', 'musomo-quote' );
		}
		return __( 'Disabled', 'musomo-quote' );
	}
}
