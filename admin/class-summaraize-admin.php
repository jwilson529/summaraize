<?php
/**
 * Admin functionality for Summaraize.
 *
 * This file contains the class definition for the public-facing functionality
 * of the Summaraize plugin, including enqueuing assets and handling AJAX
 * requests from the dashboard.
 *
 * @package    Summaraize
 * @subpackage Summaraize/public
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Summaraize_Admin
 *
 * Provides the public-facing functionality of the plugin.
 *
 * @since 1.0.0
 */
class Summaraize_Admin {

	/**
	 * The name of the plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of the plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of the plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		if ( ! is_admin() ) {
			return;
		}

		$current_screen = get_current_screen();

		$is_valid_editor_screen = $current_screen &&
			'post' === $current_screen->base &&
			post_type_supports( $current_screen->post_type, 'editor' );

		$is_settings_page = $current_screen &&
			'settings_page_summaraize-settings' === $current_screen->id;

		if ( $is_valid_editor_screen || $is_settings_page ) {
			wp_enqueue_style(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'css/summaraize-admin.css',
				array(),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! is_admin() ) {
			return;
		}

		$current_screen = get_current_screen();

		$is_valid_editor_screen = $current_screen &&
			'post' === $current_screen->base &&
			post_type_supports( $current_screen->post_type, 'editor' );

		$is_settings_page = $current_screen &&
			'settings_page_summaraize-settings' === $current_screen->id;

		if ( $is_valid_editor_screen || $is_settings_page ) {
			wp_enqueue_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/summaraize-admin.js',
				array( 'jquery' ),
				$this->version,
				false
			);

			wp_enqueue_script(
				'sortablejs',
				plugin_dir_url( __FILE__ ) . 'js/Sortable.min.js',
				array(),
				'1.14.0',
				true
			);

			wp_localize_script(
				$this->plugin_name,
				'summaraize_admin_vars',
				array(
					'ajax_url'                  => admin_url( 'admin-ajax.php' ),
					'summaraize_ajax_nonce'     => wp_create_nonce( 'summaraize_ajax_nonce' ),
					'summaraize_meta_box_nonce' => wp_create_nonce( 'summaraize_meta_box' ),
					'post_id'                   => get_the_ID(),
					'summaraize_openai_debug'   => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( is_admin() && current_user_can( 'manage_options' ) ),
				)
			);
		}
	}

	/**
	 * Handle AJAX request from the front-end.
	 *
	 * @return void
	 */
	public function summaraize_gather_content() {
		check_ajax_referer( 'summaraize_ajax_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Permission denied.', 'summaraize' ),
				)
			);
			wp_die();
		}

		if ( empty( $_POST['content'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Missing content.', 'summaraize' ),
				)
			);
			wp_die();
		}

		$query = sanitize_text_field( wp_unslash( $_POST['content'] ) );

		$allowed_ai_providers = array( 'openai', 'google_gemini' );
		$ai_provider          = get_option( 'summaraize_ai_provider', 'openai' );

		if ( ! in_array( $ai_provider, $allowed_ai_providers, true ) ) {
			$ai_provider = 'openai';
			update_option( 'summaraize_ai_provider', $ai_provider );
		}

		if ( 'openai' === $ai_provider ) {
			Summaraize_OpenAI_Settings::process_openai_request(
				$query,
				isset( $_POST['summaraize_openai_debug'] ) && is_string( $_POST['summaraize_openai_debug'] )
					? sanitize_text_field( wp_unslash( $_POST['summaraize_openai_debug'] ) )
					: ''
			);
		} elseif ( 'google_gemini' === $ai_provider ) {
			Summaraize_Google_Gemini_Settings::process_gemini_request( $query );
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Unsupported AI provider.', 'summaraize' ),
				)
			);
		}

		wp_die();
	}
}
