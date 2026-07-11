/**
 * Table of Contents — editor component.
 *
 * Live preview computed from the actual editor block tree with the same
 * outline engine the server uses. Nothing to configure; the list *is* the
 * document structure. (Preview indents visually; the server render emits
 * real nested lists.)
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { Notice } from '@wordpress/components';

import { collectOutline, type BlockNode } from '../utils/outline';

export default function Edit() {
	const outline = useSelect(
		( select: ( store: string ) => { getBlocks: () => BlockNode[] } ) =>
			collectOutline( select( 'core/block-editor' ).getBlocks() ),
		[]
	);

	const blockProps = useBlockProps();
	const baseLevel = Math.min( ...outline.map( ( e ) => e.level ), 6 );

	return (
		<nav
			{ ...blockProps }
			aria-label={ __( 'Table of contents', 'guardrail-blocks' ) }
		>
			{ outline.length === 0 ? (
				<Notice status="info" isDismissible={ false }>
					{ __(
						'Add headings to the page and they will appear here automatically.',
						'guardrail-blocks'
					) }
				</Notice>
			) : (
				<ol className="ab-toc__list">
					{ outline.map( ( entry ) => (
						<li
							key={ entry.clientId }
							className="ab-toc__item"
							style={ {
								paddingInlineStart: `${
									( entry.level - baseLevel ) * 1.25
								}em`,
							} }
						>
							{ entry.text ||
								__( '(empty heading)', 'guardrail-blocks' ) }
						</li>
					) ) }
				</ol>
			) }
		</nav>
	);
}
