<?php
/**
 * Block registration from build metadata.
 *
 * @package GuardrailBlocks
 */

declare( strict_types=1 );

namespace GuardrailBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers every block found in the build directory.
 *
 * The registry pattern from Foundation §12: a new block is added by dropping
 * a folder in `src/` — the build emits `build/<block>/block.json` and this
 * class picks it up. No per-block registration code, ever.
 */
class Block_Registrar {

	/**
	 * Absolute path to the plugin root (trailing slash).
	 *
	 * @var string
	 */
	private string $plugin_dir;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_dir Absolute path to the plugin root.
	 */
	public function __construct( string $plugin_dir ) {
		$this->plugin_dir = trailingslashit( $plugin_dir );
	}

	/**
	 * Attach WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register all built blocks.
	 *
	 * No-op when the build directory is absent (fresh checkout before
	 * `npm run build`), so activation can never fatal.
	 */
	public function register_blocks(): void {
		$build_dir = $this->plugin_dir . 'build';

		if ( ! is_dir( $build_dir ) ) {
			return;
		}

		$manifest = $build_dir . '/blocks-manifest.php';

		if ( file_exists( $manifest ) ) {
			// Single-file metadata collection (WP 6.7+; our floor is 6.9)
			// avoids one filesystem read + JSON decode per block per request.
			wp_register_block_metadata_collection( $build_dir, $manifest );
		}

		$block_json_files = glob( $build_dir . '/*/block.json' );

		if ( empty( $block_json_files ) ) {
			return;
		}

		foreach ( $block_json_files as $block_json ) {
			register_block_type( dirname( $block_json ) );
		}
	}
}
