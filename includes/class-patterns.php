<?php
/**
 * Block pattern registration.
 *
 * Patterns compose the block set into ready-made, guarantee-preserving
 * structures. Note that heading levels never appear in pattern markup —
 * they derive from Section nesting wherever the pattern is inserted.
 *
 * @package GuardrailBlocks
 */

declare( strict_types=1 );

namespace GuardrailBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the pattern category and all patterns.
 */
class Patterns {

	/**
	 * Attach WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register category + patterns.
	 */
	public function register(): void {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		register_block_pattern_category(
			'guardrail-blocks',
			array( 'label' => __( 'Guardrail Blocks', 'guardrail-blocks' ) )
		);

		foreach ( $this->get_patterns() as $name => $pattern ) {
			register_block_pattern( 'guardrail-blocks/' . $name, $pattern );
		}
	}

	/**
	 * Pattern definitions.
	 *
	 * @return array<string, array{title: string, description: string, categories: array, content: string}>
	 */
	private function get_patterns(): array {
		$hero = '<!-- wp:guardrail-blocks/section {"headingLevel":2} -->
<section class="wp-block-guardrail-blocks-section"><!-- wp:guardrail-blocks/heading {"content":"' . esc_attr__( 'A headline that welcomes everyone', 'guardrail-blocks' ) . '"} /-->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Introduce what you do in a sentence or two. This hero is a proper section with a derived H2 — reorder it anywhere and the outline stays valid.', 'guardrail-blocks' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:guardrail-blocks/button {"text":"' . esc_attr__( 'Get started', 'guardrail-blocks' ) . '","url":"#"} /--></section>
<!-- /wp:guardrail-blocks/section -->';

		$feature_grid = '<!-- wp:guardrail-blocks/section {"headingLevel":2} -->
<section class="wp-block-guardrail-blocks-section"><!-- wp:guardrail-blocks/heading {"content":"' . esc_attr__( 'What you get', 'guardrail-blocks' ) . '"} /-->

<!-- wp:guardrail-blocks/card-grid -->
<div class="wp-block-guardrail-blocks-card-grid"><!-- wp:guardrail-blocks/card -->
<article class="wp-block-guardrail-blocks-card"><!-- wp:guardrail-blocks/heading {"content":"' . esc_attr__( 'First feature', 'guardrail-blocks' ) . '"} /-->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Card titles are Accessible Headings, so they join the page outline at the correct level automatically.', 'guardrail-blocks' ) . '</p>
<!-- /wp:paragraph --></article>
<!-- /wp:guardrail-blocks/card -->

<!-- wp:guardrail-blocks/card -->
<article class="wp-block-guardrail-blocks-card"><!-- wp:guardrail-blocks/heading {"content":"' . esc_attr__( 'Second feature', 'guardrail-blocks' ) . '"} /-->

<!-- wp:paragraph -->
<p>' . esc_html__( 'The grid adapts to available space — no breakpoint settings to get wrong.', 'guardrail-blocks' ) . '</p>
<!-- /wp:paragraph --></article>
<!-- /wp:guardrail-blocks/card -->

<!-- wp:guardrail-blocks/card -->
<article class="wp-block-guardrail-blocks-card"><!-- wp:guardrail-blocks/heading {"content":"' . esc_attr__( 'Third feature', 'guardrail-blocks' ) . '"} /-->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Everything inherits your theme’s colors and type, so nothing fights your design.', 'guardrail-blocks' ) . '</p>
<!-- /wp:paragraph --></article>
<!-- /wp:guardrail-blocks/card --></div>
<!-- /wp:guardrail-blocks/card-grid --></section>
<!-- /wp:guardrail-blocks/section -->';

		$faq = '<!-- wp:guardrail-blocks/section {"headingLevel":2} -->
<section class="wp-block-guardrail-blocks-section"><!-- wp:guardrail-blocks/heading {"content":"' . esc_attr__( 'Frequently asked questions', 'guardrail-blocks' ) . '"} /-->

<!-- wp:guardrail-blocks/accordion -->
<div class="wp-block-guardrail-blocks-accordion" data-wp-interactive="guardrail-blocks/accordion"><!-- wp:guardrail-blocks/accordion-item {"title":"' . esc_attr__( 'How does this work?', 'guardrail-blocks' ) . '"} -->
<!-- wp:paragraph -->
<p>' . esc_html__( 'Each item is a real button inside a correctly-leveled heading, with keyboard support and screen-reader announcements built in.', 'guardrail-blocks' ) . '</p>
<!-- /wp:paragraph -->
<!-- /wp:guardrail-blocks/accordion-item -->

<!-- wp:guardrail-blocks/accordion-item {"title":"' . esc_attr__( 'Can I add more questions?', 'guardrail-blocks' ) . '"} -->
<!-- wp:paragraph -->
<p>' . esc_html__( 'Yes — duplicate an item or add a new one; the semantics come along automatically.', 'guardrail-blocks' ) . '</p>
<!-- /wp:paragraph -->
<!-- /wp:guardrail-blocks/accordion-item --></div>
<!-- /wp:guardrail-blocks/accordion --></section>
<!-- /wp:guardrail-blocks/section -->';

		$cta = '<!-- wp:guardrail-blocks/section {"headingLevel":2} -->
<section class="wp-block-guardrail-blocks-section"><!-- wp:guardrail-blocks/heading {"content":"' . esc_attr__( 'Ready when you are', 'guardrail-blocks' ) . '"} /-->

<!-- wp:paragraph -->
<p>' . esc_html__( 'One clear call to action, contrast-checked on every page view.', 'guardrail-blocks' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:guardrail-blocks/button {"text":"' . esc_attr__( 'Contact us', 'guardrail-blocks' ) . '","url":"#"} /--></section>
<!-- /wp:guardrail-blocks/section -->';

		return array(
			'hero'         => array(
				'title'       => __( 'Accessible hero', 'guardrail-blocks' ),
				'description' => __( 'Headline, intro, and a contrast-safe call-to-action button.', 'guardrail-blocks' ),
				'categories'  => array( 'guardrail-blocks' ),
				'content'     => $hero,
			),
			'feature-grid' => array(
				'title'       => __( 'Feature card grid', 'guardrail-blocks' ),
				'description' => __( 'Three cards whose titles join the heading outline automatically.', 'guardrail-blocks' ),
				'categories'  => array( 'guardrail-blocks' ),
				'content'     => $feature_grid,
			),
			'faq'          => array(
				'title'       => __( 'FAQ accordion', 'guardrail-blocks' ),
				'description' => __( 'Questions and answers with correct disclosure semantics and keyboard support.', 'guardrail-blocks' ),
				'categories'  => array( 'guardrail-blocks' ),
				'content'     => $faq,
			),
			'cta-band'     => array(
				'title'       => __( 'Call-to-action band', 'guardrail-blocks' ),
				'description' => __( 'A short pitch with a contrast-safe button.', 'guardrail-blocks' ),
				'categories'  => array( 'guardrail-blocks' ),
				'content'     => $cta,
			),
			'starter-page' => array(
				'title'       => __( 'Starter Brochure Page', 'guardrail-blocks' ),
				'description' => __( 'A complete accessible page skeleton: hero, features, FAQ, and call to action — valid heading outline guaranteed.', 'guardrail-blocks' ),
				'categories'  => array( 'guardrail-blocks' ),
				'content'     => $hero . "\n\n" . $feature_grid . "\n\n" . $faq . "\n\n" . $cta,
			),
		);
	}
}
