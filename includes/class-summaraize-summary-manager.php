<?php
/**
 * Summary lifecycle management for Summaraize.
 *
 * @package Summaraize
 * @subpackage Summaraize/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles summary generation, status, and automation workflows.
 *
 * @since 1.4.0
 */
class Summaraize_Summary_Manager {

	/**
	 * Option name for publish-time generation.
	 */
	const OPTION_AUTO_GENERATE_MODE = 'summaraize_auto_generate_mode';

	/**
	 * Auto-generation disabled.
	 */
	const AUTO_GENERATE_OFF = 'off';

	/**
	 * Auto-generate when first publishing and no summary exists.
	 */
	const AUTO_GENERATE_MISSING_ON_PUBLISH = 'missing_on_publish';

	/**
	 * Bulk action slug for generating missing summaries.
	 */
	const BULK_ACTION_GENERATE = 'summaraize_generate_missing';

	/**
	 * Bulk action slug for forced regeneration.
	 */
	const BULK_ACTION_REGENERATE = 'summaraize_regenerate_selected';

	/**
	 * Summary state when no points are present.
	 */
	const STATUS_MISSING = 'missing';

	/**
	 * Summary state when generated content matches the post source.
	 */
	const STATUS_CURRENT = 'current';

	/**
	 * Summary state when the post source changed after generation.
	 */
	const STATUS_STALE = 'stale';

	/**
	 * Summary state when the saved points differ from the last generated version.
	 */
	const STATUS_EDITED = 'edited';

	/**
	 * Post meta key for the last generated timestamp.
	 */
	const META_GENERATED_AT = 'summaraize_generated_at';

	/**
	 * Post meta key for the content hash used during generation.
	 */
	const META_SOURCE_HASH = 'summaraize_source_hash';

	/**
	 * Post meta key for the provider used during generation.
	 */
	const META_GENERATION_PROVIDER = 'summaraize_generation_provider';

	/**
	 * Post meta key for the model used during generation.
	 */
	const META_GENERATION_MODEL = 'summaraize_generation_model';

	/**
	 * Post meta key for the normalized generated points hash.
	 */
	const META_GENERATED_POINTS_HASH = 'summaraize_generated_points_hash';

	/**
	 * Post meta key for the manual edit flag.
	 */
	const META_MANUALLY_EDITED = 'summaraize_manually_edited';

	/**
	 * Cron hook used for delayed publish-time generation.
	 */
	const AUTO_GENERATE_EVENT = 'summaraize_generate_summary_for_post';

	/**
	 * Register list-table bulk actions.
	 *
	 * @since 1.4.0
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public function register_bulk_actions( $actions ) {
		$actions[ self::BULK_ACTION_GENERATE ]   = __( 'Generate summaries', 'summaraize' );
		$actions[ self::BULK_ACTION_REGENERATE ] = __( 'Regenerate summaries', 'summaraize' );

		return $actions;
	}

	/**
	 * Handle list-table bulk actions.
	 *
	 * @since 1.4.0
	 * @param string $redirect_to Redirect URL.
	 * @param string $doaction    Current bulk action.
	 * @param array  $post_ids    Selected post IDs.
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_to, $doaction, $post_ids ) {
		if ( ! in_array( $doaction, array( self::BULK_ACTION_GENERATE, self::BULK_ACTION_REGENERATE ), true ) ) {
			return $redirect_to;
		}

		$generated_count = 0;
		$skipped_count   = 0;
		$failed_count    = 0;

		foreach ( (array) $post_ids as $post_id ) {
			$post_id = absint( $post_id );

			if ( ! $post_id || ! self::is_post_supported( $post_id ) ) {
				++$skipped_count;
				continue;
			}

			if ( self::BULK_ACTION_GENERATE === $doaction && self::STATUS_MISSING !== self::get_summary_status( $post_id ) ) {
				++$skipped_count;
				continue;
			}

			$result = self::generate_summary_for_post( $post_id );
			if ( is_wp_error( $result ) ) {
				++$failed_count;
				continue;
			}

			++$generated_count;
		}

		return add_query_arg(
			array(
				'summaraize_bulk_action' => rawurlencode( $doaction ),
				'summaraize_generated'   => $generated_count,
				'summaraize_skipped'     => $skipped_count,
				'summaraize_failed'      => $failed_count,
			),
			$redirect_to
		);
	}

	/**
	 * Render an admin notice for bulk-generation results.
	 *
	 * @since 1.4.0
	 * @return void
	 */
	public function maybe_render_bulk_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only notice query arguments are sanitized before use.
		if ( ! is_admin() || ! isset( $_GET['summaraize_bulk_action'] ) ) {
			return;
		}

