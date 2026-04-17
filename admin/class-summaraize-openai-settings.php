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

defined( 'ABSPATH' ) || exit;

/**
 * Class Summaraize_OpenAI_Settings
 *
 * Manages the admin settings page and request handling for the Summaraize plugin.
 *
 * @since 1.0.0
 */
class Summaraize_OpenAI_Settings extends Summaraize_Admin_Settings {

	/**
	 * OpenAI API endpoints.
	 */
	const OPENAI_CHAT_COMPLETIONS_ENDPOINT   = 'https://api.openai.com/v1/chat/completions';
	const OPENAI_MODELS_ENDPOINT             = 'https://api.openai.com/v1/models';
	const OPENAI_DEFAULT_MODEL               = 'gpt-5-mini';
	const OPENAI_MODELS_CACHE_KEY            = 'summaraize_openai_models';
	const OPENAI_LOCKED_MODELS               = array(
		'gpt-5-mini',
		'gpt-5',
		'gpt-5-nano',
	);
	const OPENAI_MAX_MODEL_FALLBACK_ATTEMPTS = 5;

	/**
	 * OpenAI reasoning model pattern for GPT-5 and O-series family.
	 *
	 * These models require completion token limits and do not accept all legacy request
	 * parameters used by earlier chat-completion models.
	 */
	const OPENAI_REASONING_MODEL_PREFIX = 'gpt-5';

	/**
	 * Render the AI model dropdown.
	 *
	 * Shows available models when an OpenAI key is configured, with a fallback text
	 * input when the key is missing.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function summaraize_ai_model_callback() {
		$selected_model = get_option( 'summaraize_ai_model', self::OPENAI_DEFAULT_MODEL );
		$selected_model = self::sanitize_openai_model( $selected_model );
		$api_key        = get_option( 'summaraize_openai_api_key' );
		$models         = self::get_available_openai_models();

		if ( ! empty( $api_key ) && ! empty( $models ) ) {
			echo '<select id="summaraize_ai_model" name="summaraize_ai_model">';
			foreach ( $models as $model ) {
				printf(
					'<option value="%1$s" %2$s>%1$s</option>',
					esc_attr( $model ),
					selected( $selected_model, $model, false )
				);
			}

			if ( '' !== $selected_model && ! in_array( $selected_model, $models, true ) ) {
				echo '<option value="' . esc_attr( $selected_model ) . '" selected>' . esc_html( $selected_model ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<input type="text" id="summaraize_ai_model" name="summaraize_ai_model" value="' . esc_attr( $selected_model ) . '" />';
		}

		echo '<p class="description">';
		esc_html_e( 'Select the OpenAI model used for summarization.', 'summaraize' );
		echo ' ';
		esc_html_e( 'GPT-5 is the latest OpenAI family; use `gpt-5` for the latest behavior, or `gpt-5-mini`/`gpt-5-nano` to reduce cost.', 'summaraize' );
		echo '</p>';
	}

	/**
	 * Return the default OpenAI model when none is available.
	 *
	 * @since 1.0.0
	 * @param string $model The model input.
	 * @return string
	 */
	public static function sanitize_openai_model( $model ) {
		$model = is_string( $model ) ? sanitize_text_field( $model ) : '';

		if ( '' === trim( $model ) ) {
			return self::get_fallback_openai_model();
		}

		if ( ! preg_match( '/^[a-zA-Z0-9._:-]+$/', $model ) ) {
			return self::get_fallback_openai_model();
		}

		$model = trim( $model );

		$available_models = get_transient( self::OPENAI_MODELS_CACHE_KEY );
		$available_models = is_array( $available_models ) ? array_values( array_unique( array_filter( array_map( 'sanitize_text_field', array_values( $available_models ) ) ) ) ) : array();

		$known_models = self::get_locked_openai_models( $available_models );

		if ( ! empty( $available_models ) ) {
			if ( in_array( $model, $known_models, true ) ) {
				return $model;
			}

			return self::get_fallback_openai_model( $known_models, $available_models );
		}

		if ( in_array( $model, self::OPENAI_LOCKED_MODELS, true ) ) {
			return $model;
		}

		return self::get_fallback_openai_model( $known_models, $available_models );
	}

