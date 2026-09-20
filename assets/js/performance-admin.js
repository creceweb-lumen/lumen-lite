(function () {
	'use strict';

	var settings = window.cwLumenPerformanceMvp || {};
	var pollTimer = null;
	var startedAt = 0;
	var softRefreshRunning = false;

	function message(key) {
		return settings.messages && settings.messages[key] ? settings.messages[key] : '';
	}

	function formatMessage(template, values) {
		var sequentialIndex = 0;
		return String(template || '').replace(/%([0-9]+)\$d|%d/g, function (match, index) {
			var value = index ? values[parseInt(index, 10) - 1] : values[sequentialIndex++];
			return typeof value === 'undefined' ? match : String(value);
		});
	}

	function request(action, data) {
		var body = new URLSearchParams();
		body.set('action', action);
		body.set('nonce', settings.nonce || '');
		Object.keys(data || {}).forEach(function (key) { body.set(key, data[key]); });
		return fetch(settings.ajaxUrl, {
			method: 'POST', credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (response) { return response.json(); });
	}

	function currentShell() { return document.querySelector('[data-cw-lumen-performance-shell]'); }

	function setUrl(url, push) {
		if (!url || !window.history || !window.history[push ? 'pushState' : 'replaceState']) { return; }
		window.history[push ? 'pushState' : 'replaceState']({}, '', url);
	}

	function switchView(shell, view, updateHistory, href) {
		if (!shell || !view) { return false; }
		var target = shell.querySelector('[data-cw-performance-view-panel="' + view + '"]');
		if (!target) { return false; }
		shell.querySelectorAll('[data-cw-performance-view-panel]').forEach(function (panel) {
			panel.hidden = panel !== target;
		});
		shell.querySelectorAll('[data-cw-performance-tab]').forEach(function (tab) {
			var active = tab.getAttribute('data-cw-performance-tab') === view;
			tab.classList.toggle('nav-tab-active', active);
			tab.setAttribute('aria-selected', active ? 'true' : 'false');
		});
		shell.setAttribute('data-cw-performance-active-view', view);
		if (updateHistory && href) { setUrl(href, true); }
		return true;
	}

	function softRefresh(url, options) {
		options = options || {};
		if (!url || softRefreshRunning) { return Promise.resolve(false); }
		softRefreshRunning = true;
		var y = window.scrollY;
		var oldShell = currentShell();
		return fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) {
				if (!response.ok) { throw new Error(message('refreshFailed')); }
				return response.text();
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var nextShell = doc.querySelector('[data-cw-lumen-performance-shell]');
				if (!oldShell || !nextShell) { throw new Error(message('markupUnavailable')); }
				oldShell.replaceWith(nextShell);
				setUrl(url, !!options.pushHistory);
				initShell(nextShell);
				window.requestAnimationFrame(function () { window.scrollTo(0, y); });
				return true;
			})
			.catch(function () {
				if (options.fallback !== false) { window.location.assign(url); }
				return false;
			})
			.finally(function () { softRefreshRunning = false; });
	}

	function initTabs(shell) {
		shell.querySelectorAll('[data-cw-performance-tab]').forEach(function (tab) {
			tab.addEventListener('click', function (event) {
				var view = tab.getAttribute('data-cw-performance-tab') || '';
				if (switchView(shell, view, true, tab.href)) { event.preventDefault(); }
			});
		});
		shell.querySelectorAll('[data-cw-performance-soft-refresh]').forEach(function (link) {
			link.addEventListener('click', function (event) {
				event.preventDefault();
				softRefresh(link.href, { pushHistory: true });
			});
		});
	}

	function initFilters(shell) {
		shell.querySelectorAll('[data-cw-performance-filters]').forEach(function (bar) {
			var itemType = bar.getAttribute('data-cw-performance-filters') || '';
			if (!itemType) { return; }
			var panel = bar.closest('[data-cw-performance-view-panel]') || shell;
			var items = Array.prototype.slice.call(panel.querySelectorAll('[data-cw-performance-item="' + itemType + '"]'));
			var selects = Array.prototype.slice.call(bar.querySelectorAll('[data-cw-performance-filter]'));
			var count = bar.querySelector('[data-cw-performance-filter-count]');
			var empty = panel.querySelector('[data-cw-performance-filter-empty="' + itemType + '"]');
			var reset = bar.querySelector('[data-cw-performance-filter-reset]');
			selects.forEach(function (select) {
				var key = select.getAttribute('data-cw-performance-filter') || ''; var values = {};
				items.forEach(function (item) { var value=item.getAttribute('data-filter-'+key)||''; var label=item.getAttribute('data-filter-'+key+'-label')||value; if(value&&!values[value])values[value]=label; });
				Object.keys(values).sort(function(a,b){return String(values[a]).localeCompare(String(values[b]));}).forEach(function(value){var option=document.createElement('option');option.value=value;option.textContent=values[value];select.appendChild(option);});
			});
			function apply(){var active={};selects.forEach(function(select){var key=select.getAttribute('data-cw-performance-filter')||'';if(key&&select.value)active[key]=select.value;});var visible=0;items.forEach(function(item){var show=Object.keys(active).every(function(key){return (item.getAttribute('data-filter-'+key)||'')===active[key];});item.hidden=!show;if(show)visible++;});if(count){count.textContent=visible===items.length?formatMessage(message('filterCount'),[visible]):formatMessage(message('filterCountOf'),[visible,items.length]);}if(empty)empty.hidden=visible!==0;if(reset)reset.disabled=Object.keys(active).length===0;}
			selects.forEach(function(select){select.addEventListener('change',apply);});
			if(reset)reset.addEventListener('click',function(){selects.forEach(function(select){select.value='';});apply();});
			apply();
		});
	}

	function initComparisonActions(shell) {
		var buttons=Array.prototype.slice.call(shell.querySelectorAll('[data-cw-lumen-performance-baseline-action]'));
		var progress=shell.querySelector('[data-cw-lumen-performance-comparison-progress]');
		var comparison=shell.querySelector('[data-cw-lumen-performance-comparison-mode]');
		var mode=comparison?(comparison.getAttribute('data-cw-lumen-performance-comparison-mode')||'before_after'):'before_after';
		buttons.forEach(function(control){control.addEventListener('click',function(){var operation=control.getAttribute('data-cw-lumen-performance-baseline-action')||'';var action=operation==='set'?settings.actions.baselineSet:settings.actions.baselineClear;if(!action)return;buttons.forEach(function(b){b.disabled=true;});if(progress)progress.textContent=operation==='set'?message('baselineSet'):message('baselineClear');request(action,{profile:settings.profile||'lite',mode:mode}).then(function(payload){if(!payload||!payload.success||!payload.data||!payload.data.redirect)throw new Error(payload&&payload.data&&payload.data.message?payload.data.message:message('baselineError'));return softRefresh(payload.data.redirect,{pushHistory:false});}).catch(function(error){buttons.forEach(function(b){b.disabled=false;});if(progress)progress.textContent=error.message||message('baselineError');});});});
	}

	function initAnalyzer(shell) {
		var form=shell.querySelector('[data-cw-lumen-performance-form]');
		if(!form||!settings.ajaxUrl||!settings.nonce||!settings.actions)return;
		var input=form.querySelector('input[name="performance_url"]');var button=form.querySelector('button[type="submit"]');var progress=form.querySelector('[data-cw-lumen-performance-progress]');var probe=form.querySelector('[data-cw-lumen-performance-probe]');
		function setProgress(messageText,isError){if(!progress)return;progress.hidden=false;progress.textContent=messageText;progress.classList.toggle('is-error',!!isError);}
		function finish(){button.disabled=false;if(pollTimer){window.clearTimeout(pollTimer);pollTimer=null;}}
		function poll(token){if(Date.now()-startedAt>18000){finish();setProgress(message('timeout'),true);return;}request(settings.actions.result,{token:token}).then(function(payload){if(!payload||!payload.success)throw new Error(payload&&payload.data&&payload.data.message?payload.data.message:message('resultError'));if(payload.data&&payload.data.ready&&payload.data.redirect){finish();setProgress(message('analysisComplete'),false);softRefresh(payload.data.redirect,{pushHistory:false});return;}if(payload.data&&payload.data.expired)throw new Error(message('analysisExpired'));pollTimer=window.setTimeout(function(){poll(token);},450);}).catch(function(error){finish();setProgress(error.message||message('error'),true);});}
		form.addEventListener('submit',function(event){event.preventDefault();var url=input?input.value.trim():'';if(!url)return;button.disabled=true;setProgress(message('running'),false);request(settings.actions.prepare,{url:url,profile:settings.profile||'lite'}).then(function(payload){if(!payload||!payload.success||!payload.data||!payload.data.token||!payload.data.probeUrl)throw new Error(payload&&payload.data&&payload.data.message?payload.data.message:message('prepareError'));startedAt=Date.now();if(probe)probe.src=payload.data.probeUrl;poll(payload.data.token);}).catch(function(error){finish();setProgress(error.message||message('error'),true);});});
	}

	function initShell(shell){if(!shell)return;initTabs(shell);initFilters(shell);initComparisonActions(shell);initAnalyzer(shell);}

	document.addEventListener('DOMContentLoaded',function(){initShell(currentShell());});
	if(document.readyState!=='loading'){initShell(currentShell());}

	window.addEventListener('popstate',function(){var shell=currentShell();if(!shell)return;var params=new URL(window.location.href).searchParams;var view=params.get('perf_view')||'summary';if(!switchView(shell,view,false,'')){softRefresh(window.location.href,{pushHistory:false,fallback:true});}});
}());
