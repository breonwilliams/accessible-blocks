/**
 * Notice — editor component.
 *
 * The type comes from block variations; the role and visible label are
 * derived from it and cannot be mis-set. Text colors inherit from the
 * theme, so contrast cannot break (no color controls by design).
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { Notice as WPNotice, PanelBody } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';

import { NOTICE_ROLES, noticeLabel, normalizeNoticeType } from './shared';

export type NoticeAttributes = {
	type: string;
};

const TEMPLATE: Array< [ string, Record< string, unknown >? ] > = [
	[ 'core/paragraph' ],
];

export default function Edit( {
	attributes,
}: BlockEditProps< NoticeAttributes > ) {
	const type = normalizeNoticeType( attributes.type );

	const blockProps = useBlockProps( {
		className: `ab-notice ab-notice--${ type }`,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'ab-notice__content' },
		{ template: TEMPLATE }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Accessibility', 'guardrail-blocks' ) }>
					<WPNotice status="success" isDismissible={ false }>
						{ sprintf(
							/* translators: 1: notice type, 2: ARIA role. */
							__(
								'This %1$s notice renders with role="%2$s" and a visible text label, so its meaning never relies on color alone. Change the type via the block toolbar variations.',
								'guardrail-blocks'
							),
							type,
							NOTICE_ROLES[ type ]
						) }
					</WPNotice>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<strong className="ab-notice__label">
					{ noticeLabel( type ) }
				</strong>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
