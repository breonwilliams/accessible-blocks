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

import { pickAccessibleForeground, type PaletteColor } from '../utils/contrast';

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

	const background =
		palette.find( ( c ) => c.slug === backgroundSlug ) ?? null;
	const pairing = background
		? pickAccessibleForeground( background.color, palette )
		: null;

	const blockProps = useBlockProps( {
		className: width === 'full' ? 'ab-button--full' : undefined,
	} );

	const buttonStyle = background
		? {
				backgroundColor: background.color,
				color: pairing?.foreground.color,
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
						colors={ palette }
						value={ background?.color }
						onChange={ ( newColor ) => {
							const match = palette.find(
								( c ) => c.color === newColor
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
