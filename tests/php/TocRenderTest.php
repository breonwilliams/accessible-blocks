<?php
/**
 * Render tests for the Table of Contents dynamic block.
 *
 * @package GuardrailBlocks\Tests
 */

declare( strict_types=1 );

use GuardrailBlocks\Outline;
use PHPUnit\Framework\TestCase;

final class TocRenderTest extends TestCase {

	private const TEMPLATE = GUARDRAIL_BLOCKS_PLUGIN_ROOT . '/src/toc/render.php';

	protected function setUp(): void {
		Outline::reset_anchors();
	}

	/**
	 * @param array $parsed_blocks Parsed-block tree the "post" contains.
	 * @param int   $post_id       Context post id.
	 */
	private function render( array $parsed_blocks, int $post_id = 7 ): string {
		$post               = new stdClass();
		$post->ID           = 7;
		$post->post_content = (string) json_encode( $parsed_blocks ); // phpcs:ignore

		$GLOBALS['guardrail_blocks_test_post'] = $post;

		$attributes = array();
		$content    = '';
		$block      = new class() {
			/**
			 * @var array
			 */
			public $context = array();
		};

		$block->context = array( 'postId' => $post_id );

		ob_start();
		include self::TEMPLATE;
		return (string) ob_get_clean();
	}

	/**
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

	public function test_renders_nav_landmark_with_links(): void {
		$html = $this->render(
			array(
				self::block(
					'guardrail-blocks/section',
					array(),
					array(
						self::block( 'guardrail-blocks/heading', array( 'content' => 'Overview' ) ),
					)
				),
			)
		);

		$this->assertStringContainsString( '<nav', $html );
		$this->assertStringContainsString( 'aria-label="Table of contents"', $html );
		$this->assertStringContainsString( '<a href="#ab-overview">Overview</a>', $html );
	}

	public function test_nested_sections_produce_nested_lists(): void {
		$html = $this->render(
			array(
				self::block(
					'guardrail-blocks/section',
					array(),
					array(
						self::block( 'guardrail-blocks/heading', array( 'content' => 'Parent' ) ),
						self::block(
							'guardrail-blocks/section',
							array(),
							array(
								self::block( 'guardrail-blocks/heading', array( 'content' => 'Child' ) ),
							)
						),
					)
				),
			)
		);

		// Child list nests inside the parent item.
		$this->assertMatchesRegularExpression(
			'/<li[^>]*>.*Parent.*<ol[^>]*>.*Child.*<\/ol><\/li>/s',
			$html
		);
	}

	public function test_core_heading_without_anchor_renders_as_text(): void {
		$html = $this->render(
			array(
				self::block( 'core/heading', array( 'level' => 2 ), array(), '<h2>Unlinked</h2>' ),
			)
		);

		$this->assertStringContainsString( '<span>Unlinked</span>', $html );
		$this->assertStringNotContainsString( '<a href', $html );
	}

	public function test_renders_nothing_without_headings_or_post(): void {
		$this->assertSame( '', $this->render( array() ) );
		$this->assertSame( '', $this->render( array(), 999 ) );
	}

	public function test_toc_ids_match_heading_render_ids(): void {
		// The critical contract: heading render.php and the ToC walker
		// produce identical anchors for the same document order.
		$parsed = array(
			self::block(
				'guardrail-blocks/section',
				array(),
				array(
					self::block( 'guardrail-blocks/heading', array( 'content' => 'Overview' ) ),
					self::block( 'guardrail-blocks/heading', array( 'content' => 'Overview' ) ),
				)
			),
		);

		$toc = $this->render( $parsed );

		// Simulate the two heading renders in document order.
		Outline::reset_anchors();
		$first  = Outline::unique_anchor( 'Overview' );
		$second = Outline::unique_anchor( 'Overview' );

		$this->assertStringContainsString( 'href="#' . $first . '"', $toc );
		$this->assertStringContainsString( 'href="#' . $second . '"', $toc );
	}
}
