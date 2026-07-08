<?php
/**
 * WCAG 2.1 contrast engine — PHP mirror for render-time enforcement.
 *
 * This is layer 3 of the enforcement architecture (Foundation §9): dynamic
 * blocks re-validate color pairings against the *current* theme palette on
 * every render, so a theme change after publish can never ship a failing
 * pairing. Must stay in lockstep with `src/utils/contrast.ts` — same
 * formulas, same thresholds, same fallback strategy.
 *
 * @package AccessibleBlocks
 */

declare( strict_types=1 );

namespace AccessibleBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static WCAG 2.1 color math + palette-aware pairing selection.
 */
class Contrast {

	public const AA_NORMAL  = 4.5;
	public const AA_LARGE   = 3.0;
	public const AAA_NORMAL = 7.0;
	public const AAA_LARGE  = 4.5;

	/**
	 * Parse a CSS color into [r, g, b] or null when unverifiable.
	 *
	 * Supports #rgb/#rgba/#rrggbb/#rrggbbaa and rgb()/rgba(). Alpha is
	 * ignored (WCAG contrast is defined for opaque colors). Anything else
	 * (hsl(), var(), named colors) returns null so callers treat it as
	 * "cannot verify" instead of guessing.
	 *
	 * @param string $input CSS color string.
	 * @return array{r: int, g: int, b: int}|null
	 */
	public static function parse_color( string $input ): ?array {
		$value = strtolower( trim( $input ) );

		if ( preg_match( '/^#([0-9a-f]{3,8})$/', $value, $m ) ) {
			$hex = $m[1];
			$len = strlen( $hex );

			if ( 3 === $len || 4 === $len ) {
				return array(
					'r' => (int) hexdec( $hex[0] . $hex[0] ),
					'g' => (int) hexdec( $hex[1] . $hex[1] ),
					'b' => (int) hexdec( $hex[2] . $hex[2] ),
				);
			}

			if ( 6 === $len || 8 === $len ) {
				return array(
					'r' => (int) hexdec( substr( $hex, 0, 2 ) ),
					'g' => (int) hexdec( substr( $hex, 2, 2 ) ),
					'b' => (int) hexdec( substr( $hex, 4, 2 ) ),
				);
			}

			return null;
		}

		if ( preg_match( '/^rgba?\(\s*(\d{1,3})\s*[, ]\s*(\d{1,3})\s*[, ]\s*(\d{1,3})\s*(?:[,\/][^)]*)?\)$/', $value, $m ) ) {
			$r = (int) $m[1];
			$g = (int) $m[2];
			$b = (int) $m[3];

			if ( $r > 255 || $g > 255 || $b > 255 ) {
				return null;
			}

			return array(
				'r' => $r,
				'g' => $g,
				'b' => $b,
			);
		}

		return null;
	}

