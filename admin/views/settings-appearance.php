<?php
/**
 * Appearance settings view.
 *
 * @package Musomo_Quote
 *
 * @var array $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are defined in the including method scope.

$page_title = __( 'Appearance', 'musomo-quote' );
include MUSOMO_QUOTE_PATH . 'admin/views/partial-header.php';

$a = wp_parse_args( $settings, musomo_quote_appearance_defaults() );

/**
 * Render a color field (hex). Allows transparent when $allow_transparent.
 *
 * @param string $name              Field name key.
 * @param string $value             Current value.
 * @param bool   $allow_transparent Allow transparent keyword.
 */
$mq_color_field = static function ( $name, $value, $allow_transparent = false ) {
	$value = is_string( $value ) ? $value : '#000000';
	$is_transparent = ( 'transparent' === strtolower( $value ) || 'inherit' === strtolower( $value ) || 'currentcolor' === strtolower( $value ) );
	$picker_value   = $is_transparent ? '#000000' : $value;
	if ( ! preg_match( '/^#[a-f0-9]{6}$/i', $picker_value ) ) {
		$picker_value = '#000000';
	}
	?>
	<span class="mq-color-field">
		<?php if ( ! $allow_transparent ) : ?>
			<input
				type="color"
				class="mq-color-picker"
				value="<?php echo esc_attr( $picker_value ); ?>"
				data-mq-color-for="<?php echo esc_attr( $name ); ?>"
				aria-label="<?php echo esc_attr__( 'Color picker', 'musomo-quote' ); ?>"
			/>
		<?php endif; ?>
		<input
			type="text"
			class="mq-color-text regular-text"
			name="musomo_quote_settings[<?php echo esc_attr( $name ); ?>]"
			id="mq-field-<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			data-mq-appearance="<?php echo esc_attr( $name ); ?>"
			<?php echo $allow_transparent ? '' : ' pattern="^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$"'; ?>
			placeholder="<?php echo $allow_transparent ? esc_attr( 'transparent / #000000' ) : esc_attr( '#000000' ); ?>"
		/>
	</span>
	<?php
};
?>

