<?php
/**
 * Tools — diagnostics / export / import / reset.
 *
 * @package Musomo_Quote
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are defined in the including method scope.

$env     = Musomo_Quote_Tools::get_environment_info();
$cfg     = Musomo_Quote_Tools::get_configuration_status();
$checks  = Musomo_Quote_Tools::get_system_checks();
$sysinfo = Musomo_Quote_Tools::get_system_info_text();

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$preview_token = isset( $_GET['mq_import_token'] ) ? sanitize_text_field( wp_unslash( $_GET['mq_import_token'] ) ) : '';
$preview       = $preview_token ? Musomo_Quote_Tools::get_pending_preview( $preview_token ) : null;

$on_off = static function ( $on ) {
	return $on ? __( 'ON', 'musomo-quote' ) : __( 'OFF', 'musomo-quote' );
};
?>

<div class="mq-tools">

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Diagnostics', 'musomo-quote' ); ?></h3>

		<div class="mq-tools-grid">
			<div>
				<h4 class="mq-tools-subtitle"><?php echo esc_html__( 'Environment', 'musomo-quote' ); ?></h4>
				<table class="widefat striped mq-tools-table">
					<tbody>
						<tr><th><?php echo esc_html__( 'WordPress', 'musomo-quote' ); ?></th><td><?php echo esc_html( $env['wordpress']['version'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'Site language', 'musomo-quote' ); ?></th><td><?php echo esc_html( $env['wordpress']['language'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'Timezone', 'musomo-quote' ); ?></th><td><?php echo esc_html( $env['wordpress']['timezone'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'PHP', 'musomo-quote' ); ?></th><td><?php echo esc_html( $env['php']['version'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'memory_limit', 'musomo-quote' ); ?></th><td><?php echo esc_html( $env['php']['memory_limit'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'max_execution_time', 'musomo-quote' ); ?></th><td><?php echo esc_html( $env['php']['max_execution_time'] ); ?></td></tr>
						<tr><th><?php echo esc_html__( 'upload_max_filesize', 'musomo-quote' ); ?></th><td><?php echo esc_html( $env['php']['upload_max_filesize'] ); ?></td></tr>
						<tr>
							<th><?php echo esc_html__( 'WooCommerce', 'musomo-quote' ); ?></th>
							<td>
								<?php
								echo $env['woocommerce']['active']
									? esc_html( $env['woocommerce']['version'] ? $env['woocommerce']['version'] : __( 'Active', 'musomo-quote' ) )
									: esc_html__( 'Not active', 'musomo-quote' );
								?>
							</td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Contact Form 7', 'musomo-quote' ); ?></th>
							<td>
								<?php
								echo $env['cf7']['active']
									? esc_html( $env['cf7']['version'] ? $env['cf7']['version'] : __( 'Active', 'musomo-quote' ) )
									: esc_html__( 'Not active', 'musomo-quote' );
								?>
							</td>
						</tr>
						<tr>
							<th><?php echo esc_html__( 'Polylang', 'musomo-quote' ); ?></th>
							<td>
								<?php
								if ( $env['polylang']['active'] ) {
									$pll = $env['polylang']['version'] ? $env['polylang']['version'] : __( 'Active', 'musomo-quote' );
									echo esc_html( $pll );
								} else {
									echo esc_html__( 'Not active', 'musomo-quote' );
								}
								?>
							</td>
						</tr>
						<tr><th><?php echo esc_html__( 'Musomo Quote', 'musomo-quote' ); ?></th><td><?php echo esc_html( $env['musomo_quote']['version'] ); ?></td></tr>
						<tr>
							<th><?php echo esc_html__( 'Plugin enabled', 'musomo-quote' ); ?></th>
							<td><?php echo $env['musomo_quote']['enabled'] ? esc_html__( 'ON', 'musomo-quote' ) : esc_html__( 'OFF', 'musomo-quote' ); ?></td>
						</tr>
						<tr><th><?php echo esc_html__( 'Mode', 'musomo-quote' ); ?></th><td><?php echo esc_html( $env['musomo_quote']['mode'] ); ?></td></tr>
					</tbody>
				</table>
			</div>

			<div>
				<h4 class="mq-tools-subtitle"><?php echo esc_html__( 'System check', 'musomo-quote' ); ?></h4>
				<ul class="mq-tools-checks">
					<?php foreach ( $checks as $check ) : ?>
						<li class="mq-tools-check mq-tools-check--<?php echo esc_attr( $check['status'] ); ?>">
							<span class="mq-tools-check__badge"><?php echo esc_html( strtoupper( $check['status'] ) ); ?></span>
							<span><?php echo esc_html( $check['message'] ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="mq-tools-copy">
					<textarea id="mq-system-info" class="large-text code" rows="8" readonly><?php echo esc_textarea( $sysinfo ); ?></textarea>
					<p>
						<button type="button" class="button" id="mq-copy-system-info">
							<?php echo esc_html__( 'Copy system information', 'musomo-quote' ); ?>
						</button>
						<span class="mq-copy-feedback" id="mq-copy-feedback" hidden><?php echo esc_html__( 'Copied!', 'musomo-quote' ); ?></span>
					</p>
				</div>
			</div>
		</div>
	</div>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Musomo Quote configuration', 'musomo-quote' ); ?></h3>
		<table class="widefat striped mq-tools-table">
			<tbody>
				<tr>
					<th><?php echo esc_html__( 'Plugin frontend', 'musomo-quote' ); ?></th>
					<td><?php echo $cfg['frontend'] ? esc_html__( 'Active', 'musomo-quote' ) : esc_html__( 'Inactive', 'musomo-quote' ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'WooCommerce', 'musomo-quote' ); ?></th>
					<td><?php echo $cfg['woocommerce'] ? esc_html__( 'Available', 'musomo-quote' ) : esc_html__( 'Not available', 'musomo-quote' ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'CF7', 'musomo-quote' ); ?></th>
					<td><?php echo $cfg['cf7'] ? esc_html__( 'Available', 'musomo-quote' ) : esc_html__( 'Not available', 'musomo-quote' ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Linked global form', 'musomo-quote' ); ?></th>
					<td><?php echo esc_html( $cfg['cf7_form_label'] ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Appearance', 'musomo-quote' ); ?></th>
					<td><?php echo esc_html( $cfg['appearance'] ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Restrictions', 'musomo-quote' ); ?></th>
					<td><?php echo $cfg['restrictions'] ? esc_html__( 'Active', 'musomo-quote' ) : esc_html__( 'None', 'musomo-quote' ); ?></td>
				</tr>
				<tr>
					<th><?php echo esc_html__( 'Security', 'musomo-quote' ); ?></th>
					<td><?php echo $cfg['security'] ? esc_html__( 'Active', 'musomo-quote' ) : esc_html__( 'Disabled', 'musomo-quote' ); ?></td>
				</tr>
				<tr><th><?php echo esc_html__( 'Honeypot', 'musomo-quote' ); ?></th><td><?php echo esc_html( $on_off( $cfg['honeypot'] ) ); ?></td></tr>
				<tr><th><?php echo esc_html__( 'Time trap', 'musomo-quote' ); ?></th><td><?php echo esc_html( $on_off( $cfg['time_trap'] ) ); ?></td></tr>
				<tr><th><?php echo esc_html__( 'Content filter', 'musomo-quote' ); ?></th><td><?php echo esc_html( $on_off( $cfg['content_filter'] ) ); ?></td></tr>
				<tr><th><?php echo esc_html__( 'Rate protection', 'musomo-quote' ); ?></th><td><?php echo esc_html( $on_off( $cfg['rate_protection'] ) ); ?></td></tr>
				<tr>
					<th><?php echo esc_html__( 'Multilingual', 'musomo-quote' ); ?></th>
					<td>
						<?php
						if ( $cfg['polylang'] ) {
							/* translators: %d: language count */
							echo esc_html( sprintf( __( 'Polylang active — %d languages', 'musomo-quote' ), (int) $cfg['polylang_languages'] ) );
						} else {
							echo esc_html__( 'Polylang not active', 'musomo-quote' );
						}
						?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Export / Import', 'musomo-quote' ); ?></h3>

		<div class="mq-tools-export-import">
			<div class="mq-tools-block">
				<h4 class="mq-tools-subtitle"><?php echo esc_html__( 'Export configuration', 'musomo-quote' ); ?></h4>
				<p class="description">
					<?php echo esc_html__( 'Download a JSON file with Musomo Quote settings. CF7 and category IDs may not match on another site.', 'musomo-quote' ); ?>
				</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="musomo_quote_export_settings" />
					<?php wp_nonce_field( 'musomo_quote_export_settings' ); ?>
					<?php submit_button( __( 'Export configuration', 'musomo-quote' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<div class="mq-tools-block">
				<h4 class="mq-tools-subtitle"><?php echo esc_html__( 'Import configuration', 'musomo-quote' ); ?></h4>
				<p class="description">
					<?php echo esc_html__( 'Upload an exported JSON. Valid values are merged into current settings after sanitization. File limit: 1 MB.', 'musomo-quote' ); ?>
				</p>

				<?php if ( is_array( $preview ) ) : ?>
					<div class="mq-import-preview">
						<h4><?php echo esc_html__( 'Preview import', 'musomo-quote' ); ?></h4>
						<ul>
							<li>
								<strong><?php echo esc_html__( 'File:', 'musomo-quote' ); ?></strong>
								<?php echo esc_html( isset( $preview['filename'] ) ? $preview['filename'] : '' ); ?>
							</li>
							<li>
								<strong><?php echo esc_html__( 'Export version:', 'musomo-quote' ); ?></strong>
								<?php echo esc_html( isset( $preview['version'] ) ? (string) $preview['version'] : '' ); ?>
							</li>
							<li>
								<strong><?php echo esc_html__( 'Settings detected:', 'musomo-quote' ); ?></strong>
								<?php echo esc_html( ! empty( $preview['groups'] ) ? implode( ', ', $preview['groups'] ) : '—' ); ?>
							</li>
						</ul>
						<?php if ( ! empty( $preview['warnings'] ) ) : ?>
							<div class="notice notice-warning inline">
								<p><strong><?php echo esc_html__( 'Warnings:', 'musomo-quote' ); ?></strong></p>
								<ul>
									<?php foreach ( $preview['warnings'] as $w ) : ?>
										<li><?php echo esc_html( $w ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px;">
							<input type="hidden" name="action" value="musomo_quote_import_confirm" />
							<input type="hidden" name="mq_import_token" value="<?php echo esc_attr( $preview['token'] ); ?>" />
							<?php wp_nonce_field( 'musomo_quote_import_confirm' ); ?>
							<?php submit_button( __( 'Confirm import', 'musomo-quote' ), 'primary', 'submit', false ); ?>
						</form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
							<input type="hidden" name="action" value="musomo_quote_import_cancel" />
							<?php wp_nonce_field( 'musomo_quote_import_cancel' ); ?>
							<?php submit_button( __( 'Cancel', 'musomo-quote' ), 'secondary', 'submit', false ); ?>
						</form>
					</div>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="musomo_quote_import_preview" />
						<?php wp_nonce_field( 'musomo_quote_import_preview' ); ?>
						<p>
							<input type="file" name="mq_import_file" accept=".json,application/json" required />
						</p>
						<?php submit_button( __( 'Analyze and preview', 'musomo-quote' ), 'secondary', 'submit', false ); ?>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="mq-card mq-card--form mq-card--danger">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Reset plugin', 'musomo-quote' ); ?></h3>
		<p class="description">
			<?php echo esc_html__( 'Restore all Musomo Quote settings to defaults. Does not delete products, orders, CF7 forms, or other plugin settings.', 'musomo-quote' ); ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mq-reset-form" id="mq-reset-form">
			<input type="hidden" name="action" value="musomo_quote_reset_settings" />
			<?php wp_nonce_field( 'musomo_quote_reset_settings' ); ?>
			<p>
				<label for="mq-reset-confirm">
					<?php echo esc_html__( 'Type RESET to confirm:', 'musomo-quote' ); ?>
				</label>
				<input type="text" id="mq-reset-confirm" name="mq_reset_confirm" value="" autocomplete="off" class="regular-text" />
			</p>
			<?php
			submit_button(
				__( 'Restore all settings', 'musomo-quote' ),
				'secondary',
				'submit',
				false,
				array(
					'id' => 'mq-reset-submit',
				)
			);
			?>
		</form>
	</div>

</div>

<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
