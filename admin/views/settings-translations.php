<?php
/**
 * Texts & translations settings view.
 *
 * @package Musomo_Quote
 *
 * @var array $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;

$page_title = __( 'Texts & translations', 'musomo-quote' );
include MUSOMO_QUOTE_PATH . 'admin/views/partial-header.php';

$t            = wp_parse_args( $settings, musomo_quote_text_defaults() );
$pll_active   = Musomo_Quote_I18n::is_polylang_active();
$languages    = $pll_active ? Musomo_Quote_I18n::get_languages() : array();
$translations = Musomo_Quote_I18n::get_stored_translations( $settings );
$cf7_active   = musomo_quote_is_cf7_active();
$cf7_forms    = $cf7_active && class_exists( 'Musomo_Quote_CF7' ) ? Musomo_Quote_CF7::get_forms() : array();

/**
 * Render global or language text fields.
 *
 * @param array  $values   Field values.
 * @param string $name_prefix Name attribute prefix (e.g. musomo_quote_settings or musomo_quote_settings[translations][it]).
 * @param string $id_prefix   HTML id prefix.
 * @param bool   $is_lang     Language panel (allows empty, adds CF7 select).
 */
$mq_render_text_fields = static function ( $values, $name_prefix, $id_prefix, $is_lang = false ) use ( $cf7_active, $cf7_forms ) {
	$fields = array(
		'quote_button_text'        => array(
			'label' => __( 'Button text', 'musomo-quote' ),
			'type'  => 'text',
		),
		'modal_title'              => array(
			'label' => __( 'Modal title', 'musomo-quote' ),
			'type'  => 'text',
		),
		'close_aria_label'         => array(
			'label'       => __( 'Accessible close label', 'musomo-quote' ),
			'type'        => 'text',
			'description' => __( 'Used as the aria-label on the modal close button.', 'musomo-quote' ),
		),
		'label_sku'                => array(
			'label' => __( 'SKU label', 'musomo-quote' ),
			'type'  => 'text',
		),
		'label_price'              => array(
			'label' => __( 'Price label', 'musomo-quote' ),
			'type'  => 'text',
		),
		'label_quantity'           => array(
			'label'       => __( 'Quantity label', 'musomo-quote' ),
			'type'        => 'text',
			'description' => __( 'Available for future use; quantity is already sent to CF7 hidden fields.', 'musomo-quote' ),
		),
		'form_not_configured_text' => array(
			'label' => __( 'Form not configured', 'musomo-quote' ),
			'type'  => 'textarea',
		),
		'cf7_not_available_text'   => array(
			'label' => __( 'CF7 not available', 'musomo-quote' ),
			'type'  => 'textarea',
		),
	);
	?>
	<table class="form-table" role="presentation">
		<?php foreach ( $fields as $key => $meta ) : ?>
			<?php
			$id    = $id_prefix . '-' . $key;
			$name  = $name_prefix . '[' . $key . ']';
			$value = isset( $values[ $key ] ) ? (string) $values[ $key ] : '';
			?>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $meta['label'] ); ?></label>
				</th>
				<td>
					<?php if ( 'textarea' === $meta['type'] ) : ?>
						<textarea
							class="large-text mq-text-input"
							rows="2"
							id="<?php echo esc_attr( $id ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							<?php echo $is_lang ? 'placeholder="' . esc_attr__( 'Leave empty to use global text', 'musomo-quote' ) . '"' : ''; ?>
						><?php echo esc_textarea( $value ); ?></textarea>
					<?php else : ?>
						<input
							type="text"
							class="regular-text mq-text-input"
							id="<?php echo esc_attr( $id ); ?>"
							name="<?php echo esc_attr( $name ); ?>"
							value="<?php echo esc_attr( $value ); ?>"
							<?php echo $is_lang ? 'placeholder="' . esc_attr__( 'Leave empty to use global text', 'musomo-quote' ) . '"' : ''; ?>
						/>
					<?php endif; ?>
					<?php if ( ! empty( $meta['description'] ) ) : ?>
						<p class="description"><?php echo esc_html( $meta['description'] ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>

		<?php if ( $is_lang ) : ?>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $id_prefix . '-cf7_form_id' ); ?>">
						<?php echo esc_html__( 'Contact Form 7 form for this language', 'musomo-quote' ); ?>
					</label>
				</th>
				<td>
					<?php if ( ! $cf7_active ) : ?>
						<p class="description">
							<?php echo esc_html__( 'Contact Form 7 is not available.', 'musomo-quote' ); ?>
						</p>
						<input
							type="hidden"
							name="<?php echo esc_attr( $name_prefix . '[cf7_form_id]' ); ?>"
							value="<?php echo esc_attr( (string) absint( isset( $values['cf7_form_id'] ) ? $values['cf7_form_id'] : 0 ) ); ?>"
						/>
					<?php else : ?>
						<select
							id="<?php echo esc_attr( $id_prefix . '-cf7_form_id' ); ?>"
							name="<?php echo esc_attr( $name_prefix . '[cf7_form_id]' ); ?>"
						>
							<option value="0"><?php echo esc_html__( 'No override / use global', 'musomo-quote' ); ?></option>
							<?php
							$selected_cf7 = isset( $values['cf7_form_id'] ) ? absint( $values['cf7_form_id'] ) : 0;
							foreach ( $cf7_forms as $fid => $ftitle ) :
								?>
								<option value="<?php echo esc_attr( (string) $fid ); ?>" <?php selected( $selected_cf7, (int) $fid ); ?>>
									<?php echo esc_html( $ftitle ); ?>
								</option>
							<?php endforeach; ?>
							<?php if ( $selected_cf7 && ! isset( $cf7_forms[ $selected_cf7 ] ) ) : ?>
								<option value="<?php echo esc_attr( (string) $selected_cf7 ); ?>" selected>
									<?php
									/* translators: %d: CF7 form ID */
									echo esc_html( sprintf( __( 'ID %d (not found)', 'musomo-quote' ), $selected_cf7 ) );
									?>
								</option>
							<?php endif; ?>
						</select>
					<?php endif; ?>
				</td>
			</tr>
		<?php endif; ?>
	</table>
	<?php
};
?>

