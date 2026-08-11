/**
 * Musomo Quote — Frontend scripts (STEP 3).
 * Modal open/close, product summary, CF7 hidden-field autofill.
 */
(function () {
	'use strict';

	var config = window.musomoQuote || {};
	var i18n = config.i18n || {};
	var fieldMap = config.fields || {};

	var modal = null;
	var dialog = null;
	var lastTrigger = null;
	var previouslyFocused = null;

	window.MusomoQuote = window.MusomoQuote || {};

	/**
	 * @return {HTMLElement|null}
	 */
	function getModal() {
		if (!modal) {
			modal = document.getElementById('musomo-quote-modal');
		}
		return modal;
	}

	/**
	 * @return {HTMLElement|null}
	 */
	function getDialog() {
		var root = getModal();
		if (!dialog && root) {
			dialog = root.querySelector('.musomo-quote-dialog');
		}
		return dialog;
	}

	/**
	 * Read quantity from the WooCommerce cart form when present.
	 *
	 * @return {string}
	 */
	function getPageQuantity() {
		var qty = document.querySelector(
			'form.cart input.qty, form.cart input[name="quantity"], form.variations_form input.qty'
		);
		if (!qty || !qty.value) {
			return '1';
		}
		return String(qty.value);
	}

	/**
	 * Resolve selected variation from WooCommerce variations form JSON.
	 *
	 * @return {Object|null}
	 */
	function getSelectedVariation() {
		var form = document.querySelector('form.variations_form');
		if (!form) {
			return null;
		}

		var idInput = form.querySelector('input.variation_id, input[name="variation_id"]');
		var variationId = idInput ? String(idInput.value || '') : '';
		if (!variationId || variationId === '0') {
			return null;
		}

		var raw = form.getAttribute('data-product_variations');
		if (!raw) {
			return { variation_id: variationId };
		}

		var variations;
		try {
			variations = JSON.parse(raw);
		} catch (e) {
			return { variation_id: variationId };
		}

		if (!Array.isArray(variations)) {
			return { variation_id: variationId };
		}

		for (var i = 0; i < variations.length; i++) {
			if (String(variations[i].variation_id) === variationId) {
				return variations[i];
			}
		}

		return { variation_id: variationId };
	}

	/**
	 * Build product payload from button dataset + live page state.
	 *
	 * @param {HTMLElement} button Quote button.
	 * @return {Object}
	 */
	function buildProductData(button) {
		var dataset = button.dataset || {};
		var data = {
			id: dataset.productId || '',
			name: dataset.productName || '',
			sku: dataset.productSku || '',
			url: dataset.productUrl || '',
			image: cleanImageUrl(dataset.productImage || ''),
			price: dataset.productPrice || '',
			type: dataset.productType || '',
			quantity: getPageQuantity(),
		};

		var parentImage = data.image;

		var variation = getSelectedVariation();
		if (variation) {
			if (variation.variation_id) {
				data.id = String(variation.variation_id);
			}
			if (typeof variation.sku === 'string' && variation.sku !== '') {
				data.sku = variation.sku;
			}
			if (variation.display_price !== undefined && variation.display_price !== null && variation.display_price !== '') {
				data.price = String(variation.display_price);
			} else if (variation.display_regular_price !== undefined && variation.display_regular_price !== null) {
				data.price = String(variation.display_regular_price);
			}
			if (variation.image) {
				var img = variation.image;
				var candidate = cleanImageUrl(img.full_src || img.url || img.src || '');
				data.image = candidate || parentImage;
			} else {
				data.image = parentImage;
			}
			data.type = 'variation';
		}

		data.image = cleanImageUrl(data.image);
		return data;
	}

	/**
	 * Keep only a pure absolute http(s) image URL (no srcset, HTML, or relatives).
	 *
	 * @param {string} url Raw URL.
	 * @return {string}
	 */
	function cleanImageUrl(url) {
		url = String(url == null ? '' : url).trim();
		if (!url) {
			return '';
		}

		// Accidental HTML.
		if (/<img/i.test(url)) {
			var match = url.match(/\bsrc\s*=\s*["']([^"']+)["']/i);
			url = match ? match[1] : '';
		}

		url = url.replace(/^["']|["']$/g, '').trim();
		if (!url || /^\d+$/.test(url)) {
			return '';
		}

		// "url 800w" / srcset first token.
		var absMatch = url.match(/^(https?:)?\/\/\S+/i);
		if (absMatch) {
			url = absMatch[0];
		}

		url = absolutizeUrl(url);
		if (!url || !/^https?:\/\//i.test(url)) {
			return '';
		}
		return url;
	}

	/**
	 * Ensure image/product URLs are absolute (email clients reject relative src).
	 *
	 * @param {string} url URL.
	 * @return {string}
	 */
	function absolutizeUrl(url) {
		url = String(url || '').trim();
		if (!url) {
			return '';
		}
		// Reject non-http(s) schemes early (javascript:, data:, etc.).
		if (/^[a-z][a-z0-9+.-]*:/i.test(url) && !/^https?:/i.test(url)) {
			return '';
		}
		if (/^https?:\/\//i.test(url)) {
			return url;
		}
		if (url.indexOf('//') === 0) {
			return (window.location.protocol || 'https:') + url;
		}
		if (url.charAt(0) === '/') {
			return window.location.origin + url;
		}
		try {
			var resolved = new URL(url, window.location.href).href;
			return /^https?:\/\//i.test(resolved) ? resolved : '';
		} catch (e) {
			return '';
		}
	}

	/**
	 * Update modal product summary safely via textContent / src.
	 *
	 * @param {Object} data Product data.
	 */
	function updateProductSummary(data) {
		var root = getModal();
		if (!root) {
			return;
		}

		var nameEl = root.querySelector('[data-musomo-quote-summary-name]');
		var skuEl = root.querySelector('[data-musomo-quote-summary-sku]');
		var priceEl = root.querySelector('[data-musomo-quote-summary-price]');
		var imageEl = root.querySelector('[data-musomo-quote-summary-image]');

		if (nameEl) {
			nameEl.textContent = data.name || '';
		}

		if (skuEl) {
			var showSku = root.getAttribute('data-show-sku') !== '0';
			if (showSku && data.sku) {
				skuEl.textContent = (i18n.skuLabel || 'SKU:') + ' ' + data.sku;
				skuEl.hidden = false;
			} else {
				skuEl.textContent = '';
				skuEl.hidden = true;
			}
		}

		if (priceEl) {
			var showPrice = root.getAttribute('data-show-price') !== '0';
			if (showPrice && data.price) {
				priceEl.textContent = (i18n.priceLabel || 'Price:') + ' ' + data.price;
				priceEl.hidden = false;
			} else {
				priceEl.textContent = '';
				priceEl.hidden = true;
			}
		}

		if (imageEl) {
			var showImage = root.getAttribute('data-show-image') !== '0';
			if (showImage && data.image) {
				imageEl.src = data.image;
				imageEl.alt = data.name || '';
				imageEl.hidden = false;
			} else {
				imageEl.removeAttribute('src');
				imageEl.alt = '';
				imageEl.hidden = true;
			}
		}
	}

	/**
	 * Set a form field value if the field exists.
	 *
	 * @param {HTMLElement} form Root form.
	 * @param {string} name Field name.
	 * @param {string} value Value.
	 */
	function setFieldValue(form, name, value) {
		if (!form || !name) {
			return;
		}

		var fields = form.querySelectorAll('[name="' + name + '"]');
		for (var i = 0; i < fields.length; i++) {
			fields[i].value = value == null ? '' : String(value);
		}
	}

	/**
	 * Fill CF7 hidden fields when present.
	 *
	 * @param {Object} data Product data.
	 */
	function fillFormFields(data) {
		var root = getModal();
		if (!root) {
			return;
		}

		var form = root.querySelector('.musomo-quote-form form, .musomo-quote-form .wpcf7-form');
		if (!form) {
			return;
		}

		Object.keys(fieldMap).forEach(function (key) {
			var names = fieldMap[key] || [];
			var value = data[key] != null ? data[key] : '';
			for (var i = 0; i < names.length; i++) {
				setFieldValue(form, names[i], value);
			}
		});
	}

	/**
	 * Open modal and fill product/form data.
	 *
	 * @param {HTMLElement} trigger Button that opened the modal.
	 * @param {Object} data Product data.
	 */
	function openModal(trigger, data) {
		var root = getModal();
		var panel = getDialog();
		if (!root || !panel) {
			return;
		}

		lastTrigger = trigger || null;
		previouslyFocused = document.activeElement;

		root.classList.remove('musomo-quote-success');
		updateProductSummary(data);
		fillFormFields(data);

		root.hidden = false;
		root.setAttribute('aria-hidden', 'false');
		root.classList.add('is-open');
		document.body.classList.add('musomo-quote-modal-open');

		// Soft time-trap: stamp modal open time for CF7-linked form anti-bot check.
		var openedAt = String(Math.floor(Date.now() / 1000));
		var timeFields = root.querySelectorAll('.musomo-quote-opened-at, input[name="mq_opened_at"]');
		for (var i = 0; i < timeFields.length; i++) {
			timeFields[i].value = openedAt;
		}

		var closeBtn = root.querySelector('.musomo-quote-close');
		window.setTimeout(function () {
			if (closeBtn && typeof closeBtn.focus === 'function') {
				closeBtn.focus();
			} else if (typeof panel.focus === 'function') {
				panel.focus();
			}
		}, 0);
	}

	/**
	 * Close modal and restore focus/scroll.
	 */
	function closeModal() {
		var root = getModal();
		if (!root || root.hidden) {
			return;
		}

		root.classList.remove('is-open');
		root.classList.remove('musomo-quote-success');
		root.hidden = true;
		root.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('musomo-quote-modal-open');

		var restore = lastTrigger || previouslyFocused;
		if (restore && typeof restore.focus === 'function') {
			restore.focus();
		}

		lastTrigger = null;
		previouslyFocused = null;
	}

	/**
	 * Focusable elements inside the dialog (visible only).
	 *
	 * @param {HTMLElement} panel Dialog element.
	 * @return {HTMLElement[]}
	 */
	function getFocusable(panel) {
		if (!panel) {
			return [];
		}
		var nodes = panel.querySelectorAll(
			'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
		);
		var out = [];
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			if (el.offsetParent !== null || el === document.activeElement) {
				out.push(el);
			}
		}
		return out;
	}

	/**
	 * Simple focus cycle while modal is open (no complex trap library).
	 *
	 * @param {KeyboardEvent} event Keydown event.
	 */
	function handleFocusTrap(event) {
		if (event.key !== 'Tab') {
			return;
		}
		var root = getModal();
		var panel = getDialog();
		if (!root || root.hidden || !panel) {
			return;
		}
		var focusables = getFocusable(panel);
		if (!focusables.length) {
			event.preventDefault();
			if (typeof panel.focus === 'function') {
				panel.focus();
			}
			return;
		}
		var first = focusables[0];
		var last = focusables[focusables.length - 1];
		var active = document.activeElement;
		if (event.shiftKey) {
			if (active === first || !panel.contains(active)) {
				event.preventDefault();
				last.focus();
			}
		} else if (active === last || !panel.contains(active)) {
			event.preventDefault();
			first.focus();
		}
	}

	/**
	 * Init listeners once DOM is ready.
	 */
	function init() {
		if (!getModal()) {
			return;
		}

		document.addEventListener(
			'click',
			function (event) {
				var target = event.target;
				if (!target || typeof target.closest !== 'function') {
					target = target && target.parentElement ? target.parentElement : null;
				}
				if (!target || typeof target.closest !== 'function') {
					return;
				}

				var openBtn = target.closest('.musomo-quote-button');
				if (openBtn) {
					event.preventDefault();
					var productData = buildProductData(openBtn);
					openModal(openBtn, productData);
					return;
				}

				if (target.closest('[data-musomo-quote-close]')) {
					event.preventDefault();
					closeModal();
				}
			},
			false
		);

		document.addEventListener(
			'keydown',
			function (event) {
				var root = getModal();
				if (!root || root.hidden) {
					return;
				}

				if (event.key === 'Escape' || event.key === 'Esc') {
					event.preventDefault();
					closeModal();
					return;
				}

				handleFocusTrap(event);
			},
			false
		);

		document.addEventListener(
			'wpcf7mailsent',
			function (event) {
				var root = getModal();
				if (!root || root.hidden) {
					return;
				}
				if (event.target && root.contains(event.target)) {
					root.classList.add('musomo-quote-success');
				}
			},
			false
		);

		window.MusomoQuote.open = openModal;
		window.MusomoQuote.close = closeModal;
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
