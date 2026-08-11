<?php
/**
 * CF7 Form & Email template builder (copy/paste helpers).
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Musomo_Quote_CF7_Builder
 */
class Musomo_Quote_CF7_Builder {

	/**
	 * Customer field definitions.
	 *
	 * @return array<string,array>
	 */
	public static function field_definitions() {
		return array(
			'name'     => array(
				'label'    => __( 'Name', 'musomo-quote' ),
				'cf7_name' => 'your-name',
				'type'     => 'text',
			),
			'lastname' => array(
				'label'    => __( 'Last name', 'musomo-quote' ),
				'cf7_name' => 'your-lastname',
				'type'     => 'text',
			),
			'company'  => array(
				'label'    => __( 'Company', 'musomo-quote' ),
				'cf7_name' => 'your-company',
				'type'     => 'text',
			),
			'email'    => array(
				'label'    => __( 'Email', 'musomo-quote' ),
				'cf7_name' => 'your-email',
				'type'     => 'email',
			),
			'phone'    => array(
				'label'    => __( 'Phone', 'musomo-quote' ),
				'cf7_name' => 'your-phone',
				'type'     => 'tel',
			),
			'quantity' => array(
				'label'    => __( 'Quantity', 'musomo-quote' ),
				'cf7_name' => 'musomo_quantity',
				'type'     => 'number',
			),
			'message'  => array(
				'label'    => __( 'Message', 'musomo-quote' ),
				'cf7_name' => 'your-message',
				'type'     => 'textarea',
			),
		);
	}

	/**
	 * Product hidden CF7 field names (exact Musomo Quote contract).
	 *
	 * @return string[]
	 */
	public static function product_hidden_fields() {
		return array(
			'musomo_product_id',
			'musomo_product_name',
			'musomo_product_sku',
			'musomo_product_url',
			'musomo_product_image',
			'musomo_product_price',
			'musomo_product_type',
			'musomo_quantity',
		);
	}

	/**
	 * Default builder settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'fields'      => array(
				'name'     => array(
					'enabled'  => true,
					'required' => true,
				),
				'lastname' => array(
					'enabled'  => false,
					'required' => false,
				),
				'company'  => array(
					'enabled'  => true,
					'required' => false,
				),
				'email'    => array(
					'enabled'  => true,
					'required' => true,
				),
				'phone'    => array(
					'enabled'  => true,
					'required' => false,
				),
				'quantity' => array(
					'enabled'  => true,
					'required' => false,
				),
				'message'  => array(
					'enabled'  => true,
					'required' => true,
				),
			),
			'submit_text' => 'Send request',
			'email'       => array(
				'header_title'           => 'Quote request',
				'company_name'           => '',
				'subject'                => 'New quote request — [musomo_product_name]',
				'show_product_image'     => true,
				'show_product_name'      => true,
				'show_sku'               => true,
				'show_price'             => true,
				'show_quantity'          => true,
				'show_product_url'       => true,
				'show_customer_name'     => true,
				'show_customer_lastname' => true,
				'show_company'           => true,
				'show_email'             => true,
				'show_phone'             => true,
				'show_message'           => true,
			),
		);
	}

	/**
	 * Get builder config merged with defaults.
	 *
	 * @param array|null $settings Plugin settings.
	 * @return array
	 */
	public static function get_config( $settings = null ) {
		if ( null === $settings ) {
			$settings = musomo_quote_get_settings();
		}
		$stored = isset( $settings['cf7_builder'] ) && is_array( $settings['cf7_builder'] )
			? $settings['cf7_builder']
			: array();

		return self::normalize( $stored );
	}

