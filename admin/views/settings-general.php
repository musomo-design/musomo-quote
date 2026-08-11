<?php
/**
 * General settings view (base).
 *
 * @package Musomo_Quote
 *
 * @var array $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;

$page_title = __( 'Settings', 'musomo-quote' );
include MUSOMO_QUOTE_PATH . 'admin/views/partial-header.php';

$cf7_active = musomo_quote_is_cf7_active();
$cf7_forms  = $cf7_active ? Musomo_Quote_CF7::get_forms() : array();
$cf7_id     = isset( $settings['cf7_form_id'] ) ? absint( $settings['cf7_form_id'] ) : 0;
?>

<form method="post" action="options.php" class="mq-settings-form">
	<?php settings_fields( 'musomo_quote_settings_group' ); ?>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'General', 'musomo-quote' ); ?></h3>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__( 'Plugin active', 'musomo-quote' ); ?></th>
				<td>
					<label class="mq-toggle">
						<input type="checkbox" name="musomo_quote_settings[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
						<span><?php echo esc_html__( 'Enable Musomo Quote on the frontend', 'musomo-quote' ); ?></span>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Button mode', 'musomo-quote' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="musomo_quote_settings[button_mode]" value="add" <?php checked( $settings['button_mode'], 'add' ); ?> />
							<?php echo esc_html__( 'Add button while keeping “Add to cart”', 'musomo-quote' ); ?>
						</label><br />
						<label>
							<input type="radio" name="musomo_quote_settings[button_mode]" value="replace" <?php checked( $settings['button_mode'], 'replace' ); ?> />
							<?php echo esc_html__( 'Replace “Add to cart”', 'musomo-quote' ); ?>
						</label><br />
						<label>
							<input type="radio" name="musomo_quote_settings[button_mode]" value="selected" <?php checked( $settings['button_mode'], 'selected' ); ?> />
							<?php echo esc_html__( 'Show only on selected products', 'musomo-quote' ); ?>
						</label>
					</fieldset>
					<p class="description">
						<?php echo esc_html__( 'In “selected products only” mode, enable the Musomo Quote option on the product tab (Product data → General).', 'musomo-quote' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="mq-button-position"><?php echo esc_html__( 'Button position', 'musomo-quote' ); ?></label>
				</th>
				<td>
					<select id="mq-button-position" name="musomo_quote_settings[button_position]">
						<option value="before_add_to_cart" <?php selected( $settings['button_position'], 'before_add_to_cart' ); ?>>
							<?php echo esc_html__( 'Before Add to cart', 'musomo-quote' ); ?>
						</option>
						<option value="after_add_to_cart" <?php selected( $settings['button_position'], 'after_add_to_cart' ); ?>>
							<?php echo esc_html__( 'After Add to cart', 'musomo-quote' ); ?>
						</option>
						<option value="after_summary" <?php selected( $settings['button_position'], 'after_summary' ); ?>>
							<?php echo esc_html__( 'After product summary', 'musomo-quote' ); ?>
						</option>
						<option value="after_title" <?php selected( $settings['button_position'], 'after_title' ); ?>>
							<?php echo esc_html__( 'Below title', 'musomo-quote' ); ?>
						</option>
						<option value="after_price" <?php selected( $settings['button_position'], 'after_price' ); ?>>
							<?php echo esc_html__( 'Below price', 'musomo-quote' ); ?>
						</option>
						<option value="custom" <?php selected( $settings['button_position'], 'custom' ); ?>>
							<?php echo esc_html__( 'Custom position via hook', 'musomo-quote' ); ?>
						</option>
					</select>
					<p class="description">
						<?php echo esc_html__( 'In “replace” mode, before/after Add to cart positions place the button where Add to cart was.', 'musomo-quote' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Quote request form', 'musomo-quote' ); ?></h3>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="mq-cf7-form-id"><?php echo esc_html__( 'Contact Form 7 form', 'musomo-quote' ); ?></label>
				</th>
				<td>
					<?php if ( ! $cf7_active ) : ?>
						<p class="description">
							<?php echo esc_html__( 'Contact Form 7 is not available. Install or activate Contact Form 7 to use the quote request form.', 'musomo-quote' ); ?>
						</p>
						<input type="hidden" name="musomo_quote_settings[cf7_form_id]" value="<?php echo esc_attr( (string) $cf7_id ); ?>" />
					<?php else : ?>
						<select id="mq-cf7-form-id" name="musomo_quote_settings[cf7_form_id]">
							<option value="0"><?php echo esc_html__( 'Select form…', 'musomo-quote' ); ?></option>
							<?php foreach ( $cf7_forms as $id => $title ) : ?>
								<option value="<?php echo esc_attr( (string) $id ); ?>" <?php selected( $cf7_id, (int) $id ); ?>>
									<?php echo esc_html( $title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php
							if ( musomo_quote_is_polylang_active() ) {
								echo esc_html__( 'Default / fallback form. With Polylang you can assign different forms per language in Texts & translations.', 'musomo-quote' );
							} else {
								echo esc_html__( 'In the CF7 form you can use hidden fields: musomo_product_id, musomo_product_name, musomo_product_sku, musomo_product_url, musomo_product_image, musomo_product_price, musomo_product_type, musomo_quantity.', 'musomo-quote' );
							}
							?>
						</p>
						<?php if ( musomo_quote_is_polylang_active() ) : ?>
							<p class="description">
								<?php echo esc_html__( 'CF7 hidden fields: musomo_product_id, musomo_product_name, musomo_product_sku, musomo_product_url, musomo_product_image, musomo_product_price, musomo_product_type, musomo_quantity.', 'musomo-quote' ); ?>
							</p>
						<?php endif; ?>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<input type="hidden" name="musomo_quote_settings[_mq_settings_screen]" value="general" />

		<?php
		// Preserve advanced keys not edited on this screen.
		?>
		<input type="hidden" name="musomo_quote_settings[button_priority]" value="<?php echo esc_attr( (string) $settings['button_priority'] ); ?>" />
		<input type="hidden" name="musomo_quote_settings[delete_data_on_uninstall]" value="<?php echo ! empty( $settings['delete_data_on_uninstall'] ) ? '1' : '0'; ?>" />
		<input type="hidden" name="musomo_quote_settings[debug_mode]" value="<?php echo ! empty( $settings['debug_mode'] ) ? '1' : '0'; ?>" />

		<?php submit_button( __( 'Save settings', 'musomo-quote' ) ); ?>
	</div>
</form>

<?php
include MUSOMO_QUOTE_PATH . 'admin/views/partial-footer.php';
