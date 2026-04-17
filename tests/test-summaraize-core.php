<?php
/**
 * Tests for the core plugin class.
 *
 * @package Summaraize
 */

defined( 'ABSPATH' ) || exit;

/**
 * Covers core plugin bootstrap behavior.
 */
class Test_Summaraize_Core extends WP_UnitTestCase {

	/**
	 * Ensure the plugin core exposes stable metadata.
	 *
	 * @return void
	 */
	public function test_core_exposes_expected_plugin_metadata() {
		$plugin = new Summaraize();

		$this->assertSame( 'summaraize', $plugin->get_plugin_name() );
		$this->assertSame( SUMMARAIZE_VERSION, $plugin->get_version() );
		$this->assertInstanceOf( 'Summaraize_Loader', $plugin->get_loader() );
	}

	/**
	 * Ensure the plugin settings link is added on the plugins screen.
	 *
	 * @return void
	 */
	public function test_settings_link_is_added() {
		$settings = new Summaraize_Admin_Settings();
		$links    = $settings->add_settings_link( array( 'Deactivate' ) );

		$this->assertStringContainsString( 'options-general.php?page=summaraize-settings', $links[0] );
	}
}
