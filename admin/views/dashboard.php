<?php
/**
 * Dashboard view.
 *
 * @package Musomo_Quote
 *
 * @var array $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;

$page_title = __( 'Dashboard', 'musomo-quote' );
include MUSOMO_QUOTE_PATH . 'admin/views/partial-header.php';

$plugin_on   = ! empty( $settings['enabled'] );
$wc_active   = musomo_quote_is_woocommerce_active();
$cf7_active  = musomo_quote_is_cf7_active();
$pll_active  = musomo_quote_is_polylang_active();
$cf7_form_id = isset( $settings['cf7_form_id'] ) ? absint( $settings['cf7_form_id'] ) : 0;
$form_label  = musomo_quote_get_cf7_status_label();
$quote_products_label = musomo_quote_get_quote_products_status_label();
$linked_cf7_count = count( musomo_quote_get_linked_cf7_form_ids() );
?>

<div class="mq-admin-grid">
	<div class="mq-card mq-card--cta">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Configure quote form', 'musomo-quote' ); ?></h3>
		<p class="mq-card__text">
			<?php echo esc_html__( 'Create the form and email for Contact Form 7.', 'musomo-quote' ); ?>
		</p>
		<p class="mq-status <?php echo ( $cf7_form_id > 0 || $linked_cf7_count > 0 ) ? 'mq-status--ok' : 'mq-status--warning'; ?>" style="margin:10px 0;">
			<?php
			echo esc_html__( 'Linked CF7 form:', 'musomo-quote' ) . ' ';
			if ( $cf7_form_id > 0 || $linked_cf7_count > 0 ) {
				echo esc_html( $form_label );
			} else {
				echo esc_html__( 'No linked form', 'musomo-quote' );
			}
			?>
		</p>
		<p style="margin:0;">
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=musomo-quote-cf7-builder' ) ); ?>">
				<?php echo esc_html__( 'Configure template →', 'musomo-quote' ); ?>
			</a>
		</p>
	</div>

	<div class="mq-card">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Plugin status', 'musomo-quote' ); ?></h3>
		<p class="mq-status <?php echo $plugin_on ? 'mq-status--ok' : 'mq-status--warning'; ?>">
			<?php echo $plugin_on ? esc_html__( 'Active', 'musomo-quote' ) : esc_html__( 'Inactive', 'musomo-quote' ); ?>
		</p>
	</div>

	<div class="mq-card">
		<h3 class="mq-card__title"><?php echo esc_html__( 'WooCommerce', 'musomo-quote' ); ?></h3>
		<p class="mq-status <?php echo $wc_active ? 'mq-status--ok' : 'mq-status--error'; ?>">
			<?php echo $wc_active ? esc_html__( 'Detected', 'musomo-quote' ) : esc_html__( 'Not detected', 'musomo-quote' ); ?>
		</p>
	</div>

	<div class="mq-card">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Contact Form 7', 'musomo-quote' ); ?></h3>
		<p class="mq-status <?php echo $cf7_active ? 'mq-status--ok' : 'mq-status--error'; ?>">
			<?php echo $cf7_active ? esc_html__( 'Detected', 'musomo-quote' ) : esc_html__( 'Not detected', 'musomo-quote' ); ?>
		</p>
	</div>

	<div class="mq-card">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Linked CF7 form', 'musomo-quote' ); ?></h3>
		<p class="mq-status <?php echo ( $cf7_form_id > 0 || $linked_cf7_count > 0 ) ? 'mq-status--ok' : 'mq-status--warning'; ?>">
			<?php echo esc_html( $form_label ); ?>
		</p>
	</div>

	<div class="mq-card">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Turnstile status', 'musomo-quote' ); ?></h3>
		<?php
		$turnstile_status = Musomo_Quote_Security::detect_turnstile_status();
		$turnstile_class  = ( 'managed' === $turnstile_status ) ? 'mq-status--ok' : 'mq-status--muted';
		?>
		<p class="mq-status <?php echo esc_attr( $turnstile_class ); ?>">
			<?php echo esc_html( Musomo_Quote_Security::get_turnstile_status_label() ); ?>
		</p>
	</div>

	<div class="mq-card">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Antispam status', 'musomo-quote' ); ?></h3>
		<?php
		$antispam_key   = Musomo_Quote_Security::get_antispam_status_key();
		$antispam_class = ( 'active' === $antispam_key ) ? 'mq-status--ok' : ( ( 'partial' === $antispam_key ) ? 'mq-status--warning' : 'mq-status--muted' );
		?>
		<p class="mq-status <?php echo esc_attr( $antispam_class ); ?>">
			<?php echo esc_html( Musomo_Quote_Security::get_antispam_status_label() ); ?>
		</p>
	</div>

	<div class="mq-card">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Polylang', 'musomo-quote' ); ?></h3>
		<?php if ( $pll_active ) : ?>
			<?php
			$lang_count = class_exists( 'Musomo_Quote_I18n' ) ? Musomo_Quote_I18n::get_language_count() : 0;
			?>
			<p class="mq-status mq-status--ok">
				<?php
				/* translators: %d: number of Polylang languages */
				echo esc_html( sprintf( __( 'Active — %d languages', 'musomo-quote' ), $lang_count ) );
				?>
			</p>
		<?php else : ?>
			<p class="mq-status mq-status--muted">
				<?php echo esc_html__( 'Not present (optional)', 'musomo-quote' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<div class="mq-card">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Quote products', 'musomo-quote' ); ?></h3>
		<p class="mq-status mq-status--ok"><?php echo esc_html( $quote_products_label ); ?></p>
	</div>
</div>

<p class="mq-admin-note">
	<?php echo esc_html__( 'Use CF7 Template to generate the Contact Form 7 form and email.', 'musomo-quote' ); ?>
</p>

<div class="mq-card mq-support-card">
	<h3 class="mq-card__title"><?php echo esc_html__( 'Support Musomo Quote', 'musomo-quote' ); ?></h3>
	<p class="mq-card__text">
		<?php echo esc_html__( 'If Musomo Quote is useful for your store or projects, you can support its continued development.', 'musomo-quote' ); ?>
	</p>
	<p style="margin:12px 0 0;">
		<a
			class="mq-admin-btn mq-admin-btn--ghost"
			href="<?php echo esc_url( 'https://ko-fi.com/musomo' ); ?>"
			target="_blank"
			rel="noopener noreferrer"
		>
			<?php echo esc_html__( 'Support on Ko-fi', 'musomo-quote' ); ?>
		</a>
	</p>
</div>

<?php
include MUSOMO_QUOTE_PATH . 'admin/views/partial-footer.php';
