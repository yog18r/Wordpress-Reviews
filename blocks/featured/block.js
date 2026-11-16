/**
 * Editor script for the "Featured Review" block. The searchable list of
 * reviews is fetched from the plugin's REST route when the block is used.
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
	var ComboboxControl = wp.components.ComboboxControl;
	var Disabled = wp.components.Disabled;
	var ServerSideRender = wp.serverSideRender;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var DEFAULT_LABEL = __('Default (from Display Settings)', 'if-reviews');

	// Loaded once per editor session and shared by every Featured Review block.
	var cache = null;
	var pending = null;

	function loadReviews() {
		if (cache) {
			return Promise.resolve(cache);
		}
		if (!pending) {
			pending = wp.apiFetch({ path: '/if-reviews/v1/picker' }).then(function (list) {
				cache = (list || []).map(function (item) {
					return { label: item.label, value: String(item.value) };
				});
				return cache;
			}, function () {
				pending = null;
				return [];
			});
		}
		return pending;
	}

	function useReviewOptions() {
		var state = useState(cache);
		var options = state[0];
		var setOptions = state[1];

		useEffect(function () {
			var live = true;
			if (!options) {
				loadReviews().then(function (list) {
					if (live) {
						setOptions(list);
					}
				});
			}
			return function () { live = false; };
		}, []);

		return options;
	}

	wp.blocks.registerBlockType('if-reviews/featured', {
		edit: function (props) {
			var a = props.attributes;
			var set = props.setAttributes;
			var blockProps = useBlockProps();
			var options = useReviewOptions();
			var help = options
				? __('Type to search. Hidden reviews are listed but will not be shown on the site.', 'if-reviews')
				: __('Loading reviews…', 'if-reviews');
			var picker = ComboboxControl
				? el(ComboboxControl, {
					label: __('Review to feature', 'if-reviews'),
					value: a.reviewId ? String(a.reviewId) : null,
					options: options || [],
					help: help,
					onChange: function (value) { set({ reviewId: parseInt(value, 10) || 0 }); }
				})
				: el(SelectControl, {
					label: __('Review to feature', 'if-reviews'),
					value: String(a.reviewId || 0),
					options: [{ label: __('Choose a review…', 'if-reviews'), value: '0' }].concat(options || []),
					help: help,
					onChange: function (value) { set({ reviewId: parseInt(value, 10) || 0 }); }
				});

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Review', 'if-reviews'), initialOpen: true },
						picker
					),
					el(
						PanelBody,
						{ title: __('Style', 'if-reviews'), initialOpen: false },
						el(SelectControl, {
							label: __('Colour scheme', 'if-reviews'),
							value: a.scheme,
							options: [
								{ label: DEFAULT_LABEL, value: '' },
								{ label: __('Match my theme', 'if-reviews'), value: 'theme' },
								{ label: __('Light', 'if-reviews'), value: 'light' },
								{ label: __('Dark', 'if-reviews'), value: 'dark' },
								{ label: __("Follow the visitor's device", 'if-reviews'), value: 'auto' }
							],
							onChange: function (value) { set({ scheme: value }); }
						}),
						el(SelectControl, {
							label: __('Card style', 'if-reviews'),
							value: a.cardStyle,
							options: [
								{ label: DEFAULT_LABEL, value: '' },
								{ label: __('Soft shadow', 'if-reviews'), value: 'shadow' },
								{ label: __('Outlined', 'if-reviews'), value: 'outlined' },
								{ label: __('Flat', 'if-reviews'), value: 'flat' },
								{ label: __('Minimal (no card)', 'if-reviews'), value: 'minimal' }
							],
							onChange: function (value) { set({ cardStyle: value }); }
						}),
						el(SelectControl, {
							label: __('Spacing', 'if-reviews'),
							value: a.density,
							options: [
								{ label: DEFAULT_LABEL, value: '' },
								{ label: __('Compact', 'if-reviews'), value: 'compact' },
								{ label: __('Comfortable', 'if-reviews'), value: 'comfortable' },
								{ label: __('Spacious', 'if-reviews'), value: 'spacious' }
							],
							onChange: function (value) { set({ density: value }); }
						}),
						el(SelectControl, {
							label: __('Review alignment', 'if-reviews'),
							value: a.cardAlign,
							options: [
								{ label: DEFAULT_LABEL, value: '' },
								{ label: __('Left', 'if-reviews'), value: 'start' },
								{ label: __('Centre', 'if-reviews'), value: 'center' },
								{ label: __('Right', 'if-reviews'), value: 'end' }
							],
							onChange: function (value) { set({ cardAlign: value }); }
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
