<?php
/**
 * Render tests for the Accessible Heading dynamic block (Guarantee B,
 * enforcement layer 3).
 *
 * @package AccessibleBlocks\Tests
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

final class HeadingRenderTest extends TestCase {

	private const TEMPLATE = ACCESSIBLE_BLOCKS_PLUGIN_ROOT . '/src/heading/render.php';

	/**
	 * @param array $attributes Attributes.
	 * @param array $context    Context.
	 */
	private function render( array $attributes, array $context = array() ): string {
		return accessible_blocks_test_render( self::TEMPLATE, $attributes, $context );
	}

	public function test_renders_level_from_context(): void {
		$html = $this->render(
			array( 'content' => 'Hello' ),
			array( 'accessible-blocks/headingLevel' => 3 )
		);

		$this->assertStringStartsWith( '<h3 ', $html );
		$this->assertStringEndsWith( '</h3>', $html );
		$this->assertStringContainsString( 'Hello', $html );
	}

	public function test_renders_h2_without_context(): void {
		$html = $this->render( array( 'content' => 'Standalone' ) );

		$this->assertStringStartsWith( '<h2 ', $html );
	}

	public function test_clamps_overflow_to_h6(): void {
		$html = $this->render(
			array( 'content' => 'Deep' ),
			array( 'accessible-blocks/headingLevel' => 9 )
		);

		$this->assertStringStartsWith( '<h6 ', $html );
	}

	public function test_clamps_h1_attempts_to_h2(): void {
		// The page title owns H1 — context can never produce another one.
		$html = $this->render(
			array( 'content' => 'Sneaky' ),
			array( 'accessible-blocks/headingLevel' => 1 )
		);

		$this->assertStringStartsWith( '<h2 ', $html );
	}

	public function test_clamps_garbage_context_safely(): void {
		$html = $this->render(
			array( 'content' => 'Junk-proof' ),
			array( 'accessible-blocks/headingLevel' => 'potato' )
		);

		$this->assertStringStartsWith( '<h2 ', $html );
	}

	public function test_renders_nothing_for_empty_content(): void {
		$this->assertSame( '', $this->render( array( 'content' => '' ) ) );
		$this->assertSame( '', $this->render( array( 'content' => '   ' ) ) );
		$this->assertSame( '', $this->render( array() ) );
	}
}
