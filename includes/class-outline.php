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
