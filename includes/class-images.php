<?php
/**
 * Image optimization module for NitroMan.
 *
 * @package NitroMan
 */

namespace NitroMan;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Image_Optimizer
 *
 * Handles lazy loading, WebP support detection, and missing
 * dimension enforcement. Uses recursion guards and batch limits.
 */
class Image_Optimizer {

	/**
	 * Singleton instance.
	 *
	 * @var Image_Optimizer|null
	 */
	private static $instance = null;

	/**
	 * Recursion guard flag.
	 *
	 * @var bool
	 */
	private static $is_processing = false;

	/**
	 * Maximum images to process per content block.
	 *
	 * @var int
	 */
	private $batch_limit = 50;

	/**
	 * Get the singleton instance.
	 *
	 * @return Image_Optimizer
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function init_hooks() {
		// Filter content images.
		add_filter( 'the_content', array( $this, 'optimize_content_images' ), 99 );

		// Filter post thumbnails.
		add_filter( 'post_thumbnail_html', array( $this, 'optimize_thumbnail' ), 99 );

		// Filter widget text images.
		add_filter( 'widget_text', array( $this, 'optimize_content_images' ), 99 );

		// Add WebP support via picture element (if WebP enabled).
		if ( nitroman_get_option( 'nitroman_webp_support' ) ) {
			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_webp_attributes' ), 10, 3 );
		}
	}

	/**
	 * Optimize images within content HTML.
	 *
	 * @param string $content The post content.
	 * @return string Modified content.
	 */
	public function optimize_content_images( $content ) {
		// Recursion guard.
		if ( self::$is_processing ) {
			return $content;
		}
		self::$is_processing = true;

		if ( empty( $content ) ) {
			self::$is_processing = false;
			return $content;
		}

		// Find all img tags.
		$pattern = '/<img\s[^>]+>/i';
		$count   = 0;

		$content = preg_replace_callback( $pattern, function ( $matches ) use ( &$count ) {
			if ( $count >= $this->batch_limit ) {
				return $matches[0];
			}
			++$count;
			return $this->process_image_tag( $matches[0] );
		}, $content );

		self::$is_processing = false;
		return $content;
	}

	/**
	 * Optimize a single thumbnail.
	 *
	 * @param string $html The thumbnail HTML.
	 * @return string Modified HTML.
	 */
	public function optimize_thumbnail( $html ) {
		if ( empty( $html ) ) {
			return $html;
		}
		return $this->process_image_tag( $html );
	}

	/**
	 * Process a single <img> tag.
	 *
	 * @param string $img_tag The full <img> tag HTML.
	 * @return string Modified <img> tag.
	 */
	private function process_image_tag( $img_tag ) {
		// Skip images that are already lazy-loaded.
		if ( false !== strpos( $img_tag, 'loading=' ) ) {
			return $img_tag;
		}

		// Skip images with skip markers.
		if ( false !== strpos( $img_tag, 'data-no-lazy' ) || false !== strpos( $img_tag, 'skip-lazy' ) ) {
			return $img_tag;
		}

		// Add native lazy loading.
		if ( nitroman_get_option( 'nitroman_lazy_load' ) ) {
			$img_tag = $this->add_lazy_loading( $img_tag );
		}

		// Add missing dimensions.
		if ( nitroman_get_option( 'nitroman_add_dimensions' ) ) {
			$img_tag = $this->add_missing_dimensions( $img_tag );
		}

		return $img_tag;
	}

	/**
	 * Add native lazy loading attribute to an img tag.
	 *
	 * @param string $img_tag The <img> tag.
	 * @return string Modified tag.
	 */
	private function add_lazy_loading( $img_tag ) {
		// Don't lazy-load above-the-fold images.
		if ( false !== strpos( $img_tag, 'above-fold' ) ) {
			return $img_tag;
		}

		// Add loading="lazy" attribute.
		$img_tag = str_replace( '<img ', '<img loading="lazy" ', $img_tag );

		// Add decoding="async" for performance.
		if ( false === strpos( $img_tag, 'decoding=' ) ) {
			$img_tag = str_replace( '<img ', '<img decoding="async" ', $img_tag );
		}

		return $img_tag;
	}

	/**
	 * Add missing width and height attributes.
	 *
	 * @param string $img_tag The <img> tag.
	 * @return string Modified tag.
	 */
	private function add_missing_dimensions( $img_tag ) {
		// Check if dimensions already exist.
		if ( preg_match( '/\bwidth=["\']?\d+/', $img_tag ) && preg_match( '/\bheight=["\']?\d+/', $img_tag ) ) {
			return $img_tag;
		}

		// Try to extract the image URL.
		if ( ! preg_match( '/src=["\']([^"\']+)["\']/', $img_tag, $src_match ) ) {
			return $img_tag;
		}

		$src = $src_match[1];

		// Try to get dimensions from WordPress attachment metadata.
		$attachment_id = $this->url_to_attachment_id( $src );
		if ( $attachment_id ) {
			$metadata = wp_get_attachment_metadata( $attachment_id );
			if ( $metadata && isset( $metadata['width'], $metadata['height'] ) ) {
				if ( ! preg_match( '/\bwidth=["\']?\d+/', $img_tag ) ) {
					$img_tag = str_replace( '<img ', sprintf( '<img width="%d" ', (int) $metadata['width'] ), $img_tag );
				}
				if ( ! preg_match( '/\bheight=["\']?\d+/', $img_tag ) ) {
					$img_tag = str_replace( '<img ', sprintf( '<img height="%d" ', (int) $metadata['height'] ), $img_tag );
				}
			}
		}

		return $img_tag;
	}

	/**
	 * Convert a URL to an attachment ID using a cached lookup.
	 *
	 * @param string $url The image URL.
	 * @return int|false Attachment ID or false.
	 */
	private function url_to_attachment_id( $url ) {
		static $cache = array();

		if ( isset( $cache[ $url ] ) ) {
			return $cache[ $url ];
		}

		// Strip the base URL and find attachment by path.
		$upload_dir = wp_get_upload_dir();
		$base_url   = $upload_dir['baseurl'];

		if ( 0 !== strpos( $url, $base_url ) ) {
			$cache[ $url ] = false;
			return false;
		}

		$relative_path = str_replace( $base_url . '/', '', $url );

		// Remove size suffix for lookup (e.g., -300x200).
		$clean_path = preg_replace( '/-\d+x\d+(?=\.\w+$)/', '', $relative_path );

		global $wpdb;
		$attachment_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
				$clean_path
			)
		);

		$result        = $attachment_id ? (int) $attachment_id : false;
		$cache[ $url ] = $result;

		return $result;
	}

	/**
	 * Add WebP-related attributes for attachment images.
	 *
	 * @param array        $attr       Image attributes.
	 * @param WP_Post      $attachment The attachment post object.
	 * @param string|array $size       Image size.
	 * @return array Modified attributes.
	 */
	public function add_webp_attributes( $attr, $attachment, $size ) {
		if ( isset( $attr['src'] ) ) {
			// Check if a WebP version exists.
			$webp_url = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $attr['src'] );
			$webp_path = str_replace(
				wp_get_upload_dir()['baseurl'],
				wp_get_upload_dir()['basedir'],
				$webp_url
			);

			if ( file_exists( $webp_path ) ) {
				$attr['data-webp'] = esc_url( $webp_url );
			}
		}

		return $attr;
	}
}
