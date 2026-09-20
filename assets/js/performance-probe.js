(function () {
	'use strict';

	var settings = window.cwLumenPerformanceProbe || {};
	if (!settings.enabled || !settings.token || !settings.ajaxUrl || !settings.nonce || !settings.action) {
		return;
	}

	function normalizeUrl(value) {
		try {
			var url = new URL(value, window.location.href);
			url.hash = '';
			url.search = '';
			return url.href;
		} catch (error) {
			return '';
		}
	}

	function isLumenSource(url) {
		var value = String(url || '');
		var paths = Array.isArray(settings.allowedPaths) ? settings.allowedPaths : [];
		if (!value || !paths.length) {
			return false;
		}

		if (value.indexOf('/wp-content/plugins/creceweb-lumen-lite/assets/js/performance-probe.js') !== -1) {
			return false;
		}

		try {
			var parsed = new URL(value, window.location.href);
			return paths.some(function (path) {
				return parsed.pathname.indexOf(String(path || '')) === 0;
			});
		} catch (error) {
			return false;
		}
	}

	function collect() {
		if (!window.performance || typeof window.performance.getEntriesByType !== 'function') {
			return [];
		}

		return window.performance.getEntriesByType('resource')
			.filter(function (entry) {
				return entry && isLumenSource(entry.name);
			})
			.map(function (entry) {
				return {
					url: normalizeUrl(entry.name),
					transferSize: Number(entry.transferSize || 0),
					encodedBodySize: Number(entry.encodedBodySize || 0),
					decodedBodySize: Number(entry.decodedBodySize || 0),
					duration: Number(entry.duration || 0),
					initiatorType: String(entry.initiatorType || '')
				};
			});
	}

	function send(resources) {
		var body = new URLSearchParams();
		body.set('action', settings.action);
		body.set('nonce', settings.nonce);
		body.set('token', settings.token);
		body.set('resources', JSON.stringify(resources));

		return fetch(settings.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			keepalive: true,
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: body.toString()
		}).catch(function () {});
	}

	function flush() {
		send(collect());
	}

	if (document.readyState === 'complete') {
		window.setTimeout(flush, 150);
	} else {
		window.addEventListener('load', function () {
			window.setTimeout(flush, 150);
		}, { once: true });
	}
}());
