<?php
// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query, WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
// exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Folder View
 * 
 * This file is used to markup the folder view of the LearnDash Document Library plugin.
 * It displays the folder structure and documents within the selected library.
 * 
 * @link       http://wooninjas.com/
 * @since      1.0.2
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/public/partials
 * 
 * Available variables:
 * $atts['libraries'] - The libraries to display (array).
 * $atts['layout'] - The view type (string).
 * $atts['search'] - Whether to show the search bar (boolean).
 * $atts['limit'] - The number of documents to display (integer).
 * $atts['exclude'] - The libraries to hide (array).
 * 
 * @var array $libraries - The libraries to display.
 * @var array $categories - The categories to display.
 * @var string $view - The view type.
 * @var bool $search - Whether to show the search bar.
 * @var int $limit - The number of documents to display.
 * @var array $hide - The libraries to hide.
 */
$formData = isset($_POST['form']) ? $_POST['form'] : array();
if(!empty($formData) && is_string($formData)){
    parse_str($formData, $formData);
}
$libraries = $atts['libraries'] ?? array();
$categories = $atts['categories'] ?? array();
$view = $atts['layout'] ?? 'list';
$search = $atts['search'] ?? true;
$limit = $atts['limit'] ?? 9;
$hide = $atts['exclude'] ?? array();
$nested_in_post = $atts['nested'] ?? 'false';
$have_access = 0;
if(!in_array(1,$hide)){
    $hide[] = 1;
}
if(isset($user_have_access) && $user_have_access === 1){
    $have_access = 1; // User has access if this variable is set
} else {
    $have_access = 0; // Default to true if not set
}
if (isset($atts['current_library'])) {
    $libraries = is_array($atts['current_library']) ? $atts['current_library'] : [ $atts['current_library'] ];
} else {
    if ( !empty( $atts['libraries'] ) ) {
        $libraries = is_string( $atts['libraries'] ) ? explode( ',', $atts['libraries'] ) : ( is_array( $atts['libraries'] ) ? $atts['libraries'] : [] );
    }
}
if (isset($atts['current_category'])) {
    $categories = is_array($atts['current_category']) ? $atts['current_category'] : [ $atts['current_category'] ];
    $libraries = [];
} else {
    if ( !empty( $atts['categories'] ) ) {
        $categories = is_string( $atts['categories'] ) ? explode( ',', $atts['categories'] ) : ( is_array( $atts['categories'] ) ? $atts['categories'] : [] );
    }
}
$settings = get_option('ldl_general_settings');
$visible_columns = isset($settings['visible_list_columns']) && is_array($settings['visible_list_columns']) && count($settings['visible_list_columns']) > 0 ? $settings['visible_list_columns'] : [ 'image', 'reference', 'title', 'published', 'modified', 'author', 'downloads', 'download' ];
$is_enabled_categories_filter = isset($settings['enable_categories_filter']) && $settings['enable_categories_filter'] == 1;
$is_restricted_libraries = isset($settings['enable_libraries_restriction']) && $settings['enable_libraries_restriction'] == 1;
$is_restricted_categories = isset($settings['enable_categories_restriction']) && $settings['enable_categories_restriction'] == 1;
$paged = absint(get_query_var( 'paged')) ?: 1;
$per_page = $limit;
$offset = ($paged - 1) * $per_page;
$taxonomy = 'ldl_library';
if($formData && isset($formData['lib']) && $formData['lib'] === '' || ($categories && empty($libraries))){
    $folder_arg = [
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'include'    => $libraries,
        'exclude'    => $hide,
        'number'     => $per_page,
        'offset'     => $offset,
        'parent'     => 0,
    ];
    $folders = get_terms($folder_arg);
} else {
    $folder_arg = [
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'include'    => $libraries,
        'exclude'    => $hide,
        'number'     => $per_page,
        'offset'     => $offset,
        'parent'     => 0,
    ];
    $folders = get_terms($folder_arg);
}
$total_terms = wp_count_terms('ldl_library', ['hide_empty' => false, 'include' => $libraries, 'exclude' => $hide, 'parent' => 0]);
if($is_enabled_categories_filter){
    $folders = [];
    $folder_arg = [
        'taxonomy'   => 'category',
        'hide_empty' => false,
        'include'    => $categories,
        'exclude'    => $hide,
        'number'     => $per_page,
        'offset'     => $offset,
        'parent'     => 0,
    ];
    $cfolders = get_terms($folder_arg);
    $folders = array_merge($folders,$cfolders);
    $total_terms = 0;
    $ctotal_terms = wp_count_terms('category', ['hide_empty' => false, 'include' => $libraries, 'exclude' => $hide, 'parent' => 0]);
    $total_terms += $ctotal_terms;
}
$total_pages = ceil($total_terms / $per_page);
// unique ID for the instance
// This ID is used to identify the instance of the folder view
$instance_id = uniqid('ldl_');
$js_safe_instanceId = preg_replace('/[^a-zA-Z0-9_]/', '_', $instance_id);
$selected_libraries = implode(',', $libraries);
$selected_categories = implode(',', $categories);
$hidden_libraries = implode(',', $hide);
?>
<script>
    const ldlVisibleColumnsFV<?php echo esc_attr($js_safe_instanceId); ?> = <?php echo json_encode($visible_columns); ?>;
    var have_access<?php echo esc_attr($js_safe_instanceId); ?> = <?php echo esc_attr($have_access); ?>;
