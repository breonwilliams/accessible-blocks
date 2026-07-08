/**
 * Playwright configuration — extends the WordPress base config, which
 * handles auth setup, the tests-WordPress base URL (:8889 from wp-env),
 * and sensible retry/trace defaults.
 */
const { defineConfig } = require( '@playwright/test' );
const baseConfig = require( '@wordpress/scripts/config/playwright.config' );

module.exports = defineConfig( {
	...baseConfig,
	testDir: './tests/e2e',
} );
