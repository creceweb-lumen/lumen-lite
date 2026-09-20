(function () {
	'use strict';

	function fallbackCopy(value) {
		var field = document.createElement('textarea');
		field.value = value;
		field.setAttribute('readonly', 'readonly');
		field.style.position = 'fixed';
		field.style.opacity = '0';
		document.body.appendChild(field);
		field.select();
		var copied = false;
		try {
			copied = document.execCommand('copy');
		} catch (error) {
			copied = false;
		}
		document.body.removeChild(field);
		return copied ? Promise.resolve() : Promise.reject(new Error('copy-failed'));
	}

	function copyText(value) {
		if (navigator.clipboard && 'function' === typeof navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(value).catch(function () {
				return fallbackCopy(value);
			});
		}
		return fallbackCopy(value);
	}

	function setStatus(button, key) {
		var group = button.closest('[data-cw-lumen-share]');
		var status = group ? group.querySelector('[data-cw-lumen-share-status]') : null;
		var attribute = 'copied' === key ? 'data-cw-lumen-share-copied' : 'data-cw-lumen-share-copy-error';
		var message = group ? (group.getAttribute(attribute) || '') : '';
		if (status && message) {
			status.textContent = message;
		}
	}

	document.querySelectorAll('[data-cw-lumen-share-action="copy"]').forEach(function (button) {
		button.addEventListener('click', function (event) {
			event.preventDefault();
			var url = button.getAttribute('data-cw-lumen-share-url') || '';
			if (!url) {
				return Promise.resolve();
			}
			return copyText(url).then(function () {
				setStatus(button, 'copied');
			}).catch(function () {
				setStatus(button, 'error');
			});
		});
	});
})();
