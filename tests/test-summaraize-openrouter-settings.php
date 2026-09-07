<?php
/**
 * Regression tests for OpenRouter summaries and settings.
 *
 * @package Summaraize
 */

defined( 'ABSPATH' ) || exit;

/** Covers OpenRouter request contracts and failure handling. */
class Test_Summaraize_OpenRouter_Settings extends WP_UnitTestCase {
	/**
	 * Model IDs support vendor prefixes and variants, but reject malformed input.
	 *
	 * @return void
	 */
	public function test_model_validation() {
		$this->assertSame( 'vendor/model:free', Summaraize_OpenRouter_Settings::sanitize_model( 'vendor/model:free' ) );
		foreach ( array( '', '../model', 'no-vendor', 'vendor/model?token=x', array() ) as $invalid ) {
			$this->assertSame( '', Summaraize_OpenRouter_Settings::sanitize_model( $invalid ) );
		}
		update_option( 'summaraize_openrouter_model', 'vendor/saved' );
		$this->assertSame( 'vendor/saved', Summaraize_OpenRouter_Settings::sanitize_model_option( 'bad input' ) );
	}

	/**
	 * A completion must contain exactly five nonempty text points.
	 *
	 * @return void
	 */
	public function test_response_validation() {
		$points = array_fill( 0, 5, array( 'text' => 'A useful fact.' ) );
		$result = Summaraize_OpenRouter_Settings::parse_points( "```json\n" . wp_json_encode( array( 'points' => $points ) ) . "\n```" );
		$this->assertCount( 5, $result );
		$this->assertSame( 5, $result[4]['index'] );
		foreach ( array( null, 'not JSON', '{"points":[]}', '{"points":[1,2,3,4,5]}' ) as $invalid ) {
			$this->assertWPError( Summaraize_OpenRouter_Settings::parse_points( $invalid ) );
		}
		$points[0]['text'] = '<br>';
		$this->assertWPError( Summaraize_OpenRouter_Settings::parse_points( wp_json_encode( array( 'points' => $points ) ) ) );
	}

