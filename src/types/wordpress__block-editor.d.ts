/**
 * Local type declarations for `@wordpress/block-editor`.
 *
 * The package doesn't ship its own TypeScript declarations (unlike
 * `@wordpress/blocks` and `@wordpress/components`), so we declare the
 * subset of its API this plugin uses. Typed narrowly on purpose: if a
 * block starts using a new export, add it here with a real signature
 * rather than widening anything to `any`.
 */
declare module '@wordpress/block-editor' {
	import type {
		ComponentType,
		CSSProperties,
		HTMLAttributes,
		ReactNode,
	} from 'react';

	/**
	 * What useBlockProps/useInnerBlocksProps return: spreadable DOM props
	 * for the block's wrapper element. `ref` is intentionally not declared —
	 * it exists at runtime but typing it per-element creates spurious
	 * variance errors when spreading onto different tags.
	 */
	export type BlockDOMProps = HTMLAttributes< HTMLElement >;

	export const InspectorControls: ComponentType< {
		group?: string;
		children?: ReactNode;
	} >;

	export const InnerBlocks: ComponentType< {
		children?: ReactNode;
	} > & {
		Content: ComponentType;
	};

	export const useBlockProps: {
		( props?: Record< string, unknown > ): BlockDOMProps;
		save( props?: Record< string, unknown > ): BlockDOMProps;
	};

	export const useInnerBlocksProps: {
		(
			props?: BlockDOMProps,
			options?: {
				template?: Array< [ string, Record< string, unknown >? ] >;
				templateLock?: string | boolean;
				allowedBlocks?: string[];
			}
		): BlockDOMProps;
		save( props?: BlockDOMProps ): BlockDOMProps;
	};

	/**
	 * Returns the values for the given theme.json setting paths, in order.
	 */
	export function useSettings( ...paths: string[] ): unknown[];

	export const RichText: ComponentType<
		{
			tagName?: string;
			value: string;
			onChange: ( value: string ) => void;
			placeholder?: string;
			allowedFormats?: string[];
			className?: string;
			style?: CSSProperties;
			identifier?: string;
		} & Omit< HTMLAttributes< HTMLElement >, 'onChange' | 'placeholder' >
	>;

	export const URLInput: ComponentType< {
		label?: string;
		value: string;
		onChange: ( url: string ) => void;
		className?: string;
	} >;

	export const ColorPalette: ComponentType< {
		colors: Array< { name?: string; slug?: string; color: string } >;
		value?: string;
		onChange: ( color?: string ) => void;
		disableCustomColors?: boolean;
		clearable?: boolean;
	} >;
}
