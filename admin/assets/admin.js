/**
 * Musomo Quote — Admin scripts.
 */
(function () {
	'use strict';

	// Restrictions: category mode UI.
	(function () {
		var form = document.getElementById('mq-restrictions-form');
		if (!form) {
			return;
		}

		var row = form.querySelector('.mq-restriction-categories-row');
		var select = form.querySelector('#mq-restriction_category_ids');
		var radios = form.querySelectorAll('[data-mq-cat-mode]');

		function syncCategoryUi() {
			var selected = form.querySelector('[data-mq-cat-mode]:checked');
			var mode = selected ? selected.value : 'all';
			var enabled = mode !== 'all';

			if (row) {
				if (enabled) {
					row.removeAttribute('hidden');
				} else {
					row.setAttribute('hidden', 'hidden');
				}
			}

			if (select) {
				select.disabled = !enabled;
			}
		}

		radios.forEach(function (radio) {
			radio.addEventListener('change', syncCategoryUi);
		});

		syncCategoryUi();
	})();

	// Texts: Polylang language tabs (panels stay in DOM for save-all).
	(function () {
		var form = document.getElementById('mq-texts-form');
		if (!form) {
			return;
		}

		var tabs = form.querySelectorAll('[data-mq-i18n-tab]');
		var panels = form.querySelectorAll('[data-mq-i18n-panel]');
		if (!tabs.length || !panels.length) {
			return;
		}

		function activate(slug) {
			tabs.forEach(function (tab) {
				var active = tab.getAttribute('data-mq-i18n-tab') === slug;
				tab.classList.toggle('is-active', active);
				tab.setAttribute('aria-selected', active ? 'true' : 'false');
			});

			panels.forEach(function (panel) {
				var active = panel.getAttribute('data-mq-i18n-panel') === slug;
				panel.classList.toggle('is-active', active);
				if (active) {
					panel.removeAttribute('hidden');
				} else {
					panel.setAttribute('hidden', 'hidden');
				}
			});
		}

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				activate(tab.getAttribute('data-mq-i18n-tab') || 'global');
			});
		});
	})();

	// Tools: copy system info + reset confirm.
	(function () {
		var copyBtn = document.getElementById('mq-copy-system-info');
		var textarea = document.getElementById('mq-system-info');
		var feedback = document.getElementById('mq-copy-feedback');
		var i18n = (typeof musomoQuoteAdmin !== 'undefined' && musomoQuoteAdmin.i18n) ? musomoQuoteAdmin.i18n : {};

		function showCopied() {
			if (!feedback) {
				return;
			}
			feedback.hidden = false;
			window.setTimeout(function () {
				feedback.hidden = true;
			}, 2000);
		}

		function showCopyFailed() {
			window.alert(i18n.copyFailed || 'Copy failed');
		}

		function fallbackCopy(text) {
			if (!textarea) {
				return false;
			}
			textarea.focus();
			textarea.select();
			try {
				return document.execCommand('copy');
			} catch (e) {
				return false;
			}
		}

		if (copyBtn && textarea) {
			copyBtn.addEventListener('click', function () {
				var text = textarea.value || '';
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(showCopied).catch(function () {
						if (fallbackCopy(text)) {
							showCopied();
						} else {
							showCopyFailed();
						}
					});
				} else if (fallbackCopy(text)) {
					showCopied();
				} else {
					showCopyFailed();
				}
			});
		}

		var resetForm = document.getElementById('mq-reset-form');
		if (resetForm) {
			resetForm.addEventListener('submit', function (event) {
				var input = document.getElementById('mq-reset-confirm');
				var value = input ? String(input.value || '').trim() : '';
				if (value !== 'RESET') {
					event.preventDefault();
					window.alert(i18n.resetTypeConfirm || 'Type RESET to confirm the reset.');
					return;
				}
				if (!window.confirm(i18n.resetConfirm || 'This will restore all Musomo Quote settings. Continue?')) {
					event.preventDefault();
				}
			});
		}
	})();
})();
