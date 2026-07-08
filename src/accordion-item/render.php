<?php
/**
 * Accordion Item — server render.
 *
 * WAI-ARIA APG disclosure/accordion pattern, enforced at render time:
 * - The header is a real <button> inside a heading whose level derives
 *   from the surrounding Accessible Section context (Guarantee B applies
 *   inside interactive blocks too).
 * - aria-expanded / aria-controls / aria-labelledby wiring with unique,
 *   collision-free ids generated per render.
 * - Progressive enhancement: the panel is rendered visible (no `hidden`),
 *   so content stays reachable without JavaScript; the Interactivity API
 *   applies the collapsed state on hydration.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks (panel content).
 * @var WP_Block $block      Block instance (context source).
 *
 * @package AccessibleBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$accessible_blocks_title = isset( $attributes['title'] ) ? trim( (string) $attributes['title'] ) : '';

if ( '' === $accessible_blocks_title && '' === trim( (string) $content ) ) {
	return;
}

$accessible_blocks_level = isset( $block->context['accessible-blocks/headingLevel'] )
	? (int) $block->context['accessible-blocks/headingLevel']
	: 2;
$accessible_blocks_level = min( max( $accessible_blocks_level, 2 ), 6 );

$accessible_blocks_id = wp_unique_id( 'ab-accordion-item-' );

$accessible_blocks_wrapper = get_block_wrapper_attributes(
	array(
		'data-wp-interactive' => 'accessible-blocks/accordion',
		'data-wp-context'     => wp_json_encode( array( 'isOpen' => false ) ),
	)
);

printf(
	'<div %1$s>' .
		'<h%2$d class="ab-accordion-item__heading">' .
			'<button type="button" id="%3$s-trigger" class="ab-accordion-item__trigger"' .
				' aria-controls="%3$s-panel" aria-expanded="false"' .
				' data-wp-bind--aria-expanded="context.isOpen"' .
				' data-wp-on--click="actions.toggle"' .
				' data-wp-on--keydown="actions.handleKeydown">' .
				'<span class="ab-accordion-item__title">%4$s</span>' .
				'<span class="ab-accordion-item__icon" aria-hidden="true"></span>' .
			'</button>' .
		'</h%2$d>' .
		'<div id="%3$s-panel" class="ab-accordion-item__panel" role="region"' .
			' aria-labelledby="%3$s-trigger"' .
			' data-wp-bind--hidden="!context.isOpen">' .
			'%5$s' .
		'</div>' .
	'</div>',
	$accessible_blocks_wrapper, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped by get_block_wrapper_attributes().
	(int) $accessible_blocks_level,
	esc_attr( $accessible_blocks_id ),
	esc_html( $accessible_blocks_title ),
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inner blocks, escaped during their own render.
);
