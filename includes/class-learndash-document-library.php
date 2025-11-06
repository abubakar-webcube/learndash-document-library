<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://wooninjas.com/
 * @since      1.0.0
 *
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/includes
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
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/includes
 * @author     Wooninjas <info@wooninjas.com>
 */
class LearnDash_Document_Library
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      LearnDash_Document_Library_Loader    $loader    Maintains and registers all hooks for the plugin.
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
	public function __construct()
	{
		if (defined('LEARNDASH_DOCUMENT_LIBRARY_VERSION')) {
			$this->version = LEARNDASH_DOCUMENT_LIBRARY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'learndash-document-library';

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
	 * - LearnDash_Document_Library_Loader. Orchestrates the hooks of the plugin.
	 * - LearnDash_Document_Library_i18n. Defines internationalization functionality.
	 * - LearnDash_Document_Library_Admin. Defines all hooks for the admin area.
	 * - LearnDash_Document_Library_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-i18n.php';

		/**
		 * The class responsible for defining all license handling actions that occur in the admin area.
		 */
		require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-license-handler.php';

		/**
		 * The class responsible for defining Custom Post Type.
		 */
		require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-custom-post-type.php';

		/**
		 * The class responsible for defining Taxonomy.
		 */
		require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-taxonomy.php';

		/**
		 * The class responsible for defining Metabox.
		 */
		require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-metabox.php';

		/**
		 * The class responsible for defining settings in LearnDash Groups, Courses, Lessons, Topics and Assignments.
		 */
		require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-settings.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'admin/class-learndash-document-library-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'public/class-learndash-document-library-public.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'public/class-learndash-document-shortcode.php';

		$this->loader = new LearnDash_Document_Library_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the LearnDash_Document_Library_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new LearnDash_Document_Library_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_cpt = new LearnDash_Document_Library_CPT();

		$this->loader->add_action('init',  $plugin_cpt, 'document_library_cpt_init');
		$this->loader->add_action('init',  $plugin_cpt, 'flush_rewrite_rules');

		$plugin_taxonomy = new LearnDash_Document_Library_Taxonomy();

		$this->loader->add_action('init',  $plugin_taxonomy, 'document_library_taxonomy_init');

		$plugin_metabox = new LearnDash_Document_Library_Metabox();

		$this->loader->add_action('init',  $plugin_metabox, 'register');
		
		$plugin_settings = new LearnDash_Document_Library_Settings();
		$this->loader->add_action('init',  $plugin_settings, 'register');

		$plugin_admin = new LearnDash_Document_Library_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 100);
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', 100);

		$this->loader->add_action('admin_init',  $plugin_admin, 'get_license_class');
		$this->loader->add_action('admin_menu',  $plugin_admin, 'admin_menu');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new LearnDash_Document_Library_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
		$this->loader->add_action('wp_ajax_ldl_verify_restriction_password', $plugin_public, 'ldl_verify_restriction_password');
		$this->loader->add_action('wp_ajax_nopriv_ldl_verify_restriction_password', $plugin_public, 'ldl_verify_restriction_password');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    LearnDash_Document_Library_Loader Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
