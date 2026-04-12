<?php
/**
 * Class Summaraize_Admin_Metabox
 *
 * Handles the addition and rendering of the meta box, and saving the meta box data.
 *
 * @since 1.0.0
 * @package Summaraize
 */

/**
 * Class Summaraize_Admin_Metabox
 */
class Summaraize_Admin_Metabox {

	/**
	 * Add meta box to post edit screen.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_box() {
		$allowed_ai_providers = array( 'openai', 'google_gemini' );
		$ai_provider          = get_option( 'summaraize_ai_provider', 'openai' );

		if ( ! in_array( $ai_provider, $allowed_ai_providers, true ) ) {
			$ai_provider = 'openai';
			update_option( 'summaraize_ai_provider', $ai_provider );
		}

		$is_provider_configured = false;
		if ( 'openai' === $ai_provider ) {
			$api_key                = get_option( 'summaraize_openai_api_key' );
			$is_provider_configured = ! empty( $api_key ) && Summaraize_OpenAI_Settings::validate_openai_api_key( $api_key );
		} elseif ( 'google_gemini' === $ai_provider ) {
			$api_key                = get_option( 'summaraize_google_gemini_api_key' );
			$is_provider_configured = ! empty( $api_key ) && Summaraize_Google_Gemini_Settings::validate_google_gemini_api_key( $api_key );
		}

		if ( ! $is_provider_configured ) {
			return;
		}

		$post_types = get_option( 'summaraize_post_types', array() );

		// Ensure $post_types is an array.
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		foreach ( $post_types as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				add_meta_box(
					'summaraize_meta_box',
					__( 'SummarAIize', 'summaraize' ),
					array( $this, 'render_meta_box' ),
					$post_type,
					'side',
					'default'
				);
			}
		}
	}

	/**
	 * Render the meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The current post.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'summaraize_meta_box', 'summaraize_meta_box_nonce' );

		$summaraize_points_meta = get_post_meta( $post->ID, 'summaraize_points', true );
		$summaraize_points      = ! empty( $summaraize_points_meta ) ? $summaraize_points_meta : array_fill( 0, 5, '' );

		$override_settings = get_post_meta( $post->ID, 'summaraize_override_settings', true );
		$view              = get_post_meta( $post->ID, 'summaraize_view', true );
		$mode              = get_post_meta( $post->ID, 'summaraize_mode', true );
		$widget_title      = get_post_meta( $post->ID, 'summaraize_widget_title', true );
		$button_style      = get_post_meta( $post->ID, 'summaraize_button_style', true );
		$button_color      = get_post_meta( $post->ID, 'summaraize_button_color', true );
		$list_type         = get_post_meta( $post->ID, 'summaraize_list_type', true );

		echo '<button id="generate-summaraize-button" type="button">';
		echo '<div class="summaraize-spinner" style="display: none;"></div>';
		echo '<span class="button-text">' . esc_html__( 'Generate Top 5 Points', 'summaraize' ) . '</span>';
		echo '</button>';

		echo '<div id="summaraize-points-list" class="list-group" style="list-style: none; padding: 0;">';

		for ( $i = 0; $i < 5; $i++ ) {
			$point = isset( $summaraize_points[ $i ] ) ? $summaraize_points[ $i ] : '';
			$point = preg_replace( '/\*\*(.*?)\*\*/', '$1', $point );

