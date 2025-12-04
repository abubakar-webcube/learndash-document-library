<?php
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query, WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude

/**
 * The public-specific functionality of the plugin.
 *
 * @link       http://wooninjas.com/
 * @since      1.0.0
 *
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/admin
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * The public-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/admin
 * @author     Wooninjas <info@wooninjas.com>
 */
class LearnDash_Document_Library_shortcode
{

    /**
     * Initialize the class and set shortcode.
     */
    public function __construct()
    {
        add_shortcode('ldl_document_shortcode', [$this, 'ldl_document_shortcode']);
        add_shortcode('ldl_libraries', [$this, 'ldl_libraries_shortcode']);
        add_shortcode('ldl_libraries_upload', [$this, 'ldl_libraries_upload_shortcode_cb']);
        // add_filter('the_content', [$this, 'add_course_document']);
        // add_action('learndash-course-infobar-access-progress-before', [$this, 'add_course_document'], 10, 3);

        add_filter('learndash_content_tabs', [$this, 'add_course_libraries'], 10, 4);
        // add_filter('learndash_lesson_tabs', [$this, 'add_course_libraries'], 10, 3);
        // add_filter('learndash_topic_tabs', [$this, 'add_course_libraries'], 10, 3);

        add_action('wp_ajax_ldl_get_documents', [$this, 'ldl_ajax_get_documents']);
        add_action('wp_ajax_nopriv_ldl_get_documents', [$this, 'ldl_ajax_get_documents']);
        add_action('wp_ajax_ldl_grid_view', [$this, 'ldl_grid_get_documents']);
        add_action('wp_ajax_nopriv_ldl_grid_view', [$this, 'ldl_grid_get_documents']);
        add_action('wp_ajax_ldl_increment_download', [$this, 'ldl_ajax_increment_documents']);
        add_action('wp_ajax_nopriv_ldl_increment_download', [$this, 'ldl_ajax_increment_documents']);
        add_action('wp_ajax_ldl_switch_view', [$this, 'ldl_switch_view']);
        add_action('wp_ajax_nopriv_ldl_switch_view', [$this, 'ldl_switch_view']);
        // AJAX handler for frontend library upload
        add_action('wp_ajax_ldl_upload_library', [$this, 'ldl_handle_upload_library']);
        add_action('wp_ajax_nopriv_ldl_upload_library', [$this, 'ldl_handle_upload_library']);

        // Hook the function to the AJAX action for logged-in users
        add_action( 'wp_ajax_ldl_toggle_favorite', [$this, 'ldl_toggle_favorite_handler'] );
        add_action( 'wp_ajax_nopriv_ldl_toggle_favorite', [$this, 'ldl_toggle_favorite_handler'] );
        add_action( 'wp_ajax_ldl_get_favorite_documents', [$this, 'ldl_get_favorite_documents'] );
        add_action( 'wp_ajax_noprive_ldl_get_favorite_documents', [$this, 'ldl_get_favorite_documents'] );

        add_action('wp_ajax_ldl_get_pdf_file', [$this, 'ldl_get_pdf_file']);
        add_action('wp_ajax_nopriv_ldl_get_pdf_file', [$this, 'ldl_get_pdf_file']);
    }

