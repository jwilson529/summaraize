<?php
/**
 * Admin Settings for SummarAIze.
 *
 * This file contains the class definition for managing the admin settings page
 * for the SummarAIze plugin. It includes functions for registering settings,
 * rendering settings fields, handling AJAX saves, and more.
 *
 * @package Summaraize
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Summaraize_Admin_Settings
 *
 * Manages the admin settings page for the SummarAIze plugin.
 *
 * @since 1.0.0
 */
class Summaraize_Admin_Settings {

	/**
	 * Register the plugin settings page.
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	public function summaraize_options_page() {
		?>
		<div id="summaraize" class="wrap">
			<h1><?php esc_html_e( 'SummarAIze Settings', 'summaraize' ); ?></h1>

			<!-- Only Main Settings tab is displayed -->
			<h2 class="nav-tab-wrapper">
				<a href="#main-settings" class="nav-tab nav-tab-active"><?php esc_html_e( 'Main Settings', 'summaraize' ); ?></a>
			</h2>

			<form class="summaraize-settings-form" method="post" action="options.php">
				<?php settings_fields( 'summaraize_settings' ); ?>
				<div id="main-settings" class="tab-content">
					<?php do_settings_sections( 'summaraize_settings' ); ?>
				</div>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Display admin notices for settings.
	 *
	 * @return void
	 */
	public function display_admin_notices() {
		settings_errors();
	}

	/**
	 * Register the plugin settings.
	 *
	 * @return void
	 */
	public function summaraize_register_settings() {
		// Instantiate settings classes.
		$openai_settings = new Summaraize_OpenAI_Settings();
		$gemini_settings = new Summaraize_Google_Gemini_Settings();

		// Register the AI provider setting.
		add_settings_field(
			'summaraize_ai_provider',
			__( 'AI Provider', 'summaraize' ),
			array( $this, 'summaraize_ai_provider_callback' ),
			'summaraize_settings',
			'summaraize_settings_section'
		);

		// Register the API key settings with sanitization.
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
		// Add the main settings section.
		add_settings_section(
			'summaraize_settings_section',
			__( 'SummarAIze Settings', 'summaraize' ),
			array( $this, 'summaraize_settings_section_callback' ),
			'summaraize_settings'
		);

		$allowed_ai_providers = array( 'openai', 'google_gemini' );
		$ai_provider          = get_option( 'summaraize_ai_provider', 'openai' );

		if ( ! in_array( $ai_provider, $allowed_ai_providers, true ) ) {
			$ai_provider = 'openai';
			update_option( 'summaraize_ai_provider', $ai_provider );
		}

		if ( 'openai' === $ai_provider ) {
			// OpenAI-specific settings.
			add_settings_field(
				'summaraize_openai_api_key',
				__( 'OpenAI API Key', 'summaraize' ),
				array( $openai_settings, 'summaraize_openai_api_key_callback' ),
				'summaraize_settings',
				'summaraize_settings_section',
				array( 'label_for' => 'summaraize_openai_api_key' )
			);

			$open_api_key = get_option( 'summaraize_openai_api_key' );
			if ( ! empty( $open_api_key ) && Summaraize_OpenAI_Settings::validate_openai_api_key( $open_api_key ) ) {
				// Register main settings fields.
				$this->register_summaraize_main_settings_fields();
			} else {
				add_settings_error(
					'summaraize_openai_api_key',
					'invalid-api-key',
					wp_kses_post(
						__( 'The OpenAI API key is invalid. Please enter a valid API key in the <a href="options-general.php?page=summaraize-settings">SummarAIze settings</a> to use SummarAIze.', 'summaraize' )
					),
					'error'
				);
			}
		} elseif ( 'google_gemini' === $ai_provider ) {
			// Google Gemini-specific settings.
			add_settings_field(
				'summaraize_google_gemini_api_key',
				__( 'Google Gemini API Key', 'summaraize' ),
				array( $gemini_settings, 'summaraize_google_gemini_api_key_callback' ),
				'summaraize_settings',
				'summaraize_settings_section',
				array( 'label_for' => 'summaraize_google_gemini_api_key' )
			);
			$gemini_api_key = get_option( 'summaraize_google_gemini_api_key' );
			if ( ! empty( $gemini_api_key ) && Summaraize_Google_Gemini_Settings::validate_google_gemini_api_key( $gemini_api_key ) ) {
				$this->register_summaraize_main_settings_fields();
			} else {
				add_settings_error(
					'summaraize_google_gemini_api_key',
					'invalid-api-key',
					wp_kses_post(
						__( 'The Google Gemini API key is invalid. Please enter a valid API key in the <a href="options-general.php?page=summaraize-settings">SummarAIze settings</a> to use SummarAIze.', 'summaraize' )
					),
					'error'
				);
			}
		} else {
			add_settings_error(
				'summaraize_ai_provider',
				'invalid-ai-provider',
				__( 'Invalid AI provider selected. Defaulting to OpenAI.', 'summaraize' ),
				'error'
			);
			update_option( 'summaraize_ai_provider', 'openai' );
			$open_api_key = get_option( 'summaraize_openai_api_key' );
			if ( ! empty( $open_api_key ) && Summaraize_OpenAI_Settings::validate_openai_api_key( $open_api_key ) ) {
				$this->register_summaraize_main_settings_fields();
			}
		}
	}

