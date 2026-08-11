<?php
/**
 * Admin shell header partial.
 *
 * @package Musomo_Quote
 *
 * @var string $page_title Optional page title override.
 */

defined( 'ABSPATH' ) || exit;

$page_title = isset( $page_title ) ? $page_title : __( 'Dashboard', 'musomo-quote' );
$logo_url   = MUSOMO_QUOTE_URL . 'assets/08-logo.svg';
$current    = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'musomo-quote'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap mq-admin">
	<header class="mq-admin-header">
		<div class="mq-admin-header__brand">
			<img
				class="mq-admin-header__logo"
				src="<?php echo esc_url( $logo_url ); ?>"
				alt="<?php echo esc_attr__( 'Musomo Quote', 'musomo-quote' ); ?>"
				width="240"
				height="48"
			/>
		</div>
		<div class="mq-admin-header__meta">
			<span class="mq-admin-badge"><?php echo esc_html( 'v' . MUSOMO_QUOTE_VERSION ); ?></span>
			<a class="mq-admin-btn mq-admin-btn--ghost" href="https://musomo.com" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html__( 'Documentation', 'musomo-quote' ); ?>
			</a>
			<a class="mq-admin-btn mq-admin-btn--ghost" href="https://musomo.com" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html__( 'Support', 'musomo-quote' ); ?>
			</a>
		</div>
	</header>

	<nav class="mq-admin-tabs" aria-label="<?php echo esc_attr__( 'Musomo Quote sections', 'musomo-quote' ); ?>">
		<?php
		$tabs = array(
			'musomo-quote'              => __( 'Dashboard', 'musomo-quote' ),
			'musomo-quote-settings'     => __( 'Settings', 'musomo-quote' ),
			'musomo-quote-appearance'   => __( 'Appearance', 'musomo-quote' ),
			'musomo-quote-translations' => __( 'Texts & translations', 'musomo-quote' ),
			'musomo-quote-restrictions' => __( 'Restrictions', 'musomo-quote' ),
			'musomo-quote-cf7-builder'  => __( 'CF7 Template', 'musomo-quote' ),
			'musomo-quote-tools'        => __( 'Tools', 'musomo-quote' ),
		);

		foreach ( $tabs as $slug => $label ) :
			$active = ( $current === $slug ) ? ' is-active' : '';
			?>
			<a class="mq-admin-tabs__link<?php echo esc_attr( $active ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="mq-admin-content">
		<h2 class="mq-admin-content__title screen-reader-text"><?php echo esc_html( $page_title ); ?></h2>
