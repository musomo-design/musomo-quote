<?php
/**
 * Multilingual layer for Musomo Quote (Polylang-first).
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Musomo_Quote_I18n
 */
class Musomo_Quote_I18n {

	/**
	 * Per-request cache: languages list.
	 *
	 * @var array|null
	 */
	private static $languages_cache = null;

	/**
	 * Per-request cache: current language slug.
	 *
	 * @var string|null
	 */
	private static $current_lang_cache = null;

	/**
	 * Empty translation row (empty strings = use global fallback).
	 *
	 * @return array
	 */
	public static function empty_translation_row() {
		$row = array();
		foreach ( musomo_quote_text_keys() as $key ) {
			$row[ $key ] = '';
		}
		$row['cf7_form_id'] = 0;
		return $row;
	}

	/**
	 * Whether Polylang is available for frontend language APIs.
	 *
	 * @return bool
	 */
	public static function is_polylang_active() {
		return function_exists( 'pll_current_language' )
			&& ( function_exists( 'pll_languages_list' ) || function_exists( 'pll_the_languages' ) );
	}

	/**
	 * Available Polylang languages (slug => meta).
	 *
	 * Meta keys: slug, name, flag (HTML or empty).
	 *
	 * @return array<string,array>
	 */
	public static function get_languages() {
		if ( null !== self::$languages_cache ) {
			return self::$languages_cache;
		}

		self::$languages_cache = array();

		if ( ! self::is_polylang_active() ) {
			return self::$languages_cache;
		}

		if ( function_exists( 'pll_languages_list' ) ) {
			$slugs = pll_languages_list( array( 'fields' => 'slug' ) );
			if ( ! is_array( $slugs ) ) {
				$slugs = array();
			}

			$names = function_exists( 'pll_languages_list' )
				? pll_languages_list( array( 'fields' => 'name' ) )
				: array();
			if ( ! is_array( $names ) ) {
				$names = array();
			}

			$flags = array();
			if ( function_exists( 'PLL' ) ) {
				$pll = PLL();
				if ( is_object( $pll ) && isset( $pll->model ) && method_exists( $pll->model, 'get_languages_list' ) ) {
					foreach ( $pll->model->get_languages_list() as $lang_obj ) {
						if ( ! is_object( $lang_obj ) || empty( $lang_obj->slug ) ) {
							continue;
						}
						$slug = sanitize_key( (string) $lang_obj->slug );
						$flag = '';
						if ( ! empty( $lang_obj->flag ) && is_string( $lang_obj->flag ) ) {
							$flag = $lang_obj->flag;
						} elseif ( method_exists( $lang_obj, 'get_display_flag' ) ) {
							$flag = (string) $lang_obj->get_display_flag();
						}
						$flags[ $slug ] = $flag;
					}
				}
			}

			foreach ( $slugs as $i => $slug ) {
				$slug = sanitize_key( (string) $slug );
				if ( '' === $slug ) {
					continue;
				}
				$name = isset( $names[ $i ] ) ? (string) $names[ $i ] : $slug;
				self::$languages_cache[ $slug ] = array(
					'slug' => $slug,
					'name' => $name,
					'flag' => isset( $flags[ $slug ] ) ? $flags[ $slug ] : '',
				);
			}
		}

		return self::$languages_cache;
	}

	/**
	 * Count of configured Polylang languages.
	 *
	 * @return int
	 */
	public static function get_language_count() {
		return count( self::get_languages() );
	}

	/**
	 * Current frontend language slug (empty if unavailable).
	 *
	 * In admin (non-AJAX) returns empty so global texts are used.
	 *
	 * @return string
	 */
	public static function get_current_language() {
		if ( null !== self::$current_lang_cache ) {
			return self::$current_lang_cache;
		}

		self::$current_lang_cache = '';

		if ( ! self::is_polylang_active() ) {
			return self::$current_lang_cache;
		}

		// Admin screens (Appearance preview, settings): prefer global texts.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return self::$current_lang_cache;
		}

		$slug = '';
		if ( function_exists( 'pll_current_language' ) ) {
			$slug = pll_current_language( 'slug' );
		}

		if ( ! is_string( $slug ) || '' === $slug ) {
			return self::$current_lang_cache;
		}

