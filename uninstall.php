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
 * Cleanup plugin options.
 */
$summaraize_options = array(
	'summaraize_ai_provider',
	'summaraize_openai_api_key',
	'summaraize_openrouter_api_key',
	'summaraize_openrouter_model',
	'summaraize_google_gemini_api_key',
	'summaraize_ai_model',
	'summaraize_assistant_id',
	'summaraize_custom_prompt',
	'summaraize_post_types',
	'summaraize_prompt_type',
	'summaraize_display_mode',
	'summaraize_display_position',
	'summaraize_widget_title',
	'summaraize_button_style',
	'summaraize_button_color',
	'summaraize_list_type',
	'summaraize_auto_generate_mode',
);

foreach ( $summaraize_options as $summaraize_option ) {
	delete_option( $summaraize_option );
}

/**
 * Cleanup plugin post meta.
 */
$summaraize_post_meta_keys = array(
	'summaraize_points',
	'summaraize_override_settings',
	'summaraize_view',
	'summaraize_mode',
	'summaraize_widget_title',
	'summaraize_button_style',
	'summaraize_button_color',
	'summaraize_list_type',
	'summaraize_generated_at',
	'summaraize_source_hash',
	'summaraize_generation_provider',
	'summaraize_generation_model',
	'summaraize_generated_points_hash',
	'summaraize_manually_edited',
);

foreach ( $summaraize_post_meta_keys as $summaraize_post_meta_key ) {
	delete_post_meta_by_key( $summaraize_post_meta_key );
}

// Clear transients.
delete_transient( 'summaraize_openai_models' );
delete_transient( 'summaraize_openrouter_models' );
delete_transient( 'summaraize_gemini_api_key_valid' );
delete_transient( 'summaraize_gemini_models' );

delete_transient( 'summaraize_openai_models_v2' );
delete_transient( 'summaraize_openai_models_key' );

delete_option( 'summaraize_openrouter_test' );
