<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://wooninjas.com/
 * @since      1.0.0
 *
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/public
 * @author     Wooninjas <info@wooninjas.com>
 */
class LearnDash_Document_Library_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in LearnDash_Document_Library_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The LearnDash_Document_Library_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/learndash-document-library-public.css', array(), $this->version, 'all');
		wp_enqueue_style('ld-doc-font-awsme', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), $this->version, 'all');
		wp_enqueue_style('ldl_fontawesome', plugin_dir_url(__FILE__) . 'fontawesome/css/all.min.css', array(), $this->version, '6.7.2');
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in LearnDash_Document_Library_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The LearnDash_Document_Library_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// wp_register_style('floting_side_bar_css', plugin_dir_url(__FILE__) . 'css/hlRightPanel.css');
		// wp_register_script('floting_side_bar_js', plugin_dir_url(__FILE__) . 'js/hlRightPanel.js', array('jquery'), false, true);
		// wp_enqueue_script('floting_side_bar_js');
		// wp_enqueue_style('floting_side_bar_css');
		/**
		 * DATA Table 
		 */
		wp_enqueue_style('dataTablecss', '//cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css', array(), '1.12.1', 'all');
		wp_enqueue_script('dataTablejs', "//cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js", array('jquery'), '1.12.1', false, true);
		// wp_register_style('dataTablecss', '//cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css');
		// wp_register_script('dataTablejs', "//cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js", array('jquery'), false, true);

		// /**
		//  * DATA Table checkbox
		//  */
		// wp_enqueue_style('dataTable_checkbox_css', '//gyrocode.github.io/jquery-datatables-checkboxes/1.2.12/css/dataTables.checkboxes.css');
		// wp_enqueue_script('dataTable_checkbox_js', "//gyrocode.github.io/jquery-datatables-checkboxes/1.2.12/js/dataTables.checkboxes.min.js", array('jquery'), false, true);

		
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/learndash-document-library-public.js', array('jquery'), $this->version, false);
		wp_enqueue_script('ldl_fontawesome', plugin_dir_url(__FILE__) . 'fontawesome/js/all.min.js', array('jquery'), '6.7.2', false);

		// LDL Document Viewer
		wp_enqueue_script( 'pdfjs', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js', array(), null, true );
		wp_enqueue_script( 'mammoth', 'https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js', array(), null, true );
		wp_enqueue_script( 'sheetjs', 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js', array(), null, true );
		wp_enqueue_script( 'ldl-doc-preview', plugin_dir_url(__FILE__) . 'js/ldl-doc-preview.js', array('jquery', 'pdfjs', 'mammoth', 'sheetjs'), '1.0', true );
		$l10n = array(
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			'site_url'  => site_url(),
			'plugin_url'=> plugin_dir_url( __FILE__ ),
			'nonce'     => wp_create_nonce( 'ldl_doc_preview_nonce' ),
		);
		wp_localize_script( 'ldl-doc-preview', 'ldlDocPreview', $l10n );
	}

	/**
	 * Ajax function to verify password
	 *
	 * @return void
	 */
	public function ldl_verify_restriction_password() {
		check_ajax_referer('ldl_restriction_password');

		$term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : null;
		$restriction_type = isset($_POST['restriction_type']) ? sanitize_text_field($_POST['restriction_type']) : '';
		$password = isset($_POST['password']) ? $_POST['password'] : '';

		if (empty($restriction_type) || $password === '') {
			wp_send_json_error(__('Missing password.', 'learndash-document-library'));
		}

		$valid = false;
		if ($restriction_type === 'library' && $term_id) {
			$expected = get_term_meta($term_id, 'library_password', true);
			if ($expected && $password === $expected) {
				$valid = true;
			}
		} elseif ($restriction_type === 'category' && $term_id) {
			$expected = get_term_meta($term_id, 'library_password', true);
			if ($expected && $password === $expected) {
				$valid = true;
			}
		} elseif ($restriction_type === 'global') {
			$settings = get_option('ldl_general_settings');
			$expected = isset($settings['global_password']) ? $settings['global_password'] : '';
			if ($expected && $password === $expected) {
				$valid = true;
			}
		}

		if ($valid) {
			wp_send_json_success();
		} else {
			wp_send_json_error(__('Incorrect password.', 'learndash-document-library'));
		}
	}
}