	/**
	 * Return a fallback model based on current known models.
	 *
	 * @since 1.2.7
	 * @param array $known_models A filtered list of known models available to the account.
	 * @param array $available_models A filtered list of all available models.
	 * @return string
	 */
	private static function get_fallback_openai_model( $known_models = array(), $available_models = array() ) {
		$known_models     = is_array( $known_models ) ? array_filter( array_map( 'sanitize_text_field', $known_models ) ) : array();
		$available_models = is_array( $available_models ) ? array_filter( array_map( 'sanitize_text_field', $available_models ) ) : array();

		if ( ! empty( $known_models ) ) {
			foreach ( self::OPENAI_LOCKED_MODELS as $candidate ) {
				if ( in_array( $candidate, $known_models, true ) ) {
					return $candidate;
				}
			}
		}

		if ( ! empty( $available_models ) ) {
			return $available_models[0];
		}

		return self::OPENAI_DEFAULT_MODEL;
	}

	/**
	 * Filter model lists to the locked GPT-5 family options supported by the plugin.
	 *
	 * @since 1.2.7
	 * @param array|mixed $available_models Model IDs from cache or API.
	 * @return array
	 */
	private static function get_locked_openai_models( $available_models ) {
		if ( ! is_array( $available_models ) || empty( $available_models ) ) {
			return array();
		}

		$clean_models = array_filter( array_map( 'sanitize_text_field', array_values( $available_models ) ), 'is_string' );
		$models       = array_values( array_unique( $clean_models ) );
		$allowed      = array();

		foreach ( self::OPENAI_LOCKED_MODELS as $model ) {
			if ( in_array( $model, $models, true ) ) {
				$allowed[] = $model;
			}
		}

		return $allowed;
	}

