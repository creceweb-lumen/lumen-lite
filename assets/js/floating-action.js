(function () {
	'use strict';

	var VISIBLE = 'cw-lumen-lite-action--visible';
	var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function toInt(value, fallback) {
		var parsed = parseInt(value, 10);
		return Number.isFinite(parsed) ? parsed : fallback;
	}

	function init(button) {
		var offset = Math.max(0, toInt(button.getAttribute('data-cw-scroll-offset'), 0));
		var autoHide = button.getAttribute('data-cw-auto-hide') === '1';
		var autoHideDelay = Math.max(1, toInt(button.getAttribute('data-cw-auto-hide-delay'), 4)) * 1000;
		var smooth = button.getAttribute('data-cw-smooth') === '1' && !reducedMotion;
		var scrollTarget = button.getAttribute('data-cw-scroll-target') || '';
		var hideTimer = 0;

		function thresholdMet() {
			return window.scrollY >= offset;
		}

		function show() {
			button.classList.add(VISIBLE);
		}

		function hide() {
			if ( document.activeElement === button ) {
				return;
			}
			button.classList.remove(VISIBLE);
		}

		function clearTimer() {
			if ( hideTimer ) {
				window.clearTimeout(hideTimer);
				hideTimer = 0;
			}
		}

		function scheduleHide() {
			clearTimer();
			if ( autoHide && thresholdMet() ) {
				hideTimer = window.setTimeout(hide, autoHideDelay);
			}
		}

		function refresh() {
			if ( thresholdMet() ) {
				show();
				scheduleHide();
			} else {
				clearTimer();
				hide();
			}
		}

		window.addEventListener('scroll', refresh, { passive: true });
		button.addEventListener('pointerenter', function () {
			clearTimer();
			if ( thresholdMet() ) {
				show();
			}
		});
		button.addEventListener('pointerleave', scheduleHide);
		button.addEventListener('focus', function () {
			clearTimer();
			show();
		});
		button.addEventListener('blur', scheduleHide);

		if ( scrollTarget ) {
			button.addEventListener('click', function (event) {
				if ( ! smooth ) {
					return;
				}
				var target = document.getElementById(scrollTarget);
				if ( ! target ) {
					return;
				}

				event.preventDefault();
				target.scrollIntoView({ behavior: 'smooth', block: 'start' });
				var href = button.getAttribute('href');
				if ( href && href.charAt(0) === '#' && window.history && window.history.pushState ) {
					window.history.pushState(null, '', href);
				}

				var hadTabIndex = target.hasAttribute('tabindex');
				if ( ! hadTabIndex ) {
					target.setAttribute('tabindex', '-1');
				}
				window.setTimeout(function () {
					try {
						target.focus({ preventScroll: true });
					} catch (error) {
						target.focus();
					}
					if ( ! hadTabIndex ) {
						target.addEventListener('blur', function cleanup() {
							target.removeAttribute('tabindex');
						}, { once: true });
					}
				}, 220);
			});
		}

		refresh();
	}

	document.querySelectorAll('[data-cw-lumen-advanced-action]').forEach(init);
})();
