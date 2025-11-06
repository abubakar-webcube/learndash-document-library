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
class LearnDash_Document_Library_Taxonomy {

    /**
     * Register a Taxonomy for LearnDash Document Library
     *
     * @see get_taxonomy_labels() for label keys.
     */
    public function document_library_taxonomy_init() {

        $category_labels = array(
            'name'                  => _x( 'Document Libraries', 'taxonomy general name', 'learndash-document-library' ),
            'singular_name'         => _x( 'learndash document library', 'taxonomy singular name', 'learndash-document-library' ),
            'search_items'          => __( 'Search Libraries', 'learndash-document-library' ),
            'all_items'             => __( 'All Libraries', 'learndash-document-library' ),
            'parent_item'           => __( 'Parent Library', 'learndash-document-library' ),
            'parent_item_colon'     => __( 'Parent Library:', 'learndash-document-library' ),
            'edit_item'             => __( 'Edit Library', 'learndash-document-library' ),
            'update_item'           => __( 'Update Library', 'learndash-document-library' ),
            'add_new_item'          => __( 'Add New Library', 'learndash-document-library' ),
            'new_item_name'         => __( 'New Library Name', 'learndash-document-library' ),
            'menu_name'             => __( 'Libraries', 'learndash-document-library' ),
            'not_found'             => __( 'No libraries found', 'learndash-document-library' ),
            'item_link'             => __( 'Document Library Link', 'learndash-document-library' ),
            'item_link_description' => __( 'A link to a document library.', 'learndash-document-library' ),
            'back_to_items'         => __( '&larr; Go to libraries.', 'learndash-document-library' ),
        );
        
        $category_args = array(
            'hierarchical'      => true,
            'label'             => __( 'Libraries', 'learndash-document-library' ),
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_menu'      => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            // 'menu_position'     => 3,
            'public'            => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'ldl_library' ),
        );

        register_taxonomy( 'ldl_library', array( 'ldl-document' ), $category_args );

        $tag_labels = array(
            'name'                          => _x( 'Document Tags', 'taxonomy general name', 'learndash-document-library' ),
            'singular_name'                 => _x( 'learndash document tag', 'taxonomy singular name', 'learndash-document-library' ),
            'search_items'                  => __( 'Search Tags', 'learndash-document-library' ),
            'all_items'                     => __( 'All Tags', 'learndash-document-library' ),
            'edit_item'                     => __( 'Edit Tags', 'learndash-document-library' ),
            'update_item'                   => __( 'Update Tags', 'learndash-document-library' ),
            'add_new_item'                  => __( 'Add New Tag', 'learndash-document-library' ),
            'new_item_name'                 => __( 'New Tag Name', 'learndash-document-library' ),
            'popular_items'                 => __( 'Popular tags', 'learndash-document-library' ),
            'separate_items_with_commas'    => __( 'Separate tags with commas', 'learndash-document-library' ),
            'add_or_remove_items'           => __( 'Add or remove tags', 'learndash-document-library' ),
            'choose_from_most_used'         => __( 'Choose from the most used tags', 'learndash-document-library' ),
            'menu_name'                     => __( 'Library Tags', 'learndash-document-library' ),
            'not_found'                     => __( 'No tags found', 'learndash-document-library' ),
            'item_link'                     => __( 'Document Tag Link', 'learndash-document-library' ),
            'item_link_description'         => __( 'A link to a document tag.', 'learndash-document-library' ),
        );
        
        $tag_args = array(
            'hierarchical'      => false,
            'label'             => __( 'Document Tags', 'learndash-document-library' ),
            'labels'            => $tag_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_menu'      => true,
            'show_in_nav_menus' => true,
            'show_in_admin_bar' => true,
            // 'menu_position'     => 3,
            'public'            => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'ldl_tag' ),
        );

        register_taxonomy( 'ldl_tag', array( 'ldl-document' ), $tag_args );
        
    }
    
}