	/**
	 * Normalize / merge a builder config.
	 *
	 * @param array $config Raw config.
	 * @return array
	 */
	public static function normalize( $config ) {
		$defaults = self::defaults();
		$config   = is_array( $config ) ? $config : array();

		$out                = $defaults;
		$out['submit_text'] = isset( $config['submit_text'] )
			? sanitize_text_field( (string) $config['submit_text'] )
			: $defaults['submit_text'];
		if ( '' === $out['submit_text'] ) {
			$out['submit_text'] = $defaults['submit_text'];
		}

		$fields_in = isset( $config['fields'] ) && is_array( $config['fields'] ) ? $config['fields'] : array();
		foreach ( self::field_definitions() as $key => $def ) {
			$row = isset( $fields_in[ $key ] ) && is_array( $fields_in[ $key ] ) ? $fields_in[ $key ] : array();
			$out['fields'][ $key ] = array(
				'enabled'  => array_key_exists( 'enabled', $row ) ? ! empty( $row['enabled'] ) : $defaults['fields'][ $key ]['enabled'],
				'required' => ! empty( $row['required'] ),
			);
			// Email should stay required when enabled by default preference — still allow OFF required if user wants.
		}

		$email_in = isset( $config['email'] ) && is_array( $config['email'] ) ? $config['email'] : array();
		$email    = $defaults['email'];
		$email['header_title'] = isset( $email_in['header_title'] )
			? sanitize_text_field( (string) $email_in['header_title'] )
			: $defaults['email']['header_title'];
		if ( '' === $email['header_title'] ) {
			$email['header_title'] = $defaults['email']['header_title'];
		}
		$email['company_name'] = isset( $email_in['company_name'] )
			? sanitize_text_field( (string) $email_in['company_name'] )
			: '';
		$email['subject'] = isset( $email_in['subject'] )
			? sanitize_text_field( (string) $email_in['subject'] )
			: $defaults['email']['subject'];
		if ( '' === $email['subject'] ) {
			$email['subject'] = $defaults['email']['subject'];
		}

		$bool_keys = array(
			'show_product_image',
			'show_product_name',
			'show_sku',
			'show_price',
			'show_quantity',
			'show_product_url',
			'show_customer_name',
			'show_customer_lastname',
			'show_company',
			'show_email',
			'show_phone',
			'show_message',
		);
		foreach ( $bool_keys as $bk ) {
			if ( array_key_exists( $bk, $email_in ) ) {
				$email[ $bk ] = ! empty( $email_in[ $bk ] );
			}
		}
		$out['email'] = $email;

		return $out;
	}

	/**
	 * Sanitize builder from admin POST (cf7_builder nested array).
	 *
	 * @param array $input Raw musomo_quote_settings input or nested builder.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$raw   = isset( $input['cf7_builder'] ) && is_array( $input['cf7_builder'] )
			? $input['cf7_builder']
			: $input;

		$defaults = self::defaults();
		$out      = $defaults;

		$out['submit_text'] = isset( $raw['submit_text'] )
			? sanitize_text_field( wp_unslash( (string) $raw['submit_text'] ) )
			: $defaults['submit_text'];
		if ( '' === trim( $out['submit_text'] ) ) {
			$out['submit_text'] = $defaults['submit_text'];
		}

		$fields_in = isset( $raw['fields'] ) && is_array( $raw['fields'] ) ? $raw['fields'] : array();
		foreach ( self::field_definitions() as $key => $def ) {
			$row = isset( $fields_in[ $key ] ) && is_array( $fields_in[ $key ] ) ? $fields_in[ $key ] : array();
			// Unchecked checkboxes are absent.
			$out['fields'][ $key ] = array(
				'enabled'  => ! empty( $row['enabled'] ),
				'required' => ! empty( $row['required'] ),
			);
		}

		$email_in = isset( $raw['email'] ) && is_array( $raw['email'] ) ? $raw['email'] : array();
		$email    = $defaults['email'];

		$email['header_title'] = isset( $email_in['header_title'] )
			? sanitize_text_field( wp_unslash( (string) $email_in['header_title'] ) )
			: $defaults['email']['header_title'];
		if ( '' === trim( $email['header_title'] ) ) {
			$email['header_title'] = $defaults['email']['header_title'];
		}

		$email['company_name'] = isset( $email_in['company_name'] )
			? sanitize_text_field( wp_unslash( (string) $email_in['company_name'] ) )
			: '';

		$email['subject'] = isset( $email_in['subject'] )
			? sanitize_text_field( wp_unslash( (string) $email_in['subject'] ) )
			: $defaults['email']['subject'];
		if ( '' === trim( $email['subject'] ) ) {
			$email['subject'] = $defaults['email']['subject'];
		}

		$bool_keys = array(
			'show_product_image',
			'show_product_name',
			'show_sku',
			'show_price',
			'show_quantity',
			'show_product_url',
			'show_customer_name',
			'show_customer_lastname',
			'show_company',
			'show_email',
			'show_phone',
			'show_message',
		);
		foreach ( $bool_keys as $bk ) {
			$email[ $bk ] = ! empty( $email_in[ $bk ] );
		}

		// Align customer email toggles with form field availability conceptually —
		// still allow independent email section toggles; user can disable SKU in email while keeping form fields.

		$out['email'] = $email;
		return $out;
	}

	/**
	 * Resolved company/site name for email template.
	 *
	 * @param array $config Builder config.
	 * @return string
	 */
	public static function resolve_company_name( $config ) {
		$email = isset( $config['email'] ) ? $config['email'] : array();
		$name  = isset( $email['company_name'] ) ? trim( (string) $email['company_name'] ) : '';
		if ( '' !== $name ) {
			return $name;
		}
		$blog = get_bloginfo( 'name' );
		return is_string( $blog ) && '' !== $blog ? $blog : 'Website';
	}

