/**
 * Accessible Accordion — save.
 *
 * Static wrapper carrying the Interactivity API region; each item brings
 * its own directives from its server render.
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function save() {
	const blockProps = useBlockProps.save( {
		'data-wp-interactive': 'guardrail-blocks/accordion',
	} );
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );

	return <div { ...innerBlocksProps } />;
}
