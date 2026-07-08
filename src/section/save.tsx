/**
 * Accessible Section — save.
 *
 * Static output: a plain <section> landmark wrapping the inner blocks.
 * All heading-level intelligence lives in the provided context (from the
 * headingLevel attribute) and in the dynamic Accessible Heading render.
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function save() {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );

	return <section { ...innerBlocksProps } />;
}