			echo '<div class="summaraize-input-container" style="margin-bottom: 10px; display: flex; align-items: center;">';
			echo '<span class="dashicons dashicons-menu" style="margin-right: 10px; cursor: move;"></span>';
			echo '<input id="summaraize_points_' . esc_attr( $i + 1 ) . '" style="flex: 1; width: 100%;" type="text" name="summaraize_points[]" value="' . esc_attr( $point ) . '" placeholder="' . esc_attr__( 'Empty points are not shown.', 'summaraize' ) . '" />';
			echo '<button type="button" class="remove-point dashicons dashicons-trash" style="flex: 0 0 auto; margin-left: 10px; background: none; border: none; cursor: pointer; font-size: 20px; padding: 0; line-height: 1;" data-point-id="summaraize_points_' . esc_attr( $i + 1 ) . '"></button>';
			echo '</div>';
		}

		echo '</div>';

		// Override settings checkbox.
		echo '<p><input type="checkbox" id="summaraize_override_settings" name="summaraize_override_settings" value="1"' . checked( 1, $override_settings, false ) . ' />';
		echo '<label for="summaraize_override_settings">' . esc_html__( 'Override Settings', 'summaraize' ) . '</label></p>';

		// Override options.
		echo '<div id="summaraize_override_options" style="' . ( $override_settings ? '' : 'display:none;' ) . '">';

		// View dropdown.
		echo '<p><label for="summaraize_view">' . esc_html__( 'View:', 'summaraize' ) . '</label>';
		echo '<select id="summaraize_view" name="summaraize_view">';
		echo '<option value="above"' . selected( 'above', $view, false ) . '>' . esc_html__( 'Above', 'summaraize' ) . '</option>';
		echo '<option value="below"' . selected( 'below', $view, false ) . '>' . esc_html__( 'Below', 'summaraize' ) . '</option>';
		echo '<option value="popup"' . selected( 'popup', $view, false ) . '>' . esc_html__( 'Popup', 'summaraize' ) . '</option>';
		echo '</select></p>';

		// Mode dropdown.
		echo '<p><label for="summaraize_mode">' . esc_html__( 'Mode:', 'summaraize' ) . '</label>';
		echo '<select id="summaraize_mode" name="summaraize_mode">';
		echo '<option value="light"' . selected( 'light', $mode, false ) . '>' . esc_html__( 'Light', 'summaraize' ) . '</option>';
		echo '<option value="dark"' . selected( 'dark', $mode, false ) . '>' . esc_html__( 'Dark', 'summaraize' ) . '</option>';
		echo '</select></p>';

		// Widget title.
		echo '<p><label for="summaraize_widget_title">' . esc_html__( 'Widget Title:', 'summaraize' ) . '</label>';
		echo '<input type="text" id="summaraize_widget_title" name="summaraize_widget_title" value="' . esc_attr( $widget_title ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the title for the widget.', 'summaraize' ) . '</p></p>';

		// Button style.
		$button_styles_background = array(
			'flat'        => __( 'Flat', 'summaraize' ),
			'rounded'     => __( 'Rounded', 'summaraize' ),
			'angled'      => __( 'Angled', 'summaraize' ),
			'bubbly'      => __( 'Pillow', 'summaraize' ),
			'material'    => __( 'Material', 'summaraize' ),
			'neumorphism' => __( 'Neumorphism', 'summaraize' ),
		);

		$button_styles_no_background = array(
			'apple'  => __( 'Apple', 'summaraize' ),
			'google' => __( 'Google', 'summaraize' ),
		);

		echo '<p class="button-style-wrapper"><label for="summaraize_button_style">' . esc_html__( 'Button Style:', 'summaraize' ) . '</label>';
		echo '<select id="summaraize_button_style" name="summaraize_button_style">';
		echo '<optgroup label="' . esc_attr__( 'Styles with Selected Background Color', 'summaraize' ) . '">';
		foreach ( $button_styles_background as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $button_style, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</optgroup>';
		echo '<optgroup label="' . esc_attr__( 'Styles without Selected Background Color', 'summaraize' ) . '">';
		foreach ( $button_styles_no_background as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $button_style, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</optgroup>';
		echo '</select>';
		echo '<p class="description button-style-description">' . esc_html__( 'Choose the style for the button.', 'summaraize' ) . '</p></p>';

		// Button color.
		echo '<p class="button-color-wrapper"><label for="summaraize_button_color">' . esc_html__( 'Button Color:', 'summaraize' ) . '</label>';
		echo '<input type="color" id="summaraize_button_color" name="summaraize_button_color" value="' . esc_attr( $button_color ) . '" />';
		echo '<p class="description button-color-description">' . esc_html__( 'Choose the color for the button.', 'summaraize' ) . '</p></p>';

		// List type dropdown.
		echo '<p><label for="summaraize_list_type">' . esc_html__( 'List Type:', 'summaraize' ) . '</label>';
		echo '<select id="summaraize_list_type" name="summaraize_list_type">';
		echo '<option value="unordered"' . selected( 'unordered', $list_type, false ) . '>' . esc_html__( 'Bullet List', 'summaraize' ) . '</option>';
		echo '<option value="ordered"' . selected( 'ordered', $list_type, false ) . '>' . esc_html__( 'Ordered List', 'summaraize' ) . '</option>';
		echo '</select></p>';

		echo '</div>';

		// Modal HTML.
		echo '
	    <!-- Modal Overlay -->
	    <div id="summaraize-error-modal" class="summaraize-modal" role="dialog" aria-labelledby="summaraize-modal-title" aria-modal="true" style="display: none;">
	        <div class="summaraize-modal-content">
	            <span class="summaraize-close" aria-label="Close Modal">&times;</span>
	            <div id="summaraize-modal-message" tabindex="0"></div>
	        </div>
	    </div>';
	}

	/**
	 * Save the meta box data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save_summaraize_points( $post_id ) {
		// Verify nonce.
		if ( ! isset( $_POST['summaraize_meta_box_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['summaraize_meta_box_nonce'] ) ), 'summaraize_meta_box' ) ) {
			return;
		}

		// Check for autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check post type permissions.
		if ( isset( $_POST['post_type'] ) && 'page' === sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save 'summaraize_points' directly if set.
		if ( isset( $_POST['summaraize_points'] ) ) {
			$raw_points_post = null;

			if ( isset( $_POST['summaraize_points'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unsanitized input is immediately sanitized below.
				$raw_points_post = wp_unslash( $_POST['summaraize_points'] );
			}

			if ( is_array( $raw_points_post ) ) {
				$allowed_tags = array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
						'rel'    => array(),
					),
				);

				$summaraize_points = array_map(
					function ( $point ) use ( $allowed_tags ) {
						return wp_kses( $point, $allowed_tags );
					},
					$raw_points_post
				);

				$summaraize_points = array_filter( $summaraize_points, 'strlen' );

				update_post_meta( $post_id, 'summaraize_points', $summaraize_points );
			}
		}

		// Save override settings and other options.
		$override_settings = isset( $_POST['summaraize_override_settings'] ) ? 1 : 0;
		update_post_meta( $post_id, 'summaraize_override_settings', $override_settings );

		if ( $override_settings ) {
			$view         = isset( $_POST['summaraize_view'] ) ? sanitize_text_field( wp_unslash( $_POST['summaraize_view'] ) ) : '';
			$mode         = isset( $_POST['summaraize_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['summaraize_mode'] ) ) : '';
			$widget_title = isset( $_POST['summaraize_widget_title'] ) ? sanitize_text_field( wp_unslash( $_POST['summaraize_widget_title'] ) ) : '';
			$button_style = isset( $_POST['summaraize_button_style'] ) ? sanitize_text_field( wp_unslash( $_POST['summaraize_button_style'] ) ) : '';
			$button_color = isset( $_POST['summaraize_button_color'] ) ? sanitize_text_field( wp_unslash( $_POST['summaraize_button_color'] ) ) : '';
			$list_type    = isset( $_POST['summaraize_list_type'] ) ? sanitize_text_field( wp_unslash( $_POST['summaraize_list_type'] ) ) : '';

			update_post_meta( $post_id, 'summaraize_view', $view );
			update_post_meta( $post_id, 'summaraize_mode', $mode );
			update_post_meta( $post_id, 'summaraize_widget_title', $widget_title );
			update_post_meta( $post_id, 'summaraize_button_style', $button_style );
			update_post_meta( $post_id, 'summaraize_button_color', $button_color );
			update_post_meta( $post_id, 'summaraize_list_type', $list_type );
		} else {
			delete_post_meta( $post_id, 'summaraize_view' );
			delete_post_meta( $post_id, 'summaraize_mode' );
			delete_post_meta( $post_id, 'summaraize_widget_title' );
			delete_post_meta( $post_id, 'summaraize_button_style' );
			delete_post_meta( $post_id, 'summaraize_button_color' );
			delete_post_meta( $post_id, 'summaraize_list_type' );
		}
	}
}
