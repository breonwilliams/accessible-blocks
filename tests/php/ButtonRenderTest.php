<?php
/**
 * Render tests for the Accessible Button dynamic block (Guarantee A,
 * enforcement layer 3).
 *
 * @package GuardrailBlocks\Tests
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

final class ButtonRenderTest extends TestCase {

	private const TEMPLATE = GUARDRAIL_BLOCKS_PLUGIN_ROOT . '/src/button/render.php';

	/**
	 * @param array $attributes Attributes.
	 */
	private function render( array $attributes ): string {
		return guardrail_blocks_test_render( self::TEMPLATE, $attributes );
	}

	public function test_derives_foreground_from_live_palette(): void {
		$html = $this->render(
			array(
				'text'           => 'Go',
				'backgroundSlug' => 'primary',
			)
		);

		// Background tracks the preset var; foreground is the validated
		// concrete color (white passes on the dark primary blue).
		$this->assertStringContainsString( 'var(--wp--preset--color--primary, #1e3a8a)', $html );
		$this->assertStringContainsString( 'color:#ffffff', $html );
	}

	public function test_renders_link_when_url_present(): void {
		$html = $this->render(
			array(
				'text' => 'Go',
				'url'  => 'https://example.com',
			)
		);

		$this->assertStringContainsString( '<a class="ab-button wp-element-button" href="https://example.com"', $html );
	}

	public function test_renders_span_without_url(): void {
		$html = $this->render( array( 'text' => 'Go' ) );

		$this->assertStringContainsString( '<span class="ab-button wp-element-button"', $html );
		$this->assertStringNotContainsString( '<a ', $html );
	}

	public function test_uniform_border_radius_is_inlined_on_the_button(): void {
		// Border support with skip-serialization: the radius must land on the
		// visible element (beats theme wp-element-button rules), not the
		// wrapper.
		$html = $this->render(
			array(
				'text'  => 'Go',
				'style' => array( 'border' => array( 'radius' => '999px' ) ),
			)
		);

		$this->assertStringContainsString( 'style="border-radius:999px;"', $html );
	}

	public function test_split_border_radius_renders_each_corner(): void {
		$html = $this->render(
			array(
				'text'  => 'Go',
				'style' => array(
					'border' => array(
						'radius' => array(
							'topLeft'     => '4px',
							'bottomRight' => '12px',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( 'border-top-left-radius:4px;', $html );
		$this->assertStringContainsString( 'border-bottom-right-radius:12px;', $html );
		$this->assertStringNotContainsString( 'border-top-right-radius', $html );
	}

	public function test_radius_appends_to_color_styles(): void {
		$html = $this->render(
			array(
				'text'           => 'Go',
				'backgroundSlug' => 'primary',
				'style'          => array( 'border' => array( 'radius' => '8px' ) ),
			)
		);

		$this->assertStringContainsString( 'color:#ffffff;', $html );
		$this->assertStringContainsString( 'border-radius:8px;', $html );
	}

	public function test_renders_nothing_for_empty_text(): void {
		$this->assertSame( '', $this->render( array( 'text' => '' ) ) );
		$this->assertSame( '', $this->render( array() ) );
	}

	public function test_escapes_text_content(): void {
		$html = $this->render( array( 'text' => '<script>alert(1)</script>' ) );

		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_unknown_slug_renders_without_inline_colors(): void {
		$html = $this->render(
			array(
				'text'           => 'Go',
				'backgroundSlug' => 'nonexistent',
			)
		);

		$this->assertStringNotContainsString( 'style=', $html );
		$this->assertStringContainsString( 'Go', $html );
	}
}
