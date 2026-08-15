<?php
/**
 * Restrictions settings view.
 *
 * @package Musomo_Quote
 *
 * @var array $settings Plugin settings.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are defined in the including method scope.

$page_title = __( 'Restrictions', 'musomo-quote' );
include MUSOMO_QUOTE_PATH . 'admin/views/partial-header.php';

$r = wp_parse_args( $settings, musomo_quote_restriction_defaults() );
$s = wp_parse_args( $settings, Musomo_Quote_Security::defaults() );
$selected_cats = isset( $r['restriction_category_ids'] ) && is_array( $r['restriction_category_ids'] )
	? array_map( 'absint', $r['restriction_category_ids'] )
	: array();
$selected_types = isset( $r['restriction_product_types'] ) && is_array( $r['restriction_product_types'] )
	? $r['restriction_product_types']
	: array();

$categories = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
	)
);
if ( is_wp_error( $categories ) ) {
	$categories = array();
}

$cat_mode = isset( $r['restriction_category_mode'] ) ? $r['restriction_category_mode'] : 'all';

$cf7_active      = musomo_quote_is_cf7_active();
$turnstile_label = Musomo_Quote_Security::get_turnstile_status_label();
$patterns_value  = isset( $s['security_blocked_patterns'] ) ? (string) $s['security_blocked_patterns'] : Musomo_Quote_Security::default_patterns_string();
?>

<form method="post" action="options.php" class="mq-settings-form mq-restrictions-form" id="mq-restrictions-form">
	<?php settings_fields( 'musomo_quote_settings_group' ); ?>
	<input type="hidden" name="musomo_quote_settings[_mq_settings_screen]" value="restrictions" />

	<h2 class="mq-section-title"><?php echo esc_html__( 'Product visibility & restrictions', 'musomo-quote' ); ?></h2>

	<div class="mq-card">
		<p class="mq-admin-note" style="margin:0;">
			<?php echo esc_html__( 'Rules are combined. The button is shown only when all active restrictions are satisfied.', 'musomo-quote' ); ?>
		</p>
		<p class="description" style="margin:10px 0 0;">
			<?php echo esc_html__( 'In Replace mode, Add to cart is removed only when the Request a quote button is available for the product.', 'musomo-quote' ); ?>
		</p>
	</div>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Product categories', 'musomo-quote' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__( 'Show quote', 'musomo-quote' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_category_mode]" value="all" data-mq-cat-mode <?php checked( $cat_mode, 'all' ); ?> />
							<?php echo esc_html__( 'All categories', 'musomo-quote' ); ?>
						</label><br />
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_category_mode]" value="include" data-mq-cat-mode <?php checked( $cat_mode, 'include' ); ?> />
							<?php echo esc_html__( 'Selected categories only', 'musomo-quote' ); ?>
						</label><br />
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_category_mode]" value="exclude" data-mq-cat-mode <?php checked( $cat_mode, 'exclude' ); ?> />
							<?php echo esc_html__( 'All except selected categories', 'musomo-quote' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr class="mq-restriction-categories-row" <?php echo ( 'all' === $cat_mode ) ? 'hidden' : ''; ?>>
				<th scope="row">
					<label for="mq-restriction_category_ids"><?php echo esc_html__( 'Categories', 'musomo-quote' ); ?></label>
				</th>
				<td>
					<select
						id="mq-restriction_category_ids"
						name="musomo_quote_settings[restriction_category_ids][]"
						multiple
						size="8"
						class="mq-multi-select"
						<?php disabled( 'all' === $cat_mode ); ?>
					>
						<?php foreach ( $categories as $term ) : ?>
							<option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( in_array( (int) $term->term_id, $selected_cats, true ) ); ?>>
								<?php echo esc_html( $term->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php echo esc_html__( 'Only categories assigned to the product are matched (no automatic parent → child inheritance).', 'musomo-quote' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Stock status', 'musomo-quote' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__( 'Availability', 'musomo-quote' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_stock_mode]" value="all" <?php checked( $r['restriction_stock_mode'], 'all' ); ?> />
							<?php echo esc_html__( 'Any status', 'musomo-quote' ); ?>
						</label><br />
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_stock_mode]" value="instock" <?php checked( $r['restriction_stock_mode'], 'instock' ); ?> />
							<?php echo esc_html__( 'In-stock products only', 'musomo-quote' ); ?>
						</label><br />
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_stock_mode]" value="outofstock" <?php checked( $r['restriction_stock_mode'], 'outofstock' ); ?> />
							<?php echo esc_html__( 'Out-of-stock products only', 'musomo-quote' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
		</table>
	</div>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Price', 'musomo-quote' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__( 'Price', 'musomo-quote' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_price_mode]" value="all" <?php checked( $r['restriction_price_mode'], 'all' ); ?> />
							<?php echo esc_html__( 'Any product', 'musomo-quote' ); ?>
						</label><br />
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_price_mode]" value="no_price" <?php checked( $r['restriction_price_mode'], 'no_price' ); ?> />
							<?php echo esc_html__( 'Products without price only', 'musomo-quote' ); ?>
						</label><br />
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_price_mode]" value="with_price" <?php checked( $r['restriction_price_mode'], 'with_price' ); ?> />
							<?php echo esc_html__( 'Products with price only', 'musomo-quote' ); ?>
						</label>
					</fieldset>
					<p class="description">
						<?php echo esc_html__( 'Price 0 counts as “with price”. Only an empty price counts as “without price”.', 'musomo-quote' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Product types', 'musomo-quote' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__( 'Allowed types', 'musomo-quote' ); ?></th>
				<td>
					<label><input type="checkbox" name="musomo_quote_settings[restriction_product_types][]" value="simple" <?php checked( in_array( 'simple', $selected_types, true ) ); ?> /> <?php echo esc_html__( 'Simple', 'musomo-quote' ); ?></label><br />
					<label><input type="checkbox" name="musomo_quote_settings[restriction_product_types][]" value="variable" <?php checked( in_array( 'variable', $selected_types, true ) ); ?> /> <?php echo esc_html__( 'Variable', 'musomo-quote' ); ?></label><br />
					<label><input type="checkbox" name="musomo_quote_settings[restriction_product_types][]" value="grouped" <?php checked( in_array( 'grouped', $selected_types, true ) ); ?> /> <?php echo esc_html__( 'Grouped', 'musomo-quote' ); ?></label><br />
					<label><input type="checkbox" name="musomo_quote_settings[restriction_product_types][]" value="external" <?php checked( in_array( 'external', $selected_types, true ) ); ?> /> <?php echo esc_html__( 'External / Affiliate', 'musomo-quote' ); ?></label>
					<p class="description">
						<?php echo esc_html__( 'If no type is selected, Musomo Quote is available for all product types.', 'musomo-quote' ); ?>
					</p>
				</td>
			</tr>
		</table>
	</div>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Users', 'musomo-quote' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__( 'Users', 'musomo-quote' ); ?></th>
				<td>
					<fieldset>
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_user_mode]" value="all" <?php checked( $r['restriction_user_mode'], 'all' ); ?> />
							<?php echo esc_html__( 'All', 'musomo-quote' ); ?>
						</label><br />
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_user_mode]" value="logged_in" <?php checked( $r['restriction_user_mode'], 'logged_in' ); ?> />
							<?php echo esc_html__( 'Logged-in users only', 'musomo-quote' ); ?>
						</label><br />
						<label>
							<input type="radio" name="musomo_quote_settings[restriction_user_mode]" value="logged_out" <?php checked( $r['restriction_user_mode'], 'logged_out' ); ?> />
							<?php echo esc_html__( 'Logged-out visitors only', 'musomo-quote' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
		</table>
	</div>

	<p class="submit mq-appearance-actions">
		<?php submit_button( __( 'Save settings', 'musomo-quote' ), 'primary', 'submit', false ); ?>
		<button
			type="submit"
			class="button"
			name="musomo_quote_settings[reset_restrictions]"
			value="1"
			onclick="return confirm('<?php echo esc_js( __( 'Restore default restrictions?', 'musomo-quote' ) ); ?>');"
		>
			<?php echo esc_html__( 'Restore default restrictions', 'musomo-quote' ); ?>
		</button>
	</p>

	<hr class="mq-section-divider" />

	<h2 class="mq-section-title"><?php echo esc_html__( 'Security & antispam', 'musomo-quote' ); ?></h2>

	<div class="mq-card">
		<p class="mq-admin-note" style="margin:0;">
			<?php echo esc_html__( 'Additional protections applied only to the Contact Form 7 form linked to Musomo Quote. They do not replace Turnstile or antispam already managed by CF7.', 'musomo-quote' ); ?>
		</p>
	</div>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Security status', 'musomo-quote' ); ?></h3>
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
				<th scope="row"><?php echo esc_html__( 'Cloudflare Turnstile', 'musomo-quote' ); ?></th>
				<td><span class="mq-status mq-status--muted"><?php echo esc_html( $turnstile_label ); ?></span></td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Honeypot Musomo', 'musomo-quote' ); ?></th>
				<td>
					<span class="mq-status <?php echo ! empty( $s['security_honeypot_enabled'] ) ? 'mq-status--ok' : 'mq-status--muted'; ?>">
						<?php echo ! empty( $s['security_honeypot_enabled'] ) ? esc_html__( 'Active', 'musomo-quote' ) : esc_html__( 'Disabled', 'musomo-quote' ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Content filter', 'musomo-quote' ); ?></th>
				<td>
					<span class="mq-status <?php echo ! empty( $s['security_content_filter_enabled'] ) ? 'mq-status--ok' : 'mq-status--muted'; ?>">
						<?php echo ! empty( $s['security_content_filter_enabled'] ) ? esc_html__( 'Active', 'musomo-quote' ) : esc_html__( 'Disabled', 'musomo-quote' ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Rate protection', 'musomo-quote' ); ?></th>
				<td>
					<span class="mq-status <?php echo ! empty( $s['security_rate_limit_enabled'] ) ? 'mq-status--ok' : 'mq-status--muted'; ?>">
						<?php echo ! empty( $s['security_rate_limit_enabled'] ) ? esc_html__( 'Active', 'musomo-quote' ) : esc_html__( 'Disabled', 'musomo-quote' ); ?>
					</span>
				</td>
			</tr>
		</table>
	</div>

	<div class="mq-card mq-card--form">
		<h3 class="mq-card__title"><?php echo esc_html__( 'Musomo protections', 'musomo-quote' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__( 'Honeypot', 'musomo-quote' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="musomo_quote_settings[security_honeypot_enabled]" value="1" <?php checked( ! empty( $s['security_honeypot_enabled'] ) ); ?> />
						<?php echo esc_html__( 'Enable hidden honeypot', 'musomo-quote' ); ?>
					</label>
					<p class="description"><?php echo esc_html__( 'Hidden field for bots. If filled, the request is treated as spam by CF7.', 'musomo-quote' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Time trap', 'musomo-quote' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="musomo_quote_settings[security_time_trap_enabled]" value="1" <?php checked( ! empty( $s['security_time_trap_enabled'] ) ); ?> />
						<?php echo esc_html__( 'Enable minimum submit time check', 'musomo-quote' ); ?>
					</label>
					<p class="description"><?php echo esc_html__( 'Light anti-bot protection. Client timestamp is not strong security.', 'musomo-quote' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="mq-security_min_submit_seconds"><?php echo esc_html__( 'Minimum seconds before submit', 'musomo-quote' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="mq-security_min_submit_seconds"
						name="musomo_quote_settings[security_min_submit_seconds]"
						value="<?php echo esc_attr( (string) absint( $s['security_min_submit_seconds'] ) ); ?>"
						min="1"
						max="15"
						step="1"
						class="small-text"
					/>
					<p class="description"><?php echo esc_html__( 'Range: 1–15 seconds. Default: 3.', 'musomo-quote' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Content filter', 'musomo-quote' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="musomo_quote_settings[security_content_filter_enabled]" value="1" <?php checked( ! empty( $s['security_content_filter_enabled'] ) ); ?> />
						<?php echo esc_html__( 'Enable pattern filter on user fields', 'musomo-quote' ); ?>
					</label>
					<p class="description"><?php echo esc_html__( 'Analyzes only user-filled text fields. Excludes product URL, SKU, and Musomo hidden fields.', 'musomo-quote' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="mq-security_blocked_patterns"><?php echo esc_html__( 'Blocked patterns', 'musomo-quote' ); ?></label>
				</th>
				<td>
					<textarea
						id="mq-security_blocked_patterns"
						name="musomo_quote_settings[security_blocked_patterns]"
						rows="8"
						class="large-text code"
					><?php echo esc_textarea( $patterns_value ); ?></textarea>
					<p class="description"><?php echo esc_html__( 'One pattern per line. Case-insensitive matching. Not regex.', 'musomo-quote' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'Rate protection', 'musomo-quote' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="musomo_quote_settings[security_rate_limit_enabled]" value="1" <?php checked( ! empty( $s['security_rate_limit_enabled'] ) ); ?> />
						<?php echo esc_html__( 'Enable request limit', 'musomo-quote' ); ?>
					</label>
					<p class="description"><?php echo esc_html__( 'Counts linked form submission attempts (not modal opens). Uses temporary transients with IP hash.', 'musomo-quote' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="mq-security_rate_limit_count"><?php echo esc_html__( 'Max requests', 'musomo-quote' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="mq-security_rate_limit_count"
						name="musomo_quote_settings[security_rate_limit_count]"
						value="<?php echo esc_attr( (string) absint( $s['security_rate_limit_count'] ) ); ?>"
						min="1"
						max="50"
						step="1"
						class="small-text"
					/>
					<p class="description"><?php echo esc_html__( 'Range: 1–50. Default: 5.', 'musomo-quote' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="mq-security_rate_limit_window"><?php echo esc_html__( 'Window (minutes)', 'musomo-quote' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="mq-security_rate_limit_window"
						name="musomo_quote_settings[security_rate_limit_window]"
						value="<?php echo esc_attr( (string) absint( $s['security_rate_limit_window'] ) ); ?>"
						min="1"
						max="1440"
						step="1"
						class="small-text"
					/>
					<p class="description"><?php echo esc_html__( 'Range: 1–1440 minutes. Default: 10.', 'musomo-quote' ); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<p class="submit mq-appearance-actions">
		<?php submit_button( __( 'Save settings', 'musomo-quote' ), 'primary', 'submit', false ); ?>
		<button
			type="submit"
			class="button"
			name="musomo_quote_settings[reset_security]"
			value="1"
			onclick="return confirm('<?php echo esc_js( __( 'Restore only security settings?', 'musomo-quote' ) ); ?>');"
		>
			<?php echo esc_html__( 'Restore security', 'musomo-quote' ); ?>
		</button>
	</p>
</form>

<?php
include MUSOMO_QUOTE_PATH . 'admin/views/partial-footer.php';

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
