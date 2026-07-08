<?php
/**
 * Accessible Heading — server render.
 *
 * Enforcement layer 3 of Guarantee B: the emitted tag is computed here, on
 * every request, from the block context provided by the nearest Accessible
 * Section. Whatever happens to stored markup, the level is clamped to the
 * valid H2–H6 range — a skipped or overflowing level cannot be output.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused; dynamic).
 * @var WP_Block $block      Block instance (context source).
 *
 * @package AccessibleBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$accessible_blocks_content = isset( $attributes['content'] ) ? trim( (string) $attributes['content'] ) : '';

if ( '' === $accessible_blocks_content ) {
	return;
}

$accessible_blocks_level = isset( $block->context['accessible-blocks/headingLevel'] )
	? (int) $block->context['accessible-blocks/headingLevel']
	: 2;

// Clamp to the derived-heading range: the page title owns H1, and HTML
// stops at H6. Mirrors clampHeadingLevel() in src/utils/outline.ts.
$accessible_blocks_level = min( max( $accessible_blocks_level, 2 ), 6 );

// Anchor id so the Table of Contents can link here (same algorithm and
// document order as the ToC walker → identical ids).
$accessible_blocks_anchor = \AccessibleBlocks\Outline::unique_anchor( $accessible_blocks_content );

printf(
	'<h%1$d %2$s>%3$s</h%1$d>',
	(int) $accessible_blocks_level,
	get_block_wrapper_attributes( array( 'id' => $accessible_blocks_anchor ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped by core.
	wp_kses_post( $accessible_blocks_content )
);
