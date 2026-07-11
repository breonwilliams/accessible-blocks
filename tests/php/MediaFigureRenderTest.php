<?php
/**
 * Render tests for the Media Figure dynamic block (Guarantee C).
 *
 * @package GuardrailBlocks\Tests
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

final class MediaFigureRenderTest extends TestCase {

	private const TEMPLATE = GUARDRAIL_BLOCKS_PLUGIN_ROOT . '/src/media-figure/render.php';

	/**
	 * @param array $attributes Attributes.
	 */
	private function render( array $attributes ): string {
		$content = '';
		$block   = new class() {
			/**
			 * @var array
			 */
			public $context = array();
		};

		ob_start();
		include self::TEMPLATE;
		return (string) ob_get_clean();
	}

	public function test_hero_gets_high_priority_eager_loading(): void {
		$html = $this->render(
			array(
				'mediaId' => 42,
				'alt'     => 'A sunrise',
				'isHero'  => true,
			)
		);

		$this->assertStringContainsString( 'fetchpriority="high"', $html );
		$this->assertStringContainsString( 'loading="eager"', $html );
		$this->assertStringContainsString( 'alt="A sunrise"', $html );
		$this->assertStringContainsString( '<figure', $html );
	}

	public function test_non_hero_lazy_loads(): void {
		$html = $this->render(
			array(
				'mediaId' => 42,
				'alt'     => 'x',
			)
		);

		$this->assertStringContainsString( 'loading="lazy"', $html );
		$this->assertStringNotContainsString( 'fetchpriority', $html );
	}

	public function test_dimensions_and_sizes_always_present(): void {
		$html = $this->render(
			array(
				'mediaId' => 42,
				'alt'     => 'x',
			)
		);

		// CLS guarantee: explicit dimensions; responsive guarantee: sizes.
		$this->assertStringContainsString( 'width=', $html );
		$this->assertStringContainsString( 'height=', $html );
		$this->assertStringContainsString( 'sizes="(max-width: 782px) 100vw, 782px"', $html );
	}

	public function test_caption_renders_in_figcaption(): void {
		$html = $this->render(
			array(
				'mediaId' => 42,
				'alt'     => 'x',
				'caption' => 'Taken at dawn',
			)
		);

		$this->assertStringContainsString( '<figcaption class="ab-media-figure__caption">Taken at dawn</figcaption>', $html );
	}

	public function test_renders_nothing_without_valid_image(): void {
		$this->assertSame( '', $this->render( array() ) );
		$this->assertSame( '', $this->render( array( 'mediaId' => 999 ) ) );
	}
}