</script>
<?php
// Function to render folders recursively
// This function will render the folder structure recursively, allowing for nested folders
if (!function_exists('ldl_render_folder_recursive')) {
    // Function to render folders recursively
    function ldl_render_folder_recursive($terms, $atts, $js_safe_instanceId, $libraries = []) {
        $taxonomy = 'ldl_library';
        $hide = $atts['exclude'] ?? [];

        echo '<ul class="ldl-folder-list">';
        // Loop through each term (folder) and render it
        // The $terms variable contains the list of terms (folders) to be displayed
        foreach ($terms as $term) {
            if (in_array($term->term_id, $hide, true)) {
                continue;
            }

            echo '<li class="ldl-folder ldl_folder_type_' . esc_attr($term->taxonomy) .'">';
            echo '<a href="javascript:void(0);" class="ldl-folder-toggle" data-term-id="' . esc_attr($term->term_id) . '"><i class="fa-solid fa-folder"></i> ' . esc_html($term->name) . '</a>';

            // The data-term-id attribute is used to identify the folder when clicked
            // The term ID is passed to the JavaScript function to load the documents for that folder
            echo '<div class="ldl-documents-table-wrapper" id="ldl-table-' . esc_attr($term->term_id) . esc_attr($js_safe_instanceId) . '" style="display:none;"></div>';

            // The div with class ldl-documents-table-wrapper is where the documents will be loaded via AJAX
            // The ID of this div is unique to each folder, allowing for multiple folders to be displayed simultaneously
            if($term->taxonomy === 'category'){
                $terms = get_term_meta($term->term_id, 'ldl_related_terms', true);
                $children = get_terms([
                    'taxonomy'   => 'ldl_library',
                    'hide_empty' => false,
                    'include'    => $terms,
                    'exclude'    => $hide,
                ]);
            } else {
                $children = get_terms([
                    'taxonomy'   => $taxonomy,
                    'parent'     => $term->term_id,
                    'hide_empty' => false,
                    // 'include'    => $libraries,
                    'exclude'    => $hide,
                ]);
            }

            if (!empty($children)) {
                // Has children: render them recursively, no posts
                echo '<div class="ldl-folder-children" style="display:none;">';
                ldl_render_folder_recursive($children, $atts, $js_safe_instanceId);
                echo '</div>';
            }

            echo '</li>';
        }
        echo '</ul>';
    }
}
?>
<style>
    .ldl-libraries {
        display: grid;
        gap: 2rem;
        margin-bottom: 2rem;
        position: relative;
        min-height: 420px;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?>.ldl-folder-view-wrapper {
        display: grid;
        gap: 2rem;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> ul.ldl-folder-list {
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> ul.ldl-folder-list li {
        list-style-type: none;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> a.ldl-folder-toggle {
        text-decoration: none;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-folder-children {
        padding: 0.5rem 0rem;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-documents-table-wrapper {
        margin: 1rem 0;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .dataTables_wrapper {
        overflow-x: auto;
    }
    
    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-documents > ul {
        margin: 0;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-documents > ul ul {
        margin-left: 1.5em;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-folder-children a.ldl-folder-toggle path {
        color: #333;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-datatable th,
    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-datatable td {
        border: 0;
        border-top: 0 !important;.
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-header form {
        display: flex;
        flex-wrap: wrap;
        /* grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); */
        align-items: flex-start;
        justify-content: flex-start;
        gap: 1rem;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-doc-filter {
        display: flex;
        gap: 1rem;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-doc-view {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-form-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-wrapper {
        display: flex;
        flex-direction: row;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-header {
        flex: 1 auto;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-documents {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
        width: 100%;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-header form select,
    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-header form input[type="search"] {
        flex: 1 1 200px;            /* Grow, shrink, and minimum width */
        min-width: 200px;           /* Prevent items from getting too small */
        max-width: 100%;            /* Don't overflow container */
        box-sizing: border-box; /* Include padding in width calculation */
        height: 46px;
    }
    .ldl-datatable {
        width: 100% !important;
    }
    .<?php echo esc_attr($js_safe_instanceId); ?> span.recently-updated {
        display: flex;
        flex-wrap: wrap;
        background: #046bd2;
        padding: 3px 6px 3px 3px;
        border-radius: 40px;
        max-width: max-content;
        gap: 0.125rem;
        color: #fff;
        align-items: center;
        cursor: default;
        font-size: 10px;
        margin: -10px 0 10px 5px;
    }
    .<?php echo esc_attr($js_safe_instanceId); ?> span.recently-updated span {
        width: 10px;
        height: 10px;
        font-size: 10px;
    }
    .<?php echo esc_attr($js_safe_instanceId); ?> span.recently-updated b {
        word-break: break-word;
        text-transform: uppercase;
    }
    .<?php echo esc_attr($js_safe_instanceId); ?> .modified_date {
        display: flex;
        flex-wrap: nowrap;
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-favorites-modal {
        position: absolute;
        background: #ffffff;
        width: 100%;
        height: 100%;
        display: none;
        flex-direction: column;
    }
    .<?php echo esc_attr($js_safe_instanceId); ?> button.ldl-modal-close-btn {
        margin-left: auto;
        margin-right: 0;
    }

    /* HTML: <div class="loader"></div> */
    .<?php echo esc_attr($js_safe_instanceId); ?> .loader {
        width: 50px;
        aspect-ratio: 1;
        display: grid;
        border: 4px solid #0000;
        border-radius: 50%;
        border-right-color: #046bd2;
        animation: l15 1s infinite linear;
        margin: auto;
    }
    .<?php echo esc_attr($js_safe_instanceId); ?> .loader::before,
    .<?php echo esc_attr($js_safe_instanceId); ?> .loader::after {
        content: "";
        grid-area: 1/1;
        margin: 2px;
        border: inherit;
        border-radius: 50%;
        animation: l15 2s infinite;
    }
    .<?php echo esc_attr($js_safe_instanceId); ?> .loader::after {
        margin: 8px;
        animation-duration: 3s;
    }
    /* Featured/Pinned row styling */
    .<?php echo esc_attr($js_safe_instanceId); ?> tr.ldl-featured-row {
        background-color: #fff9e6 !important;
        border-left: 4px solid #ffc107;
        font-weight: 600;
    }
    .<?php echo esc_attr($js_safe_instanceId); ?> tr.ldl-featured-row:hover {
        background-color: #fff3cd !important;
    }
    @keyframes l15{ 
        100%{
            transform: rotate(1turn)
        }
    }

    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-favorites-modal form{
        display:flex;
        gap: 1rem;
    }
    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-favorites-modal .ldl-modal-close-btn{
        margin-bottom: 40px !important;
    }
    .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-favorites-modal .ldl-favorites-documents{
        margin-top: 30px !important;
    }

    @media (max-width: 768px) {
        .<?php echo esc_attr($js_safe_instanceId); ?> .ldl-doc-filter {
            flex-direction: column;
        }
    }
</style>
<?php
if($nested_in_post === 'false'){
// Restriction check and popup logic
$restricted_term_id = null;
$restriction_type = null;
$restriction_password = '';
$restriction_label = '';
$allowed_roles = array();
$settings = get_option('ldl_general_settings');
$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;
$access_denied = false;
if ($is_restricted_libraries && !empty($libraries)) {
    foreach ($libraries as $lib_id) {
        $lib_id = intval($lib_id);
        $password = get_term_meta($lib_id, 'library_password', true);
        $roles = get_term_meta($lib_id, 'library_user_roles', true);
        if (!empty($roles) && is_array($roles) && !empty($user_roles) && !array_intersect($roles, $user_roles)) {
            $access_denied = true;
            $restriction_label = get_term($lib_id, 'ldl_library')->name;
            $restriction_type = 'library';
            break;
        }
        if (!empty($password)) {
            $restricted_term_id = $lib_id;
            $restriction_type = 'library';
            $restriction_password = $password;
            $restriction_label = get_term($lib_id, 'ldl_library')->name;
            $allowed_roles = $roles;
            break;
        }
    }
} elseif ($is_restricted_categories && !empty($categories)) {
    foreach ($categories as $cat_id) {
        $cat_id = intval($cat_id);
        $password = get_term_meta($cat_id, 'library_password', true);
        $roles = get_term_meta($cat_id, 'library_user_roles', true);
        if (!empty($roles) && is_array($roles) && !empty($user_roles) && !array_intersect($roles, $user_roles)) {
            $access_denied = true;
            $restriction_label = get_term($cat_id, 'category')->name;
            $restriction_type = 'category';
            break;
        }
        if (!empty($password)) {
            $restricted_term_id = $cat_id;
            $restriction_type = 'category';
            $restriction_password = $password;
            $restriction_label = get_term($cat_id, 'category')->name;
            $allowed_roles = $roles;
            break;
        }
    }
} elseif (empty($libraries) && empty($categories)) {
    // Check for global password and roles
    $global_password = isset($settings['global_password']) ? $settings['global_password'] : '';
    $global_roles = isset($settings['global_user_roles']) ? $settings['global_user_roles'] : array();
    if (!empty($global_roles) && $global_roles !== array('all') && !array_intersect($global_roles, $user_roles)) {
        $access_denied = true;
        $restriction_label = 'Document Library';
        $restriction_type = 'global';
    } elseif (!empty($global_password) && ($is_restricted_libraries || $is_restricted_categories)) {
        $restricted_term_id = 0;
        $restriction_type = 'global';
        $restriction_password = $global_password;
        $restriction_label = 'Document Library';
        $allowed_roles = $global_roles;
    }
}
if ($access_denied) : ?>
    <style>
        .ldl-access-denied-modal {
            position: absolute;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            border-radius: 8px;
            box-shadow: 0 0 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ldl-access-denied-modal .ldl-modal-content {
            background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            max-width: 90vw; width: 350px; text-align: center;
        }
        .ldl-access-denied-modal .ldl-modal-content h3 {
            color: #c00;
        }
    </style>
    <div class="ldl-access-denied-modal" class="<?php echo esc_attr($js_safe_instanceId); ?>">
        <div class="ldl-modal-content <?php echo esc_attr($js_safe_instanceId); ?>">
            <h3><?php esc_html_e('Access Denied', 'learndash-document-library'); ?></h3>
            <p><?php printf(esc_html__('Access denied. Your user role is not allowed to view %s.', 'learndash-document-library'), esc_html($restriction_label)); ?></p>
        </div>
    </div>
    <script>jQuery(function($){var folder_view = $('.ldl-folder-view-wrapper.<?php echo esc_attr($js_safe_instanceId); ?>');folder_view.hide();});</script>
<?php elseif ($restricted_term_id !== null && $restriction_password && $have_access === 0) : ?>
    <style>
        .ldl-restriction-modal {
            position: relative;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            border-radius: 8px;
            box-shadow: 0 0 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            min-height: 420px;
        }
        .ldl-restriction-modal .ldl-modal-content {
            background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            max-width: 90vw; width: 350px; text-align: center;
        }
        .ldl-restriction-modal input[type="password"] {
            width: 100%; padding: 0.5rem; margin: 1rem 0; font-size: 1rem;
        }
        .ldl-restriction-modal .ldl-error {
            color: red;
            margin-bottom: 1rem;
        }
        @keyframes ldlspin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <div class="ldl-restriction-modal <?php echo esc_attr($js_safe_instanceId); ?>">
        <div class="ldl-modal-content <?php echo esc_attr($js_safe_instanceId); ?>">
            <h3><?php esc_html_e('Restricted', 'learndash-document-library'); ?></h3>
            <p><?php printf('Access to "%s" is restricted. Please enter the password.', esc_html($restriction_label)); ?></p>
            <div class="ldl-error <?php echo esc_attr($js_safe_instanceId); ?>" style="display:none;"></div>
            <input type="password" class="ldl-restriction-password <?php echo esc_attr($js_safe_instanceId); ?>" placeholder="Password" autocomplete="current-password" />
            <button class="ldl-restriction-submit <?php echo esc_attr($js_safe_instanceId); ?>">Submit</button>
            <div class="ldl-restriction-loader <?php echo esc_attr($js_safe_instanceId); ?>" style="display:none;margin-top:1rem;text-align:center;">
                <span class="ldl-spinner <?php echo esc_attr($js_safe_instanceId); ?>" style="display:inline-block;width:24px;height:24px;border:3px solid #ccc;border-top:3px solid #333;border-radius:50%;animation:ldlspin 1s linear infinite;"></span>
            </div>
        </div>
    </div>
    <script>
        jQuery(function($){
            if(have_access<?php echo esc_attr($js_safe_instanceId); ?> === 0){
                var folder_view = $('.ldl-folder-view-wrapper.<?php echo esc_attr($js_safe_instanceId); ?>');
                folder_view.hide();
                var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
                var termId = <?php echo json_encode($restricted_term_id); ?>;
                var restrictionType = <?php echo json_encode($restriction_type); ?>;
                $('.ldl-restriction-submit.<?php echo esc_attr($js_safe_instanceId); ?>').on('click', function(){
                    var pass = $('.ldl-restriction-password.<?php echo esc_attr($js_safe_instanceId); ?>').val();
                    $('.ldl-error.<?php echo esc_attr($js_safe_instanceId); ?>').hide();
                    $('.ldl-restriction-loader.<?php echo esc_attr($js_safe_instanceId); ?>').show();
                    $('.ldl-restriction-submit.<?php echo esc_attr($js_safe_instanceId); ?>').prop('disabled', true);
                    $.post(ajaxurl || '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                        action: 'ldl_verify_restriction_password',
                        term_id: termId,
                        restriction_type: restrictionType,
                        password: pass,
                        _ajax_nonce: '<?php echo esc_js(wp_create_nonce('ldl_restriction_password')); ?>'
                    }, function(resp){
                        $('.ldl-restriction-loader.<?php echo esc_attr($js_safe_instanceId); ?>').hide();
                        $('.ldl-restriction-submit.<?php echo esc_attr($js_safe_instanceId); ?>').prop('disabled', false);
                        if (resp.success) {
                            // sessionStorage.setItem(storageKey, '1');
                            $('.ldl-restriction-modal.<?php echo esc_attr($js_safe_instanceId); ?>').fadeOut(200, function(){
                                $('.ldl-folder-view-wrapper.<?php echo esc_attr($js_safe_instanceId); ?>').fadeIn(200);
                            });
                        } else {
                            $('.ldl-error.<?php echo esc_attr($js_safe_instanceId); ?>').text(resp.data || 'Incorrect password.').show();
                        }
                    });
                });
                $('.ldl-restriction-password.<?php echo esc_attr($js_safe_instanceId); ?>').on('keypress', function(e){
                    if (e.which === 13) $('.ldl-restriction-submit.<?php echo esc_attr($js_safe_instanceId); ?>').click();
                });
            }
        });
    </script>
<?php endif; } ?>
<div class="ldl-folder-view-wrapper <?php echo esc_attr($js_safe_instanceId); ?>">
    <?php if (!empty($folders)) : ?>
        <div class="ldl-view-header">
            <form method="get" class="ldl-view-form" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                <?php if(empty(array_filter($libraries))){?>
                    <select name="cat">
                        <option value=""><?php esc_html_e('Select Library', 'learndash-document-library'); ?></option>
                        <?php
                        if($is_enabled_categories_filter){
                            $library_terms = [];
                            if(!empty($category_terms) && is_array($category_terms)){
                                $library_terms = get_terms([
                                    'taxonomy' => 'ldl_library',
                                    'hide_empty' => false,
                                    'include' => $category_terms,
                                    'exclude' => $hide,
                                ]);
                            }
                        } else {
                            $library_terms = get_terms([
                                'taxonomy' => 'ldl_library',
                                'hide_empty' => false,
                                'include' => $libraries,
                                'exclude' => $hide,
                            ]);
                        }
                        foreach ($library_terms as $term) {
                            $selected = (isset($_GET['cat']) && $_GET['cat'] == $term->term_id) ? 'selected' : '';
                            echo '<option value="' . esc_attr($term->term_id) . '" ' . esc_attr($selected) . '>' . esc_html($term->name) . '</option>';
                        }
                        ?>
                    </select>
                    <?php if($is_enabled_categories_filter){?>
                    <select name="category">
                        <option value=""><?php esc_html_e('Select Category', 'learndash-document-library'); ?></option>
                        <?php
                        $library_terms = get_terms([
                            'taxonomy' => 'category',
                            'hide_empty' => false,
                            'include' => $libraries,
                            'exclude' => $hide,
                        ]);
                        foreach ($library_terms as $term) {
                            $selected = (isset($_GET['category']) && $_GET['category'] == $term->term_id) ? 'selected' : '';
                            echo '<option value="' . esc_attr($term->term_id) . '" ' . esc_attr($selected) . '>' . esc_html($term->name) . '</option>';
                        }
                        ?>
                    </select>
                    <?php } ?>
                <?php } ?>
                <?php if($is_enabled_categories_filter){ ?>
                <select name="ptag">
                    <option value=""><?php esc_html_e('Select Tag', 'learndash-document-library'); ?></option>
                    <?php
                    $tag_terms = get_terms([
                        'taxonomy' => 'post_tag',
                        'hide_empty' => false,
                    ]);
                    foreach ($tag_terms as $term) {
                        $selected = (isset($_GET['ptag']) && $_GET['ptag'] == $term->term_id) ? 'selected' : '';
                        echo '<option value="' . esc_attr($term->term_id) . '" ' . esc_attr($selected) . '>' . esc_html($term->name) . '</option>';
                    }
                    ?>
                </select>
                <?php } else { ?>
                <select name="tag">
                    <option value=""><?php esc_html_e('Select Tag', 'learndash-document-library'); ?></option>
                    <?php
                    $tag_terms = get_terms([
                        'taxonomy' => 'ldl_tag',
                        'hide_empty' => false,
                    ]);
                    foreach ($tag_terms as $term) {
                        $selected = (isset($_GET['tag']) && $_GET['tag'] == $term->term_id) ? 'selected' : '';
                        echo '<option value="' . esc_attr($term->term_id) . '" ' . esc_attr($selected) . '>' . esc_html($term->name) . '</option>';
                    }
                    ?>
                </select>
                <?php } ?>
                <select name="view">
                    <option value=""><?php esc_html_e('Select layout', 'learndash-document-library'); ?></option>
                    <option value="grid" <?php selected($view, 'grid'); ?>><?php esc_html_e('Grid', 'learndash-document-library'); ?></option>
                    <option value="list" <?php selected($view, 'list'); ?>><?php esc_html_e('List', 'learndash-document-library'); ?></option>
                    <option value="folder" <?php selected($view, 'folder'); ?>><?php esc_html_e('Folder', 'learndash-document-library'); ?></option>
                </select>
                <input type="hidden" name="action" value="ldl_grid_view">
                <input type="hidden" name="lib" value="<?php echo esc_attr($selected_libraries); ?>">
                <input type="hidden" name="cats" value="<?php echo esc_attr($selected_categories); ?>">
                <input type="hidden" name="hid" value="<?php echo esc_attr($hidden_libraries); ?>">
                <input type="hidden" name="limit" value="<?php echo esc_attr($limit); ?>">
                <input type="hidden" name="paged" value="<?php echo esc_attr($paged); ?>">
                <input type="hidden" name="wp_nonce" value="<?php echo esc_attr(wp_create_nonce('ldl_documents')); ?>">
                <input type="hidden" name="wp_refer" value="<?php echo esc_attr(wp_get_referer()); ?>">
                <?php if ($search) { ?>
                <input type="search" name="search" placeholder="<?php esc_attr_e('Search documents...', 'learndash-document-library'); ?>" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>">
                <?php } ?>
                <button type="submit" class="search"><?php esc_html_e('Filter', 'learndash-document-library'); ?></button>
                <button type="submit" class="reset"><?php esc_html_e('Reset', 'learndash-document-library'); ?></button>
                <button type="button" class="ldl-favorites"><?php esc_html_e('Favorites', 'learndash-document-library'); ?></button>
            </form>
        </div>
        <div class="ldl-view-documents">
        <?php ldl_render_folder_recursive($folders, $atts, $js_safe_instanceId, $libraries); ?>
        </div>
        <div class="ldl-view-documents-pagination">
        <?php
        // Pagination
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo paginate_links([
            'base'      => add_query_arg('paged', '%#%'),
            'format'    => '',
            'prev_text' => __('« Prev'),
            'next_text' => __('Next »'),
            'total'     => $total_pages,
            'current'   => $paged
        ]); ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('No libraries found.', 'learndash-document-library'); ?></p>
    <?php endif; ?>
    <div class="ldl-favorites-modal">
        <button class="ldl-modal-close-btn" data-dismiss="modal" aria-label="Close modal">&times;</button>
        <div class="loader"></div>
        <div class="ldl-favorites-documents"></div>
    </div>
    <div id="ldlDocPreviewModal" class="ldl-modal" style="display:none;">
        <div class="ldl-modal-content">
            <span class="ldl-modal-close">&times;</span>
            <h3 id="ldlDocTitle"></h3>
            <div id="ldlDocViewer" class="ldl-viewer"></div>
        </div>
    </div>
</div>
<script>
    jQuery(document).ready(function($) {
        let <?php echo esc_attr($js_safe_instanceId); ?> = '';
        let uniqueId = '<?php echo esc_attr($js_safe_instanceId); ?>';
        const limit = <?php echo (int) $limit; ?>;
        const columnLabels = {
            image: 'Image',
            reference: 'Reference',
            title: 'Title',
            published: 'Published',
            modified: 'Last Modified',
            author: 'Author',
            favorites: 'Favorites',
            downloads: 'Downloads',
            download: 'Download'
        };
        const allColumns = [
            { data: 'image', orderable: false },
            { data: 'reference' },
            { data: 'title' },
            { data: 'published' },
            { data: 'modified' },
            { data: 'author' },
            { data: 'favorites' },
            { data: 'downloads' },
            { data: 'download', orderable: false }
        ];
        const columns = allColumns.filter(col => ldlVisibleColumnsFV<?php echo esc_attr($js_safe_instanceId); ?>.includes(col.data));
        const visibleThs = ldlVisibleColumnsFV<?php echo esc_attr($js_safe_instanceId); ?>.map(col => `<th>${columnLabels[col] || col}</th>`).join('');
        function ldl_documents(){
            $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-folder-toggle').on('click', function (e) {
                e.preventDefault();

                const $toggle = $(this);
                const $category = $(this).parent('.ldl_folder_type_category');
                const folderId = $toggle.data('term-id');
                const $li = $toggle.closest('li');
                const $ancestors = $li.parents('li');
                const $childFolders = $li.children('.ldl-folder-children');
                const $tableWrapper = $('#ldl-table-' + folderId + uniqueId);
                const $icon = $toggle.find('i, svg');

                // Define folders to keep open (current + ancestors)
                const $preserve = $li.add($ancestors);

                // Collapse all unrelated folders
                $('.<?php echo esc_attr($js_safe_instanceId); ?> li').not($preserve).removeClass('open')
                    .find('.ldl-folder-toggle i, .ldl-folder-toggle svg').removeClass('fa-folder-open').addClass('fa-folder');

                $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-folder-children').not($preserve.find('.ldl-folder-children')).slideUp();

                $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-documents-table-wrapper').not($tableWrapper).each(function () {
                    const id = $(this).attr('id');
                    try {
                        if ($(this).hasClass('loaded')) {
                            const dt = $('#' + id.replace('table', 'dt')).DataTable();
                            if (dt) dt.destroy();
                        }
                    } catch (e) {}
                    $(this).removeClass('loaded').empty().slideUp();
                });

                // Toggle current folder open/close
                const isOpen = $li.hasClass('open');
                if (isOpen) {
                    $li.removeClass('open');
                    $icon.removeClass('fa-folder-open').addClass('fa-folder');
                    $childFolders.slideUp();
                    $tableWrapper.slideUp();
                    return;
                } else {
                    $li.addClass('open');
                    $icon.removeClass('fa-folder').addClass('fa-folder-open');
                }

                // Expand children (if any)
                if ($childFolders.length > 0) {
                    $childFolders.slideDown();
                }

                // Load and show documents table
                if (!$tableWrapper.hasClass('loaded') && $category.length === 0) {
                    $tableWrapper.addClass('loaded');
                    $tableWrapper.html(`<table class="ldl-datatable" id="ldl-dt-${folderId + uniqueId}"><thead><tr>${visibleThs}</tr></thead></table>`);

                    $('#ldl-dt-' + folderId + uniqueId).DataTable({
                        serverSide: true,
                        processing: true,
                        lengthChange: false,
                        searching: false,
                        autoWidth: false,
                        ajax: {
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'POST',
                            data: {
                                action: 'ldl_get_documents',
                                term_id: folderId,
                                limit: limit,
                                wp_nonce: '<?php echo esc_js(wp_create_nonce('ldl_documents')); ?>',
                                wp_refer: '<?php echo esc_js(wp_get_referer()); ?>',
                            }
                        },
                        columns: columns,
                        pageLength: limit,
                        responsive: true,
                        order: [[1, 'asc']],
                        // Apply row styling based on featured status
                        createdRow: function(row, data, dataIndex) {
                            // Check if this row is featured
                            if (data.is_featured === true || data.is_featured === 1 || data.is_featured === '1') {
                                $(row).addClass('ldl-featured-row');
                            }
                        },
                        // Custom ordering function to keep featured rows at top
                        drawCallback: function(settings) {
                            // Get the API instance
                            const api = this.api();
                            
                            // Reorder rows: featured first, then regular rows
                            const rows = api.rows({page: 'current'}).nodes();
                            const $rows = $(rows);
                            
                            // Separate featured and regular rows
                            const featuredRows = $rows.filter('.ldl-featured-row').detach();
                            const regularRows = $rows.not('.ldl-featured-row');
                            
                            // Prepend featured rows to the table body
                            $(api.table().body()).prepend(featuredRows);
                        }
                    });
                }

                if($category.length === 0){
                    // if (table.rows().count() > 0) {
                        $tableWrapper.slideDown();
                    // } else {
                    //     $tableWrapper.html('');
                    // }
                }

                // If parent has a documents table open, hide it (but do not close the parent)
                const $parentWrapper = $li.parents('li').children('.ldl-documents-table-wrapper');
                $parentWrapper.each(function () {
                    if ($(this).is(':visible')) {
                        const id = $(this).attr('id');
                        try {
                            if ($(this).hasClass('loaded')) {
                                const dt = $('#' + id.replace('table', 'dt')).DataTable();
                                if (dt) dt.destroy();
                            }
                        } catch (e) {}
                        $(this).removeClass('loaded').empty().slideUp();
                    }
                });
                initializeDocPreview();
            });
            // Handle download button click
            // This function will increment the download count for the document when the download button is clicked
            $(document).on('click', '.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-download-btn', function (e) {
                const docId = $(this).data('id');
                if (!docId) return;
                $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    action: 'ldl_increment_download',
                    nonce: '<?php echo esc_js(wp_create_nonce('ldl_increment_downloads')); ?>',
                    doc_id: docId
                });
            });
            $(document).on('click', '.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-favorite-btn', function() {
                var button = $(this);
                // --- 1. Single-Click Restriction ---
                if (button.hasClass('is-processing')) {
                    return; // Exit if already processing
                }
                
                var doc_id = button.data('doc-id');
                var user_id = button.data('user_id');
                var nonce = button.data('nonce');
                var is_favorited = button.hasClass('ldl-favorited');
                
                button.addClass('is-processing'); // Lock the button
                button.find('.ldl-text').text('Updating...'); // Feedback

                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'ldl_toggle_favorite', // New action hook name
                        doc_id: doc_id,
                        user_id: user_id,
                        security: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var newIcon = response.data.action === 'saved' ? '&#x2764;' : '&#x2661;';
                            var newText = response.data.action === 'saved' ? 'Remove Favorite' : 'Add to Favorites';
                            
                            // Update button state and appearance
                            button.find('.ldl-icon').html(newIcon);
                            button.find('.ldl-text').text(newText);
                            
                            if (response.data.action === 'saved') {
                                button.removeClass('ldl-unfavorited').addClass('ldl-favorited');
                            } else {
                                button.removeClass('ldl-favorited').addClass('ldl-unfavorited');
                            }
                        } else {
                            // Revert text on failure
                            button.find('.ldl-text').text(is_favorited ? 'Remove Favorite' : 'Add to Favorites');
                            console.error('Error:', response.data.message);
                        }
                    },
                    error: function() {
                        // Revert text on AJAX error
                        button.find('.ldl-text').text(is_favorited ? 'Remove Favorite' : 'Add to Favorites');
                        console.error('AJAX Error occurred.');
                    },
                    complete: function() {
                        button.removeClass('is-processing'); // Unlock the button
                    }
                });
            });
        }
        ldl_documents();
        // Handle form submission
        $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-form').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const limit = <?php echo intval($limit); ?>;
            var formData = $form.serialize();
            formData += '&action=ldl_switch_view';
            const $tableWrapper = $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-documents');
            if($form.find('[name="view"]').val() === 'folder') {
                if($('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-documents-table').length > 0) {
                    <?php echo esc_attr($js_safe_instanceId); ?>.ajax.reload(); // Refresh DataTable with new filters
                    return;
                }
                // If the view is set to folder, we need to add new table wrapper and hide the old one
                $tableWrapper.hide();
                $tableWrapper.after($('<div class="ldl-view-documents-table"></div>'));
                const columnLabels = {
                    image: 'Image',
                    reference: 'Reference',
                    title: 'Title',
                    published: 'Published',
                    modified: 'Last Modified',
                    author: 'Author',
                    favorites: 'Favorites',
                    downloads: 'Downloads',
                    download: 'Download'
                };
                const allColumns = [
                    { data: 'image', orderable: false },
                    { data: 'reference' },
                    { data: 'title' },
                    { data: 'published' },
                    { data: 'modified' },
                    { data: 'author' },
                    { data: 'favorites' },
                    { data: 'downloads' },
                    { data: 'download', orderable: false }
                ];
                // Filter columns based on visible keys
                const columns = allColumns.filter(col => ldlVisibleColumnsFV<?php echo esc_attr($js_safe_instanceId); ?>.includes(col.data));
                const visibleThs = ldlVisibleColumnsFV<?php echo esc_attr($js_safe_instanceId); ?>.map(col => `<th>${columnLabels[col] || col}</th>`).join('');
                $('.ldl-view-documents-table').html(`<table class="ldl-datatable" id="<?php echo esc_attr($js_safe_instanceId); ?>"><thead><tr>${visibleThs}</tr></thead></table>`);
                <?php echo esc_attr($js_safe_instanceId); ?> = $('#<?php echo esc_attr($js_safe_instanceId); ?>').DataTable({
                    lengthChange: false,
                    serverSide: true,
                    processing: true,
                    searching: false,
                    ajax: {
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'POST',
                        data:  function (d) {
                            // Inject filter values from form into the ajax request
                            const form = $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-form');
                            form.serializeArray().forEach(({ name, value }) => {
                                d[name] = value;
                            });
                            d.action = 'ldl_get_documents';
                            d.limit = limit;
                        }
                    },
                    columns: columns,
                    pageLength: limit,
                    responsive: true,
                    order: [[1, 'asc']],

                    // Apply row styling based on featured status
                    createdRow: function(row, data, dataIndex) {
                        // Check if this row is featured
                        if (data.is_featured === true || data.is_featured === 1 || data.is_featured === '1') {
                            $(row).addClass('ldl-featured-row');
                        }
                    },
                    // Custom ordering function to keep featured rows at top
                    drawCallback: function(settings) {
                        // Get the API instance
                        const api = this.api();
                        // Reorder rows: featured first, then regular rows
                        const rows = api.rows({page: 'current'}).nodes();
                        const $rows = $(rows);
                        // Separate featured and regular rows
                        const featuredRows = $rows.filter('.ldl-featured-row').detach();
                        const regularRows = $rows.not('.ldl-featured-row');
                        // Prepend featured rows to the table body
                        $(api.table().body()).prepend(featuredRows);
                    }
                });
            }
            if ($form.find('[name="view"]').val() === 'list' || $form.find('[name="view"]').val() === 'grid') {
                // If the view is set to list, we need to remove the old table wrapper and show the new one
                $tableWrapper.show();
                $tableWrapper.next().remove();
                $.ajax({
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data: {
                        action: 'ldl_switch_view',
                        wp_nonce: $form.find('[name="wp_nonce"]').val(),
                        form: $form.serialize(),
                        atts: <?php echo json_encode($atts); ?>,
                        view: $form.find('[name="view"]').val(),
                    },
                    success: function (response) {
                        if(response.success){
                            const newContent = $(response.data.html);
                            $form.closest('.ldl-libraries').after(newContent);
                            $form.closest('.ldl-libraries').remove();
                        } else {
                            $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-documents-table').html('<h2>' + response.data + '</h2>');
                            // Handle error if needed
                            console.error('Error switching view:', response.data);
                        }
                    }
                });
            }
        });
        // Reset button functionality
        $('.<?php echo esc_attr($js_safe_instanceId); ?> .reset').on('click', function(e) {
            e.preventDefault();
            const form = $(this).closest('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-form');
            form.find('select[name="cat"]').val('');
            form.find('select[name="tag"]').val('');
            form.find('input[name="search"]').val('');
            $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-folder.open a').trigger('click'); // Close all open folders
            $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-documents-table').remove(); // Remove the table wrapper if it exists
            // form.submit();
            $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-documents').show();
        });
        $(document).on('click', '.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-form-favorites .reset', function(e) {
            e.preventDefault();
            const form = $(this).closest('.ldl-view-form-favorites');
            form.find('select[name="cat"]').val('');
            form.find('select[name="category"]').val('');
            form.find('select[name="tag"]').val('');
            form.find('select[name="ptag"]').val('');
            form.find('input[name="s"]').val('');
            form.submit();
        });
        $(document).on('click', '.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-favorites', function(e){
            e.preventDefault();

            // --- SETUP STEPS ---
            const $instanceWrapper = $('.<?php echo esc_attr($js_safe_instanceId); ?>');
            const $favTableWrapper = $instanceWrapper.find('.ldl-favorites-documents');
            const $loader = $instanceWrapper.find('.loader'); // Select your loader element here
            
            // Show the modal and the loader immediately
            $instanceWrapper.find('.ldl-favorites-modal').css('display', 'flex');
            $loader.show();
            $favTableWrapper.hide(); // Hide the table wrapper while loading

            // const $favTableWrapper = $('.<?php // echo esc_attr($js_safe_instanceId); ?> .ldl-favorites-documents');
            $favTableWrapper.html(`<table class="ldl-datatable" id="<?php echo esc_attr($js_safe_instanceId); ?>_favorites"><thead><tr>${visibleThs}</tr></thead></table>`);
            const clonedForm = $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-form').clone();
            clonedForm.removeClass('ldl-view-form').addClass('ldl-view-form-favorites');
            clonedForm.find('select[name="view"]').remove();
            clonedForm.find('input[name="search"]').val('');
            clonedForm.find('input[name="wp_refer"]').remove();
            clonedForm.find('button.ldl-favorites').remove();
            clonedForm.find('input[name="action"]').val('ldl_get_favorite_documents');
            // We append before the table wrapper, but after we hide the loader (if it's not the first time)
            if ($instanceWrapper.find('.ldl-view-form-favorites').length === 0) {
                $favTableWrapper.before(clonedForm);
                $favForm = $instanceWrapper.find('.ldl-view-form-favorites');
                $favForm.hide();
            }
            $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-favorites-modal').css('display', 'flex');
            // --- TIMING LOGIC ---
            let datatableLoaded = false;
            let minTimePassed = false;

            // Function to hide the loader and show the table, only if both conditions are met
            const checkAndHideLoader = function() {
                if (datatableLoaded && minTimePassed) {
                    $loader.hide();
                    $favForm.show();
                    $favTableWrapper.show();
                }
            };

            // 1. Set the minimum 3-second timeout
            setTimeout(function() {
                minTimePassed = true;
                checkAndHideLoader();
            }, 3000); // 3000 milliseconds = 3 seconds

            // 2. Initialize Datatables
            const <?php echo esc_attr($js_safe_instanceId); ?>_favorites = $('#<?php echo esc_attr($js_safe_instanceId); ?>_favorites').DataTable({
                processing: true,
                serverSide: true,
                lengthChange: false,
                searching: false,
                autoWisth: false,
                initComplete: function(settings, json) {
                    datatableLoaded = true;
                    checkAndHideLoader();
                },
                ajax: {
                    url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    type: 'POST',
                    data:  function (d) {
                        // Inject filter values from form into the ajax request
                        const form = $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-form-favorites');
                        form.serializeArray().forEach(({ name, value }) => {
                            d[name] = value;
                        });
                        d.action = 'ldl_get_favorite_documents';
                        d.limit = limit;
                        d.user_id = <?php echo esc_attr($current_user->ID); ?>;
                    }
                },
                columns: columns,
                pageLength: limit,
                responsive: true,
                order: [[1, 'asc']],

                // Apply row styling based on featured status
                createdRow: function(row, data, dataIndex) {
                    // Check if this row is featured
                    if (data.is_featured === true || data.is_featured === 1 || data.is_featured === '1') {
                        $(row).addClass('ldl-featured-row');
                    }
                },
                
                // Custom ordering function to keep featured rows at top
                drawCallback: function(settings) {
                    // Get the API instance
                    const api = this.api();
                    
                    // Reorder rows: featured first, then regular rows
                    const rows = api.rows({page: 'current'}).nodes();
                    const $rows = $(rows);
                    
                    // Separate featured and regular rows
                    const featuredRows = $rows.filter('.ldl-featured-row').detach();
                    const regularRows = $rows.not('.ldl-featured-row');
                    
                    // Prepend featured rows to the table body
                    $(api.table().body()).prepend(featuredRows);
                }
            });

            // Handle the form submission for the search and filter
            $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-form-favorites').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                if (form.find('[name="view"]').val() === 'grid' || form.find('[name="view"]').val() === 'folder') {
                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'POST',
                        data: {
                            action: 'ldl_switch_view',
                            wp_nonce: form.find('[name="wp_nonce"]').val(),
                            form: form.serialize(),
                            atts: <?php echo json_encode($atts); ?>,
                            view: form.find('[name="view"]').val()
                        },
                        success: function (response) {
                            if(response.success){
                                const newContent = $(response.data.html);
                                form.closest('.ldl-libraries').after(newContent);
                                form.closest('.ldl-libraries').remove();
                            } else {
                                $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-documents-table').html('<h2>' + response.data + '</h2>');
                                // Handle error if needed
                                console.error('Error switching view:', response.data);
                            }
                        }
                    });
                } else {
                    // If the view is not list or folder, just reload the DataTable
                    <?php echo esc_attr($js_safe_instanceId); ?>_favorites.ajax.reload();
                }
            });
        });
        $(document).on('click', '.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-modal-close-btn', function(e){
            e.preventDefault();
            $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-favorites-modal').hide();
            $('.<?php echo esc_attr($js_safe_instanceId); ?> .ldl-view-form-favorites').remove();
            $('#<?php echo esc_attr($js_safe_instanceId); ?>_favorites').DataTable().destroy();
            $('#<?php echo esc_attr($js_safe_instanceId); ?>_favorites').remove();
        });
    });
</script>
<?php // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_tax_query, WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude ?>