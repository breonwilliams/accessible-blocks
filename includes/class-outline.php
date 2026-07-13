<?php
/**
 * Heading-outline engine — PHP mirror for render-time use.
 *
 * Mirrors `src/utils/outline.ts` (same derivation rules; keep in lockstep):
 * walks a parsed block tree, resolving every heading's level exactly like
 * the runtime does, and generates unique anchor ids so the Table of
 * Contents and the Accessible Heading render agree on link targets.
 *
 * @package GuardrailBlocks
 */

declare( strict_types=1 );

namespace GuardrailBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static outline walking + anchor generation.
 */
class Outline {

	public const MIN_LEVEL = 2;
	public const MAX_LEVEL = 6;

	/**
	 * Anchors handed out during this request, for de-duplication. The
	 * Accessible Heading render and the ToC walker both consume headings in
	 * document order with the same algorithm, so their sequences agree.
	 *
	 * @var array<string, int>
	 */
	private static $used_anchors = array();

	/**
	 * Reset anchor bookkeeping (tests; multi-loop edge cases).
	 */
	public static function reset_anchors(): void {
		self::$used_anchors = array();
	}

	/**
	 * Clamp a level into the derived range (H2–H6). Mirrors
	 * clampHeadingLevel() in outline.ts.
	 *
	 * @param mixed $level Candidate level.
	 */
	public static function clamp_level( $level ): int {
		$n = (int) $level;
		return min( max( $n, self::MIN_LEVEL ), self::MAX_LEVEL );
	}

	/**
	 * A unique anchor id for a heading, derived from its text.
	 *
	 * @param string $text Heading plain text.
	 */
	public static function unique_anchor( string $text ): string {
		$slug = sanitize_title( wp_strip_all_tags( $text ) );

		if ( '' === $slug ) {
			$slug = 'section';
		}

		$anchor = 'ab-' . $slug;

		if ( isset( self::$used_anchors[ $anchor ] ) ) {
			++self::$used_anchors[ $anchor ];
			return $anchor . '-' . self::$used_anchors[ $anchor ];
		}

		self::$used_anchors[ $anchor ] = 1;
		return $anchor;
	}

	/**
	 * Walk parsed blocks and return every heading in document order.
	 *
	 * Mirrors collectOutline() in outline.ts, plus anchor resolution using
	 * its own fresh registry (independent of the render-time one, but the
	 * identical algorithm over the identical order yields identical ids).
	 *
	 * @param array $blocks Parsed blocks (from parse_blocks()).
	 * @return array<int, array{level: int, text: string, anchor: string, source: string}>
	 */
	public static function collect( array $blocks ): array {
		$registry = array();
		return self::walk( $blocks, null, $registry );
	}

	/**
	 * Render-time enforcement of Guarantee B (`render_block_data` filter).
	 *
	 * Level providers (Section, Card, Accordion) serve heading context from a
	 * stored `headingLevel` attribute that the editor keeps in sync. The
	 * stored value is a cache, not a source of truth — it can briefly go
	 * stale (e.g. a save that races the editor's self-heal cascade after a
	 * deep reorder). This filter rewrites every provider's attribute from the
	 * block tree's *actual* nesting before each render, using the exact
	 * derivation rules of walk()/outline.ts, so a stale stored level can
	 * never reach the page.
	 *
	 * Since WP 6.5, core applies `render_block_data` not only to top-level
	 * blocks (render_block()) but *again* to every inner block during
	 * WP_Block::render(). Those inner applications carry no ancestry, so
	 * re-deriving there would treat every nested provider as top-level and
	 * flatten the outline. Top-level applications have `$parent_block` null;
	 * inner ones pass the parent WP_Block — so we rewrite the whole tree once
	 * at the top level and no-op for inner re-applications.
	 *
	 * @param array         $parsed_block A parsed block, possibly with children.
	 * @param array|null    $source_block Unfiltered copy (unused).
	 * @param \WP_Block|null $parent_block Parent block for inner applications,
	 *                                     null for top-level blocks.
	 * @return array The block with structurally-derived heading levels.
	 */
	public static function enforce_levels_filter( $parsed_block, $source_block = null, $parent_block = null ) {
		if ( ! is_array( $parsed_block ) || null !== $parent_block ) {
			return $parsed_block;
		}

		return self::enforce_levels( $parsed_block );
	}

