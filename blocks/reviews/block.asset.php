<?php
/**
 * Dependencies for block.js. WordPress reads this file automatically for
 * the "editorScript" entry in block.json. No build step is involved: the
 * script uses the global wp.* packages directly.
 *
 * @package IF_Reviews
 */

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-i18n',
		'wp-block-editor',
		'wp-components',
		'wp-server-side-render',
	),
	'version'      => '2.3.0',
);