		$current_screen = get_current_screen();
		if ( ! $current_screen || 'edit' !== $current_screen->base ) {
			return;
		}

		$generated_count = isset( $_GET['summaraize_generated'] ) ? absint( $_GET['summaraize_generated'] ) : 0;
		$skipped_count   = isset( $_GET['summaraize_skipped'] ) ? absint( $_GET['summaraize_skipped'] ) : 0;
		$failed_count    = isset( $_GET['summaraize_failed'] ) ? absint( $_GET['summaraize_failed'] ) : 0;
		$action          = sanitize_key( wp_unslash( $_GET['summaraize_bulk_action'] ) );
		$action_label    = self::BULK_ACTION_REGENERATE === $action ? __( 'regenerated', 'summaraize' ) : __( 'generated', 'summaraize' );

		$parts = array();

		if ( $generated_count > 0 ) {
			/* translators: %s: number of summaries. */
			$parts[] = sprintf( __( '%1$s summaries %2$s.', 'summaraize' ), number_format_i18n( $generated_count ), $action_label );
		}

		if ( $skipped_count > 0 ) {
			/* translators: %s: number of skipped posts. */
			$parts[] = sprintf( __( '%s posts skipped.', 'summaraize' ), number_format_i18n( $skipped_count ) );
		}

		if ( $failed_count > 0 ) {
			/* translators: %s: number of failed posts. */
			$parts[] = sprintf( __( '%s posts failed.', 'summaraize' ), number_format_i18n( $failed_count ) );
		}

		if ( empty( $parts ) ) {
			return;
		}

		$notice_class = $failed_count > 0 ? 'notice notice-warning is-dismissible' : 'notice notice-success is-dismissible';

		echo '<div class="' . esc_attr( $notice_class ) . '"><p>' . esc_html( implode( ' ', $parts ) ) . '</p></div>';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Add the summary status column to supported list tables.
	 *
	 * @since 1.4.0
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_status_column( $columns ) {
		$updated_columns = array();

		foreach ( $columns as $column_key => $label ) {
			if ( 'date' === $column_key ) {
				$updated_columns['summaraize_status'] = __( 'Summary Status', 'summaraize' );
			}

			$updated_columns[ $column_key ] = $label;
		}

		if ( ! isset( $updated_columns['summaraize_status'] ) ) {
			$updated_columns['summaraize_status'] = __( 'Summary Status', 'summaraize' );
		}

		return $updated_columns;
	}

	/**
	 * Render the summary status list-table column.
	 *
	 * @since 1.4.0
	 * @param string $column_name Column identifier.
	 * @param int    $post_id     Current post ID.
	 * @return void
	 */
	public function render_status_column( $column_name, $post_id ) {
		if ( 'summaraize_status' !== $column_name ) {
			return;
		}

		echo wp_kses_post( self::get_status_badge_html( $post_id ) );

		$provenance = self::get_summary_provenance( $post_id );
		if ( ! empty( $provenance['provider_label'] ) ) {
			echo '<div class="description">';
			echo esc_html( $provenance['provider_label'] );

			if ( ! empty( $provenance['model'] ) ) {
				echo '<br />';
				echo esc_html( $provenance['model'] );
			}

			echo '</div>';
		}
	}

