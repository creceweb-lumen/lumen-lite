(function () {
	'use strict';

	function qs(root, selector) {
		return root.querySelector(selector);
	}

	function qsa(root, selector) {
		return Array.prototype.slice.call(root.querySelectorAll(selector));
	}

	function activeProvider(root) {
		var provider = qs(root, '#cw-lumen-lite-messaging-provider');
		return provider ? provider.value : 'whatsapp';
	}

	function appearanceMode(root) {
		var control = qs(root, '[data-cw-messaging-appearance-mode]');
		return control && control.value === 'custom' ? 'custom' : 'provider';
	}

	function updateProvider(root) {
		var value = activeProvider(root);
		qsa(root, '[data-cw-messaging-provider-group]').forEach(function (group) {
			group.hidden = group.getAttribute('data-cw-messaging-provider-group') !== value;
		});

		var previewIcon = qs(root, '[data-cw-messaging-preview-icon]');
		var template = qs(root, '[data-cw-messaging-glyph-template="' + value + '"]');
		if (previewIcon && template) {
			previewIcon.innerHTML = template.innerHTML;
		}
	}

	function updateAppearance(root) {
		var custom = appearanceMode(root) === 'custom';
		qsa(root, '[data-cw-messaging-custom-color]').forEach(function (field) {
			field.hidden = !custom;
		});
	}

	function resolvedColors(root) {
		var background = qs(root, '#cw-lumen-lite-messaging-background');
		var icon = qs(root, '#cw-lumen-lite-messaging-icon');
		if (appearanceMode(root) === 'custom') {
			return {
				background: background ? background.value : '#0f172a',
				icon: icon ? icon.value : '#ffffff'
			};
		}

		var provider = activeProvider(root);
		var preset = qs(root, '[data-cw-messaging-style-preset="' + provider + '"]');
		return {
			background: preset ? (preset.getAttribute('data-background') || '#0f172a') : '#0f172a',
			icon: preset ? (preset.getAttribute('data-icon') || '#ffffff') : '#ffffff'
		};
	}

	function updatePreview(root) {
		var preview = qs(root, '[data-cw-messaging-preview]');
		if (!preview) {
			return;
		}
		var size = qs(root, '#cw-lumen-lite-messaging-size');
		var colors = resolvedColors(root);
		preview.style.setProperty('--cw-preview-size', Math.max(1, parseInt(size ? size.value : '46', 10) || 46) + 'px');
		preview.style.setProperty('--cw-preview-background', colors.background);
		preview.style.setProperty('--cw-preview-icon', colors.icon);
	}

	function insertToken(root, button) {
		var targetId = button.getAttribute('data-cw-messaging-token-target') || '';
		var token = button.getAttribute('data-cw-messaging-token') || '';
		var textarea = targetId ? qs(root, '#' + targetId) : null;
		if (!textarea || !token) {
			return;
		}
		var value = textarea.value || '';
		var start = Number.isInteger(textarea.selectionStart) ? textarea.selectionStart : value.length;
		var end = Number.isInteger(textarea.selectionEnd) ? textarea.selectionEnd : start;
		var scrollTop = textarea.scrollTop;
		textarea.value = value.slice(0, start) + token + value.slice(end);
		var caret = start + token.length;
		textarea.focus();
		if (typeof textarea.setSelectionRange === 'function') {
			textarea.setSelectionRange(caret, caret);
		}
		textarea.scrollTop = scrollTop;
		textarea.dispatchEvent(new Event('input', { bubbles: true }));
	}

	function init(root) {
		updateProvider(root);
		updateAppearance(root);
		updatePreview(root);
		root.addEventListener('input', function () {
			updatePreview(root);
		});
		root.addEventListener('change', function () {
			updateProvider(root);
			updateAppearance(root);
			updatePreview(root);
		});
		root.addEventListener('click', function (event) {
			var button = event.target && event.target.closest ? event.target.closest('[data-cw-messaging-token]') : null;
			if (button && root.contains(button)) {
				insertToken(root, button);
			}
		});
	}

	document.querySelectorAll('[data-cw-messaging-settings]').forEach(init);
})();
