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

		$is_supported_list_screen = $current_screen &&
			'edit' === $current_screen->base &&
			Summaraize_Summary_Manager::is_post_supported( $current_screen->post_type );

		if ( $is_valid_editor_screen || $is_settings_page || $is_supported_list_screen ) {
			wp_enqueue_style(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'css/summaraize-admin.css',
				array(),
				$this->version . '.' . filemtime( plugin_dir_path( __FILE__ ) . 'css/summaraize-admin.css' ),
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
				array( 'jquery', 'jquery-ui-sortable' ),
				$this->version . '.' . filemtime( plugin_dir_path( __FILE__ ) . 'js/summaraize-admin.js' ),
				false
			);

			wp_localize_script(
				$this->plugin_name,
				'summaraize_admin_vars',
				array(
					'openrouter'                => array(
						'choose'     => __( 'Choose a model', 'summaraize' ),
						'noMatches'  => __( 'No matching models. Try another search or enter a model ID manually.', 'summaraize' ),
						/* translators: %d: Number of matching models. */
						'matches'    => __( '%d models found', 'summaraize' ),

						'untested'   => __( 'Untested', 'summaraize' ),
						'passed'     => __( 'Test passed', 'summaraize' ),
						'testFailed' => __( 'Test failed', 'summaraize' ),
						'changed'    => __( 'Settings changed. Test this key and model, then Save Changes to use them.', 'summaraize' ),
						'testing'    => __( 'Testing the selected model...', 'summaraize' ),
						'loading'    => __( 'Loading text models...', 'summaraize' ),
						'failed'     => __( 'OpenRouter request failed.', 'summaraize' ),
						'loaded'     => __( 'Models loaded. Type in the model field to search.', 'summaraize' ),
						'empty'      => __( 'No text models found. You can paste a model ID manually.', 'summaraize' ),
						'network'    => __( 'Unable to complete the OpenRouter request. Please try again.', 'summaraize' ),
					),
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

		$allowed_ai_providers = array( 'openai', 'google_gemini', 'openrouter' );
		$ai_provider          = get_option( 'summaraize_ai_provider', 'openai' );

		if ( ! in_array( $ai_provider, $allowed_ai_providers, true ) ) {
			$ai_provider = 'openai';
			update_option( 'summaraize_ai_provider', $ai_provider );
		}

		if ( ! in_array( $ai_provider, $allowed_ai_providers, true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unsupported AI provider.', 'summaraize' ),
				)
			);
			wp_die();
		}

		$result = Summaraize_Summary_Manager::generate_summary_from_content(
			$query,
			isset( $_POST['summaraize_openai_debug'] ) && is_string( $_POST['summaraize_openai_debug'] )
				? sanitize_text_field( wp_unslash( $_POST['summaraize_openai_debug'] ) )
				: ''
		);

		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			if ( ! is_array( $error_data ) || empty( $error_data ) ) {
				$error_data = array(
					'message' => $result->get_error_message(),
				);
			}

			wp_send_json_error( $error_data );
		}

		wp_send_json_success(
			array(
				'points'       => $result['points'],
				'summary_meta' => Summaraize_Summary_Manager::build_editor_session_summary_meta(
					$result['points'],
					$query,
					$result['provider'],
					$result['model']
				),
			)
		);

		wp_die();
	}
}
