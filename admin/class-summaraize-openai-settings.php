<?php
/**
 * Class Summaraize_Admin_Settings
 *
 * Manages the admin settings page for the SummarAIze plugin.
 *
 * @since 1.0.0
 * @package Summaraize
 */

/**
 * Class Summaraize_Admin_Settings
 */
class Summaraize_OpenAI_Settings extends Summaraize_Admin_Settings {

	/**
	 * Callback for the Assistant ID field.
	 */
	public function summaraize_assistant_id_callback() {
		$value = get_option( 'summaraize_assistant_id', '' );

		if ( empty( $value ) ) {
			// Attempt to create a new assistant if none exists.
			$assistant_id = $this->summaraize_create_assistant();
			$value        = $assistant_id ? $assistant_id : 'Failed to create assistant';
			update_option( 'summaraize_assistant_id', $value );
		}

		echo '<input type="text" id="summaraize_assistant_id" name="summaraize_assistant_id" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the Assistant ID provided by OpenAI or leave as is to use the auto-generated one.', 'summaraize' ) . '</p>';
	}


	/**
	 * Hook to handle the assistant creation.
	 */
	public function summaraize_handle_assistant_creation() {
		// Log that the function was called.

		// Check if the create assistant button was clicked.
		if ( isset( $_POST['summariaze_create_assistant'] ) ) {

			// Check nonce for security.
			if ( check_admin_referer( 'summaraize_ajax_nonce', 'summaraize_create_assistant_nonce' ) ) {

				// Attempt to create the assistant.
				$assistant_id = $this->summaraize_create_assistant();

				if ( $assistant_id ) {

					update_option( 'summaraize_assistant_id', $assistant_id );
					add_settings_error( 'summaraize_assistant_id', 'assistant-created', __( 'Assistant successfully created.', 'summaraize' ), 'updated' );
				} else {

					add_settings_error( 'summaraize_assistant_id', 'assistant-creation-failed', __( 'Failed to create assistant.', 'summaraize' ), 'error' );
				}
			} else {

				add_settings_error( 'summaraize_assistant_id', 'nonce-failed', __( 'Nonce verification failed.', 'summaraize' ), 'error' );
			}
		}
	}


	/**
	 * Create the OpenAI assistant.
	 *
	 * @since 1.0.0
	 */
	private function summaraize_create_assistant() {
		$api_key        = get_option( 'summaraize_openai_api_key' );
		$selected_model = get_option( 'summaraize_ai_model', 'gpt-4o-mini' );
		if ( empty( $api_key ) ) {
			return false;
		}

		$prompt_type   = get_option( 'summaraize_prompt_type', '' );
		$custom_prompt = get_option( 'summaraize_custom_prompt', '' );

		$default_prompt = array(
			'description' => 'This Assistant extracts the top 5 key points from a given article and returns them in a JSON format being sure to use the `extract_key_points` function.',
			'behavior'    => array(
				array(
					'trigger'     => 'message',
					'instruction' => "When provided with a message containing the content of an article, analyze the article and identify the top 5 key points. Call the `extract_key_points` function to return these points in a JSON format. The expected JSON format is:\n[\n  { \"index\": 1, \"text\": \"Point 1 content\" },\n  { \"index\": 2, \"text\": \"Point 2 content\" },\n  { \"index\": 3, \"text\": \"Point 3 content\" },\n  { \"index\": 4, \"text\": \"Point 4 content\" },\n  { \"index\": 5, \"text\": \"Point 5 content\" }\n]",
				),
			),
		);

		$predefined_prompts = array(
			'formal'           => 'Ensure the language used in the key points is formal and professional.',
			'statistics'       => 'Focus on key points that mention data or statistics.',
			'non_expert'       => 'Summarize the key points in a way that is easily understandable for non-experts.',
			'summary_first'    => 'Include a short summary sentence before listing the key points.',
			'concise'          => 'Limit each key point to no more than two sentences.',
			'actionable'       => 'Highlight actionable insights or recommendations as key points.',
			'exclude_politics' => 'Avoid including points that mention politics.',
			'spanish'          => 'Translate the key points into Spanish before returning the JSON.',
			'explanation'      => 'Provide a brief explanation of why each key point is important.',
			'environment'      => 'Extract key points that are relevant to environmental sustainability.',
		);

		if ( 'custom' === $prompt_type && ! empty( $custom_prompt ) ) {
			$default_prompt['description'] = $custom_prompt . "\n\n" . $default_prompt['description'];
		} elseif ( array_key_exists( $prompt_type, $predefined_prompts ) ) {
			$default_prompt['description'] = $predefined_prompts[ $prompt_type ] . "\n\n" . $default_prompt['description'];
		}

		$function_definition = array(
			'name'        => 'extract_key_points',
			'description' => 'Extract the top 5 key points from the provided article content and return them in a specific JSON format.',
			'parameters'  => array(
				'type'       => 'object',
				'properties' => array(
					'points' => array(
						'type'  => 'array',
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'index' => array(
									'type'        => 'integer',
									'description' => 'The index of the key point.',
								),
								'text'  => array(
									'type'        => 'string',
									'description' => 'The content of the key point.',
								),
							),
							'required'   => array( 'index', 'text' ),
						),
					),
				),
				'required'   => array( 'points' ),
			),
		);

		$payload = array(
			'description'     => 'Assistant for generating concise content summaries.',
			'instructions'    => wp_json_encode( $default_prompt ),
			'name'            => 'SummarAIze Assistant',
			'tools'           => array(
				array(
					'type'     => 'function',
					'function' => $function_definition,
				),
			),
			'model'           => $selected_model,
			'response_format' => array( 'type' => 'json_object' ),
		);

		$response = wp_remote_post(
			'https://api.openai.com/v1/assistants',
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
					'OpenAI-Beta'   => 'assistants=v2',
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_body  = wp_remote_retrieve_body( $response );
		$assistant_data = json_decode( $response_body, true );

		if ( isset( $assistant_data['id'] ) ) {
			return $assistant_data['id'];
		}

		return false;
	}

	/**
	 * Callback function for the AI Model setting field.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function summaraize_ai_model_callback() {
		$selected_model = get_option( 'summaraize_ai_model', 'gpt-4o-mini' );
		$api_key        = get_option( 'summaraize_openai_api_key' );

		if ( ! empty( $api_key ) ) {
			$models = self::validate_openai_api_key( $api_key );

			if ( $models && is_array( $models ) ) {
				echo '<select id="summaraize_ai_model" name="summaraize_ai_model">';

				echo '<option value="gpt-4o-mini"' . selected( $selected_model, 'gpt-4o-mini', false ) . '>' . esc_html__( 'Default (gpt-4o-mini)', 'summaraize' ) . '</option>';

				foreach ( $models as $model ) {
					echo '<option value="' . esc_attr( $model ) . '"' . selected( $selected_model, $model, false ) . '>' . esc_html( $model ) . '</option>';
				}
				echo '</select>';
				echo '<p class="description">';
				esc_html_e( 'These models support the function calling ability that is required to use SummarAIze.', 'summaraize' );
				echo '</p>';
			} else {
				echo '<p class="summaraize-alert">';
				esc_html_e( 'Unable to retrieve models. Please check your API key.', 'summaraize' );
				echo '</p>';
			}
		} else {
			echo '<p class="summaraize-alert">';
			esc_html_e( 'Please enter a valid OpenAI API key first.', 'summaraize' );
			echo '</p>';
		}
	}

	/**
	 * Handles AJAX request to validate the OpenAI API key.
	 *
	 * This function checks the validity of the provided API key by sending a request
	 * to the OpenAI API and verifying the response. If the API key is valid, it returns
	 * a success response with the available models; otherwise, it returns an error.
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

		if ( ! isset( $_POST['api_key'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'API key is missing.', 'summaraize' ),
				)
			);
		}

		$api_key = sanitize_text_field( wp_unslash( $_POST['api_key'] ) );

		$models = self::validate_openai_api_key( $api_key );

		if ( false !== $models ) {
			wp_send_json_success(
				array(
					'message' => __( 'API key is valid.', 'summaraize' ),
					'models'  => $models,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'API key is invalid or no suitable models found.', 'summaraize' ),
				)
			);
		}
	}

	/**
	 * Validates the OpenAI API key and fetches models that support function calling.
	 *
	 * @since 1.0.0
	 * @param string $api_key The API key to validate.
	 * @return array|bool List of models if successful, false otherwise.
	 */
	public static function validate_openai_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return false;
		}

		// Make the API request if no cached models are found.
		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
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

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		$function_calling_cutoff          = 1686614400;
		$parallel_function_calling_cutoff = 1699228800;

		if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
			$models = array_filter(
				$data['data'],
				function ( $model ) use ( $function_calling_cutoff, $parallel_function_calling_cutoff ) {
					return isset( $model['created'] ) && (
						$model['created'] >= $function_calling_cutoff ||
						$model['created'] >= $parallel_function_calling_cutoff
					);
				}
			);

			if ( ! empty( $models ) ) {
				$model_ids = array_map(
					function ( $model ) {
						return $model['id'];
					},
					$models
				);

				// Cache the models in a transient for 24 hours (adjust as needed).
				set_transient( 'summaraize_openai_models', $model_ids, DAY_IN_SECONDS );

				return $model_ids;
			}
		}

		return false;
	}


	/**
	 * Callback for the OpenAI API key field.
	 */
	public function summaraize_openai_api_key_callback() {
		$ai_provider = get_option( 'summaraize_ai_provider', 'openai' );
		if ( 'openai' === $ai_provider ) {
			$value = get_option( 'summaraize_openai_api_key', '' );
			echo '<input type="password" name="summaraize_openai_api_key" value="' . esc_attr( $value ) . '" />';
			echo '<p class="description">' .
			wp_kses_post( __( 'Get your OpenAI API Key <a href="https://beta.openai.com/signup/">here</a>.', 'summaraize' ) ) . '</p>';
		}
	}

	/**
	 * Register the advanced settings fields if the API key is valid.
	 */
	public function register_summaraize_advanced_settings_fields() {
		register_setting( 'summaraize_settings_advanced', 'summaraize_prompt_type' );
		register_setting( 'summaraize_settings_advanced', 'summaraize_custom_prompt' );
		register_setting( 'summaraize_settings_advanced', 'summaraize_ai_model' );

		add_settings_field(
			'summaraize_prompt_type',
			__( 'Assistant Prompt Type', 'summaraize' ),
			array( $this, 'summaraize_prompt_type_callback' ),
			'summaraize_settings_advanced',
			'summaraize_advanced_settings_section'
		);

		add_settings_field(
			'summaraize_custom_prompt',
			__( 'Custom Assistant Instructions', 'summaraize' ),
			array( $this, 'summaraize_custom_prompt_callback' ),
			'summaraize_settings_advanced',
			'summaraize_advanced_settings_section',
			array( 'class' => 'summaraize_custom_prompt_field' )
		);

		add_settings_field(
			'summaraize_ai_model',
			__( 'AI Model', 'summaraize' ),
			array( $this, 'summaraize_ai_model_callback' ),
			'summaraize_settings_advanced',
			'summaraize_advanced_settings_section'
		);
	}
}