	/**
	 * Generate CF7 Form tab code.
	 *
	 * @param array|null $config Builder config.
	 * @return string
	 */
	public static function generate_form_code( $config = null ) {
		$config = $config ? self::normalize( $config ) : self::get_config();
		$defs   = self::field_definitions();
		$lines  = array();

		$quantity_visible = ! empty( $config['fields']['quantity']['enabled'] );

		foreach ( $defs as $key => $def ) {
			$field = isset( $config['fields'][ $key ] ) ? $config['fields'][ $key ] : array();
			if ( empty( $field['enabled'] ) ) {
				continue;
			}

			$required = ! empty( $field['required'] );
			$star     = $required ? ' *' : '';
			$tag      = self::cf7_field_tag( $def['type'], $def['cf7_name'], $required );

			$lines[] = '<label>';
			$lines[] = $def['label'] . $star;
			$lines[] = $tag;
			$lines[] = '</label>';
			$lines[] = '';
		}

		foreach ( self::product_hidden_fields() as $hidden ) {
			if ( 'musomo_quantity' === $hidden && $quantity_visible ) {
				continue;
			}
			$lines[] = '[hidden ' . $hidden . ']';
		}

		$lines[] = '';
		$submit  = isset( $config['submit_text'] ) ? $config['submit_text'] : 'Send request';
		$submit  = str_replace( array( '\\', '"' ), array( '', "'" ), $submit );
		$lines[] = '[submit "' . $submit . '"]';

		return trim( implode( "\n", $lines ) ) . "\n";
	}

	/**
	 * Build a CF7 form-tag string.
	 *
	 * @param string $type     Field type.
	 * @param string $name     CF7 name.
	 * @param bool   $required Required.
	 * @return string
	 */
	private static function cf7_field_tag( $type, $name, $required ) {
		$req = $required ? '*' : '';
		switch ( $type ) {
			case 'email':
				return '[email' . $req . ' ' . $name . ']';
			case 'tel':
				return '[tel' . $req . ' ' . $name . ']';
			case 'number':
				return '[number' . $req . ' ' . $name . ' min:1]';
			case 'textarea':
				return '[textarea' . $req . ' ' . $name . ']';
			default:
				return '[text' . $req . ' ' . $name . ']';
		}
	}

	/**
	 * Generate email subject line.
	 *
	 * @param array|null $config Config.
	 * @return string
	 */
	public static function generate_subject( $config = null ) {
		$config = $config ? self::normalize( $config ) : self::get_config();
		$subject = isset( $config['email']['subject'] ) ? (string) $config['email']['subject'] : '';
		return '' !== trim( $subject ) ? $subject : self::defaults()['email']['subject'];
	}

