/**
 * Accessible Section — editor component.
 *
 * The heart of Guarantee B's enforcement layer 1: the section's provided
 * heading level is *derived* from its position (parent section's level + 1,
 * top level = 2) and written back to the attribute whenever structure
 * changes. There is no control to set it — reordering and re-nesting simply
 * recompute it, so a broken outline is not an authorable state.
 */
import { useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { Notice, PanelBody } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';

import { deriveSectionLevel, HEADING_LEVEL_CONTEXT } from '../utils/outline';

export type SectionAttributes = {
	headingLevel: number;
};

type SectionEditProps = BlockEditProps< SectionAttributes > & {
	context: Record< string, unknown >;
};

const TEMPLATE: Array< [ string, Record< string, unknown >? ] > = [
	[ 'accessible-blocks/heading' ],
	[ 'core/paragraph' ],
];

export default function Edit( {
	attributes,
	setAttributes,
	context,
}: SectionEditProps ) {
	const parentLevel = context[ HEADING_LEVEL_CONTEXT ];
	const derived = deriveSectionLevel(
		typeof parentLevel === 'number' ? parentLevel : null
	);

	// Self-healing: whenever nesting changes (drag, indent, paste), the
	// derived value changes and the stored attribute is re-synced. The
	// attribute only exists because providesContext must read from one.
	useEffect( () => {
		if ( attributes.headingLevel !== derived ) {
			setAttributes( { headingLevel: derived } );
		}
	}, [ derived, attributes.headingLevel, setAttributes ] );

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Heading structure', 'accessible-blocks' ) }
				>
					<Notice status="info" isDismissible={ false }>
						{ sprintf(
							/* translators: %d: heading level number (2-6). */
							__(
								'Headings inside this section render as H%d. The level comes from section nesting and always stays in order — no manual setting needed.',
								'accessible-blocks'
							),
							derived
						) }
					</Notice>
				</PanelBody>
			</InspectorControls>
			<section { ...innerBlocksProps } />
		</>
	);
}
