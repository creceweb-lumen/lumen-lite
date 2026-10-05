(function (wp, settings) {
	'use strict';

	if (
		!wp || !wp.plugins || !wp.editor || !wp.element || !wp.components ||
		!wp.data || !wp.blocks || typeof wp.element.useEffect !== 'function' ||
		!settings || !Array.isArray(settings.patterns)
	) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var PluginSidebar = wp.editor.PluginSidebar;
	var PluginMoreMenuItem = wp.editor.PluginMoreMenuItem;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useMemo = wp.element.useMemo;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var useCommand = wp.commands && wp.commands.useCommand;
	var Modal = wp.components.Modal;
	var Button = wp.components.Button;
	var SearchControl = wp.components.SearchControl;
	var Notice = wp.components.Notice;
	var parse = wp.blocks.parse;
	var BlockPreview = wp.blockEditor && wp.blockEditor.BlockPreview;
	var select = wp.data.select;
	var dispatch = wp.data.dispatch;

	var labels = settings.labels || {};
	var families = Array.isArray(settings.families) ? settings.families : [];
	var kits = Array.isArray(settings.kits) ? settings.kits : [];
	var sources = Array.isArray(settings.sources) ? settings.sources : [];
	var types = Array.isArray(settings.types) ? settings.types : [];
	var configPreset = settings.configPreset || {};
	var pageTemplate = settings.pageTemplate || {};
	var pendingKitInsertionKey = 'cwLumenLitePendingKitInsertionV1';
	var pendingKitInsertionMaxAge = 5 * 60 * 1000;

	function writePendingKitInsertion(pattern, postId) {
		if (!window.sessionStorage || !pattern || !pattern.slug || !postId) {
			return false;
		}
		try {
			window.sessionStorage.setItem(pendingKitInsertionKey, JSON.stringify({
				patternSlug: pattern.slug,
				postId: parseInt(postId, 10),
				createdAt: Date.now()
			}));
			return true;
		} catch (error) {
			return false;
		}
	}

	function readPendingKitInsertion() {
		var raw;
		var data;
		if (!window.sessionStorage) {
			return null;
		}
		try {
			raw = window.sessionStorage.getItem(pendingKitInsertionKey);
			if (!raw) {
				return null;
			}
			data = JSON.parse(raw);
			if (!data || !data.patternSlug || !data.postId || !data.createdAt || (Date.now() - data.createdAt) > pendingKitInsertionMaxAge) {
				window.sessionStorage.removeItem(pendingKitInsertionKey);
				return null;
			}
			return data;
		} catch (error) {
			window.sessionStorage.removeItem(pendingKitInsertionKey);
			return null;
		}
	}

	function clearPendingKitInsertion() {
		if (!window.sessionStorage) {
			return;
		}
		try {
			window.sessionStorage.removeItem(pendingKitInsertionKey);
		} catch (error) {
			// Storage cleanup is best effort only.
		}
	}

	function navigateToSavedPostEditor(postId) {
		var baseUrl = pageTemplate.editPostUrl || '';
		var target;

		if (!postId || !baseUrl) {
			return false;
		}

		target = baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') + 'post=' + encodeURIComponent(String(postId)) + '&action=edit';
		window.location.assign(target);
		return true;
	}

	function saveEditorBeforeKitReload() {
		var editorSelect = select('core/editor');
		var editorDispatch = dispatch('core/editor');
		var status;

		if (!editorSelect || !editorDispatch || typeof editorDispatch.savePost !== 'function') {
			return Promise.reject(new Error(labels.insertWithPresetSaveError || 'The current page could not be saved before reloading.'));
		}

		status = typeof editorSelect.getEditedPostAttribute === 'function'
			? editorSelect.getEditedPostAttribute('status')
			: '';
		if (!status || status === 'auto-draft') {
			editorDispatch.editPost({ status: 'draft' });
		}

		return Promise.resolve(editorDispatch.savePost()).then(function () {
			var postId = typeof editorSelect.getCurrentPostId === 'function'
				? parseInt(editorSelect.getCurrentPostId() || 0, 10)
				: 0;
			if (!postId) {
				throw new Error(labels.insertWithPresetSaveError || 'The current page could not be saved before reloading.');
			}
			return postId;
		});
	}

	// PluginSidebar is the public SlotFill that adds a discoverable icon beside the
	// editor settings controls. Keep PluginMoreMenuItem only as a safe fallback for
	// editor screens that do not expose the sidebar SlotFill.
	if (typeof PluginSidebar !== 'function' && typeof PluginMoreMenuItem !== 'function') {
		return;
	}

	function normalize(value) {
		var text = String(value || '').toLowerCase();
		if (text.normalize) {
			text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
		}
		return text;
	}

	function familyForExactQuery(query) {
		var normalizedQuery = normalize(query).trim();
		if (!normalizedQuery) {
			return '';
		}

		var match = families.find(function (item) {
			return normalize(item.label).trim() === normalizedQuery || normalize(item.key).trim() === normalizedQuery;
		});

		return match ? match.key : '';
	}

	function matchesPattern(pattern, type, family, source, query) {
		if ((pattern.type || 'section') !== type) {
			return false;
		}
		if (type === 'section' && family !== 'all' && pattern.family !== family) {
			return false;
		}
		if (source !== 'all' && pattern.source !== source) {
			return false;
		}
		if (!query) {
			return true;
		}

		var exactFamily = type === 'section' ? familyForExactQuery(query) : '';
		if (exactFamily) {
			return pattern.family === exactFamily;
		}

		var haystack = [
			pattern.title,
			pattern.description,
			pattern.use,
			pattern.familyLabel,
			(pattern.keywords || []).join(' '),
			pattern.slug
		].join(' ');

		return normalize(haystack).indexOf(normalize(query)) !== -1;
	}


	function matchesKit(kit, source, query) {
		if (source !== 'all' && kit.source !== source) {
			return false;
		}
		if (!query) {
			return true;
		}

		var haystack = [
			kit.title,
			kit.description,
			kit.use,
			(kit.keywords || []).join(' '),
			kit.slug
		].join(' ');

		return normalize(haystack).indexOf(normalize(query)) !== -1;
	}

	function insertionPoint() {
		var editor = select('core/block-editor');
		var selected = editor.getSelectedBlockClientId();

		if (selected && editor.getBlockHierarchyRootClientId) {
			var anchor = editor.getBlockHierarchyRootClientId(selected);
			var rootClientId = editor.getBlockRootClientId(anchor) || '';
			return {
				rootClientId: rootClientId,
				index: editor.getBlockIndex(anchor, rootClientId) + 1
			};
		}

		if (editor.getBlockInsertionPoint) {
			var point = editor.getBlockInsertionPoint();
			if (point && typeof point.index === 'number') {
				return {
					rootClientId: point.rootClientId || '',
					index: point.index
				};
			}
		}

		return {
			rootClientId: '',
			index: editor.getBlockCount('')
		};
	}

	function insertPattern(pattern) {
		var blocks;
		var point;
		var editorSelect;
		var beforeCount;
		var afterCount;

		try {
			blocks = parse(pattern.content || '');
		} catch (error) {
			return { ok: false, message: labels.parseError || 'The pattern could not be prepared.' };
		}

		if (!blocks || !blocks.length) {
			return { ok: false, message: labels.parseError || 'The pattern could not be prepared.' };
		}

		point = insertionPoint();
		editorSelect = select('core/block-editor');
		beforeCount = editorSelect.getBlockCount(point.rootClientId);

		dispatch('core/block-editor').insertBlocks(
			blocks,
			point.index,
			point.rootClientId,
			true,
			0,
			{ source: 'creceweb-lumen-library' }
		);

		afterCount = select('core/block-editor').getBlockCount(point.rootClientId);
		if (afterCount <= beforeCount) {
			return {
				ok: false,
				message: labels.insertError || 'The pattern could not be inserted at this location.'
			};
		}

		if (blocks[0] && blocks[0].clientId && dispatch('core/block-editor').flashBlock) {
			dispatch('core/block-editor').flashBlock(blocks[0].clientId, 1200);
		}

		return {
			ok: true,
			message: labels.insertSuccess || 'Pattern inserted successfully.'
		};
	}

	function applyRecommendedPageTemplate(pattern) {
		var editorSelect;
		var editorDispatch;
		var coreDispatch;
		var postType;
		var postId;
		var body;

		if (!pattern || pattern.type !== 'page' || !pattern.template) {
			return Promise.resolve({ applied: false, skipped: true });
		}

		editorSelect = select('core/editor');
		editorDispatch = dispatch('core/editor');
		coreDispatch = dispatch('core');

		if (!editorSelect || !editorDispatch || typeof editorDispatch.editPost !== 'function') {
			return Promise.resolve({ applied: false, skipped: false });
		}

		postType = typeof editorSelect.getCurrentPostType === 'function'
			? editorSelect.getCurrentPostType()
			: '';
		if (!postType) {
			postType = pageTemplate.currentType || '';
		}

		postId = typeof editorSelect.getCurrentPostId === 'function'
			? editorSelect.getCurrentPostId()
			: 0;
		if (!postId) {
			postId = parseInt(pageTemplate.currentPostId || 0, 10);
		}

		if (postType !== 'page' || !postId) {
			return Promise.resolve({ applied: false, skipped: false });
		}

		/* Keep the editor/entity state synchronized immediately. */
		editorDispatch.editPost({ template: pattern.template });
		if (coreDispatch && typeof coreDispatch.editEntityRecord === 'function') {
			coreDispatch.editEntityRecord('postType', 'page', postId, { template: pattern.template });
		}

		if (!pageTemplate.ajaxUrl || !pageTemplate.nonce || typeof window.fetch !== 'function') {
			return Promise.resolve({ applied: false, skipped: false });
		}

		body = new URLSearchParams();
		body.append('action', 'cw_lumen_lite_apply_page_template');
		body.append('nonce', pageTemplate.nonce);
		body.append('post_id', String(postId));
		body.append('template', pattern.template);

		return window.fetch(pageTemplate.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (response) {
			return response.json();
		}).then(function (response) {
			if (!response || !response.success) {
				throw new Error(
					response && response.data && response.data.message
						? response.data.message
						: (labels.pageTemplateFailed || 'The recommended page template could not be saved.')
				);
			}

			editorDispatch.editPost({ template: response.data.template || pattern.template });
			if (coreDispatch && typeof coreDispatch.editEntityRecord === 'function') {
				coreDispatch.editEntityRecord(
					'postType',
					'page',
					postId,
					{ template: response.data.template || pattern.template }
				);
			}

			return { applied: true, skipped: false };
		}).catch(function (error) {
			return { applied: false, skipped: false, error: error };
		});
	}

	function fallbackPreview(pattern, eager) {
		return createElement('img', {
			src: pattern.preview,
			alt: '',
			width: 960,
			height: 540,
			loading: eager ? 'eager' : 'lazy',
			decoding: 'async'
		});
	}

	function livePreview(pattern) {
		var blocks;

		if (typeof BlockPreview !== 'function') {
			return null;
		}

		try {
			blocks = parse(pattern.content || '');
		} catch (error) {
			return null;
		}

		if (!blocks || !blocks.length) {
			return null;
		}

		return createElement(BlockPreview, {
			blocks: blocks,
			viewportWidth: 1200
		});
	}

	function PatternPreview(props) {
		var preview = props.live ? livePreview(props.pattern) : null;

		return createElement(
			'div',
			{ className: 'cw-lumen-library__image-frame' },
			preview || fallbackPreview(props.pattern, props.eager)
		);
	}

	function PatternCard(props) {
		var pattern = props.pattern;
		return createElement(
			'article',
			{ className: 'cw-lumen-library__card' },
			createElement(PatternPreview, { pattern: pattern }),
			createElement(
				'div',
				{ className: 'cw-lumen-library__card-body' },
				createElement(
					'div',
					{ className: 'cw-lumen-library__meta' },
					createElement('span', { className: 'cw-lumen-library__category' }, pattern.familyLabel)
				),
				createElement('h3', null, pattern.title),
				createElement('p', null, pattern.description),
				createElement(
					'div',
					{ className: 'cw-lumen-library__card-actions' },
					createElement(Button, {
						variant: 'tertiary',
						onClick: function () { props.onPreview(pattern); }
					}, labels.preview || 'Preview'),
					createElement(Button, {
						variant: 'primary',
						onClick: function () { props.onInsert(pattern); }
					}, pattern.type === 'page' ? (labels.insertPage || labels.insert || 'Insert') : (labels.insert || 'Insert'))
				)
			)
		);
	}

	function PatternDetail(props) {
		var pattern = props.pattern;
		return createElement(
			'div',
			{ className: 'cw-lumen-library__detail' },
			createElement(
				'div',
				{ className: 'cw-lumen-library__detail-toolbar' },
				createElement(Button, {
					variant: 'tertiary',
					icon: 'arrow-left-alt2',
					onClick: props.onBack
				}, labels.back || 'Back to the Library')
			),
			createElement(
				'div',
				{ className: 'cw-lumen-library__detail-grid' },
				createElement(PatternPreview, { pattern: pattern, eager: true, live: true }),
				createElement(
					'div',
					{ className: 'cw-lumen-library__detail-copy' },
					createElement(
						'div',
						{ className: 'cw-lumen-library__meta' },
						createElement('span', { className: 'cw-lumen-library__category' }, pattern.familyLabel)
					),
					createElement('h2', null, pattern.title),
					createElement('p', null, pattern.description),
					createElement('h3', null, labels.recommendedUse || 'Recommended use'),
					createElement('p', null, pattern.use),
					pattern.type === 'page' && pattern.template ? createElement(
						'p',
						{ className: 'cw-lumen-library__kit-help' },
						labels.pageTemplateHelp || ''
					) : null,
					createElement(Button, {
						variant: 'primary',
						size: 'compact',
						onClick: function () { props.onInsert(pattern); }
					}, pattern.type === 'page' ? (labels.insertPage || labels.insert || 'Insert') : (labels.insertPattern || 'Insert pattern'))
				)
			)
		);
	}


	function KitCard(props) {
		var kit = props.kit;
		return createElement(
			'article',
			{ className: 'cw-lumen-library__card' },
			fallbackPreview(kit),
			createElement(
				'div',
				{ className: 'cw-lumen-library__card-body' },
				createElement('div', { className: 'cw-lumen-library__meta' }, createElement('span', { className: 'cw-lumen-library__category' }, labels.kitType || 'Kit')),
				createElement('h3', null, kit.title),
				createElement('p', null, kit.description),
				createElement(
					'div',
					{ className: 'cw-lumen-library__card-actions' },
					createElement(Button, { variant: 'primary', onClick: function () { props.onPreview(kit); } }, labels.preview || 'Preview')
				)
			)
		);
	}

	function KitDetail(props) {
		var kit = props.kit;
		var included = (kit.items || []).map(function (id) {
			return settings.patterns.find(function (pattern) { return pattern.slug === id; });
		}).filter(Boolean);
		return createElement(
			'div',
			{ className: 'cw-lumen-library__detail cw-lumen-library__kit-detail' },
			createElement(
				'div',
				{ className: 'cw-lumen-library__detail-toolbar' },
				createElement(Button, { variant: 'tertiary', icon: 'arrow-left-alt2', onClick: props.onBack }, labels.back || 'Back to the Library')
			),
			createElement(
				'div',
				{ className: 'cw-lumen-library__kit-detail-grid' },
				createElement(
					'div',
					{ className: 'cw-lumen-library__image-frame cw-lumen-library__kit-preview' },
					fallbackPreview(kit, true)
				),
				createElement(
					'div',
					{ className: 'cw-lumen-library__kit-actions-panel' },
					createElement(
						'div',
						{ className: 'cw-lumen-library__kit-summary' },
						createElement('div', { className: 'cw-lumen-library__meta' }, createElement('span', { className: 'cw-lumen-library__category' }, labels.kitType || 'Kit')),
						createElement('h2', null, kit.title),
						createElement('p', null, kit.description)
					),
					kit.hasConfigPreset ? createElement(
						'div',
						{ className: 'cw-lumen-library__kit-preset' },
						createElement('h3', null, labels.recommendedDesign || 'Recommended design'),
						createElement('p', null, labels.configPresetHelp || ''),
						createElement(
							'div',
							{ className: 'cw-lumen-library__card-actions' },
							createElement(Button, {
								variant: 'secondary',
								isBusy: props.presetBusy,
								disabled: props.presetBusy,
								onClick: function () { props.onPreset(kit, 'apply'); }
							}, labels.applyPreset || 'Apply recommended design'),
							props.hasSnapshot ? createElement(Button, {
								variant: 'tertiary',
								isBusy: props.presetBusy,
								disabled: props.presetBusy,
								onClick: function () { props.onPreset(kit, 'restore'); }
							}, labels.restorePreset || 'Restore previous design') : null
						),
						createElement('p', { className: 'cw-lumen-library__kit-help' }, labels.presetExportHelp || '')
					) : null
				)
			),
			createElement(
				'section',
				{ className: 'cw-lumen-library__kit-pages-section' },
				createElement(
					'div',
					{ className: 'cw-lumen-library__kit-pages-header' },
					createElement('h3', null, labels.individualPages || 'Insert individual pages'),
					createElement('p', null, labels.individualPagesHelp || labels.kitHelp || '')
				),
				createElement(
					'div',
					{ className: 'cw-lumen-library__kit-items-grid' },
					included.map(function (pattern) {
						return createElement(
							'div',
							{ className: 'cw-lumen-library__kit-item', key: pattern.slug },
							createElement('div', { className: 'cw-lumen-library__kit-item-copy' }, createElement('strong', null, pattern.title), createElement('span', null, pattern.description)),
							createElement(
								'div',
								{ className: 'cw-lumen-library__card-actions' },
								createElement(Button, {
									variant: 'secondary',
									size: 'compact',
									disabled: props.presetBusy,
									onClick: function () { props.onInsert(pattern); }
								}, pattern.type === 'page' ? (labels.insertPage || 'Insert page') : (labels.insertPattern || 'Insert pattern')),
								kit.hasConfigPreset && pattern.type === 'page' ? createElement(Button, {
									variant: 'primary',
									size: 'compact',
									isBusy: props.presetBusy,
									disabled: props.presetBusy,
									onClick: function () { props.onInsertWithPreset(kit, pattern); }
								}, labels.insertWithPreset || 'Insert + recommended design') : null
							)
						);
					})
				)
			)
		);
	}


	function LibraryModal(props) {
		var _useState = useState(props.initialType || 'section');
		var activeType = _useState[0];
		var setActiveType = _useState[1];
		var _useState2 = useState(props.initialFamily || 'all');
		var activeFamily = _useState2[0];
		var setActiveFamily = _useState2[1];
		var _useState3 = useState(props.initialSource || 'all');
		var activeSource = _useState3[0];
		var setActiveSource = _useState3[1];
		var _useState4 = useState('');
		var query = _useState4[0];
		var setQuery = _useState4[1];
		var _useState5 = useState(null);
		var detail = _useState5[0];
		var setDetail = _useState5[1];
		var _useState6 = useState(null);
		var notice = _useState6[0];
		var setNotice = _useState6[1];
		var _useState7 = useState(!!configPreset.hasSnapshot);
		var hasSnapshot = _useState7[0];
		var setHasSnapshot = _useState7[1];
		var _useStatePreset = useState(false);
		var presetBusy = _useStatePreset[0];
		var setPresetBusy = _useStatePreset[1];


		function createReloadNotice(message) {
			var notices = wp.data.dispatch('core/notices');

			if (!notices || typeof notices.createInfoNotice !== 'function') {
				return;
			}

			notices.createInfoNotice(message || labels.presetReloadNotice || 'Reload the editor to refresh the preview.', {
				type: 'snackbar',
				id: 'cw-lumen-library-reload-editor',
				actions: [{
					label: labels.reloadEditor || 'Reload editor',
					onClick: function () { window.location.reload(); }
				}]
			});
		}

		var visibleItems = useMemo(function () {
			if (activeType === 'kit') {
				return kits.filter(function (kit) { return matchesKit(kit, activeSource, query); });
			}
			return settings.patterns.filter(function (pattern) {
				return matchesPattern(pattern, activeType, activeFamily, activeSource, query);
			});
		}, [activeType, activeFamily, activeSource, query]);


		function handleInsert(pattern, options) {
			var result = insertPattern(pattern);
			var notices = wp.data.dispatch('core/notices');
			var insertOptions = options || {};

			if (!result.ok) {
				setNotice(result.message);
				return;
			}

			props.onClose();

			applyRecommendedPageTemplate(pattern).then(function (templateResult) {
				var successMessage = result.message;

				if (templateResult.applied) {
					successMessage = labels.pageTemplateApplied || result.message;
				} else if (!templateResult.skipped && pattern.type === 'page' && pattern.template) {
					if (notices && notices.createWarningNotice) {
						notices.createWarningNotice(
							templateResult.error && templateResult.error.message
								? templateResult.error.message
								: (labels.pageTemplateFailed || result.message),
							{ type: 'snackbar', id: 'cw-lumen-library-template-warning' }
						);
					}
				}

				if (notices && notices.createSuccessNotice) {
					notices.createSuccessNotice(successMessage, {
						type: 'snackbar',
						id: 'cw-lumen-library-inserted'
					});
				}

				if (insertOptions.presetApplied) {
					createReloadNotice(labels.insertWithPresetReloadNotice || labels.presetReloadNotice);
				}
			});
		}


		function requestPreset(kit, operation, askConfirmation, options) {
			var confirmLabel = operation === 'restore' ? labels.restorePresetConfirm : labels.applyPresetConfirm;
			var body;
			var requestOptions = options || {};

			if (askConfirmation && confirmLabel && !window.confirm(confirmLabel)) {
				return Promise.resolve(false);
			}
			if (!configPreset.ajaxUrl || !configPreset.nonce || typeof window.fetch !== 'function') {
				setNotice(labels.presetRequestError || 'The design preset request could not be completed.');
				return Promise.resolve(false);
			}

			body = new URLSearchParams();
			body.append('action', 'cw_lumen_lite_kit_config_preset');
			body.append('nonce', configPreset.nonce);
			body.append('operation', operation);
			body.append('kit_id', kit.slug || '');
			setPresetBusy(true);

			return window.fetch(configPreset.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			}).then(function (response) {
				return response.json();
			}).then(function (response) {
				var data = response && response.data ? response.data : {};
				if (!response || !response.success) {
					throw new Error(data.message || labels.presetRequestError || 'The design preset request could not be completed.');
				}
				if (data.resetColorModePreference) {
					try {
						window.localStorage.removeItem('cw_lumen_color_mode');
					} catch (error) {}
				}
				setHasSnapshot(!!data.hasSnapshot);
				if (!requestOptions.silentSuccess) {
					setNotice({ status: 'success', message: data.message || '' });
				}
				if (requestOptions.reloadNotice) {
					createReloadNotice(requestOptions.reloadMessage || (operation === 'restore' ? labels.presetRestoreReloadNotice : labels.presetReloadNotice));
				}
				return true;
			}).catch(function (error) {
				setNotice(error && error.message ? error.message : (labels.presetRequestError || 'The design preset request could not be completed.'));
				return false;
			}).finally(function () {
				setPresetBusy(false);
			});
		}

		function handlePreset(kit, operation) {
			return requestPreset(kit, operation, true, { reloadNotice: true });
		}

		function handleInsertWithPreset(kit, pattern) {
			var confirmLabel = labels.insertWithPresetConfirm || labels.applyPresetConfirm;
			var preparedPostId = 0;

			if (confirmLabel && !window.confirm(confirmLabel)) {
				return;
			}

			setPresetBusy(true);
			setNotice({ status: 'info', message: labels.insertWithPresetPreparing || 'Saving the current page and preparing the recommended design…' });

			saveEditorBeforeKitReload().then(function (postId) {
				preparedPostId = postId;
				return requestPreset(kit, 'apply', false, { silentSuccess: true });
			}).then(function (applied) {
				if (!applied) {
					setPresetBusy(false);
					return;
				}

				if (!writePendingKitInsertion(pattern, preparedPostId)) {
					setPresetBusy(false);
					setNotice(labels.presetRequestError || 'The combined Kit action could not be prepared.');
					return;
				}

				if (!navigateToSavedPostEditor(preparedPostId)) {
					clearPendingKitInsertion();
					setPresetBusy(false);
					setNotice(labels.insertWithPresetResumeError || 'The recommended design was applied, but WordPress could not reopen the saved draft for automatic insertion.');
				}
			}).catch(function (error) {
				setPresetBusy(false);
				setNotice(error && error.message ? error.message : (labels.insertWithPresetSaveError || 'The current page could not be saved before reloading.'));
			});
		}


		return createElement(
			Modal,
			{
				title: labels.title || 'Lumen Library',
				onRequestClose: props.onClose,
				className: 'cw-lumen-library',
				overlayClassName: 'cw-lumen-library-overlay',
				shouldCloseOnClickOutside: false
			},
			notice ? createElement(Notice, {
				status: typeof notice === 'object' && notice.status ? notice.status : 'error',
				isDismissible: true,
				onRemove: function () { setNotice(null); }
			}, typeof notice === 'object' ? notice.message : notice) : null,
			detail ? (detail.type === 'kit' ? createElement(KitDetail, {
				kit: detail,
				onBack: function () { setDetail(null); },
				onInsert: handleInsert,
				onInsertWithPreset: handleInsertWithPreset,
				onPreset: handlePreset,
				hasSnapshot: hasSnapshot,
				presetBusy: presetBusy
			}) : createElement(PatternDetail, {
				pattern: detail,
				onBack: function () { setDetail(null); },
				onInsert: handleInsert
			})) : createElement(
				Fragment,
				null,
				createElement(
					'div',
					{ className: 'cw-lumen-library__controls' },
					createElement(
						'div',
						{ className: 'cw-lumen-library__types', role: 'group', 'aria-label': labels.title || 'Lumen Library' },
						types.map(function (type) {
							return createElement(Button, {
								key: type.key,
								variant: activeType === type.key ? 'primary' : 'tertiary',
								isPressed: activeType === type.key,
								onClick: function () { setActiveType(type.key); setDetail(null); setActiveFamily('all'); }
							}, type.label);
						})
					),
					createElement(SearchControl, {
						label: labels.searchLabel || 'Search patterns',
						placeholder: labels.searchPlaceholder || 'E.g. hero, services, contact',
						value: query,
						onChange: setQuery,
						className: 'cw-lumen-library__search'
					}),
					activeType === 'section' ? createElement(
						'div',
						{ className: 'cw-lumen-library__filters', role: 'group', 'aria-label': labels.filterLabel || 'Filter patterns' },
						families.map(function (family) {
							return createElement(Button, {
								key: family.key,
								variant: activeFamily === family.key ? 'primary' : 'secondary',
								isPressed: activeFamily === family.key,
								onClick: function () { setActiveFamily(family.key); }
							}, family.label);
						})
					) : null,
					sources.length > 2 ? createElement(
						'div',
						{ className: 'cw-lumen-library__filters cw-lumen-library__filters--source', role: 'group', 'aria-label': labels.sourceFilterLabel || 'Filter by source' },
						sources.map(function (source) {
							return createElement(Button, {
								key: source.key,
								variant: activeSource === source.key ? 'primary' : 'secondary',
								isPressed: activeSource === source.key,
								onClick: function () { setActiveSource(source.key); }
							}, source.label);
						})
					) : null
				),
				createElement(
					'div',
					{ className: 'cw-lumen-library__summary', 'aria-live': 'polite' },
					visibleItems.length === 1 ? (labels.oneResult || '') : String(visibleItems.length) + ' ' + (labels.results || '')
				),
				visibleItems.length ? createElement(
					'div',
					{ className: 'cw-lumen-library__grid' },
					visibleItems.map(function (item) {
						if (activeType === 'kit') {
							return createElement(KitCard, { key: item.slug, kit: item, onPreview: setDetail });
						}
						return createElement(PatternCard, {
							key: item.slug,
							pattern: item,
							onPreview: setDetail,
							onInsert: handleInsert
						});
					})
				) : createElement(
					'div',
					{ className: 'cw-lumen-library__empty' },
					createElement('h3', null, labels.noResultsTitle || 'No patterns found'),
					createElement('p', null, labels.noResults || 'Try another search or select All.')
				)

			)
		);
	}


	function LibraryLauncher(props) {
		return createElement(
			'div',
			{ className: 'cw-lumen-library-launcher' },
			createElement(
				'div',
				{ className: 'cw-lumen-library-launcher__intro' },
				createElement('span', { className: 'cw-lumen-library-launcher__icon', 'aria-hidden': 'true' }, '▦'),
				createElement(
					'div',
					null,
					createElement('h2', null, labels.sidebarTitle || 'Visual Library'),
					createElement('p', null, labels.sidebarDescription || 'Search, filter, and insert predesigned Lumen sections.')
				)
			),
			createElement('p', { className: 'cw-lumen-library-launcher__count' }, labels.patternCount || (String(settings.patterns.length + kits.length) + ' available Library items')),
			createElement(Button, {
				variant: 'primary',
				className: 'cw-lumen-library-launcher__primary',
				onClick: function () { props.onOpen('all', 'all', 'section'); }
			}, labels.openLibrary || 'Open visual Library'),
			createElement(
				'div',
				{ className: 'cw-lumen-library-launcher__types' },
				types.map(function (type) {
					return createElement(Button, { key: type.key, variant: 'secondary', onClick: function () { props.onOpen('all', 'all', type.key); } }, type.label);
				})
			),
			createElement('h3', { className: 'cw-lumen-library-launcher__quick-title' }, labels.quickAccess || 'Quick access'),
			createElement(
				'div',
				{ className: 'cw-lumen-library-launcher__quick' },
				families.filter(function (family) { return family.key !== 'all'; }).map(function (family) {
					return createElement(Button, {
						key: family.key,
						variant: 'secondary',
						onClick: function () { props.onOpen(family.key, 'all', 'section'); }
					}, family.label);
				})
			),
			sources.length > 2 ? createElement(
				'div',
				{ className: 'cw-lumen-library-launcher__quick cw-lumen-library-launcher__quick--sources' },
				sources.filter(function (source) { return source.key !== 'all'; }).map(function (source) {
					return createElement(Button, {
						key: source.key,
						variant: 'secondary',
						onClick: function () { props.onOpen('all', source.key, 'section'); }
					}, source.label + (source.count ? ' · ' + String(source.count) : ''));
				})
			) : null,
			createElement('p', { className: 'cw-lumen-library-launcher__hint' }, labels.commandHint || 'You can also open it from the command palette with Ctrl/Cmd + K.')
		);
	}


	function LibraryPlugin() {
		var _useState8 = useState(false);
		var isOpen = _useState8[0];
		var setIsOpen = _useState8[1];
		var _useState9 = useState('section');
		var initialType = _useState9[0];
		var setInitialType = _useState9[1];
		var _useState10 = useState('all');
		var initialFamily = _useState10[0];
		var setInitialFamily = _useState10[1];
		var _useState11 = useState('all');
		var initialSource = _useState11[0];
		var setInitialSource = _useState11[1];
		var label = labels.open || 'Lumen Library';
		var open = function (family, source, type) {
			setInitialType(typeof type === 'string' ? type : 'section');
			setInitialFamily(typeof family === 'string' ? family : 'all');
			setInitialSource(typeof source === 'string' ? source : 'all');
			setIsOpen(true);
		};
		var close = function () { setIsOpen(false); };

		useEffect(function () {
			if (settings.autoOpen) { open('all', 'all', 'section'); }
		}, []);

		useEffect(function () {
			var pending = readPendingKitInsertion();
			var cancelled = false;
			var attempts = 0;
			var maxAttempts = 75;

			if (!pending) {
				return undefined;
			}

			function tryPendingInsertion() {
				var editorSelect = select('core/editor');
				var blockEditorSelect = select('core/block-editor');
				var currentPostId = editorSelect && typeof editorSelect.getCurrentPostId === 'function'
					? parseInt(editorSelect.getCurrentPostId() || 0, 10)
					: 0;
				var pattern = settings.patterns.find(function (item) { return item.slug === pending.patternSlug; });
				var result;

				if (cancelled) {
					return;
				}

				if (currentPostId && currentPostId !== parseInt(pending.postId, 10)) {
					attempts += 1;
					if (attempts < 10) {
						window.setTimeout(tryPendingInsertion, 200);
						return;
					}
					clearPendingKitInsertion();
					if (wp.data.dispatch('core/notices') && wp.data.dispatch('core/notices').createWarningNotice) {
						wp.data.dispatch('core/notices').createWarningNotice(
							labels.insertWithPresetResumeError || 'The recommended design was applied, but WordPress reopened a different page than the saved draft.',
							{ type: 'snackbar', id: 'cw-lumen-library-pending-post-mismatch' }
						);
					}
					return;
				}

				if (!currentPostId || !blockEditorSelect || typeof blockEditorSelect.getBlockCount !== 'function' || !pattern) {
					attempts += 1;
					if (attempts < maxAttempts) {
						window.setTimeout(tryPendingInsertion, 200);
						return;
					}
					clearPendingKitInsertion();
					if (wp.data.dispatch('core/notices') && wp.data.dispatch('core/notices').createWarningNotice) {
						wp.data.dispatch('core/notices').createWarningNotice(
							labels.insertWithPresetAutoError || 'The editor reloaded with the recommended design, but the selected page could not be inserted automatically.',
							{ type: 'snackbar', id: 'cw-lumen-library-pending-insert-error' }
						);
					}
					return;
				}

				result = insertPattern(pattern);
				if (!result.ok) {
					attempts += 1;
					if (attempts < maxAttempts) {
						window.setTimeout(tryPendingInsertion, 200);
						return;
					}
					clearPendingKitInsertion();
					if (wp.data.dispatch('core/notices') && wp.data.dispatch('core/notices').createWarningNotice) {
						wp.data.dispatch('core/notices').createWarningNotice(
							labels.insertWithPresetAutoError || result.message,
							{ type: 'snackbar', id: 'cw-lumen-library-pending-insert-error' }
						);
					}
					return;
				}

				clearPendingKitInsertion();
				applyRecommendedPageTemplate(pattern).then(function () {
					var notices = wp.data.dispatch('core/notices');
					if (notices && notices.createSuccessNotice) {
						notices.createSuccessNotice(
							labels.insertWithPresetSuccess || 'Page inserted with the recommended design.',
							{ type: 'snackbar', id: 'cw-lumen-library-pending-inserted' }
						);
					}
				});
			}

			window.setTimeout(tryPendingInsertion, 250);
			return function () { cancelled = true; };
		}, []);

		if (typeof useCommand === 'function') {
			useCommand({
				name: 'creceweb-lumen/open-visual-library',
				label: labels.commandLabel || 'Open Lumen Library',
				category: 'view',
				keywords: Array.isArray(settings.commandKeywords) ? settings.commandKeywords : [],
				callback: function (command) {
					if (command && typeof command.close === 'function') {
						command.close();
					}
					open('all', 'all', 'section');
				}
			});
		}

		var launcher = typeof PluginSidebar === 'function'
			? createElement(
				PluginSidebar,
				{
					name: 'creceweb-lumen-library-sidebar',
					title: label,
					icon: 'layout'
				},
				createElement(LibraryLauncher, { onOpen: open })
			)
			: createElement(PluginMoreMenuItem, {
				icon: 'layout',
				onClick: function () { open('all', 'all', 'section'); }
			}, label);

		return createElement(
			Fragment,
			null,
			launcher,
			isOpen ? createElement(LibraryModal, { onClose: close, initialType: initialType, initialFamily: initialFamily, initialSource: initialSource }) : null
		);
	}

	registerPlugin('creceweb-lumen-visual-library', {
		render: LibraryPlugin,
		icon: 'layout'
	});
}(window.wp, window.cwLumenVisualLibrary));
