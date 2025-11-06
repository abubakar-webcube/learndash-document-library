<?php
/**
 * Core plugin class for LearnDash Document Library Settings.
 *
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/includes
 * @since      1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the Document Class.
if ( file_exists( LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-document.php' ) ) {
	require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-document.php';
}

if ( ! class_exists( 'LearnDash_Document_Library_Settings' ) ) :

	/**
	 * Main Settings Class.
	 */
	class LearnDash_Document_Library_Settings {

		/**
		 * Plugin Name.
		 *
		 * @var string
		 */
		protected $plugin_name;

		/**
		 * Plugin Version.
		 *
		 * @var string
		 */
		protected $version;

		/**
		 * Register hooks.
		 */
		public function register() {
			add_action( 'learndash_settings_fields', array( $this, 'add_ld_library_settings_metabox' ), 30, 2 );
			add_action( 'save_post', array( $this, 'save_ld_library_settings_metabox' ), 99, 1 );
		}

		/**
		 * Render settings metabox fields.
		 *
		 * @param WP_Post $post Current post object.
		 */
		public function add_ld_library_settings_metabox( $setting_option_fields = array(), $settings_metabox_key = '' ) {
			// Check if we are on the correct settings metabox
            // if ( 'learndash-group-display-content-settings' === $settings_metabox_key || 'learndash-course-display-content-settings' === $settings_metabox_key || 'learndash-lesson-display-content-settings' === $settings_metabox_key || 'learndash-topic-display-content-settings' === $settings_metabox_key || 'learndash-quiz-display-content-settings' === $settings_metabox_key ) {
            if ( 'learndash-group-display-content-settings' === $settings_metabox_key || 'learndash-course-display-content-settings' === $settings_metabox_key ) {
                $array_temp = array();
                $child_section = '';

                foreach ( $setting_option_fields as $index => $item ) {
                    $array_temp[ $index ] = $item;
                    // Inject our fields after "group_courses_order"
                    if ( isset( $item['name'] ) && $item['name'] === 'group_courses_order' || $item['name'] === 'course_completion_page' || $item['name'] === 'forced_lesson_time_enabled' || $item['name'] === 'forced_lesson_time_enabled' || $item['name'] === 'titleHidden' ) {

                        $library_enabled = learndash_get_setting( get_the_ID(), 'ld_libraries_enabled' );
                        $selected_library = learndash_get_setting( get_the_ID(), 'ld_selected_libraries' );
                        $selected_categories = learndash_get_setting( get_the_ID(), 'ld_selected_categories' );
                        $allowed_roles = learndash_get_setting( get_the_ID(), 'ld_allowed_roles' );

                        $settings = get_option('ldl_general_settings');
                        $is_enabled_categories_filter = isset($settings['enable_categories_filter']) && $settings['enable_categories_filter'] == 1;

                        if ( ! empty( $library_enabled ) && $library_enabled === 'on' ) {
                            $child_section = 'open'; // Expand child fields if enabled
                        }

                        global $post;
                        if(isset( $post ) && get_post_type( $post ) === 'groups'){
                            $current_post_type = 'group';
                        } else {
                            $current_post_type = 'course';
                        }

                        // Parent Checkbox
                        $array_temp['ld_libraries_enabled'] = array(
                            'name'                  => 'ld_libraries_enabled',
                            'label'                 => esc_html__( 'Enable Document Libraries', 'learndash-document-library' ),
                            'type'                  => 'checkbox-switch', // << VERY IMPORTANT
                            'value'                 => ( ! empty( $library_enabled ) && $library_enabled === 'on' ? 'on' : '' ),
                            'default'               => '',
                            'help_text'             => esc_html__( 'Enable the document libraries.', 'learndash-document-library' ),
                            'class'                 => '-small',
                            'child_section_state'   => $child_section,
                            'options'               => array(
                                'on'                => '',
                                ''                  => '',
                            ),
                            'attrs'                 => array(
                                'data-ldl-library-nonce' => esc_attr( wp_create_nonce( 'ldl_library_nonce' ) ),
                            ),
                            'rest'                  => array(
                                'show_in_rest'      => LearnDash_REST_API::enabled(),
                                'rest_args'         => array(
                                    'schema'        => array(
                                        'description' => esc_html__( 'Enable the document library.', 'learndash-document-library' ),
                                        'type'        => 'boolean',
                                        'default'     => false,
                                    ),
                                ),
                            ),
                        );

                        if($is_enabled_categories_filter){
                            // Child Field 1: Select Categories
                            $array_temp['ld_selected_categories'] = array(
                                'name'              => 'ld_selected_categories',
                                'label'             => esc_html__( 'Select Document Categories', 'learndash-document-library' ),
                                'type'              => 'multiselect',
                                'parent_setting'    => 'ld_libraries_enabled', // important!
                                'value'             => $selected_categories,
                                'options'           => $this->get_available_categories(), // You'll define this method
                                'help_text'         => sprintf(esc_html__( 'Select which document categories to link with this %s.', 'learndash-document-library' ),esc_html( $current_post_type )),
                                'class'             => '-small',
                                'child_section_state'   => $child_section,
                            );
                        } else {
                            // Child Field 1: Select Libraries
                            $array_temp['ld_selected_libraries'] = array(
                                'name'              => 'ld_selected_libraries',
                                'label'             => esc_html__( 'Select Document Libraries', 'learndash-document-library' ),
                                'type'              => 'multiselect',
                                'parent_setting'    => 'ld_libraries_enabled', // important!
                                'value'             => $selected_library,
                                'options'           => $this->get_available_libraries(), // You'll define this method
                                'help_text'         => sprintf(esc_html__( 'Select which document libraries to link with this %s.', 'learndash-document-library' ),esc_html( $current_post_type )),
                                'class'             => '-small',
                                'child_section_state'   => $child_section,
                            );
                        }

                        // Child Field 2: Allowed User Roles
                        $array_temp['ld_allowed_roles'] = array(
                            'name'              => 'ld_allowed_roles',
                            'label'             => esc_html__( 'Allowed User Roles', 'learndash-document-library' ),
                            'type'              => 'multiselect',
                            'parent_setting'    => 'ld_libraries_enabled', // important!
                            'value'             => $allowed_roles,
                            'options'           => $this->get_available_user_roles(), // You'll define this method
                            'help_text'         => esc_html__( 'Select user roles allowed to access the document library.', 'learndash-document-library' ),
                            'child_section_state'   => $child_section,
                        );
                    }
                }

                return $array_temp;
            }

            return $setting_option_fields;
		}

		/**
		 * Save the settings metabox fields.
		 *
		 * @param int $post_id Post ID.
		 */
		public function save_ld_library_settings_metabox( $post_id ) {
            // Avoid autosaves
            if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
                return;
            }

            // Verify this is not an AJAX call or a revision
            if ( defined('DOING_AJAX') && DOING_AJAX ) {
                return;
            }

            if ( wp_is_post_revision( $post_id ) ) {
                return;
            }

            // Optional: Only run for specific post type
            if ( ! in_array( get_post_type( $post_id ), array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'groups' ), true ) ) {
                return;
            }

            

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            // Verify nonce
            // if ( ! isset($_POST['your_nonce_field']) || 
            //     ! wp_verify_nonce( $_POST['your_nonce_field'], 'your_nonce_action' ) ) {
            //     return;
            // }
            // error_log(var_export([$post_id,$_POST], true));
            $meta_key = '';
            // $meta_keys = array(
            //     'learndash-group-display-content-settings',
            //     'learndash-course-display-content-settings',
            //     'learndash-lesson-display-content-settings',
            //     'learndash-topic-display-content-settings',
            //     'learndash-quiz-display-content-settings',
            // );
            $meta_keys = array(
                'learndash-group-display-content-settings',
                'learndash-course-display-content-settings',
            );

            // Check if nonce is set for each meta key
            foreach ( $meta_keys as $metakey ) {
                if ( isset( $_POST[ $metakey ] ) && isset( $_POST[ $metakey ]['nonce'] ) ) {
                    $meta_key = $metakey;
                    break;
                }
            }

            if ( ! empty( $meta_key ) && isset( $_POST[ $meta_key ]['nonce'] ) ) {
                $nonce = sanitize_text_field( wp_unslash( $_POST[ $meta_key ]['nonce'] ) );
                // error_log(var_export([$meta_key,$nonce,wp_create_nonce($meta_key)],true));
                if ( ! wp_verify_nonce( $nonce, $meta_key ) ) {
                    return;
                }
            }

            // Enable/Disable
            $enabled = isset( $_POST[$meta_key]['ld_libraries_enabled'] ) ? sanitize_text_field( $_POST[$meta_key]['ld_libraries_enabled'] ) : '';
            learndash_update_setting( $post_id, 'ld_libraries_enabled', $enabled );

            // Save Libraries
            if ( isset( $_POST[$meta_key]['ld_selected_libraries'] ) && is_array( $_POST[$meta_key]['ld_selected_libraries'] ) ) {
                $libraries = (array) array_map( 'intval', $_POST[$meta_key]['ld_selected_libraries'] );
                learndash_update_setting( $post_id, 'ld_selected_libraries', $libraries );
            } else {
                learndash_update_setting( $post_id, 'ld_selected_libraries', [] );
            }

            // Save Categories
            if ( isset( $_POST[$meta_key]['ld_selected_categories'] ) && is_array( $_POST[$meta_key]['ld_selected_categories'] ) ) {
                $categories = (array) array_map( 'intval', $_POST[$meta_key]['ld_selected_categories'] );
                learndash_update_setting( $post_id, 'ld_selected_categories', $categories );
            } else {
                learndash_update_setting( $post_id, 'ld_selected_categories', [] );
            }

            // Save Roles
            if ( isset( $_POST[$meta_key]['ld_allowed_roles'] ) && is_array( $_POST[$meta_key]['ld_allowed_roles'] ) ) {
                $roles = (array) array_map( 'sanitize_text_field', $_POST[$meta_key]['ld_allowed_roles'] );
                learndash_update_setting( $post_id, 'ld_allowed_roles', $roles );
            } else {
                learndash_update_setting( $post_id, 'ld_allowed_roles', [] );
            }

            // error_log(var_export([learndash_get_setting($post_id,'ld_libraries_enabled'),learndash_get_setting($post_id,'ld_selected_libraries')],true));
		}

        /**
         * Get available libraries.
         *
         * @return array Available libraries.
         */
        public function get_available_libraries() {
            $libraries = get_terms( array(
                'taxonomy'   => 'ldl_library',
                'hide_empty' => false,
            ) );
            $library_options = array();
            if ( ! empty( $libraries ) && ! is_wp_error( $libraries ) ) {
                foreach ( $libraries as $library ) {
                    $library_options[ $library->term_id ] = esc_html( $library->name );
                }
            }
            return $library_options;
        }

        /**
         * Get available categories.
         *
         * @return array Available categories.
         */
        public function get_available_categories() {
            $categories = get_terms( array(
                'taxonomy'   => 'category',
                'hide_empty' => false,
                // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
                'exclude'   => array(1), // Exclude default category
                'orderby'   => 'name',
            ) );
            $category_options = array();
            if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
                foreach ( $categories as $category ) {
                    $category_options[ $category->term_id ] = esc_html( $category->name );
                }
            }
            return $category_options;
        }

        /**
         * Get available user roles.
         *
         * @return array Available user roles.
         */
        public function get_available_user_roles() {
            global $wp_roles;
            $roles = (array) $wp_roles->get_names();
            return array_map( 'esc_html', $roles );
        }
	}

endif;
