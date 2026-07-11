<?php
/**
 * Tests for GuardrailBlocks\Contrast — must stay in lockstep with the
 * Jest suite for src/utils/contrast.ts (same cases, same expected values).
 *
 * @package GuardrailBlocks\Tests
 */

declare( strict_types=1 );

use GuardrailBlocks\Contrast;
use PHPUnit\Framework\TestCase;

final class ContrastTest extends TestCase {

	public function test_parses_six_digit_hex(): void {
		$this->assertSame(
			array(
				'r' => 26,
				'g' => 43,
				'b' => 60,
			),
			Contrast::parse_color( '#1a2b3c' )
		);
	}

	public function test_parses_three_digit_hex(): void {
		$this->assertSame(
			array(
				'r' => 255,
				'g' => 255,
				'b' => 255,
			),
			Contrast::parse_color( '#fff' )
		);
	}

	public function test_parses_eight_digit_hex_ignoring_alpha(): void {
		$this->assertSame(
			array(
				'r' => 255,
				'g' => 0,
				'b' => 0,
			),
			Contrast::parse_color( '#ff000080' )
		);
	}

	public function test_parses_rgb_and_rgba(): void {
		$this->assertSame(
			array(
				'r' => 255,
				'g' => 0,
				'b' => 0,
			),
			Contrast::parse_color( 'rgb(255, 0, 0)' )
		);
		$this->assertSame(
			array(
				'r' => 0,
				'g' => 128,
				'b' => 255,
			),
			Contrast::parse_color( 'rgba(0, 128, 255, 0.5)' )
		);
	}

	public function test_parses_var_with_color_fallback(): void {
		// Blocksy/Astra-style theme.json palettes.
		$this->assertSame(
			array(
				'r' => 40,
				'g' => 114,
				'b' => 250,
			),
			Contrast::parse_color( 'var(--theme-palette-color-1, #2872fa)' )
		);
		$this->assertSame(
			array(
				'r' => 255,
				'g' => 255,
				'b' => 255,
			),
			Contrast::parse_color( 'var(--a, var(--b, #fff))' )
		);
		$this->assertNull( Contrast::parse_color( 'var(--no-fallback)' ) );
	}

	public function test_rejects_unparseable_values(): void {
		$this->assertNull( Contrast::parse_color( 'var(--wp--preset--color--primary)' ) );
		$this->assertNull( Contrast::parse_color( 'hsl(0, 100%, 50%)' ) );
		$this->assertNull( Contrast::parse_color( 'tomato' ) );
		$this->assertNull( Contrast::parse_color( '#12345' ) );
		$this->assertNull( Contrast::parse_color( 'rgb(300, 0, 0)' ) );
		$this->assertNull( Contrast::parse_color( '' ) );
	}

	public function test_luminance_reference_values(): void {
		$this->assertEqualsWithDelta(
			0.0,
			Contrast::relative_luminance(
				array(
					'r' => 0,
					'g' => 0,
					'b' => 0,
				)
			),
			0.00001
		);
		$this->assertEqualsWithDelta(
			1.0,
			Contrast::relative_luminance(
				array(
					'r' => 255,
					'g' => 255,
					'b' => 255,
				)
			),
			0.00001
		);
		// Pure red per WCAG: 0.2126.
		$this->assertEqualsWithDelta(
			0.2126,
			Contrast::relative_luminance(
				array(
					'r' => 255,
					'g' => 0,
					'b' => 0,
				)
			),
			0.0001
		);
	}

	public function test_black_on_white_is_21_to_1(): void {
		$this->assertEqualsWithDelta(
			21.0,
			Contrast::contrast_ratio(
				array(
					'r' => 0,
					'g' => 0,
					'b' => 0,
				),
				array(
					'r' => 255,
					'g' => 255,
					'b' => 255,
				)
			),
			0.0001
		);
	}

