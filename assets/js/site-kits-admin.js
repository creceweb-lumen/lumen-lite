(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var result = document.querySelector('[data-cw-site-kit-reset-color-mode="1"]');
		if (result) {
			try {
				window.localStorage.removeItem('cw_lumen_color_mode');
			} catch (error) {
				// Storage can be unavailable in restricted browser contexts.
			}
		}

		document.querySelectorAll('[data-cw-site-kit-install-form]').forEach(function (form) {
			form.addEventListener('submit', function (event) {
				var message = form.getAttribute('data-confirm') || '';
				if (message && !window.confirm(message)) {
					event.preventDefault();
				}
			});
		});
	});
}());