	/**
	 * Return available OpenAI models from cache or API lookup.
	 *
	 * @since 1.0.0
	 * @return string[]
	 */
	public static function get_available_openai_models() {
		$cached_models = get_transient( self::OPENAI_MODELS_CACHE_KEY );
		if ( is_array( $cached_models ) && ! empty( $cached_models ) ) {
			$clean_models  = array_values(
				array_unique(
					array_filter( array_map( 'sanitize_text_field', array_values( $cached_models ) ) )
				)
			);
			$locked_models = self::get_locked_openai_models( $clean_models );

			if ( ! empty( $locked_models ) ) {
				return $locked_models;
			}

			return $clean_models;
		}

		$api_key = get_option( 'summaraize_openai_api_key' );
		if ( empty( $api_key ) ) {
			return array();
		}

		$models = self::validate_openai_api_key( $api_key );
		if ( is_array( $models ) ) {
			$clean_models  = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', $models ) ) ) );
			$locked_models = self::get_locked_openai_models( $clean_models );

			if ( ! empty( $locked_models ) ) {
				return $locked_models;
			}

			if ( ! empty( $clean_models ) ) {
				return $clean_models;
			}
		}

		return array();
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
				'sanitize_callback' => array( __CLASS__, 'sanitize_openai_model' ),
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
	 * @param string $debug_request Raw debug flag passed from the request.
	 * @return void Outputs a JSON response with the extracted key points or an error.
	 */
	public static function process_openai_request( $query, $debug_request = '' ) {
		// Retrieve API key and model from settings.
		$api_key        = get_option( 'summaraize_openai_api_key' );
		$selected_model = self::sanitize_openai_model( get_option( 'summaraize_ai_model', self::OPENAI_DEFAULT_MODEL ) );
		$model_attempts = self::get_openai_request_model_candidates( $selected_model );
		$debug_request  = is_string( $debug_request ) ? sanitize_text_field( $debug_request ) : '';
		$debug_enabled  = self::is_openai_request_debug_enabled( $debug_request );
		$debug_attempts = array();

		// Verify that the API key is set.
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'API key is not configured.', 'summaraize' ) ) );
			return;
		}

		// Define the OpenAI Chat Completions endpoint.
		$openai_url  = self::OPENAI_CHAT_COMPLETIONS_ENDPOINT;
		$last_error  = __( 'OpenAI API request failed.', 'summaraize' );
		$last_status = 0;
		$last_model  = $selected_model;
		$last_body   = '';
		$last_data   = array();

		$total_attempts = count( $model_attempts );
		foreach ( $model_attempts as $attempt_index => $model ) {
			$payload    = self::build_openai_request_payload( $query, $model );
			$response   = self::send_openai_request_with_fallback( $openai_url, $payload, $model );
			$last_model = $model;

			if ( is_wp_error( $response ) ) {
				$last_error = $response->get_error_message();

				if ( $debug_enabled ) {
					$debug_attempts[] = self::build_openai_attempt_debug_payload(
						$model,
						0,
						'',
						array(),
						$last_error
					);
				}

				break;
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$last_status   = $response_code;
			$last_body     = wp_remote_retrieve_body( $response );

			if ( $response_code >= 200 && $response_code < 300 ) {
				$result = json_decode( $last_body, true );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					$last_error = __( 'Failed to decode response.', 'summaraize' );

					if ( $debug_enabled ) {
						$debug_attempts[] = self::build_openai_attempt_debug_payload(
							$model,
							$response_code,
							$last_body,
							array(),
							$last_error
						);
					}

					continue;
				}

				// Ensure we have choices and extract the completion.
				if ( isset( $result['choices'] ) && ! empty( $result['choices'] ) ) {
					if ( ! isset( $result['choices'][0]['message']['content'] ) || ! is_string( $result['choices'][0]['message']['content'] ) ) {
						$last_error = __( 'Unexpected response structure.', 'summaraize' );

						if ( $debug_enabled ) {
							$debug_attempts[] = self::build_openai_attempt_debug_payload(
								$model,
								$response_code,
								$last_body,
								$result,
								$last_error
							);
						}

						wp_send_json_error(
							self::build_openai_request_error_payload(
								$last_error,
								$selected_model,
								$model_attempts,
								$debug_attempts,
								$last_model,
								$last_status,
								$last_body,
								$result,
								$debug_enabled
							)
						);
						return;
					}

					$completion = $result['choices'][0]['message']['content'];

					$json_result = self::extract_points_from_openai_completion( $completion );
					if ( ! is_array( $json_result ) ) {
						$last_error = __( 'Failed to decode completion response.', 'summaraize' );

						if ( $debug_enabled ) {
							$debug_attempts[] = self::build_openai_attempt_debug_payload(
								$model,
								$response_code,
								$last_body,
								$result,
								$last_error
							);
						}

						continue;
					}

					if ( isset( $json_result['points'] ) && is_array( $json_result['points'] ) ) {
						wp_send_json_success( array( 'points' => $json_result['points'] ) );
						return;
					}

					$last_error = __( 'No key points extracted.', 'summaraize' );

					if ( $debug_enabled ) {
						$debug_attempts[] = self::build_openai_attempt_debug_payload(
							$model,
							$response_code,
							$last_body,
							$result,
							$last_error
						);
					}

					continue;
				}

				$last_error = __( 'Unexpected response structure.', 'summaraize' );

				if ( $debug_enabled ) {
					$debug_attempts[] = self::build_openai_attempt_debug_payload(
						$model,
						$response_code,
						$last_body,
						$result,
						$last_error
					);
				}

				continue;
			}

			$decoded_body = self::decode_openai_error_response( $last_body );
			$last_data    = $decoded_body;
			$last_error   = self::get_openai_error_message( $decoded_body, $response_code );

			if ( $debug_enabled ) {
				$debug_attempts[] = self::build_openai_attempt_debug_payload( $model, $response_code, $last_body, $decoded_body, $last_error );
			}

			if ( ! self::is_openai_model_unavailable_error( $response_code, $decoded_body ) ) {
				break;
			}

			if ( ( $attempt_index + 1 ) >= $total_attempts ) {
				break;
			}

			Summaraize_Logger::warning(
				'OpenAI model unavailable, retrying fallback model.',
				array(
					'failed_model'  => $model,
					'http_status'   => $last_status,
					'error_message' => $last_error,
				)
			);
		}

		Summaraize_Logger::warning(
			'OpenAI chat completion request failed.',
			array(
				'selected_model' => $selected_model,
				'attempted'      => $model_attempts,
				'final_model'    => $last_model,
				'http_status'    => $last_status,
				'error_message'  => $last_error,
			)
		);

		wp_send_json_error(
			self::build_openai_request_error_payload(
				$last_error,
				$selected_model,
				$model_attempts,
				$debug_attempts,
				$last_model,
				$last_status,
				$last_body,
				$last_data,
				$debug_enabled
			)
		);
	}

	/**
	 * Build a consistent OpenAI debug response payload.
	 *
	 * Includes request metadata only when debug output is explicitly enabled.
	 *
	 * @since 1.2.9
	 * @param string $message Error message to return to the client.
	 * @param string $selected_model Selected model in settings.
	 * @param array  $model_attempts Models attempted during this request.
	 * @param array  $attempt_debug_payloads Per-attempt debug details.
	 * @param string $last_model Final model attempted.
	 * @param int    $last_status Final HTTP status code observed.
	 * @param string $last_response_body Last raw response body.
	 * @param array  $last_response_data Last decoded response data.
	 * @param bool   $debug_enabled If true, include debug payloads in the response.
	 * @return array
	 */
	private static function build_openai_request_error_payload( $message, $selected_model, $model_attempts, $attempt_debug_payloads, $last_model, $last_status, $last_response_body = '', $last_response_data = array(), $debug_enabled = null ) {
		$payload = array(
			'message' => is_string( $message ) ? sanitize_text_field( $message ) : __( 'OpenAI API request failed.', 'summaraize' ),
		);

		if ( ! is_bool( $debug_enabled ) ) {
			$debug_enabled = self::is_openai_request_debug_enabled();
		}

		if ( ! $debug_enabled ) {
			return $payload;
		}

		$clean_model_attempts = array();
		foreach ( (array) $model_attempts as $attempt_model ) {
			if ( is_string( $attempt_model ) ) {
				$clean_model_attempts[] = sanitize_text_field( $attempt_model );
			}
		}

		$payload['debug_selected_model'] = is_string( $selected_model ) ? sanitize_text_field( $selected_model ) : '';
		$payload['debug_model_attempts'] = array_values( array_unique( array_filter( $clean_model_attempts ) ) );
		$payload['debug_attempts']       = is_array( $attempt_debug_payloads ) ? $attempt_debug_payloads : array();
		$payload['debug_last_model']     = is_string( $last_model ) ? sanitize_text_field( $last_model ) : '';
		$payload['debug_last_status']    = (int) $last_status;

		$response_body = self::sanitize_openai_debug_response_body( $last_response_body );
		if ( '' !== $response_body ) {
			$payload['debug_response'] = $response_body;
		}

		if ( is_array( $last_response_data ) && ! empty( $last_response_data ) ) {
			$payload['debug_response_data'] = $last_response_data;
		}

		return $payload;
	}

	/**
	 * Build debug metadata for a single request attempt.
	 *
	 * @since 1.2.9
	 * @param string $model         Attempted model.
	 * @param int    $response_code HTTP status code.
	 * @param string $response_body Raw response body.
	 * @param array  $response_data Decoded response body.
	 * @param string $error_message Error message for the attempt.
	 * @return array
	 */
	private static function build_openai_attempt_debug_payload( $model, $response_code, $response_body = '', $response_data = array(), $error_message = '' ) {
		$payload = array(
			'model'       => is_string( $model ) ? sanitize_text_field( $model ) : '',
			'http_status' => (int) $response_code,
		);

		if ( is_string( $error_message ) ) {
			$error_message = trim( sanitize_text_field( $error_message ) );
			if ( '' !== $error_message ) {
				$payload['error_message'] = $error_message;
			}
		}

		$sanitized_body = self::sanitize_openai_debug_response_body( $response_body );
		if ( '' !== $sanitized_body ) {
			$payload['response_body'] = $sanitized_body;
		}

		if ( is_array( $response_data ) && ! empty( $response_data ) ) {
			$payload['response_json'] = $response_data;
		}

		return $payload;
	}

	/**
	 * Determine whether debug information should be returned to the client.
	 *
	 * Debug is enabled when running with WP_DEBUG, when a valid debug flag is
	 * provided in the request, or for administrator users.
	 *
	 * @since 1.2.9
	 * @param string $debug_request Optional debug flag from the request.
	 * @return bool
	 */
	private static function is_openai_request_debug_enabled( $debug_request = '' ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}

		if ( is_string( $debug_request ) ) {
			$debug_request = strtolower( trim( $debug_request ) );
			if ( '1' === $debug_request || 'true' === $debug_request ) {
				return true;
			}
		}

		return is_admin() && current_user_can( 'manage_options' );
	}

	/**
	 * Sanitize OpenAI response content for safe UI rendering.
	 *
	 * @since 1.2.9
	 * @param string $response_body The raw response body.
	 * @return string
	 */
	private static function sanitize_openai_debug_response_body( $response_body ) {
		if ( ! is_string( $response_body ) ) {
			return '';
		}

		$response_body = trim( $response_body );
		if ( '' === $response_body ) {
			return '';
		}

		$response_body = (string) preg_replace( '/<script\b[^>]*>.*?<\/script\s*>/is', '', $response_body );
		$response_body = (string) preg_replace( '/<style\b[^>]*>.*?<\/style\s*>/is', '', $response_body );

		return trim( wp_strip_all_tags( $response_body ) );
	}

	/**
	 * Detects OpenAI reasoning-like models that use different request tuning.
	 *
	 * @since 1.2.7
	 * @param string $model The model name.
	 * @return bool
	 */
	private static function is_openai_reasoning_model( $model ) {
		if ( ! is_string( $model ) ) {
			return false;
		}

		$model = strtolower( trim( $model ) );

		if ( '' === $model ) {
			return false;
		}

		if ( 0 === strpos( $model, self::OPENAI_REASONING_MODEL_PREFIX ) ) {
			return true;
		}

		return (bool) preg_match( '/^o\d+/', $model );
	}

	/**
	 * Build the OpenAI payload for a request.
	 *
	 * GPT-5 and O-series models use max_completion_tokens and do not support the
	 * full legacy temperature/max_tokens pair used for earlier chat models.
	 *
	 * @since 1.2.7
	 * @param string $query The content to summarize.
	 * @param string $model The selected model.
	 * @return array
	 */
	private static function build_openai_request_payload( $query, $model ) {
		$payload = array(
			'model'    => $model,
			'messages' => array(
				array(
					'role'    => 'system',
					'content' => 'Analyze the provided content, detect its primary language, and extract the top 5 key points in that same language (do NOT translate). Return ONLY the key points in a JSON object with a key "points". Each point should be an object with "index" (1-5) and "text".',
				),
				array(
					'role'    => 'user',
					'content' => $query,
				),
			),
		);

		if ( self::is_openai_reasoning_model( $model ) ) {
			$payload['max_completion_tokens'] = 4000;
		} else {
			$payload['temperature'] = 0.7;
			$payload['max_tokens']  = 2000;
		}

		$payload['response_format'] = array(
			'type' => 'json_object',
		);

		return $payload;
	}

	/**
	 * Decode and sanitize the model completion payload.
	 *
	 * Handles code-fenced JSON, extra whitespace, and plain text around the
	 * JSON payload.
	 *
	 * @since 1.2.8
	 * @param string $completion The content returned by the model.
	 * @return array|false Decoded JSON object or false on failure.
	 */
	private static function extract_points_from_openai_completion( $completion ) {
		$result = self::decode_openai_completion_response( $completion );
		if ( is_array( $result ) && isset( $result['points'] ) && is_array( $result['points'] ) ) {
			return $result;
		}

		$points = self::extract_points_from_text( $completion );
		if ( ! empty( $points ) ) {
			return array( 'points' => $points );
		}

		return false;
	}

	/**
	 * Decode and sanitize the model completion payload.
	 *
	 * Handles code-fenced JSON, extra whitespace, and plain text around the
	 * JSON payload.
	 *
	 * @since 1.2.8
	 * @param string $completion The content returned by the model.
	 * @return array|false Decoded JSON object or false on failure.
	 */
	private static function decode_openai_completion_response( $completion ) {
		if ( ! is_string( $completion ) ) {
			return false;
		}

		$completion = trim( $completion );
		if ( '' === $completion ) {
			return false;
		}

		$decoded = json_decode( $completion, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decoded;
		}

		$completion = preg_replace( '/^```(?:json)?\s*/i', '', $completion );
		$completion = preg_replace( '/\s*```$/', '', $completion );

		$decoded = json_decode( $completion, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decoded;
		}

		foreach ( self::extract_json_payload_candidates( $completion ) as $json_candidate ) {
			$decoded = json_decode( $json_candidate, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $decoded;
			}
		}

		return false;
	}

	/**
	 * Extract JSON payload candidates from a noisy text blob.
	 *
	 * @since 1.2.8
	 * @param string $content Raw completion content.
	 * @return string[]
	 */
	private static function extract_json_payload_candidates( $content ) {
		if ( ! is_string( $content ) ) {
			return array();
		}

		$content     = strval( $content );
		$length      = strlen( $content );
		$candidates  = array();
		$depth       = 0;
		$in_string   = false;
		$start       = -1;
		$was_escaped = false;

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $content[ $i ];

			if ( $in_string ) {
				if ( $was_escaped ) {
					$was_escaped = false;
					continue;
				}

				if ( '\\' === $char ) {
					$was_escaped = true;
					continue;
				}

				if ( '"' === $char ) {
					$in_string = false;
				}

				continue;
			}

			if ( '"' === $char ) {
				$in_string = true;
				continue;
			}

			if ( '{' === $char || '[' === $char ) {
				if ( 0 === $depth ) {
					$start = $i;
				}

				++$depth;
				continue;
			}

			if ( '}' === $char || ']' === $char ) {
				if ( $depth > 0 ) {
					--$depth;
					if ( 0 === $depth && $start >= 0 ) {
						$candidates[] = trim( substr( $content, $start, $i - $start + 1 ) );
						$start        = -1;
					}
				}
			}
		}

		return array_values( array_unique( array_filter( array_map( 'trim', $candidates ) ) ) );
	}

	/**
	 * Extract numbered/bulleted key points from non-JSON responses.
	 *
	 * @since 1.2.8
	 * @param string $completion The content returned by the model.
	 * @return array<int, array<string, mixed>>
	 */
	private static function extract_points_from_text( $completion ) {
		if ( ! is_string( $completion ) ) {
			return array();
		}

		$lines = preg_split( '/\R/', trim( $completion ) );
		if ( false === $lines ) {
			return array();
		}

		$points   = array();
		$index    = 1;
		$patterns = array(
			'/^\s*[-*•]\s*(.+)$/u',
			'/^\s*\d+[\.|)]\s*(.+)$/u',
		);

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}

			$matched = false;
			foreach ( $patterns as $pattern ) {
				if ( preg_match( $pattern, $line, $match ) ) {
					$points[] = array(
						'index' => $index,
						'text'  => sanitize_text_field( $match[1] ),
					);
					++$index;
					$matched = true;
					break;
				}
			}

			if ( ! $matched && 0 === strpos( $line, '{' ) ) {
				continue;
			}
		}

		return $points;
	}

	/**
	 * Build a short model fallback chain for request retries.
	 *
	 * @since 1.2.8
	 * @param string $selected_model The user-selected model.
	 * @return string[]
	 */
	private static function get_openai_request_model_candidates( $selected_model ) {
		$selected_model   = is_string( $selected_model ) ? sanitize_text_field( trim( $selected_model ) ) : '';
		$candidates       = array();
		$available_models = self::get_available_openai_models();
		$locked_models    = self::get_locked_openai_models( $available_models );

		if ( '' !== $selected_model ) {
			$candidates[] = $selected_model;
		}

		if ( ! empty( $locked_models ) ) {
			foreach ( self::OPENAI_LOCKED_MODELS as $locked_model ) {
				if ( in_array( $locked_model, $locked_models, true ) && ! in_array( $locked_model, $candidates, true ) ) {
					$candidates[] = $locked_model;
				}
			}
		}

		foreach ( $available_models as $available_model ) {
			if ( '' === $available_model || ! is_string( $available_model ) ) {
				continue;
			}

			if ( ! in_array( $available_model, $candidates, true ) ) {
				$candidates[] = sanitize_text_field( $available_model );
			}
		}

		if ( '' !== self::OPENAI_DEFAULT_MODEL && ! in_array( self::OPENAI_DEFAULT_MODEL, $candidates, true ) ) {
			$candidates[] = self::OPENAI_DEFAULT_MODEL;
		}

		$candidates = array_values( array_unique( $candidates ) );

		return array_slice( $candidates, 0, self::OPENAI_MAX_MODEL_FALLBACK_ATTEMPTS );
	}

	/**
	 * Return the error message from an OpenAI error payload.
	 *
	 * @since 1.2.8
	 * @param array $decoded_body Response decoded from JSON.
	 * @param int   $response_code HTTP response code.
	 * @return string
	 */
	private static function get_openai_error_message( $decoded_body, $response_code ) {
		$default_message = __( 'OpenAI API request failed.', 'summaraize' );

		if ( is_array( $decoded_body ) && isset( $decoded_body['error']['message'] ) && is_string( $decoded_body['error']['message'] ) ) {
			$message = sanitize_text_field( $decoded_body['error']['message'] );
			if ( '' !== $message ) {
				return $message;
			}
		}

		if ( 401 === $response_code ) {
			return __( 'Invalid API key or unauthorized access.', 'summaraize' );
		}

		if ( 429 === $response_code ) {
			return __( 'OpenAI rate limit reached.', 'summaraize' );
		}

		return $default_message;
	}

	/**
	 * Detect if an OpenAI failure indicates the model is unavailable.
	 *
	 * These are the cases where trying an alternate model is likely to succeed.
	 *
	 * @since 1.2.8
	 * @param int   $response_code HTTP status code.
	 * @param array $decoded_body  Decoded response data.
	 * @return bool
	 */
	private static function is_openai_model_unavailable_error( $response_code, $decoded_body ) {
		if ( ! is_int( $response_code ) ) {
			return false;
		}

		if ( 400 !== $response_code && 404 !== $response_code ) {
			return false;
		}

		$error_code = '';
		if ( is_array( $decoded_body ) && isset( $decoded_body['error']['code'] ) && is_string( $decoded_body['error']['code'] ) ) {
			$error_code = strtolower( trim( $decoded_body['error']['code'] ) );
		}

		if ( in_array( $error_code, array( 'model_not_found', 'model_not_available', 'unsupported_model', 'not_found', 'invalid_model' ), true ) ) {
			return true;
		}

		$error_message = '';
		if ( is_array( $decoded_body ) && isset( $decoded_body['error']['message'] ) && is_string( $decoded_body['error']['message'] ) ) {
			$error_message = strtolower( trim( $decoded_body['error']['message'] ) );
		}

		if ( '' === $error_message ) {
			return false;
		}

		$patterns = array(
			'/\bmodel\b[^\n]{0,120}\bnot\s+found\b/i',
			'/\bmodel\b[^\n]{0,120}\bnot\s+available\b/i',
			'/\bmodel\b[^\n]{0,120}\bdoes\s+not\s+(exist|support|available)/i',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $error_message ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Decode and normalize OpenAI error payloads.
	 *
	 * @since 1.2.8
	 * @param string $response_body Raw response body.
	 * @return array
	 */
	private static function decode_openai_error_response( $response_body ) {
		if ( ! is_string( $response_body ) ) {
			return array();
		}

		$decoded_body = json_decode( trim( $response_body ), true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_body ) ) {
			return $decoded_body;
		}

		return array();
	}

	/**
	 * Send the OpenAI request with a compatibility fallback for new model families.
	 *
	 * GPT-5 style models reject legacy request parameters in some regions. This
	 * method retries with a compatibility payload when we detect that failure.
	 *
	 * @since 1.2.7
	 * @param string $openai_url API endpoint.
	 * @param array  $payload   Primary request payload.
	 * @param string $model     Model used for the request.
	 * @return array|WP_Error
	 */
	private static function send_openai_request_with_fallback( $openai_url, $payload, $model ) {
		$args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . get_option( 'summaraize_openai_api_key' ),
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 120,
		);

		$response = wp_remote_post( $openai_url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 <= $response_code && 300 > $response_code ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$decoded_body  = json_decode( $response_body, true );

		$error_message                   = '';
		$has_unsupported_parameter_error = false;
		if ( isset( $decoded_body['error']['message'] ) && is_string( $decoded_body['error']['message'] ) ) {
			$error_message                   = strtolower( $decoded_body['error']['message'] );
			$has_unsupported_parameter_error = false !== strpos( $error_message, 'max_tokens' )
				|| false !== strpos( $error_message, 'max_completion_tokens' )
				|| false !== strpos( $error_message, 'temperature' )
				|| false !== strpos( $error_message, 'response_format' )
				|| false !== strpos( $error_message, 'role' );
		}

		if ( ! $has_unsupported_parameter_error ) {
			return $response;
		}

		$fallback_payload = array(
			'model'    => $model,
			'messages' => $payload['messages'],
		);

		if ( self::is_openai_reasoning_model( $model ) ) {
			$fallback_payload['max_completion_tokens'] = 4000;

			// Convert system messages to user messages if reasoning model fails on role.
			if ( false !== strpos( $error_message, 'role' ) ) {
				foreach ( $fallback_payload['messages'] as &$msg ) {
					if ( 'system' === $msg['role'] ) {
						$msg['role'] = 'user';
					}
				}
			}
		} else {
			$fallback_payload['max_tokens']  = 2000;
			$fallback_payload['temperature'] = 0.7;
		}

		if ( isset( $payload['response_format'] ) && false === strpos( $error_message, 'response_format' ) ) {
			$fallback_payload['response_format'] = $payload['response_format'];
		}

		$args['body'] = wp_json_encode( $fallback_payload );

		return wp_remote_post( $openai_url, $args );
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

		$cached_models = get_transient( self::OPENAI_MODELS_CACHE_KEY );
		if ( is_array( $cached_models ) && ! empty( $cached_models ) ) {
			return $cached_models;
		}

		$response = wp_remote_get(
			self::OPENAI_MODELS_ENDPOINT,
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

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code >= 300 ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return false;
		}

		$models = array_filter(
			array_map(
				function ( $model ) {
					if ( isset( $model['id'] ) ) {
						return sanitize_text_field( $model['id'] );
					}
					return '';
				},
				$data['data']
			)
		);

		if ( ! empty( $models ) ) {
			$model_ids = array_values( array_unique( array_filter( $models ) ) );
			// Cache the models in a transient for 24 hours.
			set_transient( self::OPENAI_MODELS_CACHE_KEY, $model_ids, DAY_IN_SECONDS );

			return $model_ids;
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

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Permission denied.', 'summaraize' ),
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
			self::OPENAI_MODELS_ENDPOINT,
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

		if ( wp_remote_retrieve_response_code( $response ) < 200 || wp_remote_retrieve_response_code( $response ) >= 300 ) {
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
