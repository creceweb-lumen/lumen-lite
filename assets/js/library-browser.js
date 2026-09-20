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
	var sources = Array.isArray(settings.sources) ? settings.sources : [];
	var types = Array.isArray(settings.types) ? settings.types : [];

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
					createElement(Button, {
						variant: 'primary',
						size: 'compact',
						onClick: function () { props.onInsert(pattern); }
					}, pattern.type === 'page' ? (labels.insertPage || labels.insert || 'Insert') : (labels.insertPattern || 'Insert pattern'))
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

		var visiblePatterns = useMemo(function () {
			return settings.patterns.filter(function (pattern) {
				return matchesPattern(pattern, activeType, activeFamily, activeSource, query);
			});
		}, [activeType, activeFamily, activeSource, query]);


		function handleInsert(pattern) {
			var result = insertPattern(pattern);
			if (result.ok) {
				props.onClose();
				if (wp.data.dispatch('core/notices') && wp.data.dispatch('core/notices').createSuccessNotice) {
					wp.data.dispatch('core/notices').createSuccessNotice(result.message, {
						type: 'snackbar',
						id: 'cw-lumen-library-inserted'
					});
				}
				return;
			}
			setNotice(result.message);
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
			detail ? createElement(PatternDetail, {
				pattern: detail,
				onBack: function () { setDetail(null); },
				onInsert: handleInsert
			}) : createElement(
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
					visiblePatterns.length === 1 ? (labels.oneResult || '') : String(visiblePatterns.length) + ' ' + (labels.results || '')
				),
				visiblePatterns.length ? createElement(
					'div',
					{ className: 'cw-lumen-library__grid' },
					visiblePatterns.map(function (pattern) {
						return createElement(PatternCard, {
							key: pattern.slug,
							pattern: pattern,
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
			createElement('p', { className: 'cw-lumen-library-launcher__count' }, labels.patternCount || (String(settings.patterns.length) + ' available patterns')),
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
