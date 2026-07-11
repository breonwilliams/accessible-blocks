/**
 * Jest suite for the outline engine (Guarantee B core).
 */
import {
	clampHeadingLevel,
	collectOutline,
	deriveSectionLevel,
	findOutlineIssues,
	type BlockNode,
} from './outline';

let nextId = 0;

/**
 * Terse block-tree builder for tests.
 *
 * @param name       Block name.
 * @param attributes Attributes.
 * @param children   Inner blocks.
 */
function block(
	name: string,
	attributes: Record< string, unknown > = {},
	children: BlockNode[] = []
): BlockNode {
	nextId += 1;
	return {
		clientId: `client-${ nextId }`,
		name,
		attributes,
		innerBlocks: children,
	};
}

const section = ( ...children: BlockNode[] ) =>
	block( 'guardrail-blocks/section', {}, children );
const heading = ( content = 'Heading' ) =>
	block( 'guardrail-blocks/heading', { content } );
const coreHeading = ( level: number, content = 'Core' ) =>
	block( 'core/heading', { level, content } );
const group = ( ...children: BlockNode[] ) =>
	block( 'core/group', {}, children );
const paragraph = () => block( 'core/paragraph', { content: 'text' } );

describe( 'clampHeadingLevel', () => {
	it( 'clamps into the H2–H6 range', () => {
		expect( clampHeadingLevel( 2 ) ).toBe( 2 );
		expect( clampHeadingLevel( 6 ) ).toBe( 6 );
		expect( clampHeadingLevel( 1 ) ).toBe( 2 );
		expect( clampHeadingLevel( 7 ) ).toBe( 6 );
		expect( clampHeadingLevel( 99 ) ).toBe( 6 );
	} );

	it( 'handles junk input safely', () => {
		expect( clampHeadingLevel( undefined ) ).toBe( 2 );
		expect( clampHeadingLevel( null ) ).toBe( 2 );
		expect( clampHeadingLevel( 'potato' ) ).toBe( 2 );
		expect( clampHeadingLevel( 3.9 ) ).toBe( 3 );
	} );
} );

describe( 'deriveSectionLevel', () => {
	it( 'is 2 at the top level (below the page-title H1)', () => {
		expect( deriveSectionLevel( null ) ).toBe( 2 );
		expect( deriveSectionLevel( undefined ) ).toBe( 2 );
	} );

	it( 'is parent + 1 when nested', () => {
		expect( deriveSectionLevel( 2 ) ).toBe( 3 );
		expect( deriveSectionLevel( 3 ) ).toBe( 4 );
		expect( deriveSectionLevel( 5 ) ).toBe( 6 );
	} );

	it( 'caps at 6 no matter how deep (edge-case checklist)', () => {
		expect( deriveSectionLevel( 6 ) ).toBe( 6 );
		expect( deriveSectionLevel( 12 ) ).toBe( 6 );
	} );
} );

