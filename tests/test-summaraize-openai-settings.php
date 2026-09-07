<?php
/**
 * Tests for Summaraize OpenAI settings helpers.
 *
 * @package Summaraize
 */

defined( 'ABSPATH' ) || exit;

/**
 * Covers OpenAI helper behaviors.
 */
class Test_Summaraize_OpenAI_Settings extends WP_UnitTestCase {

	/**
	 * Ensure transient state is reset after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		delete_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY );
		parent::tearDown();
	}

	/**
	 * Ensure sanitize falls back to default when no model is provided.
	 *
	 * @return void
	 */
	public function test_sanitize_openai_model_falls_back_when_empty() {
		$this->assertSame( Summaraize_OpenAI_Settings::OPENAI_DEFAULT_MODEL, Summaraize_OpenAI_Settings::sanitize_openai_model( '' ) );
	}

	/**
	 * Ensure an empty transient does not break validation.
	 *
	 * @return void
	 */
	public function test_sanitize_openai_model_falls_back_with_invalid_format() {
		$this->assertSame( Summaraize_OpenAI_Settings::OPENAI_DEFAULT_MODEL, Summaraize_OpenAI_Settings::sanitize_openai_model( 'model with spaces' ) );
	}

	/**
	 * Ensure cached model lists are honored and invalid values are rejected.
	 *
	 * @return void
	 */
	public function test_sanitize_openai_model_uses_cached_model_list() {
		set_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY, array( 'gpt-4o-mini', 'gpt-4o', 'gpt-5', 'gpt-5.5', 'gpt-5.5-2026-04-23' ), DAY_IN_SECONDS );

