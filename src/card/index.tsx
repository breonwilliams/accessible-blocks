/**
 * Card — registration + components.
 *
 * A card is a semantic subsection: its title (an Accessible Heading) sits
 * one level deeper than the surrounding section's heading. The card
 * derives and provides that level exactly like Section does, with the
 * same self-healing on reorder.
 */
import { useEffect } from '@wordpress/element';
import {
	registerBlockType,
	type BlockConfiguration,
	BlockEditProps,
} from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import { deriveSectionLevel, HEADING_LEVEL_CONTEXT } from '../utils/outline';
import metadata from './block.json';
import './style.scss';

type CardAttributes = {
	headingLevel: number;
};

type CardEditProps = BlockEditProps< CardAttributes > & {
	context: Record< string, unknown >;
};

const TEMPLATE: Array< [ string, Record< string, unknown >? ] > = [
	[ 'accessible-blocks/heading' ],
	[ 'core/paragraph' ],
];

function Edit( { attributes, setAttributes, context }: CardEditProps ) {
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
		template: TEMPLATE,
	} );

	return <article { ...innerBlocksProps } />;
}

function save() {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <article { ...innerBlocksProps } />;
}

registerBlockType< CardAttributes >(
	metadata as unknown as BlockConfiguration< CardAttributes >,
	{
		edit: Edit,
		save,
	}
);
