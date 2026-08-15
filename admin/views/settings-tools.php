<?php
/**
 * Tools settings view — diagnostics, export/import, reset.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are defined in the including method scope.

$page_title = __( 'Tools', 'musomo-quote' );
include MUSOMO_QUOTE_PATH . 'admin/views/partial-header.php';
include MUSOMO_QUOTE_PATH . 'admin/views/partial-tools-diagnostics.php';
include MUSOMO_QUOTE_PATH . 'admin/views/partial-footer.php';

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
