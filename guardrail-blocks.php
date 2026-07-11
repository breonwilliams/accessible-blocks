<?php
/**
 * Plugin Name:       Guardrail Blocks
 * Plugin URI:        https://github.com/breonwilliams/guardrail-blocks
 * Description:       Accessibility-first blocks with WCAG-safe color contrast and unbreakable heading hierarchy — enforced by design, not left to the author.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Breon Williams
 * Author URI:        https://breonwilliams.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       guardrail-blocks
 *
 * @package GuardrailBlocks
 */

declare( strict_types=1 );

namespace GuardrailBlocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GUARDRAIL_BLOCKS_VERSION', '0.1.0' );
define( 'GUARDRAIL_BLOCKS_PLUGIN_FILE', __FILE__ );
define( 'GUARDRAIL_BLOCKS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GUARDRAIL_BLOCKS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once GUARDRAIL_BLOCKS_PLUGIN_DIR . 'includes/class-contrast.php';
require_once GUARDRAIL_BLOCKS_PLUGIN_DIR . 'includes/class-outline.php';
require_once GUARDRAIL_BLOCKS_PLUGIN_DIR . 'includes/class-block-registrar.php';
require_once GUARDRAIL_BLOCKS_PLUGIN_DIR . 'includes/class-patterns.php';

/**
 * Boot the plugin.
 *
 * Kept intentionally thin: each subsystem is its own class with its own
 * hooks, so new subsystems (patterns, editor panels) register here without
 * touching each other.
 */
function bootstrap(): void {
	( new Block_Registrar( GUARDRAIL_BLOCKS_PLUGIN_DIR ) )->register_hooks();
	( new Patterns() )->register_hooks();
}

bootstrap();
