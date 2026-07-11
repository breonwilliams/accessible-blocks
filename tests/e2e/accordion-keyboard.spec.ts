/**
 * E2E — Accordion ARIA + keyboard behavior on the real front end
 * (Interactivity API hydrated): Enter toggles, arrows move focus,
 * aria-expanded and hidden stay in sync.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

const ACCORDION_CONTENT = `
<!-- wp:guardrail-blocks/accordion -->
<div class="wp-block-guardrail-blocks-accordion" data-wp-interactive="guardrail-blocks/accordion"><!-- wp:guardrail-blocks/accordion-item {"title":"First question"} -->
<!-- wp:paragraph --><p>First answer.</p><!-- /wp:paragraph -->
<!-- /wp:guardrail-blocks/accordion-item -->

<!-- wp:guardrail-blocks/accordion-item {"title":"Second question"} -->
<!-- wp:paragraph --><p>Second answer.</p><!-- /wp:paragraph -->
<!-- /wp:guardrail-blocks/accordion-item --></div>
<!-- /wp:guardrail-blocks/accordion -->
`;

test.describe( 'Accordion keyboard interaction (front end)', () => {
	test( 'Enter toggles, arrows move focus, ARIA stays in sync', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { postType: 'page', title: 'FAQ E2E' } );
		await editor.setContent( ACCORDION_CONTENT );
		const postId = await editor.publishPost();

		await page.goto( `/?page_id=${ postId }` );

		const triggers = page.locator( '.ab-accordion-item__trigger' );
		await expect( triggers ).toHaveCount( 2 );

		// Collapsed after hydration.
		await expect( triggers.nth( 0 ) ).toHaveAttribute(
			'aria-expanded',
			'false'
		);

		// Keyboard: focus first trigger, Enter opens it.
		await triggers.nth( 0 ).focus();
		await page.keyboard.press( 'Enter' );
		await expect( triggers.nth( 0 ) ).toHaveAttribute(
			'aria-expanded',
			'true'
		);
		await expect( page.getByText( 'First answer.' ) ).toBeVisible();

		// ArrowDown moves focus to the second header (APG pattern).
		await page.keyboard.press( 'ArrowDown' );
		await expect( triggers.nth( 1 ) ).toBeFocused();

		// Space toggles the second item open.
		await page.keyboard.press( 'Space' );
		await expect( triggers.nth( 1 ) ).toHaveAttribute(
			'aria-expanded',
			'true'
		);

		// ArrowUp wraps focus back to the first header.
		await page.keyboard.press( 'ArrowUp' );
		await expect( triggers.nth( 0 ) ).toBeFocused();

		// Enter again closes it; panel gets hidden.
		await page.keyboard.press( 'Enter' );
		await expect( triggers.nth( 0 ) ).toHaveAttribute(
			'aria-expanded',
			'false'
		);
		await expect( page.getByText( 'First answer.' ) ).toBeHidden();
	} );
} );
