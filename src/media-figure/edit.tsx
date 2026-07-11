/**
 * Media Figure — editor component.
 *
 * Enforcement layer 1+2 for Guarantee C: the author picks an image and
 * must give it alt text (or rely on the media library's). An empty alt
 * shows a persistent warning; the hero toggle is the only performance
 * decision the author makes — everything else is automatic.
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	MediaPlaceholder,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import {
	Button,
	Notice,
	PanelBody,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';

export type MediaFigureAttributes = {
	mediaId: number;
	alt: string;
	caption: string;
	isHero: boolean;
};

type MediaRecord = {
	source_url?: string;
	alt_text?: string;
	media_details?: {
		sizes?: Record< string, { source_url?: string } | undefined >;
	};
};

export default function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< MediaFigureAttributes > ) {
	const { mediaId, alt, caption, isHero } = attributes;
	const blockProps = useBlockProps();

	const media = useSelect(
		(
			select: ( store: string ) => {
				getMedia: ( id: number ) => MediaRecord | undefined;
			}
		) => ( mediaId > 0 ? select( 'core' ).getMedia( mediaId ) : undefined ),
		[ mediaId ]
	);

	const previewUrl =
		media?.media_details?.sizes?.large?.source_url ??
		media?.source_url ??
		'';
	const libraryAlt = media?.alt_text?.trim() ?? '';
	const effectiveAltMissing =
		mediaId > 0 && '' === alt.trim() && '' === libraryAlt;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Accessibility', 'guardrail-blocks' ) }>
					<TextareaControl
						label={ __( 'Alternative text', 'guardrail-blocks' ) }
						help={ __(
							'Describe the image for people who can’t see it. Falls back to the media library’s alt text.',
							'guardrail-blocks'
						) }
						value={ alt }
						onChange={ ( value ) =>
							setAttributes( { alt: value } )
						}
						__nextHasNoMarginBottom
					/>
					{ effectiveAltMissing && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'This image has no alt text anywhere — add it above or in the Media Library. Screen-reader users currently get nothing.',
								'guardrail-blocks'
							) }
						</Notice>
					) }
					{ mediaId > 0 && '' === alt.trim() && '' !== libraryAlt && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Using the Media Library’s alt text.',
								'guardrail-blocks'
							) }
						</Notice>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Performance', 'guardrail-blocks' ) }>
					<ToggleControl
						label={ __(
							'Hero image (above the fold)',
							'guardrail-blocks'
						) }
						help={ __(
							'Loads eagerly with high priority for better LCP. Leave off for images further down the page (they lazy-load).',
							'guardrail-blocks'
						) }
						checked={ isHero }
						onChange={ ( value ) =>
							setAttributes( { isHero: value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<figure { ...blockProps }>
				{ mediaId > 0 && previewUrl ? (
					<>
						<img src={ previewUrl } alt={ alt || libraryAlt } />
						<RichText
							tagName="figcaption"
							className="ab-media-figure__caption"
							value={ caption }
							onChange={ ( value ) =>
								setAttributes( { caption: value } )
							}
							placeholder={ __(
								'Add a caption… (optional)',
								'guardrail-blocks'
							) }
							allowedFormats={ [ 'core/italic', 'core/link' ] }
						/>
						<Button
							className="ab-media-figure__replace"
							variant="secondary"
							onClick={ () => setAttributes( { mediaId: 0 } ) }
						>
							{ __( 'Replace image', 'guardrail-blocks' ) }
						</Button>
					</>
				) : (
					<MediaPlaceholder
						accept="image/*"
						allowedTypes={ [ 'image' ] }
						onSelect={ ( selected: { id: number; alt?: string } ) =>
							setAttributes( {
								mediaId: selected.id,
								alt: alt || selected.alt || '',
							} )
						}
						labels={ {
							title: __( 'Media Figure', 'guardrail-blocks' ),
							instructions: __(
								'Pick an image. Responsive markup, dimensions, and loading strategy are handled for you.',
								'guardrail-blocks'
							),
						} }
					/>
				) }
			</figure>
		</>
	);
}
