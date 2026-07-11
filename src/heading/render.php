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
 * @package GuardrailBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$guardrail_blocks_content = isset( $attributes['content'] ) ? trim( (string) $attributes['content'] ) : '';

if ( '' === $guardrail_blocks_content ) {
	return;
}

$guardrail_blocks_level = isset( $block->context['guardrail-blocks/headingLevel'] )
	? (int) $block->context['guardrail-blocks/headingLevel']
	: 2;

// Clamp to the derived-heading range: the page title owns H1, and HTML
// stops at H6. Mirrors clampHeadingLevel() in src/utils/outline.ts.
$guardrail_blocks_level = min( max( $guardrail_blocks_level, 2 ), 6 );

// Anchor id so the Table of Contents can link here (same algorithm and
// document order as the ToC walker → identical ids).
$guardrail_blocks_anchor = \GuardrailBlocks\Outline::unique_anchor( $guardrail_blocks_content );

printf(
	'<h%1$d %2$s>%3$s</h%1$d>',
	(int) $guardrail_blocks_level,
	get_block_wrapper_attributes( array( 'id' => $guardrail_blocks_anchor ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped by core.
	wp_kses_post( $guardrail_blocks_content )
);
