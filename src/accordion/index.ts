/**
 * Accessible Accordion — registration.
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';

import metadata from './block.json';
import Edit, { type AccordionAttributes } from './edit';
import save from './save';
import './style.scss';

registerBlockType< AccordionAttributes >(
	metadata as unknown as BlockConfiguration< AccordionAttributes >,
	{
		edit: Edit,
		save,
	}
);
