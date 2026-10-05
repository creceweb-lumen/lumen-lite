(function (wp, settings) {
	'use strict';

	if (
		!wp || !wp.hooks || !wp.compose || !wp.element || !wp.data ||
		!wp.blockEditor || !wp.components
	) {
		return;
	}

	var addFilter = wp.hooks.addFilter;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useSelect = wp.data.useSelect;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var Button = wp.components.Button;
	var ButtonGroup = wp.components.ButtonGroup;
	var ColorPalette = wp.components.ColorPalette;
	var labels = settings || {};

	function hasClassName(attributes, className) {
		var current = attributes && attributes.className ? attributes.className : '';
		return current.split(/\s+/).indexOf(className) !== -1;
	}


	function isAuroraNote(attributes) {
		return hasClassName(attributes || {}, 'cw-kit-aurora-note');
	}

	function editorPaletteColors() {
		var store = typeof wp.data.select === 'function' ? wp.data.select('core/block-editor') : null;
		var editorSettings = store && typeof store.getSettings === 'function' ? store.getSettings() : null;
		return editorSettings && Array.isArray(editorSettings.colors) ? editorSettings.colors : [];
	}

	function auroraNoteAccent(attributes, colors) {
		var style = attributes && attributes.style ? attributes.style : {};
		var color = style.color && style.color.text ? style.color.text : '';
		if (color || !attributes || !attributes.textColor) {
			return color;
		}
		var match = colors.filter(function (item) {
			return item && item.slug === attributes.textColor;
		})[0];
		return match && match.color ? match.color : '';
	}

	function setAuroraNoteAccent(props, color) {
		var style = Object.assign({}, props.attributes.style || {});
		var styleColor = Object.assign({}, style.color || {});

		if (color) {
			styleColor.text = color;
			style.color = styleColor;
			props.setAttributes({ style: style, textColor: undefined });
			return;
		}

		delete styleColor.text;
		if (Object.keys(styleColor).length) {
			style.color = styleColor;
		} else {
			delete style.color;
		}
		props.setAttributes({
			style: Object.keys(style).length ? style : undefined,
			textColor: undefined
		});
	}

	function hasLitePatternAncestor(select, clientId) {
		var store = select('core/block-editor');
		if (
			!store || !clientId ||
			typeof store.getBlockParents !== 'function' ||
			typeof store.getBlock !== 'function'
		) {
			return false;
		}

		return store.getBlockParents(clientId).some(function (parentId) {
			var parent = store.getBlock(parentId);
			var className = parent && parent.attributes && parent.attributes.className ? parent.attributes.className : '';
			return className.split(/\s+/).some(function (item) {
				return item === 'cw-lumen-lite-pattern' || item.indexOf('cw-lumen-lite-pattern--') === 0;
			});
		});
	}

	var withLiteEyebrowControls = createHigherOrderComponent(function (BlockEdit) {
		return function (props) {
			var isLiteEyebrow = useSelect(function (select) {
				return (
					props.name === 'core/paragraph' &&
					hasClassName(props.attributes || {}, 'cw-lumen-lite-pattern__eyebrow') &&
					hasLitePatternAncestor(select, props.clientId)
				);
			}, [props.name, props.clientId, props.attributes && props.attributes.className]);

			if (!props.isSelected || !isLiteEyebrow) {
				return createElement(BlockEdit, props);
			}

			var current = props.attributes.align || '';
			var positions = [
				{ value: '', label: labels.defaultPosition || 'Default' },
				{ value: 'left', label: labels.left || 'Left' },
				{ value: 'center', label: labels.center || 'Center' },
				{ value: 'right', label: labels.right || 'Right' }
			];

			return createElement(
				Fragment,
				null,
				createElement(BlockEdit, props),
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{ title: labels.panelTitle || 'Eyebrow position', initialOpen: true },
						createElement(
							ButtonGroup,
							{ 'aria-label': labels.panelTitle || 'Eyebrow position' },
							positions.map(function (position) {
								return createElement(
									Button,
									{
										key: position.value || 'default',
										variant: current === position.value ? 'primary' : 'secondary',
										isPressed: current === position.value,
										onClick: function () {
											props.setAttributes({ align: position.value || undefined });
										}
									},
									position.label
								);
							})
						),
						createElement(
							'p',
							{ className: 'components-base-control__help' },
							labels.help || 'The default option preserves the pattern original alignment.'
						)
					)
				)
			);
		};
	}, 'withLiteEyebrowControls');

	var withAuroraNoteControls = createHigherOrderComponent(function (BlockEdit) {
		return function (props) {
			if (
				!props.isSelected || props.name !== 'core/group' ||
				!isAuroraNote(props.attributes) || !ColorPalette
			) {
				return createElement(BlockEdit, props);
			}

			var colors = editorPaletteColors();
			var current = auroraNoteAccent(props.attributes || {}, colors);

			return createElement(
				Fragment,
				null,
				createElement(BlockEdit, props),
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{ title: labels.notePanelTitle || 'Aurora note', initialOpen: true },
						createElement('p', { className: 'components-base-control__label' }, labels.noteAccentLabel || 'Accent color'),
						createElement(ColorPalette, {
							colors: colors,
							value: current,
							disableCustomColors: false,
							clearable: true,
							onChange: function (color) {
								setAuroraNoteAccent(props, color || '');
							}
						}),
						createElement(
							'p',
							{ className: 'components-base-control__help' },
							labels.noteAccentHelp || 'Changes only the top accent line and Open note link.'
						),
						current ? createElement(
							Button,
							{ variant: 'secondary', onClick: function () { setAuroraNoteAccent(props, ''); } },
							labels.noteAccentReset || 'Use default accent'
						) : null
					)
				)
			);
		};
	}, 'withAuroraNoteControls');

	addFilter(
		'editor.BlockEdit',
		'creceweb-lumen-lite/eyebrow-position-controls',
		withLiteEyebrowControls
	);
	addFilter(
		'editor.BlockEdit',
		'creceweb-lumen-lite/aurora-note-accent-controls',
		withAuroraNoteControls
	);
}(window.wp, window.cwLumenLitePatternControls));
