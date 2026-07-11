/**
 * Outline Checker — document sidebar panel (enforcement layer 2).
 *
 * Watches the *whole* document, including core Heading blocks that our
 * derived system can't constrain, and flags skipped levels and extra H1s.
 * Clicking an issue selects the offending block.
 *
 * Registered from the Accessible Heading editor script (all registered
 * blocks' editor scripts load in the editor, so the panel is always
 * available; a dedicated webpack entry can replace this if the plugin
 * grows more editor-wide UI).
 */
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { Button, Notice } from '@wordpress/components';

import {
	collectOutline,
	findOutlineIssues,
	type BlockNode,
	type OutlineIssue,
} from '../utils/outline';

/**
 * Human-readable message for an outline issue.
 *
 * @param issue Issue from findOutlineIssues().
 */
function issueMessage( issue: OutlineIssue ): string {
	if ( issue.type === 'multiple-h1' ) {
		return __(
			'Extra H1 — the page title is already the H1.',
			'guardrail-blocks'
		);
	}
	return sprintf(
		/* translators: 1: heading level found, 2: deepest valid level. */
		__(
			'H%1$d skips levels — H%2$d is the deepest valid here.',
			'guardrail-blocks'
		),
		issue.level,
		issue.expectedMax
	);
}

function OutlineCheckerPanel() {
	const outline = useSelect(
		( select: ( store: string ) => { getBlocks: () => BlockNode[] } ) =>
			collectOutline( select( 'core/block-editor' ).getBlocks() ),
		[]
	);
	const { selectBlock } = useDispatch( 'core/block-editor' ) as {
		selectBlock: ( clientId: string ) => void;
	};

	const issues = findOutlineIssues( outline );
	const issuesByClientId = new Map(
		issues.map( ( issue ) => [ issue.clientId, issue ] )
	);

	return (
		<PluginDocumentSettingPanel
			name="guardrail-blocks-outline-checker"
			title={ __( 'Heading outline', 'guardrail-blocks' ) }
			className="ab-outline-checker"
		>
			{ outline.length === 0 && (
				<p>
					{ __(
						'No headings yet. The page title is the H1.',
						'guardrail-blocks'
					) }
				</p>
			) }
			{ outline.length > 0 && issues.length === 0 && (
				<Notice status="success" isDismissible={ false }>
					{ __(
						'Outline is valid — no skipped levels, no extra H1s.',
						'guardrail-blocks'
					) }
				</Notice>
			) }
			<ol className="ab-outline-checker__list">
				{ outline.map( ( entry ) => {
					const issue = issuesByClientId.get( entry.clientId );
					return (
						<li
							key={ entry.clientId }
							style={ {
								paddingInlineStart: `${
									( entry.level - 2 ) * 0.75
								}em`,
								listStyle: 'none',
							} }
						>
							<Button
								variant="link"
								onClick={ () => selectBlock( entry.clientId ) }
								aria-label={ sprintf(
									/* translators: 1: heading level, 2: heading text. */
									__(
										'Select heading level %1$d: %2$s',
										'guardrail-blocks'
									),
									entry.level,
									entry.text
								) }
							>
								{ `H${ entry.level } ` }
								{ entry.text ||
									__(
										'(empty heading)',
										'guardrail-blocks'
									) }
								{ entry.source === 'manual'
									? ' ' + __( '(core)', 'guardrail-blocks' )
									: '' }
							</Button>
							{ issue && (
								<Notice
									status="warning"
									isDismissible={ false }
								>
									{ issueMessage( issue ) }
								</Notice>
							) }
						</li>
					);
				} ) }
			</ol>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'guardrail-blocks-outline-checker', {
	render: OutlineCheckerPanel,
} );
