<?php
/**
 * Frontend quote modal markup.
 *
 * @package Musomo_Quote
 *
 * @var string $modal_title Modal heading.
 * @var int    $form_id     Selected CF7 form ID.
 * @var bool   $cf7_active  Whether CF7 is available.
 */

defined( 'ABSPATH' ) || exit;

$modal_title = isset( $modal_title ) ? $modal_title : musomo_quote_get_text( 'modal_title' );
$cf7_active  = isset( $cf7_active ) ? (bool) $cf7_active : musomo_quote_is_cf7_active();
$form_id     = isset( $form_id ) ? absint( $form_id ) : 0;
$root_class  = musomo_quote_get_appearance_root_classes();
$appearance  = musomo_quote_get_resolved_appearance();
$close_label = musomo_quote_get_text( 'close_aria_label' );
?>
<div
	id="musomo-quote-modal"
	class="musomo-quote-modal <?php echo esc_attr( $root_class ); ?>"
	hidden
	aria-hidden="true"
	data-show-image="<?php echo ! empty( $appearance['summary_show_image'] ) ? '1' : '0'; ?>"
	data-show-sku="<?php echo ! empty( $appearance['summary_show_sku'] ) ? '1' : '0'; ?>"
	data-show-price="<?php echo ! empty( $appearance['summary_show_price'] ) ? '1' : '0'; ?>"
>
	<div class="musomo-quote-overlay" data-musomo-quote-close tabindex="-1"></div>

	<div
		class="musomo-quote-dialog"
		role="dialog"
		aria-modal="true"
		aria-labelledby="musomo-quote-modal-title"
		tabindex="-1"
	>
		<header class="musomo-quote-dialog__header">
			<h2 id="musomo-quote-modal-title" class="musomo-quote-dialog__title">
				<?php echo esc_html( $modal_title ); ?>
			</h2>
			<button
				type="button"
				class="musomo-quote-close"
				data-musomo-quote-close
				aria-label="<?php echo esc_attr( $close_label ); ?>"
			>
				<svg class="musomo-quote-close__icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
					<path d="M6 6l12 12M18 6L6 18" fill="none" />
				</svg>
			</button>
		</header>

		<div class="musomo-quote-dialog__body">
			<div class="musomo-quote-product-summary" data-musomo-quote-summary>
				<img
					class="musomo-quote-product-summary__image"
					alt=""
					data-musomo-quote-summary-image
					hidden
				/>
				<div class="musomo-quote-product-summary__meta">
					<h3 class="musomo-quote-product-summary__name" data-musomo-quote-summary-name></h3>
					<p class="musomo-quote-product-summary__sku" data-musomo-quote-summary-sku hidden></p>
					<p class="musomo-quote-product-summary__price" data-musomo-quote-summary-price hidden></p>
				</div>
			</div>

			<div class="musomo-quote-form" data-musomo-quote-form>
				<?php if ( ! $cf7_active ) : ?>
					<p class="musomo-quote-form-missing">
						<?php echo esc_html( musomo_quote_get_text( 'cf7_not_available_text' ) ); ?>
					</p>
				<?php elseif ( ! $form_id ) : ?>
					<p class="musomo-quote-form-missing">
						<?php echo esc_html( musomo_quote_get_text( 'form_not_configured_text' ) ); ?>
					</p>
				<?php else : ?>
					<?php Musomo_Quote_CF7::render_form( $form_id ); ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
