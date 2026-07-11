/**
 * Accessible Heading — editor component.
 *
 * The author writes text; the level is consumed from the nearest Accessible
 * Section's context and rendered as the real hN element right in the editor.
 * There is deliberately no level control (Guarantee B, enforcement layer 1).
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { Notice, PanelBody } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';

import { clampHeadingLevel, HEADING_LEVEL_CONTEXT } from '../utils/outline';

export type HeadingAttributes = {
	content: string;
};

type HeadingEditProps = BlockEditProps< HeadingAttributes > & {
	context: Record< string, unknown >;
};

export default function Edit( {
	attributes,
	setAttributes,
	context,
}: HeadingEditProps ) {
	const contextLevel = context[ HEADING_LEVEL_CONTEXT ];
	const insideSection = typeof contextLevel === 'number';
	const level = clampHeadingLevel( insideSection ? contextLevel : 2 );
	const tagName = `h${ level }`;

	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Heading level', 'guardrail-blocks' ) }>
					<Notice status="success" isDismissible={ false }>
						{ sprintf(
							/* translators: %d: heading level number (2-6). */
							__(
								'Renders as H%d — derived from section nesting, so the document outline always stays valid.',
								'guardrail-blocks'
							),
							level
						) }
					</Notice>
					{ ! insideSection && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'This heading is not inside an Accessible Section, so it uses H2. Place it in a section to participate in nesting.',
								'guardrail-blocks'
							) }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<RichText
				{ ...blockProps }
				tagName={ tagName }
				value={ attributes.content }
				onChange={ ( content ) => setAttributes( { content } ) }
				placeholder={ sprintf(
					/* translators: %d: heading level number (2-6). */
					__( 'Heading (H%d)…', 'guardrail-blocks' ),
					level
				) }
				// Inline formats (bold/color/etc.) are disabled: a heading's
				// semantics and contrast shouldn't vary word-by-word.
				allowedFormats={ [] }
			/>
		</>
	);
}