	/**
	 * Summary generation sends only the selected model and the OpenRouter key.
	 *
	 * @return void
	 */
	public function test_generation_routes_and_records_model() {
		update_option( 'summaraize_ai_provider', 'openrouter' );
		update_option( 'summaraize_openrouter_api_key', 'test-openrouter-key' );
		update_option( 'summaraize_openrouter_model', 'vendor/model' );
		$mock = function ( $preempt, $args, $url ) {
			$this->assertSame( 'https://openrouter.ai/api/v1/chat/completions', $url );
			$this->assertSame( 'Bearer test-openrouter-key', $args['headers']['Authorization'] );
			$payload = json_decode( $args['body'], true );
			$this->assertSame( 'vendor/model', $payload['model'] );
			$this->assertSame( 'Example source.', $payload['messages'][1]['content'] );
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'model'   => 'vendor/resolved',
						'choices' => array(
							array(
								'finish_reason' => 'stop',
								'message'       => array( 'content' => wp_json_encode( array( 'points' => array_fill( 0, 5, array( 'text' => 'A fact.' ) ) ) ) ),
							),
						),
					)
				),
			);
		};
		add_filter( 'pre_http_request', $mock, 10, 3 );
		try {
			$result = Summaraize_Summary_Manager::generate_summary_from_content( 'Example source.' );
			$this->assertSame( 'openrouter', $result['provider'] );
			$this->assertSame( 'vendor/resolved', $result['model'] );
			$this->assertCount( 5, $result['points'] );
		} finally {
			remove_filter( 'pre_http_request', $mock, 10 );
		}
	}

	/**
	 * Provider failures do not overwrite an existing post summary or expose payloads.
	 *
	 * @return void
	 */
	public function test_failures_preserve_saved_summary() {
		update_option( 'summaraize_ai_provider', 'openrouter' );
		update_option( 'summaraize_openrouter_api_key', 'test-key' );
		update_option( 'summaraize_openrouter_model', 'vendor/model' );
		$post_id = self::factory()->post->create( array( 'post_content' => 'A source article.' ) );
		update_post_meta( $post_id, 'summaraize_points', array( 'Keep this edited summary.' ) );
		foreach ( array( 401, 402, 403, 404, 429, 500 ) as $status ) {
			$mock = function () use ( $status ) {
				return array(
					'response' => array( 'code' => $status ),
					'body'     => '{"error":{"message":"SECRET upstream content"}}',
				);
			};
			add_filter( 'pre_http_request', $mock );
			try {
				$result = Summaraize_Summary_Manager::generate_summary_for_post( $post_id );
				$this->assertWPError( $result );
				$this->assertStringNotContainsString( 'SECRET', $result->get_error_message() );
				$this->assertSame( array( 'Keep this edited summary.' ), get_post_meta( $post_id, 'summaraize_points', true ) );
			} finally {
				remove_filter( 'pre_http_request', $mock );
			}
		}
	}

	/**
	 * Public catalog queries filter unsupported output and use a cache.
	 *
	 * @return void
	 */
	public function test_catalog_filtering_and_cache() {
		delete_transient( Summaraize_OpenRouter_Settings::MODELS_CACHE_KEY );
		$calls = 0;
		$mock  = function () use ( &$calls ) {
			++$calls;
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'data' => array(
							array(
								'id'           => 'vendor/text',
								'architecture' => array(
									'input_modalities'  => array( 'text' ),
									'output_modalities' => array( 'text' ),
								),
							),
							array(
								'id'           => 'vendor/image',
								'architecture' => array(
									'input_modalities'  => array( 'text' ),
									'output_modalities' => array( 'image' ),
								),
							),
						),
					)
				),
			);
		};
		add_filter( 'pre_http_request', $mock );
		try {
			$models = Summaraize_OpenRouter_Settings::get_models();
			$this->assertCount( 1, $models );
			$this->assertSame( 'vendor/text', $models[0]['id'] );
			$this->assertSame( $models, Summaraize_OpenRouter_Settings::get_models() );
			$this->assertSame( 1, $calls );
		} finally {
			remove_filter( 'pre_http_request', $mock );
			delete_transient( Summaraize_OpenRouter_Settings::MODELS_CACHE_KEY );
		}
	}

	/**
	 * Opening an editor keeps OpenRouter selected and registers its metabox.
	 *
	 * @return void
	 */
	public function test_editor_keeps_openrouter_provider() {
		update_option( 'summaraize_ai_provider', 'openrouter' );
		update_option( 'summaraize_openrouter_api_key', 'test-key' );
		update_option( 'summaraize_openrouter_model', 'vendor/model' );
		update_option( 'summaraize_post_types', array( 'post' ) );
		set_current_screen( 'post' );
		$metabox = new Summaraize_Admin_Metabox();
		$metabox->add_meta_box();
		$this->assertSame( 'openrouter', get_option( 'summaraize_ai_provider' ) );
		$this->assertArrayHasKey( 'summaraize_meta_box', $GLOBALS['wp_meta_boxes']['post']['side']['default'] );
		set_current_screen( 'front' );
	}

	/**
	 * Test badges require a recent successful parse for exactly this key/model.
	 *
	 * @return void
	 */
	public function test_model_badge_is_bound_to_key_model_and_age() {
		$valid = true;
		$mock  = function () use ( &$valid ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode(
					array(
						'choices' => array(
							array(
								'finish_reason' => 'stop',
								'message'       => array( 'content' => $valid ? wp_json_encode( array( 'points' => array_fill( 0, 5, array( 'text' => 'Sample fact.' ) ) ) ) : 'Not a summary' ),
							),
						),
					)
				),
			);
		};
		add_filter( 'pre_http_request', $mock );
		try {
			$this->assertSame( 'untested', Summaraize_OpenRouter_Settings::get_test_status( 'private-test-key', 'vendor/model' )['state'] );
			$result = Summaraize_OpenRouter_Settings::test_model( 'private-test-key', 'vendor/model' );
			$this->assertCount( 5, $result['points'] );
			$this->assertSame( 'passed', Summaraize_OpenRouter_Settings::get_test_status( 'private-test-key', 'vendor/model' )['state'] );
			$this->assertSame( 'untested', Summaraize_OpenRouter_Settings::get_test_status( 'different-key', 'vendor/model' )['state'] );
			$this->assertSame( 'untested', Summaraize_OpenRouter_Settings::get_test_status( 'private-test-key', 'vendor/other' )['state'] );
			$record = get_option( 'summaraize_openrouter_test' );
			$this->assertStringNotContainsString( 'private-test-key', wp_json_encode( $record ) );
			$record['time'] = time() - 8 * DAY_IN_SECONDS;
			update_option( 'summaraize_openrouter_test', $record );
			$this->assertSame( 'untested', Summaraize_OpenRouter_Settings::get_test_status( 'private-test-key', 'vendor/model' )['state'] );
			Summaraize_OpenRouter_Settings::test_model( 'private-test-key', 'vendor/model' );
			$valid = false;
			$this->assertWPError( Summaraize_OpenRouter_Settings::test_model( 'private-test-key', 'vendor/model' ) );
			$this->assertSame( 'failed', Summaraize_OpenRouter_Settings::get_test_status( 'private-test-key', 'vendor/model' )['state'] );
		} finally {
			remove_filter( 'pre_http_request', $mock );
			delete_option( 'summaraize_openrouter_test' );
		}
	}
}
