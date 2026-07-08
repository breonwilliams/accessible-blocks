/**
 * E2E — Guarantee B: heading levels derive from section nesting and
 * self-correct on reorder. This is the editor-level proof that the unit
 * tests can't give: real block context, real store, real reordering.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Accessible Heading level derivation', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost( { postType: 'page' } );
	} );

	test( 'renders H2 at the top level and H3 when nested', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: 'accessible-blocks/section' } );

		// The section template creates an Accessible Heading; it must be
		// a real <h2> in the editor canvas.
		const heading = editor.canvas.locator(
			'[data-type="accessible-blocks/heading"]'
		);
		await expect( heading ).toHaveJSProperty( 'tagName', 'H2' );

		// Nest a second section inside the first via the block store (the
		// same operation the inserter performs).
		await page.evaluate( () => {
			const w = window as any;
			const outer = w.wp.data
				.select( 'core/block-editor' )
				.getBlocks()
				.find( ( b: any ) => b.name === 'accessible-blocks/section' );
			const nested = w.wp.blocks.createBlock(
				'accessible-blocks/section'
			);
			w.wp.data
				.dispatch( 'core/block-editor' )
				.insertBlocks(
					nested,
					outer.innerBlocks.length,
					outer.clientId
				);
		} );

		const nestedHeading = editor.canvas.locator(
			'[data-type="accessible-blocks/section"] [data-type="accessible-blocks/section"] [data-type="accessible-blocks/heading"]'
		);
		await expect( nestedHeading ).toHaveJSProperty( 'tagName', 'H3' );
	} );

	test( 'self-corrects when a nested section moves to the top level', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: 'accessible-blocks/section' } );

		await page.evaluate( () => {
			const w = window as any;
			const sel = w.wp.data.select( 'core/block-editor' );
			const d = w.wp.data.dispatch( 'core/block-editor' );
			const outer = sel
				.getBlocks()
				.find( ( b: any ) => b.name === 'accessible-blocks/section' );
			const nested = w.wp.blocks.createBlock(
				'accessible-blocks/section'
			);
			d.insertBlocks( nested, outer.innerBlocks.length, outer.clientId );
		} );

		// Move the nested section to the document root (= drag out).
		await page.evaluate( () => {
			const w = window as any;
			const sel = w.wp.data.select( 'core/block-editor' );
			const d = w.wp.data.dispatch( 'core/block-editor' );
			const outer = sel
				.getBlocks()
				.find( ( b: any ) => b.name === 'accessible-blocks/section' );
			const nested = outer.innerBlocks.find(
				( b: any ) => b.name === 'accessible-blocks/section'
			);
			d.moveBlocksToPosition(
				[ nested.clientId ],
				outer.clientId,
				'',
				1
			);
		} );

		// Both sections are now top-level → both headings are H2.
		const headings = editor.canvas.locator(
			'[data-type="accessible-blocks/heading"]'
		);
		await expect( headings ).toHaveCount( 2 );
		await expect( headings.nth( 0 ) ).toHaveJSProperty( 'tagName', 'H2' );
		await expect( headings.nth( 1 ) ).toHaveJSProperty( 'tagName', 'H2' );
	} );
} );
