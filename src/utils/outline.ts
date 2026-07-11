/**
 * Heading-outline engine — the shared core of Guarantee B.
 *
 * Pure functions over a block tree. The Section block derives its provided
 * heading level with deriveSectionLevel(); the Accessible Heading consumes
 * that context; the Outline Checker (Phase 4) walks the whole document —
 * including core Heading blocks — with collectOutline() + findOutlineIssues().
 *
 * Everything here is intentionally free of @wordpress imports so it can be
 * unit-tested without an editor.
 */

/** The context key Sections provide and Headings consume. */
export const HEADING_LEVEL_CONTEXT = 'guardrail-blocks/headingLevel';

export const SECTION_BLOCK = 'guardrail-blocks/section';
export const HEADING_BLOCK = 'guardrail-blocks/heading';
export const CORE_HEADING_BLOCK = 'core/heading';
export const ACCORDION_ITEM_BLOCK = 'guardrail-blocks/accordion-item';

/**
 * Blocks that provide a heading level one deeper than their context:
 * Sections (obviously), plus Cards and Accordions — their titles are
 * semantically subsections of the surrounding section's heading.
 */
export const LEVEL_PROVIDER_BLOCKS = [
	SECTION_BLOCK,
	'guardrail-blocks/card',
	'guardrail-blocks/accordion',
];

/**
 * The document's H1 is the post/page title, so derived heading content
 * starts at 2 and can never go deeper than 6.
 */
export const MIN_HEADING_LEVEL = 2;
export const MAX_HEADING_LEVEL = 6;

/**
 * Minimal shape of an editor block for tree walks (structural subset of
 * what select( 'core/block-editor' ).getBlocks() returns).
 */
export interface BlockNode {
	clientId: string;
	name: string;
	attributes: Record< string, unknown >;
	innerBlocks: BlockNode[];
}

export interface OutlineEntry {
	clientId: string;
	/** Resolved heading level, 1–6. */
	level: number;
	/** Plain-text content (may be empty while the author is typing). */
	text: string;
	/** Which system produced the level. */
	source: 'derived' | 'manual';
}

export type OutlineIssue =
	| {
			type: 'skipped-level';
			clientId: string;
			/** The level that appeared. */
			level: number;
			/** The deepest level that would have been valid. */
			expectedMax: number;
	  }
	| {
			type: 'multiple-h1';
			clientId: string;
			level: 1;
	  };

/**
 * Clamp any value into the valid derived-heading range (H2–H6).
 *
 * @param level Candidate level (context value, attribute, anything).
 */
export function clampHeadingLevel( level: unknown ): number {
	const n = Math.trunc( Number( level ) );
	if ( Number.isNaN( n ) ) {
		return MIN_HEADING_LEVEL;
	}
	return Math.min( Math.max( n, MIN_HEADING_LEVEL ), MAX_HEADING_LEVEL );
}

/**
 * The heading level a Section provides to its children.
 *
 * A top-level Section (no ancestor Section → no context) provides 2, one
 * step below the page title's H1. A nested Section provides its parent's
 * level + 1, capped at 6 so arbitrarily deep nesting can never overflow
 * or skip.
 *
 * @param parentLevel The level provided by the nearest ancestor Section,
 *                    or null/undefined at the top level.
 */
export function deriveSectionLevel( parentLevel?: number | null ): number {
	if ( parentLevel === null || parentLevel === undefined ) {
		return MIN_HEADING_LEVEL;
	}
	return clampHeadingLevel( parentLevel + 1 );
}

/**
 * Strip tags/entities from RichText-ish content for display.
 *
 * @param value Raw attribute value.
 */
function toPlainText( value: unknown ): string {
	return String( value ?? '' )
		.replace( /<[^>]*>/g, '' )
		.trim();
}

/**
 * Walk a block tree and return every heading — ours and core's — in
 * document order with its resolved level.
 *
 * Our headings resolve exactly like the runtime: nearest ancestor Section's
 * derived level, else the H2 default. Core headings report whatever level
 * the author hand-picked (that's why they're the Outline Checker's problem
 * children).
 *
 * @param blocks       Top-level block list.
 * @param contextLevel Heading level provided by an ancestor Section (used
 *                     during recursion; omit for a whole document).
 */
export function collectOutline(
	blocks: BlockNode[],
	contextLevel: number | null = null
): OutlineEntry[] {
	const entries: OutlineEntry[] = [];

	for ( const block of blocks ) {
		if ( LEVEL_PROVIDER_BLOCKS.includes( block.name ) ) {
			const provided = deriveSectionLevel( contextLevel );
			entries.push( ...collectOutline( block.innerBlocks, provided ) );
			continue;
		}

		if ( block.name === ACCORDION_ITEM_BLOCK ) {
			// The item's title renders as a real heading (with a button
			// inside); its panel content shares the item's context.
			entries.push( {
				clientId: block.clientId,
				level: clampHeadingLevel( contextLevel ?? MIN_HEADING_LEVEL ),
				text: toPlainText( block.attributes.title ),
				source: 'derived',
			} );
			entries.push(
				...collectOutline( block.innerBlocks, contextLevel )
			);
			continue;
		}

		if ( block.name === HEADING_BLOCK ) {
			entries.push( {
				clientId: block.clientId,
				level: clampHeadingLevel( contextLevel ?? MIN_HEADING_LEVEL ),
				text: toPlainText( block.attributes.content ),
				source: 'derived',
			} );
			continue;
		}

		if ( block.name === CORE_HEADING_BLOCK ) {
			const raw = Number( block.attributes.level ?? 2 );
			entries.push( {
				clientId: block.clientId,
				level: Number.isNaN( raw )
					? 2
					: Math.min( Math.max( Math.trunc( raw ), 1 ), 6 ),
				text: toPlainText( block.attributes.content ),
				source: 'manual',
			} );
			continue;
		}

		// Any other container (core/group, columns, …) passes the current
		// context through unchanged — exactly how block context propagates.
		if ( block.innerBlocks.length > 0 ) {
			entries.push(
				...collectOutline( block.innerBlocks, contextLevel )
			);
		}
	}

	return entries;
}

/**
 * Find outline problems in a collected outline.
 *
 * Rules (the page title is assumed to be the document's H1):
 * - A heading must not be more than one level deeper than the deepest
 *   valid level so far ("skipped level").
 * - No content heading should be another H1 ("multiple-h1") — only core
 *   headings can even produce one.
 *
 * Derived headings can't create these issues by construction; this exists
 * to catch hand-picked core heading levels (enforcement layer 2).
 *
 * @param outline Entries from collectOutline(), in document order.
 */
export function findOutlineIssues( outline: OutlineEntry[] ): OutlineIssue[] {
	const issues: OutlineIssue[] = [];

	// The page title is H1, so the first content heading may be at most H2.
	let previousLevel = 1;

	for ( const entry of outline ) {
		if ( entry.level === 1 ) {
			issues.push( {
				type: 'multiple-h1',
				clientId: entry.clientId,
				level: 1,
			} );
			// An H1 still participates in sequence checking.
			previousLevel = 1;
			continue;
		}

		const expectedMax = previousLevel + 1;
		if ( entry.level > expectedMax ) {
			issues.push( {
				type: 'skipped-level',
				clientId: entry.clientId,
				level: entry.level,
				expectedMax,
			} );
		}

		previousLevel = entry.level;
	}

	return issues;
}
