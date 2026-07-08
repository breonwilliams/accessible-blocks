/**
 * Accessible Accordion — registration.
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.scss';

registerBlockType(
	metadata as unknown as BlockConfiguration< Record< string, unknown > >,
	{
		edit: Edit,
		save,
	}
);