describe( 'collectOutline', () => {
	it( 'derives levels from section nesting', () => {
		const tree = [
			section( heading( 'Top' ), section( heading( 'Nested' ) ) ),
		];
		const outline = collectOutline( tree );
		expect( outline.map( ( e ) => e.level ) ).toEqual( [ 2, 3 ] );
		expect( outline.map( ( e ) => e.text ) ).toEqual( [ 'Top', 'Nested' ] );
		expect( outline.every( ( e ) => e.source === 'derived' ) ).toBe( true );
	} );

	it( 'caps derived levels at 6 for absurd nesting', () => {
		// 8 sections deep → still H6.
		let tree = section( heading( 'Deep' ) );
		for ( let i = 0; i < 7; i++ ) {
			tree = section( tree );
		}
		const outline = collectOutline( [ tree ] );
		expect( outline ).toHaveLength( 1 );
		expect( outline[ 0 ]!.level ).toBe( 6 );
	} );

	it( 'defaults a heading outside any section to H2', () => {
		const outline = collectOutline( [ heading( 'Loose' ) ] );
		expect( outline[ 0 ]!.level ).toBe( 2 );
	} );

	it( 'passes context through non-section containers (like core/group)', () => {
		const tree = [ section( group( heading( 'Grouped' ) ) ) ];
		const outline = collectOutline( tree );
		expect( outline[ 0 ]!.level ).toBe( 2 );
	} );

	it( 'includes core headings with their hand-picked level', () => {
		const tree = [
			section( heading( 'Ours' ) ),
			coreHeading( 4, 'Theirs' ),
		];
		const outline = collectOutline( tree );
		expect( outline ).toHaveLength( 2 );
		expect( outline[ 1 ] ).toMatchObject( {
			level: 4,
			text: 'Theirs',
			source: 'manual',
		} );
	} );

	it( 'reorder simply recomputes — moving a nested heading up promotes it', () => {
		const nested = heading( 'Movable' );
		const before = collectOutline( [
			section( heading( 'A' ), section( nested ) ),
		] );
		expect( before[ 1 ]!.level ).toBe( 3 );

		// Same block moved to the outer section (what drag/drop produces).
		const after = collectOutline( [ section( heading( 'A' ), nested ) ] );
		expect( after[ 1 ]!.level ).toBe( 2 );
	} );

	it( 'card and accordion titles nest one deeper than the section heading', () => {
		const tree = [
			section(
				heading( 'Features' ),
				block( 'guardrail-blocks/card-grid', {}, [
					block( 'guardrail-blocks/card', {}, [
						heading( 'Card title' ),
					] ),
				] ),
				block( 'guardrail-blocks/accordion', {}, [
					block( 'guardrail-blocks/accordion-item', {
						title: 'Question?',
					} ),
				] )
			),
		];
		const outline = collectOutline( tree );
		expect( outline.map( ( e ) => [ e.level, e.text ] ) ).toEqual( [
			[ 2, 'Features' ],
			[ 3, 'Card title' ],
			[ 3, 'Question?' ],
		] );
	} );

	it( 'strips markup from heading text and skips non-heading blocks', () => {
		const tree = [
			section(
				block( 'guardrail-blocks/heading', {
					content: 'Hello <em>world</em>',
				} ),
				paragraph()
			),
		];
		const outline = collectOutline( tree );
		expect( outline ).toHaveLength( 1 );
		expect( outline[ 0 ]!.text ).toBe( 'Hello world' );
	} );
} );

describe( 'findOutlineIssues', () => {
	it( 'reports no issues for a clean derived outline', () => {
		const outline = collectOutline( [
			section(
				heading( 'One' ),
				section( heading( 'One.One' ), section( heading( 'Deep' ) ) )
			),
			section( heading( 'Two' ) ),
		] );
		expect( findOutlineIssues( outline ) ).toEqual( [] );
	} );

	it( 'catches a skipped level from a hand-picked core heading', () => {
		const outline = collectOutline( [
			coreHeading( 2, 'Fine' ),
			coreHeading( 5, 'Skipped' ),
		] );
		const issues = findOutlineIssues( outline );
		expect( issues ).toHaveLength( 1 );
		expect( issues[ 0 ] ).toMatchObject( {
			type: 'skipped-level',
			level: 5,
			expectedMax: 3,
		} );
	} );

	it( 'catches a document that starts deeper than H2', () => {
		const outline = collectOutline( [ coreHeading( 3, 'Starts at 3' ) ] );
		const issues = findOutlineIssues( outline );
		expect( issues ).toHaveLength( 1 );
		expect( issues[ 0 ] ).toMatchObject( {
			type: 'skipped-level',
			expectedMax: 2,
		} );
	} );

	it( 'catches extra H1s (page title already is one)', () => {
		const outline = collectOutline( [ coreHeading( 1, 'Rogue H1' ) ] );
		expect( findOutlineIssues( outline ) ).toEqual( [
			expect.objectContaining( { type: 'multiple-h1' } ),
		] );
	} );

	it( 'going shallower is always fine (H4 back to H2)', () => {
		const outline = collectOutline( [
			coreHeading( 2, 'a' ),
			coreHeading( 3, 'b' ),
			coreHeading( 4, 'c' ),
			coreHeading( 2, 'back up' ),
		] );
		expect( findOutlineIssues( outline ) ).toEqual( [] );
	} );

	it( 'mixed derived + core headings are checked as one document', () => {
		const outline = collectOutline( [
			section( heading( 'H2 derived' ) ),
			coreHeading( 4, 'H4 skips H3' ),
		] );
		const issues = findOutlineIssues( outline );
		expect( issues ).toHaveLength( 1 );
		expect( issues[ 0 ] ).toMatchObject( {
			type: 'skipped-level',
			level: 4,
			expectedMax: 3,
		} );
	} );
} );
