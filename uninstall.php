<?php
/**
 * Uninstall handler for Musomo Quote.
 *
 * @package Musomo_Quote
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'musomo_quote_settings', array() );

if ( empty( $settings['delete_data_on_uninstall'] ) ) {
	return;
}

delete_option( 'musomo_quote_settings' );
delete_option( 'musomo_quote_translations' );