    /**
     * add documents to course page
     */
    public function add_course_document($content)
    {
        $post = get_post();
        $defaults = array(
            // By default WP_User_Query will return ALL users. Strange.
            'user_id' => get_current_user_id(),
        );
        $aa = learndash_get_users_for_course($post->ID, $defaults);
        // error_log('role:' . var_export($aa, true));
        
        if (is_singular('sfwd-courses') || is_singular('sfwd-lessons') || is_singular('sfwd-topic') || is_singular('sfwd-quiz')) {
            $posts = get_posts([
                'post_type' => 'ldl-document',
                'post_status' => 'publish',
                'numberposts' => -1
                // 'order'    => 'ASC'  
            ]);
            // error_log('actual_link:' . var_export(is_singular('sfwd-courses'), true));
            $contents = '<style>
                #ldd_Sidebar.doc-panel-view{
                    width: 0px;
                    max-width: 450px;
                    position: fixed;
                    right: 0px;
                    top: 0px;
                    z-index: 9999;
                    height: 100vh;
                    background: #fff;
                    padding: 60px 0px;
                    box-shadow: 0px 0px 6px 0px #e1e1e1;
                    box-sizing: border-box;
                    overflow-x: hidden;
                    pointer-events: none;
                    opacity: 0;
                    transition: .1s;

                }
                #ldd_Sidebar.doc-panel-view.ldd_Sidebar-active{
                    width: 100%;
                    opacity: 1;
                    overflow-x: visible;
                    pointer-events: all;
                }
                #ldd_Sidebar.doc-panel-view h3{
                    padding: 0px 20px;
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul{
                    padding: 20px 0 0px;
                    margin: 0px;
                    list-style-type: none;
                    overflow-y: auto;
                    height: 80vh;
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul::-webkit-scrollbar {
                    width: 10px;
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul::-webkit-scrollbar-track {
                    background: #f1f1f1; 
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul::-webkit-scrollbar-thumb {
                    background: #888; 
                }                
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul::-webkit-scrollbar-thumb:hover {
                    background: #555; 
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul li{
                    border-bottom: 1px solid #eee;
                    padding: 15px 20px;
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul li:nth-child(odd){
                    background: #f5f5f5;
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul li a{
                    padding: 0px;
                    display: block;
                    text-decoration: none;
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul li .ld-side-tble{
                    display:none;
                    transition:  0.01s ; 
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul li a.ld-doc-no-down i{
                    font-size: 16px;
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul li table{
                    background: #fff;
                    margin: 15px 0 0 0;
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul li table tr td button{
                    padding: 9px 16px;
                }
                #ldd_Sidebar.doc-panel-view .ldd_sidebar ul li table tr td button i{
                    font-size: 14px;
                    margin-left: 5px;
                }
                #ldd_Sidebar.doc-panel-view .ldd_closebtn {
                    position: absolute;
                    top: 60px;
                    right: 22px;
                    padding: 0px;
                    border-radius: 100%;
                    text-decoration: none !important;
                    width: 36px;
                    height: 36px;
                    display: inline-block;
                    text-align: center;
                }
                #ldd_Sidebar.doc-panel-view .ldd_closebtn i{
                    font-size: 19px;
                }
                .ldd_sidebar ul li h5{
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin: 0px;
                }
                .ldd_sidebar ul li h5 button{
                    width: 24px;
                    height: 24px;
                    border-radius: 100%;
                    display: grid;
                    place-content: center;
                    padding: 0px;
                }
                .ldd_sidebar ul li h5 button i{
                    font-size: 12px;
                }
                #ldd_main {
                    position:fixed;
                    bottom: 65px;
                    right:0;
                }
                .ldd_openbtn {
                padding: 10px 15px; 
                }
                @media(max-width:767px){
                    #ldd_main{
                        bottom: 25px;
                    }
                }
            </style>';
            $contents .= '<div id="ldd_Sidebar" class="doc-panel-view">
            <button class="ldd_closebtn" onclick="ldd_closeNav()"><i class="fa-solid fa-xmark"></i></button>
            <div class="ldd_sidebar">
            
            <h3>Document</h3><ul>';
            if (is_array($posts) && count($posts) != 0) {
                foreach ($posts as $ldl_document) {
                    $thumbnil_id           = (int) get_post_meta($ldl_document->ID, '_thumbnail_id', true);
                    $attechmet_id          = (int) get_post_meta($ldl_document->ID, '_ldl_attached_file_id', true);
                    $image                 = wp_get_attachment_image($thumbnil_id);
                    $attechmet             = wp_get_attachment_url($attechmet_id);
                    $categories_data       = get_the_terms($ldl_document->ID, 'ldl_category');

                    $contents .=  '<li><h5>' . $ldl_document->post_title . ' <button><i class="fa-solid fa-chevron-down"></i></button></h5><div class="ld-side-tble"><table>';

                    if ($categories_data) {
                        foreach ($categories_data as $categories) {
                            if(isset($categories->name) && $categories->name !== '') {
                                $contents .= '<tr><th>Category</th><td>' . $categories->name . ' </td></tr>';
                            } else {
                                $contents .= '<tr><th>Category</th><td> </td></tr>';
                            }
                        }
                    }
                    $contents .=  '<tr><th>Description</th><td>' . $ldl_document->post_content . '</td></tr>';
                    $contents .= $attechmet !== false ? '<tr><th>Download</th><td><a href="' . $attechmet . '" download="ld_documenet_attechment"> <button>Download <i class="fas fa-download"></i></button></a></li></td></tr>' : '<tr><th>Download</th><td><span class="ld-doc-no-down"> Not Available </span></td></tr>';
                    $contents .= '</table></div></li>';
                }
            }
            $contents .= '</ul></div></div>';
            $contents .= '<div id="ldd_main">';
            $contents .= '<button class="ldd_openbtn" onclick="ldd_openNav()">☰ Documents</button> ';
            $contents .= '</div>';
            $contents .= '<script>';
            $contents .= 'function ldd_openNav() {
                jQuery("#ldd_Sidebar").addClass("ldd_Sidebar-active");
              }';
            $contents .= 'function ldd_closeNav() {
                jQuery("#ldd_Sidebar").removeClass("ldd_Sidebar-active");
              }';
            $contents .= '</script>';
            $contents .= $content;

            return $contents;
        }

        return $content;
    }

    /**
     * add libraries to course page
     */
    public function add_course_libraries($tabs = array(), $context = '', $course_id = 0, $user_id = 0)
    {
        $settings = get_option('ldl_general_settings');
        $is_enabled_categories_filter = isset($settings['enable_categories_filter']) && $settings['enable_categories_filter'] == 1;
        $libraries_enabled  = learndash_get_setting($course_id, 'ld_libraries_enabled', true);
        $selected_libraries = learndash_get_setting($course_id, 'ld_selected_libraries', true);
        $allowed_roles      = learndash_get_setting($course_id, 'ld_allowed_roles', true);
        $current_user       = wp_get_current_user();
        $user_roles         = (array) $current_user->roles;
        if(get_post_type( $course_id ) === 'sfwd-courses'){
            $is_enrolled = sfwd_lms_has_access( $course_id, $user_id );
        } else if(get_post_type( $course_id ) === 'groups'){
            $user_groups = learndash_get_users_group_ids( $user_id );
            $is_enrolled = in_array( $course_id, $user_groups, true );
        }
        if($is_enabled_categories_filter){
            $type = 'category';
        } else {
            $type = 'library';
        }
        // error_log($is_enrolled);
        if(
            !empty($libraries_enabled) && $libraries_enabled === 'on' && $is_enrolled &&
            (
                empty($allowed_roles) || // If no roles selected, allow access
                (
                    is_array($allowed_roles) && count($allowed_roles) > 0 && !empty(array_intersect($allowed_roles, $user_roles))
                ) // If roles selected, current user must match at least one
            )
        ){
            $tabs['library_documents'] = array (
                'id'       => 'ld-course-library_documents', // Unique tab ID
                'icon'     => 'fa-solid fa-book', // Tab icon
                'class'    => 'ld-course-library_documents', // Tab class
                'label'    => $is_enabled_categories_filter ? __('Categories', 'learndash-document-library') : __('Libraries', 'learndash-document-library'), // Tab title
                'content'  => $this->ldl_library_content( $course_id, $type ), // Tab content
                'priority' => 30, // Determines tab order
            );
        }
    
        return $tabs;
    }

    /**
     * Function to get the content of the library tab
     */
    public function ldl_library_content($clt_id, $type = 'library') {
        $ldl_libraries_enabled = learndash_get_setting($clt_id,'ld_libraries_enabled');
        $ldl_selected_libraries = learndash_get_setting($clt_id,'ld_selected_libraries');
        $ldl_selected_categories = learndash_get_setting($clt_id,'ld_selected_categories');
        if(is_array($ldl_selected_libraries) && count($ldl_selected_libraries) > 1){
            $libraries = implode(',', $ldl_selected_libraries);
        } else if(is_array($ldl_selected_libraries) && count($ldl_selected_libraries) == 1){
            $libraries = intval($ldl_selected_libraries[0]);
        } else {
            $libraries = '';
        }
        if(is_array($ldl_selected_categories) && count($ldl_selected_categories) > 1){
            $categories = implode(',', $ldl_selected_categories);
        } else if(is_array($ldl_selected_categories) && count($ldl_selected_categories) == 1){
            $categories = intval($ldl_selected_categories[0]);
        } else {
            $categories = '';
        }
        error_log('categories: ' . print_r($categories,true));
        ob_start();
        ?>
        <div class="ld-course-library-content">
        <?php
            if($type === 'category'){
                echo do_shortcode('[ldl_libraries categories='.$categories.' nested="true"]');
            } else {
                echo do_shortcode('[ldl_libraries libraries='.$libraries.' nested="true"]');
            }
        ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Function to check the server host and the URL
     */
    public function is_external($url) {
        $host = parse_url($url, PHP_URL_HOST);
        return $host && $host !== $_SERVER['HTTP_HOST'];
    }

    /**
     * shortcode functinality
     *
     * @return void
     */
    public function ldl_document_shortcode()
    {
        wp_enqueue_script('dataTablejs');
        wp_enqueue_style('dataTablecss');
        $posts = get_posts([
            'post_type' => 'ldl-document',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids',
            // 'order'    => 'ASC'  
        ]);

        ob_start(); ?>

        <script>
            jQuery(document).ready(function($) {
                var Datatable;
                // Data Table
                Datatable = jQuery('#ldl_shortcode_table').DataTable({
                    columnDefs: [{
                        targets: 0,
                        checkboxes: {
                            selectRow: true,
                        },
                        orderable: false,
                        className: 'select-checkbox',
                    }],
                    select: {
                        style: 'os',
                        selector: 'td:first-child'
                    },
                    order: [
                        [1, 'asc']
                    ]
                });

                // Handle click on download button
                $(document).on('click', '.ld-doc-tble-download a', function(e){
                    const docId = $(this).data('id');
                    if (!docId) return;
                    $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                        action: 'ldl_increment_download',
                        nonce: '<?php echo esc_js(wp_create_nonce('ldl_increment_downloads')); ?>',
                        doc_id: docId
                    });
                });
            });
        </script>


        <h3>Learndash Document List</h3> <br>
        <table id="ldl_shortcode_table" class="display ld-doc-frnt">
            <thead>
                <tr>
                    <th>ID</th>
                    <th class="ld-doc-tble-title">Title</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th class="ld-doc-tble-date">Date</th>
                    <th><?php echo esc_html('Libraries', 'learndash-document-library'); ?></th>
                    <th class="ld-doc-tc">Download</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($posts) && count($posts) != 0) {
                    foreach ($posts as $ldl_document) {
                        $thumbnil_id           = (int) get_post_meta($ldl_document, '_thumbnail_id', true);
                        $dlt   = get_post_meta( $ldl_document, '_ldl_document_upload_type', true );
                        switch ($dlt){
                            case 'file':
                                $file  = esc_url(wp_get_attachment_url(get_post_meta($ldl_document, '_ldl_uploaded_file', true)));
                                break;
                            case 'url':
                                $file = esc_url(get_post_meta($ldl_document, '_ldl_document_url', true));
                                break;
                            case 'library':
                                $file = esc_url(wp_get_attachment_url(get_post_meta($ldl_document, '_ldl_attached_file_id', true)));
                                break;
                            default:
                                break;
                        }
                        // $attechmet_id = (int) get_post_meta($ldl_document, '_ldl_attached_file_id', true);
                        $image = get_the_post_thumbnail_url($ldl_document, 'thumbnail') ?: includes_url('images/media/default.png');
                        // $attechmet = wp_get_attachment_url($attechmet_id);
                        $categories_data = get_the_terms($ldl_document, 'ldl_library');
                        $access_type = get_post_meta($ldl_document, '_ldl_access_type', true) ?: 'preview_download';
                        ?>
                        <tr>
                            <?php // error_log('attechmet:' . var_export($attechmet, true)); ?>
                            <td><?php echo esc_html($ldl_document); ?></td>
                            <td><?php echo wp_kses_post(get_the_title($ldl_document)); ?></td>
                            <td><?php echo wp_kses_post(get_the_excerpt($ldl_document)); ?></td>
                            <td><?php echo '<img src="' . esc_url($image) . '" width="150" height="150" />'; ?></td>
                            <td><?php echo esc_html(get_the_modified_date('', $ldl_document)); ?></td>
                            <td>
                                <?php if ($categories_data) {
                                    $names = [];
                                    foreach ($categories_data as $category) {
                                        if (isset($category->name) && $category->name !== '') {
                                            $names[] = $category->name;
                                        }
                                    }
                                    echo esc_html(implode(', ', $names));
                                } ?>
                            </td>

                            <?php if ($file !== '' && $access_type === 'preview_download') { ?>
                                <td class="ld-doc-tble-download ld-doc-tc"><a data-id="<?php echo esc_attr($ldl_document); ?>" href="<?php echo esc_attr($file); ?>" class="button button-primary"<?php echo ($this->is_external($file) ? ' target="_blank" rel="noopener noreferrer"' : ' download'); ?>>Download</a></td>
                            <?php } else { ?>
                                <td> <span class="button button-primary ld-doc-empty"> <?php echo esc_html__('Preview Only', 'learndash-document-library'); ?> </span> </td>
                            <?php } ?>

                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode function to display libraries
     */
    public function ldl_libraries_shortcode($atts){
        $general_settings = get_option('ldl_general_settings', []);
        $visible_columns = isset( $general_settings['visible_list_columns'] ) && is_array( $general_settings['visible_list_columns'] )
		? array_values( $general_settings['visible_list_columns'] )
		: array( 'image', 'reference', 'title', 'published', 'modified', 'author', 'favorites', 'downloads', 'download' );
        // Helper to normalize attribute into an array of values (handles string, comma list, or array)
        $normalize_to_array = function($val) {
            if (is_array($val)) {
                return $val;
            }
            if ($val === null || $val === '') {
                return [];
            }
            if (is_string($val)) {
                // Split comma separated string, trim whitespace, remove empty parts
                return preg_split('/\s*,\s*/', trim($val), -1, PREG_SPLIT_NO_EMPTY);
            }
            // fallback: cast to array
            return (array) $val;
        };
        $exclude_raw    = $normalize_to_array($atts['exclude'] ?? []);
        $libraries_raw  = $normalize_to_array($atts['libraries'] ?? []);
        $categories_raw = $normalize_to_array($atts['categories'] ?? []);
	    $current_user_id = get_current_user_id();
        $allowed = ['list','grid','folder'];
        $props = [
            'exclude'    => array_values( array_filter( array_map( 'intval', $exclude_raw ) ) ),
            'limit'      => isset($atts['limit']) ? (int) $atts['limit'] : 9,
            'libraries'  => array_values( array_filter( array_map( 'intval', $libraries_raw ) ) ),
            'categories' => array_values( array_filter( array_map( 'intval', $categories_raw ) ) ),
            // 'layout'     => isset($atts['layout']) && in_array( $atts['layout'] ?? 'list', ['list','grid','folder'], true ) ? $atts['layout'] : 'list',
            'layout'     => in_array($atts['layout'] ?? '', $allowed, true) ? $atts['layout'] : ( in_array($general_settings['default_libraries_layout'] ?? '', $allowed, true) ? $general_settings['default_libraries_layout'] : 'list'),
            'search'     => isset($atts['search']) ? filter_var( $atts['search'], FILTER_VALIDATE_BOOLEAN ) : true,
            'restUrl'    => esc_url_raw( rest_url( 'ldl/v1' ) ),
            'restNonce'  => wp_create_nonce( 'wp_rest' ),
		    'visibleColumns' => $visible_columns,
		    'currentUserId'  => $current_user_id,
        ];
        $handle = 'learndash-document-libraries-view-script'; // auto-generated from block.json viewScript
        if ( wp_script_is( $handle, 'registered' ) ) {
            wp_enqueue_script( $handle );
            wp_localize_script( $handle, 'ldl_settings', $general_settings );
        }
        wp_enqueue_style( 'learndash-document-libraries-style' ); // from block.json "style"
        $json      = wp_json_encode( $props, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_QUOT|JSON_HEX_APOS );
        $json_attr = esc_attr( $json );
        // return '<div class="ldl-frontend" data-ldl-root data-props="'.$json_attr.'"></div>';
        // In render_callback (block) and in shortcode callback:
        return '<div class="ldl-frontend" data-ldl-root data-props="' . esc_attr( wp_json_encode( $props ) ) . '"></div>';
    }

    /**
     * Function to switch view
     */
    public function ldl_switch_view() {
        if (!isset($_POST['wp_nonce']) || !wp_verify_nonce($_POST['wp_nonce'], 'ldl_documents')) {
            wp_send_json_error('Invalid nonce.');
            return;
        }
        $atts = isset($_POST['atts']) ? $_POST['atts'] : [];
        $view = isset($_POST['view']) ? sanitize_text_field($_POST['view']) : '';
        // $settings = get_option('ldl_general_settings');
        // if($view !== '' && $settings['default_libraries_layout'] !== $view) {
        if($view !== '') {
            $atts['layout'] = $view;
            $user_have_access = 1;
            ob_start(); // Start buffering
            // if (in_array($atts['layout'], ['list', 'grid']) && !empty($atts['libraries'])) {
            //     foreach ($atts['libraries'] as $library_id) {
            //         $atts['current_library'] = absint($library_id);

            //         $term = get_term($library_id, 'ldl_library');
            //         if (!is_wp_error($term)) {
            //             echo '<h2 class="library-title">' . esc_html($term->name) . '</h2>';
            //         }
            //         // Include view template
            //         include plugin_dir_path(__FILE__) . 'partials/' . $atts['layout'] . '-view.php';
            //     }
            // } else {
            //     include plugin_dir_path(__FILE__) . 'partials/' . $atts['layout'] . '-view.php';
            // }
            if (in_array($atts['layout'], ['list', 'grid', 'folder'])) {
                $form = isset($_POST['form']) ? $_POST['form'] : array();
                if(!empty($form) && is_string($form)){
                    parse_str($form, $form);
                    if(isset($form['lib']) && $form['lib'] !== ''){
                        $atts['libraries'] = explode(',', $form['lib']);
                    } elseif(isset($form['cats']) && $form['cats'] !== ''){
                        $atts['categories'] = explode(',', $form['cats']);
                    }
                }
                if (!empty($atts['libraries'])) {
                    foreach ($atts['libraries'] as $library_id) {
                        $atts['current_library'] = array_map('absint', explode(',', $library_id));
                        $term = get_term($library_id, 'ldl_library');
                        echo '<div class="ldl-libraries">';
                        if (!is_wp_error($term)) {
                            echo '<h2 class="library-title">' . esc_html($term->name) . '</h2>';
                        }
                        include plugin_dir_path(__FILE__) . 'partials/' . $atts['layout'] . '-view.php';
                        echo '</div>';
                    }
                } elseif (!empty($atts['categories'])) {
                    foreach ($atts['categories'] as $category_id) {
                        $atts['current_category'] = array_map('absint', explode(',', $category_id));
                        $term = get_term($category_id, 'category');
                        echo '<div class="ldl-libraries">';
                        if (!is_wp_error($term)) {
                            echo '<h2 class="library-title">' . esc_html($term->name) . '</h2>';
                        }
                        include plugin_dir_path(__FILE__) . 'partials/' . $atts['layout'] . '-view.php';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="ldl-libraries">';
                    include plugin_dir_path(__FILE__) . 'partials/' . $atts['layout'] . '-view.php';
                    echo '</div>';
                }
            } else {
                echo '<div class="ldl-libraries">';
                include plugin_dir_path(__FILE__) . 'partials/' . $atts['layout'] . '-view.php';
                echo '</div>';
            }
            $html = ob_get_clean(); // End buffering and capture output
            wp_send_json_success(['html' => $html]); // Send back to JS
            // $settings['default_libraries_layout'] = $view;
            // update_option('ldl_general_settings', $settings);
            // wp_send_json_success('Layout switched successfully.');
        } else {
            wp_send_json_error(['data' => esc_html('Something went wrong! please reload this page and try again.', 'learndash-document-library')]);
        }
    }

    /**
     * Shortcode function to display upload libraries
     */
    public function ldl_libraries_upload_shortcode_cb(){
        $settings = get_option('ldl_general_settings');
        // Get allowed roles from settings
        if ( ! function_exists( 'get_editable_roles' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/user.php' );
        }
        $allowed_upload = isset($settings['enable_library_upload']) ? $settings['enable_library_upload'] : 0;
        if (!$allowed_upload) {
            return '<div style="color:red;">' . esc_html__('You are not allowed to upload libraries.', 'learndash-document-library') . '</div>';
        }
        $is_restricted_libraries = isset($settings['enable_libraries_restriction']) && $settings['enable_libraries_restriction'] == 1;
        $all_docs = get_posts([
            'post_type' => 'ldl-document',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        $all_roles = get_editable_roles();
        // Get all libraries for parent selection
        $all_libraries = get_terms([
            'taxonomy' => 'ldl_library',
            'hide_empty' => false,
        ]);
        ob_start();
        // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet, WordPress.WP.EnqueuedResources.NonEnqueuedScript
        ?>
        <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"></link>
        <script src="//cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <form method="post" class="ldl-upload-library-form" style="margin:2em auto;background:#fafafa;padding:2em;border-radius:8px;">
            <label for="library_name"><strong><?php esc_html_e('Library Name', 'learndash-document-library'); ?></strong></label><br>
            <input type="text" name="library_name" id="library_name" required style="width:100%;margin-bottom:1em;" />

            <label for="library_description"><strong><?php esc_html_e('Description', 'learndash-document-library'); ?></strong></label><br>
            <textarea name="library_description" id="library_description" rows="3" style="width:100%;margin-bottom:1em;"></textarea>

            <label for="library_parent"><strong><?php esc_html_e('Parent Library (optional)', 'learndash-document-library'); ?></strong></label><br>
            <select name="library_parent" id="library_parent" style="width:100%;margin-bottom:1em;">
                <option value="0"><?php esc_html_e('None', 'learndash-document-library'); ?></option>
                <?php foreach ($all_libraries as $lib) : ?>
                    <option value="<?php echo esc_attr($lib->term_id); ?>"><?php echo esc_html($lib->name); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="library_documents"><strong><?php esc_html_e('Select Documents', 'learndash-document-library'); ?></strong></label><br>
            <select name="library_documents[]" id="library_documents" multiple style="width:100%;margin-bottom:1em;">
                <?php foreach ($all_docs as $doc) : ?>
                    <option value="<?php echo esc_attr($doc->ID); ?>"><?php echo esc_html(get_the_title($doc->ID)); ?></option>
                <?php endforeach; ?>
            </select>

            <?php if ($is_restricted_libraries) : ?>
                <label for="library_password"><strong><?php esc_html_e('Password (optional)', 'learndash-document-library'); ?></strong></label><br>
                <input type="password" name="library_password" id="library_password" style="width:100%;margin-bottom:1em;" />

                <label for="library_user_roles"><strong><?php esc_html_e('Allowed User Roles', 'learndash-document-library'); ?></strong></label><br>
                <select name="library_user_roles[]" id="library_user_roles" multiple style="width:100%;margin-bottom:1em;">
                    <?php foreach ($all_roles as $role_key => $role) : ?>
                        <option value="<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <?php wp_nonce_field('ldl_upload_library', 'ldl_upload_library_nonce'); ?>
            <button type="submit" style="margin-top:1em;"><?php esc_html_e('Create Library', 'learndash-document-library'); ?></button>
        </form>

        <!-- Modal Popup for Processing/Results -->
        <div id="ldl-upload-modal">
            <div style="background:#fff;padding:2em 2em 1em 2em;border-radius:8px;max-width:90vw;width:350px;text-align:center;position:relative;">
                <div id="ldl-upload-modal-spinner" style="margin-bottom:1em;">
                    <span class="ldl-spinner" style="display:inline-block;width:32px;height:32px;border:4px solid #ccc;border-top:4px solid #333;border-radius:50%;animation:ldlspin 1s linear infinite;"></span>
                </div>
                <div id="ldl-upload-modal-message" style="font-size:1.1em;"></div>
                <button id="ldl-upload-modal-close" style="display:none;margin-top:1em;">Close</button>
            </div>
        </div>
        <style>
        @keyframes ldlspin {0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}
        #ldl-upload-modal {
            display: none;
            align-items: center;
            justify-content: center;
            position: fixed;
            z-index: 9999;
            top: 0; left: 0;
            width: 100vw;
            height: 100vh;
            max-width: 100vw;
            background: rgba(0,0,0,0.7);
        }
        </style>
        <script>
        jQuery(document).ready(function($) {
            if ($.fn.select2) {
                $('#library_documents, #library_user_roles, #library_parent').select2({
                    placeholder: 'Select...',
                    allowClear: true
                });
            }
            $('.ldl-upload-library-form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var formData = new FormData($form[0]);
                formData.append('action', 'ldl_upload_library');
                $('#ldl-upload-modal-message').text('');
                $('#ldl-upload-modal-spinner').show();
                $('#ldl-upload-modal-close').hide();
                $('#ldl-upload-modal').css('display','flex');
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $('#ldl-upload-modal-spinner').hide();
                        $('#ldl-upload-modal-message').html(response.data ? response.data : '');
                        $('#ldl-upload-modal-message').css('color', 'red');
                        $('#ldl-upload-modal-close').show();
                        if (response.success) {
                            $('#ldl-upload-modal-message').css('color', 'green');
                            // setTimeout(function() {
                            //     $('#ldl-upload-modal').hide();
                            //     location.reload(); // Reload the page to show updated libraries
                            // }, 2000);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#ldl-upload-modal-spinner').hide();
                        let msg = '<div style="color:red;">Error: ' + error + '</div>';
                        if (xhr.responseText) {
                            msg += '<pre style="text-align:left;max-height:200px;overflow:auto;">' + xhr.responseText + '</pre>';
                        }
                        $('#ldl-upload-modal-message').html(msg);
                        $('#ldl-upload-modal-message').css('color', 'red');
                        $('#ldl-upload-modal-close').show();
                    }
                });
            });
            $('#ldl-upload-modal-close').on('click', function(){
                $('#ldl-upload-modal').hide();
                $('.ldl-upload-library-form')[0].reset();
                $('#library_documents, #library_user_roles, #library_parent').val(null).trigger('change');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Ajax function to get documents for folder view
     */
    public function ldl_ajax_get_documents() {
        if (!isset($_POST['wp_nonce']) || !wp_verify_nonce($_POST['wp_nonce'], 'ldl_documents')) {
            wp_send_json_error('Invalid nonce.');
            return;
        }
        $settings = get_option('ldl_general_settings');
        $is_enabled_categories_filter = isset($settings['enable_categories_filter']) && $settings['enable_categories_filter'] == 1;
        $visible_columns = isset($settings['visible_list_columns']) && is_array($settings['visible_list_columns']) && count($settings['visible_list_columns']) > 0 ? $settings['visible_list_columns'] : [ 'image', 'reference', 'title', 'published', 'modified', 'author', 'favorites', 'downloads', 'download' ];
        $tag = isset($_POST['tag']) ? absint($_POST['tag']) : 0;
        $ptag = isset($_POST['ptag']) ? absint($_POST['ptag']) : 0;
        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        $cterm_id = isset($_POST['cterm_id']) ? absint($_POST['cterm_id']) : 0;
        $limit = absint($_POST['limit'] ?? 9);
        $start = absint($_POST['start'] ?? 0);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $page = max(1, floor($start / $limit) + 1); // Ensure page starts at 1
        $order_by = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
        $order = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';
        if (isset($_POST['cat']) && $_POST['cat'] !== '') {
            $library = absint($_POST['cat']);
        } elseif (isset($_POST['lib']) && $_POST['lib'] !== '') {
            $library = array_map('absint', explode(',', $_POST['lib']));
        } else {
            $library = 0;
        }
        if (isset($_POST['category']) && $_POST['category'] !== '') {
            $category = absint($_POST['category']);
        } else if (isset($_POST['cats']) && $_POST['cats'] !== '') {
            $category = array_map('absint', explode(',', $_POST['cats']));
        } else {
            $category = 0;
        }
        // error_log('category ' . var_export($category,true));

        $query_arg = [
            'post_type'      => 'ldl-document',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'paged'          => $page,
            'fields'         => 'ids',
            'orderby'        => $order_by,
            'order'          => $order,
        ];

        $tax_query = [];
        $set1 = [];
        $set2 = [];

        // Set 1: ldl_library AND ldl_tag
        if (($library && $library !== 0) || ($term_id && $term_id !== 0)) {
            $lib_terms = $library && $library !== 0 ? $library : $term_id;
            if(!is_array($lib_terms) && intval($lib_terms)){
                $taxonomy = get_term($lib_terms);
                if($taxonomy->taxonomy === 'category'){
                    $set1[] = [
                        'taxonomy'          => 'ldl_library',
                        'terms'             => get_term_meta($lib_terms, 'ldl_related_terms', true),
                        'field'             => 'term_id',
                        'include_children'  => true,
                    ];
                } else {
                    $set1[] = [
                        'taxonomy'          => 'ldl_library',
                        'terms'             => $lib_terms,
                        'field'             => 'term_id',
                        'include_children'  => false,
                    ];
                }
            } elseif (is_array($lib_terms)){
                foreach($lib_terms as $lib_term){
                    $taxonomy = get_term($lib_term);
                    if($taxonomy->taxonomy === 'category'){
                        $set1[] = [
                            'taxonomy'          => 'ldl_library',
                            'terms'             => get_term_meta($lib_term, 'ldl_related_terms', true),
                            'field'             => 'term_id',
                            'include_children'  => true,
                        ];
                    } else {
                        $set1[] = [
                            'taxonomy'          => 'ldl_library',
                            'terms'             => $lib_term,
                            'field'             => 'term_id',
                            'include_children'  => false,
                        ];
                    }
                }
            }
        }
        if ($tag && $tag !== 0) {
            $set1[] = [
                'taxonomy'          => 'ldl_tag',
                'terms'             => $tag,
                'field'             => 'term_id',
                'include_children'  => false,
            ];
        }

        // Set 2: category AND post_tag
        if (($category && $category !== 0) || ($cterm_id && $cterm_id !== 0)) {
            $cat_terms = $category && $category !== 0 ? $category : $cterm_id;
            // $terms = get_term_meta($cat_terms, 'ldl_related_terms', true);
            // $set2[] = [
            //     'taxonomy'          => 'ldl_library',
            //     'terms'             => $terms,
            //     'field'             => 'term_id',
            //     'include_children'  => false,
            // ];
            // Check if $cat_terms is an array
            if (is_array($cat_terms)) {
                // Gather terms meta from all categories
                $all_terms = array();
                foreach ($cat_terms as $single_cat) {
                    $terms = get_term_meta($single_cat, 'ldl_related_terms', true);
                    // If terms exist and is an array, merge them
                    if (!empty($terms) && is_array($terms)) {
                        $all_terms = array_merge($all_terms, $terms);
                    } elseif (!empty($terms)) {
                        // If it's a single value, add it
                        $all_terms[] = $terms;
                    }
                }
                // Remove duplicates
                $all_terms = array_unique($all_terms);
            } else {
                // Single category - get terms as before
                $all_terms = get_term_meta($cat_terms, 'ldl_related_terms', true);
            }
            // Only add to tax query if we have terms
            if (!empty($all_terms)) {
                $set2[] = [
                    'taxonomy'          => 'ldl_library',
                    'terms'             => $all_terms,
                    'field'             => 'term_id',
                    'include_children'  => true,
                ];
            }
        }
        if ($ptag && $ptag !== 0) {
            $set2[] = [
                'taxonomy'          => 'post_tag',
                'terms'             => $ptag,
                'field'             => 'term_id',
                'include_children'  => false,
            ];
        }

        // Build the final tax_query
        if (!empty($set1) && !empty($set2)) {
            $tax_query = [
                'relation' => 'OR',
                [
                    'relation' => 'AND',
                    ...$set1
                ],
                [
                    'relation' => 'AND',
                    ...$set2
                ]
            ];
        } elseif (!empty($set1)) {
            $tax_query = [
                'relation' => 'AND',
                ...$set1
            ];
        } elseif (!empty($set2)) {
            $tax_query = [
                'relation' => 'AND',
                ...$set2
            ];
        }

        if (!empty($tax_query)) {
            $query_arg['tax_query'] = $tax_query;
        }
        if($is_enabled_categories_filter){
            if (!isset($query_arg['tax_query']) || empty(array_filter($query_arg['tax_query']))) {
                $categories = get_terms([
                    'taxonomy'   => 'category',
                    'hide_empty' => false,
                    'exclude'    => [1],
                ]);
                if(!empty(array_filter($categories))){
                    $categories = wp_list_pluck($categories, 'term_id');
                    $terms = [];
                    foreach($categories as $cat){
                        $related_terms = get_term_meta($cat, 'ldl_related_terms', true);
                        if(!empty($related_terms) && is_array($related_terms)){
                            $terms = array_merge($terms, $related_terms);
                        }
                    }
                    $query_arg['tax_query'][] = [
                        'taxonomy'          => 'ldl_library',
                        'terms'             => $terms,
                        'field'             => 'term_id',
                        'include_children'  => true,
                    ];
                } else {
                    wp_send_json([
                        'draw' => isset($_POST['draw']) ? absint($_POST['draw']) : '',
                        'data' => [],
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                    ]);
                }
            }
        }

        if (!empty($search)) {
            $query_arg['s'] = $search;
        }

        error_log('$query_arg ' . var_export($query_arg,true));

        $query = new WP_Query($query_arg);
        // error_log('query:' . var_export($query, true));
        $data = [];
    
        foreach ($query->posts as $doc) {
            $image = get_the_post_thumbnail_url($doc, 'thumbnail') ?: includes_url('images/media/default.png');
            $ref   = (int) $doc;
            $auth  = get_the_author_meta('display_name', get_post_field('post_author', $doc));
            $dls   = get_post_meta($doc, '_ldl_downloads', true) ?: 0;
            $dlt   = get_post_meta( $doc, '_ldl_document_upload_type', true );
            $access_type = get_post_meta($doc, '_ldl_access_type', true) ?: 'preview_download';
            switch ($dlt){
                case 'file':
                    $file  = esc_url(wp_get_attachment_url(get_post_meta($doc, '_ldl_uploaded_file', true)));
                    break;
                case 'url':
                    $file = esc_url(get_post_meta($doc, '_ldl_document_url', true));
                    break;
                case 'library':
                    $file = esc_url(wp_get_attachment_url(get_post_meta($doc, '_ldl_attached_file_id', true)));
                    break;
                default:
                    break;
            }

            $allValues = array();
            foreach ($visible_columns as $key => $value) {
                if ($value == 'image') {
                    $allValues[$value] = '<img src="' . esc_url($image) . '" width="60" height="60" />';
                } elseif ($value == 'reference') {
                    $allValues[$value] = esc_html($ref);
                } elseif ($value == 'title') {
                    $allValues[$value] = esc_html(get_the_title($doc));
                } elseif ($value == 'published') {
                    $allValues[$value] = esc_html(get_the_date('', $doc));
                } elseif ($value == 'modified') {
                    $modified_timestamp = strtotime(get_the_modified_date('', $doc));
                    $three_days_ago = strtotime( '-3 days' );
                    if($modified_timestamp > strtotime(get_the_date('', $doc)) && $modified_timestamp >= $three_days_ago){
                        $allValues[$value] = '<div class="modified_date">' . esc_html(get_the_modified_date('', $doc)) . '<span class="recently-updated"><span class="dashicons dashicons-clock"></span><b>Updated</b></span></div>';
                    } else {
                        $allValues[$value] = esc_html(get_the_modified_date('', $doc));
                    }
                } elseif ($value == 'author') {
                    $allValues[$value] = '<span style="text-transform:capitalize;">' . esc_html($auth) . '</span>';
                } elseif ($value == 'favorites') {
                    $user_id = get_current_user_id();
                    $doc_id = $doc;
                    // Fetch the array of saved IDs, ensuring it's an array if meta doesn't exist.
                    $saved_docs = (array) get_user_meta( $user_id, 'favorite_documents', true );
                    $is_bookmarked = in_array( $doc_id, $saved_docs );
                    // Use a span for the icon and text to update separately
                    $icon = $is_bookmarked ? '&#x2764;' : '&#x2661;'; // ❤ (Filled Heart) vs. ♡ (Hollow Heart)
                    $text = $is_bookmarked ? 'Remove Favorite' : 'Add to Favorites';
                    $class = $is_bookmarked ? 'ldl-favorited' : 'ldl-unfavorited';
                    $allValues[$value] = '<span id="ldl-favorite-btn-'.esc_attr($doc_id).'" class="ldl-favorite-btn '.esc_attr($class).'" data-doc-id="'.esc_attr($doc_id).'" data-user_id="'.esc_attr($user_id).'" data-nonce="'.esc_attr(wp_create_nonce("ldl_save_doc_nonce")).'"><span class="ldl-icon">'.$icon.'</span></span>';
                } elseif ($value == 'downloads') {
                    $allValues[$value] = esc_html($dls);
                } elseif ($value == 'download') {
                    // if ($file && $access_type === 'preview_download') {
                        $allValues[$value] = '<button class="ldl_doc_view" data-doc-url="'.esc_url($file).'" data-doc-title="'.esc_attr(get_the_title($doc)).'">'.esc_html__('View', 'learndash-document-library').'</button>' . '<a class="button ldl-download-btn" data-id="'.esc_attr($doc).'" href="' . esc_url($file) . '" download>'.esc_html__('Download', 'learndash-document-library').'</a>';
                        // $allValues[$value] = '<a class="button ldl-download-btn" data-id="'.esc_attr($doc).'" href="' . esc_url($file) . '" download>Download</a>';
                    // } else {
                    //     $allValues[$value] = '<span class="button button-primary ld-doc-empty">Preview Only</span>';
                    // }
                }
            }
            $allValues['is_featured'] = (bool) get_post_meta($doc, '_ldl_featured_document', true);
    
            $data[] = $allValues;
        }
        wp_send_json([
            'draw' => isset($_POST['draw']) ? absint($_POST['draw']) : '',
            'data' => $data,
            'recordsTotal' => $query->found_posts,
            'recordsFiltered' => $query->found_posts,
        ]);
    }

    /**
     * Ajax function to get documents for folder view
     */
    public function ldl_get_favorite_documents() {
        if (!isset($_POST['wp_nonce']) || !wp_verify_nonce($_POST['wp_nonce'], 'ldl_documents')) {
            wp_send_json_error('Invalid nonce.');
            return;
        }
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        if ( !is_user_logged_in() && $user_id === 0 ) {
            wp_send_json_error('User not logged in.');
            return;
        }
        $settings = get_option('ldl_general_settings');
        $is_enabled_categories_filter = isset($settings['enable_categories_filter']) && $settings['enable_categories_filter'] == 1;
        $visible_columns = isset($settings['visible_list_columns']) && is_array($settings['visible_list_columns']) && count($settings['visible_list_columns']) > 0 ? $settings['visible_list_columns'] : [ 'image', 'reference', 'title', 'published', 'modified', 'author', 'favorites', 'downloads', 'download' ];
        $tag = isset($_POST['tag']) ? absint($_POST['tag']) : 0;
        $ptag = isset($_POST['ptag']) ? absint($_POST['ptag']) : 0;
        $term_id = isset($_POST['term_id']) ? absint($_POST['term_id']) : 0;
        $cterm_id = isset($_POST['cterm_id']) ? absint($_POST['cterm_id']) : 0;
        $limit = absint($_POST['limit'] ?? 9);
        $start = absint($_POST['start'] ?? 0);
        $search = sanitize_text_field($_POST['search'] ?? '');
        if ($search === '' && isset($_POST['s'])) {
            $search = sanitize_text_field($_POST['s'] ?? '');
        }
        // error_log($search);
        $page = max(1, floor($start / $limit) + 1); // Ensure page starts at 1
        $order_by = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 1;
        $order = isset($_POST['order'][0]['dir']) ? sanitize_text_field($_POST['order'][0]['dir']) : 'asc';
        if (isset($_POST['cat']) && $_POST['cat'] !== '') {
            $library = absint($_POST['cat']);
        } elseif (isset($_POST['lib']) && $_POST['lib'] !== '') {
            $library = array_map('absint', explode(',', $_POST['lib']));
        } else {
            $library = 0;
        }
        if (isset($_POST['category']) && $_POST['category'] !== '') {
            $category = absint($_POST['category']);
        } else if (isset($_POST['cats']) && $_POST['cats'] !== '') {
            $category = array_map('absint', explode(',', $_POST['cats']));
        } else {
            $category = 0;
        }
        $favorite_docs = get_user_meta( $user_id, 'favorite_documents', true );
        if ( ! is_array( $favorite_docs ) ) {
            $favorite_docs = array();
        }
        // error_log(var_export($favorite_docs,true));
        $query_arg = [
            'post_type'      => 'ldl-document',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'paged'          => $page,
            'post__in'       => !empty($favorite_docs) ? $favorite_docs : [0],
            'fields'         => 'ids',
            'orderby'        => $order_by,
            'order'          => $order,
        ];

        $tax_query = [];
        $set1 = [];
        $set2 = [];

        // Set 1: ldl_library AND ldl_tag
        if (($library && $library !== 0) || ($term_id && $term_id !== 0)) {
            $lib_terms = $library && $library !== 0 ? $library : $term_id;
            if(!is_array($lib_terms) && intval($lib_terms)){
                $taxonomy = get_term($lib_terms);
                if($taxonomy->taxonomy === 'category'){
                    $set1[] = [
                        'taxonomy'          => 'category',
                        'terms'             => $lib_terms,
                        'field'             => 'term_id',
                        'include_children'  => false,
                    ];
                } else {
                    $set1[] = [
                        'taxonomy'          => 'ldl_library',
                        'terms'             => $lib_terms,
                        'field'             => 'term_id',
                        'include_children'  => false,
                    ];
                }
            } elseif (is_array($lib_terms)){
                foreach($lib_terms as $lib_term){
                    $taxonomy = get_term($lib_term);
                    if($taxonomy->taxonomy === 'category'){
                        $set1[] = [
                            'taxonomy'          => 'category',
                            'terms'             => $lib_term,
                            'field'             => 'term_id',
                            'include_children'  => false,
                        ];
                    } else {
                        $set1[] = [
                            'taxonomy'          => 'ldl_library',
                            'terms'             => $lib_term,
                            'field'             => 'term_id',
                            'include_children'  => false,
                        ];
                    }
                }
            }
        }
        if ($tag && $tag !== 0) {
            $set1[] = [
                'taxonomy'          => 'ldl_tag',
                'terms'             => $tag,
                'field'             => 'term_id',
                'include_children'  => false,
            ];
        }

        // Set 2: category AND post_tag
        if (($category && $category !== 0) || ($cterm_id && $cterm_id !== 0)) {
            $cat_terms = $category && $category !== 0 ? $category : $cterm_id;
            $set2[] = [
                'taxonomy'          => 'category',
                'terms'             => $cat_terms,
                'field'             => 'term_id',
                'include_children'  => false,
            ];
        }
        if ($ptag && $ptag !== 0) {
            $set2[] = [
                'taxonomy'          => 'post_tag',
                'terms'             => $ptag,
                'field'             => 'term_id',
                'include_children'  => false,
            ];
        }

        // Build the final tax_query
        if (!empty($set1) && !empty($set2)) {
            $tax_query = [
                'relation' => 'OR',
                [
                    'relation' => 'AND',
                    ...$set1
                ],
                [
                    'relation' => 'AND',
                    ...$set2
                ]
            ];
        } elseif (!empty($set1)) {
            $tax_query = [
                'relation' => 'AND',
                ...$set1
            ];
        } elseif (!empty($set2)) {
            $tax_query = [
                'relation' => 'AND',
                ...$set2
            ];
        }

        if (!empty($tax_query)) {
            $query_arg['tax_query'] = $tax_query;
        }
        if($is_enabled_categories_filter){
            if (!isset($query_arg['tax_query']) || empty(array_filter($query_arg['tax_query']))) {
                $categories = get_terms([
                    'taxonomy'   => 'category',
                    'hide_empty' => false,
                    'exclude'    => [1],
                ]);
                if(!empty(array_filter($categories))){
                    $categories = wp_list_pluck($categories, 'term_id');
                    $query_arg['tax_query'][] = [
                        'taxonomy'          => 'category',
                        'terms'             => $categories,
                        'field'             => 'term_id',
                        'include_children'  => false,
                    ];
                } else {
                    wp_send_json([
                        'draw' => isset($_POST['draw']) ? absint($_POST['draw']) : '',
                        'data' => [],
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                    ]);
                }
            }
        }

        if (!empty($search)) {
            $query_arg['s'] = $search;
        }

        $query = new WP_Query($query_arg);
        // error_log('query:' . var_export($query, true));
        $data = [];
    
        foreach ($query->posts as $doc) {
            $image = get_the_post_thumbnail_url($doc, 'thumbnail') ?: includes_url('images/media/default.png');
            $ref   = (int) $doc;
            $auth  = get_the_author_meta('display_name', get_post_field('post_author', $doc));
            $dls   = get_post_meta($doc, '_ldl_downloads', true) ?: 0;
            $dlt   = get_post_meta( $doc, '_ldl_document_upload_type', true );
            $access_type = get_post_meta($doc, '_ldl_access_type', true) ?: 'preview_download';
            switch ($dlt){
                case 'file':
                    $file  = esc_url(wp_get_attachment_url(get_post_meta($doc, '_ldl_uploaded_file', true)));
                    break;
                case 'url':
                    $file = esc_url(get_post_meta($doc, '_ldl_document_url', true));
                    break;
                case 'library':
                    $file = esc_url(wp_get_attachment_url(get_post_meta($doc, '_ldl_attached_file_id', true)));
                    break;
                default:
                    break;
            }

            $allValues = array();
            foreach ($visible_columns as $key => $value) {
                if ($value == 'image') {
                    $allValues[$value] = '<img src="' . esc_url($image) . '" width="60" height="60" />';
                } elseif ($value == 'reference') {
                    $allValues[$value] = esc_html($ref);
                } elseif ($value == 'title') {
                    $allValues[$value] = esc_html(get_the_title($doc));
                } elseif ($value == 'published') {
                    $allValues[$value] = esc_html(get_the_date('', $doc));
                } elseif ($value == 'modified') {
                    $modified_timestamp = strtotime(get_the_modified_date('', $doc));
                    $three_days_ago = strtotime( '-3 days' );
                    if($modified_timestamp > strtotime(get_the_date('', $doc)) && $modified_timestamp >= $three_days_ago){
                        $allValues[$value] = '<div class="modified_date">' . esc_html(get_the_modified_date('', $doc)) . '<span class="recently-updated"><span class="dashicons dashicons-clock"></span><b>Updated</b></span></div>';
                    } else {
                        $allValues[$value] = esc_html(get_the_modified_date('', $doc));
                    }
                } elseif ($value == 'author') {
                    $allValues[$value] = '<span style="text-transform:capitalize;">' . esc_html($auth) . '</span>';
                } elseif ($value == 'favorites') {
                    $user_id = get_current_user_id();
                    $doc_id = $doc;
                    // Fetch the array of saved IDs, ensuring it's an array if meta doesn't exist.
                    $saved_docs = (array) get_user_meta( $user_id, 'favorite_documents', true );
                    $is_bookmarked = in_array( $doc_id, $saved_docs );
                    // Use a span for the icon and text to update separately
                    $icon = $is_bookmarked ? '&#x2764;' : '&#x2661;'; // ❤ (Filled Heart) vs. ♡ (Hollow Heart)
                    $text = $is_bookmarked ? 'Remove Favorite' : 'Add to Favorites';
                    $class = $is_bookmarked ? 'ldl-favorited' : 'ldl-unfavorited';
                    $allValues[$value] = '<span id="ldl-favorite-btn-'.esc_attr($doc_id).'" class="ldl-favorite-btn '.esc_attr($class).'" data-doc-id="'.esc_attr($doc_id).'" data-user_id="'.esc_attr($user_id).'" data-nonce="'.esc_attr(wp_create_nonce("ldl_save_doc_nonce")).'"><span class="ldl-icon">'.$icon.'</span></span>';
                } elseif ($value == 'downloads') {
                    $allValues[$value] = esc_html($dls);
                } elseif ($value == 'download') {
                    // if ($file && $access_type === 'preview_download') {
                        $allValues[$value] = '<a class="button ldl-download-btn" data-id="'.esc_attr($doc).'" href="' . esc_url($file) . '" download>Download</a>';
                    // } else {
                    //     $allValues[$value] = '<span class="button button-primary ld-doc-empty">Preview Only</span>';
                    // }
                }
            }
            $allValues['is_featured'] = (bool) get_post_meta($doc, '_ldl_featured_document', true);
    
            $data[] = $allValues;
        }
    
        wp_send_json([
            'draw' => isset($_POST['draw']) ? absint($_POST['draw']) : '',
            'data' => $data,
            'recordsTotal' => $query->found_posts,
            'recordsFiltered' => $query->found_posts,
        ]);
    }

    /**
     * Ajax function to increment downloads
     */
    public function ldl_ajax_increment_documents(){
        if(isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'ldl_increment_downloads')) {
            wp_send_json_error('Invalid nonce.');
            return;
        }
        if (!isset($_POST['doc_id']) && empty($_POST['doc_id']) && $_POST['doc_id'] == '' && $_POST['doc_id'] == 0) {
            wp_send_json_error('Document ID not provided.');
            return;
        }
        $doc_id = absint($_POST['doc_id']);
        $downloads = get_post_meta($doc_id, '_ldl_downloads', true) ?: 0;
        $downloads++;
        update_post_meta($doc_id, '_ldl_downloads', $downloads);
        wp_send_json_success([
            'downloads' => $downloads,
        ]);
    }

    /**
     * Function to get the documents for grid view
     */
    public function ldl_grid_get_documents() {
        if (!isset($_POST['wp_nonce']) || !wp_verify_nonce($_POST['wp_nonce'], 'ldl_documents')) {
            wp_send_json_error('Invalid nonce.');
            return;
        }

        $settings = get_option('ldl_general_settings');
        $visible_columns = isset($settings['visible_list_columns']) && is_array($settings['visible_list_columns']) && count($settings['visible_list_columns']) > 0 ? $settings['visible_list_columns'] : [ 'image', 'reference', 'title', 'published', 'modified', 'author', 'downloads', 'download' ];
        $is_enabled_categories_filter = isset($settings['enable_categories_filter']) && $settings['enable_categories_filter'] == 1;
        $limit = isset($_POST['limit']) ? absint($_POST['limit'] ?? 9) : 9;
        $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;
        $search = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
        $tag = isset($_POST['tag']) ? absint($_POST['tag']) : 0;
        $ptag = isset($_POST['ptag']) ? absint($_POST['ptag']) : 0;
        if (isset($_POST['cat']) && $_POST['cat'] !== '') {
            $library = absint($_POST['cat']);
        } elseif (isset($_POST['lib']) && $_POST['lib'] !== '') {
            $library = array_map('absint', explode(',', $_POST['lib']));
        } else {
            $library = 0;
        }
        if (isset($_POST['category']) && $_POST['category'] !== '') {
            $category = absint($_POST['category']);
        } elseif (isset($_POST['cats']) && $_POST['cats'] !== '') {
            $category = array_map('absint', explode(',', $_POST['cats']));
        } else {
            $category = 0;
        }

        $query_arg = [
            'post_type'         => 'ldl-document',
            'post_status'       => 'publish',
            'posts_per_page'    => $limit,
            'paged'             => $paged,
            'fields'            => 'ids',
            'orderby'           => 'title',
            'order'             => 'ASC',
        ];

        if ($search && $search !== '') {
            $query_arg['s'] = $search;
        }
        // --- Begin advanced tax_query logic ---
        $tax_query = [];
        $set1 = [];
        $set2 = [];
        if ($library && $library !== 0) {
            $set1[] = [
                'taxonomy'          => 'ldl_library',
                'terms'             => $library,
                'field'             => 'term_id',
                'include_children'  => false,
            ];
        }
        if ($category && $category !== 0) {
            $set2[] = [
                'taxonomy'          => 'category',
                'terms'             => $category,
                'field'             => 'term_id',
                'include_children'  => false,
            ];
        }
        if ($tag && $tag !== 0) {
            $set1[] = [
                'taxonomy'          => 'ldl_tag',
                'terms'             => $tag,
                'field'             => 'term_id',
                'include_children'  => false,
            ];
        }
        if ($ptag && $ptag !== 0) {
            $set2[] = [
                'taxonomy'          => 'post_tag',
                'terms'             => $ptag,
                'field'             => 'term_id',
                'include_children'  => false,
            ];
        }
        if (!empty($set1) && !empty($set2)) {
            $tax_query = [
                'relation' => 'OR',
                [
                    'relation' => 'AND',
                    ...$set1
                ],
                [
                    'relation' => 'AND',
                    ...$set2
                ]
            ];
        } elseif (!empty($set1)) {
            $tax_query = [
                'relation' => 'AND',
                ...$set1
            ];
        } elseif (!empty($set2)) {
            $tax_query = [
                'relation' => 'AND',
                ...$set2
            ];
        }
        if (!empty($tax_query)) {
            $query_arg['tax_query'] = $tax_query;
        }

        if($is_enabled_categories_filter){
            if(!isset($query_arg['tax_query']) || empty(array_filter($query_arg['tax_query']))){
                $categories = get_terms([
                    'taxonomy'   => 'category',
                    'hide_empty' => false,
                    'exclude'    => [1],
                ]);
                if(!empty(array_filter($categories))){
                    $categories = wp_list_pluck($categories, 'term_id');
                    $query_arg['tax_query'][] = [
                        'taxonomy'          => 'category',
                        'terms'             => $categories,
                        'field'             => 'term_id',
                        'include_children'  => false,
                    ];
                } else {
                    $query_arg['post__in'] = array(0);
                }
            }
        }
    
        $query = new WP_query($query_arg);

        if ($query->have_posts()) {
            $data = [];
            $data['docs'] = '';
            while ($query->have_posts()) {
                $query->the_post();
                $doc_id = get_the_ID();
                $doc_icon = get_the_post_thumbnail( $doc_id, [300,300], array('class' => 'ldl-document-icon') );
                if($doc_icon === ''){
                    $doc_icon = '<img src="' . esc_url(includes_url('images/media/default.png')) . '" alt="' . esc_attr(get_the_title($doc_id)) . '" class="ldl-document-icon" />';
                }
                $doc_title = get_the_title($doc_id);
                $doc_description = get_the_excerpt($doc_id);
                $doc_url = get_permalink($doc_id);
                $doc_downloadable = get_post_meta($doc_id, '_ldl_document_upload_type', true);
                $doc_downloads = get_post_meta($doc_id, '_ldl_downloads', true) ?: 0;
                $access_type = get_post_meta($doc_id, '_ldl_access_type') ?: 'preview_download';
                
                // favorite / unfavorite logic
                $user_id = get_current_user_id();
                // Fetch the array of saved IDs, ensuring it's an array if meta doesn't exist.
                $saved_docs = (array) get_user_meta( $user_id, 'favorite_documents', true );
                $is_bookmarked = in_array( $doc_id, $saved_docs );
                // Use a span for the icon and text to update separately
                $icon = $is_bookmarked ? '&#x2764;' : '&#x2661;'; // ❤ (Filled Heart) vs. ♡ (Hollow Heart)
                $text = $is_bookmarked ? 'Remove Favorite' : 'Add to Favorites';
                $class = $is_bookmarked ? 'ldl-favorited' : 'ldl-unfavorited';

                $data['docs'] .= '<div class="ldl-document-item">';
                if(in_array('image', $visible_columns)){
                    $data['docs'] .= $doc_icon;
                } if(in_array('title', $visible_columns)){
                    $data['docs'] .= '<div class="fav-icon"><h2 class="ldl-document-title">'.esc_html($doc_title).'</h2> <span id="ldl-favorite-btn-'.esc_attr($doc_id).'" class="ldl-favorite-btn '.esc_attr($class).'" data-doc-id="'.esc_attr($doc_id).'" data-user_id="'.esc_attr($user_id).'" data-nonce="'.esc_attr(wp_create_nonce("ldl_save_doc_nonce")).'"><span class="ldl-icon">'.$icon.'</span></span></div>';
                    $data['docs'] .= '<p class="ldl-document-description">'.esc_html($doc_description).'</p>';
                } if($doc_downloadable !== 'none'){
                    if(in_array('downloads', $visible_columns)){
                        $data['docs'] .= '<span class="ldl-document-downloads"><b>'.esc_html($doc_downloads).'</b> '.esc_html__('Downloads', 'learndash-document-library').'</span>';
                    } if(in_array('download', $visible_columns)){
                        $data['docs'] .= '<button class="ldl_doc_view" data-doc-url="'.esc_url($file).'" data-doc-title="'.esc_attr(get_the_title($doc)).'">'.esc_html__('View', 'learndash-document-library').'</button>';
                        $data['docs'] .= '<a class="ldl-download-btn" data-id="'.esc_attr($doc_id).'">'.esc_html__('Download', 'learndash-document-library').'</a>';
                        // if ($access_type === 'preview_download') {
                            // $data['docs'] .= '<a class="ldl-download-btn" data-id="'.esc_attr($doc_id).'">'.esc_html__('Download', 'learndash-document-library').'</a>';
                        // } else {
                        //     $data['docs'] .= '<span class="button button-primary ld-doc-empty">'.esc_html__('Preview Only', 'learndash-document-library').'</span>';
                        // }
                    }
                }
                $data['docs'] .= '</div>';
            }
            // Pagination
            $data['pagination'] = paginate_links( array(
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'current'   => $paged,
                'total'     => $query->max_num_pages,
                'prev_text' => __('« Previous', 'learndash-document-library'),
                'next_text' => __('Next »', 'learndash-document-library'),
                'type'      => 'plain',
            ) );
            wp_reset_postdata();
            wp_send_json([
                'data' => $data,
            ]);
        } else {
            wp_send_json_error('No documents found.');
        }
    }

    /**
     * Function handle upload library
     */
    public function ldl_handle_upload_library() {
        if ( ! function_exists( 'get_editable_roles' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/user.php' );
        }
        $settings = get_option('ldl_general_settings');
        $allowed_upload = isset($settings['enable_library_upload']) ? $settings['enable_library_upload'] : 0;
        if (!$allowed_upload) {
            wp_send_json_error(__('You are not allowed to upload libraries.', 'learndash-document-library'));
        }
        check_ajax_referer('ldl_upload_library', 'ldl_upload_library_nonce');
        $is_restricted_libraries = isset($settings['enable_libraries_restriction']) && $settings['enable_libraries_restriction'] == 1;
        $lib_name = sanitize_text_field($_POST['library_name'] ?? '');
        $lib_desc = sanitize_textarea_field($_POST['library_description'] ?? '');
        $lib_parent = isset($_POST['library_parent']) ? intval($_POST['library_parent']) : 0;
        $doc_ids = isset($_POST['library_documents']) ? array_map('intval', (array)$_POST['library_documents']) : array();
        $lib_password = $is_restricted_libraries ? sanitize_text_field($_POST['library_password'] ?? '') : '';
        $lib_user_roles = $is_restricted_libraries && isset($_POST['library_user_roles']) ? array_map('sanitize_text_field', (array)$_POST['library_user_roles']) : array();
        if (!$lib_name) {
            wp_send_json_error(__('Library name is required.', 'learndash-document-library'));
        }
        $args = array('description' => $lib_desc);
        if ($lib_parent) $args['parent'] = $lib_parent;
        $result = wp_insert_term($lib_name, 'ldl_library', $args);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        $term_id = $result['term_id'];
        foreach ($doc_ids as $doc_id) {
            wp_set_object_terms($doc_id, $term_id, 'ldl_library', true);
        }
        if ($is_restricted_libraries) {
            if ($lib_password) {
                update_term_meta($term_id, 'library_password', $lib_password);
            }
            if (!empty($lib_user_roles)) {
                update_term_meta($term_id, 'library_user_roles', $lib_user_roles);
            }
        }
        wp_send_json_success(__('Library created successfully!', 'learndash-document-library'));
    }

    /**
     * Handles the AJAX request to toggle the favorite status.
     */
    public function ldl_toggle_favorite_handler() {
        // Standard security checks
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'ldl_save_doc_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'You must be logged in to favorite documents.' ) );
        }

        $doc_id = (int) $_POST['doc_id'];
        $meta_key = 'favorite_documents';
        
        // Retrieve the user meta, which will be an array because of the `true` flag and the way we structure it.
        // If no meta exists, get_user_meta returns an empty string, so we cast it to an array.
        // Retrieve the user meta. If it doesn't exist, get_user_meta returns an empty string.
        $favorites = get_user_meta( $user_id, $meta_key, true );

        // Ensure $favorites is always a clean array, even if it returned an empty string or false.
        if ( ! is_array( $favorites ) ) {
            $favorites = array();
        }
        
        $action_performed = '';

        // Check if the document is already a favorite
        if ( in_array( $doc_id, $favorites ) ) {
            // It's a favorite, so remove it
            $favorites = array_diff( $favorites, array( $doc_id ) );
            $action_performed = 'removed';
        } else {
            // It's not a favorite, so add it
            $favorites[] = $doc_id;
            $action_performed = 'saved';
        }

        // Ensure array is clean and save it (WordPress automatically serializes the array)
        $favorites = array_values( array_unique( $favorites ) );
        update_user_meta( $user_id, $meta_key, $favorites );

        wp_send_json_success( array( 
            'action' => $action_performed, 
            'message' => 'Favorite list updated.' 
        ) );
    }

    /**
     * Function handle PDF view
     */
    public function ldl_get_pdf_file() {
        $file_url = isset($_POST['file_url']) ? esc_url_raw($_POST['file_url']) : '';
        if (empty($file_url) || !wp_http_validate_url($file_url)) {
            wp_die('Invalid file URL.');
        }
        $response = wp_remote_get($file_url);
        if (is_wp_error($response)) {
            wp_die('Unable to fetch file.');
        }
        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        if (empty($body)) {
            wp_die('File content empty.');
        }
        header('Content-Type: ' . ($content_type ?: 'application/pdf'));
        echo $body;
        exit;
    }
}

new LearnDash_Document_Library_shortcode();
// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_tax_query, WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude, WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet, WordPress.WP.EnqueuedResources.NonEnqueuedScript