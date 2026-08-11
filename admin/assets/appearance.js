/**
 * Musomo Quote — Appearance admin live preview (STEP 4).
 */
(function () {
	'use strict';

	var form = document.getElementById('mq-appearance-form');
	var preview = document.getElementById('mq-appearance-preview');
	if (!form || !preview) {
		return;
	}

	var config = window.musomoQuoteAppearance || {};
	var defaults = config.defaults || {};

	/**
	 * Read current form values into an appearance object.
	 *
	 * @return {Object}
	 */
	function readValues() {
		var values = {};
		var styleInput = form.querySelector('input[name="musomo_quote_settings[appearance_style]"]:checked');
		values.appearance_style = styleInput ? styleInput.value : 'theme';

		form.querySelectorAll('[data-mq-appearance]').forEach(function (el) {
			var key = el.getAttribute('data-mq-appearance');
			if (!key || key === 'appearance_style') {
				return;
			}

			if (el.type === 'checkbox') {
				values[key] = el.checked;
				return;
			}

			if (el.type === 'radio') {
				if (el.checked) {
					values[key] = el.value;
				}
				return;
			}

			values[key] = el.value;
		});

		return values;
	}

	/**
	 * Hex + opacity to rgba.
	 *
	 * @param {string} hex
	 * @param {number} opacity
	 * @return {string}
	 */
	function hexToRgba(hex, opacity) {
		if (!hex || hex === 'transparent') {
			return 'transparent';
		}
		var h = String(hex).replace('#', '');
		if (h.length === 3) {
			h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
		}
		if (h.length !== 6) {
			return 'rgba(0,0,0,0.5)';
		}
		var r = parseInt(h.slice(0, 2), 16);
		var g = parseInt(h.slice(2, 4), 16);
		var b = parseInt(h.slice(4, 6), 16);
		var a = Math.max(0, Math.min(90, Number(opacity) || 0)) / 100;
		return 'rgba(' + r + ',' + g + ',' + b + ',' + a + ')';
	}

	/**
	 * Resolve preview values for the selected preset.
	 *
	 * @param {Object} values
	 * @return {Object}
	 */
	function resolve(values) {
		var style = values.appearance_style || 'theme';
		if (style === 'theme' && config.presets && config.presets.theme) {
			return Object.assign({}, config.presets.theme, {
				summary_show_image: values.summary_show_image,
				summary_show_sku: values.summary_show_sku,
				summary_show_price: values.summary_show_price,
				summary_image_size: values.summary_image_size,
				summary_layout: values.summary_layout,
				appearance_style: 'theme',
			});
		}
		if (style === 'musomo' && config.presets && config.presets.musomo) {
			return Object.assign({}, config.presets.musomo, {
				summary_show_image: values.summary_show_image,
				summary_show_sku: values.summary_show_sku,
				summary_show_price: values.summary_show_price,
				summary_image_size: values.summary_image_size,
				summary_layout: values.summary_layout,
				appearance_style: 'musomo',
			});
		}
		return Object.assign({}, defaults, values, { appearance_style: 'custom' });
	}

	/**
	 * Apply CSS variables and modifier classes to preview.
	 */
	function updatePreview() {
		var values = readValues();
		var a = resolve(values);

		preview.style.setProperty('--mq-btn-bg', a.btn_bg);
		preview.style.setProperty('--mq-btn-color', a.btn_text);
		preview.style.setProperty('--mq-btn-border', a.btn_border);
		preview.style.setProperty('--mq-btn-hover-bg', a.btn_hover_bg);
		preview.style.setProperty('--mq-btn-hover-color', a.btn_hover_text);
		preview.style.setProperty('--mq-btn-radius', Number(a.btn_radius || 0) + 'px');
		preview.style.setProperty('--mq-btn-height', Number(a.btn_height || 48) + 'px');
		preview.style.setProperty('--mq-btn-padding-x', Number(a.btn_padding_x || 20) + 'px');
		preview.style.setProperty('--mq-btn-font-size', Number(a.btn_font_size || 16) + 'px');
		preview.style.setProperty('--mq-btn-font-weight', String(a.btn_font_weight || 600));
		preview.style.setProperty('--mq-btn-width', a.btn_width === 'full' ? '100%' : 'auto');
		preview.style.setProperty('--mq-modal-width', Number(a.modal_width || 900) + 'px');
		preview.style.setProperty('--mq-modal-radius', Number(a.modal_radius || 12) + 'px');
		preview.style.setProperty('--mq-modal-bg', a.modal_bg);
		preview.style.setProperty('--mq-modal-text', a.modal_text);
		preview.style.setProperty('--mq-modal-padding', Number(a.modal_padding || 20) + 'px');
		preview.style.setProperty('--mq-overlay-bg', hexToRgba(a.overlay_color, a.overlay_opacity));
		preview.style.setProperty('--mq-close-size', Number(a.close_size || 40) + 'px');
		preview.style.setProperty('--mq-close-color', a.close_color);
		preview.style.setProperty('--mq-close-bg', a.close_bg);
		preview.style.setProperty('--mq-close-radius', Number(a.close_radius || 8) + 'px');
		preview.style.setProperty('--mq-summary-image', Number(a.summary_image_size || 96) + 'px');
		preview.style.setProperty('--mq-field-height', Number(a.field_height || 44) + 'px');
		preview.style.setProperty('--mq-field-radius', Number(a.field_radius || 4) + 'px');
		preview.style.setProperty('--mq-field-border', a.field_border);
		preview.style.setProperty('--mq-field-focus', a.field_focus);
		preview.style.setProperty('--mq-submit-bg', a.submit_bg);
		preview.style.setProperty('--mq-submit-color', a.submit_text);
		preview.style.setProperty('--mq-submit-radius', Number(a.submit_radius || 4) + 'px');

		preview.className =
			'musomo-quote-root musomo-quote-root--' +
			(a.appearance_style || 'theme') +
			' musomo-quote-summary--' +
			(a.summary_layout || 'horizontal') +
			' mq-appearance-preview' +
			(a.summary_show_image ? '' : ' musomo-quote-hide-image') +
			(a.summary_show_sku ? '' : ' musomo-quote-hide-sku') +
			(a.summary_show_price ? '' : ' musomo-quote-hide-price');

		var opacityLabel = form.querySelector('[data-mq-opacity-label]');
		if (opacityLabel) {
			opacityLabel.textContent = String(a.overlay_opacity || 0) + '%';
		}

		form.classList.toggle('is-custom-style', a.appearance_style === 'custom');
		form.classList.toggle('is-theme-style', a.appearance_style === 'theme');
		form.classList.toggle('is-musomo-style', a.appearance_style === 'musomo');
	}

	// Sync native color pickers with text fields.
	form.querySelectorAll('.mq-color-picker').forEach(function (picker) {
		var key = picker.getAttribute('data-mq-color-for');
		var text = form.querySelector('#mq-field-' + key);
		if (!text) {
			return;
		}
		picker.addEventListener('input', function () {
			text.value = picker.value;
			updatePreview();
		});
		text.addEventListener('input', function () {
			if (/^#[0-9a-fA-F]{6}$/.test(text.value)) {
				picker.value = text.value;
			}
			updatePreview();
		});
	});

	form.addEventListener('input', updatePreview);
	form.addEventListener('change', updatePreview);

	updatePreview();
})();