<form method="post" action="options.php" class="mq-settings-form mq-appearance-form" id="mq-appearance-form">
	<?php settings_fields( 'musomo_quote_settings_group' ); ?>
	<input type="hidden" name="musomo_quote_settings[_mq_settings_screen]" value="appearance" />

	<div class="mq-appearance-layout">
		<div class="mq-appearance-controls">

			<div class="mq-card mq-card--form">
				<h3 class="mq-card__title"><?php echo esc_html__( 'Style', 'musomo-quote' ); ?></h3>
				<fieldset class="mq-preset-fieldset" data-mq-appearance-group="preset">
					<label class="mq-preset-option">
						<input type="radio" name="musomo_quote_settings[appearance_style]" value="theme" data-mq-appearance="appearance_style" <?php checked( $a['appearance_style'], 'theme' ); ?> />
						<span><?php echo esc_html__( 'Inherit from theme', 'musomo-quote' ); ?></span>
					</label>
					<label class="mq-preset-option">
						<input type="radio" name="musomo_quote_settings[appearance_style]" value="musomo" data-mq-appearance="appearance_style" <?php checked( $a['appearance_style'], 'musomo' ); ?> />
						<span><?php echo esc_html__( 'Musomo', 'musomo-quote' ); ?></span>
					</label>
					<label class="mq-preset-option">
						<input type="radio" name="musomo_quote_settings[appearance_style]" value="custom" data-mq-appearance="appearance_style" <?php checked( $a['appearance_style'], 'custom' ); ?> />
						<span><?php echo esc_html__( 'Custom', 'musomo-quote' ); ?></span>
					</label>
				</fieldset>
				<p class="description"><?php echo esc_html__( '“Inherit from theme” blends the button with the site style. “Musomo” applies the plugin design. “Custom” enables the controls below.', 'musomo-quote' ); ?></p>
			</div>

			<div class="mq-card mq-card--form mq-appearance-custom-only">
				<h3 class="mq-card__title"><?php echo esc_html__( 'Request a Quote button', 'musomo-quote' ); ?></h3>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Background color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'btn_bg', $a['btn_bg'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Text color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'btn_text', $a['btn_text'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Border color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'btn_border', $a['btn_border'], true ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Hover background', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'btn_hover_bg', $a['btn_hover_bg'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Hover text', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'btn_hover_text', $a['btn_hover_text'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-btn_radius"><?php echo esc_html__( 'Border radius', 'musomo-quote' ); ?></label></th>
						<td><input type="number" min="0" max="40" id="mq-field-btn_radius" name="musomo_quote_settings[btn_radius]" value="<?php echo esc_attr( (string) $a['btn_radius'] ); ?>" data-mq-appearance="btn_radius" /> px</td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-btn_height"><?php echo esc_html__( 'Height', 'musomo-quote' ); ?></label></th>
						<td><input type="number" min="32" max="72" id="mq-field-btn_height" name="musomo_quote_settings[btn_height]" value="<?php echo esc_attr( (string) $a['btn_height'] ); ?>" data-mq-appearance="btn_height" /> px</td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-btn_padding_x"><?php echo esc_html__( 'Horizontal padding', 'musomo-quote' ); ?></label></th>
						<td><input type="number" min="8" max="64" id="mq-field-btn_padding_x" name="musomo_quote_settings[btn_padding_x]" value="<?php echo esc_attr( (string) $a['btn_padding_x'] ); ?>" data-mq-appearance="btn_padding_x" /> px</td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-btn_font_size"><?php echo esc_html__( 'Font size', 'musomo-quote' ); ?></label></th>
						<td><input type="number" min="12" max="24" id="mq-field-btn_font_size" name="musomo_quote_settings[btn_font_size]" value="<?php echo esc_attr( (string) $a['btn_font_size'] ); ?>" data-mq-appearance="btn_font_size" /> px</td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-btn_font_weight"><?php echo esc_html__( 'Font weight', 'musomo-quote' ); ?></label></th>
						<td>
							<select id="mq-field-btn_font_weight" name="musomo_quote_settings[btn_font_weight]" data-mq-appearance="btn_font_weight">
								<?php foreach ( array( 400, 500, 600, 700 ) as $weight ) : ?>
									<option value="<?php echo esc_attr( (string) $weight ); ?>" <?php selected( (int) $a['btn_font_weight'], $weight ); ?>><?php echo esc_html( (string) $weight ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Width', 'musomo-quote' ); ?></th>
						<td>
							<label><input type="radio" name="musomo_quote_settings[btn_width]" value="auto" data-mq-appearance="btn_width" <?php checked( $a['btn_width'], 'auto' ); ?> /> <?php echo esc_html__( 'Auto', 'musomo-quote' ); ?></label>
							&nbsp;
							<label><input type="radio" name="musomo_quote_settings[btn_width]" value="full" data-mq-appearance="btn_width" <?php checked( $a['btn_width'], 'full' ); ?> /> <?php echo esc_html__( '100%', 'musomo-quote' ); ?></label>
						</td>
					</tr>
				</table>
			</div>

			<div class="mq-card mq-card--form mq-appearance-custom-only">
				<h3 class="mq-card__title"><?php echo esc_html__( 'Quote request window', 'musomo-quote' ); ?></h3>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="mq-field-modal_width"><?php echo esc_html__( 'Width desktop', 'musomo-quote' ); ?></label></th>
						<td>
							<select id="mq-field-modal_width" name="musomo_quote_settings[modal_width]" data-mq-appearance="modal_width">
								<?php foreach ( array( 600, 720, 800, 900, 1000 ) as $w ) : ?>
									<option value="<?php echo esc_attr( (string) $w ); ?>" <?php selected( (int) $a['modal_width'], $w ); ?>><?php echo esc_html( $w . ' px' ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-modal_radius"><?php echo esc_html__( 'Border radius', 'musomo-quote' ); ?></label></th>
						<td><input type="number" min="0" max="30" id="mq-field-modal_radius" name="musomo_quote_settings[modal_radius]" value="<?php echo esc_attr( (string) $a['modal_radius'] ); ?>" data-mq-appearance="modal_radius" /> px</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Background color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'modal_bg', $a['modal_bg'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Text color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'modal_text', $a['modal_text'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-modal_padding"><?php echo esc_html__( 'Inner padding', 'musomo-quote' ); ?></label></th>
						<td><input type="number" min="16" max="48" id="mq-field-modal_padding" name="musomo_quote_settings[modal_padding]" value="<?php echo esc_attr( (string) $a['modal_padding'] ); ?>" data-mq-appearance="modal_padding" /> px</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Overlay color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'overlay_color', $a['overlay_color'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-overlay_opacity"><?php echo esc_html__( 'Overlay opacity', 'musomo-quote' ); ?></label></th>
						<td>
							<input type="range" min="0" max="90" id="mq-field-overlay_opacity" name="musomo_quote_settings[overlay_opacity]" value="<?php echo esc_attr( (string) $a['overlay_opacity'] ); ?>" data-mq-appearance="overlay_opacity" />
							<span data-mq-opacity-label><?php echo esc_html( (string) (int) $a['overlay_opacity'] ); ?>%</span>
						</td>
					</tr>
				</table>
			</div>

			<div class="mq-card mq-card--form mq-appearance-custom-only">
				<h3 class="mq-card__title"><?php echo esc_html__( 'Close button', 'musomo-quote' ); ?></h3>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="mq-field-close_size"><?php echo esc_html__( 'Size', 'musomo-quote' ); ?></label></th>
						<td>
							<select id="mq-field-close_size" name="musomo_quote_settings[close_size]" data-mq-appearance="close_size">
								<?php foreach ( array( 32, 36, 40, 44 ) as $size ) : ?>
									<option value="<?php echo esc_attr( (string) $size ); ?>" <?php selected( (int) $a['close_size'], $size ); ?>><?php echo esc_html( $size . ' px' ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Icon color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'close_color', $a['close_color'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Background color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'close_bg', $a['close_bg'], true ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-close_radius"><?php echo esc_html__( 'Border radius', 'musomo-quote' ); ?></label></th>
						<td><input type="number" min="0" max="24" id="mq-field-close_radius" name="musomo_quote_settings[close_radius]" value="<?php echo esc_attr( (string) $a['close_radius'] ); ?>" data-mq-appearance="close_radius" /> px</td>
					</tr>
				</table>
			</div>

			<div class="mq-card mq-card--form">
				<h3 class="mq-card__title"><?php echo esc_html__( 'Product summary', 'musomo-quote' ); ?></h3>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Show image', 'musomo-quote' ); ?></th>
						<td><label><input type="checkbox" name="musomo_quote_settings[summary_show_image]" value="1" data-mq-appearance="summary_show_image" <?php checked( ! empty( $a['summary_show_image'] ) ); ?> /> <?php echo esc_html__( 'Active', 'musomo-quote' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Show SKU', 'musomo-quote' ); ?></th>
						<td><label><input type="checkbox" name="musomo_quote_settings[summary_show_sku]" value="1" data-mq-appearance="summary_show_sku" <?php checked( ! empty( $a['summary_show_sku'] ) ); ?> /> <?php echo esc_html__( 'Active', 'musomo-quote' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Show price', 'musomo-quote' ); ?></th>
						<td><label><input type="checkbox" name="musomo_quote_settings[summary_show_price]" value="1" data-mq-appearance="summary_show_price" <?php checked( ! empty( $a['summary_show_price'] ) ); ?> /> <?php echo esc_html__( 'Active', 'musomo-quote' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-summary_image_size"><?php echo esc_html__( 'Image size', 'musomo-quote' ); ?></label></th>
						<td>
							<select id="mq-field-summary_image_size" name="musomo_quote_settings[summary_image_size]" data-mq-appearance="summary_image_size">
								<?php foreach ( array( 64, 80, 96, 120 ) as $size ) : ?>
									<option value="<?php echo esc_attr( (string) $size ); ?>" <?php selected( (int) $a['summary_image_size'], $size ); ?>><?php echo esc_html( $size . ' px' ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-summary_layout"><?php echo esc_html__( 'Layout', 'musomo-quote' ); ?></label></th>
						<td>
							<select id="mq-field-summary_layout" name="musomo_quote_settings[summary_layout]" data-mq-appearance="summary_layout">
								<option value="compact" <?php selected( $a['summary_layout'], 'compact' ); ?>><?php echo esc_html__( 'Compact', 'musomo-quote' ); ?></option>
								<option value="horizontal" <?php selected( $a['summary_layout'], 'horizontal' ); ?>><?php echo esc_html__( 'Horizontal', 'musomo-quote' ); ?></option>
								<option value="vertical" <?php selected( $a['summary_layout'], 'vertical' ); ?>><?php echo esc_html__( 'Vertical', 'musomo-quote' ); ?></option>
							</select>
						</td>
					</tr>
				</table>
			</div>

			<div class="mq-card mq-card--form mq-appearance-custom-only">
				<h3 class="mq-card__title"><?php echo esc_html__( 'Form', 'musomo-quote' ); ?></h3>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="mq-field-field_height"><?php echo esc_html__( 'Field height', 'musomo-quote' ); ?></label></th>
						<td>
							<select id="mq-field-field_height" name="musomo_quote_settings[field_height]" data-mq-appearance="field_height">
								<?php foreach ( array( 40, 44, 48, 52 ) as $h ) : ?>
									<option value="<?php echo esc_attr( (string) $h ); ?>" <?php selected( (int) $a['field_height'], $h ); ?>><?php echo esc_html( $h . ' px' ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-field_radius"><?php echo esc_html__( 'Field border radius', 'musomo-quote' ); ?></label></th>
						<td><input type="number" min="0" max="16" id="mq-field-field_radius" name="musomo_quote_settings[field_radius]" value="<?php echo esc_attr( (string) $a['field_radius'] ); ?>" data-mq-appearance="field_radius" /> px</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Border color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'field_border', $a['field_border'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Focus color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'field_focus', $a['field_focus'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'SEND button color', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'submit_bg', $a['submit_bg'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Text color SEND', 'musomo-quote' ); ?></th>
						<td><?php $mq_color_field( 'submit_text', $a['submit_text'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="mq-field-submit_radius"><?php echo esc_html__( 'SEND border radius', 'musomo-quote' ); ?></label></th>
						<td><input type="number" min="0" max="24" id="mq-field-submit_radius" name="musomo_quote_settings[submit_radius]" value="<?php echo esc_attr( (string) $a['submit_radius'] ); ?>" data-mq-appearance="submit_radius" /> px</td>
					</tr>
				</table>
			</div>

			<p class="submit mq-appearance-actions">
				<?php submit_button( __( 'Save appearance', 'musomo-quote' ), 'primary', 'submit', false ); ?>
				<button type="submit" class="button" name="musomo_quote_settings[reset_appearance]" value="1" onclick="return confirm('<?php echo esc_js( __( 'Restore only Appearance settings to defaults?', 'musomo-quote' ) ); ?>');">
					<?php echo esc_html__( 'Restore defaults', 'musomo-quote' ); ?>
				</button>
			</p>
		</div>

		<div class="mq-appearance-preview-col">
			<div class="mq-card mq-appearance-preview-card">
				<h3 class="mq-card__title"><?php echo esc_html__( 'Preview', 'musomo-quote' ); ?></h3>
				<p class="description"><?php echo esc_html__( 'Demo preview. Does not use a real Contact Form 7 form. Save to apply on the frontend.', 'musomo-quote' ); ?></p>

				<div
					id="mq-appearance-preview"
					class="<?php echo esc_attr( musomo_quote_get_appearance_root_classes( $settings ) ); ?> mq-appearance-preview"
				>
					<div class="musomo-quote-action">
						<button type="button" class="musomo-quote-button" tabindex="-1"><?php echo esc_html( musomo_quote_get_text( 'quote_button_text' ) ); ?></button>
					</div>

					<div class="musomo-quote-dialog mq-appearance-preview-dialog" aria-hidden="true">
						<header class="musomo-quote-dialog__header">
							<h2 class="musomo-quote-dialog__title"><?php echo esc_html( musomo_quote_get_text( 'modal_title' ) ); ?></h2>
							<button type="button" class="musomo-quote-close" tabindex="-1" aria-label="<?php echo esc_attr( musomo_quote_get_text( 'close_aria_label' ) ); ?>">
								<svg class="musomo-quote-close__icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
									<path d="M6 6l12 12M18 6L6 18" fill="none" />
								</svg>
							</button>
						</header>
						<div class="musomo-quote-dialog__body">
							<div class="musomo-quote-product-summary">
								<img class="musomo-quote-product-summary__image" src="<?php echo esc_url( MUSOMO_QUOTE_URL . 'assets/07-icon.svg' ); ?>" alt="" />
								<div class="musomo-quote-product-summary__meta">
									<h3 class="musomo-quote-product-summary__name"><?php echo esc_html__( 'Product name', 'musomo-quote' ); ?></h3>
									<p class="musomo-quote-product-summary__sku"><?php echo esc_html( musomo_quote_get_text( 'label_sku' ) . ' ABC123' ); ?></p>
									<p class="musomo-quote-product-summary__price"><?php echo esc_html( musomo_quote_get_text( 'label_price' ) . ' €99.00' ); ?></p>
								</div>
							</div>
							<div class="musomo-quote-form">
								<p>
									<label><?php echo esc_html__( 'Name', 'musomo-quote' ); ?><br />
										<input type="text" value="" readonly tabindex="-1" />
									</label>
								</p>
								<p>
									<label><?php echo esc_html__( 'Email', 'musomo-quote' ); ?><br />
										<input type="email" value="" readonly tabindex="-1" />
									</label>
								</p>
								<p>
									<label><?php echo esc_html__( 'Message', 'musomo-quote' ); ?><br />
										<textarea readonly tabindex="-1"></textarea>
									</label>
								</p>
								<p>
									<input type="submit" class="wpcf7-submit" value="<?php echo esc_attr__( 'SEND REQUEST', 'musomo-quote' ); ?>" tabindex="-1" onclick="return false;" />
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>

<?php
include MUSOMO_QUOTE_PATH . 'admin/views/partial-footer.php';

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