<form method="post" action="options.php" class="mq-settings-form mq-texts-form" id="mq-texts-form">
	<?php settings_fields( 'musomo_quote_settings_group' ); ?>
	<input type="hidden" name="musomo_quote_settings[_mq_settings_screen]" value="texts" />

	<?php if ( ! $pll_active ) : ?>
		<div class="mq-card">
			<p class="mq-admin-note" style="margin:0;">
				<?php echo esc_html__( 'Install/activate Polylang to manage different texts per language.', 'musomo-quote' ); ?>
			</p>
		</div>

		<div class="mq-card mq-card--form">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Button texts', 'musomo-quote' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="mq-quote_button_text"><?php echo esc_html__( 'Button label', 'musomo-quote' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							class="regular-text mq-text-input"
							id="mq-quote_button_text"
							name="musomo_quote_settings[quote_button_text]"
							value="<?php echo esc_attr( $t['quote_button_text'] ); ?>"
						/>
					</td>
				</tr>
			</table>
		</div>

		<div class="mq-card mq-card--form">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Modal', 'musomo-quote' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="mq-modal_title"><?php echo esc_html__( 'Modal title', 'musomo-quote' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text mq-text-input" id="mq-modal_title" name="musomo_quote_settings[modal_title]" value="<?php echo esc_attr( $t['modal_title'] ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="mq-close_aria_label"><?php echo esc_html__( 'Accessible close label', 'musomo-quote' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text mq-text-input" id="mq-close_aria_label" name="musomo_quote_settings[close_aria_label]" value="<?php echo esc_attr( $t['close_aria_label'] ); ?>" />
						<p class="description"><?php echo esc_html__( 'Used as the aria-label on the modal close button.', 'musomo-quote' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="mq-card mq-card--form">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Product summary', 'musomo-quote' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mq-label_sku"><?php echo esc_html__( 'SKU label', 'musomo-quote' ); ?></label></th>
					<td><input type="text" class="regular-text mq-text-input" id="mq-label_sku" name="musomo_quote_settings[label_sku]" value="<?php echo esc_attr( $t['label_sku'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="mq-label_price"><?php echo esc_html__( 'Price label', 'musomo-quote' ); ?></label></th>
					<td><input type="text" class="regular-text mq-text-input" id="mq-label_price" name="musomo_quote_settings[label_price]" value="<?php echo esc_attr( $t['label_price'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="mq-label_quantity"><?php echo esc_html__( 'Quantity label', 'musomo-quote' ); ?></label></th>
					<td>
						<input type="text" class="regular-text mq-text-input" id="mq-label_quantity" name="musomo_quote_settings[label_quantity]" value="<?php echo esc_attr( $t['label_quantity'] ); ?>" />
						<p class="description"><?php echo esc_html__( 'Available for future use; quantity is already sent to CF7 hidden fields.', 'musomo-quote' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="mq-card mq-card--form">
			<h3 class="mq-card__title"><?php echo esc_html__( 'System messages', 'musomo-quote' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="mq-form_not_configured_text"><?php echo esc_html__( 'Form not configured', 'musomo-quote' ); ?></label></th>
					<td><textarea class="large-text mq-text-input" rows="2" id="mq-form_not_configured_text" name="musomo_quote_settings[form_not_configured_text]"><?php echo esc_textarea( $t['form_not_configured_text'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="mq-cf7_not_available_text"><?php echo esc_html__( 'CF7 not available', 'musomo-quote' ); ?></label></th>
					<td><textarea class="large-text mq-text-input" rows="2" id="mq-cf7_not_available_text" name="musomo_quote_settings[cf7_not_available_text]"><?php echo esc_textarea( $t['cf7_not_available_text'] ); ?></textarea></td>
				</tr>
			</table>
		</div>

		<div class="mq-card mq-card--form">
			<h3 class="mq-card__title"><?php echo esc_html__( 'Contact Form 7', 'musomo-quote' ); ?></h3>
			<p>
				<?php echo esc_html__( 'Submit button text and validation/success messages are managed directly by the Contact Form 7 form.', 'musomo-quote' ); ?>
			</p>
		</div>

		<p class="submit mq-appearance-actions">
			<?php submit_button( __( 'Save texts', 'musomo-quote' ), 'primary', 'submit', false ); ?>
			<button
				type="submit"
				class="button"
				name="musomo_quote_settings[reset_texts]"
				value="1"
				onclick="return confirm('<?php echo esc_js( __( 'Restore default texts?', 'musomo-quote' ) ); ?>');"
			>
				<?php echo esc_html__( 'Restore default texts', 'musomo-quote' ); ?>
			</button>
		</p>
	<?php else : ?>
		<div class="mq-card">
			<p class="mq-admin-note" style="margin:0;">
				<?php echo esc_html__( 'Global texts are the fallback. For each language you can override texts and assign a dedicated CF7 form. Leave a language field empty to use the global value.', 'musomo-quote' ); ?>
			</p>
		</div>

		<nav class="mq-i18n-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'Languages', 'musomo-quote' ); ?>">
			<button type="button" class="mq-i18n-tab is-active" role="tab" aria-selected="true" data-mq-i18n-tab="global">
				<?php echo esc_html__( 'Global', 'musomo-quote' ); ?>
			</button>
			<?php foreach ( $languages as $slug => $lang ) : ?>
				<button type="button" class="mq-i18n-tab" role="tab" aria-selected="false" data-mq-i18n-tab="<?php echo esc_attr( $slug ); ?>">
					<?php
					if ( ! empty( $lang['flag'] ) ) {
						echo wp_kses_post( $lang['flag'] ) . ' ';
					}
					echo esc_html( isset( $lang['name'] ) ? $lang['name'] : $slug );
					?>
				</button>
			<?php endforeach; ?>
		</nav>

		<div class="mq-i18n-panels">
			<div class="mq-i18n-panel is-active" data-mq-i18n-panel="global" role="tabpanel">
				<div class="mq-card mq-card--form">
					<h3 class="mq-card__title"><?php echo esc_html__( 'Default language / Global', 'musomo-quote' ); ?></h3>
					<?php
					$mq_render_text_fields(
						$t,
						'musomo_quote_settings',
						'mq-global',
						false
					);
					?>
					<p class="description" style="margin-top:12px;">
						<?php echo esc_html__( 'The global CF7 form is configured in Mu Quote → Settings (fallback).', 'musomo-quote' ); ?>
					</p>
				</div>
			</div>

			<?php foreach ( $languages as $slug => $lang ) : ?>
				<?php
				$row = isset( $translations[ $slug ] )
					? $translations[ $slug ]
					: Musomo_Quote_I18n::empty_translation_row();
				?>
				<div class="mq-i18n-panel" data-mq-i18n-panel="<?php echo esc_attr( $slug ); ?>" role="tabpanel" hidden>
					<div class="mq-card mq-card--form">
						<h3 class="mq-card__title">
							<?php echo esc_html( isset( $lang['name'] ) ? $lang['name'] : $slug ); ?>
						</h3>
						<?php
						$mq_render_text_fields(
							$row,
							'musomo_quote_settings[translations][' . $slug . ']',
							'mq-lang-' . $slug,
							true
						);
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<p class="submit mq-appearance-actions">
			<?php submit_button( __( 'Save texts', 'musomo-quote' ), 'primary', 'submit', false ); ?>
			<button
				type="submit"
				class="button"
				name="musomo_quote_settings[reset_texts]"
				value="1"
				onclick="return confirm('<?php echo esc_js( __( 'Restore only default global texts? Language overrides will not be cleared.', 'musomo-quote' ) ); ?>');"
			>
				<?php echo esc_html__( 'Restore texts', 'musomo-quote' ); ?>
			</button>
			<button
				type="submit"
				class="button"
				name="musomo_quote_settings[reset_translations]"
				value="1"
				onclick="return confirm('<?php echo esc_js( __( 'Clear all multilingual overrides (texts and forms per language)? Global texts will remain unchanged.', 'musomo-quote' ) ); ?>');"
			>
				<?php echo esc_html__( 'Restore multilingual overrides', 'musomo-quote' ); ?>
			</button>
		</p>
	<?php endif; ?>
</form>

<?php
include MUSOMO_QUOTE_PATH . 'admin/views/partial-footer.php';
