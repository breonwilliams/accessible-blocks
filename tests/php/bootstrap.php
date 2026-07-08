<?php
/**
 * PHPUnit bootstrap: minimal WordPress function stubs.
 *
 * These are *unit* tests for the plugin's pure logic (Contrast math) and
 * render templates — they deliberately don't boot WordPress. Only the
 * handful of WP functions the tested code touches are stubbed, with
 * simplified but behavior-compatible implementations. Full WordPress
 * integration tests (wp-env) can be layered on later without changing
 * these suites.
 *
 * @package AccessibleBlocks\Tests
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

define( 'ACCESSIBLE_BLOCKS_PLUGIN_ROOT', dirname( __DIR__, 2 ) );

/**
 * Test palette used by the wp_get_global_settings() stub. Tests may
 * overwrite this global to simulate different themes.
 */
$GLOBALS['accessible_blocks_test_palette'] = array(
	'theme' => array(
		array(
			'slug'  => 'primary',
			'color' => '#1e3a8a',
			'name'  => 'Primary',
		),
		array(
			'slug'  => 'base',
			'color' => '#ffffff',
			'name'  => 'Base',
		),
		array(
			'slug'  => 'contrast',
			'color' => '#111111',
			'name'  => 'Contrast',
		),
	),
);

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * @param string $value Path.
	 */
	function trailingslashit( string $value ): string {
		return rtrim( $value, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * @param string $text Text.
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * @param string $text Text.
	 */
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * @param string $url URL.
	 */
	function esc_url( string $url ): string {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'tag_escape' ) ) {
	/**
	 * @param string $tag_name Tag.
	 */
	function tag_escape( string $tag_name ): string {
		return strtolower( preg_replace( '/[^a-zA-Z0-9-]/', '', $tag_name ) ?? '' );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Simplified stub: real filtering is WordPress's job; unit tests only
	 * assert the content is passed through this gate.
	 *
	 * @param string $text Content.
	 */
	function wp_kses_post( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'get_block_wrapper_attributes' ) ) {
	/**
	 * @param array $extra Extra attributes.
	 */
	function get_block_wrapper_attributes( array $extra = array() ): string {
		$class = trim( 'wp-block-test ' . ( $extra['class'] ?? '' ) );
		$attrs = 'class="' . esc_attr( $class ) . '"';

		foreach ( $extra as $key => $value ) {
			if ( 'class' === $key || '' === $value ) {
				continue;
			}
			$attrs .= ' ' . $key . '="' . esc_attr( (string) $value ) . '"';
		}

		return $attrs;
	}
}

if ( ! function_exists( 'wp_unique_id' ) ) {
	/**
	 * @param string $prefix Prefix.
	 */
	function wp_unique_id( string $prefix = '' ): string {
		static $counter = 0;
		++$counter;
		return $prefix . $counter;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $value Value.
	 */
	function wp_json_encode( $value ): string {
		return (string) json_encode( $value ); // phpcs:ignore
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * @param string $text Text.
	 */
	function wp_strip_all_tags( string $text ): string {
		return trim( strip_tags( $text ) ); // phpcs:ignore
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * Simplified slugifier (behavior-compatible for ASCII input).
	 *
	 * @param string $title Title.
	 */
	function sanitize_title( string $title ): string {
		$slug = strtolower( strip_tags( $title ) ); // phpcs:ignore
		$slug = preg_replace( '/[^a-z0-9]+/', '-', $slug ) ?? '';
		return trim( $slug, '-' );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 */
	function esc_attr__( string $text, string $domain = 'default' ): string {
		unset( $domain );
		return esc_attr( $text );
	}
}

if ( ! function_exists( 'get_post' ) ) {
	/**
	 * @param int $post_id Post ID.
	 */
	function get_post( int $post_id ) {
		$post = $GLOBALS['accessible_blocks_test_post'] ?? null;
		return ( $post && (int) $post->ID === $post_id ) ? $post : null;
	}
}

if ( ! function_exists( 'parse_blocks' ) ) {
	/**
	 * Test stub: post_content holds a JSON-encoded parsed-block tree.
	 *
	 * @param string $content Content.
	 */
	function parse_blocks( string $content ): array {
		$decoded = json_decode( $content, true );
		return is_array( $decoded ) ? $decoded : array();
	}
}

if ( ! function_exists( 'wp_get_global_settings' ) ) {
	/**
	 * @param array $path Settings path.
	 */
	function wp_get_global_settings( array $path = array() ) {
		if ( array( 'color', 'palette' ) === $path ) {
			return $GLOBALS['accessible_blocks_test_palette'];
		}
		return array();
	}
}

require_once ACCESSIBLE_BLOCKS_PLUGIN_ROOT . '/includes/class-contrast.php';
require_once ACCESSIBLE_BLOCKS_PLUGIN_ROOT . '/includes/class-outline.php';

/**
 * Render a block template file the way WordPress does: with $attributes,
 * $content, and $block in scope, capturing output.
 *
 * @param string $file       Absolute path to the render template.
 * @param array  $attributes Block attributes.
 * @param array  $context    Block context.
 * @return string Rendered markup.
 */
function accessible_blocks_test_render( string $file, array $attributes, array $context = array() ): string {
	$content = '';
	$block   = new class() {
		/**
		 * @var array
		 */
		public $context = array();
	};

	$block->context = $context;

	ob_start();
	include $file;
	return (string) ob_get_clean();
}