	/**
	 * Register the main settings fields.
	 *
	 * These include post types, widget title, display position, display mode,
	 * button style, button color, and list type.
	 *
	 * @return void
	 */
	private function register_summaraize_main_settings_fields() {
		// Register main plugin settings.
		register_setting(
			'summaraize_settings',
			'summaraize_post_types',
			array(
				'sanitize_callback' => array( $this, 'sanitize_post_types' ),
			)
		);

		register_setting(
			'summaraize_settings',
			'summaraize_widget_title',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'summaraize_settings',
			'summaraize_display_position',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'summaraize_settings',
			'summaraize_display_mode',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'summaraize_settings',
			'summaraize_button_style',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'summaraize_settings',
			'summaraize_button_color',
			array(
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);

		register_setting(
			'summaraize_settings',
			'summaraize_list_type',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

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

	/**
	 * Sanitize the post types array.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array Sanitized array of post types.
	 */
	public function sanitize_post_types( $value ) {
		if ( ! is_array( $value ) ) {
			return array(); // Return an empty array if the input isn’t an array.
		}
		return array_map( 'sanitize_text_field', $value ); // Sanitize each element.
	}

	/**
	 * Callback for the Post Types setting field.
	 *
	 * Outputs a list of checkboxes for selecting which post types
	 * SummarAIze should be enabled on. Custom post types must have the
	 * editor enabled.
	 *
	 * @return void
	 */
	public function summaraize_post_types_callback() {
		// Retrieve the currently selected post types; default to 'post'.
		$selected_post_types = (array) get_option( 'summaraize_post_types', array( 'post' ) );

		// Get all public post types.
		$post_types = get_post_types( array( 'public' => true ), 'names', 'and' );

		// Remove 'attachment' as it's not relevant.
		unset( $post_types['attachment'] );

		// Display instructions.
		echo '<p>' . esc_html__( 'Select which post types SummarAIze should be enabled on:', 'summaraize' ) . '</p>';
		echo '<p><em>' . esc_html__( 'Custom post types must have the editor enabled.', 'summaraize' ) . '</em></p>';

		// Begin container for the checkboxes.
		echo '<div class="summaraize-post-types-container">';

		// Loop through each post type and display a checkbox.
		foreach ( $post_types as $post_type ) {
			$checked         = in_array( $post_type, $selected_post_types, true ) ? 'checked' : '';
			$post_type_label = str_replace( '_', ' ', ucwords( $post_type ) );

			echo '<div class="summaraize-toggle-wrapper">';
				echo '<label class="toggle-switch">';
					printf(
						'<input type="checkbox" name="summaraize_post_types[]" value="%s" %s>',
						esc_attr( $post_type ),
						esc_attr( $checked )
					);
					echo '<span class="slider"></span>';
				echo '</label>';
				printf(
					'<span class="post-type-label">%s</span>',
					esc_html( $post_type_label )
				);
			echo '</div>';
		}

		// End container.
		echo '</div>';
	}

	/**
	 * Callback for the Display Mode setting field.
	 *
	 * Outputs a dropdown for selecting the display mode (e.g., Light or Dark)
	 * for the key points.
	 *
	 * @return void
	 */
	public function summaraize_display_mode_callback() {
		// Retrieve the current display mode; default to 'light'.
		$value = get_option( 'summaraize_display_mode', 'light' );
		?>
		<select id="summaraize_display_mode" name="summaraize_display_mode">
			<option value="light" <?php selected( $value, 'light' ); ?>>
				<?php esc_html_e( 'Light', 'summaraize' ); ?>
			</option>
			<option value="dark" <?php selected( $value, 'dark' ); ?>>
				<?php esc_html_e( 'Dark', 'summaraize' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Choose the display mode for the key points.', 'summaraize' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the AI provider selection dropdown.
	 *
	 * @return void
	 */
	public function summaraize_ai_provider_callback() {
		$allowed_ai_providers = array( 'openai', 'google_gemini' );
		$ai_provider          = get_option( 'summaraize_ai_provider', 'openai' );

		if ( ! in_array( $ai_provider, $allowed_ai_providers, true ) ) {
			$ai_provider = 'openai';
			update_option( 'summaraize_ai_provider', $ai_provider );
		}

		?>
		<select name="summaraize_ai_provider" id="summaraize_ai_provider">
			<option value="openai" <?php selected( $ai_provider, 'openai' ); ?>>
				<?php esc_html_e( 'OpenAI', 'summaraize' ); ?>
			</option>
			<option value="google_gemini" <?php selected( $ai_provider, 'google_gemini' ); ?>>
				<?php esc_html_e( 'Google Gemini', 'summaraize' ); ?>
			</option>
		</select>
		<?php
	}

	/**
	 * Render the Google Gemini API key input field.
	 *
	 * @return void
	 */
	public function summaraize_google_gemini_api_key_callback() {
		$ai_provider = get_option( 'summaraize_ai_provider', 'openai' );
		if ( 'google_gemini' === $ai_provider ) {
			$value = get_option( 'summaraize_google_gemini_api_key', '' );
			?>
			<input type="password" name="summaraize_google_gemini_api_key" value="<?php echo esc_attr( $value ); ?>" />
			<p class="description">
				<?php
				echo wp_kses_post(
					__( 'Get your Google Gemini API key from <a href="https://aistudio.google.com/" target="_blank" rel="noopener noreferrer">Google AI Studio</a>.', 'summaraize' )
				);
				?>
			</p>
			<?php
		}
	}

	/**
	 * Callback for the OpenAI API key field.
	 *
	 * @return void
	 */
	public function summaraize_openai_api_key_callback() {
		$value = get_option( 'summaraize_openai_api_key', '' );
		?>
		<input type="password" name="summaraize_openai_api_key" id="summaraize_openai_api_key" value="<?php echo esc_attr( $value ); ?>" />
		<p class="description">
			<?php
			printf(
				wp_kses_post( __( 'Get your OpenAI API Key <a href="https://beta.openai.com/signup/">here</a>.', 'summaraize' ) )
			);
			?>
		</p>
		<?php
	}

	/**
	 * Callback for the List Type setting field.
	 *
	 * Outputs a dropdown for choosing an ordered or unordered list.
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
	 * Callback for the widget title field.
	 *
	 * @return void
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
	 *
	 * @return void
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
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_style, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Choose the style for the button.', 'summaraize' ); ?></p>
		<?php
	}

	/**
	 * Callback for the button color field.
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	public function summaraize_display_position_callback() {
		$selected_position = get_option( 'summaraize_display_position', 'above' );
		?>
		<select name="summaraize_display_position" id="summaraize_display_position">
			<option value="above" <?php selected( $selected_position, 'above' ); ?>>
				<?php esc_html_e( 'Above Content', 'summaraize' ); ?>
			</option>
			<option value="below" <?php selected( $selected_position, 'below' ); ?>>
				<?php esc_html_e( 'Below Content', 'summaraize' ); ?>
			</option>
			<option value="popup" <?php selected( $selected_position, 'popup' ); ?>>
				<?php esc_html_e( 'Popup', 'summaraize' ); ?>
			</option>
		</select>
		<p class="description"><?php esc_html_e( 'Choose where to display the key points.', 'summaraize' ); ?></p>
		<?php
	}

	/**
	 * Callback for the settings section.
	 *
	 * @return void
	 */
	public function summaraize_settings_section_callback() {
		echo '<p>' . esc_html__( 'Configure SummarAIze with your OpenAI or Google Gemini API key.', 'summaraize' ) . '</p>';
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
		check_ajax_referer( 'summaraize_ajax_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'summaraize' ) ) );
		}
		if ( empty( $_POST['field_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing field name.', 'summaraize' ) ) );
		}
		$field_name = sanitize_text_field( wp_unslash( $_POST['field_name'] ) );
		if ( 'summaraize_points_sorted' === $field_name ) {
			$this->handle_points_sorted();
			return;
		}
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
		$option_key      = sanitize_key( str_replace( '[]', '', $field_name ) );
		if ( ! in_array( $option_key, $allowed_options, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid option key.', 'summaraize' ) ) );
		}
		if ( isset( $_POST['field_value'] ) && is_array( $_POST['field_value'] ) ) {
			$field_value = array_map( 'sanitize_text_field', wp_unslash( $_POST['field_value'] ) );
		} else {
			$field_value = isset( $_POST['field_value'] ) ? sanitize_text_field( wp_unslash( $_POST['field_value'] ) ) : '';
		}
		$success = update_option( $option_key, $field_value );
		wp_cache_flush();
		$current_value = get_option( $option_key, array() );
		if ( $success || $current_value === $field_value ) {
			if ( 'summaraize_ai_provider' === $option_key ) {
				wp_send_json_success(
					array(
						'message' => __( 'Option saved. Refreshing page...', 'summaraize' ),
						'refresh' => true,
					)
				);
			} else {
				wp_send_json_success( array( 'message' => __( 'Option saved.', 'summaraize' ) ) );
			}
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to save option.', 'summaraize' ) ) );
		}
	}

	/**
	 * Handle saving of sorted points for a specific post.
	 *
	 * Triggered when the field name is 'summaraize_points_sorted'.
	 *
	 * @return void JSON success or error response.
	 */
	private function handle_points_sorted() {
		// Check AJAX nonce.
		check_ajax_referer( 'summaraize_ajax_nonce', 'nonce' );

		// Ensure required data is provided.
		if ( empty( $_POST['post_id'] ) || empty( $_POST['field_value'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID or points data.', 'summaraize' ) ) );
		}

		$post_id        = absint( $_POST['post_id'] );
		$sanitized_json = sanitize_text_field( wp_unslash( $_POST['field_value'] ) );
		$sorted_points  = json_decode( $sanitized_json, true );

		if ( ! is_array( $sorted_points ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid points data.', 'summaraize' ) ) );
		}

		// Sanitize each point in the array while allowing anchor tags.
		$allowed_tags     = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
		);
		$sanitized_points = array_map(
			function ( $point ) use ( $allowed_tags ) {
				return wp_kses( $point, $allowed_tags );
			},
			$sorted_points
		);

		if ( update_post_meta( $post_id, 'summaraize_points', $sanitized_points ) ) {
			wp_send_json_success( array( 'message' => __( 'Points reordered and saved.', 'summaraize' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to save points.', 'summaraize' ) ) );
		}
	}
}
