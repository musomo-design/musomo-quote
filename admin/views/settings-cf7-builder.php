<?php
/**
 * CF7 Form & Email template builder UI.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

$config     = Musomo_Quote_CF7_Builder::get_config();
$defs       = Musomo_Quote_CF7_Builder::field_definitions();
$form_code  = Musomo_Quote_CF7_Builder::generate_form_code( $config );
$email_html = Musomo_Quote_CF7_Builder::generate_email_html( $config );
$subject    = Musomo_Quote_CF7_Builder::generate_subject( $config );
$reply_to   = Musomo_Quote_CF7_Builder::generate_reply_to_header( $config );
$company    = Musomo_Quote_CF7_Builder::resolve_company_name( $config );
$logo_url   = MUSOMO_QUOTE_URL . 'assets/08-logo.svg';
$email_cfg  = $config['email'];

$cf7_active  = musomo_quote_is_cf7_active();
$settings    = musomo_quote_get_settings();
$cf7_form_id = isset( $settings['cf7_form_id'] ) ? absint( $settings['cf7_form_id'] ) : 0;
$form_title  = '';
if ( $cf7_form_id && class_exists( 'Musomo_Quote_CF7' ) ) {
	$form_title = Musomo_Quote_CF7::get_form_title( $cf7_form_id );
}

$antispam_label  = class_exists( 'Musomo_Quote_Security' ) ? Musomo_Quote_Security::get_antispam_status_label() : __( 'N/A', 'musomo-quote' );
$turnstile_label = class_exists( 'Musomo_Quote_Security' ) ? Musomo_Quote_Security::get_turnstile_status_label() : __( 'Not detected automatically', 'musomo-quote' );
?>

<div class="mq-cf7-builder" id="mq-cf7-builder">

	<div class="mq-card">
		<p class="mq-admin-note" style="margin:0 0 6px;">
			<?php echo esc_html__( 'Configure the quote form and email. Mu Quote generates code ready to copy into Contact Form 7.', 'musomo-quote' ); ?>
		</p>
		<p class="description" style="margin:0;">
			<?php echo esc_html__( 'Mu Quote does not automatically modify your CF7 forms.', 'musomo-quote' ); ?>
		</p>
	</div>

	<div class="mq-card mq-card--form">
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__( 'Contact Form 7', 'musomo-quote' ); ?></th>
				<td>
					<span class="mq-status <?php echo $cf7_active ? 'mq-status--ok' : 'mq-status--muted'; ?>">
						<?php echo $cf7_active ? esc_html__( 'Detected', 'musomo-quote' ) : esc_html__( 'Not detected', 'musomo-quote' ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Linked form', 'musomo-quote' ); ?></th>
				<td>
					<?php if ( $cf7_form_id && $form_title ) : ?>
						<span class="mq-status mq-status--ok"><?php echo esc_html( $form_title ); ?></span>
					<?php elseif ( $cf7_form_id ) : ?>
						<span class="mq-status mq-status--muted">
							<?php
							/* translators: %d: form ID */
							echo esc_html( sprintf( __( 'ID %d', 'musomo-quote' ), $cf7_form_id ) );
							?>
						</span>
					<?php else : ?>
						<span class="mq-status mq-status--muted"><?php echo esc_html__( 'No linked form', 'musomo-quote' ); ?></span>
						<p class="description">
							<?php echo esc_html__( 'No CF7 form is linked. You can create the template now and link the form later.', 'musomo-quote' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Musomo protections', 'musomo-quote' ); ?></th>
				<td><span class="mq-status"><?php echo esc_html( $antispam_label ); ?></span></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Cloudflare Turnstile', 'musomo-quote' ); ?></th>
				<td>
					<span class="mq-status mq-status--muted"><?php echo esc_html( $turnstile_label ); ?></span>
					<p class="description"><?php echo esc_html__( 'Configure Turnstile via Contact Form 7 or an external integration.', 'musomo-quote' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<nav class="mq-i18n-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'Builder', 'musomo-quote' ); ?>">
		<button type="button" class="mq-i18n-tab is-active" data-mq-builder-tab="form" role="tab" aria-selected="true">
			<?php echo esc_html__( 'Form', 'musomo-quote' ); ?>
		</button>
		<button type="button" class="mq-i18n-tab" data-mq-builder-tab="email" role="tab" aria-selected="false">
			<?php echo esc_html__( 'Email', 'musomo-quote' ); ?>
		</button>
	</nav>

	<form method="post" action="options.php" class="mq-settings-form" id="mq-cf7-builder-form">
		<?php settings_fields( 'musomo_quote_settings_group' ); ?>
		<input type="hidden" name="musomo_quote_settings[_mq_settings_screen]" value="cf7_builder" />

		<div class="mq-builder-panel is-active" data-mq-builder-panel="form">
			<div class="mq-builder-layout">
				<div class="mq-builder-controls">
					<div class="mq-card mq-card--form">
						<h3 class="mq-card__title"><?php echo esc_html__( 'Form fields', 'musomo-quote' ); ?></h3>
						<table class="widefat striped mq-builder-fields">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'Field', 'musomo-quote' ); ?></th>
									<th><?php echo esc_html__( 'Active', 'musomo-quote' ); ?></th>
									<th><?php echo esc_html__( 'Required', 'musomo-quote' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $defs as $key => $def ) : ?>
									<?php
									$f = isset( $config['fields'][ $key ] ) ? $config['fields'][ $key ] : array( 'enabled' => false, 'required' => false );
									?>
									<tr data-mq-field="<?php echo esc_attr( $key ); ?>">
										<td><strong><?php echo esc_html( $def['label'] ); ?></strong></td>
										<td>
											<label class="screen-reader-text" for="mq-field-<?php echo esc_attr( $key ); ?>-enabled"><?php echo esc_html( $def['label'] ); ?> — <?php echo esc_html__( 'Active', 'musomo-quote' ); ?></label>
											<input type="checkbox" class="mq-builder-enabled" id="mq-field-<?php echo esc_attr( $key ); ?>-enabled" name="musomo_quote_settings[cf7_builder][fields][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $f['enabled'] ) ); ?> data-field="<?php echo esc_attr( $key ); ?>" />
										</td>
										<td>
											<label class="screen-reader-text" for="mq-field-<?php echo esc_attr( $key ); ?>-required"><?php echo esc_html( $def['label'] ); ?> — <?php echo esc_html__( 'Required', 'musomo-quote' ); ?></label>
											<input type="checkbox" class="mq-builder-required" id="mq-field-<?php echo esc_attr( $key ); ?>-required" name="musomo_quote_settings[cf7_builder][fields][<?php echo esc_attr( $key ); ?>][required]" value="1" <?php checked( ! empty( $f['required'] ) ); ?> data-field="<?php echo esc_attr( $key ); ?>" />
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<p class="description" style="margin-top:10px;">
							<?php echo esc_html__( 'Product data (ID, name, SKU, URL, image, price, type) is added automatically as hidden fields.', 'musomo-quote' ); ?>
						</p>

						<details class="mq-builder-advanced">
							<summary><?php echo esc_html__( 'Technical details', 'musomo-quote' ); ?></summary>
							<table class="widefat striped" style="margin-top:10px;">
								<thead>
									<tr>
										<th><?php echo esc_html__( 'Field', 'musomo-quote' ); ?></th>
										<th><?php echo esc_html__( 'CF7 mail-tag', 'musomo-quote' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $defs as $def ) : ?>
										<tr>
											<td><?php echo esc_html( $def['label'] ); ?></td>
											<td><code><?php echo esc_html( $def['cf7_name'] ); ?></code></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<p class="description"><?php echo esc_html__( 'Technical names are read-only. Do not rename them to maintain compatibility with Mu Quote.', 'musomo-quote' ); ?></p>
						</details>
					</div>

					<div class="mq-card mq-card--form">
						<h3 class="mq-card__title"><?php echo esc_html__( 'Submit button', 'musomo-quote' ); ?></h3>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row">
									<label for="mq-submit_text"><?php echo esc_html__( 'Submit text', 'musomo-quote' ); ?></label>
								</th>
								<td>
									<input type="text" class="regular-text" id="mq-submit_text" name="musomo_quote_settings[cf7_builder][submit_text]" value="<?php echo esc_attr( $config['submit_text'] ); ?>" />
								</td>
							</tr>
						</table>
					</div>

					<div class="mq-card mq-card--form">
						<h3 class="mq-card__title"><?php echo esc_html__( 'Code for Contact Form 7', 'musomo-quote' ); ?></h3>
						<ol class="mq-builder-steps">
							<li><?php echo esc_html__( 'Copy the code.', 'musomo-quote' ); ?></li>
							<li><?php echo esc_html__( 'Open Contact → Contact Forms.', 'musomo-quote' ); ?></li>
							<li><?php echo esc_html__( 'Open the form used by Mu Quote.', 'musomo-quote' ); ?></li>
							<li><?php echo esc_html__( 'Paste the code in the Form tab.', 'musomo-quote' ); ?></li>
						</ol>
						<p>
							<button type="button" class="button button-primary button-hero" data-mq-copy="mq-cf7-form-code">
								<?php echo esc_html__( 'Copy form code', 'musomo-quote' ); ?>
							</button>
							<span class="mq-copy-feedback" data-mq-copy-feedback hidden><?php echo esc_html__( 'Copied!', 'musomo-quote' ); ?></span>
						</p>
						<details class="mq-builder-code-details">
							<summary><?php echo esc_html__( 'Show code', 'musomo-quote' ); ?></summary>
							<textarea id="mq-cf7-form-code" class="large-text code" rows="14" readonly><?php echo esc_textarea( $form_code ); ?></textarea>
						</details>
					</div>
				</div>

				<div class="mq-builder-preview-col">
					<div class="mq-card mq-card--form mq-builder-preview-card">
						<h3 class="mq-card__title"><?php echo esc_html__( 'Preview form', 'musomo-quote' ); ?></h3>
						<div class="mq-form-preview" id="mq-form-preview" aria-live="polite"></div>
					</div>
				</div>
			</div>
		</div>

		<div class="mq-builder-panel" data-mq-builder-panel="email" hidden>
			<div class="mq-builder-layout">
				<div class="mq-builder-controls">
					<div class="mq-card mq-card--form">
						<h3 class="mq-card__title"><?php echo esc_html__( 'Email header', 'musomo-quote' ); ?></h3>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="mq-email-header_title"><?php echo esc_html__( 'Header title', 'musomo-quote' ); ?></label></th>
								<td>
									<input type="text" class="regular-text" id="mq-email-header_title" name="musomo_quote_settings[cf7_builder][email][header_title]" value="<?php echo esc_attr( $email_cfg['header_title'] ); ?>" />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="mq-email-company_name"><?php echo esc_html__( 'Company / Website name', 'musomo-quote' ); ?></label></th>
								<td>
									<input type="text" class="regular-text" id="mq-email-company_name" name="musomo_quote_settings[cf7_builder][email][company_name]" value="<?php echo esc_attr( $email_cfg['company_name'] ); ?>" placeholder="<?php echo esc_attr( $company ); ?>" />
									<p class="description"><?php echo esc_html__( 'Empty = WordPress site name.', 'musomo-quote' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="mq-email-subject"><?php echo esc_html__( 'Email subject', 'musomo-quote' ); ?></label></th>
								<td>
									<input type="text" class="large-text" id="mq-email-subject" name="musomo_quote_settings[cf7_builder][email][subject]" value="<?php echo esc_attr( $email_cfg['subject'] ); ?>" />
								</td>
							</tr>
						</table>
					</div>

					<div class="mq-card mq-card--form">
						<h3 class="mq-card__title"><?php echo esc_html__( 'Product content', 'musomo-quote' ); ?></h3>
						<?php
						$product_toggles = array(
							'show_product_image' => __( 'Product image', 'musomo-quote' ),
							'show_product_name'  => __( 'Product name', 'musomo-quote' ),
							'show_sku'           => __( 'SKU', 'musomo-quote' ),
							'show_price'         => __( 'Price', 'musomo-quote' ),
							'show_quantity'      => __( 'Quantity', 'musomo-quote' ),
							'show_product_url'   => __( 'Product URL', 'musomo-quote' ),
						);
						?>
						<fieldset class="mq-builder-toggles">
							<?php foreach ( $product_toggles as $pkey => $plabel ) : ?>
								<label>
									<input type="checkbox" class="mq-email-toggle" name="musomo_quote_settings[cf7_builder][email][<?php echo esc_attr( $pkey ); ?>]" value="1" <?php checked( ! empty( $email_cfg[ $pkey ] ) ); ?> data-email-key="<?php echo esc_attr( $pkey ); ?>" />
									<?php echo esc_html( $plabel ); ?>
								</label>
							<?php endforeach; ?>
						</fieldset>
					</div>

					<div class="mq-card mq-card--form">
						<h3 class="mq-card__title"><?php echo esc_html__( 'Customer content', 'musomo-quote' ); ?></h3>
						<p class="description"><?php echo esc_html__( 'Shown only if the corresponding field is enabled in the Form.', 'musomo-quote' ); ?></p>
						<?php
						$customer_toggles = array(
							'show_customer_name'     => array( __( 'Customer name', 'musomo-quote' ), 'name' ),
							'show_customer_lastname' => array( __( 'Last name', 'musomo-quote' ), 'lastname' ),
							'show_company'           => array( __( 'Company', 'musomo-quote' ), 'company' ),
							'show_email'             => array( __( 'Email', 'musomo-quote' ), 'email' ),
							'show_phone'             => array( __( 'Phone', 'musomo-quote' ), 'phone' ),
							'show_message'           => array( __( 'Message', 'musomo-quote' ), 'message' ),
						);
						?>
						<fieldset class="mq-builder-toggles">
							<?php foreach ( $customer_toggles as $ckey => $cdata ) : ?>
								<label data-depends-field="<?php echo esc_attr( $cdata[1] ); ?>">
									<input type="checkbox" class="mq-email-toggle" name="musomo_quote_settings[cf7_builder][email][<?php echo esc_attr( $ckey ); ?>]" value="1" <?php checked( ! empty( $email_cfg[ $ckey ] ) ); ?> data-email-key="<?php echo esc_attr( $ckey ); ?>" />
									<?php echo esc_html( $cdata[0] ); ?>
								</label>
							<?php endforeach; ?>
						</fieldset>
					</div>

					<div class="mq-card mq-card--form">
						<h3 class="mq-card__title"><?php echo esc_html__( 'Email ready for Contact Form 7', 'musomo-quote' ); ?></h3>
						<ol class="mq-builder-steps">
							<li><?php echo esc_html__( 'Open the form in Contact Form 7.', 'musomo-quote' ); ?></li>
							<li><?php echo esc_html__( 'Go to the Mail tab.', 'musomo-quote' ); ?></li>
							<li><?php echo esc_html__( 'Enable “Use HTML content type”.', 'musomo-quote' ); ?></li>
							<li><?php echo esc_html__( 'Paste the template in the message body.', 'musomo-quote' ); ?></li>
							<li><?php echo esc_html__( 'Also copy Subject and Headers into their respective fields.', 'musomo-quote' ); ?></li>
						</ol>

						<textarea id="mq-cf7-subject-code" class="screen-reader-text" readonly aria-hidden="true"><?php echo esc_textarea( $subject ); ?></textarea>
						<textarea id="mq-cf7-email-code" class="screen-reader-text" readonly aria-hidden="true"><?php echo esc_textarea( $email_html ); ?></textarea>
						<code id="mq-cf7-reply-to" class="screen-reader-text" aria-hidden="true"><?php echo esc_html( $reply_to ); ?></code>

						<p class="mq-builder-copy-row">
							<button type="button" class="button button-primary" data-mq-copy="mq-cf7-subject-code"><?php echo esc_html__( 'Copy subject', 'musomo-quote' ); ?></button>
							<button type="button" class="button" id="mq-copy-reply-to" data-copy-text="<?php echo esc_attr( $reply_to ); ?>" <?php disabled( '' === $reply_to ); ?>><?php echo esc_html__( 'Copy headers', 'musomo-quote' ); ?></button>
							<button type="button" class="button button-primary" data-mq-copy="mq-cf7-email-code"><?php echo esc_html__( 'Copy email template', 'musomo-quote' ); ?></button>
							<span class="mq-copy-feedback" data-mq-copy-feedback hidden><?php echo esc_html__( 'Copied!', 'musomo-quote' ); ?></span>
						</p>

						<p class="description">
							<?php echo esc_html__( 'Recommended From: [_site_title] &lt;wordpress@[_site_domain]&gt; — Reply-To: [your-email]. Do not use [your-email] as From.', 'musomo-quote' ); ?>
						</p>

						<details class="mq-builder-code-details">
							<summary><?php echo esc_html__( 'Show code', 'musomo-quote' ); ?></summary>
							<p><strong><?php echo esc_html__( 'Subject', 'musomo-quote' ); ?></strong></p>
							<textarea class="large-text code mq-mirror-subject" rows="2" readonly><?php echo esc_textarea( $subject ); ?></textarea>
							<p><strong><?php echo esc_html__( 'Additional headers', 'musomo-quote' ); ?></strong></p>
							<textarea class="large-text code mq-mirror-headers" rows="2" readonly><?php echo esc_textarea( $reply_to ? $reply_to : '' ); ?></textarea>
							<p><strong><?php echo esc_html__( 'Message body (HTML)', 'musomo-quote' ); ?></strong></p>
							<textarea class="large-text code mq-mirror-email" rows="12" readonly><?php echo esc_textarea( $email_html ); ?></textarea>
						</details>
					</div>
				</div>

				<div class="mq-builder-preview-col">
					<div class="mq-card mq-card--form mq-builder-preview-card">
						<h3 class="mq-card__title"><?php echo esc_html__( 'Preview email', 'musomo-quote' ); ?></h3>
						<p class="description"><?php echo esc_html__( 'Preview with demo data. The copied template uses CF7 mail-tags.', 'musomo-quote' ); ?></p>
						<div class="mq-email-preview-wrap">
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="" class="mq-email-preview-logo" width="120" height="32" />
							<div class="mq-email-preview" id="mq-email-preview" aria-live="polite"></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<p class="submit mq-appearance-actions">
			<?php submit_button( __( 'Save CF7 Template', 'musomo-quote' ), 'primary', 'submit', false ); ?>
			<button
				type="submit"
				class="button"
				name="musomo_quote_settings[reset_cf7_builder]"
				value="1"
				onclick="return confirm('<?php echo esc_js( __( 'Restore only CF7 Template preferences?', 'musomo-quote' ) ); ?>');"
			>
				<?php echo esc_html__( 'Restore CF7 Template', 'musomo-quote' ); ?>
			</button>
		</p>
	</form>
</div>
