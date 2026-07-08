/**
 * Accessible Section — registration.
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';

import metadata from './block.json';
import Edit, { type SectionAttributes } from './edit';
import save from './save';

registerBlockType< SectionAttributes >(
	metadata as unknown as BlockConfiguration< SectionAttributes >,
	{
		edit: Edit,
		save,
	}
);
