(function () {
	'use strict';

	function qs(root, selector) {
		return root.querySelector(selector);
	}

	function qsa(root, selector) {
		return Array.prototype.slice.call(root.querySelectorAll(selector));
	}

	function validHex(value) {
		return /^#[0-9a-f]{6}$/i.test(value || '');
	}

	function setGroupVisible(root, name, visible) {
		qsa(root, '[data-cw-floating-group="' + name + '"]').forEach(function (field) {
			field.hidden = !visible;
		});
	}

	function updateVisibility(root) {
		var content = qs(root, '#cw-lumen-lite-action-content');
		var action = qs(root, '#cw-lumen-lite-action-type');
		var autoHide = qs(root, 'input[name="floating_action[auto_hide]"]');
		var contentValue = content ? content.value : 'both';
		var actionValue = action ? action.value : 'element';

		setGroupVisible(root, 'icon', contentValue !== 'text');
		setGroupVisible(root, 'text', contentValue !== 'icon');
		setGroupVisible(root, 'element', actionValue === 'element');
		setGroupVisible(root, 'url', actionValue === 'url');
		setGroupVisible(root, 'auto-hide', !!(autoHide && autoHide.checked));
	}

	function syncColorControl(root, input) {
		if (input.matches('[data-cw-color-picker]')) {
			var target = qs(root, '#' + input.getAttribute('data-cw-color-picker'));
			if (target) {
				target.value = input.value.toLowerCase();
			}
			return;
		}

		if (input.closest && input.closest('.cw-lumen-floating-color-control') && validHex(input.value)) {
			var picker = qs(input.closest('.cw-lumen-floating-color-control'), '[data-cw-color-picker]');
			if (picker) {
				picker.value = input.value.toLowerCase();
			}
		}
	}

	function updatePreview(root) {
		var preview = qs(root, '[data-cw-floating-action-preview]');
		if (!preview) {
			return;
		}

		var content = qs(root, '#cw-lumen-lite-action-content');
		var shape = qs(root, '#cw-lumen-lite-action-shape');
		var icon = qs(root, '#cw-lumen-lite-action-icon');
		var label = qs(root, '#cw-lumen-lite-action-label');
		var size = qs(root, '#cw-lumen-lite-action-size');
		var opacity = qs(root, '#cw-lumen-lite-action-opacity');
		var iconSlot = qs(preview, '[data-cw-preview-icon]');
		var labelSlot = qs(preview, '[data-cw-preview-label]');
		var emptyLabel = preview.getAttribute('data-cw-preview-empty-label') || 'Action';
		var contentValue = content ? content.value : 'both';
		var labelValue = label && label.value.trim() ? label.value.trim() : '';
		var effectiveContent = contentValue !== 'icon' && !labelValue ? 'icon' : contentValue;

		preview.setAttribute('data-content', effectiveContent);
		preview.setAttribute('data-shape', shape ? shape.value : 'pill');
		preview.style.setProperty('--cw-preview-size', Math.max(1, parseInt(size ? size.value : '46', 10) || 46) + 'px');
		var opacityValue = parseInt(opacity ? opacity.value : '100', 10);
		if (isNaN(opacityValue)) {
			opacityValue = 100;
		}
		preview.style.setProperty('--cw-preview-opacity', Math.max(0, Math.min(100, opacityValue)) / 100);

		[
			['#cw-lumen-lite-action-background', '--cw-preview-background'],
			['#cw-lumen-lite-action-background-hover', '--cw-preview-background-hover'],
			['#cw-lumen-lite-action-icon-color', '--cw-preview-icon'],
			['#cw-lumen-lite-action-icon-hover', '--cw-preview-icon-hover'],
			['#cw-lumen-lite-action-text-color', '--cw-preview-text'],
			['#cw-lumen-lite-action-text-hover', '--cw-preview-text-hover']
		].forEach(function (pair) {
			var field = qs(root, pair[0]);
			if (field && validHex(field.value)) {
				preview.style.setProperty(pair[1], field.value);
			}
		});

		if (labelSlot) {
			labelSlot.textContent = labelValue || emptyLabel;
		}

		if (iconSlot && icon) {
			var template = qs(root, '[data-cw-floating-icon-template="' + icon.value + '"]');
			if (template) {
				iconSlot.innerHTML = template.innerHTML;
			}
		}
	}

	function init(root) {
		updateVisibility(root);
		updatePreview(root);

		root.addEventListener('input', function (event) {
			if (!event.target) {
				return;
			}
			syncColorControl(root, event.target);
			updateVisibility(root);
			updatePreview(root);
		});

		root.addEventListener('change', function (event) {
			if (!event.target) {
				return;
			}
			syncColorControl(root, event.target);
			updateVisibility(root);
			updatePreview(root);
		});
	}

	document.querySelectorAll('[data-cw-floating-settings]').forEach(init);
})();
