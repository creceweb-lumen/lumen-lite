(function () {
	'use strict';

	function updateReturnUrl(form, view) {
		var returnInput = form ? form.querySelector('[data-cw-content-return-url]') : null;
		if (!returnInput || !returnInput.value) { return; }
		try {
			var url = new URL(returnInput.value, window.location.href);
			url.searchParams.set('content_view', view);
			returnInput.value = url.toString();
		} catch (error) {
			// Keep the server-rendered return URL when URL parsing is unavailable.
		}
	}

	function setHistory(href) {
		if (!href || !window.history || !window.history.pushState) { return; }
		window.history.pushState({}, '', href);
	}

	function switchView(shell, view, updateHistory, href) {
		if (!shell || !view) { return false; }
		var panel = shell.querySelector('[data-cw-content-panel="' + view + '"]');
		if (!panel) { return false; }

		shell.querySelectorAll('[data-cw-content-panel]').forEach(function (candidate) {
			candidate.hidden = candidate !== panel;
		});
		shell.querySelectorAll('[data-cw-content-tab]').forEach(function (tab) {
			var active = tab.getAttribute('data-cw-content-tab') === view;
			tab.classList.toggle('nav-tab-active', active);
			tab.setAttribute('aria-selected', active ? 'true' : 'false');
			tab.setAttribute('tabindex', active ? '0' : '-1');
		});

		shell.setAttribute('data-cw-content-active-view', view);
		var form = shell.closest('form');
		var viewInput = form ? form.querySelector('[data-cw-content-view-input]') : null;
		if (viewInput) { viewInput.value = view; }
		updateReturnUrl(form, view);
		if (updateHistory && href) { setHistory(href); }
		return true;
	}

	function viewFromLocation() {
		try {
			return new URL(window.location.href).searchParams.get('content_view') || '';
		} catch (error) {
			return '';
		}
	}

	function initRelatedRatioSelect(root) {
		(root || document).querySelectorAll('[data-cw-related-ratio-preset]').forEach(function (select) {
			if (select.dataset.cwRelatedRatioReady === '1') { return; }
			select.dataset.cwRelatedRatioReady = '1';
			var field = select.closest('.cw-lumen-related-content-ratio-field');
			var custom = field ? field.querySelector('[data-cw-related-ratio-custom]') : null;
			if (!custom) { return; }
			var update = function () {
				var isCustom = select.value === 'custom';
				custom.hidden = !isCustom;
				select.setAttribute('aria-expanded', isCustom ? 'true' : 'false');
			};
			select.addEventListener('change', update);
			update();
		});
	}


	function initPopularContentUI(root) {
		(root || document).querySelectorAll('[data-cw-popular-settings]').forEach(function (settings) {
			if (settings.dataset.cwPopularReady === '1') { return; }
			settings.dataset.cwPopularReady = '1';

			var selectionMode = settings.querySelector('#cw-lumen-popular-selection-mode');
			var imageToggle = settings.querySelector('[data-cw-popular-image-toggle]');
			var taxonomyToggle = settings.querySelector('[data-cw-popular-taxonomy-toggle]');
			var dateToggle = settings.querySelector('[data-cw-popular-date-toggle]');
			var excerptToggle = settings.querySelector('[data-cw-popular-excerpt-toggle]');
			var preview = settings.querySelector('[data-cw-popular-preview]');
			var previewImage = settings.querySelector('[data-cw-popular-preview-image]');
			var previewTaxonomy = settings.querySelector('[data-cw-popular-preview-taxonomy]');
			var previewDate = settings.querySelector('[data-cw-popular-preview-date]');
			var previewExcerpt = settings.querySelector('[data-cw-popular-preview-excerpt]');
			var styleInputs = Array.prototype.slice.call(settings.querySelectorAll('input[name="popular_content[card_style]"]'));
			var ratioSelect = settings.querySelector('#cw-lumen-popular-image-ratio');
			var ratioWidth = settings.querySelector('input[name="popular_content[image_ratio_width]"]');
			var ratioHeight = settings.querySelector('input[name="popular_content[image_ratio_height]"]');
			var deviceToggles = Array.prototype.slice.call(settings.querySelectorAll('[data-cw-popular-device-toggle]'));
			var previewDevices = Array.prototype.slice.call(settings.querySelectorAll('[data-cw-popular-preview-device]'));
			var previewDeviceEmpty = settings.querySelector('[data-cw-popular-preview-device-empty]');

			function updateMode() {
				var mode = selectionMode ? selectionMode.value : 'automatic';
				settings.querySelectorAll('[data-cw-popular-mode]').forEach(function (field) {
					field.hidden = field.getAttribute('data-cw-popular-mode') !== mode;
				});
			}

			function updateImageDependency() {
				var showImage = !!(imageToggle && imageToggle.checked);
				settings.querySelectorAll('[data-cw-popular-image-dependent]').forEach(function (field) {
					field.hidden = !showImage;
					field.setAttribute('aria-hidden', showImage ? 'false' : 'true');
				});
				if (previewImage) { previewImage.hidden = !showImage; }
			}

			function updateDevicePreview() {
				var visibleCount = 0;
				previewDevices.forEach(function (chip) {
					var device = chip.getAttribute('data-cw-popular-preview-device') || '';
					var toggle = deviceToggles.find(function (input) { return input.getAttribute('data-cw-popular-device-toggle') === device; });
					var visible = !!(toggle && toggle.checked);
					chip.hidden = !visible;
					if (visible) { visibleCount += 1; }
				});
				if (previewDeviceEmpty) { previewDeviceEmpty.hidden = visibleCount > 0; }
			}

			function updatePreview() {
				if (!preview) { return; }
				if (previewTaxonomy) { previewTaxonomy.hidden = !(taxonomyToggle && taxonomyToggle.checked); }
				if (previewDate) { previewDate.hidden = !(dateToggle && dateToggle.checked); }
				if (previewExcerpt) { previewExcerpt.hidden = !(excerptToggle && excerptToggle.checked); }

				var style = 'default';
				styleInputs.forEach(function (input) {
					if (input.checked) { style = input.value || 'default'; }
				});
				preview.setAttribute('data-card-style', style);

				if (previewImage && !previewImage.hidden && ratioSelect) {
					var ratio = ratioSelect.value;
					if (ratio === 'custom' && ratioWidth && ratioHeight) {
						var width = parseFloat(ratioWidth.value);
						var height = parseFloat(ratioHeight.value);
						ratio = width > 0 && height > 0 ? width + ' / ' + height : '16 / 9';
					} else if (ratio && ratio.indexOf(':') !== -1) {
						ratio = ratio.replace(':', ' / ');
					}
					previewImage.style.aspectRatio = ratio || '16 / 9';
				}
			}

			if (selectionMode) { selectionMode.addEventListener('change', updateMode); }
			[imageToggle, taxonomyToggle, dateToggle, excerptToggle].forEach(function (input) {
				if (input) { input.addEventListener('change', function () { updateImageDependency(); updatePreview(); }); }
			});
			styleInputs.forEach(function (input) { input.addEventListener('change', updatePreview); });
			deviceToggles.forEach(function (input) { input.addEventListener('change', updateDevicePreview); });
			[ratioSelect, ratioWidth, ratioHeight].forEach(function (input) {
				if (input) { input.addEventListener('change', updatePreview); input.addEventListener('input', updatePreview); }
			});

			updateMode();
			updateImageDependency();
			updateDevicePreview();
			updatePreview();
		});
	}


	function initPostsGridUI(root) {
		(root || document).querySelectorAll('[data-cw-posts-grid-settings]').forEach(function (settings) {
			if (settings.dataset.cwPostsGridReady === '1') { return; }
			settings.dataset.cwPostsGridReady = '1';

			var source = settings.querySelector('[data-cw-posts-grid-source]');
			var imageToggle = settings.querySelector('[data-cw-posts-grid-image-toggle]');
			var taxonomyToggle = settings.querySelector('[data-cw-posts-grid-taxonomy-toggle]');
			var dateToggle = settings.querySelector('[data-cw-posts-grid-date-toggle]');
			var excerptToggle = settings.querySelector('[data-cw-posts-grid-excerpt-toggle]');
			var readMoreToggle = settings.querySelector('[data-cw-posts-grid-read-more-toggle]');
			var readMoreText = settings.querySelector('[data-cw-posts-grid-read-more-text]');
			var layoutSelect = settings.querySelector('[data-cw-posts-grid-layout]');
			var styleSelect = settings.querySelector('[data-cw-posts-grid-style]');
			var ratioSelect = settings.querySelector('#cw-lumen-posts-grid-image-ratio');
			var ratioWidth = settings.querySelector('input[name="posts_grid[image_ratio_width]"]');
			var ratioHeight = settings.querySelector('input[name="posts_grid[image_ratio_height]"]');
			var preview = settings.querySelector('[data-cw-posts-grid-preview]');
			var previewImage = settings.querySelector('[data-cw-posts-grid-preview-image]');
			var previewTaxonomy = settings.querySelector('[data-cw-posts-grid-preview-taxonomy]');
			var previewDate = settings.querySelector('[data-cw-posts-grid-preview-date]');
			var previewExcerpt = settings.querySelector('[data-cw-posts-grid-preview-excerpt]');
			var previewReadMore = settings.querySelector('[data-cw-posts-grid-preview-read-more]');
			var previewReadMoreLabel = settings.querySelector('[data-cw-posts-grid-preview-read-more-label]');

			function updateSourceMode() {
				var current = source ? source.value : 'latest';
				settings.querySelectorAll('[data-cw-posts-grid-source-mode]').forEach(function (field) {
					field.hidden = field.getAttribute('data-cw-posts-grid-source-mode') !== current;
				});
			}

			function updateImageDependency() {
				var visible = !!(imageToggle && imageToggle.checked);
				settings.querySelectorAll('[data-cw-posts-grid-image-dependent]').forEach(function (field) {
					field.hidden = !visible;
					field.setAttribute('aria-hidden', visible ? 'false' : 'true');
				});
				if (previewImage) { previewImage.hidden = !visible; }
			}

			function previewRatio() {
				if (!ratioSelect) { return '16 / 9'; }
				var ratio = ratioSelect.value || '16:9';
				if (ratio === 'custom') {
					var width = ratioWidth ? parseFloat(ratioWidth.value) : 16;
					var height = ratioHeight ? parseFloat(ratioHeight.value) : 9;
					return width > 0 && height > 0 ? width + ' / ' + height : '16 / 9';
				}
				return ratio.indexOf(':') !== -1 ? ratio.replace(':', ' / ') : '16 / 9';
			}

			function updatePreview() {
				if (!preview) { return; }
				if (previewTaxonomy) { previewTaxonomy.hidden = !(taxonomyToggle && taxonomyToggle.checked); }
				if (previewDate) { previewDate.hidden = !(dateToggle && dateToggle.checked); }
				if (previewExcerpt) { previewExcerpt.hidden = !(excerptToggle && excerptToggle.checked); }
				if (previewReadMore) { previewReadMore.hidden = !(readMoreToggle && readMoreToggle.checked); }
				if (previewReadMoreLabel && readMoreText) { previewReadMoreLabel.textContent = readMoreText.value || ''; }
				if (previewImage && !previewImage.hidden) { previewImage.style.aspectRatio = previewRatio(); }
				if (styleSelect) { preview.setAttribute('data-card-style', styleSelect.value || 'default'); }
				if (layoutSelect) { preview.setAttribute('data-layout', layoutSelect.value || 'grid'); }
			}

			if (source) { source.addEventListener('change', updateSourceMode); }
			[imageToggle, taxonomyToggle, dateToggle, excerptToggle, readMoreToggle].forEach(function (input) {
				if (input) { input.addEventListener('change', function () { updateImageDependency(); updatePreview(); }); }
			});
			[ratioSelect, ratioWidth, ratioHeight, styleSelect, layoutSelect].forEach(function (input) {
				if (input) { input.addEventListener('change', updatePreview); input.addEventListener('input', updatePreview); }
			});
			if (readMoreText) { readMoreText.addEventListener('input', updatePreview); }

			updateSourceMode();
			updateImageDependency();
			updatePreview();
		});
	}

	function init(shell) {
		if (!shell || shell.dataset.cwContentTabsReady === '1') { return; }
		shell.dataset.cwContentTabsReady = '1';

		var tabs = Array.prototype.slice.call(shell.querySelectorAll('[data-cw-content-tab]'));
		tabs.forEach(function (tab) {
			tab.addEventListener('click', function (event) {
				var view = tab.getAttribute('data-cw-content-tab') || '';
				if (switchView(shell, view, true, tab.href)) { event.preventDefault(); }
			});

			tab.addEventListener('keydown', function (event) {
				if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) { return; }
				event.preventDefault();
				var index = tabs.indexOf(tab);
				var nextIndex = index;
				if ('Home' === event.key) { nextIndex = 0; }
				else if ('End' === event.key) { nextIndex = tabs.length - 1; }
				else if ('ArrowRight' === event.key) { nextIndex = (index + 1) % tabs.length; }
				else if ('ArrowLeft' === event.key) { nextIndex = (index - 1 + tabs.length) % tabs.length; }
				var nextTab = tabs[nextIndex];
				if (!nextTab) { return; }
				var view = nextTab.getAttribute('data-cw-content-tab') || '';
				if (switchView(shell, view, true, nextTab.href)) { nextTab.focus(); }
			});
		});

		window.addEventListener('popstate', function () {
			var view = viewFromLocation() || 'general';
			switchView(shell, view, false, '');
		});
	}

	document.querySelectorAll('[data-cw-content-tabs]').forEach(init);
	initRelatedRatioSelect(document);
	initPopularContentUI(document);
	initPostsGridUI(document);
})();
