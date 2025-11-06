<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wooninjas.com/
 * @since             1.0.0
 * @package           LearnDash-Document-Library
 *
 * @wordpress-plugin
 * Plugin Name:       LearnDash Document Library
 * Plugin URI:        https://wooninjas.com/downloads/learndash-document-library/
 * Description:       Manage documents for LearnDash.
 * Version:           1.0.3
 * Requires PHP:	  7.4
 * Requires at least: 6.6
 * Tested up to: 	  6.8.2
 * Author:            Wooninjas
 * Author URI:        https://wooninjas.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       learndash-document-library
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin path.
 * Plugin url.
 * Plugin version.
 */
define( 'LEARNDASH_DOCUMENT_LIBRARY_DIR', plugin_dir_path( __FILE__ ) );
define( 'LEARNDASH_DOCUMENT_LIBRARY_FILE', LEARNDASH_DOCUMENT_LIBRARY_DIR . basename ( __FILE__ ) );
define( 'LEARNDASH_DOCUMENT_LIBRARY_URL', plugin_dir_url( __FILE__ ) );
define( 'LEARNDASH_DOCUMENT_LIBRARY_VERSION', '1.0.3' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-learndash-document-library-activator.php
 */
function activate_learndash_document_library() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-learndash-document-library-activator.php';
	LearnDash_Document_Library_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-learndash-document-library-deactivator.php
 */
function deactivate_learndash_document_library() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-learndash-document-library-deactivator.php';
	LearnDash_Document_Library_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_learndash_document_library' );
register_deactivation_hook( __FILE__, 'deactivate_learndash_document_library' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-learndash-document-library.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function learndash_document_library_run() {

	$plugin = new LearnDash_Document_Library();
	$plugin->run();

}


function learndash_document_library_loaded() {
	learndash_document_library_run();
}

add_action('plugins_loaded', 'learndash_document_library_loaded');

