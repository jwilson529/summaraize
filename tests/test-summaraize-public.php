<?php
/**
 * Tests for public rendering behavior.
 *
 * @package Summaraize
 */

defined( 'ABSPATH' ) || exit;

/**
 * Covers shortcode and public display markup.
 */
class Test_Summaraize_Public extends WP_UnitTestCase {

	/**
	 * Public renderer under test.
	 *
	 * @var Summaraize_Public
	 */
	private $public;

	/**
	 * Prime the public renderer before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->public = new Summaraize_Public( 'summaraize', SUMMARAIZE_VERSION );
	}

	/**
	 * Remove title tag filters after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_filter( 'summaraize_widget_title_tag', array( $this, 'filter_widget_title_tag_to_h3' ) );
		remove_filter( 'summaraize_widget_title_tag', array( $this, 'filter_widget_title_tag_to_script' ) );

		parent::tearDown();
	}

	/**
	 * Ensure the default widget title is not rendered as a content heading.
	 *
	 * @return void
	 */
	public function test_build_view_renders_widget_title_as_paragraph_by_default() {
		$output = $this->public->build_view(
			array( 'Alpha point' ),
			'above',
			'light',
			'',
			'Key Takeaways',
			'flat',
			'#0073aa',
			'unordered'
		);

		$this->assertStringContainsString( '<p class="summaraize-title">Key Takeaways</p>', $output );
		$this->assertStringNotContainsString( '<h2>Key Takeaways</h2>', $output );
	}

	/**
	 * Ensure custom title heading tags remain available to sites that opt in.
	 *
	 * @return void
	 */
	public function test_build_view_allows_filtered_widget_title_heading_tag() {
		add_filter( 'summaraize_widget_title_tag', array( $this, 'filter_widget_title_tag_to_h3' ) );

		$output = $this->public->build_view(
			array( 'Alpha point' ),
			'above',
			'light',
			'',
			'Key Takeaways',
			'flat',
			'#0073aa',
			'unordered'
		);

		$this->assertStringContainsString( '<h3 class="summaraize-title">Key Takeaways</h3>', $output );
	}

	/**
	 * Ensure unsafe filtered title tags fall back to paragraph output.
	 *
	 * @return void
	 */
	public function test_build_view_rejects_unsafe_filtered_widget_title_tag() {
		add_filter( 'summaraize_widget_title_tag', array( $this, 'filter_widget_title_tag_to_script' ) );

		$output = $this->public->build_view(
			array( 'Alpha point' ),
			'above',
			'light',
			'',
			'Key Takeaways',
			'flat',
			'#0073aa',
			'unordered'
		);

		$this->assertStringContainsString( '<p class="summaraize-title">Key Takeaways</p>', $output );
		$this->assertStringNotContainsString( '<script', $output );
	}

	/**
	 * Return an h3 title tag for filter coverage.
	 *
	 * @return string
	 */
	public function filter_widget_title_tag_to_h3() {
		return 'h3';
	}

	/**
	 * Return an unsafe title tag for fallback coverage.
	 *
	 * @return string
	 */
	public function filter_widget_title_tag_to_script() {
		return 'script';
	}
}