	/**
	 * Schedule publish-time generation when the post first becomes published.
	 *
	 * @since 1.4.0
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 * @return void
	 */
	public function schedule_auto_generate_on_publish( $new_status, $old_status, $post ) {
		if ( self::AUTO_GENERATE_MISSING_ON_PUBLISH !== self::get_auto_generate_mode() ) {
			return;
		}

		if ( ! $post instanceof WP_Post || 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		if ( ! self::is_post_supported( $post ) || self::has_summary( $post->ID ) ) {
			return;
		}

		if ( false === wp_next_scheduled( self::AUTO_GENERATE_EVENT, array( $post->ID ) ) ) {
			wp_schedule_single_event( time() + 5, self::AUTO_GENERATE_EVENT, array( $post->ID ) );
		}
	}

	/**
	 * Run the delayed publish-time generation worker.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID to process.
	 * @return void
	 */
	public function run_scheduled_auto_generate( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || ! self::is_post_supported( $post_id ) || self::has_summary( $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
			return;
		}

		$result = self::generate_summary_for_post( $post_id );
		if ( is_wp_error( $result ) ) {
			Summaraize_Logger::warning(
				'Automatic publish-time summary generation failed.',
				array(
					'post_id'       => $post_id,
					'error_message' => $result->get_error_message(),
				)
			);
		}
	}

	/**
	 * Return the configured auto-generation mode.
	 *
	 * @since 1.4.0
	 * @return string
	 */
	public static function get_auto_generate_mode() {
		return self::sanitize_auto_generate_mode( get_option( self::OPTION_AUTO_GENERATE_MODE, self::AUTO_GENERATE_OFF ) );
	}

	/**
	 * Sanitize the auto-generation mode.
	 *
	 * @since 1.4.0
	 * @param mixed $value Raw option value.
	 * @return string
	 */
	public static function sanitize_auto_generate_mode( $value ) {
		$value = is_string( $value ) ? sanitize_text_field( $value ) : '';

		if ( in_array( $value, array( self::AUTO_GENERATE_OFF, self::AUTO_GENERATE_MISSING_ON_PUBLISH ), true ) ) {
			return $value;
		}

		return self::AUTO_GENERATE_OFF;
	}

	/**
	 * Return the configured post types that support summary automation.
	 *
	 * @since 1.4.0
	 * @return array
	 */
	public static function get_supported_post_types() {
		$post_types = get_option( 'summaraize_post_types', array( 'post' ) );
		$post_types = is_array( $post_types ) ? $post_types : array( $post_types );
		$supported  = array();

		foreach ( $post_types as $post_type ) {
			$post_type = sanitize_key( $post_type );
			if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
				continue;
			}

			if ( ! post_type_supports( $post_type, 'editor' ) ) {
				continue;
			}

			$supported[] = $post_type;
		}

		return array_values( array_unique( $supported ) );
	}

	/**
	 * Determine whether a post or post type is supported.
	 *
	 * @since 1.4.0
	 * @param int|WP_Post|string $post Post ID, object, or post type.
	 * @return bool
	 */
	public static function is_post_supported( $post ) {
		if ( is_string( $post ) ) {
			return in_array( sanitize_key( $post ), self::get_supported_post_types(), true );
		}

		if ( is_numeric( $post ) ) {
			$post = get_post( absint( $post ) );
		}

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		return self::is_post_supported( $post->post_type );
	}

	/**
	 * Generate and persist a summary for a post.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID.
	 * @return array|WP_Error
	 */
	public static function generate_summary_for_post( $post_id ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );

		if ( ! $post instanceof WP_Post || ! self::is_post_supported( $post ) ) {
			return new WP_Error( 'summaraize_invalid_post', __( 'This post type is not supported.', 'summaraize' ) );
		}

