<?php
/**
 * Media Figure — server render (Guarantee C: performance-correct media).
 *
 * Rendered through core's responsive-image pipeline on every request:
 * - wp_get_attachment_image() emits srcset/sizes and explicit
 *   width/height (CLS protection) from the *current* attachment metadata.
 * - Hero images get fetchpriority="high" + eager loading (LCP); everything
 *   else lazy-loads.
 * - Alt resolution: block attribute override → media-library alt. The
 *   editor warns when both are empty (enforcement layer 2).
 *
 * Deliberately NOT an image optimizer — compression/format conversion is
 * core/host territory (Foundation §4).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused; dynamic).
 * @var WP_Block $block      Block instance.
 *
 * @package GuardrailBlocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$guardrail_blocks_media_id = isset( $attributes['mediaId'] ) ? (int) $attributes['mediaId'] : 0;

if ( ! $guardrail_blocks_media_id || ! wp_attachment_is_image( $guardrail_blocks_media_id ) ) {
	return;
}

$guardrail_blocks_alt     = isset( $attributes['alt'] ) ? trim( (string) $attributes['alt'] ) : '';
$guardrail_blocks_caption = isset( $attributes['caption'] ) ? trim( (string) $attributes['caption'] ) : '';
$guardrail_blocks_is_hero = ! empty( $attributes['isHero'] );

$guardrail_blocks_img_attrs = array(
	// Accurate sizes: content images render at content width, not viewport.
	'sizes' => '(max-width: 782px) 100vw, 782px',
);

if ( '' !== $guardrail_blocks_alt ) {
	$guardrail_blocks_img_attrs['alt'] = $guardrail_blocks_alt;
}

if ( $guardrail_blocks_is_hero ) {
	$guardrail_blocks_img_attrs['fetchpriority'] = 'high';
	$guardrail_blocks_img_attrs['loading']       = 'eager';
} else {
	$guardrail_blocks_img_attrs['loading'] = 'lazy';
}

$guardrail_blocks_image = wp_get_attachment_image(
	$guardrail_blocks_media_id,
	'large',
	false,
	$guardrail_blocks_img_attrs
);

if ( '' === $guardrail_blocks_image ) {
	return;
}

printf(
	'<figure %1$s>%2$s%3$s</figure>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped by core.
	$guardrail_blocks_image, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by wp_get_attachment_image().
	'' !== $guardrail_blocks_caption
		? '<figcaption class="ab-media-figure__caption">' . wp_kses_post( $guardrail_blocks_caption ) . '</figcaption>'
		: ''
);
