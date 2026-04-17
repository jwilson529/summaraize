<?php
/**
 * Tests for the plugin logger.
 *
 * @package Summaraize
 */

defined( 'ABSPATH' ) || exit;

/**
 * Covers logger behavior.
 */
class Test_Summaraize_Logger extends WP_UnitTestCase {

	/**
	 * The temporary log file path.
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * Set up logger redirection for each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->log_file = trailingslashit( sys_get_temp_dir() ) . 'summaraize-test.log';

		if ( file_exists( $this->log_file ) ) {
			wp_delete_file( $this->log_file );
		}

		add_filter( 'summaraize_log_file_path', array( $this, 'filter_log_file_path' ) );
	}

	/**
	 * Remove logger redirection after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'summaraize_log_file_path', array( $this, 'filter_log_file_path' ) );

		if ( file_exists( $this->log_file ) ) {
			wp_delete_file( $this->log_file );
		}

		parent::tear_down();
	}

	/**
	 * Provide the filtered test log path.
	 *
	 * @return string
	 */
	public function filter_log_file_path() {
		return $this->log_file;
	}

	/**
	 * Logger writes contextual messages to the configured file.
	 *
	 * @return void
	 */
	public function test_logger_writes_messages_to_file() {
		$written = Summaraize_Logger::error(
			'Test failure',
			array(
				'post_id' => 123,
			)
		);

		$this->assertTrue( $written );
		$this->assertFileExists( $this->log_file );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading the temp test artifact is required to assert logger output.
		$contents = file_get_contents( $this->log_file );

		$this->assertIsString( $contents );
		$this->assertStringContainsString( 'error: Test failure', $contents );
		$this->assertStringContainsString( '"post_id":123', $contents );
	}
}
