<?php
/**
 * Logger for Summaraize.
 *
 * @package    Summaraize
 * @subpackage Summaraize/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Provides a lightweight file logger for plugin diagnostics.
 *
 * @since 1.2.6
 */
class Summaraize_Logger {

	/**
	 * The default log filename.
	 *
	 * @since 1.2.6
	 * @var string
	 */
	const DEFAULT_LOG_FILE = 'plugin-error.log';

	/**
	 * Write an informational log entry.
	 *
	 * @since 1.2.6
	 * @param string $message The message to write.
	 * @param array  $context Optional structured context to append.
	 * @return bool True when the message was written, false otherwise.
	 */
	public static function info( $message, $context = array() ) {
		return self::write( 'INFO', $message, $context );
	}

	/**
	 * Write a warning log entry.
	 *
	 * @since 1.2.6
	 * @param string $message The message to write.
	 * @param array  $context Optional structured context to append.
	 * @return bool True when the message was written, false otherwise.
	 */
	public static function warning( $message, $context = array() ) {
		return self::write( 'WARNING', $message, $context );
	}

	/**
	 * Write an error log entry.
	 *
	 * @since 1.2.6
	 * @param string $message The message to write.
	 * @param array  $context Optional structured context to append.
	 * @return bool True when the message was written, false otherwise.
	 */
	public static function error( $message, $context = array() ) {
		return self::write( 'ERROR', $message, $context );
	}

	/**
	 * Resolve the log file path.
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public static function get_log_file_path() {
		$path = '';

		if ( defined( 'SUMMARAIZE_LOG_FILE' ) && is_string( SUMMARAIZE_LOG_FILE ) ) {
			$path = SUMMARAIZE_LOG_FILE;
		}

		if ( empty( $path ) ) {
			$path = trailingslashit( plugin_dir_path( __DIR__ ) ) . self::DEFAULT_LOG_FILE;
		}

		/**
		 * Filters the path used for plugin logging.
		 *
		 * @since 1.2.6
		 * @param string $path The log file path.
		 */
		$path = apply_filters( 'summaraize_log_file_path', $path );

		return is_string( $path ) ? $path : '';
	}

	/**
	 * Format and write a log entry.
	 *
	 * @since 1.2.6
	 * @param string $level   The log level label.
	 * @param string $message The message to write.
	 * @param array  $context Optional structured context.
	 * @return bool True when the message was written, false otherwise.
	 */
	private static function write( $level, $message, $context ) {
		if ( ! is_string( $message ) || '' === trim( $message ) ) {
			return false;
		}

		$path = self::get_log_file_path();

		if ( '' === $path ) {
			return false;
		}

		$line = sprintf(
			'[%s] %s: %s',
			gmdate( 'Y-m-d H:i:s' ),
			sanitize_key( strtolower( $level ) ),
			sanitize_text_field( $message )
		);

		if ( ! empty( $context ) ) {
			$encoded_context = wp_json_encode( $context );
			if ( is_string( $encoded_context ) && '' !== $encoded_context ) {
				$line .= ' ' . $encoded_context;
			}
		}

		$line .= PHP_EOL;

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This class intentionally persists plugin diagnostics to a dedicated log file.
		return false !== error_log( $line, 3, $path );
	}
}
