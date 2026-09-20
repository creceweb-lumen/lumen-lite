(function () {
	'use strict';

	var root = document.querySelector('[data-cw-lumen-reading-progress]');
	var bar = root ? root.querySelector('[data-cw-lumen-reading-progress-bar]') : null;
	if (!root || !bar) {
		return;
	}

	var targetSelector = root.getAttribute('data-cw-lumen-reading-progress-target') || '';
	var postId = root.getAttribute('data-cw-lumen-reading-progress-post-id') || '';
	var target = targetSelector ? document.querySelector(targetSelector) : null;

	if (!target && postId) {
		target = document.querySelector('.elementor[data-elementor-id="' + postId + '"]');
	}
	if (!target) {
		target = document.querySelector('#cw-main-content article');
	}
	if (!target) {
		target = document.querySelector('#cw-main-content .entry-content');
	}
	if (!target) {
		return;
	}

	var ticking = false;

	function update() {
		var current = Math.max(0, window.scrollY || window.pageYOffset || 0);
		var rect = target.getBoundingClientRect();
		var targetTop = current + rect.top;
		var targetHeight = Math.max(0, rect.height || (rect.bottom - rect.top) || 0);
		var targetEnd = Math.max(targetTop, targetTop + targetHeight - window.innerHeight);
		var progress = 0;

		if (targetHeight <= window.innerHeight && rect.bottom <= window.innerHeight) {
			progress = 1;
		} else if (current >= targetTop) {
			progress = targetEnd > targetTop ? (current - targetTop) / (targetEnd - targetTop) : 1;
		}

		progress = Math.min(1, Math.max(0, progress));
		bar.style.transform = 'scaleX(' + progress + ')';
		ticking = false;
	}

	function requestUpdate() {
		if (ticking) {
			return;
		}
		ticking = true;
		window.requestAnimationFrame(update);
	}

	window.addEventListener('scroll', requestUpdate, { passive: true });
	window.addEventListener('resize', requestUpdate);
	window.addEventListener('load', requestUpdate);
	requestUpdate();
})();
