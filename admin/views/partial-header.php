<?php
/**
 * Admin shell header partial.
 *
 * @package Musomo_Quote
 *
 * @var string $page_title Optional page title override.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are defined in the including method scope.

$page_title = isset( $page_title ) ? $page_title : __( 'Dashboard', 'musomo-quote' );
$logo_url   = add_query_arg( 'ver', MUSOMO_QUOTE_VERSION, MUSOMO_QUOTE_URL . 'assets/08-logo.svg' );
$current    = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'musomo-quote'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap mq-admin">
	<header class="mq-admin-header">
		<div class="mq-admin-header__brand">
			<img
				class="mq-admin-header__logo"
				src="<?php echo esc_url( $logo_url ); ?>"
				alt="<?php echo esc_attr__( 'Musomo Quote', 'musomo-quote' ); ?>"
				width="170"
				height="60"
			/>
		</div>
		<div class="mq-admin-header__meta">
			<span class="mq-admin-badge"><?php echo esc_html( 'v' . MUSOMO_QUOTE_VERSION ); ?></span>
			<a class="mq-admin-btn mq-admin-btn--ghost" href="https://github.com/musomo-design/musomo-quote#readme" target="_blank" rel="noopener noreferrer">
				<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/documentation.svg' ); ?>" alt="" aria-hidden="true" width="18" height="18" />
				<?php echo esc_html__( 'Documentation', 'musomo-quote' ); ?>
			</a>
			<a class="mq-admin-btn mq-admin-btn--ghost" href="https://github.com/musomo-design/musomo-quote/issues" target="_blank" rel="noopener noreferrer">
				<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/support.svg' ); ?>" alt="" aria-hidden="true" width="18" height="18" />
				<?php echo esc_html__( 'Support', 'musomo-quote' ); ?>
			</a>
		</div>
	</header>

	<nav class="mq-admin-tabs" aria-label="<?php echo esc_attr__( 'Musomo Quote sections', 'musomo-quote' ); ?>">
		<?php
		$tabs = array(
			'musomo-quote'              => array( __( 'Dashboard', 'musomo-quote' ), 'dashboard.svg' ),
			'musomo-quote-settings'     => array( __( 'Settings', 'musomo-quote' ), 'settings.svg' ),
			'musomo-quote-appearance'   => array( __( 'Appearance', 'musomo-quote' ), 'appearance.svg' ),
			'musomo-quote-translations' => array( __( 'Texts & translations', 'musomo-quote' ), 'text_traslation.svg' ),
			'musomo-quote-restrictions' => array( __( 'Restrictions', 'musomo-quote' ), 'restrictions.svg' ),
			'musomo-quote-cf7-builder'  => array( __( 'CF7 Template', 'musomo-quote' ), 'cf7_template.svg' ),
			'musomo-quote-tools'        => array( __( 'Tools', 'musomo-quote' ), 'tools.svg' ),
		);

		foreach ( $tabs as $slug => $tab ) :
			$active = ( $current === $slug ) ? ' is-active' : '';
			?>
			<a class="mq-admin-tabs__link<?php echo esc_attr( $active ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>">
				<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/' . $tab[1] ); ?>" alt="" aria-hidden="true" width="20" height="20" />
				<span><?php echo esc_html( $tab[0] ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="mq-admin-content">
		<h2 class="mq-admin-content__title screen-reader-text"><?php echo esc_html( $page_title ); ?></h2>

<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
