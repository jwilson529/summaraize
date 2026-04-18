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
	const GEMINI_API_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=%s';

	/**
	 * Default Gemini model used for summary generation.
	 */
	const GEMINI_DEFAULT_MODEL = 'gemini-2.5-flash-lite';

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

		$is_valid = self::validate_google_gemini_api_key( $api_key );
		if ( $is_valid ) {
			wp_send_json_success( array( 'message' => __( 'API key is valid.', 'summaraize' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'API key is invalid.', 'summaraize' ) ) );
		}
	}

	/**
	 * Validates the Google Gemini API key by making a test API call.
	 *
	 * This function uses the 'gemini-2.0-flash-lite' model for testing.
	 *
	 * @since 1.0.0
	 * @param string $api_key The API key to validate.
	 * @return bool True if the API key is valid, false otherwise.
	 */
	public static function validate_google_gemini_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return false;
		}

		// Check if the API key has been validated recently via transient.
		$validated_status = get_transient( 'summaraize_gemini_api_key_valid' );
		if ( 'valid' === $validated_status ) {
			return true;
		}

		// Perform the API request.
		$response = wp_remote_post(
			sprintf( self::GEMINI_API_ENDPOINT, $api_key ),
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'contents' => array(
							array(
								'parts' => array(
									array(
										'text' => 'Hello',
									),
								),
							),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code >= 200 && $response_code < 300 ) {
			// Cache the validation result for 24 hours.
			set_transient( 'summaraize_gemini_api_key_valid', 'valid', DAY_IN_SECONDS );
			return true;
		}

		return false;
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
			sprintf( self::GEMINI_API_ENDPOINT, $api_key ),
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
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
			return new WP_Error( 'api_error', 'Google Gemini API call unsuccessful.' );
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
