/**
 * Accessible Heading — registration.
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';

import metadata from './block.json';
import Edit, { type HeadingAttributes } from './edit';

registerBlockType< HeadingAttributes >(
	metadata as unknown as BlockConfiguration< HeadingAttributes >,
	{
		edit: Edit,
		// Dynamic block: the hN tag is chosen server-side from context on
		// every request (render.php).
		save: () => null,
	}
);
