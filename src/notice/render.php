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
 * @package AccessibleBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( '' === trim( (string) $content ) ) {
	return;
}

$accessible_blocks_type = isset( $attributes['type'] ) ? (string) $attributes['type'] : 'info';

if ( ! in_array( $accessible_blocks_type, array( 'info', 'success', 'warning', 'error' ), true ) ) {
	$accessible_blocks_type = 'info';
}

$accessible_blocks_roles = array(
	'info'    => 'note',
	'success' => 'status',
	'warning' => 'status',
	'error'   => 'alert',
);

$accessible_blocks_labels = array(
	'info'    => __( 'Note', 'accessible-blocks' ),
	'success' => __( 'Success', 'accessible-blocks' ),
	'warning' => __( 'Warning', 'accessible-blocks' ),
	'error'   => __( 'Error', 'accessible-blocks' ),
);

$accessible_blocks_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'ab-notice ab-notice--' . $accessible_blocks_type,
		'role'  => $accessible_blocks_roles[ $accessible_blocks_type ],
	)
);

printf(
	'<div %1$s><strong class="ab-notice__label">%2$s</strong><div class="ab-notice__content">%3$s</div></div>',
	$accessible_blocks_wrapper, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped by core.
	esc_html( $accessible_blocks_labels[ $accessible_blocks_type ] ),
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inner blocks, escaped during their own render.
);
