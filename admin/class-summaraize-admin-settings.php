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
class Summaraize_Admin_Settings {

	/**
	 * Register the plugin settings page.
	 */
	public function summaraize_register_options_page() {
		add_options_page(
			__( 'SummarAIze Settings', 'summaraize' ),
			__( 'SummarAIze', 'summaraize' ),
			'manage_options',
			'summaraize-settings',
			array( $this, 'summaraize_options_page' )
		);
	}

	/**
	 * Display the options page.
	 */
	public function summaraize_options_page() {
		// Instantiate the OpenAI settings class
		$openai_settings = new Summaraize_OpenAI_Settings();
		// Get the selected AI provider
		$ai_provider = get_option( 'summaraize_ai_provider', 'openai' ); 
		?>
		<div id="summaraize" class="wrap">
			<h1><?php esc_html_e( 'SummarAIze Settings', 'summaraize' ); ?></h1>
			
			<h2 class="nav-tab-wrapper">
			    <a href="#main-settings" class="nav-tab nav-tab-active"><?php esc_html_e( 'Main Settings', 'summaraize' ); ?></a>
			    <?php if ( $ai_provider === 'openai' ) : ?>
			        <a href="#advanced-settings" class="nav-tab"><?php esc_html_e( 'OpenAI Assistant Settings', 'summaraize' ); ?></a>
			    <?php endif; ?>
			</h2>

			<form class="summaraize-settings-form" method="post" action="options.php">
				<?php settings_fields( 'summaraize_settings' ); ?>
				
				<div id="main-settings" class="tab-content">
					<?php do_settings_sections( 'summaraize_settings' ); ?>
				</div>

				<div id="advanced-settings" class="tab-content" style="display:none;">
					<h2><?php esc_html_e( 'OpenAI Assistant Settings', 'summaraize' ); ?></h2>

					<h4 class="description summaraize-alert">
						<?php esc_html_e( 'If you modify the Assistant Prompt Type, Custom Instructions, or AI Model, please regenerate the Assistant to apply these changes. If you encounter issues, try reverting to the default settings.', 'summaraize' ); ?>
					</h4>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'AI Model', 'summaraize' ); ?></th>
							<td><?php $openai_settings->summaraize_ai_model_callback(); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Assistant Prompt Type', 'summaraize' ); ?></th>
							<td><?php $this->summaraize_prompt_type_callback(); ?></td>
						</tr>
						<tr id="summaraize_custom_prompt_row" style="display: none;">
							<th scope="row"><?php esc_html_e( 'Custom Assistant Instructions', 'summaraize' ); ?></th>
							<td><?php $this->summaraize_custom_prompt_callback(); ?></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Reset Assistant ID', 'summaraize' ); ?></th>
							<td>
								<button id="summariaze_create_assistant" class="button button-secondary">
									<?php esc_html_e( 'Regenerate Assistant', 'summaraize' ); ?>
								</button>
								<p class="description"><?php esc_html_e( 'This will clear the current Assistant ID and generate a new one.', 'summaraize' ); ?></p>
							</td>
						</tr>
					</table>
				</div>


				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}








	/**
	 * Display admin notices for settings.
	 */
	public function display_admin_notices() {
		settings_errors();
	}


	/**
	 * Register the plugin settings.
	 */
	public function summaraize_register_settings() {
	    // Instantiate the settings classes
	    $openai_settings = new Summaraize_OpenAI_Settings();
	    $gemini_settings = new Summaraize_Google_Gemini_Settings();

	    // Register the AI provider setting
	    add_settings_field(
	        'summaraize_ai_provider',
	        __( 'AI Provider', 'summaraize' ),
	        array( $this, 'summaraize_ai_provider_callback' ),
	        'summaraize_settings',
	        'summaraize_settings_section'
	    );

	    // Register the API key settings with sanitization
	    register_setting(
	        'summaraize_settings',
	        'summaraize_openai_api_key',
	        array( 'sanitize_callback' => 'sanitize_text_field' )
	    );
	    register_setting(
	        'summaraize_settings',
	        'summaraize_google_gemini_api_key',
	        array( 'sanitize_callback' => 'sanitize_text_field' )
	    );

	    // Add the main settings section
	    add_settings_section(
	        'summaraize_settings_section',
	        __( 'SummarAIze Settings', 'summaraize' ),
	        array( $this, 'summaraize_settings_section_callback' ),
	        'summaraize_settings'
	    );

	    // Conditionally register settings based on the selected AI provider
	    $ai_provider = get_option( 'summaraize_ai_provider', 'openai' );

	    if ( $ai_provider === 'openai' ) {
	        // --- OpenAI specific settings ---

	        // Add the OpenAI API key field
	        add_settings_field(
	            'summaraize_openai_api_key',
	            __( 'OpenAI API Key', 'summaraize' ),
	            array( $openai_settings, 'summaraize_openai_api_key_callback' ),
	            'summaraize_settings',
	            'summaraize_settings_section',
	            array( 'label_for' => 'summaraize_openai_api_key' )
	        );

	        // Register Assistant ID setting and field
	        register_setting( 'summaraize_settings', 'summaraize_assistant_id' );
	        add_settings_field(
	            'summaraize_assistant_id',
	            __( 'Assistant ID', 'summaraize' ),
	            array( $openai_settings, 'summaraize_assistant_id_callback' ),
	            'summaraize_settings',
	            'summaraize_settings_section',
	            array( 'label_for' => 'summaraize_assistant_id' )
	        );

	        // Retrieve the API key
	        $open_api_key = get_option( 'summaraize_openai_api_key' );

	        // Check if the API key is valid
	        if ( ! empty( $open_api_key ) && Summaraize_OpenAI_Settings::validate_openai_api_key( $open_api_key ) ) {
	            $this->register_summaraize_main_settings_fields();
	            $openai_settings->register_summaraize_advanced_settings_fields();
	        } else {
	            add_settings_error(
	                'summaraize_openai_api_key',
	                'invalid-api-key',
	                sprintf(
	                    __( 'The OpenAI API key is invalid. Please enter a valid API key in the <a href="%s">SummarAIze settings</a> to use SummarAIze.', 'summaraize' ),
	                    esc_url( admin_url( 'options-general.php?page=summaraize-settings' ) )
	                ),
	                'error'
	            );
	        }
	    } elseif ( $ai_provider === 'google_gemini' ) {
	        // --- Google Gemini specific settings ---
	        $gemini_api_key = get_option( 'summaraize_google_gemini_api_key' );
	    	if ( ! empty( $gemini_api_key ) && Summaraize_Google_Gemini_Settings::validate_google_gemini_api_key( $gemini_api_key ) ) {
	    		$this->register_summaraize_main_settings_fields();
		        // Add the Google Gemini API key field
		        add_settings_field(
		            'summaraize_google_gemini_api_key',
		            __( 'Google Gemini API Key', 'summaraize' ),
		            array( $gemini_settings, 'summaraize_google_gemini_api_key_callback' ),
		            'summaraize_settings',
		            'summaraize_settings_section',
		            array( 'label_for' => 'summaraize_google_gemini_api_key' )
		        );
			}

	    }
	}


	/**
	 * Register the main settings fields if the API key is valid.
	 */
	private function register_summaraize_main_settings_fields() {
		// Instantiate the OpenAI settings class
		$openai_settings = new Summaraize_OpenAI_Settings();

		register_setting( 'summaraize_settings', 'summaraize_post_types' );

		register_setting( 'summaraize_settings', 'summaraize_widget_title' );
		register_setting( 'summaraize_settings', 'summaraize_display_position' );
		register_setting( 'summaraize_settings', 'summaraize_display_mode' );
		register_setting( 'summaraize_settings', 'summaraize_button_style' );
		register_setting( 'summaraize_settings', 'summaraize_button_color' );
		register_setting( 'summaraize_settings', 'summaraize_list_type' );



		add_settings_field(
			'summaraize_post_types',
			__( 'Post Types', 'summaraize' ),
			array( $this, 'summaraize_post_types_callback' ),
			'summaraize_settings',
			'summaraize_settings_section'
		);

		add_settings_field(
			'summaraize_widget_title',
			__( 'Widget Title', 'summaraize' ),
			array( $this, 'summaraize_widget_title_callback' ),
			'summaraize_settings',
			'summaraize_settings_section'
		);

		add_settings_field(
			'summaraize_display_position',
			__( 'Display Position', 'summaraize' ),
			array( $this, 'summaraize_display_position_callback' ),
			'summaraize_settings',
			'summaraize_settings_section'
		);

		add_settings_field(
			'summaraize_display_mode',
			__( 'Display Mode', 'summaraize' ),
			array( $this, 'summaraize_display_mode_callback' ),
			'summaraize_settings',
			'summaraize_settings_section'
		);

		add_settings_field(
			'summaraize_button_style',
			__( 'Button Style', 'summaraize' ),
			array( $this, 'summaraize_button_style_callback' ),
			'summaraize_settings',
			'summaraize_settings_section'
		);

		add_settings_field(
			'summaraize_button_color',
			__( 'Button Color', 'summaraize' ),
			array( $this, 'summaraize_button_color_callback' ),
			'summaraize_settings',
			'summaraize_settings_section'
		);

		add_settings_field(
			'summaraize_list_type',
			__( 'List Type', 'summaraize' ),
			array( $this, 'summaraize_list_type_callback' ),
			'summaraize_settings',
			'summaraize_settings_section'
		);


	}

	public function summaraize_ai_provider_callback() {
	    $ai_provider = get_option( 'summaraize_ai_provider', 'openai' ); // Default to OpenAI
	    ?>
	    <select name="summaraize_ai_provider" id="summaraize_ai_provider">
	        <option value="openai" <?php selected( $ai_provider, 'openai' ); ?>><?php esc_html_e( 'OpenAI', 'summaraize' ); ?></option>
	        <option value="google_gemini" <?php selected( $ai_provider, 'google_gemini' ); ?>><?php esc_html_e( 'Google Gemini', 'summaraize' ); ?></option>
	    </select>
	    <?php
	}



	public function summaraize_google_gemini_api_key_callback() {
	    $ai_provider = get_option( 'summaraize_ai_provider', 'openai' );
	    if ( $ai_provider === 'google_gemini' ) {
	        $value = get_option( 'summaraize_google_gemini_api_key', '' );
	        echo '<input type="password" name="summaraize_google_gemini_api_key" value="' . esc_attr( $value ) . '" />';
	        // You might want to add a description with a link to get the Google Gemini API key
	    }
	}


	/**
	 * Callback function for the List Type setting field.
	 *
	 * This function outputs a dropdown menu allowing users to select the type of list
	 * (ordered or unordered) for displaying key points generated by the SummarAIze plugin.
	 * The selected option is saved to the WordPress options table and used to control
	 * the list format in the front-end display.
	 *
	 * @return void
	 */
	public function summaraize_list_type_callback() {
		$list_type = get_option( 'summaraize_list_type', 'unordered' );
		?>
		<select name="summaraize_list_type" id="summaraize_list_type">
			<option value="unordered" <?php selected( $list_type, 'unordered' ); ?>><?php esc_html_e( 'Bullet List', 'summaraize' ); ?></option>
			<option value="ordered" <?php selected( $list_type, 'ordered' ); ?>><?php esc_html_e( 'Ordered List', 'summaraize' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Choose the type of list for displaying the key points.', 'summaraize' ); ?></p>
		<?php
	}


	/**
	 * Callback for the custom prompt field.
	 */
	public function summaraize_custom_prompt_callback() {
		$value       = get_option( 'summaraize_custom_prompt', '' );
		$prompt_type = get_option( 'summaraize_prompt_type', '' );

		// Generate a unique ID for the custom prompt textarea.
		$unique_id = 'summaraize_custom_prompt_custom';

		// Only display the textarea if 'custom' is selected.
		$style = ( 'custom' === $prompt_type ) ? '' : 'display:none;';

		echo '<textarea id="' . esc_attr( $unique_id ) . '" name="summaraize_custom_prompt" rows="5" cols="50" style="' . esc_attr( $style ) . '">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Add your custom instructions or description for the Assistant. These will be prepended to the default instructions.', 'summaraize' ) . '</p>';
	}



	/**
	 * Callback for the custom prompt dropdown.
	 */
	public function summaraize_prompt_type_callback() {
		$value   = get_option( 'summaraize_prompt_type', '' );
		$options = array(
			''                 => 'Default',
			'formal'           => 'Formal and Professional',
			'statistics'       => 'Focus on Statistics',
			'non_expert'       => 'Understandable for Non-Experts',
			'summary_first'    => 'Include a Summary Sentence',
			'concise'          => 'Limit to Two Sentences',
			'actionable'       => 'Highlight Actionable Insights',
			'exclude_politics' => 'Avoid Politics',
			'spanish'          => 'Translate to Spanish',
			'explanation'      => 'Include Explanation of Importance',
			'environment'      => 'Relevant to Environmental Sustainability',
			'custom'           => 'Custom',
		);

		echo '<select id="summaraize_prompt_type" name="summaraize_prompt_type">';
		foreach ( $options as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $value, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Choose a predefined instruction set for the Assistant or select Custom to provide your own.', 'summaraize' ) . '</p>';
	}


	/**
	 * Callback for the widget title field.
	 */
	public function summaraize_widget_title_callback() {
		$widget_title = get_option( 'summaraize_widget_title', 'Key Takeaways' );
		?>
		<input type="text" name="summaraize_widget_title" id="summaraize_widget_title" value="<?php echo esc_attr( $widget_title ); ?>" />
		<p class="description"><?php esc_html_e( 'Enter the title for the widget.', 'summaraize' ); ?></p>
		<?php
	}

	/**
	 * Callback for the button style field.
	 */
	public function summaraize_button_style_callback() {
		$selected_style = get_option( 'summaraize_button_style', 'flat' );
		$button_styles  = array(
			'flat'        => __( 'Flat', 'summaraize' ),
			'rounded'     => __( 'Rounded', 'summaraize' ),
			'angled'      => __( 'Angled', 'summaraize' ),
			'apple'       => __( 'Apple', 'summaraize' ),
			'google'      => __( 'Google', 'summaraize' ),
			'bubbly'      => __( 'Bubbly', 'summaraize' ),
			'material'    => __( 'Material', 'summaraize' ),
			'windows'     => __( 'Windows', 'summaraize' ),
			'neumorphism' => __( 'Neumorphism', 'summaraize' ),
			'3d'          => __( '3D', 'summaraize' ),
		);
		?>
		<select name="summaraize_button_style" id="summaraize_button_style">
			<?php foreach ( $button_styles as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_style, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Choose the style for the button.', 'summaraize' ); ?></p>
		<?php
	}

	/**
	 * Callback for the button color field.
	 */
	public function summaraize_button_color_callback() {
		$button_color = get_option( 'summaraize_button_color', '#0073aa' );
		?>
		<input type="color" name="summaraize_button_color" id="summaraize_button_color" value="<?php echo esc_attr( $button_color ); ?>" />
		<p class="description"><?php esc_html_e( 'Choose the color for the button.', 'summaraize' ); ?></p>
		<?php
	}

	/**
	 * Callback for the display position field.
	 */
	public function summaraize_display_position_callback() {
		$selected_position = get_option( 'summaraize_display_position', 'above' );
		?>
		<select name="summaraize_display_position" id="summaraize_display_position">
			<option value="above" <?php selected( $selected_position, 'above' ); ?>><?php esc_html_e( 'Above Content', 'summaraize' ); ?></option>
			<option value="below" <?php selected( $selected_position, 'below' ); ?>><?php esc_html_e( 'Below Content', 'summaraize' ); ?></option>
			<option value="popup" <?php selected( $selected_position, 'popup' ); ?>><?php esc_html_e( 'Popup', 'summaraize' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Choose where to display the key points.', 'summaraize' ); ?></p>
		<?php
	}

	/**
	 * Callback function for the Post Types setting field.
	 *
	 * @return void
	 */
	public function summaraize_post_types_callback() {
		// Ensure $selected_post_types is always an array.
		$selected_post_types = (array) get_option( 'summaraize_post_types', array( 'post' ) );
		$post_types          = get_post_types( array( 'public' => true ), 'names', 'and' );
		unset( $post_types['attachment'] );

		echo '<p>' . esc_html__( 'Select which post types SummarAIze should be enabled on:', 'summaraize' ) . '</p>';
		echo '<p><em>' . esc_html__( 'Custom post types must have the editor enabled.', 'summaraize' ) . '</em></p>';

		foreach ( $post_types as $post_type ) {
			$checked         = in_array( $post_type, $selected_post_types, true ) ? 'checked' : '';
			$post_type_label = str_replace( '_', ' ', ucwords( $post_type ) );

			echo '<div class="summaraize-toggle-wrapper">';
			echo '<label class="toggle-switch">';
			echo '<input type="checkbox" name="summaraize_post_types[]" value="' . esc_attr( $post_type ) . '" ' . esc_attr( $checked ) . '>';
			echo '<span class="slider"></span>';
			echo '</label>';
			echo '<span class="post-type-label">' . esc_html( $post_type_label ) . '</span>';
			echo '</div>';
		}
	}





	/**
	 * Callback for the settings section.
	 */
	public function summaraize_settings_section_callback() {
		echo '<p>' . esc_html__( 'Configure the settings for the SummarAIze Pro plugin.', 'summaraize' ) . '</p>';
	}



	/**
	 * Callback for the Display Mode field.
	 */
	public function summaraize_display_mode_callback() {
		$value = get_option( 'summaraize_display_mode', 'light' );
		?>
		<select id="summaraize_display_mode" name="summaraize_display_mode">
			<option value="light" <?php selected( $value, 'light' ); ?>><?php esc_html_e( 'Light', 'summaraize' ); ?></option>
			<option value="dark" <?php selected( $value, 'dark' ); ?>><?php esc_html_e( 'Dark', 'summaraize' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Choose the display mode for the key points.', 'summaraize' ); ?></p>
		<?php
	}

	/**
	 * Handles AJAX requests for saving SummarAIze settings or post meta.
	 *
	 * This method is used to save options or metadata dynamically via AJAX.
	 * It ensures security, validates inputs, and handles both option saving
	 * and specific post meta updates.
	 *
	 * @return void Outputs JSON success or error response.
	 */
	public function summaraize_auto_save() {
	    // Check AJAX nonce for security.
	    check_ajax_referer( 'summaraize_ajax_nonce', 'nonce' );

	    // Verify the user has the appropriate capability.
	    if ( ! current_user_can( 'manage_options' ) ) {
	        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'summaraize' ) ) );
	    }

	    // Ensure 'field_name' is set in the request.
	    if ( empty( $_POST['field_name'] ) ) {
	        wp_send_json_error( array( 'message' => __( 'Missing field name.', 'summaraize' ) ) );
	    }

	    // Sanitize the field name.
	    $field_name = sanitize_text_field( wp_unslash( $_POST['field_name'] ) );

	    // Handle special case for 'summaraize_points_sorted'.
	    if ( 'summaraize_points_sorted' === $field_name ) {
	        $this->handle_points_sorted();
	        return;
	    }

	    // Define allowed option keys for settings.
	    $allowed_options = array(
	        'summaraize_openai_api_key',
	        'summaraize_post_types',
	        'summaraize_assistant_id',
	        'summaraize_widget_title',
	        'summaraize_display_position',
	        'summaraize_display_mode',
	        'summaraize_button_style',
	        'summaraize_button_color',
	        'summaraize_list_type',
	        'summaraize_prompt_type',
	        'summaraize_custom_prompt',
	        'summaraize_ai_model',
	        'summaraize_ai_provider',
	        'summaraize_google_gemini_api_key',
	    );

	    // Sanitize and validate the option key.
	    $option_key = sanitize_key( str_replace( '[]', '', $field_name ) );

	    if ( ! in_array( $option_key, $allowed_options, true ) ) {
	        wp_send_json_error( array( 'message' => __( 'Invalid option key.', 'summaraize' ) ) );
	    }

	    // Sanitize field value.
	    if ( isset( $_POST['field_value'] ) && is_array( $_POST['field_value'] ) ) {
	        // Sanitize each element if value is an array.
	        $field_value = array_map( 'sanitize_text_field', wp_unslash( $_POST['field_value'] ) );
	    } else {
	        // Otherwise, sanitize as a string.
	        $field_value = isset( $_POST['field_value'] ) ? sanitize_text_field( wp_unslash( $_POST['field_value'] ) ) : '';
	    }

	    // Save the option and return the result.
	    if ( update_option( $option_key, $field_value ) || get_option( $option_key ) === $field_value ) {
	        // Check if the saved option is the AI provider
	        if ( $option_key === 'summaraize_ai_provider' ) {
	            // Refresh the page if the AI provider is changed
	            wp_send_json_success( array(
	                'message' => __( 'Option saved. Refreshing page...', 'summaraize' ),
	                'refresh' => true, // Add a flag to indicate page refresh
	            ) );
	        } else {
	            // Normal success response for other options
	            wp_send_json_success( array( 'message' => __( 'Option saved.', 'summaraize' ) ) );
	        }
	    } else {
	        wp_send_json_error( array( 'message' => __( 'Failed to save option.', 'summaraize' ) ) );
	    }
	}

	/**
	 * Handles saving of sorted points for a specific post.
	 *
	 * This method is triggered when 'summaraize_points_sorted' is the field name.
	 *
	 * @return void Outputs JSON success or error response.
	 */
	private function handle_points_sorted() {
	    // Ensure required fields are present.
	    if ( empty( $_POST['post_id'] ) || empty( $_POST['field_value'] ) ) {
	        wp_send_json_error( array( 'message' => __( 'Missing post ID or points data.', 'summaraize' ) ) );
	    }

	    // Sanitize post ID.
	    $post_id = absint( $_POST['post_id'] );

	    // Sanitize and decode the points data.
	    $sanitized_json = sanitize_text_field( wp_unslash( $_POST['field_value'] ) );
	    $sorted_points  = json_decode( $sanitized_json, true );

	    if ( ! is_array( $sorted_points ) ) {
	        wp_send_json_error( array( 'message' => __( 'Invalid points data.', 'summaraize' ) ) );
	    }

	    // Sanitize each point in the array.
	    $sanitized_points = array_map( 'sanitize_text_field', $sorted_points );

	    // Save the sorted points as post meta.
	    if ( update_post_meta( $post_id, 'summaraize_points', $sanitized_points ) ) {
	        wp_send_json_success( array( 'message' => __( 'Points reordered and saved.', 'summaraize' ) ) );
	    } else {
	        wp_send_json_error( array( 'message' => __( 'Failed to save points.', 'summaraize' ) ) );
	    }
	}

}