		$source_content = self::get_generation_source_for_post( $post );
		$result         = self::generate_summary_from_content( $source_content );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		self::persist_generated_summary(
			$post_id,
			$result['points'],
			$source_content,
			$result['provider'],
			$result['model']
		);

		return $result;
	}

	/**
	 * Generate summary points for a raw content string.
	 *
	 * @since 1.4.0
	 * @param string $content       Content to summarize.
	 * @param string $debug_request Optional debug flag.
	 * @return array|WP_Error
	 */
	public static function generate_summary_from_content( $content, $debug_request = '' ) {
		$content = is_string( $content ) ? trim( $content ) : '';
		if ( '' === $content ) {
			return new WP_Error( 'summaraize_empty_content', __( 'Content is empty.', 'summaraize' ) );
		}

		$provider_context = self::get_generation_context();

		if ( 'openrouter' === $provider_context['provider'] ) {
			$response = Summaraize_OpenRouter_Settings::request_summary( $content );
		} elseif ( 'google_gemini' === $provider_context['provider'] ) {
			$response = Summaraize_Google_Gemini_Settings::request_gemini_summary( $content );
		} else {
			$response = Summaraize_OpenAI_Settings::request_openai_summary( $content, $debug_request );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$points = self::sanitize_points( isset( $response['points'] ) ? $response['points'] : array() );
		if ( empty( $points ) ) {
			return new WP_Error( 'summaraize_empty_points', __( 'No key points were returned.', 'summaraize' ) );
		}

		return array(
			'points'   => $points,
			'provider' => $provider_context['provider'],
			'model'    => ! empty( $response['model'] ) ? sanitize_text_field( $response['model'] ) : $provider_context['model'],
		);
	}

	/**
	 * Persist a freshly generated summary and lifecycle metadata.
	 *
	 * @since 1.4.0
	 * @param int    $post_id        Post ID.
	 * @param array  $points         Summary points.
	 * @param string $source_content Content used to generate the summary.
	 * @param string $provider       Provider slug.
	 * @param string $model          Provider model.
	 * @return void
	 */
	public static function persist_generated_summary( $post_id, $points, $source_content, $provider, $model ) {
		$post_id = absint( $post_id );
		$points  = self::sanitize_points( $points );

		if ( ! $post_id || empty( $points ) ) {
			return;
		}

		$summary_meta = self::build_editor_session_summary_meta( $points, $source_content, $provider, $model );

		update_post_meta( $post_id, 'summaraize_points', $points );
		update_post_meta( $post_id, self::META_GENERATED_AT, $summary_meta[ self::META_GENERATED_AT ] );
		update_post_meta( $post_id, self::META_SOURCE_HASH, $summary_meta[ self::META_SOURCE_HASH ] );
		update_post_meta( $post_id, self::META_GENERATION_PROVIDER, $summary_meta[ self::META_GENERATION_PROVIDER ] );
		update_post_meta( $post_id, self::META_GENERATION_MODEL, $summary_meta[ self::META_GENERATION_MODEL ] );
		update_post_meta( $post_id, self::META_GENERATED_POINTS_HASH, $summary_meta[ self::META_GENERATED_POINTS_HASH ] );
		update_post_meta( $post_id, self::META_MANUALLY_EDITED, '0' );
	}

	/**
	 * Build editor-session metadata for a generated summary.
	 *
	 * @since 1.4.0
	 * @param array  $points         Generated summary points.
	 * @param string $source_content Source content used for generation.
	 * @param string $provider       Provider slug.
	 * @param string $model          Model identifier.
	 * @return array
	 */
	public static function build_editor_session_summary_meta( $points, $source_content, $provider, $model ) {
		$points = self::sanitize_points( $points );

		return array(
			self::META_GENERATED_AT          => current_time( 'mysql' ),
			self::META_SOURCE_HASH           => self::get_source_hash_for_content( $source_content ),
			self::META_GENERATION_PROVIDER   => sanitize_text_field( $provider ),
			self::META_GENERATION_MODEL      => sanitize_text_field( $model ),
			self::META_GENERATED_POINTS_HASH => self::get_points_hash( $points ),
		);
	}

	/**
	 * Sync editor-session generation metadata after a post save.
	 *
	 * @since 1.4.0
	 * @param int   $post_id     Post ID.
	 * @param array $points      Saved summary points.
	 * @param array $summary_meta Posted summary metadata.
	 * @return void
	 */
	public static function sync_editor_session_generation_state( $post_id, $points, $summary_meta = array() ) {
		$post_id      = absint( $post_id );
		$points       = self::sanitize_points( $points );
		$summary_meta = self::sanitize_editor_session_summary_meta( $summary_meta );

		if ( empty( $points ) ) {
			self::clear_summary_lifecycle_meta( $post_id );
			return;
		}

		if ( self::has_complete_editor_session_meta( $summary_meta ) ) {
			update_post_meta( $post_id, self::META_GENERATED_AT, $summary_meta[ self::META_GENERATED_AT ] );
			update_post_meta( $post_id, self::META_SOURCE_HASH, $summary_meta[ self::META_SOURCE_HASH ] );
			update_post_meta( $post_id, self::META_GENERATION_PROVIDER, $summary_meta[ self::META_GENERATION_PROVIDER ] );
			update_post_meta( $post_id, self::META_GENERATION_MODEL, $summary_meta[ self::META_GENERATION_MODEL ] );
			update_post_meta( $post_id, self::META_GENERATED_POINTS_HASH, $summary_meta[ self::META_GENERATED_POINTS_HASH ] );
			update_post_meta(
				$post_id,
				self::META_MANUALLY_EDITED,
				self::get_points_hash( $points ) === $summary_meta[ self::META_GENERATED_POINTS_HASH ] ? '0' : '1'
			);
			return;
		}

		self::update_manual_edit_state( $post_id, $points );
	}

	/**
	 * Return the current summary status for a post.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_summary_status( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! self::has_summary( $post_id ) ) {
			return self::STATUS_MISSING;
		}

		if ( self::is_manually_edited( $post_id ) ) {
			return self::STATUS_EDITED;
		}

		$stored_source_hash = get_post_meta( $post_id, self::META_SOURCE_HASH, true );
		if ( ! is_string( $stored_source_hash ) || '' === $stored_source_hash ) {
			return self::STATUS_CURRENT;
		}

		$current_source_hash = self::get_source_hash_for_content( self::get_generation_source_for_post( $post_id ) );

		if ( '' !== $current_source_hash && $stored_source_hash !== $current_source_hash ) {
			return self::STATUS_STALE;
		}

		return self::STATUS_CURRENT;
	}

	/**
	 * Return saved summary points as normalized strings.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_saved_points( $post_id ) {
		$points = get_post_meta( absint( $post_id ), 'summaraize_points', true );

		return self::sanitize_points( is_array( $points ) ? $points : array() );
	}

	/**
	 * Determine whether a post currently has summary points.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function has_summary( $post_id ) {
		return ! empty( self::get_saved_points( $post_id ) );
	}

	/**
	 * Determine whether a summary has been manually edited.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_manually_edited( $post_id ) {
		return '1' === get_post_meta( absint( $post_id ), self::META_MANUALLY_EDITED, true );
	}

	/**
	 * Return provenance data for display.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_summary_provenance( $post_id ) {
		$generated_at = get_post_meta( absint( $post_id ), self::META_GENERATED_AT, true );
		$provider     = get_post_meta( absint( $post_id ), self::META_GENERATION_PROVIDER, true );
		$model        = get_post_meta( absint( $post_id ), self::META_GENERATION_MODEL, true );

		return array(
			'generated_at'   => is_string( $generated_at ) ? $generated_at : '',
			'provider'       => is_string( $provider ) ? $provider : '',
			'provider_label' => self::get_provider_label( $provider ),
			'model'          => is_string( $model ) ? $model : '',
		);
	}

	/**
	 * Return a status badge for admin UIs.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_status_badge_html( $post_id ) {
		$status = self::get_summary_status( $post_id );
		$label  = self::get_status_label( $status );
		$class  = 'summaraize-status-badge summaraize-status-' . sanitize_html_class( $status );

		return '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Render metabox status and provenance details.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function render_summary_status_panel( $post_id ) {
		$post_id     = absint( $post_id );
		$status      = self::get_summary_status( $post_id );
		$provenance  = self::get_summary_provenance( $post_id );
		$status_note = '';

		if ( self::STATUS_EDITED === $status ) {
			$status_note = __( 'Manual edits are protected from automatic generation.', 'summaraize' );
		} elseif ( self::STATUS_STALE === $status ) {
			$status_note = __( 'Post content changed since this summary was generated.', 'summaraize' );
		} elseif ( self::STATUS_MISSING === $status ) {
			$status_note = __( 'No summary is saved for this post yet.', 'summaraize' );
		}

		echo '<div class="summaraize-summary-status-panel">';
		echo '<p><strong>' . esc_html__( 'Summary Status:', 'summaraize' ) . '</strong> ' . wp_kses_post( self::get_status_badge_html( $post_id ) ) . '</p>';

		if ( ! empty( $provenance['generated_at'] ) && ! empty( $provenance['provider_label'] ) ) {
			echo '<p class="description">';
			/* translators: 1: provider name, 2: model name, 3: generation timestamp. */
			$generated_format  = __( 'Last generated with %1$s using %2$s on %3$s.', 'summaraize' );
			$generated_message = sprintf(
				$generated_format,
				$provenance['provider_label'],
				! empty( $provenance['model'] ) ? $provenance['model'] : __( 'the configured model', 'summaraize' ),
				self::format_generated_at( $provenance['generated_at'] )
			);

			echo esc_html( $generated_message );
			echo '</p>';
		}

		if ( '' !== $status_note ) {
			echo '<p class="description">' . esc_html( $status_note ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Render hidden inputs used to persist editor-session generation metadata.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function render_editor_session_hidden_fields( $post_id ) {
		$meta = self::sanitize_editor_session_summary_meta(
			array(
				self::META_GENERATED_AT          => get_post_meta( absint( $post_id ), self::META_GENERATED_AT, true ),
				self::META_SOURCE_HASH           => get_post_meta( absint( $post_id ), self::META_SOURCE_HASH, true ),
				self::META_GENERATION_PROVIDER   => get_post_meta( absint( $post_id ), self::META_GENERATION_PROVIDER, true ),
				self::META_GENERATION_MODEL      => get_post_meta( absint( $post_id ), self::META_GENERATION_MODEL, true ),
				self::META_GENERATED_POINTS_HASH => get_post_meta( absint( $post_id ), self::META_GENERATED_POINTS_HASH, true ),
			)
		);

		foreach ( $meta as $meta_key => $meta_value ) {
			echo '<input type="hidden" id="' . esc_attr( $meta_key ) . '" name="' . esc_attr( $meta_key ) . '" value="' . esc_attr( $meta_value ) . '" />';
		}
	}

	/**
	 * Normalize points returned from providers or saved in post meta.
	 *
	 * @since 1.4.0
	 * @param array $points Raw points.
	 * @return array
	 */
	public static function sanitize_points( $points ) {
		if ( ! is_array( $points ) ) {
			return array();
		}

		$allowed_tags = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
		);
		$clean_points = array();

		foreach ( $points as $point ) {
			if ( is_array( $point ) && isset( $point['text'] ) ) {
				$point = $point['text'];
			}

			if ( ! is_string( $point ) ) {
				continue;
			}

			$point = trim( wp_kses( $point, $allowed_tags ) );
			if ( '' !== $point ) {
				$clean_points[] = $point;
			}
		}

		return array_slice( array_values( $clean_points ), 0, 5 );
	}

	/**
	 * Return the content string used for summary generation.
	 *
	 * @since 1.4.0
	 * @param int|WP_Post $post Post object or ID.
	 * @return string
	 */
	public static function get_generation_source_for_post( $post ) {
		if ( is_numeric( $post ) ) {
			$post = get_post( absint( $post ) );
		}

		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$content = is_string( $post->post_content ) ? $post->post_content : '';

		/**
		 * Filter the source content sent to AI providers.
		 *
		 * @since 1.4.0
		 * @param string  $content Source content.
		 * @param WP_Post $post    Post being summarized.
		 */
		return (string) apply_filters( 'summaraize_generation_source_content', $content, $post );
	}

	/**
	 * Build a stable hash for a content string.
	 *
	 * @since 1.4.0
	 * @param string $content Content to hash.
	 * @return string
	 */
	public static function get_source_hash_for_content( $content ) {
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( '' === $content ) {
			return '';
		}

		return md5( $content );
	}

	/**
	 * Build a stable hash for a points array.
	 *
	 * @since 1.4.0
	 * @param array $points Points to hash.
	 * @return string
	 */
	public static function get_points_hash( $points ) {
		$points = self::sanitize_points( $points );

		if ( empty( $points ) ) {
			return '';
		}

		$json = wp_json_encode( array_values( $points ) );

		return is_string( $json ) ? md5( $json ) : '';
	}

	/**
	 * Return the configured provider and model.
	 *
	 * @since 1.4.0
	 * @return array
	 */
	private static function get_generation_context() {
		$provider = get_option( 'summaraize_ai_provider', 'openai' );
		$provider = in_array( $provider, array( 'openai', 'google_gemini', 'openrouter' ), true ) ? $provider : 'openai';
		$model    = Summaraize_OpenAI_Settings::sanitize_openai_model( get_option( 'summaraize_ai_model', Summaraize_OpenAI_Settings::OPENAI_DEFAULT_MODEL ) );

		if ( 'google_gemini' === $provider ) {
			$model = Summaraize_Google_Gemini_Settings::GEMINI_DEFAULT_MODEL;
		} elseif ( 'openrouter' === $provider ) {
			$model = Summaraize_OpenRouter_Settings::sanitize_model( get_option( 'summaraize_openrouter_model', '' ) );
		}

		return array(
			'provider' => $provider,
			'model'    => $model,
		);
	}

	/**
	 * Update the manual edit flag against saved generation metadata.
	 *
	 * @since 1.4.0
	 * @param int   $post_id Post ID.
	 * @param array $points  Saved points.
	 * @return void
	 */
	private static function update_manual_edit_state( $post_id, $points ) {
		$generated_points_hash = get_post_meta( absint( $post_id ), self::META_GENERATED_POINTS_HASH, true );

		if ( ! is_string( $generated_points_hash ) || '' === $generated_points_hash ) {
			return;
		}

		update_post_meta(
			absint( $post_id ),
			self::META_MANUALLY_EDITED,
			self::get_points_hash( $points ) === $generated_points_hash ? '0' : '1'
		);
	}

	/**
	 * Clear lifecycle metadata when no summary is stored.
	 *
	 * @since 1.4.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private static function clear_summary_lifecycle_meta( $post_id ) {
		$post_id = absint( $post_id );

		delete_post_meta( $post_id, self::META_GENERATED_AT );
		delete_post_meta( $post_id, self::META_SOURCE_HASH );
		delete_post_meta( $post_id, self::META_GENERATION_PROVIDER );
		delete_post_meta( $post_id, self::META_GENERATION_MODEL );
		delete_post_meta( $post_id, self::META_GENERATED_POINTS_HASH );
		delete_post_meta( $post_id, self::META_MANUALLY_EDITED );
	}

	/**
	 * Normalize posted editor-session metadata.
	 *
	 * @since 1.4.0
	 * @param array $summary_meta Raw metadata.
	 * @return array
	 */
	private static function sanitize_editor_session_summary_meta( $summary_meta ) {
		$summary_meta = is_array( $summary_meta ) ? $summary_meta : array();

		return array(
			self::META_GENERATED_AT          => isset( $summary_meta[ self::META_GENERATED_AT ] ) ? sanitize_text_field( $summary_meta[ self::META_GENERATED_AT ] ) : '',
			self::META_SOURCE_HASH           => isset( $summary_meta[ self::META_SOURCE_HASH ] ) ? sanitize_text_field( $summary_meta[ self::META_SOURCE_HASH ] ) : '',
			self::META_GENERATION_PROVIDER   => isset( $summary_meta[ self::META_GENERATION_PROVIDER ] ) ? sanitize_text_field( $summary_meta[ self::META_GENERATION_PROVIDER ] ) : '',
			self::META_GENERATION_MODEL      => isset( $summary_meta[ self::META_GENERATION_MODEL ] ) ? sanitize_text_field( $summary_meta[ self::META_GENERATION_MODEL ] ) : '',
			self::META_GENERATED_POINTS_HASH => isset( $summary_meta[ self::META_GENERATED_POINTS_HASH ] ) ? sanitize_text_field( $summary_meta[ self::META_GENERATED_POINTS_HASH ] ) : '',
		);
	}

	/**
	 * Check whether editor-session metadata is complete enough to persist.
	 *
	 * @since 1.4.0
	 * @param array $summary_meta Normalized summary metadata.
	 * @return bool
	 */
	private static function has_complete_editor_session_meta( $summary_meta ) {
		foreach (
			array(
				self::META_GENERATED_AT,
				self::META_SOURCE_HASH,
				self::META_GENERATION_PROVIDER,
				self::META_GENERATION_MODEL,
				self::META_GENERATED_POINTS_HASH,
			) as $meta_key
		) {
			if ( empty( $summary_meta[ $meta_key ] ) || ! is_string( $summary_meta[ $meta_key ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Return a translated label for the current status.
	 *
	 * @since 1.4.0
	 * @param string $status Status identifier.
	 * @return string
	 */
	private static function get_status_label( $status ) {
		switch ( $status ) {
			case self::STATUS_EDITED:
				return __( 'Edited', 'summaraize' );

			case self::STATUS_STALE:
				return __( 'Stale', 'summaraize' );

			case self::STATUS_CURRENT:
				return __( 'Current', 'summaraize' );

			case self::STATUS_MISSING:
			default:
				return __( 'Missing', 'summaraize' );
		}
	}

	/**
	 * Return a human-readable provider label.
	 *
	 * @since 1.4.0
	 * @param string $provider Provider slug.
	 * @return string
	 */
	private static function get_provider_label( $provider ) {
		if ( 'openrouter' === $provider ) {
			return __( 'OpenRouter', 'summaraize' );
		}
		if ( 'google_gemini' === $provider ) {
			return __( 'Google Gemini', 'summaraize' );
		}

		if ( 'openai' === $provider ) {
			return __( 'OpenAI', 'summaraize' );
		}

		return '';
	}

	/**
	 * Format a stored generation timestamp for admin display.
	 *
	 * @since 1.4.0
	 * @param string $generated_at Stored timestamp.
	 * @return string
	 */
	private static function format_generated_at( $generated_at ) {
		$generated_at = is_string( $generated_at ) ? trim( $generated_at ) : '';
		if ( '' === $generated_at ) {
			return '';
		}

		$timestamp = mysql2date( 'U', $generated_at, false );
		if ( ! $timestamp ) {
			return $generated_at;
		}

		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}
}
