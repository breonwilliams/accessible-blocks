<?php
/**
 * Tests for render-time heading-level enforcement (Outline::enforce_levels).
 *
 * These cover the render_block_data layer of Guarantee B: stored
 * headingLevel attributes are caches and may be stale; enforcement must
 * rewrite them from the tree's actual nesting using the same derivation
 * rules as Outline::collect() / outline.ts.
 *
 * @package GuardrailBlocks\Tests
 */

declare( strict_types=1 );

use GuardrailBlocks\Outline;
use PHPUnit\Framework\TestCase;

final class LevelEnforcementTest extends TestCase {

	/**
	 * @param string $name  Block name.
	 * @param array  $attrs Attributes.
	 * @param array  $inner Inner blocks.
	 */
	private static function block( string $name, array $attrs = array(), array $inner = array() ): array {
		return array(
			'blockName'   => $name,
			'attrs'       => $attrs,
			'innerBlocks' => $inner,
			'innerHTML'   => '',
		);
	}

	public function test_top_level_section_is_forced_to_level_2(): void {
		$stale    = self::block( 'guardrail-blocks/section', array( 'headingLevel' => 5 ) );
		$enforced = Outline::enforce_levels( $stale );

		$this->assertSame( 2, $enforced['attrs']['headingLevel'] );
	}

	public function test_stale_deeply_nested_level_is_corrected(): void {
		// The race this layer exists for: a save captured mid-cascade left
		// the deepest section at 3 when its true depth demands 4.
		$tree = self::block(
			'guardrail-blocks/section',
			array( 'headingLevel' => 2 ),
			array(
				self::block(
					'guardrail-blocks/section',
					array( 'headingLevel' => 3 ),
					array(
						self::block( 'guardrail-blocks/section', array( 'headingLevel' => 3 ) ), // Stale.
					)
				),
			)
		);

		$enforced = Outline::enforce_levels( $tree );

		$this->assertSame( 2, $enforced['attrs']['headingLevel'] );
		$this->assertSame( 3, $enforced['innerBlocks'][0]['attrs']['headingLevel'] );
		$this->assertSame( 4, $enforced['innerBlocks'][0]['innerBlocks'][0]['attrs']['headingLevel'] );
	}

	public function test_card_and_accordion_derive_one_below_their_section(): void {
		$tree = self::block(
			'guardrail-blocks/section',
			array( 'headingLevel' => 2 ),
			array(
				self::block(
					'guardrail-blocks/card-grid',
					array(),
					array(
						self::block( 'guardrail-blocks/card', array( 'headingLevel' => 6 ) ), // Stale.
					)
				),
				self::block( 'guardrail-blocks/accordion', array( 'headingLevel' => 2 ) ), // Stale.
			)
		);

		$enforced = Outline::enforce_levels( $tree );

		$card      = $enforced['innerBlocks'][0]['innerBlocks'][0];
		$accordion = $enforced['innerBlocks'][1];

		$this->assertSame( 3, $card['attrs']['headingLevel'] );
		$this->assertSame( 3, $accordion['attrs']['headingLevel'] );
	}

	public function test_non_provider_containers_pass_context_through(): void {
		// A core/group between sections must not add a level.
		$tree = self::block(
			'guardrail-blocks/section',
			array( 'headingLevel' => 2 ),
			array(
				self::block(
					'core/group',
					array(),
					array(
						self::block( 'guardrail-blocks/section', array( 'headingLevel' => 2 ) ), // Stale.
					)
				),
			)
		);

		$enforced = Outline::enforce_levels( $tree );

		$this->assertSame(
			3,
			$enforced['innerBlocks'][0]['innerBlocks'][0]['attrs']['headingLevel']
		);
	}

	public function test_levels_clamp_at_6_for_very_deep_nesting(): void {
		// Seven sections deep: 2,3,4,5,6,6,6.
		$tree = self::block( 'guardrail-blocks/section' );
		$leaf = &$tree;
		for ( $i = 0; $i < 6; $i++ ) {
			$leaf['innerBlocks'][0] = self::block( 'guardrail-blocks/section' );
			$leaf                   = &$leaf['innerBlocks'][0];
		}
		unset( $leaf );

		$enforced = Outline::enforce_levels( $tree );

		$levels = array();
		$node   = $enforced;
		while ( $node ) {
			$levels[] = $node['attrs']['headingLevel'];
			$node     = $node['innerBlocks'][0] ?? null;
		}

		$this->assertSame( array( 2, 3, 4, 5, 6, 6, 6 ), $levels );
	}

	public function test_missing_attrs_array_is_created(): void {
		$block    = array(
			'blockName'   => 'guardrail-blocks/section',
			'innerBlocks' => array(),
			'innerHTML'   => '',
		);
		$enforced = Outline::enforce_levels( $block );

		$this->assertSame( 2, $enforced['attrs']['headingLevel'] );
	}

	public function test_filter_enforces_for_top_level_blocks(): void {
		$stale    = self::block( 'guardrail-blocks/section', array( 'headingLevel' => 6 ) );
		$enforced = Outline::enforce_levels_filter( $stale, $stale, null );

		$this->assertSame( 2, $enforced['attrs']['headingLevel'] );
	}

	public function test_filter_noops_for_inner_block_reapplications(): void {
		// Since WP 6.5 core re-applies render_block_data per inner block with
		// no ancestry; re-deriving there would flatten nested levels. The
		// top-level pass already rewrote the tree, so inner calls must
		// return the block untouched.
		$nested = self::block( 'guardrail-blocks/section', array( 'headingLevel' => 4 ) );
		$parent = new stdClass(); // Stands in for the parent WP_Block.

		$this->assertSame( $nested, Outline::enforce_levels_filter( $nested, $nested, $parent ) );
	}

	public function test_enforced_levels_agree_with_collect(): void {
		// The two Guarantee B layers must never disagree: enforcing then
		// reading provider attrs yields the same levels collect() reports
		// for the headings inside them.
		$tree = array(
			self::block(
				'guardrail-blocks/section',
				array( 'headingLevel' => 4 ), // Stale.
				array(
					self::block( 'guardrail-blocks/heading', array( 'content' => 'Outer' ) ),
					self::block(
						'guardrail-blocks/section',
						array( 'headingLevel' => 2 ), // Stale.
						array(
							self::block( 'guardrail-blocks/heading', array( 'content' => 'Inner' ) ),
						)
					),
				)
			),
		);

		$enforced  = array_map( array( Outline::class, 'enforce_levels' ), $tree );
		$collected = Outline::collect( $tree );

		$this->assertSame( $collected[0]['level'], $enforced[0]['attrs']['headingLevel'] );
		$this->assertSame( $collected[1]['level'], $enforced[0]['innerBlocks'][1]['attrs']['headingLevel'] );
	}
}
