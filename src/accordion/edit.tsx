/**
 * Accessible Accordion — editor component.
 *
 * Pure container: only Accordion Items are allowed inside, and the ARIA
 * machinery lives in the item's server render. The accordion derives and
 * provides a heading level one deeper than its context, so item headers
 * nest correctly under the surrounding section's heading — self-healing
 * on reorder, exactly like Section.
 */
import { useEffect } from '@wordpress/element';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';

import { deriveSectionLevel, HEADING_LEVEL_CONTEXT } from '../utils/outline';

export type AccordionAttributes = {
	headingLevel: number;
};

type AccordionEditProps = BlockEditProps< AccordionAttributes > & {
	context: Record< string, unknown >;
};

const TEMPLATE: Array< [ string, Record< string, unknown >? ] > = [
	[ 'accessible-blocks/accordion-item' ],
	[ 'accessible-blocks/accordion-item' ],
];

export default function Edit( {
	attributes,
	setAttributes,
	context,
}: AccordionEditProps ) {
	const parentLevel = context[ HEADING_LEVEL_CONTEXT ];
	const derived = deriveSectionLevel(
		typeof parentLevel === 'number' ? parentLevel : null
	);

	useEffect( () => {
		if ( attributes.headingLevel !== derived ) {
			setAttributes( { headingLevel: derived } );
		}
	}, [ derived, attributes.headingLevel, setAttributes ] );

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: [ 'accessible-blocks/accordion-item' ],
		template: TEMPLATE,
	} );

	return <div { ...innerBlocksProps } />;
}
