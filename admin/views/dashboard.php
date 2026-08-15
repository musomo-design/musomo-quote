<?php
/**
 * Dashboard view.
 *
 * @package Musomo_Quote
 *
 * @var array $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are defined in the including method scope.

$page_title = __( 'Dashboard', 'musomo-quote' );
include MUSOMO_QUOTE_PATH . 'admin/views/partial-header.php';

$plugin_on            = ! empty( $settings['enabled'] );
$wc_active            = musomo_quote_is_woocommerce_active();
$cf7_active           = musomo_quote_is_cf7_active();
$pll_active           = musomo_quote_is_polylang_active();
$cf7_form_id          = isset( $settings['cf7_form_id'] ) ? absint( $settings['cf7_form_id'] ) : 0;
$form_label           = musomo_quote_get_cf7_status_label();
$quote_products_label = musomo_quote_get_quote_products_status_label();
$linked_cf7_count     = count( musomo_quote_get_linked_cf7_form_ids() );
$has_form             = ( $cf7_form_id > 0 || $linked_cf7_count > 0 );

/*
 * Setup progress (visual only):
 * 1) WooCommerce + CF7 available
 * 2) At least one CF7 form linked
 * 3) Plugin enabled (ready to customize / use)
 * 4) Test & publish — left open (manual)
 */
$setup_steps = array(
	( $wc_active && $cf7_active ),
	$has_form,
	( $has_form && $plugin_on ),
	false,
);
$setup_done  = 0;
foreach ( $setup_steps as $done ) {
	if ( $done ) {
		++$setup_done;
	}
}
$setup_labels = array(
	__( 'Connect integrations', 'musomo-quote' ),
	__( 'Configure form', 'musomo-quote' ),
	__( 'Customize template', 'musomo-quote' ),
	__( 'Test & publish', 'musomo-quote' ),
);

$turnstile_status = Musomo_Quote_Security::detect_turnstile_status();
$turnstile_class  = ( 'managed' === $turnstile_status ) ? 'mq-status--ok' : 'mq-status--muted';
$antispam_key     = Musomo_Quote_Security::get_antispam_status_key();
$antispam_class   = ( 'active' === $antispam_key ) ? 'mq-status--ok' : ( ( 'partial' === $antispam_key ) ? 'mq-status--warning' : 'mq-status--muted' );
?>

<section class="mq-dashboard-hero mq-card">
	<div class="mq-dashboard-hero__action">
		<div class="mq-dashboard-hero__icon">
			<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/configure_quote_form.svg' ); ?>" alt="" aria-hidden="true" width="58" height="58" />
		</div>
		<div>
			<h3 class="mq-card__title"><?php echo esc_html__( 'Configure quote form', 'musomo-quote' ); ?></h3>
			<p class="mq-card__text">
				<?php echo esc_html__( 'Create the form and email for Contact Form 7.', 'musomo-quote' ); ?>
			</p>
			<p class="mq-linked-form <?php echo $has_form ? 'is-ok' : 'is-warning'; ?>">
				<span aria-hidden="true">●</span>
				<?php
				echo esc_html__( 'Linked CF7 form:', 'musomo-quote' ) . ' ';
				echo $has_form ? esc_html( $form_label ) : esc_html__( 'No linked form', 'musomo-quote' );
				?>
			</p>
			<a class="mq-admin-btn mq-admin-btn--primary" href="<?php echo esc_url( admin_url( 'admin.php?page=musomo-quote-cf7-builder' ) ); ?>">
				<?php echo esc_html__( 'Configure template', 'musomo-quote' ); ?>
				<span aria-hidden="true">›</span>
			</a>
		</div>
	</div>

	<div class="mq-setup">
		<div class="mq-setup__heading">
			<strong><?php echo esc_html__( 'Setup progress', 'musomo-quote' ); ?></strong>
			<span>
				<?php
				/* translators: 1: completed steps, 2: total steps */
				echo esc_html( sprintf( __( '%1$d of %2$d completed', 'musomo-quote' ), $setup_done, 4 ) );
				?>
			</span>
		</div>
		<ol class="mq-setup__steps">
			<?php foreach ( $setup_labels as $index => $label ) : ?>
				<?php
				$is_complete = ! empty( $setup_steps[ $index ] );
				$is_current  = ( ! $is_complete && $index === $setup_done );
				$li_class    = $is_complete ? 'is-complete' : ( $is_current ? 'is-current' : '' );
				?>
				<li class="<?php echo esc_attr( $li_class ); ?>">
					<span><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
					<small><?php echo esc_html( $label ); ?></small>
				</li>
			<?php endforeach; ?>
		</ol>
		<p class="mq-setup__note">
			<span class="mq-info-dot" aria-hidden="true">i</span>
			<?php echo esc_html__( 'Use CF7 Template to generate the Contact Form 7 form and email.', 'musomo-quote' ); ?>
		</p>
	</div>
