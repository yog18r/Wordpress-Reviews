/**
 * Editor script for the "Reviews" block.
 *
 * Plain JavaScript on the global wp.* packages so the plugin needs no
 * build step. The block's title, icon and attributes come from block.json.
 */
(function (wp) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var Disabled = wp.components.Disabled;
	var ServerSideRender = wp.serverSideRender;

	var DEFAULT_LABEL = __('Default (from Display Settings)', 'if-reviews');

	var yesNo = [
		{ label: DEFAULT_LABEL, value: '' },
		{ label: __('Yes', 'if-reviews'), value: 'yes' },
		{ label: __('No', 'if-reviews'), value: 'no' }
	];

	wp.blocks.registerBlockType('if-reviews/reviews', {
		edit: function (props) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Layout', 'if-reviews'), initialOpen: true },
						el(SelectControl, {
							label: __('Layout', 'if-reviews'),
							value: a.layout,
							options: [
								{ label: DEFAULT_LABEL, value: '' },
								{ label: __('Grid', 'if-reviews'), value: 'grid' },
								{ label: __('List', 'if-reviews'), value: 'list' },
								{ label: __('Carousel', 'if-reviews'), value: 'carousel' }
							],
							onChange: function (value) { set({ layout: value }); }
						}),
						el(SelectControl, {
							label: __('Grid columns', 'if-reviews'),
							value: String(a.columns || 0),
							options: [
								{ label: DEFAULT_LABEL, value: '0' },
								{ label: '1', value: '1' },
								{ label: '2', value: '2' },
								{ label: '3', value: '3' },
								{ label: '4', value: '4' }
							],
							onChange: function (value) { set({ columns: parseInt(value, 10) || 0 }); }
						}),
						el(TextControl, {
							label: __('Reviews to show', 'if-reviews'),
							type: 'number',
							min: 0,
							value: a.limit < 0 ? '' : String(a.limit),
							help: __('Blank uses the default. 0 shows every visible review.', 'if-reviews'),
							onChange: function (value) {
								set({ limit: value === '' ? -1 : Math.max(0, parseInt(value, 10) || 0) });
							}
						}),
						el(SelectControl, {
							label: __('"Show more" button', 'if-reviews'),
							value: a.showMore,
							options: yesNo,
							onChange: function (value) { set({ showMore: value }); }
						}),
						el(SelectControl, {
							label: __('Order', 'if-reviews'),
							value: a.order,
							options: [
								{ label: DEFAULT_LABEL, value: '' },
								{ label: __('Newest first', 'if-reviews'), value: 'newest' },
								{ label: __('Oldest first', 'if-reviews'), value: 'oldest' },
								{ label: __('Highest rating first', 'if-reviews'), value: 'highest' },
								{ label: __('Random', 'if-reviews'), value: 'random' }
							],
							onChange: function (value) { set({ order: value }); }
						})
					),
					el(
						PanelBody,
						{ title: __('Header and sources', 'if-reviews'), initialOpen: false },
						el(SelectControl, {
							label: __('Summary header', 'if-reviews'),
							value: a.showHeader,
							options: yesNo,
							onChange: function (value) { set({ showHeader: value }); }
						}),
						el(TextControl, {
							label: __('Header title', 'if-reviews'),
							value: a.title,
							help: __('Blank for automatic ("Google Reviews" when every review is from Google, "Reviews" otherwise).', 'if-reviews'),
							onChange: function (value) { set({ title: value }); }
						}),
						el(SelectControl, {
							label: __('"Leave us a review" buttons', 'if-reviews'),
							value: a.showButtons,
							options: yesNo,
							help: __('Buttons appear only for platforms that have a link in Display Settings.', 'if-reviews'),
							onChange: function (value) { set({ showButtons: value }); }
						}),
						el(TextControl, {
							label: __('Only these sources', 'if-reviews'),
							value: a.source,
							help: __('Source keys, comma-separated, e.g. google,facebook. Blank shows all. The header then describes only those sources.', 'if-reviews'),
							onChange: function (value) { set({ source: value }); }
						})
					)
				),
				el(
					'div',
					blockProps,
					el(
						Disabled,
						null,
						el(ServerSideRender, { block: 'if-reviews/reviews', attributes: a })
					)
				)
			);
		},

		save: function () {
			return null;
		}
	});
})(window.wp);