	/**
	 * Recursively rewrite provider heading levels from actual nesting.
	 *
	 * @param array    $parsed_block  A parsed block, possibly with children.
	 * @param int|null $context_level Level provided by the nearest enclosing
	 *                                provider (null at the document root).
	 * @return array The block with structurally-derived heading levels.
	 */
	public static function enforce_levels( array $parsed_block, ?int $context_level = null ): array {
		$level_providers = array(
			'guardrail-blocks/section',
			'guardrail-blocks/card',
			'guardrail-blocks/accordion',
		);

		$child_context = $context_level;

		if ( in_array( $parsed_block['blockName'] ?? '', $level_providers, true ) ) {
			$derived = null === $context_level ? self::MIN_LEVEL : self::clamp_level( $context_level + 1 );

			$parsed_block['attrs']                 = is_array( $parsed_block['attrs'] ?? null ) ? $parsed_block['attrs'] : array();
			$parsed_block['attrs']['headingLevel'] = $derived;

			$child_context = $derived;
		}

		if ( ! empty( $parsed_block['innerBlocks'] ) ) {
			foreach ( $parsed_block['innerBlocks'] as $i => $inner_block ) {
				$parsed_block['innerBlocks'][ $i ] = self::enforce_levels( $inner_block, $child_context );
			}
		}

		return $parsed_block;
	}

	/**
	 * Recursive walker.
	 *
	 * @param array             $blocks        Parsed blocks.
	 * @param int|null          $context_level Level provided by the nearest Section.
	 * @param array<string,int> $registry      Anchor registry (by reference).
	 */
	private static function walk( array $blocks, ?int $context_level, array &$registry ): array {
		$entries = array();

		// Blocks whose children's headings sit one level deeper (mirrors
		// LEVEL_PROVIDER_BLOCKS in outline.ts).
		$level_providers = array(
			'guardrail-blocks/section',
			'guardrail-blocks/card',
			'guardrail-blocks/accordion',
		);

		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? '';

			if ( in_array( $name, $level_providers, true ) ) {
				$provided = null === $context_level ? self::MIN_LEVEL : self::clamp_level( $context_level + 1 );
				$entries  = array_merge( $entries, self::walk( $block['innerBlocks'] ?? array(), $provided, $registry ) );
				continue;
			}

			if ( 'guardrail-blocks/accordion-item' === $name ) {
				$text = wp_strip_all_tags( (string) ( $block['attrs']['title'] ?? '' ) );

				if ( '' !== trim( $text ) ) {
					$entries[] = array(
						'level'  => self::clamp_level( $context_level ?? self::MIN_LEVEL ),
						'text'   => trim( $text ),
						// Item ids are per-render unique; not reliably
						// linkable from a ToC.
						'anchor' => '',
						'source' => 'derived',
					);
				}

				$entries = array_merge( $entries, self::walk( $block['innerBlocks'] ?? array(), $context_level, $registry ) );
				continue;
			}

			if ( 'guardrail-blocks/heading' === $name ) {
				$text = wp_strip_all_tags( (string) ( $block['attrs']['content'] ?? '' ) );

				if ( '' === trim( $text ) ) {
					continue;
				}

				$entries[] = array(
					'level'  => self::clamp_level( $context_level ?? self::MIN_LEVEL ),
					'text'   => trim( $text ),
					'anchor' => self::registry_anchor( $text, $registry ),
					'source' => 'derived',
				);
				continue;
			}

			if ( 'core/heading' === $name ) {
				$text = wp_strip_all_tags( (string) ( $block['innerHTML'] ?? '' ) );

				if ( '' === trim( $text ) ) {
					continue;
				}

				$level     = (int) ( $block['attrs']['level'] ?? 2 );
				$entries[] = array(
					'level'  => min( max( $level, 1 ), 6 ),
					'text'   => trim( $text ),
					// Core headings link only when the author set an anchor.
					'anchor' => isset( $block['attrs']['anchor'] ) ? (string) $block['attrs']['anchor'] : '',
					'source' => 'manual',
				);
				continue;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$entries = array_merge( $entries, self::walk( $block['innerBlocks'], $context_level, $registry ) );
			}
		}

		return $entries;
	}

	/**
	 * Anchor generation against a caller-owned registry (same algorithm as
	 * unique_anchor(), without touching request-global state).
	 *
	 * @param string            $text     Heading text.
	 * @param array<string,int> $registry Registry (by reference).
	 */
	private static function registry_anchor( string $text, array &$registry ): string {
		$slug = sanitize_title( wp_strip_all_tags( $text ) );

		if ( '' === $slug ) {
			$slug = 'section';
		}

		$anchor = 'ab-' . $slug;

		if ( isset( $registry[ $anchor ] ) ) {
			++$registry[ $anchor ];
			return $anchor . '-' . $registry[ $anchor ];
		}

		$registry[ $anchor ] = 1;
		return $anchor;
	}
}