</section>

<div class="mq-admin-grid mq-dashboard-grid">
	<a class="mq-card mq-status-card" href="<?php echo esc_url( admin_url( 'admin.php?page=musomo-quote-settings' ) ); ?>">
		<span class="mq-status-card__icon">
			<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/plugin_status.svg' ); ?>" alt="" aria-hidden="true" width="34" height="34" />
		</span>
		<span class="mq-status-card__content">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Plugin status', 'musomo-quote' ); ?></h3>
			<span class="mq-status <?php echo $plugin_on ? 'mq-status--ok' : 'mq-status--warning'; ?>">
				<?php echo $plugin_on ? esc_html__( 'Active', 'musomo-quote' ) : esc_html__( 'Inactive', 'musomo-quote' ); ?>
			</span>
		</span>
		<span class="mq-card__chevron" aria-hidden="true">›</span>
	</a>

	<a class="mq-card mq-status-card" href="<?php echo esc_url( admin_url( 'admin.php?page=musomo-quote-settings' ) ); ?>">
		<span class="mq-status-card__icon">
			<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/woocommerce.svg' ); ?>" alt="" aria-hidden="true" width="34" height="34" />
		</span>
		<span class="mq-status-card__content">
			<h3 class="mq-card__title"><?php echo esc_html__( 'WooCommerce', 'musomo-quote' ); ?></h3>
			<span class="mq-status <?php echo $wc_active ? 'mq-status--ok' : 'mq-status--error'; ?>">
				<?php echo $wc_active ? esc_html__( 'Connected', 'musomo-quote' ) : esc_html__( 'Not detected', 'musomo-quote' ); ?>
			</span>
		</span>
		<span class="mq-card__chevron" aria-hidden="true">›</span>
	</a>

	<a class="mq-card mq-status-card" href="<?php echo esc_url( admin_url( 'admin.php?page=musomo-quote-settings' ) ); ?>">
		<span class="mq-status-card__icon">
			<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/contact_form7.svg' ); ?>" alt="" aria-hidden="true" width="34" height="34" />
		</span>
		<span class="mq-status-card__content">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Contact Form 7', 'musomo-quote' ); ?></h3>
			<span class="mq-status <?php echo $cf7_active ? 'mq-status--ok' : 'mq-status--error'; ?>">
				<?php echo $cf7_active ? esc_html__( 'Connected', 'musomo-quote' ) : esc_html__( 'Not detected', 'musomo-quote' ); ?>
			</span>
		</span>
		<span class="mq-card__chevron" aria-hidden="true">›</span>
	</a>

	<a class="mq-card mq-status-card mq-status-card--info" href="<?php echo esc_url( admin_url( 'admin.php?page=musomo-quote-cf7-builder' ) ); ?>">
		<span class="mq-status-card__icon">
			<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/link.svg' ); ?>" alt="" aria-hidden="true" width="34" height="34" />
		</span>
		<span class="mq-status-card__content">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Linked CF7 form', 'musomo-quote' ); ?></h3>
			<span class="mq-status <?php echo $has_form ? 'mq-status--info' : 'mq-status--warning'; ?>">
				<?php echo esc_html( $form_label ); ?>
			</span>
		</span>
		<span class="mq-card__chevron" aria-hidden="true">›</span>
	</a>

	<a class="mq-card mq-status-card" href="<?php echo esc_url( admin_url( 'admin.php?page=musomo-quote-restrictions' ) ); ?>">
		<span class="mq-status-card__icon">
			<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/turnstile_status.svg' ); ?>" alt="" aria-hidden="true" width="34" height="34" />
		</span>
		<span class="mq-status-card__content">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Turnstile status', 'musomo-quote' ); ?></h3>
			<span class="mq-status <?php echo esc_attr( $turnstile_class ); ?>">
				<?php echo esc_html( Musomo_Quote_Security::get_turnstile_status_label() ); ?>
			</span>
		</span>
		<span class="mq-card__chevron" aria-hidden="true">›</span>
	</a>

	<a class="mq-card mq-status-card" href="<?php echo esc_url( admin_url( 'admin.php?page=musomo-quote-restrictions' ) ); ?>">
		<span class="mq-status-card__icon">
			<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/antispam_status.svg' ); ?>" alt="" aria-hidden="true" width="34" height="34" />
		</span>
		<span class="mq-status-card__content">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Antispam status', 'musomo-quote' ); ?></h3>
			<span class="mq-status <?php echo esc_attr( $antispam_class ); ?>">
				<?php echo esc_html( Musomo_Quote_Security::get_antispam_status_label() ); ?>
			</span>
		</span>
		<span class="mq-card__chevron" aria-hidden="true">›</span>
	</a>

	<a class="mq-card mq-status-card mq-status-card--info" href="<?php echo esc_url( admin_url( 'admin.php?page=musomo-quote-translations' ) ); ?>">
		<span class="mq-status-card__icon">
			<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/Polylang.svg' ); ?>" alt="" aria-hidden="true" width="34" height="34" />
		</span>
		<span class="mq-status-card__content">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Polylang', 'musomo-quote' ); ?></h3>
			<?php if ( $pll_active ) : ?>
				<?php $lang_count = class_exists( 'Musomo_Quote_I18n' ) ? Musomo_Quote_I18n::get_language_count() : 0; ?>
				<span class="mq-status mq-status--ok">
					<?php
					/* translators: %d: number of Polylang languages */
					echo esc_html( sprintf( __( 'Active — %d languages', 'musomo-quote' ), $lang_count ) );
					?>
				</span>
			<?php else : ?>
				<span class="mq-status mq-status--info">
					<?php echo esc_html__( 'Not present (optional)', 'musomo-quote' ); ?>
				</span>
			<?php endif; ?>
		</span>
		<span class="mq-card__chevron" aria-hidden="true">›</span>
	</a>

	<a class="mq-card mq-status-card" href="<?php echo esc_url( admin_url( 'admin.php?page=musomo-quote-restrictions' ) ); ?>">
		<span class="mq-status-card__icon">
			<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/quote_products.svg' ); ?>" alt="" aria-hidden="true" width="34" height="34" />
		</span>
		<span class="mq-status-card__content">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Quote products', 'musomo-quote' ); ?></h3>
			<span class="mq-status mq-status--ok"><?php echo esc_html( $quote_products_label ); ?></span>
		</span>
		<span class="mq-card__chevron" aria-hidden="true">›</span>
	</a>
