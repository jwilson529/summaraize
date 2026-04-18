<?php
/**
 * Tests for summary lifecycle automation.
 *
 * @package Summaraize
 */

defined( 'ABSPATH' ) || exit;

/**
 * Covers generation metadata, stale detection, and automation helpers.
 */
class Test_Summaraize_Summary_Manager extends WP_UnitTestCase {

	/**
	 * Mocked summary points returned by the OpenAI API.
	 *
	 * @var array
	 */
	private $mock_points = array();

	/**
	 * Scheduled post IDs created during a test.
	 *
	 * @var array
	 */
	private $scheduled_post_ids = array();

	/**
	 * Prime options and HTTP mocks before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'summaraize_post_types', array( 'post' ) );
		update_option( 'summaraize_ai_provider', 'openai' );
		update_option( 'summaraize_openai_api_key', 'sk-test' );
		update_option( 'summaraize_ai_model', 'gpt-5-mini' );
		update_option( Summaraize_Summary_Manager::OPTION_AUTO_GENERATE_MODE, Summaraize_Summary_Manager::AUTO_GENERATE_OFF );
		set_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY, array( 'gpt-5-mini' ), DAY_IN_SECONDS );

		$this->mock_points = array(
			array(
				'index' => 1,
				'text'  => 'Alpha point',
			),
			array(
				'index' => 2,
				'text'  => 'Beta point',
			),
		);

		add_filter( 'pre_http_request', array( $this, 'mock_openai_requests' ), 10, 3 );
	}

	/**
	 * Remove options, transients, and cron events after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_openai_requests' ), 10 );

		foreach ( $this->scheduled_post_ids as $post_id ) {
			wp_clear_scheduled_hook( Summaraize_Summary_Manager::AUTO_GENERATE_EVENT, array( $post_id ) );
		}

		delete_option( 'summaraize_post_types' );
		delete_option( 'summaraize_ai_provider' );
		delete_option( 'summaraize_openai_api_key' );
		delete_option( 'summaraize_ai_model' );
		delete_option( Summaraize_Summary_Manager::OPTION_AUTO_GENERATE_MODE );
		delete_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY );

		parent::tearDown();
	}

	/**
	 * Return a mocked OpenAI chat-completions response.
	 *
	 * @param mixed  $preempt Existing preempt value.
	 * @param array  $args    Request arguments.
	 * @param string $url     Request URL.
	 * @return mixed
	 */
	public function mock_openai_requests( $preempt, $args, $url ) {
		if ( Summaraize_OpenAI_Settings::OPENAI_CHAT_COMPLETIONS_ENDPOINT !== $url ) {
			return $preempt;
		}

		$body = wp_json_encode(
			array(
				'choices' => array(
					array(
						'message' => array(
							'content' => wp_json_encode(
								array(
									'points' => $this->mock_points,
								)
							),
						),
					),
				),
			)
		);

		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Ensure generated summaries persist points and provenance metadata.
	 *
	 * @return void
	 */
	public function test_generate_summary_for_post_persists_lifecycle_metadata() {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_content' => 'Example article body.',
			)
		);

		$result = Summaraize_Summary_Manager::generate_summary_for_post( $post_id );

