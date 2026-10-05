(function () {
	'use strict';
	function sync(root) {
		if (!root) return;
		var select = root.querySelector('[data-cw-lumen-menu-indicator-type]');
		if (!select) return;
		root.querySelectorAll('[data-cw-lumen-menu-indicator-panel]').forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-cw-lumen-menu-indicator-panel') !== select.value;
		});
		var visibility = root.querySelector('[data-cw-lumen-menu-indicator-visibility]');
		if (visibility) visibility.hidden = select.value === 'none';
	}
	document.addEventListener('change', function (event) {
		if (!event.target.matches('[data-cw-lumen-menu-indicator-type]')) return;
		sync(event.target.closest('[data-cw-lumen-menu-indicator]'));
	});
	document.addEventListener('click', function (event) {
		var choose = event.target.closest('[data-cw-lumen-menu-indicator-image-select]');
		var remove = event.target.closest('[data-cw-lumen-menu-indicator-image-remove]');
		if (!choose && !remove) return;
		event.preventDefault();
		var root = event.target.closest('[data-cw-lumen-menu-indicator]');
		if (!root) return;
		var input = root.querySelector('[data-cw-lumen-menu-indicator-image-id]');
		var preview = root.querySelector('[data-cw-lumen-menu-indicator-image-preview]');
		if (remove) {
			if (input) input.value = '';
			if (preview) preview.innerHTML = '';
			return;
		}
		if (!window.wp || !wp.media) return;
		var frame = wp.media({ multiple: false, library: { type: 'image' } });
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			if (input) input.value = attachment.id || '';
			if (preview) {
				var src = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
				preview.innerHTML = src ? '<img src="' + String(src).replace(/"/g, '&quot;') + '" alt="">' : '';
			}
		});
		frame.open();
	});
	function syncAll() { document.querySelectorAll('[data-cw-lumen-menu-indicator]').forEach(sync); }
	document.addEventListener('DOMContentLoaded', syncAll);
	if (window.jQuery) { jQuery(document).on('menu-item-added', syncAll); }
})();
