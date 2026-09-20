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
	var labels = settings || {};

	function hasClassName(attributes, className) {
		var current = attributes && attributes.className ? attributes.className : '';
		return current.split(/\s+/).indexOf(className) !== -1;
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

	addFilter(
		'editor.BlockEdit',
		'creceweb-lumen-lite/eyebrow-position-controls',
		withLiteEyebrowControls
	);
}(window.wp, window.cwLumenLitePatternControls));
