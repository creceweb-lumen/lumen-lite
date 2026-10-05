(function () {
	'use strict';

	var root = document.querySelector('.cw-lumen-color-mode-settings');
	if (!root) {
		return;
	}

	var storageKey = 'cw_lumen_color_mode';
	var browserBox = root.querySelector('[data-cw-color-mode-browser]');
	var browserStatus = root.querySelector('[data-cw-color-mode-browser-status]');
	var resetButton = root.querySelector('#cw-lumen-color-mode-reset-browser');
	var floatingToggle = root.querySelector('[data-cw-color-mode-floating-toggle]');
	var floatingOptions = root.querySelector('[data-cw-color-mode-floating-options]');
	var preview = root.querySelector('[data-cw-color-mode-preview]');
	var previewCanvas = preview ? preview.querySelector('[data-cw-color-mode-preview-canvas]') : null;
	var form = root.querySelector('form');

	function storedMode() {
		try {
			var value = window.localStorage.getItem(storageKey) || '';
			return value === 'light' || value === 'dark' ? value : '';
		} catch (error) {
			return '';
		}
	}

	function updateBrowserStatus() {
		if (!browserBox || !browserStatus) {
			return;
		}
		var mode = storedMode();
		var key = mode === 'light' ? 'statusLight' : (mode === 'dark' ? 'statusDark' : 'statusDefault');
		browserStatus.textContent = browserBox.dataset[key] || '';
		if (resetButton) {
			resetButton.disabled = !mode;
		}
	}

	if (form) {
		form.addEventListener('submit', function () {
			var selected = root.querySelector('input[name="color_mode[default_mode]"]:checked');
			var original = String(root.getAttribute('data-cw-color-mode-original-default') || 'system');
			if (selected && selected.value !== original) {
				try {
					window.localStorage.removeItem(storageKey);
				} catch (error) {}
			}
		});
	}

	function updateFloatingOptions() {
		if (!floatingToggle || !floatingOptions) {
			return;
		}
		floatingOptions.hidden = !floatingToggle.checked;
	}

	function relativeLuminance(hex) {
		var value = String(hex || '').replace('#', '');
		if (!/^[0-9a-fA-F]{6}$/.test(value)) {
			return 0;
		}
		var channels = [0, 2, 4].map(function (offset) {
			var channel = parseInt(value.slice(offset, offset + 2), 16) / 255;
			return channel <= 0.04045 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
		});
		return (0.2126 * channels[0]) + (0.7152 * channels[1]) + (0.0722 * channels[2]);
	}

	function contrastRatio(first, second) {
		var a = relativeLuminance(first);
		var b = relativeLuminance(second);
		return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
	}

	function buttonTextForAccent(accent) {
		var dark = '#0f172a';
		var light = '#ffffff';
		return contrastRatio(accent, dark) >= contrastRatio(accent, light) ? dark : light;
	}

	function updateContrastChecks(palette) {
		var rows = root.querySelectorAll('[data-contrast-pair]');
		if (!rows.length) {
			return;
		}
		var pairs = {
			'text-surface': [palette.text, palette.surface],
			'heading-surface': [palette.heading, palette.surface],
			'link-surface': [palette.link, palette.surface],
			'text-background': [palette.text, palette.background],
			'accent-button': [buttonTextForAccent(palette.accent), palette.accent],
			'primary-chip': [buttonTextForAccent(palette.primary), palette.primary]
		};
		var counts = { low: 0, aa: 0, aaa: 0 };
		var issues = [];

		rows.forEach(function (row) {
			var pair = pairs[row.getAttribute('data-contrast-pair')];
			if (!pair || !pair[0] || !pair[1]) {
				return;
			}
			var ratio = contrastRatio(pair[0], pair[1]);
			var level = ratio >= 7 ? 'aaa' : (ratio >= 4.5 ? 'aa' : 'low');
			var ratioText = ratio.toFixed(2) + ':1';
			var ratioNode = row.querySelector('[data-cw-contrast-ratio]');
			var statusNode = row.querySelector('[data-cw-contrast-status]');
			counts[level] += 1;
			if (ratioNode) {
				ratioNode.textContent = ratioText;
			}
			if (statusNode) {
				statusNode.textContent = level === 'aaa' ? row.dataset.aaaLabel : (level === 'aa' ? row.dataset.aaLabel : row.dataset.lowLabel);
			}
			row.setAttribute('data-contrast-level', level);
			if (level === 'low') {
				issues.push({
					label: row.firstElementChild ? row.firstElementChild.textContent : '',
					ratio: ratioText
				});
			}
		});

		var lowCount = root.querySelector('[data-cw-contrast-count-low]');
		var aaCount = root.querySelector('[data-cw-contrast-count-aa]');
		var aaaCount = root.querySelector('[data-cw-contrast-count-aaa]');
		if (lowCount) { lowCount.textContent = String(counts.low); }
		if (aaCount) { aaCount.textContent = String(counts.aa); }
		if (aaaCount) { aaaCount.textContent = String(counts.aaa); }

		var issuesBox = root.querySelector('[data-cw-contrast-issues]');
		var issuesList = root.querySelector('[data-cw-contrast-issues-list]');
		var allGood = root.querySelector('[data-cw-contrast-all-good]');
		if (issuesList) {
			issuesList.textContent = '';
			issues.forEach(function (issue) {
				var item = document.createElement('li');
				var label = document.createElement('span');
				var ratio = document.createElement('strong');
				label.textContent = issue.label;
				ratio.textContent = issue.ratio;
				item.appendChild(label);
				item.appendChild(ratio);
				issuesList.appendChild(item);
			});
		}
		if (issuesBox) { issuesBox.hidden = issues.length === 0; }
		if (allGood) { allGood.hidden = issues.length !== 0; }
	}

	function updatePalettePreview(input) {
		var control = input.closest('[data-color-key]');
		if (control) {
			var valueNode = control.querySelector('[data-cw-color-value]');
			if (valueNode) {
				valueNode.textContent = input.value;
			}
		}
		if (!previewCanvas) {
			return;
		}
		var palette = {};
		root.querySelectorAll('.cw-lumen-color-mode-color[data-color-key] input[type="color"]').forEach(function (colorInput) {
			var wrapper = colorInput.closest('[data-color-key]');
			if (wrapper) {
				palette[wrapper.getAttribute('data-color-key')] = colorInput.value;
			}
		});
		Object.keys(palette).forEach(function (key) {
			previewCanvas.style.setProperty('--cw-color-mode-preview-' + key, palette[key]);
		});
		if (palette.accent) {
			previewCanvas.style.setProperty('--cw-color-mode-preview-button-text', buttonTextForAccent(palette.accent));
		}
		if (palette.primary) {
			previewCanvas.style.setProperty('--cw-color-mode-preview-primary-text', buttonTextForAccent(palette.primary));
		}
		updateContrastChecks(palette);
	}

	if (resetButton) {
		resetButton.addEventListener('click', function () {
			try {
				window.localStorage.removeItem(storageKey);
			} catch (error) {}
			updateBrowserStatus();
		});
	}

	if (floatingToggle) {
		floatingToggle.addEventListener('change', updateFloatingOptions);
	}

	root.querySelectorAll('.cw-lumen-color-mode-color[data-color-key] input[type="color"]').forEach(function (input) {
		input.addEventListener('input', function () {
			updatePalettePreview(input);
		});
	});

	updateBrowserStatus();
	updateFloatingOptions();
	var firstColor = root.querySelector('.cw-lumen-color-mode-color[data-color-key] input[type="color"]');
	if (firstColor) {
		updatePalettePreview(firstColor);
	}
}());
