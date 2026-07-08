<?php
/**
 * Tests for AccessibleBlocks\Outline — must stay in lockstep with the Jest
 * suite for src/utils/outline.ts.
 *
 * @package AccessibleBlocks\Tests
 */

declare( strict_types=1 );

use AccessibleBlocks\Outline;
use PHPUnit\Framework\TestCase;

final class OutlineTest extends TestCase {

	protected function setUp(): void {
		Outline::reset_anchors();
	}

	/**
	 * Parsed-block builder.
	 *
	 * @param string $name  Block name.
	 * @param array  $attrs Attributes.
	 * @param array  $inner Inner blocks.
	 * @param string $html  Inner HTML.
	 */
	private static function block( string $name, array $attrs = array(), array $inner = array(), string $html = '' ): array {
		return array(
			'blockName'   => $name,
			'attrs'       => $attrs,
			'innerBlocks' => $inner,
			'innerHTML'   => $html,
		);
	}

	private static function section( array ...$inner ): array {
		return self::block( 'accessible-blocks/section', array(), $inner );
	}

	private static function heading( string $content ): array {
		return self::block( 'accessible-blocks/heading', array( 'content' => $content ) );
	}

	public function test_levels_derive_from_section_nesting(): void {
		$entries = Outline::collect(
			array(
				self::section(
					self::heading( 'Top' ),
					self::section( self::heading( 'Nested' ) )
				),
			)
		);

		$this->assertSame( array( 2, 3 ), array_column( $entries, 'level' ) );
		$this->assertSame( array( 'Top', 'Nested' ), array_column( $entries, 'text' ) );
	}

	public function test_levels_cap_at_six(): void {
		$tree = self::heading( 'Deep' );
		for ( $i = 0; $i < 8; $i++ ) {
			$tree = self::section( $tree );
		}

		$entries = Outline::collect( array( $tree ) );

		$this->assertCount( 1, $entries );
		$this->assertSame( 6, $entries[0]['level'] );
	}

	public function test_context_passes_through_other_containers(): void {
		$entries = Outline::collect(
			array(
				self::section(
					self::block( 'core/group', array(), array( self::heading( 'Grouped' ) ) )
				),
			)
		);

		$this->assertSame( 2, $entries[0]['level'] );
	}

	public function test_core_headings_included_with_manual_level(): void {
		$entries = Outline::collect(
			array(
				self::section( self::heading( 'Ours' ) ),
				self::block( 'core/heading', array( 'level' => 4 ), array(), '<h4>Theirs</h4>' ),
			)
		);

		$this->assertCount( 2, $entries );
		$this->assertSame( 4, $entries[1]['level'] );
		$this->assertSame( 'Theirs', $entries[1]['text'] );
		$this->assertSame( 'manual', $entries[1]['source'] );
	}

	public function test_anchors_are_slugs_with_dedupe(): void {
		$entries = Outline::collect(
			array(
				self::section(
					self::heading( 'Overview' ),
					self::heading( 'Overview' ),
					self::heading( 'Pricing & Plans' )
				),
			)
		);

		$this->assertSame( 'ab-overview', $entries[0]['anchor'] );
		$this->assertSame( 'ab-overview-2', $entries[1]['anchor'] );
		$this->assertSame( 'ab-pricing-plans', $entries[2]['anchor'] );
	}

	public function test_unique_anchor_matches_walker_sequence(): void {
		// The heading render and the ToC walker must produce identical ids
		// for the same document order.
		$this->assertSame( 'ab-overview', Outline::unique_anchor( 'Overview' ) );
		$this->assertSame( 'ab-overview-2', Outline::unique_anchor( 'Overview' ) );

		$entries = Outline::collect(
			array(
				self::section(
					self::heading( 'Overview' ),
					self::heading( 'Overview' )
				),
			)
		);

		$this->assertSame( 'ab-overview', $entries[0]['anchor'] );
		$this->assertSame( 'ab-overview-2', $entries[1]['anchor'] );
	}

	public function test_card_and_accordion_titles_nest_one_deeper(): void {
		$entries = Outline::collect(
			array(
				self::section(
					self::heading( 'Features' ),
					self::block(
						'accessible-blocks/card-grid',
						array(),
						array(
							self::block( 'accessible-blocks/card', array(), array( self::heading( 'Card title' ) ) ),
						)
					),
					self::block(
						'accessible-blocks/accordion',
						array(),
						array(
							self::block( 'accessible-blocks/accordion-item', array( 'title' => 'Question?' ) ),
						)
					)
				),
			)
		);

		$this->assertSame( array( 2, 3, 3 ), array_column( $entries, 'level' ) );
		$this->assertSame( array( 'Features', 'Card title', 'Question?' ), array_column( $entries, 'text' ) );
	}

	public function test_empty_headings_are_skipped(): void {
		$entries = Outline::collect(
			array( self::section( self::heading( '   ' ), self::heading( 'Real' ) ) )
		);

		$this->assertCount( 1, $entries );
		$this->assertSame( 'Real', $entries[0]['text'] );
	}

	public function test_core_heading_without_anchor_gets_empty_anchor(): void {
		$entries = Outline::collect(
			array( self::block( 'core/heading', array( 'level' => 2 ), array(), '<h2>No anchor</h2>' ) )
		);

		$this->assertSame( '', $entries[0]['anchor'] );
	}
}
