<?php
/**
 * Render tests for the Accordion Item dynamic block (ARIA disclosure
 * pattern, enforcement layer 3).
 *
 * @package GuardrailBlocks\Tests
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

final class AccordionItemRenderTest extends TestCase {

	private const TEMPLATE = GUARDRAIL_BLOCKS_PLUGIN_ROOT . '/src/accordion-item/render.php';

	/**
	 * @param array  $attributes Attributes.
	 * @param array  $context    Context.
	 * @param string $content    Inner content.
	 */
	private function render( array $attributes, array $context = array(), string $content = '<p>Panel body</p>' ): string {
		$block          = new class() {
			/**
			 * @var array
			 */
			public $context = array();
		};
		$block->context = $context;

		ob_start();
		include self::TEMPLATE;
		return (string) ob_get_clean();
	}

	public function test_header_button_has_full_aria_wiring(): void {
		$html = $this->render( array( 'title' => 'Shipping' ) );

		// Real button, collapsed by default.
		$this->assertStringContainsString( '<button type="button"', $html );
		$this->assertStringContainsString( 'aria-expanded="false"', $html );

		// Trigger controls the panel; panel is labelled by the trigger.
		$this->assertMatchesRegularExpression(
			'/id="(?<id>[\w-]+)-trigger".*aria-controls="\k<id>-panel"/s',
			$html
		);
		$this->assertMatchesRegularExpression(
			'/id="(?<id>[\w-]+)-panel"[^>]*aria-labelledby="\k<id>-trigger"/s',
			$html
		);
		$this->assertStringContainsString( 'role="region"', $html );
	}

	public function test_header_heading_level_derives_from_section_context(): void {
		$default = $this->render( array( 'title' => 'T' ) );
		$this->assertStringContainsString( '<h2 class="ab-accordion-item__heading">', $default );

		$nested = $this->render(
			array( 'title' => 'T' ),
			array( 'guardrail-blocks/headingLevel' => 4 )
		);
		$this->assertStringContainsString( '<h4 class="ab-accordion-item__heading">', $nested );

		$overflow = $this->render(
			array( 'title' => 'T' ),
			array( 'guardrail-blocks/headingLevel' => 42 )
		);
		$this->assertStringContainsString( '<h6 class="ab-accordion-item__heading">', $overflow );
	}

	public function test_panel_is_visible_without_javascript(): void {
		$html = $this->render( array( 'title' => 'T' ) );

		// Progressive enhancement: no `hidden` attribute in server markup —
		// the Interactivity API applies the collapsed state on hydration.
		$this->assertStringNotContainsString( ' hidden', $html );
		$this->assertStringContainsString( 'data-wp-bind--hidden="!context.isOpen"', $html );
		$this->assertStringContainsString( '<p>Panel body</p>', $html );
	}

	public function test_interactivity_directives_present(): void {
		$html = $this->render( array( 'title' => 'T' ) );

		$this->assertStringContainsString( 'data-wp-interactive="guardrail-blocks/accordion"', $html );
		$this->assertStringContainsString( 'data-wp-on--click="actions.toggle"', $html );
		$this->assertStringContainsString( 'data-wp-on--keydown="actions.handleKeydown"', $html );
		$this->assertStringContainsString( 'data-wp-bind--aria-expanded="context.isOpen"', $html );
	}

	public function test_unique_ids_across_items(): void {
		$first  = $this->render( array( 'title' => 'A' ) );
		$second = $this->render( array( 'title' => 'B' ) );

		preg_match( '/id="([\w-]+)-trigger"/', $first, $m1 );
		preg_match( '/id="([\w-]+)-trigger"/', $second, $m2 );

		$this->assertNotEmpty( $m1[1] );
		$this->assertNotEmpty( $m2[1] );
		$this->assertNotSame( $m1[1], $m2[1] );
	}

	public function test_title_is_escaped(): void {
		$html = $this->render( array( 'title' => '<img onerror=x>' ) );

		$this->assertStringNotContainsString( '<img', $html );
	}

	public function test_renders_nothing_when_completely_empty(): void {
		$this->assertSame( '', $this->render( array( 'title' => '' ), array(), '' ) );
	}
}
