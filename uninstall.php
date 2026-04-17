<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://github.com/jwilson529/summaraize
 * @since      1.0.0
 *
 * @package    Summaraize
 */

defined( 'ABSPATH' ) || exit;

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Cleanup plugin data.
 */
$options = array(
	'summaraize_ai_provider',
	'summaraize_openai_api_key',
	'summaraize_google_gemini_api_key',
	'summaraize_ai_model',
	'summaraize_post_types',
	'summaraize_display_mode',
	'summaraize_display_position',
	'summaraize_widget_title',
	'summaraize_button_style',
	'summaraize_button_color',
	'summaraize_list_type',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete post meta.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'summaraize_%'" );

// Clear transients.
delete_transient( 'summaraize_openai_models' );
delete_transient( 'summaraize_gemini_api_key_valid' );
delete_transient( 'summaraize_gemini_models' );
