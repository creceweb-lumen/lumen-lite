(function () {
	'use strict';

	var modal = document.querySelector('[data-cw-search-modal]');
	if (!modal) {
		return;
	}

	var previousFocus = null;
	var input = modal.querySelector('[data-cw-search-input]');
	var closeButton = modal.querySelector('[data-cw-search-close]');
	var focusableSelector = 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

	function triggers() {
		return Array.prototype.slice.call(document.querySelectorAll('[data-cw-search-trigger]'));
	}

	function openModal(trigger) {
		previousFocus = trigger || document.activeElement;
		modal.hidden = false;
		document.body.classList.add('cw-lumen-search-modal-open');
		if (trigger) {
			trigger.setAttribute('aria-expanded', 'true');
		}
		window.requestAnimationFrame(function () {
			if (input) {
				input.focus();
			}
		});
	}

	function closeModal() {
		if (modal.hidden) {
			return;
		}
		modal.hidden = true;
		document.body.classList.remove('cw-lumen-search-modal-open');
		triggers().forEach(function (trigger) {
			trigger.setAttribute('aria-expanded', 'false');
		});
		if (previousFocus && typeof previousFocus.focus === 'function') {
			previousFocus.focus();
		}
		previousFocus = null;
	}

	document.addEventListener('click', function (event) {
		var trigger = event.target.closest('[data-cw-search-trigger]');
		if (trigger) {
			event.preventDefault();
			openModal(trigger);
			return;
		}
		if (event.target.closest('[data-cw-search-close]')) {
			closeModal();
			return;
		}
		if (event.target === modal) {
			closeModal();
		}
	});

	document.addEventListener('keydown', function (event) {
		if (modal.hidden) {
			return;
		}
		if (event.key === 'Escape') {
			event.preventDefault();
			closeModal();
			return;
		}
		if (event.key !== 'Tab') {
			return;
		}

		var focusable = Array.prototype.slice.call(modal.querySelectorAll(focusableSelector)).filter(function (element) {
			return element.offsetParent !== null;
		});
		if (!focusable.length) {
			event.preventDefault();
			return;
		}
		var first = focusable[0];
		var last = focusable[focusable.length - 1];
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	});

	if (closeButton) {
		closeButton.setAttribute('aria-controls', 'cw-lumen-search-modal');
	}
}());
