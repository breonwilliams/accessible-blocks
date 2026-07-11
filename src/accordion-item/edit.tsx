/**
 * Accordion Item — editor component.
 *
 * The editor shows the item permanently expanded (authors need to edit the
 * panel), with the same visual structure the front end renders. The header
 * heading level derives from section context, exactly like render.php.
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { Notice, PanelBody } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';

import { clampHeadingLevel, HEADING_LEVEL_CONTEXT } from '../utils/outline';

export type AccordionItemAttributes = {
	title: string;
};

type AccordionItemEditProps = BlockEditProps< AccordionItemAttributes > & {
	context: Record< string, unknown >;
};

const TEMPLATE: Array< [ string, Record< string, unknown >? ] > = [
	[ 'core/paragraph' ],
];

export default function Edit( {
	attributes,
	setAttributes,
	context,
}: AccordionItemEditProps ) {
	const level = clampHeadingLevel( context[ HEADING_LEVEL_CONTEXT ] ?? 2 );
	const HeadingTag = `h${ level }` as 'h2' | 'h3' | 'h4' | 'h5' | 'h6';

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'ab-accordion-item__panel' },
		{ template: TEMPLATE }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Accessibility', 'guardrail-blocks' ) }>
					<Notice status="success" isDismissible={ false }>
						{ sprintf(
							/* translators: %d: heading level number (2-6). */
							__(
								'Renders as an H%d header button with ARIA disclosure semantics and keyboard support — nothing to configure.',
								'guardrail-blocks'
							),
							level
						) }
					</Notice>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<HeadingTag className="ab-accordion-item__heading">
					<div
						className="ab-accordion-item__trigger"
						aria-expanded="true"
					>
						<RichText
							tagName="span"
							className="ab-accordion-item__title"
							value={ attributes.title }
							onChange={ ( title ) => setAttributes( { title } ) }
							placeholder={ __(
								'Accordion title…',
								'guardrail-blocks'
							) }
							allowedFormats={ [] }
						/>
						<span
							className="ab-accordion-item__icon"
							aria-hidden="true"
						/>
					</div>
				</HeadingTag>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