		self::$current_lang_cache = sanitize_key( $slug );
		return self::$current_lang_cache;
	}

	/**
	 * Stored translations map from settings.
	 *
	 * @param array|null $settings Optional settings array.
	 * @return array<string,array>
	 */
	public static function get_stored_translations( $settings = null ) {
		if ( null === $settings ) {
			$settings = musomo_quote_get_settings();
		}
		$raw = isset( $settings['translations'] ) && is_array( $settings['translations'] )
			? $settings['translations']
			: array();

		$out = array();
		foreach ( $raw as $slug => $row ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' === $slug || ! is_array( $row ) ) {
				continue;
			}
			$out[ $slug ] = self::normalize_translation_row( $row );
		}
		return $out;
	}

	/**
	 * Normalize one language row.
	 *
	 * @param array $row Raw row.
	 * @return array
	 */
	public static function normalize_translation_row( $row ) {
		$base = self::empty_translation_row();
		$row  = is_array( $row ) ? $row : array();

		foreach ( musomo_quote_text_keys() as $key ) {
			if ( isset( $row[ $key ] ) && is_string( $row[ $key ] ) ) {
				$base[ $key ] = $row[ $key ];
			} elseif ( isset( $row[ $key ] ) ) {
				$base[ $key ] = (string) $row[ $key ];
			}
		}

		$base['cf7_form_id'] = isset( $row['cf7_form_id'] ) ? absint( $row['cf7_form_id'] ) : 0;
		return $base;
	}

	/**
	 * Sanitize translations from admin POST (merge into existing).
	 *
	 * Empty text fields stay empty (fallback). Does not delete orphan language keys.
	 *
	 * @param array $input   Raw input (expects translations[lang][...]).
	 * @param array $current Current settings.
	 * @return array<string,array>
	 */
	public static function sanitize_translations( $input, $current = array() ) {
		$existing = self::get_stored_translations( $current );
		$input    = is_array( $input ) ? $input : array();
		$posted   = isset( $input['translations'] ) && is_array( $input['translations'] )
			? $input['translations']
			: array();

		$text_keys = musomo_quote_text_keys();

		foreach ( $posted as $slug => $row ) {
			$slug = sanitize_key( (string) $slug );
			if ( '' === $slug || ! is_array( $row ) ) {
				continue;
			}

			$clean = self::empty_translation_row();
			foreach ( $text_keys as $key ) {
				if ( ! array_key_exists( $key, $row ) ) {
					$clean[ $key ] = '';
					continue;
				}
				$raw = wp_unslash( (string) $row[ $key ] );
				if ( in_array( $key, array( 'form_not_configured_text', 'cf7_not_available_text' ), true ) ) {
					$clean[ $key ] = sanitize_textarea_field( $raw );
				} else {
					$clean[ $key ] = sanitize_text_field( $raw );
				}
			}
			$clean['cf7_form_id'] = isset( $row['cf7_form_id'] ) ? absint( $row['cf7_form_id'] ) : 0;
			$existing[ $slug ]    = $clean;
		}

		return $existing;
	}

	/**
	 * Resolve a text for language with fallback chain.
	 *
	 * 1) language override (non-empty)
	 * 2) global STEP 5 setting
	 * 3) plugin default
	 *
	 * @param string $key  Text key.
	 * @param string $lang Language slug (empty = current / global only).
	 * @return string
	 */
	public static function resolve_text( $key, $lang = '' ) {
		$defaults = musomo_quote_text_defaults();
		$key      = (string) $key;

		if ( ! array_key_exists( $key, $defaults ) ) {
			return '';
		}

		$settings = musomo_quote_get_settings();
		$lang     = $lang ? sanitize_key( $lang ) : self::get_current_language();

		if ( $lang ) {
			$translations = self::get_stored_translations( $settings );
			if ( isset( $translations[ $lang ][ $key ] ) ) {
				$local = trim( (string) $translations[ $lang ][ $key ] );
				if ( '' !== $local ) {
					return $local;
				}
			}
		}

		$global = isset( $settings[ $key ] ) ? $settings[ $key ] : $defaults[ $key ];
		$global = is_string( $global ) ? trim( $global ) : '';
		if ( '' !== $global ) {
			return $global;
		}

		return $defaults[ $key ];
	}

	/**
	 * Global CF7 form ID from settings (not language-aware).
	 *
	 * @param array|null $settings Settings.
	 * @return int
	 */
	public static function get_global_cf7_form_id( $settings = null ) {
		if ( null === $settings ) {
			$settings = musomo_quote_get_settings();
		}
		return isset( $settings['cf7_form_id'] ) ? absint( $settings['cf7_form_id'] ) : 0;
	}

	/**
	 * CF7 form ID for a language with fallback to global.
	 *
	 * @param string $lang Language slug (empty = current).
	 * @return int Raw ID (may be unpublished; caller validates if needed).
	 */
	public static function get_cf7_form_id_for_language( $lang = '' ) {
		$settings = musomo_quote_get_settings();
		$lang     = $lang ? sanitize_key( $lang ) : self::get_current_language();

		if ( $lang ) {
			$translations = self::get_stored_translations( $settings );
			if ( ! empty( $translations[ $lang ]['cf7_form_id'] ) ) {
				return absint( $translations[ $lang ]['cf7_form_id'] );
			}
		}

		return self::get_global_cf7_form_id( $settings );
	}

	/**
	 * All CF7 form IDs linked to Musomo Quote (global + translations).
	 *
	 * @return int[]
	 */
	public static function get_linked_cf7_form_ids() {
		$settings = musomo_quote_get_settings();
		$ids      = array();

		$global = self::get_global_cf7_form_id( $settings );
		if ( $global > 0 ) {
			$ids[] = $global;
		}

		foreach ( self::get_stored_translations( $settings ) as $row ) {
			$id = isset( $row['cf7_form_id'] ) ? absint( $row['cf7_form_id'] ) : 0;
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		sort( $ids );
		return $ids;
	}

	/**
	 * Count of language-specific CF7 overrides (non-zero).
	 *
	 * @return int
	 */
	public static function count_cf7_translation_overrides() {
		$count = 0;
		foreach ( self::get_stored_translations() as $row ) {
			if ( ! empty( $row['cf7_form_id'] ) ) {
				$count++;
			}
		}
		return $count;
	}
}
