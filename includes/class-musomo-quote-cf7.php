<?php
/**
 * Contact Form 7 integration for Musomo Quote.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Musomo_Quote_CF7
 */
class Musomo_Quote_CF7 {

	/**
	 * Singleton instance.
	 *
	 * @var Musomo_Quote_CF7|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Musomo_Quote_CF7
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

		// Ensure musomo_product_image is a pure absolute URL in posted data / mail tags.
		add_filter( 'wpcf7_posted_data', array( $this, 'normalize_posted_product_image' ), 20 );
		add_filter( 'wpcf7_mail_tag_replaced', array( $this, 'replace_product_image_mail_tag' ), 20, 4 );
	}

	/**
	 * Whether a CF7 form is linked to Musomo Quote.
	 *
	 * @param mixed $contact_form Form object or ID.
	 * @return bool
	 */
	private function is_linked_form( $contact_form ) {
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
	 * Extract a scalar string from CF7 posted value.
	 *
	 * @param mixed $value Posted value.
	 * @return string
	 */
	private function posted_scalar( $value ) {
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	/**
	 * Resolve absolute product image URL from posted product ID (server-side source of truth).
	 *
	 * @param array $posted_data Posted data.
	 * @return string
	 */
	private function resolve_image_url_from_posted_data( $posted_data ) {
		$posted_data = is_array( $posted_data ) ? $posted_data : array();

		$product_id = absint( $this->posted_scalar( isset( $posted_data['musomo_product_id'] ) ? $posted_data['musomo_product_id'] : '' ) );
		if ( $product_id && musomo_quote_is_woocommerce_active() ) {
			$url = musomo_quote_get_product_image_url( $product_id );
			if ( $url ) {
				return $url;
			}
		}

		// Fallback: clean client-provided URL if already absolute http(s).
		$client = $this->posted_scalar( isset( $posted_data['musomo_product_image'] ) ? $posted_data['musomo_product_image'] : '' );
		return musomo_quote_normalize_image_url( $client );
	}

	/**
	 * Force musomo_product_image to a pure absolute URL for linked forms only.
	 *
	 * @param array $posted_data Posted data.
	 * @return array
	 */
	public function normalize_posted_product_image( $posted_data ) {
		if ( ! is_array( $posted_data ) ) {
			return $posted_data;
		}

		$submission = class_exists( 'WPCF7_Submission' ) ? WPCF7_Submission::get_instance() : null;
		if ( ! $submission || ! method_exists( $submission, 'get_contact_form' ) ) {
			return $posted_data;
		}

		if ( ! $this->is_linked_form( $submission->get_contact_form() ) ) {
			return $posted_data;
		}

		$posted_data['musomo_product_image'] = $this->resolve_image_url_from_posted_data( $posted_data );
		return $posted_data;
	}

	/**
	 * Ensure [musomo_product_image] mail-tag outputs only a clean absolute URL.
	 *
	 * @param string          $replaced  Replaced string.
	 * @param mixed           $submitted Submitted value.
	 * @param bool            $html      Whether HTML mail.
	 * @param WPCF7_MailTag   $mail_tag  Mail tag.
	 * @return string
	 */
	public function replace_product_image_mail_tag( $replaced, $submitted, $html, $mail_tag ) {
		if ( ! is_object( $mail_tag ) || ! method_exists( $mail_tag, 'field_name' ) ) {
			return $replaced;
		}

		if ( 'musomo_product_image' !== $mail_tag->field_name() ) {
			return $replaced;
		}

		$submission = class_exists( 'WPCF7_Submission' ) ? WPCF7_Submission::get_instance() : null;
		if ( ! $submission || ! method_exists( $submission, 'get_contact_form' ) ) {
			return musomo_quote_normalize_image_url( $this->posted_scalar( $submitted ) );
		}

		if ( ! $this->is_linked_form( $submission->get_contact_form() ) ) {
			return $replaced;
		}

		$posted = method_exists( $submission, 'get_posted_data' ) ? $submission->get_posted_data() : array();
		$url    = $this->resolve_image_url_from_posted_data( is_array( $posted ) ? $posted : array() );

		if ( ! $url ) {
			$url = musomo_quote_normalize_image_url( $this->posted_scalar( $submitted ) );
		}

		// Pure URL only — never HTML. CF7 may esc_html afterward; safe for https URLs.
		return $url;
	}

	/**
	 * List published CF7 forms as id => title.
	 *
	 * @return array<int,string>
	 */
	public static function get_forms() {
		if ( ! musomo_quote_is_cf7_active() ) {
			return array();
		}

		$args = array(
			'post_type'              => 'wpcf7_contact_form',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		// Polylang may otherwise limit results to the current admin language.
		if ( function_exists( 'pll_current_language' ) ) {
			$args['lang'] = '';
		}

		$posts = get_posts( $args );

		$forms = array();

		foreach ( $posts as $post ) {
			$forms[ (int) $post->ID ] = get_the_title( $post );
		}

		return $forms;
	}

	/**
	 * Get CF7 form title by ID.
	 *
	 * @param int $form_id Form post ID.
	 * @return string
	 */
	public static function get_form_title( $form_id ) {
		$form_id = absint( $form_id );

		if ( ! $form_id || ! musomo_quote_is_cf7_active() ) {
			return '';
		}

		$post = get_post( $form_id );

		if ( ! $post || 'wpcf7_contact_form' !== $post->post_type ) {
			return '';
		}

		$title = get_the_title( $post );
		return is_string( $title ) ? $title : '';
	}

	/**
	 * Selected form ID for the current language (fallback: global).
	 *
	 * @return int
	 */
	public static function get_selected_form_id() {
		$candidates = array();

		if ( class_exists( 'Musomo_Quote_I18n' ) ) {
			$lang = Musomo_Quote_I18n::get_current_language();
			if ( $lang ) {
				$translations = Musomo_Quote_I18n::get_stored_translations();
				if ( ! empty( $translations[ $lang ]['cf7_form_id'] ) ) {
					$candidates[] = absint( $translations[ $lang ]['cf7_form_id'] );
				}
			}
			$candidates[] = Musomo_Quote_I18n::get_global_cf7_form_id();
		} else {
			$settings     = musomo_quote_get_settings();
			$candidates[] = isset( $settings['cf7_form_id'] ) ? absint( $settings['cf7_form_id'] ) : 0;
		}

		if ( ! musomo_quote_is_cf7_active() ) {
			return 0;
		}

		foreach ( $candidates as $form_id ) {
			$form_id = absint( $form_id );
			if ( ! $form_id ) {
				continue;
			}
			$post = get_post( $form_id );
			if ( $post && 'wpcf7_contact_form' === $post->post_type && 'publish' === $post->post_status ) {
				return $form_id;
			}
		}

		return 0;
	}

	/**
	 * Global (fallback) CF7 form ID from settings.
	 *
	 * @return int
	 */
	public static function get_global_form_id() {
		if ( class_exists( 'Musomo_Quote_I18n' ) ) {
			$form_id = Musomo_Quote_I18n::get_global_cf7_form_id();
		} else {
			$settings = musomo_quote_get_settings();
			$form_id  = isset( $settings['cf7_form_id'] ) ? absint( $settings['cf7_form_id'] ) : 0;
		}

		return $form_id;
	}

	/**
	 * Render selected CF7 form HTML via official CF7 API.
	 *
	 * @param int $form_id Optional form ID override.
	 */
	public static function render_form( $form_id = 0 ) {
		$form_id = $form_id ? absint( $form_id ) : self::get_selected_form_id();

		if ( ! $form_id || ! musomo_quote_is_cf7_active() ) {
			echo '<p class="musomo-quote-form-missing">';
			echo esc_html( musomo_quote_get_text( 'form_not_configured_text' ) );
			echo '</p>';
			return;
		}

		if ( function_exists( 'wpcf7_contact_form' ) ) {
			$contact_form = wpcf7_contact_form( $form_id );
			if ( $contact_form ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CF7 returns form HTML.
				echo $contact_form->form_html();
				return;
			}
		}

		// Fallback shortcode if API unavailable.
		echo do_shortcode( sprintf( '[contact-form-7 id="%d"]', $form_id ) );
	}

	/**
	 * Ensure CF7 assets load when the form is only printed in the footer modal.
	 */
	public static function enqueue_assets() {
		if ( ! musomo_quote_is_cf7_active() ) {
			return;
		}

		// Load if any linked form exists (current language or global / overrides).
		$linked = musomo_quote_get_linked_cf7_form_ids();
		if ( empty( $linked ) && ! self::get_selected_form_id() ) {
			return;
		}

		if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
			wpcf7_enqueue_scripts();
		}

		if ( function_exists( 'wpcf7_enqueue_styles' ) ) {
			wpcf7_enqueue_styles();
		}
	}
}
