<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://oneclickcontent.com
 * @since      1.0.0
 *
 * @package    Summaraize
 * @subpackage Summaraize/public
 */

/**
 * The public-facing functionality of the plugin.
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
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue admin styles.
	 */
	public function enqueue_styles() {
		// Verify we're in the admin area first.
		if ( ! is_admin() ) {
			return;
		}

		$current_screen = get_current_screen();

		// Check if on post/page edit screen with an editor.
		$is_valid_editor_screen = $current_screen &&
								'post' === $current_screen->base &&
								post_type_supports( $current_screen->post_type, 'editor' );

		// Check if on plugin settings page.
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
	 */
	public function enqueue_scripts() {
		if ( ! is_admin() ) {
			return;
		}

		$current_screen = get_current_screen();

		// Check if on post/page edit screen with an editor.
		$is_valid_editor_screen = $current_screen &&
								'post' === $current_screen->base &&
								post_type_supports( $current_screen->post_type, 'editor' );

		// Check if on plugin settings page.
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
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'summaraize_ajax_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid nonce.', 'summaraize' ),
				)
			);
			wp_die();
		}

		// Ensure content is set.
		if ( ! isset( $_POST['content'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Missing content.', 'summaraize' ),
				)
			);
			wp_die();
		}

		$query = sanitize_text_field( wp_unslash( $_POST['content'] ) );

		// Get the selected AI provider.
		$ai_provider = get_option( 'summaraize_ai_provider', 'openai' );

		if ( 'openai' === $ai_provider ) {
			// Retrieve individual options.
			$api_key      = get_option( 'summaraize_openai_api_key' );
			$assistant_id = get_option( 'summaraize_assistant_id' );

			if ( empty( $assistant_id ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Assistant ID is not configured.', 'summaraize' ),
					)
				);
				wp_die();
			}

			if ( empty( $api_key ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'API key is not configured.', 'summaraize' ),
					)
				);
				wp_die();
			}

			// Step 2: Create a thread.
			$thread_id = $this->create_thread( $api_key );
			if ( ! $thread_id ) {
				$settings_url = admin_url( 'admin.php?page=summaraize-settings' );
				$reset_url    = add_query_arg( 'reset', '1', $settings_url );

				$message = sprintf(
					/* translators: 1: Settings page URL, 2: Reset link URL */
					__( 'Failed to create a thread. Please check your <a href="%1$s">AI Provider settings</a>, including API key and Assistant ID, or <a href="%2$s">reset your settings</a>.', 'summaraize' ),
					esc_url( $settings_url ),
					esc_url( $reset_url )
				);

				wp_send_json_error(
					array(
						'message' => $message,
					)
				);
				wp_die();
			}

			// Step 3: Add a user's message to the thread.
			$response = $this->add_message_and_run_thread( $api_key, $thread_id, $assistant_id, $query );
			if ( is_string( $response ) ) {
				wp_send_json_error(
					array(
						'message' => $response,
					)
				);
			} else {
				wp_send_json_success( $response );
			}
			wp_die();

		} elseif ( 'google_gemini' === $ai_provider ) {

			$api_key = get_option( 'summaraize_google_gemini_api_key' );

			if ( empty( $api_key ) ) {
				wp_send_json_error( 'Google Gemini API key is not configured.' );
				wp_die();
			}

			// Construct the request payload with the instructions directly.
			$payload = array(
				'contents'         => array(
					array(
						'parts' => array(
							array(
								'text' => 'Analyze the provided article and extract the top 5 key points.
    Return ONLY the key points in a JSON array containing 5 objects, each representing a key point. The array should be the value of the key "points".

    Where:

    *   `"index"`: Represents the order of the key point (1 to 5).
    *   `"text"`: Contains the textual content of the key point.
    Respond in the language of the provided article.
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
				'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key,
				array(
					'headers' => array( 'Content-Type' => 'application/json' ),
					'body'    => wp_json_encode( $payload ),
					'timeout' => 60, // Increased timeout to 60 seconds.
				)
			);

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( $response->get_error_message() );
			} else {
				$response_code = wp_remote_retrieve_response_code( $response );
				$response_body = wp_remote_retrieve_body( $response );

				if ( $response_code >= 200 && $response_code < 300 ) {
					// Attempt to decode the JSON response.
					$decoded_body = json_decode( $response_body, true );

					if ( json_last_error() === JSON_ERROR_NONE ) {
						// Extract the 'points' array from the response.
						if ( isset( $decoded_body['candidates'][0]['content']['parts'][0]['text'] ) ) {
							$points_json   = $decoded_body['candidates'][0]['content']['parts'][0]['text'];
							$points_object = json_decode( $points_json, true ); // Decode into an object.

							if ( json_last_error() === JSON_ERROR_NONE && is_array( $points_object ) && isset( $points_object['points'] ) ) {
								$points_array = $points_object['points']; // Access the nested 'points' array.

								// Send the extracted points in the desired format.
								wp_send_json_success( array( 'points' => $points_array ) );
							} else {
								wp_send_json_error( 'Failed to process the response.' );
							}
						} else {
							wp_send_json_error( 'Invalid response format.' );
						}
					} else {
						wp_send_json_error( 'Failed to decode JSON response.' );
					}
				} else {
					wp_send_json_error( 'Google Gemini API call unsuccessful.' );
				}
			}
			wp_die();

		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Unsupported AI provider.', 'summaraize' ),
				)
			);
			wp_die();
		}
	}
	/**
	 * Create a new thread in the OpenAI API.
	 *
	 * @param string $api_key The OpenAI API key.
	 * @return string|null The thread ID or null if failed.
	 */
	private function create_thread( $api_key ) {
		$response = wp_remote_post(
			'https://api.openai.com/v1/threads',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'    => '{}',
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $response_body['id'] ) ) {
			return null;
		}

		return $response_body['id'];
	}

	/**
	 * Add a message and run the thread in the OpenAI API.
	 *
	 * @param string $api_key      The OpenAI API key.
	 * @param string $thread_id    The thread ID.
	 * @param string $assistant_id The assistant ID.
	 * @param string $query        The query to add as a message.
	 * @return mixed The result of the run or an error message.
	 */
	private function add_message_and_run_thread( $api_key, $thread_id, $assistant_id, $query ) {
		// Step 3: Add a message to the thread.
		$message_api_url = "https://api.openai.com/v1/threads/{$thread_id}/messages";
		$body            = wp_json_encode(
			array(
				'role'    => 'user',
				'content' => $query,
			)
		);
		$response        = wp_remote_post(
			$message_api_url,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'Failed to add message.';
		}

		// Step 4: Run the thread.
		$run_api_url = "https://api.openai.com/v1/threads/{$thread_id}/runs";
		$body        = wp_json_encode(
			array(
				'assistant_id' => $assistant_id,
			)
		);
		$response    = wp_remote_post(
			$run_api_url,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'Failed to run thread.';
		}

		$response_body    = wp_remote_retrieve_body( $response );
		$decoded_response = json_decode( $response_body, true );

		if ( 'queued' === $decoded_response['status'] || 'running' === $decoded_response['status'] ) {
			return $this->wait_for_run_completion( $api_key, $decoded_response['id'], $thread_id );
		} elseif ( 'completed' === $decoded_response['status'] ) {
			return $this->fetch_messages_from_thread( $api_key, $thread_id );
		} else {
			return 'Run failed or was cancelled.';
		}
	}

	/**
	 * Wait for the run to complete in the OpenAI API.
	 *
	 * @param string $api_key  The OpenAI API key.
	 * @param string $run_id   The run ID.
	 * @param string $thread_id The thread ID.
	 * @return mixed The run result or an error message.
	 */
	private function wait_for_run_completion( $api_key, $run_id, $thread_id ) {
		$status_check_url = "https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}";

		$attempts     = 0;
		$max_attempts = 20;

		while ( $attempts < $max_attempts ) {
			sleep( 5 );
			$response = wp_remote_get(
				$status_check_url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
						'OpenAI-Beta'   => 'assistants=v2',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return 'Failed to check run status.';
			}

			$response_body    = wp_remote_retrieve_body( $response );
			$decoded_response = json_decode( $response_body, true );

			if ( isset( $decoded_response['error'] ) ) {
				return 'Error retrieving run status: ' . $decoded_response['error']['message'];
			}

			if ( isset( $decoded_response['status'] ) && 'completed' === $decoded_response['status'] ) {
				return $this->fetch_messages_from_thread( $api_key, $thread_id );
			} elseif ( isset( $decoded_response['status'] ) && ( 'failed' === $decoded_response['status'] || 'cancelled' === $decoded_response['status'] ) ) {
				return 'Run failed or was cancelled.';
			} elseif ( isset( $decoded_response['status'] ) && 'requires_action' === $decoded_response['status'] ) {
				return $this->handle_requires_action( $api_key, $run_id, $thread_id, $decoded_response['required_action'] );
			}

			++$attempts;
		}

		return 'Run did not complete in expected time.';
	}

	/**
	 * Handle required actions for the run.
	 *
	 * @param string $api_key         The OpenAI API key.
	 * @param string $run_id          The run ID.
	 * @param string $thread_id       The thread ID.
	 * @param array  $required_action The required action details.
	 * @return mixed The run result or an error message.
	 */
	private function handle_requires_action( $api_key, $run_id, $thread_id, $required_action ) {
		if ( 'submit_tool_outputs' === $required_action['type'] ) {
			$tool_calls   = $required_action['submit_tool_outputs']['tool_calls'];
			$tool_outputs = array();

			foreach ( $tool_calls as $tool_call ) {
				$output = '';
				if ( 'function' === $tool_call['type'] ) {
					switch ( $tool_call['function']['name'] ) {
						case 'extract_key_points':
							$output = wp_json_encode(
								array(
									'points' => array(
										array(
											'index' => 1,
											'text'  => 'Point 1',
										),
										array(
											'index' => 2,
											'text'  => 'Point 2',
										),
										array(
											'index' => 3,
											'text'  => 'Point 3',
										),
										array(
											'index' => 4,
											'text'  => 'Point 4',
										),
										array(
											'index' => 5,
											'text'  => 'Point 5',
										),
									),
								)
							);
							break;

						default:
							$output = wp_json_encode( array( 'success' => 'true' ) );
							break;
					}

					$tool_outputs[] = array(
						'tool_call_id' => $tool_call['id'],
						'output'       => $output,
					);
				}
			}

			$submit_tool_outputs_url = "https://api.openai.com/v1/threads/{$thread_id}/runs/{$run_id}/submit_tool_outputs";
			$response                = wp_remote_post(
				$submit_tool_outputs_url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
						'OpenAI-Beta'   => 'assistants=v2',
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode( array( 'tool_outputs' => $tool_outputs ) ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return 'Failed to submit tool outputs.';
			}

			return $this->wait_for_run_completion( $api_key, $run_id, $thread_id );
		}

		return 'Unhandled requires_action.';
	}

	/**
	 * Fetch messages from the thread.
	 *
	 * @param string $api_key   The OpenAI API key.
	 * @param string $thread_id The thread ID.
	 * @return mixed The messages from the thread or an error message.
	 */
	private function fetch_messages_from_thread( $api_key, $thread_id ) {
		$messages_url = "https://api.openai.com/v1/threads/{$thread_id}/messages";

		$response = wp_remote_get(
			$messages_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
					'OpenAI-Beta'   => 'assistants=v2',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return 'Failed to fetch messages.';
		}

		$response_body    = wp_remote_retrieve_body( $response );
		$decoded_response = json_decode( $response_body, true );

		if ( ! isset( $decoded_response['data'] ) ) {
			return 'No messages found.';
		}

		$messages = array_map(
			function ( $message ) {
				foreach ( $message['content'] as $content ) {
					if ( 'text' === $content['type'] ) {
						return json_decode( $content['text']['value'], true );
					}
				}
				return 'No text content.';
			},
			$decoded_response['data']
		);

		return $messages[0];
	}
}
