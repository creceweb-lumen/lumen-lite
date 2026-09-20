(function () {
	'use strict';

	function validHex(value) {
		return /^#[0-9a-f]{6}$/i.test(value || '');
	}

	function init(root) {
		var preview = root.querySelector('[data-cw-reading-progress-preview]');
		var previewRange = root.querySelector('[data-cw-reading-progress-preview-range]');
		var previewValue = root.querySelector('[data-cw-reading-progress-preview-value]');
		var position = root.querySelector('[name="reading_progress[position]"]');
		var thickness = root.querySelector('[name="reading_progress[thickness]"]');
		var color = root.querySelector('[name="reading_progress[color]"]');
		var picker = root.querySelector('[data-cw-reading-progress-color-picker]');
		var trackColor = root.querySelector('[name="reading_progress[track_color]"]');
		var trackPicker = root.querySelector('[data-cw-reading-progress-track-color-picker]');
		if (!preview) {
			return;
		}

		function update() {
			var thicknessValue = thickness && !isNaN(parseFloat(thickness.value)) ? Math.max(0, parseFloat(thickness.value)) : 4;
			var progressValue = previewRange && !isNaN(parseFloat(previewRange.value)) ? Math.max(0, Math.min(100, parseFloat(previewRange.value))) : 64;
			preview.setAttribute('data-position', position ? position.value : 'top');
			preview.style.setProperty('--cw-reading-progress-preview-thickness', thicknessValue + 'px');
			preview.style.setProperty('--cw-reading-progress-preview-value', String(progressValue / 100));
			if (previewValue) {
				previewValue.textContent = Math.round(progressValue) + '%';
			}
			if (color && validHex(color.value)) {
				preview.style.setProperty('--cw-reading-progress-preview-color', color.value);
				if (picker) {
					picker.value = color.value.toLowerCase();
				}
			}
			if (trackColor && validHex(trackColor.value)) {
				preview.style.setProperty('--cw-reading-progress-preview-track-color', trackColor.value);
				if (trackPicker) {
					trackPicker.value = trackColor.value.toLowerCase();
				}
			}
		}

		root.addEventListener('input', function (event) {
			if (picker && event.target === picker && color) {
				color.value = picker.value.toLowerCase();
			}
			if (trackPicker && event.target === trackPicker && trackColor) {
				trackColor.value = trackPicker.value.toLowerCase();
			}
			update();
		});
		root.addEventListener('change', update);
		update();
	}

	document.querySelectorAll('[data-cw-reading-progress-settings]').forEach(init);
})();
