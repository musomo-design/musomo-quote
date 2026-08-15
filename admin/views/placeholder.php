<?php
/**
 * Placeholder view for pages not yet implemented.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are defined in the including method scope.

$page_title = __( 'Coming soon', 'musomo-quote' );
include MUSOMO_QUOTE_PATH . 'admin/views/partial-header.php';
?>

<div class="mq-card">
	<h3 class="mq-card__title"><?php echo esc_html__( 'Section coming soon', 'musomo-quote' ); ?></h3>
	<p><?php echo esc_html__( 'This screen will be implemented in upcoming roadmap steps. The menu skeleton is already in place.', 'musomo-quote' ); ?></p>
</div>

<?php
include MUSOMO_QUOTE_PATH . 'admin/views/partial-footer.php';

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
