<?php
/**
 * Provider model and authentication regression tests.
 *
 * @package Summaraize
 */

defined( 'ABSPATH' ) || exit;

/** Tests provider request contracts. */
class Test_Summaraize_Provider_Updates extends WP_UnitTestCase {
	/**
	 * GPT-6 uses supported parameters and remains selectable.
	 *
	 * @return void
	 */
	public function test_gpt6_request_contract() {
		set_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY, array( 'gpt-6-astra', 'gpt-5-mini' ), HOUR_IN_SECONDS );
		$this->assertContains( 'gpt-6-astra', Summaraize_OpenAI_Settings::get_available_openai_models() );
		$this->assertSame( 'gpt-6-astra', Summaraize_OpenAI_Settings::sanitize_openai_model( 'gpt-6-astra' ) );
		$method = new ReflectionMethod( 'Summaraize_OpenAI_Settings', 'build_openai_request_payload' );
		$method->setAccessible( true );
		$payload = $method->invoke( null, 'Example content', 'gpt-6-astra' );
		$this->assertSame( 4000, $payload['max_completion_tokens'] );
		$this->assertSame( 'low', $payload['reasoning_effort'] );
		$this->assertArrayNotHasKey( 'temperature', $payload );
		$this->assertArrayNotHasKey( 'max_tokens', $payload );
		delete_transient( Summaraize_OpenAI_Settings::OPENAI_MODELS_CACHE_KEY );
	}

	/**
	 * Validation is read-only, and a different key cannot inherit success.
	 *
	 * @return void
	 */
	public function test_gemini_key_validation_is_bound_to_key() {
		delete_transient( 'summaraize_gemini_api_key_valid' );
		$calls = 0;
		$mock  = function ( $preempt, $args, $url ) use ( &$calls ) {
			++$calls;
			$this->assertSame( 'GET', $args['method'] );
			$this->assertSame( 'https://generativelanguage.googleapis.com/v1beta/models', $url );
			$valid = 'valid-key' === $args['headers']['x-goog-api-key'];
			return array(
				'response' => array( 'code' => $valid ? 200 : 400 ),
				'body'     => $valid ? '{"models":[]}' : '{"error":{"message":"API key not valid"}}',
			);
		};
		add_filter( 'pre_http_request', $mock, 10, 3 );
		try {
			$this->assertTrue( Summaraize_Google_Gemini_Settings::validate_google_gemini_api_key( 'valid-key' ) );
			$this->assertTrue( Summaraize_Google_Gemini_Settings::validate_google_gemini_api_key( 'valid-key' ) );
			$this->assertFalse( Summaraize_Google_Gemini_Settings::validate_google_gemini_api_key( 'invalid-key' ) );
			$this->assertSame( 2, $calls );
		} finally {
			remove_filter( 'pre_http_request', $mock, 10 );
			delete_transient( 'summaraize_gemini_api_key_valid' );
		}
	}

	/**
	 * Model retirement and quota failures are not authentication errors.
	 *
	 * @return void
	 */
	public function test_gemini_generation_errors() {
		foreach ( array(
			404 => 'model is unavailable',
			429 => 'quota or rate limit',
			403 => 'denied access',
		) as $status => $message ) {
			$result = Summaraize_Google_Gemini_Settings::parse_gemini_response(
				array(
					'response' => array( 'code' => $status ),
					'body'     => '{}',
				)
			);
			$this->assertWPError( $result );
			$this->assertStringContainsString( $message, $result->get_error_message() );
		}
	}

	/**
	 * Gemini uses the replacement model and keeps credentials out of URLs.
	 *
	 * @return void
	 */
	public function test_gemini_generation_contract() {
		$mock = function ( $preempt, $args, $url ) {
			$this->assertSame( 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent', $url );
			$this->assertSame( 'test-key', $args['headers']['x-goog-api-key'] );
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => wp_json_encode( array( 'candidates' => array( array( 'content' => array( 'parts' => array( array( 'text' => wp_json_encode( array( 'points' => array_fill( 0, 5, array( 'text' => 'Fact' ) ) ) ) ) ) ) ) ) ) ),
			);
		};
		add_filter( 'pre_http_request', $mock, 10, 3 );
		try {
			$this->assertCount( 5, Summaraize_Google_Gemini_Settings::make_gemini_api_request( 'test-key', 'Example' ) );
		} finally {
			remove_filter( 'pre_http_request', $mock, 10 );
		}
	}

	/**
	 * Activating an upgrade must preserve intentional existing configuration.
	 *
	 * @return void
	 */
	public function test_activation_preserves_existing_settings() {
		$settings = array(
			'summaraize_post_types'            => array(),
			'summaraize_ai_provider'           => 'google_gemini',
			'summaraize_openai_api_key'        => 'existing-openai',
			'summaraize_google_gemini_api_key' => 'existing-gemini',
			'summaraize_ai_model'              => 'gpt-5.5',
			'summaraize_widget_title'          => 'Custom takeaways',
			'summaraize_display_mode'          => 'dark',
		);
		foreach ( $settings as $option => $value ) {
			update_option( $option, $value );
		}
		require_once dirname( __DIR__ ) . '/includes/class-summaraize-activator.php';
		Summaraize_Activator::activate();
		foreach ( $settings as $option => $value ) {
			$this->assertSame( $value, get_option( $option ) );
		}
	}

	/**
	 * Provider forms must not register absent credential fields for overwriting.
	 *
	 * @return void
	 */
	public function test_settings_registration_preserves_inactive_credentials() {
		$keys = array(
			'openai'        => 'summaraize_openai_api_key',
			'google_gemini' => 'summaraize_google_gemini_api_key',
			'openrouter'    => 'summaraize_openrouter_api_key',
		);
		foreach ( $keys as $option ) {
			delete_option( $option );
		}
		foreach ( $keys as $provider => $active_key ) {
			foreach ( $keys as $option ) {
				unregister_setting( 'summaraize_settings', $option );
			}
			unregister_setting( 'summaraize_settings', 'summaraize_ai_model' );
			unregister_setting( 'summaraize_settings', 'summaraize_openrouter_model' );
			update_option( 'summaraize_ai_provider', $provider );
			$settings = new Summaraize_Admin_Settings();
			$settings->summaraize_register_settings();
			$registered = get_registered_settings();
			foreach ( $keys as $option ) {
				if ( $option === $active_key ) {
					$this->assertArrayHasKey( $option, $registered );
				} else {
					$this->assertArrayNotHasKey( $option, $registered );
				}
			}
			$this->assertSame( $provider, get_option( 'summaraize_ai_provider' ) );
		}
	}
}
