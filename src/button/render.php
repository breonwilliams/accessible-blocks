<?php
/**
 * Accessible Button — server render.
 *
 * Enforcement layer 3: the foreground color is derived here, on every
 * request, from the *current* theme palette. Editor state stores only the
 * background slug — if the theme's palette changes after publish, the
 * pairing self-corrects. An inaccessible button is not a state this
 * template can output.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused; dynamic).
 * @var WP_Block $block      Block instance.
 *
 * @package AccessibleBlocks
 */

$accessible_blocks_text = isset( $attributes['text'] ) ? trim( (string) $attributes['text'] ) : '';

if ( '' === $accessible_blocks_text ) {
	return;
}

$accessible_blocks_url     = isset( $attributes['url'] ) ? (string) $attributes['url'] : '';
$accessible_blocks_slug    = isset( $attributes['backgroundSlug'] ) ? (string) $attributes['backgroundSlug'] : '';
$accessible_blocks_width   = isset( $attributes['width'] ) ? (string) $attributes['width'] : 'auto';
$accessible_blocks_bgcolor = '' !== $accessible_blocks_slug ? \AccessibleBlocks\Contrast::color_for_slug( $accessible_blocks_slug ) : null;

$accessible_blocks_style = '';

if ( null !== $accessible_blocks_bgcolor ) {
	$accessible_blocks_pairing = \AccessibleBlocks\Contrast::pick_accessible_foreground( $accessible_blocks_bgcolor );

	if ( null !== $accessible_blocks_pairing ) {
		// Background via preset var so it tracks theme.json; foreground as
		// the validated concrete color.
		$accessible_blocks_style = sprintf(
			'background-color:var(--wp--preset--color--%1$s, %2$s);color:%3$s;',
			esc_attr( $accessible_blocks_slug ),
			esc_attr( $accessible_blocks_bgcolor ),
			esc_attr( $accessible_blocks_pairing['foreground']['color'] )
		);
	}
}

$accessible_blocks_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'full' === $accessible_blocks_width ? 'ab-button--full' : '',
	)
);

$accessible_blocks_tag  = '' !== $accessible_blocks_url ? 'a' : 'span';
$accessible_blocks_href = '' !== $accessible_blocks_url ? sprintf( ' href="%s"', esc_url( $accessible_blocks_url ) ) : '';

// $accessible_blocks_href is pre-escaped above; everything else is escaped inline.
// wp-element-button inherits the theme's button styling (theme.json
// elements.button), so an uncolored button still *looks* like a button —
// matching core's own Button block. Author-chosen palette colors override
// it with the contrast-validated pairing.
printf(
	'<div %1$s><%2$s class="ab-button wp-element-button"%3$s%4$s>%5$s</%2$s></div>',
	$accessible_blocks_wrapper, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() output is pre-escaped by core.
	tag_escape( $accessible_blocks_tag ),
	$accessible_blocks_href, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_url() above.
	'' !== $accessible_blocks_style ? ' style="' . $accessible_blocks_style . '"' : '', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_attr() above.
	esc_html( $accessible_blocks_text )
);