		$this->assertSame( 'gpt-5', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-5' ) );
		$this->assertSame( 'gpt-5.5', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-5.5' ) );
		$this->assertSame( 'gpt-5.5-2026-04-23', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-5.5-2026-04-23' ) );
		$this->assertSame( 'gpt-5.5', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-4.1' ) );
	}

	/**
	 * Fall back to available models when no GPT-5 family is visible.
	 *
	 * @return void
	 */
	public function test_sanitize_openai_model_uses_first_available_model_when_locked_family_missing() {
		set_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY, array( 'gpt-4o-mini', 'gpt-4o', 'text-embedding-3-small' ), DAY_IN_SECONDS );

		$this->assertSame( 'gpt-4o-mini', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-5' ) );
	}

	/**
	 * Ensure only locked OpenAI model families are accepted.
	 *
	 * @return void
	 */
	public function test_sanitize_openai_model_rejects_unknown_gpt5_versions() {
		set_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY, array( 'gpt-5', 'gpt-5-mini', 'gpt-5.5', 'gpt-5.5-2026-04-23', 'gpt-5.4', 'gpt-4o-mini' ), DAY_IN_SECONDS );

		$this->assertSame( 'gpt-5', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-5' ) );
		$this->assertSame( 'gpt-5-mini', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-5-mini' ) );
		$this->assertSame( 'gpt-5.5', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-5.5' ) );
		$this->assertSame( 'gpt-5.5-2026-04-23', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-5.5-2026-04-23' ) );
		$this->assertSame( 'gpt-5-mini', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-5.4' ) );
	}

	/**
	 * Ensure GPT-5 style models use reasoning-style request payload fields.
	 *
	 * @return void
	 */
	public function test_openai_payload_uses_completion_tokens_for_reasoning_models() {
		$reflection = new ReflectionClass( Summaraize_OpenAI_Settings::class );
		$method     = $reflection->getMethod( 'build_openai_request_payload' );
		$method->setAccessible( true );

		$payload = $method->invokeArgs( null, array( 'sample content', 'gpt-5.5' ) );

		$this->assertArrayHasKey( 'max_completion_tokens', $payload );
		$this->assertArrayNotHasKey( 'temperature', $payload );
		$this->assertSame( 4000, $payload['max_completion_tokens'] );
	}

	/**
	 * Ensure non-locked models are returned when no GPT-5 family is available.
	 *
	 * @return void
	 */
	public function test_get_available_openai_models_falls_back_to_unlocked_models() {
		set_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY, array( 'gpt-4o-mini', 'gpt-4o', 'text-embedding-3-small' ), DAY_IN_SECONDS );

		$this->assertSame(
			array( 'gpt-4o-mini', 'gpt-4o', 'text-embedding-3-small' ),
			Summaraize_OpenAI_Settings::get_available_openai_models()
		);
	}

	/**
	 * Ensure supported GPT-5.5 models are returned in plugin fallback order.
	 *
	 * @return void
	 */
	public function test_get_available_openai_models_includes_gpt55_models() {
		set_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY, array( 'gpt-5.5-2026-04-23', 'gpt-5', 'gpt-5.5', 'gpt-5-mini', 'gpt-5-nano' ), DAY_IN_SECONDS );

		$this->assertSame(
			array( 'gpt-5-mini', 'gpt-5.5', 'gpt-5.5-2026-04-23', 'gpt-5', 'gpt-5-nano' ),
			Summaraize_OpenAI_Settings::get_available_openai_models()
		);
	}

	/**
	 * Build fallback model attempts from selected model and available list.
	 *
	 * @return void
	 */
	public function test_get_openai_request_model_candidates_orders_attempts() {
		set_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY, array( 'gpt-4o-mini', 'gpt-4o' ), DAY_IN_SECONDS );

		$reflection = new ReflectionClass( Summaraize_OpenAI_Settings::class );
		$method     = $reflection->getMethod( 'get_openai_request_model_candidates' );
		$method->setAccessible( true );

		$this->assertSame(
			array( 'gpt-4o', 'gpt-4o-mini', 'gpt-5-mini' ),
			$method->invokeArgs( null, array( 'gpt-4o' ) )
		);
	}

	/**
	 * Detect model-unavailable errors from API error payloads.
	 *
	 * @return void
	 */
	public function test_is_openai_model_unavailable_error() {
		$reflection = new ReflectionClass( Summaraize_OpenAI_Settings::class );
		$method     = $reflection->getMethod( 'is_openai_model_unavailable_error' );
		$method->setAccessible( true );

		$this->assertTrue(
			$method->invokeArgs( null, array( 404, array( 'error' => array( 'code' => 'model_not_found' ) ) ) )
		);

		$this->assertTrue(
			$method->invokeArgs(
				null,
				array(
					400,
					array(
						'error' => array(
							'message' => 'The model `gpt-5` does not exist',
						),
					),
				)
			)
		);

		$this->assertFalse(
			$method->invokeArgs(
				null,
				array(
					401,
					array(
						'error' => array(
							'message' => 'Invalid API key provided',
						),
					),
				)
			)
		);
	}

	/**
	 * Map response body to user-facing OpenAI error messages.
	 *
	 * @return void
	 */
	public function test_get_openai_error_message_from_payload() {
		$reflection = new ReflectionClass( Summaraize_OpenAI_Settings::class );
		$method     = $reflection->getMethod( 'get_openai_error_message' );
		$method->setAccessible( true );

		$this->assertSame(
			'The model does not exist',
			$method->invokeArgs(
				null,
				array( array( 'error' => array( 'message' => 'The model does not exist' ) ), 400 )
			)
		);

		$this->assertSame(
			'Invalid API key or unauthorized access.',
			$method->invokeArgs(
				null,
				array( array(), 401 )
			)
		);
	}

	/**
	 * Ensure debug response bodies are sanitized before rendering.
	 *
	 * @return void
	 */
	public function test_sanitize_openai_debug_response_body_strips_markup() {
		$reflection = new ReflectionClass( Summaraize_OpenAI_Settings::class );
		$method     = $reflection->getMethod( 'sanitize_openai_debug_response_body' );
		$method->setAccessible( true );

		$payload = $method->invokeArgs(
			null,
			array( '<script>alert("x")</script> {"error":"bad"}' )
		);

		$this->assertSame( '{"error":"bad"}', $payload );
	}

	/**
	 * Ensure debug payloads include diagnostic metadata only when debug is enabled.
	 *
	 * @return void
	 */
	public function test_openai_request_error_payload_includes_debug_when_requested() {
		$reflection = new ReflectionClass( Summaraize_OpenAI_Settings::class );
		$method     = $reflection->getMethod( 'build_openai_request_error_payload' );
		$method->setAccessible( true );

		$plain_payload = $method->invokeArgs(
			null,
			array(
				'Failure payload',
				'gpt-4o',
				array( 'gpt-4o', 'gpt-4o-mini' ),
				array(),
				'gpt-4o',
				400,
				'{"error":"bad"}',
				array( 'error' => array( 'message' => 'bad' ) ),
				false,
			)
		);

		$this->assertSame( array( 'message' => 'Failure payload' ), $plain_payload );

		$debug_payload = $method->invokeArgs(
			null,
			array(
				'Failure payload',
				'gpt-4o',
				array( 'gpt-4o', 'gpt-4o-mini' ),
				array(
					array(
						'model'       => 'gpt-4o',
						'http_status' => 400,
					),
				),
				'gpt-4o',
				400,
				'{"error":"bad"}',
				array( 'error' => array( 'message' => 'bad' ) ),
				true,
			)
		);

		$this->assertSame( 'Failure payload', $debug_payload['message'] );
		$this->assertSame( 'gpt-4o', $debug_payload['debug_selected_model'] );
		$this->assertSame( array( 'gpt-4o', 'gpt-4o-mini' ), $debug_payload['debug_model_attempts'] );
		$this->assertSame( 400, $debug_payload['debug_last_status'] );
		$this->assertSame( '{"error":"bad"}', $debug_payload['debug_response'] );
		$this->assertSame( array( 'error' => array( 'message' => 'bad' ) ), $debug_payload['debug_response_data'] );
		$this->assertSame(
			array(
				array(
					'model'       => 'gpt-4o',
					'http_status' => 400,
				),
			),
			$debug_payload['debug_attempts']
		);
	}

	/**
	 * Ensure fenced completion text is parsed into JSON.
	 *
	 * @return void
	 */
	public function test_decode_openai_completion_response_strips_markdown_fences() {
		$reflection = new ReflectionClass( Summaraize_OpenAI_Settings::class );
		$method     = $reflection->getMethod( 'decode_openai_completion_response' );
		$method->setAccessible( true );

		$payload = $method->invokeArgs( null, array( "```json\n{\n  \"points\": [\n    {\"index\": 1, \"text\": \"Point 1\"}\n  ]\n}\n```" ) );

		$this->assertIsArray( $payload );
		$this->assertArrayHasKey( 'points', $payload );
		$this->assertCount( 1, $payload['points'] );
	}

	/**
	 * Ensure noisy completion text still decodes.
	 *
	 * @return void
	 */
	public function test_decode_openai_completion_response_handles_wrapped_payloads() {
		$reflection = new ReflectionClass( Summaraize_OpenAI_Settings::class );
		$method     = $reflection->getMethod( 'decode_openai_completion_response' );
		$method->setAccessible( true );

		$payload = $method->invokeArgs( null, array( "Sure, here is the JSON:\n{\"points\":[{\"index\":1,\"text\":\"A\"}]}" ) );

		$this->assertIsArray( $payload );
		$this->assertSame( 'A', $payload['points'][0]['text'] );
	}

	/**
	 * Ensure text-only responses are still extracted into points when possible.
	 *
	 * @return void
	 */
	public function test_extract_points_from_openai_completion_handles_bulleted_output() {
		$reflection = new ReflectionClass( Summaraize_OpenAI_Settings::class );
		$method     = $reflection->getMethod( 'extract_points_from_openai_completion' );
		$method->setAccessible( true );

		$result = $method->invokeArgs(
			null,
			array(
				"1. First important point\n2) Second important point\n- Third point from dash\n* Fourth with bullet",
			)
		);

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'points', $result );
		$this->assertCount( 4, $result['points'] );
		$this->assertSame( 'First important point', $result['points'][0]['text'] );
		$this->assertSame( 4, $result['points'][3]['index'] );
	}

	/**
	 * Ensure nested JSON objects in noisy text are extracted.
	 *
	 * @return void
	 */
	public function test_decode_openai_completion_response_extracts_nested_json() {
		$reflection = new ReflectionClass( Summaraize_OpenAI_Settings::class );
		$method     = $reflection->getMethod( 'decode_openai_completion_response' );
		$method->setAccessible( true );

		$payload = $method->invokeArgs(
			null,
			array(
				"Leading text\n```\n{\n  \"points\": [\n    {\n      \"index\": 1,\n      \"text\": \"Top one\"\n    },\n    {\n      \"index\": 2,\n      \"text\": \"Top two\"\n    }\n  ]\n}\n```\nTrailing text",
			)
		);

		$this->assertIsArray( $payload );
		$this->assertCount( 2, $payload['points'] );
	}
}