	/**
	 * Relative luminance per WCAG 2.1.
	 *
	 * @param array{r: int, g: int, b: int} $rgb Color.
	 */
	public static function relative_luminance( array $rgb ): float {
		$channels = array();

		foreach ( array( 'r', 'g', 'b' ) as $key ) {
			$c          = $rgb[ $key ] / 255;
			$channels[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		}

		return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
	}

	/**
	 * Contrast ratio between two colors, 1–21.
	 *
	 * @param array{r: int, g: int, b: int} $a First color.
	 * @param array{r: int, g: int, b: int} $b Second color.
	 */
	public static function contrast_ratio( array $a, array $b ): float {
		$la = self::relative_luminance( $a );
		$lb = self::relative_luminance( $b );

		$lighter = max( $la, $lb );
		$darker  = min( $la, $lb );

		return ( $lighter + 0.05 ) / ( $darker + 0.05 );
	}

	/**
	 * Does a ratio meet a WCAG level?
	 *
	 * @param float  $ratio      Contrast ratio.
	 * @param string $level      'AA' or 'AAA'.
	 * @param bool   $large_text Large-scale text per WCAG.
	 */
	public static function meets_wcag( float $ratio, string $level = 'AA', bool $large_text = false ): bool {
		if ( 'AAA' === $level ) {
			$threshold = $large_text ? self::AAA_LARGE : self::AAA_NORMAL;
		} else {
			$threshold = $large_text ? self::AA_LARGE : self::AA_NORMAL;
		}

		return $ratio >= $threshold;
	}

	/**
	 * The active theme palette as a flat list of slug/color/name entries.
	 *
	 * Merges theme, custom, and default origins with theme taking priority
	 * (matches how the editor presents the palette).
	 *
	 * @return array<int, array{slug: string, color: string, name?: string}>
	 */
	public static function get_palette(): array {
		$settings = wp_get_global_settings( array( 'color', 'palette' ) );

		if ( ! is_array( $settings ) ) {
			return array();
		}

		$merged = array();

		foreach ( array( 'theme', 'custom', 'default' ) as $origin ) {
			if ( empty( $settings[ $origin ] ) || ! is_array( $settings[ $origin ] ) ) {
				continue;
			}

			foreach ( $settings[ $origin ] as $entry ) {
				if ( empty( $entry['slug'] ) || empty( $entry['color'] ) ) {
					continue;
				}

				// First origin wins per slug.
				if ( ! isset( $merged[ $entry['slug'] ] ) ) {
					$merged[ $entry['slug'] ] = array(
						'slug'  => (string) $entry['slug'],
						'color' => (string) $entry['color'],
						'name'  => isset( $entry['name'] ) ? (string) $entry['name'] : (string) $entry['slug'],
					);
				}
			}
		}

		return array_values( $merged );
	}

	/**
	 * Resolve a palette slug to its current CSS color, or null.
	 *
	 * @param string $slug Palette slug.
	 */
	public static function color_for_slug( string $slug ): ?string {
		foreach ( self::get_palette() as $entry ) {
			if ( $entry['slug'] === $slug ) {
				return $entry['color'];
			}
		}

		return null;
	}

	/**
	 * Pick the best accessible foreground for a background.
	 *
	 * Same strategy as pickAccessibleForeground() in contrast.ts:
	 * highest-contrast palette color meeting the minimum ratio, else the
	 * better of black/white.
	 *
	 * @param string     $background CSS color of the background.
	 * @param array|null $palette    Palette entries; null = active theme palette.
	 * @param float      $min_ratio  Minimum acceptable ratio.
	 * @return array{foreground: array{slug: string, color: string}, ratio: float, from_palette: bool}|null
	 */
	public static function pick_accessible_foreground( string $background, ?array $palette = null, float $min_ratio = self::AA_NORMAL ): ?array {
		$bg = self::parse_color( $background );

		if ( null === $bg ) {
			return null;
		}

		if ( null === $palette ) {
			$palette = self::get_palette();
		}

		$best = null;

		foreach ( $palette as $candidate ) {
			if ( empty( $candidate['color'] ) ) {
				continue;
			}

			$fg = self::parse_color( (string) $candidate['color'] );

			if ( null === $fg ) {
				continue;
			}

			$ratio = self::contrast_ratio( $fg, $bg );

			if ( $ratio >= $min_ratio && ( null === $best || $ratio > $best['ratio'] ) ) {
				$best = array(
					'foreground'   => array(
						'slug'  => (string) ( $candidate['slug'] ?? '' ),
						'color' => (string) $candidate['color'],
					),
					'ratio'        => $ratio,
					'from_palette' => true,
				);
			}
		}

		if ( null !== $best ) {
			return $best;
		}

		$black_ratio = self::contrast_ratio(
			array(
				'r' => 0,
				'g' => 0,
				'b' => 0,
			),
			$bg
		);
		$white_ratio = self::contrast_ratio(
			array(
				'r' => 255,
				'g' => 255,
				'b' => 255,
			),
			$bg
		);

		if ( $black_ratio >= $white_ratio ) {
			return array(
				'foreground'   => array(
					'slug'  => 'black',
					'color' => '#000000',
				),
				'ratio'        => $black_ratio,
				'from_palette' => false,
			);
		}

		return array(
			'foreground'   => array(
				'slug'  => 'white',
				'color' => '#ffffff',
			),
			'ratio'        => $white_ratio,
			'from_palette' => false,
		);
	}
}
