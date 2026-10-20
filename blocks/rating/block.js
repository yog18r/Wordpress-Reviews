/**
 * Editor script for the "Rating Badge" block.
 */
(function (wp) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var Disabled = wp.components.Disabled;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType('if-reviews/rating', {
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
						{ title: __('Badge', 'if-reviews'), initialOpen: true },
						el(ToggleControl, {
							label: __('Show stars', 'if-reviews'),
							checked: !!a.showStars,
							onChange: function (value) { set({ showStars: !!value }); }
						}),
						el(ToggleControl, {
							label: __('Show review count', 'if-reviews'),
							checked: !!a.showCount,
							onChange: function (value) { set({ showCount: !!value }); }
						}),
						el(TextControl, {
							label: __('Only these sources', 'if-reviews'),
							value: a.source,
							help: __('Source keys, comma-separated, e.g. google. Blank for all.', 'if-reviews'),
							onChange: function (value) { set({ source: value }); }
						}),
						el(TextControl, {
							label: __('Only these categories', 'if-reviews'),
							value: a.category,
							help: __('Category slugs, comma-separated. Blank for all.', 'if-reviews'),
							onChange: function (value) { set({ category: value }); }
						})
					)
				),
				el(
					'div',
					blockProps,
					el(
						Disabled,
						null,
						el(ServerSideRender, { block: 'if-reviews/rating', attributes: a })
					)
				)
			);
		},

		save: function () {
			return null;
		}
	});
})(window.wp);
