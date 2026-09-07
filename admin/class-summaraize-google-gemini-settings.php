<?php
/**
 * Google Gemini specific settings and functions for Summaraize.
 *
 * @package Summaraize/Admin
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Summaraize_Google_Gemini_Settings
 *
 * Handles Google Gemini settings and API key validation.
 *
 * @since 1.0.0
 */
class Summaraize_Google_Gemini_Settings extends Summaraize_Admin_Settings {

	/**
	 * Gemini API endpoint for the configured model.
	 */
	const GEMINI_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent';

	/**
	 * Default Gemini model used for summary generation.
	 */
	const GEMINI_DEFAULT_MODEL = 'gemini-3.5-flash-lite';

	/**
	 * Handles AJAX request to validate the Google Gemini API key.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function summaraize_ajax_validate_google_gemini_api_key() {

		if ( ! check_ajax_referer( 'summaraize_ajax_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'summaraize' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'summaraize' ) ) );
		}

		if ( ! isset( $_POST['api_key'] ) ) {
			wp_send_json_error( array( 'message' => __( 'API key is missing.', 'summaraize' ) ) );
		}

		$api_key = sanitize_text_field( wp_unslash( $_POST['api_key'] ) );

		$is_valid = self::check_google_gemini_api_key( $api_key );
		if ( ! is_wp_error( $is_valid ) ) {
			wp_send_json_success( array( 'message' => __( 'API key is valid.', 'summaraize' ) ) );
		} else {
			wp_send_json_error( array( 'message' => $is_valid->get_error_message() ) );
		}
	}

	/**
	 * Validate a key without generating billable content.
	 *
	 * @param string $api_key API key.
	 * @return bool
	 */
	public static function validate_google_gemini_api_key( $api_key ) {
		return ! is_wp_error( self::check_google_gemini_api_key( $api_key ) );
	}

