/**
 * Accordion Item — registration.
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';

import metadata from './block.json';
import Edit, { type AccordionItemAttributes } from './edit';
import save from './save';

registerBlockType< AccordionItemAttributes >(
	metadata as unknown as BlockConfiguration< AccordionItemAttributes >,
	{
		edit: Edit,
		save,
	}
);
