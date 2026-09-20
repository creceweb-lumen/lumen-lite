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
})();