</div>

<div class="mq-help-strip">
	<span class="mq-help-strip__icon" aria-hidden="true">i</span>
	<div>
		<strong><?php echo esc_html__( 'Need help getting started?', 'musomo-quote' ); ?></strong>
		<small><?php echo esc_html__( 'Check out our documentation or contact support for assistance.', 'musomo-quote' ); ?></small>
	</div>
	<a class="mq-admin-btn mq-admin-btn--blue" href="https://github.com/musomo-design/musomo-quote#readme" target="_blank" rel="noopener noreferrer">
		<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/documentation.svg' ); ?>" alt="" aria-hidden="true" width="18" height="18" />
		<?php echo esc_html__( 'Documentation', 'musomo-quote' ); ?>
	</a>
	<a class="mq-admin-btn mq-admin-btn--ghost" href="https://github.com/musomo-design/musomo-quote/issues" target="_blank" rel="noopener noreferrer">
		<img src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/support.svg' ); ?>" alt="" aria-hidden="true" width="18" height="18" />
		<?php echo esc_html__( 'Support', 'musomo-quote' ); ?>
	</a>
</div>

<p class="mq-support-inline">
	<?php echo esc_html__( 'If Musomo Quote is useful for your store or projects, you can', 'musomo-quote' ); ?>
	<a href="<?php echo esc_url( 'https://ko-fi.com/musomo' ); ?>" target="_blank" rel="noopener noreferrer">
		<?php echo esc_html__( 'support development on Ko-fi', 'musomo-quote' ); ?>
	</a>.
</p>

<?php
include MUSOMO_QUOTE_PATH . 'admin/views/partial-footer.php';

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
