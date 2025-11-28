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
 * This is used to define Custom Post Type for LearnDash Document Library.
 *
 * @since      1.0.0
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/includes
 * @author     Wooninjas <info@wooninjas.com>
 */
class LearnDash_Document_Library_CPT {

    /**
     * Register a custom post type for LearnDash Document Library
     *
     * @see get_post_type_labels() for label keys.
     */
    public function document_library_cpt_init() {
        $labels = array(
            'name'                  => _x( 'LearnDash Documents', 'Post type general name', 'learndash-document-library' ),
            'singular_name'         => _x( 'LearnDash Document', 'Post type singular name', 'learndash-document-library' ),
            'menu_name'             => _x( 'LearnDash Documents', 'Admin Menu text', 'learndash-document-library' ),
            'name_admin_bar'        => _x( 'LearnDash Document', 'Add New on Toolbar', 'learndash-document-library' ),
            'add_new'               => __( 'Add New', 'learndash-document-library' ),
            'add_new_item'          => __( 'Add New LearnDash Document', 'learndash-document-library' ),
            'new_item'              => __( 'New LearnDash Document', 'learndash-document-library' ),
            'edit_item'             => __( 'Edit LearnDash Document', 'learndash-document-library' ),
            'view_item'             => __( 'View LearnDash Document', 'learndash-document-library' ),
            'all_items'             => __( 'All Documents', 'learndash-document-library' ),
            'search_items'          => __( 'Search LearnDash Documents', 'learndash-document-library' ),
            'parent_item_colon'     => __( 'Parent LearnDash Documents:', 'learndash-document-library' ),
            'not_found'             => __( 'No learndash documents found.', 'learndash-document-library' ),
            'not_found_in_trash'    => __( 'No learndash documents found in Trash.', 'learndash-document-library' ),
            'featured_image'        => _x( 'Document Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'learndash-document-library' ),
            'set_featured_image'    => _x( 'Set document image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'learndash-document-library' ),
            'remove_featured_image' => _x( 'Remove document image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'learndash-document-library' ),
            'use_featured_image'    => _x( 'Use as document image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'learndash-document-library' ),
            'archives'              => _x( 'Document archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'learndash-document-library' ),
            'insert_into_item'      => _x( 'Insert into document', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'learndash-document-library' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this document', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'learndash-document-library' ),
            'filter_items_list'     => _x( 'Filter learndash documents list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'learndash-document-library' ),
            'items_list_navigation' => _x( 'LearnDash Documents list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'learndash-document-library' ),
            'items_list'            => _x( 'LearnDash Documents list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'learndash-document-library' ),
        );
    
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            // 'query_var'          => true,
            'rewrite'            => array( 'slug' => 'ld-document' ),
            'capability_type'    => 'post',
            'menu_icon'          => 'dashicons-list-view',
            'has_archive'        => false,
            'show_in_admin_bar'  => true,
            'show_in_nav_menus'  => true,
            'hierarchical'       => false,
            // 'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments' ),
            'taxonomies'         => array( 'ldl_library', 'ldl_tag' ),
        );

        // $settings = get_option('ldl_general_settings');
        // $enable_categories_filter = !empty($settings['enable_categories_filter']);
        // if($enable_categories_filter){
            $args['taxonomies'][] = 'category';
            // $args['taxonomies'][] = 'post_tag';
        // }
    
        register_post_type( 'ldl-document', $args );
    }

    /**
	 * Flushes rewrite rules.
	 */
	public function flush_rewrite_rules() {
        flush_rewrite_rules();
	}
    
}
