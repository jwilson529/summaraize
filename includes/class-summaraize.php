<?php
/**
 * Core plugin class for Summaraize.
 *
 * @link       https://github.com/jwilson529/summaraize
 * @since      1.0.0
 *
 * @package    Summaraize
 * @subpackage Summaraize/includes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines the admin hooks, public hooks, and i18n behavior for the plugin.
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
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-summaraize-logger.php';

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
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-summaraize-openrouter-settings.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-summaraize-summary-manager.php';

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

		$plugin_admin      = new Summaraize_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_settings   = new Summaraize_Admin_Settings();
		$plugin_openai     = new Summaraize_OpenAI_Settings();
		$plugin_gemini     = new Summaraize_Google_Gemini_Settings();
		$plugin_openrouter = new Summaraize_OpenRouter_Settings();
		$this->loader->add_action( 'wp_ajax_summaraize_openrouter', $plugin_openrouter, 'ajax_action' );
		$plugin_metabox  = new Summaraize_Admin_Metabox();
		$summary_manager = new Summaraize_Summary_Manager();
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
		$this->loader->add_action( 'admin_notices', $summary_manager, 'maybe_render_bulk_notice' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/summaraize.php' ), $plugin_settings, 'add_settings_link' );
		$this->loader->add_action( 'transition_post_status', $summary_manager, 'schedule_auto_generate_on_publish', 10, 3 );
		$this->loader->add_action( Summaraize_Summary_Manager::AUTO_GENERATE_EVENT, $summary_manager, 'run_scheduled_auto_generate' );

		foreach ( Summaraize_Summary_Manager::get_supported_post_types() as $post_type ) {
			$this->loader->add_filter( 'bulk_actions-edit-' . $post_type, $summary_manager, 'register_bulk_actions' );
			$this->loader->add_filter( 'handle_bulk_actions-edit-' . $post_type, $summary_manager, 'handle_bulk_actions', 10, 3 );
			$this->loader->add_filter( 'manage_' . $post_type . '_posts_columns', $summary_manager, 'add_status_column' );
			$this->loader->add_action( 'manage_' . $post_type . '_posts_custom_column', $summary_manager, 'render_status_column', 10, 2 );
		}
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

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Register the shortcode early so it is available before content rendering.
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes', 1 );

		// Append plugin output after shortcode processing has already occurred.
		$this->loader->add_filter( 'the_content', $plugin_public, 'append_summaraize_to_content_automatically', 20 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Retrieve the plugin slug.
	 *
	 * @since     1.0.0
	 * @return    string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the loader instance.
	 *
	 * @since     1.0.0
	 * @return    Summaraize_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the plugin version.
	 *
	 * @since     1.0.0
	 * @return    string
	 */
	public function get_version() {
		return $this->version;
	}
}
