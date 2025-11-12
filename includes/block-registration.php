<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Register the LearnDash Document Libraries block
 */

function ldl_register_libraries_block() {
    // Block build folder (jahan block.json pada hai)
    $block_path = trailingslashit( LEARNDASH_DOCUMENT_LIBRARY_DIR ) . 'blocks/libraries/build';

    // Debug: Check if path exists
    if ( ! file_exists( $block_path . '/block.json' ) ) {
        error_log( 'LDL Block Error: Build folder or block.json not found at ' . $block_path );
        return;
    }

    /**
     * Yahan hum FRONTEND React bundle register kar rahe hain
     * 
     * IMPORTANT:
     * LEARNDASH_DOCUMENT_LIBRARY_URL . 'assets/react-app/app.bundle.js'
     * ko apne React build ke actual path se replace karo.
     * Example:
     *   frontend/dist/assets/index.js
     *   react-app/build/static/js/main.js
     */
    wp_register_script(
        'ldl-document-library-frontend', // handle
        LEARNDASH_DOCUMENT_LIBRARY_URL . 'assets/react-app/app.bundle.js', // <-- apna path lagao
        array(), // dependencies agar koi nahi to empty
        '1.0.0',
        true
    );

    // Gutenberg block register
    register_block_type(
        $block_path,
        array(
            'render_callback' => 'ldl_render_libraries_block',
        )
    );
}
add_action( 'init', 'ldl_register_libraries_block' );

/**
 * Enqueue block editor assets (sirf editor ke liye – ye waise hi reh sakta hai)
 */
function ldl_enqueue_block_editor_assets() {
    // Get general settings for defaults
    $general_settings = get_option( 'ldl_general_settings' );
    $is_enabled_categories_filter = ! empty( $general_settings['enable_categories_filter'] );
    $default_layout = isset( $general_settings['default_libraries_layout'] ) ? $general_settings['default_libraries_layout'] : 'list';

    // Get all libraries for the dropdown
    $libraries = get_terms(
        array(
            'taxonomy'   => 'ldl_library',
            'hide_empty' => false,
        )
    );

    // Get all categories for the dropdown
    $categories = get_terms(
        array(
            'taxonomy'   => 'category',
            'hide_empty' => false,
        )
    );

    // Script handle (block build ke hisaab se)
    $script_handle = 'learndash-document-libraries-editor-script';

    // Pass data to JavaScript
    wp_localize_script(
        $script_handle,
        'ldlBlockData',
        array(
            'isCategoriesFilterEnabled' => $is_enabled_categories_filter,
            'defaultLayout'             => $default_layout,
            'libraries'                 => is_array( $libraries ) ? array_map(
                function ( $term ) {
                    return array(
                        'value' => $term->term_id,
                        'label' => $term->name,
                    );
                },
                $libraries
            ) : array(),
            'categories'                => is_array( $categories ) ? array_map(
                function ( $term ) {
                    return array(
                        'value' => $term->term_id,
                        'label' => $term->name,
                    );
                },
                $categories
            ) : array(),
        )
    );
}
add_action( 'enqueue_block_editor_assets', 'ldl_enqueue_block_editor_assets' );

/**
 * Render callback for the LearnDash Document Libraries block
 * Yahan hum React app ka ROOT div + props bhej rahe hain
 */
function ldl_render_libraries_block( $attributes, $content = '', $block = null ) {
    // React bundle sirf jab block use ho raha ho
    wp_enqueue_script( 'ldl-document-library-frontend' );
    // Shortcode waale defaults yahan copy kiye
    $defaults = array(
        'exclude'    => array(),
        'limit'      => 9,
        'libraries'  => array(),
        'categories' => array(),
        'layout'     => 'list',   // ya $view agar tum upar se la rahe ho
        'search'     => 'true',   // shortcode me string thi, block se bool bhi aa sakta hai
    );
    // Block ke $attributes + defaults merge
    $atts = shortcode_atts( $defaults, $attributes, 'ldl_libraries' );
    // Types normalize karna (React ko clean data mile)
    $props = array(
        'exclude'    => array_map( 'intval', (array) $atts['exclude'] ),
        'limit'      => (int) $atts['limit'],
        'libraries'  => array_map( 'intval', (array) $atts['libraries'] ),
        'categories' => array_map( 'intval', (array) $atts['categories'] ),
        'layout'     => sanitize_text_field( $atts['layout'] ),
        // search: block se bool aa sakta hai, shortcode se string 'true'/'false'
        'search'     => (
            $atts['search'] === true
            || $atts['search'] === 'true'
            || $atts['search'] === 1
            || $atts['search'] === '1'
        ),
    );
    $html  = '<div class="ldl-frontend" data-ldl-root';
    $html .= ' data-props="' . esc_attr( wp_json_encode( $props ) ) . '"></div>';
    return $html;
}
