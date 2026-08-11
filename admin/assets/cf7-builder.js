/**
 * Musomo Quote — CF7 Form / Email template builder (admin only).
 */
(function () {
	'use strict';

	var root = document.getElementById('mq-cf7-builder');
	if (!root || typeof musomoQuoteCf7Builder === 'undefined') {
		return;
	}

	var data = musomoQuoteCf7Builder;
	var fields = data.fields || {};
	var productHidden = data.productHidden || [];
	var demo = data.demo || {};
	var defaultSubmit = data.defaultSubmit || 'Send request';

	function esc(str) {
		return String(str == null ? '' : str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function readConfig() {
		var config = {
			fields: {},
			submit_text: defaultSubmit,
			email: {}
		};

		Object.keys(fields).forEach(function (key) {
			var enabled = root.querySelector('.mq-builder-enabled[data-field="' + key + '"]');
			var required = root.querySelector('.mq-builder-required[data-field="' + key + '"]');
			config.fields[key] = {
				enabled: !!(enabled && enabled.checked),
				required: !!(required && required.checked)
			};
		});

		var submit = document.getElementById('mq-submit_text');
		config.submit_text = submit && submit.value ? submit.value.trim() : defaultSubmit;
		if (!config.submit_text) {
			config.submit_text = defaultSubmit;
		}

		var emailKeys = [
			'header_title',
			'company_name',
			'subject',
			'show_product_image',
			'show_product_name',
			'show_sku',
			'show_price',
			'show_quantity',
			'show_product_url',
			'show_customer_name',
			'show_customer_lastname',
			'show_company',
			'show_email',
			'show_phone',
			'show_message'
		];

		emailKeys.forEach(function (key) {
			if (key === 'header_title' || key === 'company_name' || key === 'subject') {
				var input = document.getElementById(
					key === 'header_title'
						? 'mq-email-header_title'
						: key === 'company_name'
							? 'mq-email-company_name'
							: 'mq-email-subject'
				);
				config.email[key] = input ? String(input.value || '') : '';
			} else {
				var toggle = root.querySelector('.mq-email-toggle[data-email-key="' + key + '"]');
				config.email[key] = !!(toggle && toggle.checked);
			}
		});

		if (!config.email.header_title) {
			config.email.header_title = 'Quote request';
		}
		if (!config.email.subject) {
			config.email.subject = 'New quote request — [musomo_product_name]';
		}

		return config;
	}

	function cf7Tag(type, name, required) {
		var req = required ? '*' : '';
		if (type === 'email') {
			return '[email' + req + ' ' + name + ']';
		}
		if (type === 'tel') {
			return '[tel' + req + ' ' + name + ']';
		}
		if (type === 'number') {
			return '[number' + req + ' ' + name + ' min:1]';
		}
		if (type === 'textarea') {
			return '[textarea' + req + ' ' + name + ']';
		}
		return '[text' + req + ' ' + name + ']';
	}

	function generateFormCode(config) {
		var lines = [];
		var quantityVisible = !!(config.fields.quantity && config.fields.quantity.enabled);

		Object.keys(fields).forEach(function (key) {
			var def = fields[key];
			var field = config.fields[key] || {};
			if (!field.enabled) {
				return;
			}
			var star = field.required ? ' *' : '';
			lines.push('<label>');
			lines.push(def.label + star);
			lines.push(cf7Tag(def.type, def.cf7_name, !!field.required));
			lines.push('</label>');
			lines.push('');
		});

		productHidden.forEach(function (hidden) {
			if (hidden === 'musomo_quantity' && quantityVisible) {
				return;
			}
			lines.push('[hidden ' + hidden + ']');
		});

		lines.push('');
		var submit = String(config.submit_text || defaultSubmit).replace(/\\/g, '').replace(/"/g, "'");
		lines.push('[submit "' + submit + '"]');
		return lines.join('\n').replace(/\n+$/, '') + '\n';
	}

	function companyName(config) {
		var name = (config.email.company_name || '').trim();
		return name || data.siteName || 'Website';
	}

	function generateSubject(config) {
		return config.email.subject || 'New quote request — [musomo_product_name]';
	}

	function generateReplyTo(config) {
		if (config.fields.email && config.fields.email.enabled) {
			return 'Reply-To: [your-email]';
		}
		return '';
	}

	function generateEmailHtml(config) {
		var email = config.email;
		var f = config.fields;
		var company = esc(companyName(config));
		var title = esc(email.header_title || 'Quote request');

		var productRows = '';
		if (email.show_product_image) {
			productRows +=
				'<tr><td style="padding:0 0 16px 0;" align="center">' +
				'<img src="[musomo_product_image]" alt="[musomo_product_name]" width="240" style="display:block;max-width:240px;height:auto;border:0;outline:none;text-decoration:none;" />' +
				'</td></tr>';
		}
		if (email.show_product_name) {
			productRows +=
				'<tr><td style="padding:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:bold;color:#111;">[musomo_product_name]</td></tr>';
		}

		var meta = [];
		if (email.show_sku) {
			meta.push('SKU: [musomo_product_sku]');
		}
		if (email.show_price) {
			meta.push('Price: [musomo_product_price]');
		}
		if (email.show_quantity) {
			meta.push('Quantity: [musomo_quantity]');
		}
		if (meta.length) {
			productRows +=
				'<tr><td style="padding:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#444;line-height:1.6;">' +
				meta.join('<br />') +
				'</td></tr>';
		}

		var customerRows = '';
		var customerLines = [];
		if (email.show_customer_name && f.name && f.name.enabled) {
			customerLines.push(['Name', '[your-name]']);
		}
		if (email.show_customer_lastname && f.lastname && f.lastname.enabled) {
			customerLines.push(['Last name', '[your-lastname]']);
		}
		if (email.show_company && f.company && f.company.enabled) {
			customerLines.push(['Company', '[your-company]']);
		}
		if (email.show_email && f.email && f.email.enabled) {
			customerLines.push(['Email', '[your-email]']);
		}
		if (email.show_phone && f.phone && f.phone.enabled) {
			customerLines.push(['Phone', '[your-phone]']);
		}
		customerLines.forEach(function (line) {
			customerRows +=
				'<tr><td style="padding:0 0 10px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#444;">' +
				'<strong style="color:#111;">' +
				esc(line[0]) +
				':</strong><br />' +
				line[1] +
				'</td></tr>';
		});

		var messageBlock = '';
		if (email.show_message && f.message && f.message.enabled) {
			messageBlock =
				'<tr><td style="padding:20px 24px;border-top:1px solid #e5e5e5;">' +
				'<p style="margin:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#888;">Message</p>' +
				'<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#333;line-height:1.6;">[your-message]</p>' +
				'</td></tr>';
		}

		var urlBlock = '';
		if (email.show_product_url) {
			urlBlock =
				'<tr><td style="padding:20px 24px;border-top:1px solid #e5e5e5;">' +
				'<a href="[musomo_product_url]" style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#014b43;text-decoration:underline;">View product</a>' +
				'</td></tr>';
		}

		var productSection = productRows
			? '<tr><td style="padding:20px 24px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' +
				productRows +
				'</table></td></tr>'
			: '';

		var customerSection = customerRows
			? '<tr><td style="padding:20px 24px;border-top:1px solid #e5e5e5;">' +
				'<p style="margin:0 0 12px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#888;">Customer</p>' +
				'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' +
				customerRows +
				'</table></td></tr>'
			: '';

		return (
			'<!DOCTYPE html>\n<html><body style="margin:0;padding:0;background-color:#f4f4f4;">\n' +
			'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f4;padding:24px 0;">\n' +
			'<tr><td align="center">\n' +
			'<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background-color:#ffffff;border:1px solid #e5e5e5;">\n' +
			'<tr><td style="padding:20px 24px;background-color:#111111;">' +
			'<p style="margin:0 0 4px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#bbbbbb;">' +
			company +
			'</p>' +
			'<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:bold;color:#ffffff;">' +
			title +
			'</p></td></tr>\n' +
			productSection +
			'\n' +
			customerSection +
			'\n' +
			messageBlock +
			'\n' +
			urlBlock +
			'\n' +
			'</table>\n</td></tr></table>\n</body></html>'
		);
	}

	function renderFormPreview(config) {
		var box = document.getElementById('mq-form-preview');
		if (!box) {
			return;
		}
		var html = '';
		Object.keys(fields).forEach(function (key) {
			var def = fields[key];
			var field = config.fields[key] || {};
			if (!field.enabled) {
				return;
			}
			var star = field.required ? ' *' : '';
			html += '<div class="mq-form-preview__row">';
			html += '<label class="mq-form-preview__label">' + esc(def.label + star) + '</label>';
			if (def.type === 'textarea') {
				html += '<div class="mq-form-preview__textarea"></div>';
			} else if (def.type === 'number') {
				html += '<div class="mq-form-preview__input mq-form-preview__input--sm"></div>';
			} else {
				html += '<div class="mq-form-preview__input"></div>';
			}
			html += '</div>';
		});
		html +=
			'<div class="mq-form-preview__submit">' +
			esc(config.submit_text || defaultSubmit) +
			'</div>';
		box.innerHTML = html;
	}

	function renderEmailPreview(config) {
		var box = document.getElementById('mq-email-preview');
		if (!box) {
			return;
		}
		var email = config.email;
		var f = config.fields;
		var html = '';

		html += '<div class="mq-ep__header">';
		html += '<div class="mq-ep__site">' + esc(companyName(config)) + '</div>';
		html += '<div class="mq-ep__title">' + esc(email.header_title || 'Quote request') + '</div>';
		html += '</div>';

		html += '<div class="mq-ep__body">';
		if (email.show_product_image) {
			html +=
				'<div class="mq-ep__image">' +
				(demo.image
					? '<img src="' + esc(demo.image) + '" alt="' + esc(demo.product_name || '') + '" width="120" style="display:block;max-width:120px;height:auto;" />'
					: '<div class="mq-ep__image-ph"></div>') +
				'</div>';
		}
		if (email.show_product_name) {
			html += '<div class="mq-ep__pname">' + esc(demo.product_name || '') + '</div>';
		}
		var meta = [];
		if (email.show_sku) {
			meta.push('SKU: ' + (demo.sku || ''));
		}
		if (email.show_price) {
			meta.push('Price: ' + (demo.price || ''));
		}
		if (email.show_quantity) {
			meta.push('Quantity: ' + (demo.quantity || ''));
		}
		if (meta.length) {
			html += '<div class="mq-ep__meta">' + esc(meta.join(' · ')) + '</div>';
		}

		var customerBits = [];
		if (email.show_customer_name && f.name && f.name.enabled) {
			customerBits.push(['Name', demo.customer_name]);
		}
		if (email.show_customer_lastname && f.lastname && f.lastname.enabled) {
			customerBits.push(['Last name', demo.lastname]);
		}
		if (email.show_company && f.company && f.company.enabled) {
			customerBits.push(['Company', demo.company]);
		}
		if (email.show_email && f.email && f.email.enabled) {
			customerBits.push(['Email', demo.email]);
		}
		if (email.show_phone && f.phone && f.phone.enabled) {
			customerBits.push(['Phone', demo.phone]);
		}
		if (customerBits.length) {
			html += '<div class="mq-ep__section"><div class="mq-ep__h">Customer</div>';
			customerBits.forEach(function (row) {
				html +=
					'<div class="mq-ep__row"><strong>' +
					esc(row[0]) +
					':</strong> ' +
					esc(row[1] || '') +
					'</div>';
			});
			html += '</div>';
		}

		if (email.show_message && f.message && f.message.enabled) {
			html +=
				'<div class="mq-ep__section"><div class="mq-ep__h">Message</div><div class="mq-ep__msg">' +
				esc(demo.message || '') +
				'</div></div>';
		}

		if (email.show_product_url) {
			html += '<div class="mq-ep__link">View product →</div>';
		}

		html += '</div>';
		box.innerHTML = html;
	}

	function syncDependentEmailToggles(config) {
		root.querySelectorAll('[data-depends-field]').forEach(function (label) {
			var key = label.getAttribute('data-depends-field');
			var enabled = !!(config.fields[key] && config.fields[key].enabled);
			label.style.opacity = enabled ? '1' : '0.45';
			var input = label.querySelector('input');
			if (input) {
				input.disabled = !enabled;
			}
		});
	}

	function refresh() {
		var config = readConfig();
		var formCodeEl = document.getElementById('mq-cf7-form-code');
		var emailCodeEl = document.getElementById('mq-cf7-email-code');
		var subjectEl = document.getElementById('mq-cf7-subject-code');
		var replyEl = document.getElementById('mq-cf7-reply-to');
		var replyBtn = document.getElementById('mq-copy-reply-to');

		if (formCodeEl) {
			formCodeEl.value = generateFormCode(config);
		}
		if (emailCodeEl) {
			emailCodeEl.value = generateEmailHtml(config);
		}
		if (subjectEl) {
			subjectEl.value = generateSubject(config);
		}

		var mirrorSubject = root.querySelector('.mq-mirror-subject');
		var mirrorHeaders = root.querySelector('.mq-mirror-headers');
		var mirrorEmail = root.querySelector('.mq-mirror-email');
		if (mirrorSubject) {
			mirrorSubject.value = generateSubject(config);
		}
		if (mirrorEmail) {
			mirrorEmail.value = generateEmailHtml(config);
		}

		var reply = generateReplyTo(config);
		if (replyEl) {
			replyEl.textContent = reply || '';
		}
		if (mirrorHeaders) {
			mirrorHeaders.value = reply || '';
		}
		if (replyBtn) {
			replyBtn.disabled = !reply;
			replyBtn.setAttribute('data-copy-text', reply || '');
		}

		syncDependentEmailToggles(config);
		renderFormPreview(config);
		renderEmailPreview(config);
	}

	function copyText(text, feedbackEl) {
		function show() {
			if (!feedbackEl) {
				return;
			}
			feedbackEl.hidden = false;
			window.setTimeout(function () {
				feedbackEl.hidden = true;
			}, 1800);
		}

		function fail() {
			window.alert(data.copyFailed || 'Copy failed');
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(show).catch(function () {
				if (!fallback(text, show)) {
					fail();
				}
			});
		} else if (!fallback(text, show)) {
			fail();
		}
	}

	function fallback(text, done) {
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.setAttribute('readonly', '');
		ta.style.position = 'absolute';
		ta.style.left = '-9999px';
		document.body.appendChild(ta);
		ta.select();
		var ok = false;
		try {
			ok = document.execCommand('copy');
			if (ok) {
				done();
			}
		} catch (e) {
			ok = false;
		}
		document.body.removeChild(ta);
		return ok;
	}

	// Tabs Form / Email
	root.querySelectorAll('[data-mq-builder-tab]').forEach(function (tab) {
		tab.addEventListener('click', function () {
			var slug = tab.getAttribute('data-mq-builder-tab');
			root.querySelectorAll('[data-mq-builder-tab]').forEach(function (t) {
				var on = t === tab;
				t.classList.toggle('is-active', on);
				t.setAttribute('aria-selected', on ? 'true' : 'false');
			});
			root.querySelectorAll('[data-mq-builder-panel]').forEach(function (panel) {
				var on = panel.getAttribute('data-mq-builder-panel') === slug;
				panel.classList.toggle('is-active', on);
				if (on) {
					panel.removeAttribute('hidden');
				} else {
					panel.setAttribute('hidden', 'hidden');
				}
			});
		});
	});

	root.addEventListener('change', function (event) {
		if (
			event.target.classList.contains('mq-builder-enabled') ||
			event.target.classList.contains('mq-builder-required') ||
			event.target.classList.contains('mq-email-toggle')
		) {
			refresh();
		}
	});

	root.addEventListener('input', function (event) {
		if (
			event.target.id === 'mq-submit_text' ||
			event.target.id === 'mq-email-header_title' ||
			event.target.id === 'mq-email-company_name' ||
			event.target.id === 'mq-email-subject'
		) {
			refresh();
		}
	});

	root.querySelectorAll('[data-mq-copy]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var id = btn.getAttribute('data-mq-copy');
			var el = document.getElementById(id);
			var feedback = btn.parentNode
				? btn.parentNode.querySelector('[data-mq-copy-feedback]')
				: null;
			if (el) {
				copyText(el.value || '', feedback);
			}
		});
	});

	var replyBtn = document.getElementById('mq-copy-reply-to');
	if (replyBtn) {
		replyBtn.addEventListener('click', function () {
			var text = replyBtn.getAttribute('data-copy-text') || '';
			var feedback = replyBtn.parentNode
				? replyBtn.parentNode.querySelector('[data-mq-copy-feedback]')
				: null;
			if (text) {
				copyText(text, feedback);
			}
		});
	}

	refresh();
})();
