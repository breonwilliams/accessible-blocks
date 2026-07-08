/**
 * Accessible Accordion — editor component.
 *
 * Pure container: only Accordion Items are allowed inside, and the ARIA
 * machinery lives in the item's server render. Nothing to configure —
 * correct semantics aren't optional.
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

const TEMPLATE: Array< [ string, Record< string, unknown >? ] > = [
	[ 'accessible-blocks/accordion-item' ],
	[ 'accessible-blocks/accordion-item' ],
];

export default function Edit() {
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: [ 'accessible-blocks/accordion-item' ],
		template: TEMPLATE,
	} );

	return <div { ...innerBlocksProps } />;
}
