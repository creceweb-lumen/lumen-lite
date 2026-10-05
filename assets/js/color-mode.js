(function () {
	'use strict';

	var root = document.documentElement;
	var key = 'cw_lumen_color_mode';
	var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

	function storedMode() {
		try {
			var value = window.localStorage.getItem(key) || '';
			return value === 'light' || value === 'dark' ? value : '';
		} catch (error) {
			return '';
		}
	}

	function siteDefault() {
		var rootValue = String(root.getAttribute('data-cw-color-mode-default') || '');
		if (rootValue === 'light' || rootValue === 'dark' || rootValue === 'system') {
			return rootValue;
		}
		var button = document.querySelector('[data-cw-color-mode-switch]');
		if (!button) {
			return 'system';
		}
		var value = String(button.getAttribute('data-cw-default-mode') || 'system');
		return value === 'light' || value === 'dark' || value === 'system' ? value : 'system';
	}

	function resolvedDefault() {
		var value = siteDefault();
		if (value === 'light' || value === 'dark') {
			return value;
		}
		return media && media.matches ? 'dark' : 'light';
	}

	function currentMode() {
		var value = root.getAttribute('data-cw-color-mode');
		return value === 'dark' ? 'dark' : 'light';
	}

	function updateButtons() {
		var dark = currentMode() === 'dark';
		document.querySelectorAll('[data-cw-color-mode-switch]').forEach(function (button) {
			var label = dark ? button.getAttribute('data-cw-label-light') : button.getAttribute('data-cw-label-dark');
			button.setAttribute('aria-pressed', dark ? 'true' : 'false');
			if (label) {
				button.setAttribute('aria-label', label);
				button.setAttribute('title', label);
				var sr = button.querySelector('.screen-reader-text');
				if (sr) {
					sr.textContent = label;
				}
			}
		});
	}

	function applyMode(mode, persist) {
		var next = mode === 'dark' ? 'dark' : 'light';
		root.setAttribute('data-cw-color-mode', next);
		if (persist) {
			try {
				window.localStorage.setItem(key, next);
			} catch (error) {}
		}
		updateButtons();
	}

	document.addEventListener('click', function (event) {
		var button = event.target.closest ? event.target.closest('[data-cw-color-mode-switch]') : null;
		if (!button) {
			return;
		}
		applyMode(currentMode() === 'dark' ? 'light' : 'dark', true);
	});

	if (media) {
		var onSystemChange = function () {
			if (!storedMode() && siteDefault() === 'system') {
				applyMode(resolvedDefault(), false);
			}
		};
		if (typeof media.addEventListener === 'function') {
			media.addEventListener('change', onSystemChange);
		} else if (typeof media.addListener === 'function') {
			media.addListener(onSystemChange);
		}
	}

	window.addEventListener('storage', function (event) {
		if (event.key !== key) {
			return;
		}
		var value = storedMode();
		applyMode(value || resolvedDefault(), false);
	});

	updateButtons();
}());
