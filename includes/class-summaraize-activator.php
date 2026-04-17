<?php
/**
 * Fired during plugin activation
 *
 * @link       https://github.com/jwilson529/summaraize
 * @since      1.0.0
 *
 * @package    Summaraize
 * @subpackage Summaraize/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Summaraize
 * @subpackage Summaraize/includes
 * @author     James Wilson <james@middletnwebdesign.com>
 */
class Summaraize_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if ( false === get_option( 'summaraize_post_types', false ) ) {
			update_option( 'summaraize_post_types', array( 'post' ) );
		}

		Summaraize_Logger::info( 'Plugin activated.' );
	}
}