	public function test_known_reference_pairing(): void {
		// #767676 on #ffffff — the classic "exactly AA" gray: 4.54:1.
		$this->assertEqualsWithDelta(
			4.54,
			Contrast::contrast_ratio(
				Contrast::parse_color( '#767676' ),
				Contrast::parse_color( '#ffffff' )
			),
			0.01
		);
	}

	public function test_wcag_thresholds(): void {
		$this->assertTrue( Contrast::meets_wcag( 4.5 ) );
		$this->assertFalse( Contrast::meets_wcag( 4.49 ) );
		$this->assertTrue( Contrast::meets_wcag( 3.0, 'AA', true ) );
		$this->assertFalse( Contrast::meets_wcag( 2.99, 'AA', true ) );
		$this->assertTrue( Contrast::meets_wcag( 7.0, 'AAA' ) );
		$this->assertFalse( Contrast::meets_wcag( 6.99, 'AAA' ) );
	}

	public function test_picks_highest_contrast_passing_palette_color(): void {
		$palette = array(
			array(
				'slug'  => 'primary',
				'color' => '#1e3a8a',
			),
			array(
				'slug'  => 'base',
				'color' => '#ffffff',
			),
			array(
				'slug'  => 'contrast',
				'color' => '#111111',
			),
		);

		$result = Contrast::pick_accessible_foreground( '#ffffff', $palette );

		$this->assertTrue( $result['from_palette'] );
		$this->assertSame( 'contrast', $result['foreground']['slug'] );
		$this->assertGreaterThanOrEqual( 4.5, $result['ratio'] );
	}

	public function test_falls_back_to_black_or_white(): void {
		$on_white = Contrast::pick_accessible_foreground( '#ffffff', array() );
		$this->assertFalse( $on_white['from_palette'] );
		$this->assertSame( '#000000', $on_white['foreground']['color'] );

		$on_black = Contrast::pick_accessible_foreground( '#000000', array() );
		$this->assertSame( '#ffffff', $on_black['foreground']['color'] );
	}

	public function test_uses_active_theme_palette_by_default(): void {
		// Palette comes from the wp_get_global_settings() stub.
		$result = Contrast::pick_accessible_foreground( '#1e3a8a' );

		$this->assertTrue( $result['from_palette'] );
		$this->assertSame( 'base', $result['foreground']['slug'] );
	}

	public function test_theme_palette_wins_over_core_defaults(): void {
		$original = $GLOBALS['guardrail_blocks_test_palette'];

		$GLOBALS['guardrail_blocks_test_palette'] = array(
			'default' => array(
				array(
					'slug'  => 'black',
					'color' => '#000000',
				),
			),
			'theme'   => array(
				array(
					'slug'  => 'brand-dark',
					'color' => '#192a3d',
				),
				array(
					'slug'  => 'brand-light',
					'color' => '#f2f5f7',
				),
			),
		);

		// Core black (19:1) would beat brand-dark (12:1) — but defaults are
		// excluded when the theme defines a palette, mirroring the editor.
		$result = Contrast::pick_accessible_foreground( '#f2f5f7' );
		$this->assertSame( 'brand-dark', $result['foreground']['slug'] );

		$GLOBALS['guardrail_blocks_test_palette'] = $original;
	}

	public function test_color_for_slug_reads_live_palette(): void {
		$this->assertSame( '#1e3a8a', Contrast::color_for_slug( 'primary' ) );
		$this->assertNull( Contrast::color_for_slug( 'nonexistent' ) );
	}

	public function test_skips_unparseable_palette_entries(): void {
		$palette = array(
			array(
				'slug'  => 'weird',
				'color' => 'var(--brand)',
			),
			array(
				'slug'  => 'contrast',
				'color' => '#111111',
			),
		);

		$result = Contrast::pick_accessible_foreground( '#ffffff', $palette );
		$this->assertSame( 'contrast', $result['foreground']['slug'] );
	}

	public function test_returns_null_for_unparseable_background(): void {
		$this->assertNull( Contrast::pick_accessible_foreground( 'var(--bg)' ) );
	}
}
