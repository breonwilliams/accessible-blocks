/**
 * Accessible Accordion — Interactivity API store.
 *
 * Behavior per the WAI-ARIA APG accordion pattern:
 * - Enter/Space toggle the focused header (native button behavior).
 * - Up/Down arrows move focus between headers; Home/End jump to first/last.
 * - aria-expanded and the panel's hidden attribute are bound to context.
 *
 * Progressive enhancement: panels are server-rendered *visible* (no hidden
 * attribute), so content is reachable without JavaScript. On hydration the
 * closed state applies.
 */
import { getContext, store } from '@wordpress/interactivity';

interface AccordionItemContext {
	isOpen: boolean;
}

const TRIGGER_SELECTOR = '.ab-accordion-item__trigger';

/**
 * All trigger buttons in the same accordion as the event target.
 *
 * @param target Event target (a trigger button).
 */
function getTriggers( target: HTMLElement ): HTMLElement[] {
	const accordion = target.closest( '.wp-block-guardrail-blocks-accordion' );
	return accordion
		? ( Array.from(
				accordion.querySelectorAll( TRIGGER_SELECTOR )
		  ) as HTMLElement[] )
		: [];
}

store( 'guardrail-blocks/accordion', {
	actions: {
		toggle(): void {
			const context = getContext< AccordionItemContext >();
			context.isOpen = ! context.isOpen;
		},

		handleKeydown( event: KeyboardEvent ): void {
			const { key } = event;
			if (
				key !== 'ArrowDown' &&
				key !== 'ArrowUp' &&
				key !== 'Home' &&
				key !== 'End'
			) {
				return;
			}

			const target = event.target as HTMLElement;
			const triggers = getTriggers( target );
			if ( triggers.length === 0 ) {
				return;
			}

			event.preventDefault();

			const current = triggers.indexOf( target );
			let next = current;

			if ( key === 'ArrowDown' ) {
				next = ( current + 1 ) % triggers.length;
			} else if ( key === 'ArrowUp' ) {
				next = ( current - 1 + triggers.length ) % triggers.length;
			} else if ( key === 'Home' ) {
				next = 0;
			} else {
				next = triggers.length - 1;
			}

			triggers[ next ]?.focus();
		},
	},
} );