	/**
	 * Check authentication independently of generation model availability.
	 *
	 * @param string $api_key API key.
	 * @return true|WP_Error
	 */
	public static function check_google_gemini_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return new WP_Error( 'missing_key', __( 'Enter your Google Gemini API key.', 'summaraize' ) );
		}
		$fingerprint = hash( 'sha256', $api_key );
		if ( hash_equals( (string) get_transient( 'summaraize_gemini_api_key_valid' ), $fingerprint ) ) {
			return true;
		}
		$response = wp_remote_get(
			'https://generativelanguage.googleapis.com/v1beta/models',
			array(
				'headers' => array( 'x-goog-api-key' => $api_key ),
				'timeout' => 30,
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'connection_error', __( 'Unable to reach Google Gemini. Please try again.', 'summaraize' ) );
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return self::api_error( $response );
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['models'] ) || ! is_array( $body['models'] ) ) {
			return new WP_Error( 'invalid_response', __( 'Google Gemini returned an unexpected model list. Please try again.', 'summaraize' ) );
		}
		set_transient( 'summaraize_gemini_api_key_valid', $fingerprint, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Explain provider failures without exposing credentials or response bodies.
	 *
	 * @param array $response HTTP response.
	 * @return WP_Error
	 */
	private static function api_error( $response ) {
		$code    = wp_remote_retrieve_response_code( $response );
		$body    = json_decode( wp_remote_retrieve_body( $response ), true );
		$message = __( 'Google Gemini could not complete the request. Please try again.', 'summaraize' );
		if ( 429 === $code ) {
			$message = __( 'Google Gemini quota or rate limit reached. Check this key\'s project quota and billing in Google AI Studio.', 'summaraize' );
		} elseif ( 404 === $code ) {
			$message = __( 'The requested Gemini model is unavailable for this account. Update SummarAIze to use a supported model.', 'summaraize' );
		} elseif ( 403 === $code ) {
			$message = __( 'Google denied access. Check the API key restrictions and Gemini API access for its Google Cloud project.', 'summaraize' );
		} elseif ( 401 === $code || ( isset( $body['error']['message'] ) && false !== stripos( $body['error']['message'], 'API key not valid' ) ) ) {
			$message = __( 'Google rejected this API key. Copy a Gemini API key from Google AI Studio.', 'summaraize' );
		}
		return new WP_Error( 'summaraize_gemini_api_error', $message, array( 'status' => $code ) );
	}

	/**
	 * Process request using Google Gemini provider.
	 *
	 * Sends the query to the Google Gemini API and returns the extracted key points.
	 *
	 * @since 1.0.0
	 * @param string $query The text content to summarize.
	 * @return void
	 */
	public static function process_gemini_request( $query ) {
		$response = self::request_gemini_summary( $query );
		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => $response->get_error_message(),
				)
			);
			return;
		}

		wp_send_json_success(
			array(
				'points' => $response['points'],
			)
		);
	}

	/**
	 * Request summary points from Gemini and return structured data.
	 *
	 * @since 1.4.0
	 * @param string $query The text content to summarize.
	 * @return array|WP_Error
	 */
	public static function request_gemini_summary( $query ) {
		$api_key = get_option( 'summaraize_google_gemini_api_key' );

		if ( empty( $api_key ) ) {
			return new WP_Error( 'summaraize_gemini_missing_key', __( 'Google Gemini API key is not configured.', 'summaraize' ) );
		}

		$response = self::make_gemini_api_request( $api_key, $query );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array(
			'points' => $response,
			'model'  => self::GEMINI_DEFAULT_MODEL,
		);
	}

	/**
	 * Makes an API request to Google Gemini.
	 *
	 * @since 1.0.0
	 * @param string $api_key The Google Gemini API key.
	 * @param string $query   The content to summarize.
	 * @return array|WP_Error Array of points or WP_Error on failure.
	 */
	public static function make_gemini_api_request( $api_key, $query ) {
		$payload = array(
			'contents'         => array(
				array(
					'parts' => array(
						array(
							'text' => 'Analyze the provided article, detect its primary language, and extract the top 5 key points in that same language. Do NOT translate.
Return ONLY the key points in a JSON array containing 5 objects, each representing a key point. The array should be the value of the key "points".

Here is the article:

' . $query,
						),
					),
				),
			),
			'generationConfig' => array(
				'response_mime_type' => 'application/json',
				'response_schema'    => array(
					'type'       => 'OBJECT',
					'properties' => array(
						'points' => array(
							'type'  => 'ARRAY',
							'items' => array(
								'type'       => 'OBJECT',
								'properties' => array(
									'index' => array( 'type' => 'INTEGER' ),
									'text'  => array( 'type' => 'STRING' ),
								),
							),
						),
					),
				),
			),
		);

		$response = wp_remote_post(
			self::GEMINI_API_ENDPOINT,
			array(
				'headers' => array(
					'Content-Type'   => 'application/json',
					'x-goog-api-key' => $api_key,
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return self::parse_gemini_response( $response );
	}

	/**
	 * Parses the Gemini API response.
	 *
	 * Extracts the key points from the API response.
	 *
	 * @since 1.0.0
	 * @param array $response The API response from wp_remote_post.
	 * @return array|WP_Error Array of points or WP_Error on failure.
	 */
	public static function parse_gemini_response( $response ) {
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code < 200 || $response_code >= 300 ) {
			return self::api_error( $response );
		}

		$decoded_body = json_decode( $response_body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'json_decode_error', 'Failed to decode JSON response.' );
		}

		if ( ! isset( $decoded_body['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return new WP_Error( 'invalid_response', 'Invalid response format.' );
		}

		$points_json   = $decoded_body['candidates'][0]['content']['parts'][0]['text'];
		$points_object = json_decode( $points_json, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $points_object ) || ! isset( $points_object['points'] ) ) {
			return new WP_Error( 'invalid_points', 'Failed to process the response.' );
		}

		return $points_object['points'];
	}
}