		$this->assertIsArray( $result );
		$this->assertSame( array( 'Alpha point', 'Beta point' ), get_post_meta( $post_id, 'summaraize_points', true ) );
		$this->assertSame( Summaraize_Summary_Manager::STATUS_CURRENT, Summaraize_Summary_Manager::get_summary_status( $post_id ) );
		$this->assertSame( 'openai', get_post_meta( $post_id, Summaraize_Summary_Manager::META_GENERATION_PROVIDER, true ) );
		$this->assertSame( 'gpt-5-mini', get_post_meta( $post_id, Summaraize_Summary_Manager::META_GENERATION_MODEL, true ) );
		$this->assertNotEmpty( get_post_meta( $post_id, Summaraize_Summary_Manager::META_GENERATED_AT, true ) );
		$this->assertSame( '0', get_post_meta( $post_id, Summaraize_Summary_Manager::META_MANUALLY_EDITED, true ) );
	}

	/**
	 * Ensure summaries become stale when post content changes after generation.
	 *
	 * @return void
	 */
	public function test_summary_status_becomes_stale_after_content_change() {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => 'Original body content.',
			)
		);

		Summaraize_Summary_Manager::generate_summary_for_post( $post_id );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => 'Updated body content.',
			)
		);

		$this->assertSame( Summaraize_Summary_Manager::STATUS_STALE, Summaraize_Summary_Manager::get_summary_status( $post_id ) );
	}

	/**
	 * Ensure editor-session metadata marks changed points as manually edited.
	 *
	 * @return void
	 */
	public function test_sync_editor_session_generation_state_marks_manual_edits() {
		$post_id = self::factory()->post->create();
		$points  = array( 'Alpha point', 'Beta point' );

		update_post_meta( $post_id, 'summaraize_points', $points );

		Summaraize_Summary_Manager::sync_editor_session_generation_state(
			$post_id,
			array( 'Alpha point', 'Customized point' ),
			Summaraize_Summary_Manager::build_editor_session_summary_meta(
				$points,
				'Original generated content',
				'openai',
				'gpt-5-mini'
			)
		);

		$this->assertSame( '1', get_post_meta( $post_id, Summaraize_Summary_Manager::META_MANUALLY_EDITED, true ) );
		$this->assertSame( Summaraize_Summary_Manager::STATUS_EDITED, Summaraize_Summary_Manager::get_summary_status( $post_id ) );
	}

	/**
	 * Ensure bulk generate only processes posts that are missing summaries.
	 *
	 * @return void
	 */
	public function test_bulk_generate_skips_posts_that_already_have_summaries() {
		$missing_post_id  = self::factory()->post->create(
			array(
				'post_content' => 'Missing summary content.',
			)
		);
		$existing_post_id = self::factory()->post->create(
			array(
				'post_content' => 'Existing summary content.',
			)
		);

		update_post_meta( $existing_post_id, 'summaraize_points', array( 'Already saved point' ) );

		$manager  = new Summaraize_Summary_Manager();
		$redirect = $manager->handle_bulk_actions(
			admin_url( 'edit.php' ),
			Summaraize_Summary_Manager::BULK_ACTION_GENERATE,
			array( $missing_post_id, $existing_post_id )
		);

		parse_str( (string) wp_parse_url( $redirect, PHP_URL_QUERY ), $query_args );

		$this->assertSame( '1', (string) $query_args['summaraize_generated'] );
		$this->assertSame( '1', (string) $query_args['summaraize_skipped'] );
		$this->assertSame( '0', (string) $query_args['summaraize_failed'] );
		$this->assertSame( array( 'Alpha point', 'Beta point' ), get_post_meta( $missing_post_id, 'summaraize_points', true ) );
		$this->assertSame( array( 'Already saved point' ), get_post_meta( $existing_post_id, 'summaraize_points', true ) );
	}

	/**
	 * Ensure first-publish automation schedules and generates missing summaries.
	 *
	 * @return void
	 */
	public function test_auto_generate_on_publish_schedules_and_runs_for_missing_summary() {
		update_option( Summaraize_Summary_Manager::OPTION_AUTO_GENERATE_MODE, Summaraize_Summary_Manager::AUTO_GENERATE_MISSING_ON_PUBLISH );

		$post_id = self::factory()->post->create(
			array(
				'post_status'  => 'draft',
				'post_content' => 'Publish-time content.',
			)
		);

		$this->scheduled_post_ids[] = $post_id;

		$manager = new Summaraize_Summary_Manager();
		$manager->schedule_auto_generate_on_publish( 'publish', 'draft', get_post( $post_id ) );

		$this->assertNotFalse( wp_next_scheduled( Summaraize_Summary_Manager::AUTO_GENERATE_EVENT, array( $post_id ) ) );

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		$manager->run_scheduled_auto_generate( $post_id );

		$this->assertSame( array( 'Alpha point', 'Beta point' ), get_post_meta( $post_id, 'summaraize_points', true ) );
	}
}