	/**
	 * Generate Reply-To header suggestion.
	 *
	 * @param array|null $config Config.
	 * @return string
	 */
	public static function generate_reply_to_header( $config = null ) {
		$config = $config ? self::normalize( $config ) : self::get_config();
		if ( empty( $config['fields']['email']['enabled'] ) ) {
			return '';
		}
		return 'Reply-To: [your-email]';
	}

	/**
	 * Generate CF7 Mail HTML body (generic mail-tags, no branding requirement).
	 *
	 * @param array|null $config Config.
	 * @return string
	 */
	public static function generate_email_html( $config = null ) {
		$config  = $config ? self::normalize( $config ) : self::get_config();
		$email   = $config['email'];
		$fields  = $config['fields'];
		$company = self::resolve_company_name( $config );
		$title   = isset( $email['header_title'] ) ? $email['header_title'] : 'Quote request';

		$company_esc = htmlspecialchars( $company, ENT_QUOTES, 'UTF-8' );
		$title_esc   = htmlspecialchars( $title, ENT_QUOTES, 'UTF-8' );

		$rows_product  = '';
		$rows_customer = '';
		$message_block = '';

		if ( ! empty( $email['show_product_image'] ) ) {
			$rows_product .= '<tr><td style="padding:0 0 16px 0;" align="center">'
				. '<img src="[musomo_product_image]" alt="[musomo_product_name]" width="240" style="display:block;max-width:240px;height:auto;border:0;outline:none;text-decoration:none;" />'
				. '</td></tr>';
		}

		if ( ! empty( $email['show_product_name'] ) ) {
			$rows_product .= '<tr><td style="padding:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:bold;color:#111111;line-height:1.4;">[musomo_product_name]</td></tr>';
		}

		$meta = array();
		if ( ! empty( $email['show_sku'] ) ) {
			$meta[] = 'SKU: [musomo_product_sku]';
		}
		if ( ! empty( $email['show_price'] ) ) {
			$meta[] = 'Price: [musomo_product_price]';
		}
		if ( ! empty( $email['show_quantity'] ) ) {
			$meta[] = 'Quantity: [musomo_quantity]';
		}
		if ( ! empty( $meta ) ) {
			$rows_product .= '<tr><td style="padding:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#444444;line-height:1.6;">'
				. implode( '<br />', $meta )
				. '</td></tr>';
		}

		$customer_lines = array();
		if ( ! empty( $email['show_customer_name'] ) && ! empty( $fields['name']['enabled'] ) ) {
			$customer_lines[] = array( 'Name', '[your-name]' );
		}
		if ( ! empty( $email['show_customer_lastname'] ) && ! empty( $fields['lastname']['enabled'] ) ) {
			$customer_lines[] = array( 'Last name', '[your-lastname]' );
		}
		if ( ! empty( $email['show_company'] ) && ! empty( $fields['company']['enabled'] ) ) {
			$customer_lines[] = array( 'Company', '[your-company]' );
		}
		if ( ! empty( $email['show_email'] ) && ! empty( $fields['email']['enabled'] ) ) {
			$customer_lines[] = array( 'Email', '[your-email]' );
		}
		if ( ! empty( $email['show_phone'] ) && ! empty( $fields['phone']['enabled'] ) ) {
			$customer_lines[] = array( 'Phone', '[your-phone]' );
		}

		foreach ( $customer_lines as $line ) {
			$rows_customer .= '<tr><td style="padding:0 0 10px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#444444;line-height:1.5;">'
				. '<strong style="color:#111111;">' . htmlspecialchars( $line[0], ENT_QUOTES, 'UTF-8' ) . ':</strong><br />'
				. $line[1]
				. '</td></tr>';
		}

		if ( ! empty( $email['show_message'] ) && ! empty( $fields['message']['enabled'] ) ) {
			$message_block = '<tr><td style="padding:20px 24px;border-top:1px solid #e5e5e5;">'
				. '<p style="margin:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#888888;">Message</p>'
				. '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#333333;line-height:1.6;">[your-message]</p>'
				. '</td></tr>';
		}

		$url_block = '';
		if ( ! empty( $email['show_product_url'] ) ) {
			$url_block = '<tr><td style="padding:20px 24px;border-top:1px solid #e5e5e5;">'
				. '<a href="[musomo_product_url]" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#014b43;text-decoration:underline;">View product</a>'
				. '</td></tr>';
		}

		$product_section = '';
		if ( '' !== $rows_product ) {
			$product_section = '<tr><td style="padding:20px 24px;">'
				. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
				. $rows_product
				. '</table></td></tr>';
		}

		$customer_section = '';
		if ( '' !== $rows_customer ) {
			$customer_section = '<tr><td style="padding:20px 24px;border-top:1px solid #e5e5e5;">'
				. '<p style="margin:0 0 12px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#888888;">Customer</p>'
				. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
				. $rows_customer
				. '</table></td></tr>';
		}

		$html  = '<!DOCTYPE html>' . "\n";
		$html .= '<html><body style="margin:0;padding:0;background-color:#f4f4f4;">' . "\n";
		$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4;padding:24px 0;">' . "\n";
		$html .= '<tr><td align="center">' . "\n";
		$html .= '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background-color:#ffffff;border:1px solid #e5e5e5;">' . "\n";

		$html .= '<tr><td style="padding:20px 24px;background-color:#111111;">'
			. '<p style="margin:0 0 4px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#bbbbbb;">' . $company_esc . '</p>'
			. '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:bold;color:#ffffff;">' . $title_esc . '</p>'
			. '</td></tr>' . "\n";

		$html .= $product_section . "\n";
		$html .= $customer_section . "\n";
		$html .= $message_block . "\n";
		$html .= $url_block . "\n";

		$html .= '</table>' . "\n";
		$html .= '</td></tr></table>' . "\n";
		$html .= '</body></html>';

		return $html;
	}

