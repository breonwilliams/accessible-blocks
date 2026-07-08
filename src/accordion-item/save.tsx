/**
 * Accordion Item — save.
 *
 * Only the inner blocks are serialized; all structure and ARIA wiring is
 * produced by render.php on every request.
 */
import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}
