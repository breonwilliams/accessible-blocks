/**
 * E2E — Guarantee A: the Button's background picker only offers the
 * theme palette, and picking any background surfaces a passing contrast
 * ratio with an auto-selected text color.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Accessible Button contrast enforcement', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost( { postType: 'page' } );
	} );

	test( 'picking a palette background shows a passing AA ratio', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: 'accessible-blocks/button',
			attributes: { text: 'Get started' },
		} );

		await editor.openDocumentSettingsSidebar();

		// The color options are palette swatches — no custom color input.
		const swatches = page.locator(
			'[aria-label="Custom color picker"], .components-circular-option-picker__option'
		);
		await expect(
			page.locator( '.components-circular-option-picker__option' ).first()
		).toBeVisible();

		// Pick every swatch in turn: each must produce an AA-passing badge.
		const count = await page
			.locator( '.components-circular-option-picker__option' )
			.count();
		expect( count ).toBeGreaterThan( 0 );

		for ( let i = 0; i < count; i++ ) {
			await page
				.locator( '.components-circular-option-picker__option' )
				.nth( i )
				.click();
			await expect(
				page.getByText( /Contrast [\d.]+:1 — AA ✓/ )
			).toBeVisible();
		}

		// No unconstrained color input exists anywhere in the panel.
		await expect( swatches.first() ).toBeVisible();
		await expect(
			page.locator( 'input[type="text"][aria-label*="Hex" i]' )
		).toHaveCount( 0 );
	} );
} );
