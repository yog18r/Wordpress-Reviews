/**
 * Editor script for the "Featured Review" block. The list of reviews for
 * the picker is provided by PHP as window.ifReviewsEditorData.
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
	var Disabled = wp.components.Disabled;
	var ServerSideRender = wp.serverSideRender;

	function reviewOptions() {
		var data = window.ifReviewsEditorData || {};
		var reviews = data.reviews || [];
		var options = [{ label: __('Choose a review…', 'if-reviews'), value: '0' }];
		var i;

		for (i = 0; i < reviews.length; i++) {
			options.push({ label: reviews[i].label, value: String(reviews[i].value) });
		}

		return options;
	}

	wp.blocks.registerBlockType('if-reviews/featured', {
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
						{ title: __('Review', 'if-reviews'), initialOpen: true },
						el(SelectControl, {
							label: __('Review to feature', 'if-reviews'),
							value: String(a.reviewId || 0),
							options: reviewOptions(),
							help: __('Hidden reviews are listed but will not be shown on the site.', 'if-reviews'),
							onChange: function (value) { set({ reviewId: parseInt(value, 10) || 0 }); }
						})
					)
				),
				el(
					'div',
					blockProps,
					el(
						Disabled,
						null,
						el(ServerSideRender, { block: 'if-reviews/featured', attributes: a })
					)
				)
			);
		},

		save: function () {
			return null;
		}
	});
})(window.wp);
