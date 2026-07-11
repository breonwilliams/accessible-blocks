<?php
/**
 * Notice — server render.
 *
 * The semantic role and visible label are derived from the type on every
 * request (enforcement layer 3): info → note, success/warning → status,
 * error → alert. The label is translated server-side and guarantees the
 * meaning never relies on color alone (WCAG 1.4.1).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks (notice body).
 * @var WP_Block $block      Block instance.
 *
 * @package GuardrailBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '' === trim( (string) $content ) ) {
	return;
}

$guardrail_blocks_type = isset( $attributes['type'] ) ? (string) $attributes['type'] : 'info';

if ( ! in_array( $guardrail_blocks_type, array( 'info', 'success', 'warning', 'error' ), true ) ) {
	$guardrail_blocks_type = 'info';
}

$guardrail_blocks_roles = array(
	'info'    => 'note',
	'success' => 'status',
	'warning' => 'status',
	'error'   => 'alert',
);

$guardrail_blocks_labels = array(
	'info'    => __( 'Note', 'guardrail-blocks' ),
	'success' => __( 'Success', 'guardrail-blocks' ),
	'warning' => __( 'Warning', 'guardrail-blocks' ),
	'error'   => __( 'Error', 'guardrail-blocks' ),
);

$guardrail_blocks_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'ab-notice ab-notice--' . $guardrail_blocks_type,
		'role'  => $guardrail_blocks_roles[ $guardrail_blocks_type ],
	)
);

printf(
	'<div %1$s><strong class="ab-notice__label">%2$s</strong><div class="ab-notice__content">%3$s</div></div>',
	$guardrail_blocks_wrapper, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped by core.
	esc_html( $guardrail_blocks_labels[ $guardrail_blocks_type ] ),
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inner blocks, escaped during their own render.
);
