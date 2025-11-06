<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://wooninjas.com/
 * @since      1.0.0
 *
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/admin
 */

// Include the License Class
if (file_exists(LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-license.php')) {
	require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-license.php';
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/admin
 * @author     Wooninjas <info@wooninjas.com>
 */
class LearnDash_Document_Library_Admin {

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

	private $license_class;

	public $page_tab;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->page_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->license_class = new LearnDash_Document_Library_License();

		add_action( 'admin_init', [$this, 'ldl_save_general_settings'] );

		// Hook into the user profile page
		add_action( 'show_user_profile', [$this, 'ldl_show_user_favorites_on_profile'] );
		add_action( 'edit_user_profile', [$this, 'ldl_show_user_favorites_on_profile'] );
	}

	public function get_license_class()
	{
		return $this->license_class;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles($hook)
	{
		$screen = get_current_screen();
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

		if ( (isset( $_GET['page'] ) && ! empty( $_GET['page'] ) && 'ld-document-library' === $_GET['page']) || (in_array($hook, ['post.php', 'post-new.php', 'edit.php'], true) && is_object($screen) && 'ldl-document' === $screen->post_type) ) {
			wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/learndash-document-library-admin.css', array(), $this->version, 'all');
		}

		// enqueue select2 only on the document library page
		if(in_array($hook, ['post.php', 'post-new.php', 'edit.php'], true) && is_object($screen) && 'ldl-document' === $screen->post_type){
			wp_dequeue_style('learndash-select2-jquery-style');
			wp_deregister_style('learndash-select2-jquery-style');
			wp_enqueue_style(
				'select2',
				'//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
				array(),
				'4.1.0',
				'all'
			);
			wp_enqueue_style('select2-css', plugin_dir_url(__FILE__) . 'css/learndash-document-library-admin-select2.css', array(), $this->version, 'all');
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook)
	{
		$screen = get_current_screen();

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

		// if (in_array($hook, ['post.php', 'post-new.php'], true) && is_object($screen) && 'ldl-document' === $screen->post_type) {
			wp_enqueue_media();
			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/learndash-document-library-admin.js', array(), $this->version, true);
			wp_localize_script(
				$this->plugin_name,
				'ldlAdminObject',
				[
					'i18n' => [
						'select_file'  => __('Select File', 'learndash-document-library'),
						'add_file'     => __('Add File', 'learndash-document-library'),
						'replace_file' => __('Replace File', 'learndash-document-library'),
					],
				]
			);
		// }

		// wp_dequeue_script('learndash-select2-jquery-script');
		// wp_deregister_script('learndash-select2-jquery-script');

		// enqueue select2 only on the document library page
		if((in_array($hook, ['post.php', 'post-new.php', 'edit.php'], true) && is_object($screen) && 'ldl-document' === $screen->post_type) || (isset($_REQUEST['page']) && $_REQUEST['page'] === "ld-document-library")){
			wp_enqueue_script('select2', '//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array(), '4.1.0', true);
			wp_enqueue_script('select2-js', plugin_dir_url(__FILE__) . 'js/learndash-document-library-admin-select2.js', array(), $this->version, true);
		}
	}

	/**
	 * Save the LDL General Settings.
	 */
	public function ldl_save_general_settings() {
		if ( isset( $_POST['save_ldl_general_settings'] ) && check_admin_referer( 'ldl_general_settings', 'ldl_general_settings_field' ) ) {
			// Sanitize the input
			$new_settings = array();
			$new_settings['default_libraries_layout'] = isset( $_POST['ldl_libraries_layout'] ) ? sanitize_text_field( wp_unslash( $_POST['ldl_libraries_layout'] ) ) : 'list';

			// Sanitize visible columns array
			if ( isset( $_POST['ldl_visible_list_columns'] ) && is_array( $_POST['ldl_visible_list_columns'] ) ) {
				$new_settings['visible_list_columns'] = array_map( 'sanitize_text_field', wp_unslash( $_POST['ldl_visible_list_columns'] ) );
			} else {
				$new_settings['visible_list_columns'] = array(); // If none checked
			}

			$new_settings['enable_categories_filter'] = isset( $_POST['ldl_enable_categories_filter'] ) ? sanitize_text_field( wp_unslash( $_POST['ldl_enable_categories_filter'] ) ) : '0';
			$new_settings['enable_libraries_restriction'] = isset( $_POST['ldl_enable_libraries_restriction'] ) ? sanitize_text_field( wp_unslash( $_POST['ldl_enable_libraries_restriction'] ) ) : '0';
			$new_settings['enable_categories_restriction'] = isset( $_POST['ldl_enable_categories_restriction'] ) ? sanitize_text_field( wp_unslash( $_POST['ldl_enable_categories_restriction'] ) ) : '0';
			$new_settings['enable_library_upload'] = isset( $_POST['ldl_enable_library_upload'] ) ? sanitize_text_field( wp_unslash( $_POST['ldl_enable_library_upload'] ) ) : '0';

			// Save global password
			$new_settings['global_password'] = isset( $_POST['ldl_global_password'] ) ? sanitize_text_field( wp_unslash( $_POST['ldl_global_password'] ) ) : '';
			// Save global user roles
			// error_log('post: ' . var_export($_POST,true));
			if ( isset( $_POST['ldl_global_user_role'] ) && is_array( $_POST['ldl_global_user_role'] ) ) {
				$new_settings['global_user_roles'] = array_map( 'sanitize_text_field', $_POST['ldl_global_user_role'] );
			} else if(isset( $_POST['ldl_global_user_role'] ) && $_POST['ldl_global_user_role'] === 'all') {
				$new_settings['global_user_roles'] = array('all');
			} else if(isset( $_POST['ldl_global_user_role'] ) && !empty( $_POST['ldl_global_user_role'] )) {
				$new_settings['global_user_roles'] = array(sanitize_text_field( $_POST['ldl_global_user_role'] ) );
			} else {
				$new_settings['global_user_roles'] = array();
			}
			// error_log('setting: ' . var_export($new_settings,true));
			update_option( 'ldl_general_settings', $new_settings );
			
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'learndash-document-library' ) . '</p></div>';
			} );
		}
	}

	public function admin_menu()
	{
		add_submenu_page(
			'edit.php?post_type=ldl-document',
			__('Document Library Settings', 'learndash-document-library'),
			__('Settings', 'learndash-document-library'),
			'manage_options',
			'ld-document-library',
			array($this, 'menu_page'),
			10
		);
	}

	public function menu_page()
	{
		?>
		<div id="wrap">
			<div class="ld-document-nav-wrapper">
				<?php
				$setting_sections = $this->get_setting_sections();
				foreach ($setting_sections as $key => $settings_section) {
				?>
					<a href="?post_type=ldl-document&page=ld-document-library&tab=<?php echo esc_attr($key); ?>" class="nav-tab <?php echo $this->page_tab == $key ? 'nav-tab-active' : ''; ?>">
						<?php esc_html_e($settings_section['title'], 'learndash-document-library'); ?>
					</a>
				<?php
				}
				?>
			</div>
			<?php
			foreach ($setting_sections as $key => $setting_section) {
				if ($this->page_tab == $key) {
					if (is_file(plugin_dir_path(__FILE__) . 'partials/' . $key . '.php')) {
						include_once plugin_dir_path(__FILE__) . 'partials/' . $key . '.php';
					}
				}
			}
			?>
		</div>
		<?php
	}

	public function get_setting_sections()
	{
		$settings_sections = array(
			'general' => array(
			    'title' => __( 'General', 'learndash-document-library' ),
			),
			'license' => array(
				'title' => __('License', 'learndash-document-library'),
			),
			'Shortcodes' => array(
				'title' => __('Shortcodes', 'learndash-document-library'),
			)
		);
		return apply_filters('settings_section', $settings_sections);
	}

	/**
	 * Adds a section to the User Profile screen to show saved documents.
	 */
	public function ldl_show_user_favorites_on_profile( $user ) {
		$favorites = (array) get_user_meta( $user->ID, 'favorite_documents', true );

		// if ( empty( $favorites ) ) {
		// 	echo 'favorites';
		// 	return;
		// }

		$args = array(
			'post_type'      => 'document', // Use your custom post type slug
			'post__in'       => $favorites,
			'posts_per_page' => -1,
			'orderby'        => 'post__in',
		);

		$favorite_docs = new WP_Query( $args );

		if ( $favorite_docs->have_posts() ) {
			?>
			<h3><?php esc_html_e( 'Favorited Documents', 'learndash-document-library' ); ?></h3>
			<table class="form-table">
				<tr>
					<th><label><?php esc_html_e( 'Documents for Later', 'learndash-document-library' ); ?></label></th>
					<td>
						<ul>
						<?php
						while ( $favorite_docs->have_posts() ) : $favorite_docs->the_post();
							// Get the direct edit link for quick access
							$edit_link = get_edit_post_link( get_the_ID() );
							?>
							<li>
								<a href="<?php the_permalink(); ?>" target="_blank"><?php the_title(); ?></a> 
								<?php if ( $edit_link ) : ?>
									(<a href="<?php echo esc_url( $edit_link ); ?>"><?php esc_html_e( 'Edit', 'learndash-document-library' ); ?></a>)
								<?php endif; ?>
							</li>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
						</ul>
					</td>
				</tr>
			</table>
			<?php
		}
	}
}
