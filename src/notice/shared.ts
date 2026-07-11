/**
 * Notice — shared type/role/label mapping used by edit and save.
 *
 * Roles per the semantics of static page content:
 * - info/success/warning → 'note'/'status' (polite),
 * - error → 'alert' (assertive) so it's announced when revealed.
 * The visible text label ensures the meaning never relies on color alone
 * (WCAG 1.4.1).
 */
import { __ } from '@wordpress/i18n';

export type NoticeType = 'info' | 'success' | 'warning' | 'error';

export const NOTICE_ROLES: Record< NoticeType, string > = {
	info: 'note',
	success: 'status',
	warning: 'status',
	error: 'alert',
};

/**
 * Visible label for a notice type (translated at call time).
 *
 * @param type Notice type.
 */
export function noticeLabel( type: NoticeType ): string {
	switch ( type ) {
		case 'success':
			return __( 'Success', 'guardrail-blocks' );
		case 'warning':
			return __( 'Warning', 'guardrail-blocks' );
		case 'error':
			return __( 'Error', 'guardrail-blocks' );
		default:
			return __( 'Note', 'guardrail-blocks' );
	}
}

/**
 * Normalize any stored value to a valid type.
 *
 * @param value Stored attribute value.
 */
export function normalizeNoticeType( value: unknown ): NoticeType {
	return value === 'success' || value === 'warning' || value === 'error'
		? value
		: 'info';
}
