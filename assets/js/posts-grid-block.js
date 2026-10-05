(function (wp) {
	'use strict';
	if (!wp || !wp.blocks || !wp.element || !wp.components || !wp.blockEditor || !wp.serverSideRender) {
		return;
	}
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var Inspector = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var Panel = wp.components.PanelBody;
	var Toggle = wp.components.ToggleControl;
	var Text = wp.components.TextControl;
	var Select = wp.components.SelectControl;
	var ServerSideRender = wp.serverSideRender;

	function positiveInt(value, fallback) {
		var parsed = parseInt(value, 10);
		return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
	}
	function positiveFloat(value, fallback) {
		var parsed = parseFloat(value);
		return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
	}
	function nonNegativeFloat(value) {
		if (value === '') { return undefined; }
		var parsed = parseFloat(value);
		return Number.isFinite(parsed) && parsed >= 0 ? parsed : undefined;
	}
	function ids(value) {
		return String(value || '').split(/[^0-9]+/).map(function (item) { return parseInt(item, 10); }).filter(function (item, index, list) { return item > 0 && list.indexOf(item) === index; });
	}
	function idsText(value) {
		return Array.isArray(value) ? value.join(', ') : '';
	}
	function numberControl(label, value, onChange, min, step) {
		return el(Text, { label: label, type: 'number', value: value, min: min || 1, step: step || 1, onChange: onChange });
	}

	wp.blocks.registerBlockType('creceweb-lumen/posts-grid', {
		edit: function (props) {
			var a = props.attributes;
			var blockProps = useBlockProps({ className: 'wp-block-creceweb-lumen-posts-grid' });
			var set = props.setAttributes;
			var custom = !a.useGlobalDefaults;
			var controls = el(Inspector, {},
				el(Panel, { title: __('Lumen Posts', 'creceweb-lumen-lite'), initialOpen: true },
					el(Toggle, { label: __('Use global defaults', 'creceweb-lumen-lite'), checked: a.useGlobalDefaults, onChange: function (v) { set({ useGlobalDefaults: v }); } }),
					custom && el(Text, { label: __('Section title', 'creceweb-lumen-lite'), value: a.title, onChange: function (v) { set({ title: v }); } }),
					custom && el(Text, { label: __('Post type', 'creceweb-lumen-lite'), help: __('Use a public post type slug, for example post.', 'creceweb-lumen-lite'), value: a.postType, onChange: function (v) { set({ postType: v }); } }),
					custom && el(Select, { label: __('Source', 'creceweb-lumen-lite'), value: a.source, options: [{ label: __('Latest', 'creceweb-lumen-lite'), value: 'latest' }, { label: __('Popular', 'creceweb-lumen-lite'), value: 'popular' }, { label: __('Manual', 'creceweb-lumen-lite'), value: 'manual' }], onChange: function (v) { set({ source: v }); } }),
					custom && a.source === 'manual' && el(Text, { label: __('Manual post IDs', 'creceweb-lumen-lite'), help: __('Comma-separated IDs; order is preserved.', 'creceweb-lumen-lite'), value: idsText(a.manualIds), onChange: function (v) { set({ manualIds: ids(v) }); } }),
					custom && a.source !== 'manual' && el(Select, { label: __('Order by', 'creceweb-lumen-lite'), value: a.orderby, options: [{ label: __('Date', 'creceweb-lumen-lite'), value: 'date' }, { label: __('Title', 'creceweb-lumen-lite'), value: 'title' }, { label: __('Comment count', 'creceweb-lumen-lite'), value: 'comment_count' }], onChange: function (v) { set({ orderby: v }); } }),
					custom && a.source !== 'manual' && el(Select, { label: __('Order', 'creceweb-lumen-lite'), value: a.order, options: [{ label: 'DESC', value: 'DESC' }, { label: 'ASC', value: 'ASC' }], onChange: function (v) { set({ order: v }); } }),
					custom && el(Text, { label: __('Taxonomy', 'creceweb-lumen-lite'), help: __('Optional compatible public taxonomy slug.', 'creceweb-lumen-lite'), value: a.taxonomy, onChange: function (v) { set({ taxonomy: v }); } }),
					custom && a.taxonomy && el(Text, { label: __('Term IDs', 'creceweb-lumen-lite'), value: idsText(a.termIds), onChange: function (v) { set({ termIds: ids(v) }); } })
				),
				custom && el(Panel, { title: __('Responsive layout', 'creceweb-lumen-lite'), initialOpen: false },
					numberControl(__('Desktop items', 'creceweb-lumen-lite'), a.itemsDesktop, function (v) { set({ itemsDesktop: positiveInt(v, 1) }); }),
					numberControl(__('Tablet items', 'creceweb-lumen-lite'), a.itemsTablet, function (v) { set({ itemsTablet: positiveInt(v, 1) }); }),
					numberControl(__('Mobile items', 'creceweb-lumen-lite'), a.itemsMobile, function (v) { set({ itemsMobile: positiveInt(v, 1) }); }),
					el(Select, { label: __('Layout', 'creceweb-lumen-lite'), value: a.layout, options: [{ label: __('Grid', 'creceweb-lumen-lite'), value: 'grid' }, { label: __('List', 'creceweb-lumen-lite'), value: 'list' }], onChange: function (v) { set({ layout: v }); } }),
					numberControl(__('Desktop columns', 'creceweb-lumen-lite'), a.columnsDesktop, function (v) { set({ columnsDesktop: positiveInt(v, 1) }); }),
					numberControl(__('Tablet columns', 'creceweb-lumen-lite'), a.columnsTablet, function (v) { set({ columnsTablet: positiveInt(v, 1) }); }),
					numberControl(__('Mobile columns', 'creceweb-lumen-lite'), a.columnsMobile, function (v) { set({ columnsMobile: positiveInt(v, 1) }); }),
					el(Select, { label: __('Card style', 'creceweb-lumen-lite'), value: a.style, options: [{ label: __('Theme', 'creceweb-lumen-lite'), value: 'default' }, { label: __('Elevated', 'creceweb-lumen-lite'), value: 'elevated' }, { label: __('Minimal', 'creceweb-lumen-lite'), value: 'minimal' }], onChange: function (v) { set({ style: v }); } }),
					numberControl(__('Image ratio width', 'creceweb-lumen-lite'), a.imageRatioWidth, function (v) { set({ imageRatioWidth: positiveFloat(v, 16) }); }, 0.1, 0.1),
					numberControl(__('Image ratio height', 'creceweb-lumen-lite'), a.imageRatioHeight, function (v) { set({ imageRatioHeight: positiveFloat(v, 9) }); }, 0.1, 0.1),
					numberControl(__('Gap (px)', 'creceweb-lumen-lite'), a.gap === undefined ? '' : a.gap, function (v) { set({ gap: nonNegativeFloat(v) }); }, 0, 0.1)
				),
				custom && el(Panel, { title: __('Card content', 'creceweb-lumen-lite'), initialOpen: false },
					el(Toggle, { label: __('Featured image', 'creceweb-lumen-lite'), checked: a.showImage, onChange: function (v) { set({ showImage: v }); } }),
					el(Toggle, { label: __('Taxonomy', 'creceweb-lumen-lite'), checked: a.showTaxonomy, onChange: function (v) { set({ showTaxonomy: v }); } }),
					el(Toggle, { label: __('Date', 'creceweb-lumen-lite'), checked: a.showDate, onChange: function (v) { set({ showDate: v }); } }),
					el(Toggle, { label: __('Excerpt', 'creceweb-lumen-lite'), checked: a.showExcerpt, onChange: function (v) { set({ showExcerpt: v }); } }),
					el(Toggle, { label: __('Read more', 'creceweb-lumen-lite'), checked: a.showReadMore, onChange: function (v) { set({ showReadMore: v }); } }),
					a.showReadMore && el(Text, { label: __('Read more text', 'creceweb-lumen-lite'), value: a.readMoreText, onChange: function (v) { set({ readMoreText: v }); } })
				),
				custom && el(Panel, { title: __('Device visibility', 'creceweb-lumen-lite'), initialOpen: false },
					el(Toggle, { label: __('Desktop', 'creceweb-lumen-lite'), checked: a.desktop, onChange: function (v) { set({ desktop: v }); } }),
					el(Toggle, { label: __('Tablet', 'creceweb-lumen-lite'), checked: a.tablet, onChange: function (v) { set({ tablet: v }); } }),
					el(Toggle, { label: __('Mobile', 'creceweb-lumen-lite'), checked: a.mobile, onChange: function (v) { set({ mobile: v }); } })
				)
			);
			var extensionControls = [];
			if (wp.hooks && typeof wp.hooks.applyFilters === 'function') {
				extensionControls = wp.hooks.applyFilters('creceweb.lumen.postsGrid.inspectorControls', extensionControls, { attributes: a, setAttributes: set });
				if (!Array.isArray(extensionControls)) { extensionControls = []; }
			}
			return el('div', blockProps, controls, extensionControls, el(ServerSideRender, { block: 'creceweb-lumen/posts-grid', attributes: a }));
		},
		save: function () { return null; }
	});
}(window.wp));
