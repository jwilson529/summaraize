<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/jwilson529/summaraize
 * @since      1.0.0
 *
 * @package    Summaraize
 * @subpackage Summaraize/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Summaraize
 * @subpackage Summaraize/includes
 * @author     James Wilson <james@middletnwebdesign.com>
 */
class Summaraize_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		Summaraize_Logger::info( 'Plugin deactivated.' );
	}
}
