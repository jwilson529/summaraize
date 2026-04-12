<?php
	/**
	 * The file that defines the core plugin class
	 *
	 * A class definition that includes attributes and functions used across both the
	 * public-facing side of the site and the admin area.
	 *
	 * @link       https://github.com/jwilson529/summaraize
	 * @since      1.0.0
	 *
	 * @package    Summaraize
	 * @subpackage Summaraize/includes
	 */

	/**
	 * The core plugin class.
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 * @package    Summaraize
	 * @subpackage Summaraize/includes
	 * @author     James Wilson <james@middletnwebdesign.com>
	 */
class Summaraize {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Summaraize_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'SUMMARAIZE_VERSION' ) ) {
			$this->version = SUMMARAIZE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'summaraize';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Summaraize_Loader. Orchestrates the hooks of the plugin.
	 * - Summaraize_I18n. Defines internationalization functionality.
	 * - Summaraize_Admin. Defines all hooks for the admin area.
	 * - Summaraize_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-summaraize-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-summaraize-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-summaraize-admin.php';

		/**
		 * The class responsible for defining all settings for the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-summaraize-admin-settings.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-summaraize-openai-settings.php';
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-summaraize-google-gemini-settings.php';

		/**
		 * The class responsible for defining all metabox items.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-summaraize-admin-metabox.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'public/class-summaraize-public.php';

		$this->loader = new Summaraize_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Summaraize_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Summaraize_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin    = new Summaraize_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_settings = new Summaraize_Admin_Settings();
		$plugin_openai   = new Summaraize_OpenAI_Settings();
		$plugin_gemini   = new Summaraize_Google_Gemini_Settings();
		$plugin_metabox  = new Summaraize_Admin_Metabox();
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_menu', $plugin_settings, 'summaraize_register_options_page' );
		$this->loader->add_action( 'admin_init', $plugin_settings, 'summaraize_register_settings' );
		$this->loader->add_action( 'wp_ajax_summaraize_ajax_validate_openai_api_key', $plugin_openai, 'summaraize_ajax_validate_openai_api_key' );
		$this->loader->add_action( 'wp_ajax_summaraize_ajax_validate_google_gemini_api_key', $plugin_gemini, 'summaraize_ajax_validate_google_gemini_api_key' );

		$this->loader->add_action( 'wp_ajax_summaraize_gather_content', $plugin_admin, 'summaraize_gather_content' );
		$this->loader->add_action( 'wp_ajax_summaraize_auto_save', $plugin_settings, 'summaraize_auto_save' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_metabox, 'add_meta_box' );
		$this->loader->add_action( 'save_post', $plugin_metabox, 'save_summaraize_points' );
		$this->loader->add_action( 'admin_notices', $plugin_settings, 'display_admin_notices' );
		$this->loader->add_action( 'plugin_action_links_', $plugin_settings, 'add_settings_link' );
	}


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Summaraize_Public( $this->get_plugin_name(), $this->get_version() );

			// Enqueue styles and scripts.
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

			// Register the shortcode separately.
			$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes', 1 );  // Very high priority to register early.

			// Append content automatically after the shortcode is processed.
			$this->loader->add_action( 'the_content', $plugin_public, 'append_summaraize_to_content_automatically', 20 );  // Lower priority, so this fires after shortcodes.
	}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 */
	public function run() {
		$this->loader->run();
	}

		/**
		 * The name of the plugin used to uniquely identify it within the context of
		 * WordPress and to define internationalization functionality.
		 *
		 * @since     1.0.0
		 * @return    string    The name of the plugin.
		 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @since     1.0.0
		 * @return    Summaraize_Loader    Orchestrates the hooks of the plugin.
		 */
	public function get_loader() {
		return $this->loader;
	}

		/**
		 * Retrieve the version number of the plugin.
		 *
		 * @since     1.0.0
		 * @return    string    The version number of the plugin.
		 */
	public function get_version() {
		return $this->version;
	}
}
