<?php
/**
 * Google Gemini specific settings and functions for SummarAIze.
 *
 * @package SummarAIze/Admin
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Summaraize_Google_Gemini_Settings
 *
 * Handles Google Gemini settings and API key validation.
 *
 * @since 1.0.0
 */
class Summaraize_Google_Gemini_Settings extends Summaraize_Admin_Settings {

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

		if ( ! isset( $_POST['api_key'] ) ) {

			wp_send_json_error( array( 'message' => __( 'API key is missing.', 'summaraize' ) ) );
		}

		$api_key = sanitize_text_field( wp_unslash( $_POST['api_key'] ) );

		$is_valid = self::validate_google_gemini_api_key( $api_key );

		if ( $is_valid ) {

			wp_send_json_success(
				array( 'message' => __( 'API key is valid.', 'summaraize' ) )
			);
		} else {

			wp_send_json_error( array( 'message' => __( 'API key is invalid.', 'summaraize' ) ) );
		}
	}

	/**
	 * Validates the Google Gemini API key by making a test API call.
	 *
	 * This function uses the 'gemini-1.5-flash' model for testing.
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
			return true; // Key was validated recently.
		}

		// Perform the API request.
		$response = wp_remote_post(
			'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=' . $api_key,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'contents' => array(
							array(
								'parts' => array(
									array( 'text' => 'Hello' ),
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
			// Cache the validation result in a transient for 24 hours.
			set_transient( 'summaraize_gemini_api_key_valid', 'valid', DAY_IN_SECONDS );
			return true;
		}

		return false;
	}
}
