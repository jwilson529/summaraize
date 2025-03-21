<?php
/**
 * OpenAI Settings for Summaraize.
 *
 * This file contains the class definition for handling the admin settings page
 * and request processing for the Summaraize plugin using OpenAI.
 *
 * It includes methods to render settings fields, process OpenAI requests, and
 * validate the OpenAI API key.
 *
 * @package Summaraize
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Summaraize_OpenAI_Settings
 *
 * Manages the admin settings page and request handling for the Summaraize plugin.
 *
 * @since 1.0.0
 */
class Summaraize_OpenAI_Settings extends Summaraize_Admin_Settings {

	/**
	 * Callback for the AI Model setting field.
	 *
	 * Displays an input field for specifying the AI model. If an API key is set,
	 * the field is shown; otherwise, a warning is displayed.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function summaraize_ai_model_callback() {
		$selected_model = get_option( 'summaraize_ai_model', 'gpt-4o-mini-2024-07-18' );
		$api_key        = get_option( 'summaraize_openai_api_key' );

		if ( ! empty( $api_key ) ) {
			// Optionally, you could fetch available models here.
			echo '<input type="text" id="summaraize_ai_model" name="summaraize_ai_model" value="' . esc_attr( $selected_model ) . '" />';
			echo '<p class="description">' . esc_html__( 'Enter the AI model you want to use.', 'summaraize' ) . '</p>';
		} else {
			echo '<p class="summaraize-alert">';
			esc_html_e( 'Please enter a valid OpenAI API key first.', 'summaraize' );
			echo '</p>';
		}
	}

	/**
	 * Callback for the OpenAI API key field.
	 *
	 * Displays an input field for the OpenAI API key.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function summaraize_openai_api_key_callback() {
		$ai_provider = get_option( 'summaraize_ai_provider', 'openai' );
		if ( 'openai' === $ai_provider ) {
			$value = get_option( 'summaraize_openai_api_key', '' );
			echo '<input type="password" name="summaraize_openai_api_key" value="' . esc_attr( $value ) . '" />';
			echo '<p class="description">' .
				wp_kses_post( __( 'Get your OpenAI API Key <a href="https://beta.openai.com/signup/">here</a>.', 'summaraize' ) ) .
				'</p>';
		}
	}

	/**
	 * Register the advanced settings fields.
	 *
	 * Registers fields for AI Model and OpenAI API key in an advanced settings section.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_summaraize_advanced_settings_fields() {
		// Register AI model setting.
		register_setting(
			'summaraize_settings_advanced',
			'summaraize_ai_model',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		add_settings_field(
			'summaraize_ai_model',
			__( 'AI Model', 'summaraize' ),
			array( $this, 'summaraize_ai_model_callback' ),
			'summaraize_settings_advanced',
			'summaraize_advanced_settings_section'
		);

		// Register OpenAI API key setting.
		register_setting(
			'summaraize_settings_advanced',
			'summaraize_openai_api_key',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		add_settings_field(
			'summaraize_openai_api_key',
			__( 'OpenAI API Key', 'summaraize' ),
			array( $this, 'summaraize_openai_api_key_callback' ),
			'summaraize_settings_advanced',
			'summaraize_advanced_settings_section'
		);
	}

	/**
	 * Process the OpenAI request using a simplified chat completion call.
	 *
	 * Sends a simple chat completion call directly to OpenAI and returns
	 * the extracted key points.
	 *
	 * @since 1.0.0
	 * @param string $query The content to summarize.
	 * @return void Outputs a JSON response with the extracted key points or an error.
	 */
	public static function process_openai_request( $query ) {
		// Retrieve API key and model from settings.
		$api_key        = get_option( 'summaraize_openai_api_key' );
		$selected_model = get_option( 'summaraize_ai_model', 'gpt-4o-mini-2024-07-18' );

		// Verify that the API key is set.
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'API key is not configured.', 'summaraize' ) ) );
			return;
		}

		// Build the payload for the chat completion request.
		$payload = array(
			'model'       => $selected_model,
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => 'Analyze the provided content and extract the top 5 key points. Return ONLY the key points in a JSON object with a key "points". Each point should be an object with "index" (1-5) and "text".',
				),
				array(
					'role'    => 'user',
					'content' => $query,
				),
			),
			'temperature' => 0.7,
			'max_tokens'  => 500,
		);

		// Define the OpenAI Chat Completions endpoint.
		$openai_url = 'https://api.openai.com/v1/chat/completions';

		// Set up the HTTP request arguments.
		$args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 60,
		);

		// Send the request.
		$response = wp_remote_post( $openai_url, $args );

		// Check for errors in the response.
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
			return;
		}

		// Retrieve and decode the response body.
		$response_body = wp_remote_retrieve_body( $response );
		$result        = json_decode( $response_body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => __( 'Failed to decode response.', 'summaraize' ) ) );
			return;
		}

		// Ensure we have choices and extract the completion.
		if ( isset( $result['choices'] ) && ! empty( $result['choices'] ) ) {
			$completion = $result['choices'][0]['message']['content'];

			// Remove any markdown code fences from the completion.
			$completion = preg_replace( '/^```(json)?\s*|\s*```$/', '', $completion );
			$completion = trim( $completion );

			// Attempt to decode the completion as JSON.
			$json_result = json_decode( $completion, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				wp_send_json_error( array( 'message' => __( 'Failed to decode completion response.', 'summaraize' ) ) );
				return;
			}

			if ( isset( $json_result['points'] ) && is_array( $json_result['points'] ) ) {
				wp_send_json_success( array( 'points' => $json_result['points'] ) );
				return;
			} else {
				wp_send_json_error( array( 'message' => __( 'No key points extracted.', 'summaraize' ) ) );
				return;
			}
		} else {
			wp_send_json_error( array( 'message' => __( 'Unexpected response structure.', 'summaraize' ) ) );
			return;
		}
	}

	/**
	 * Validates the OpenAI API key and fetches models that support function calling.
	 *
	 * @since 1.0.0
	 * @param string $api_key The API key to validate.
	 * @return array|bool List of model IDs if successful, false otherwise.
	 */
	public static function validate_openai_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return false;
		}

		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		$function_calling_cutoff          = 1686614400;
		$parallel_function_calling_cutoff = 1699228800;

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$models = array_filter(
				$data['data'],
				function ( $model ) use ( $function_calling_cutoff, $parallel_function_calling_cutoff ) {
					return isset( $model['created'] ) && (
						$model['created'] >= $function_calling_cutoff ||
						$model['created'] >= $parallel_function_calling_cutoff
					);
				}
			);

			if ( ! empty( $models ) ) {
				$model_ids = array_map(
					function ( $model ) {
						return $model['id'];
					},
					$models
				);

				// Cache the models in a transient for 24 hours.
				set_transient( 'summaraize_openai_models', $model_ids, DAY_IN_SECONDS );

				return $model_ids;
			}
		}

		return false;
	}

	/**
	 * Handles AJAX request to validate the OpenAI API key.
	 *
	 * Checks nonce, validates that the API key is provided, and verifies the key
	 * by attempting to fetch the models.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function summaraize_ajax_validate_openai_api_key() {
		if ( ! check_ajax_referer( 'summaraize_ajax_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid nonce', 'summaraize' ),
				)
			);
		}

		if ( ! isset( $_POST['api_key'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'API key is missing.', 'summaraize' ),
				)
			);
		}

		$api_key = sanitize_text_field( wp_unslash( $_POST['api_key'] ) );

		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid API key or unable to reach OpenAI.', 'summaraize' ),
				)
			);
			return;
		}

		wp_send_json_success(
			array(
				'message' => __( 'API key is valid.', 'summaraize' ),
			)
		);
	}
}