	/**
	 * Demo data for visual email preview (admin only).
	 *
	 * @return array
	 */
	public static function demo_preview_data() {
		return array(
			'product_name'  => 'Organic dad hat',
			'sku'           => 'ABC123',
			'price'         => '€23.50',
			'quantity'      => '4',
			'product_url'   => '#',
			'customer_name' => 'John',
			'lastname'      => 'Smith',
			'company'       => 'Example Company',
			'email'         => 'john@example.com',
			'phone'         => '+1 555 0100',
			'message'       => 'Hello, I would like information about this product.',
			'image'         => MUSOMO_QUOTE_URL . 'assets/07-icon.svg',
		);
	}

	/**
	 * Data for JS localization.
	 *
	 * @return array
	 */
	public static function js_payload() {
		$config = self::get_config();
		$defs   = array();
		foreach ( self::field_definitions() as $key => $def ) {
			$defs[ $key ] = array(
				'label'    => $def['label'],
				'cf7_name' => $def['cf7_name'],
				'type'     => $def['type'],
			);
		}

		return array(
			'config'         => $config,
			'fields'         => $defs,
			'productHidden'  => self::product_hidden_fields(),
			'demo'           => self::demo_preview_data(),
			'siteName'       => self::resolve_company_name( $config ),
			'copied'         => __( 'Copied!', 'musomo-quote' ),
			'copyFailed'     => __( 'Copy failed', 'musomo-quote' ),
			'defaultSubmit'  => 'Send request',
			'logoUrl'        => MUSOMO_QUOTE_URL . 'assets/08-logo.svg',
			'iconUrl'        => MUSOMO_QUOTE_URL . 'assets/07-icon.svg',
			'formCode'       => self::generate_form_code( $config ),
			'emailHtml'      => self::generate_email_html( $config ),
			'subject'        => self::generate_subject( $config ),
			'replyTo'        => self::generate_reply_to_header( $config ),
		);
	}
}
