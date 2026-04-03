<?php
/**
 * HTML minification for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HTML_Minifier
 *
 * Minifies HTML output by removing whitespace, comments,
 * and unnecessary attributes. Applied on the output buffer
 * before caching — can reduce page size by 10-25%.
 */
class HTML_Minifier {

	/**
	 * Minify an HTML string.
	 *
	 * @param string $html The HTML to minify.
	 * @return string The minified HTML.
	 */
	public static function minify( $html ) {
		if ( empty( $html ) || strlen( $html ) < 100 ) {
			return $html;
		}

		// Preserve content inside <pre>, <code>, <script>, <style>, <textarea> tags.
		$preserved = array();
		$index     = 0;

		// Preserve <pre>, <code>, <textarea> content.
		$html = preg_replace_callback(
			'/<(pre|code|textarea|script|style)\b[^>]*>.*?<\/\1>/is',
			function ( $matches ) use ( &$preserved, &$index ) {
				$placeholder              = '<!--NM_PRESERVED_' . $index . '-->';
				$preserved[ $placeholder ] = $matches[0];
				++$index;
				return $placeholder;
			},
			$html
		);

		// Remove HTML comments (except IE conditionals and preserved markers).
		$html = preg_replace( '/<!--(?!\[|NM_PRESERVED_).*?-->/s', '', $html );

		// Remove whitespace between HTML tags.
		$html = preg_replace( '/>\s+</', '> <', $html );

		// Remove excess whitespace.
		$html = preg_replace( '/\s{2,}/', ' ', $html );

		// Remove whitespace around block-level tags.
		$block_tags = 'html|head|body|header|footer|nav|main|aside|section|article|div|p|ul|ol|li|table|tr|td|th|thead|tbody|h[1-6]|form|fieldset|meta|link|title|base';
		$html = preg_replace( '/\s*(<\/?(?:' . $block_tags . ')[^>]*>)\s*/i', '$1', $html );

		// Remove optional closing tags.
		$html = str_replace( array( '</option>', '</li>' ), '', $html );

		// Remove type="text/javascript" and type="text/css" (unnecessary in HTML5).
		$html = str_replace( array( ' type="text/javascript"', " type='text/javascript'", ' type="text/css"', " type='text/css'" ), '', $html );

		// Clean up empty attributes.
		$html = preg_replace( '/\s+(class|id|style)=["\']["\']/', '', $html );

		// Restore preserved content.
		foreach ( $preserved as $placeholder => $original ) {
			$html = str_replace( $placeholder, $original, $html );
		}

		return trim( $html );
	}
}
