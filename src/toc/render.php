<?php
/**
 * Table of Contents — server render.
 *
 * Reads the post's *current* block tree on every request and emits a nav
 * landmark with a nested list mirroring the true outline. There is no
 * stored copy of the outline to go stale (Guarantee B's render-time layer
 * applied to navigation).
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

$guardrail_blocks_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
$guardrail_blocks_post    = $guardrail_blocks_post_id ? get_post( $guardrail_blocks_post_id ) : null;

if ( ! $guardrail_blocks_post ) {
	return;
}

$guardrail_blocks_entries = \GuardrailBlocks\Outline::collect(
	parse_blocks( (string) $guardrail_blocks_post->post_content )
);

if ( empty( $guardrail_blocks_entries ) ) {
	return;
}

/**
 * Build nested list markup from the flat, ordered entries.
 *
 * @param array $entries Outline entries.
 */
$guardrail_blocks_build_list = static function ( array $entries ): string {
	$html  = '';
	$stack = array();
	$prev  = null;

	foreach ( $entries as $entry ) {
		$level = (int) $entry['level'];

		if ( null === $prev ) {
			$html   .= '<ol class="ab-toc__list">';
			$stack[] = $level;
		} elseif ( $level > $prev ) {
			// One nested list per document-order step deeper (a valid
			// outline only ever steps one level at a time; hand-picked core
			// levels that skip are flattened to a single step).
			$html   .= '<ol class="ab-toc__list">';
			$stack[] = $level;
		} else {
			$html       .= '</li>';
			$stack_depth = count( $stack );
			while ( $stack_depth > 1 && end( $stack ) > $level ) {
				array_pop( $stack );
				--$stack_depth;
				$html .= '</ol></li>';
			}
		}

		$guardrail_blocks_text = esc_html( $entry['text'] );
		$html                  .= '<li class="ab-toc__item">';
		$html                  .= '' !== $entry['anchor']
			? '<a href="#' . esc_attr( $entry['anchor'] ) . '">' . $guardrail_blocks_text . '</a>'
			: '<span>' . $guardrail_blocks_text . '</span>';

		$prev = $level;
	}

	$html       .= '</li>';
	$stack_depth = count( $stack );
	while ( $stack_depth > 1 ) {
		array_pop( $stack );
		--$stack_depth;
		$html .= '</ol></li>';
	}
	$html .= '</ol>';

	return $html;
};

printf(
	'<nav %1$s aria-label="%2$s">%3$s</nav>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped by core.
	esc_attr__( 'Table of contents', 'guardrail-blocks' ),
	$guardrail_blocks_build_list( $guardrail_blocks_entries ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped during construction above.
);
