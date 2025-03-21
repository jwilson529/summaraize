<?php
/**
 * Custom Server integration for SummarAIze free trial.
 *
 * @package Summaraize/Admin
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Summaraize_Oneclickcontent_Settings
 *
 * Handles integration with the custom server for free trial functionality.
 *
 * @since 1.0.0
 */
class Summaraize_Oneclickcontent_Settings {

    /**
     * Product slug for free trial.
     *
     * @var string
     */
    const PRODUCT_SLUG = 'summaraize';

    /**
     * API endpoint for the free trial service.
     *
     * @var string
     */
    const API_ENDPOINT = 'http://oneclickcontent.com/wp-json/free-trial/v1/analyze-content';

    /**
     * Secret key for bypassing limits (optional, for demo purposes).
     *
     * @var string
     */
    const SECRET_KEY = 'demo_bypass_key_123';

    /**
     * Process request using OneClickContent provider.
     *
     * This method sends the content to your OneClickContent server endpoint using a constant
     * product slug, the actual origin URL, and an optional secret key.
     *
     * @param string $query The text content to summarize.
     * @return void
     */
    public static function process_oneclickcontent_request( $query ) {
        // Retrieve the real site URL (not hashed, as the server expects the actual URL).
        $origin_url = get_site_url();

        // Build the payload using the actual origin URL, product slug, and content.
        $payload = array(
            'origin_url'   => $origin_url,
            'product_slug' => self::PRODUCT_SLUG,
            'content'      => $query,
        );

        // Make the API request.
        $response = wp_remote_post(
            self::API_ENDPOINT,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body'    => wp_json_encode( $payload ),
                'timeout' => 60,
            )
        );

        // Check for errors in the response.
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => $response->get_error_message() ) );
            return;
        }

        // Retrieve the response body.
        $response_body = wp_remote_retrieve_body( $response );

        // Decode the response body.
        $data = json_decode( $response_body, true );

        // Check if the response contains the expected data structure.
        if ( isset( $data['success'] ) && $data['success'] && isset( $data['points'] ) ) {
            // Wrap the points array in an object so it matches the expected response structure.
            wp_send_json_success( array( 'points' => $data['points'] ) );
        } else {
            $error_message = isset( $data['error'] ) ? $data['error'] : 'Unknown error';
            wp_send_json_error( array( 'message' => $error_message ) );
        }
    }
}