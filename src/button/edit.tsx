/**
 * Accessible Button — editor component.
 *
 * Enforcement layer 1 (constrained input): the author picks only a
 * background, and only from the theme palette. The foreground is derived by
 * the contrast engine — an inaccessible pairing is not a state this UI can
 * produce. The Inspector surfaces the resulting ratio as a read-only status
 * (layer 2), and render.php re-derives everything server-side (layer 3).
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	ColorPalette,
	InspectorControls,
	RichText,
	URLInput,
	useBlockProps,
	useSettings,
} from '@wordpress/block-editor';
import { Notice, PanelBody, ToggleControl } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';

import {
	parseColor,
	pickAccessibleForeground,
	toHex,
	type PaletteColor,
} from '../utils/contrast';

/**
 * Canonical form for comparing CSS colors: parsed hex when possible
 * (ColorPalette may normalize case/format of the value it returns),
 * else the trimmed original.
 *
 * @param color CSS color string.
 */
function canonicalColor( color?: string ): string {
	if ( ! color ) {
		return '';
	}
	const parsed = parseColor( color );
	return parsed ? toHex( parsed ) : color.trim().toLowerCase();
}

// A type alias (not an interface) so it structurally satisfies the
// Record< string, unknown > constraint on BlockEditProps.
export type ButtonAttributes = {
	text: string;
	url: string;
	backgroundSlug: string;
	width: 'auto' | 'full';
};

/**
 * Normalize the (possibly origin-grouped) palette setting into a flat list.
 */
function usePalette(): PaletteColor[] {
	const [ paletteSetting ] = useSettings( 'color.palette' ) as [
		| PaletteColor[]
		| {
				theme?: PaletteColor[];
				custom?: PaletteColor[];
				default?: PaletteColor[];
		  }
		| undefined,
	];

	if ( Array.isArray( paletteSetting ) ) {
		return paletteSetting;
	}

	if ( paletteSetting && typeof paletteSetting === 'object' ) {
		const seen = new Set< string >();
		const merged: PaletteColor[] = [];
		for ( const origin of [ 'theme', 'custom', 'default' ] as const ) {
			for ( const entry of paletteSetting[ origin ] ?? [] ) {
				if ( ! seen.has( entry.slug ) ) {
					seen.add( entry.slug );
					merged.push( entry );
				}
			}
		}
		return merged;
	}

	return [];
}

export default function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< ButtonAttributes > ) {
	const { text, url, backgroundSlug, width } = attributes;
	const palette = usePalette();

	// Only offer colors the contrast engine can verify (constrain, don't
	// configure). var(--x, #hex) fallbacks parse fine; a color with no
	// recoverable value is not offered rather than silently unchecked.
	const verifiablePalette = palette.filter(
		( c ) => parseColor( c.color ) !== null
	);
	const hiddenCount = palette.length - verifiablePalette.length;

	const background =
		palette.find( ( c ) => c.slug === backgroundSlug ) ?? null;
	const pairing = background
		? pickAccessibleForeground( background.color, palette )
		: null;
	const unverifiableSelection = Boolean( background ) && ! pairing;

	const blockProps = useBlockProps( {
		className: width === 'full' ? 'ab-button--full' : undefined,
	} );

	// Only preview colors the engine verified — an unverifiable selection
	// must not render a misleading (possibly failing) pairing.
	const buttonStyle =
		background && pairing
			? {
					backgroundColor: background.color,
					color: pairing.foreground.color,
			  }
			: undefined;

	const contrastStatus = pairing
		? sprintf(
				/* translators: 1: contrast ratio, 2: color name or slug. */
				__(
					'Contrast %1$s:1 — AA ✓. Text color: %2$s (auto-selected).',
					'accessible-blocks'
				),
				pairing.ratio.toFixed( 2 ),
				pairing.foreground.name ?? pairing.foreground.slug
		  )
		: null;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Background color', 'accessible-blocks' ) }
				>
					<ColorPalette
						colors={ verifiablePalette }
						value={ background?.color }
						onChange={ ( newColor ) => {
							const target = canonicalColor( newColor );
							const match = palette.find(
								( c ) => canonicalColor( c.color ) === target
							);
							setAttributes( {
								backgroundSlug: match?.slug ?? '',
							} );
						} }
						disableCustomColors
						clearable
					/>
					{ contrastStatus && (
						<Notice status="success" isDismissible={ false }>
							{ contrastStatus }
						</Notice>
					) }
					{ pairing && ! pairing.fromPalette && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'No theme palette color passes WCAG AA on this background, so black/white was used instead.',
								'accessible-blocks'
							) }
						</Notice>
					) }
					{ unverifiableSelection && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'The saved color can’t be contrast-checked with this theme, so it isn’t applied — the button uses the theme’s default button styling. Pick a color above to re-enable enforcement.',
								'accessible-blocks'
							) }
						</Notice>
					) }
					{ hiddenCount > 0 && (
						<Notice status="info" isDismissible={ false }>
							{ sprintf(
								/* translators: %d: number of hidden colors. */
								__(
									'%d theme color(s) aren’t offered because their values can’t be contrast-checked.',
									'accessible-blocks'
								),
								hiddenCount
							) }
						</Notice>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Link', 'accessible-blocks' ) }>
					<URLInput
						label={ __( 'Link URL', 'accessible-blocks' ) }
						value={ url }
						onChange={ ( newUrl ) =>
							setAttributes( { url: newUrl } )
						}
					/>
				</PanelBody>
				<PanelBody title={ __( 'Layout', 'accessible-blocks' ) }>
					<ToggleControl
						label={ __( 'Full width', 'accessible-blocks' ) }
						checked={ width === 'full' }
						onChange={ ( isFull ) =>
							setAttributes( {
								width: isFull ? 'full' : 'auto',
							} )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<RichText
					tagName="span"
					className="ab-button wp-element-button"
					style={ buttonStyle }
					value={ text }
					onChange={ ( newText ) =>
						setAttributes( { text: newText } )
					}
					placeholder={ __( 'Add text…', 'accessible-blocks' ) }
					// Inline color/formatting could break the contrast
					// guarantee, so no formats are allowed (constrain,
					// don't configure).
					allowedFormats={ [] }
				/>
			</div>
		</>
	);
}
