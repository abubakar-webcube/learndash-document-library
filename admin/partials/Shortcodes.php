<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://wooninjas.com/
 * @since      1.0.0
 *
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/admin/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

$general_settings = get_option('ldl_general_settings');

?>
<div class="ldl_general_options ld-documents-panel">
    <form method="post">
        <h2><?php esc_html_e('Shortcodes ', 'learndash-document-library'); ?></h2>
        <div class="woo-shrt-code-panel">
            <div class="ld-doc-shrt-code-box">
                <h3><?php esc_html_e('Learndash Document Shortcode:', 'learndash-document-library'); ?></h3>
                <p><?php esc_html_e('Shortcode display the learndash document list in the table.', 'learndash-document-library'); ?></p>
                <div class="ld-doc-shrtcde-panel">
                    <code>[ldl_document_shortcode]</code>
                    <button class="ld-doc-shrtcde-panel-button" type="button">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
            </div>
            <div class="ld-doc-shrt-code-box">
                <h3><?php esc_html_e('Learndash Document Shortcode:', 'learndash-document-library'); ?></h3>
                <p><?php esc_html_e('Shortcode display the learndash document libraries.', 'learndash-document-library'); ?></p>
                <div class="ld-doc-shrtcde-panel">
                    <code>[ldl_libraries]</code>
                    <button class="ld-doc-shrtcde-panel-button" type="button">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
                <p>
                    <?php echo wp_kses_post(__(
                        'You can use the following shortcode attributes: 
                        <code>exclude="{term_id}" libraries="{term_id}" layout="list" search="true" limit="9"</code>.<br>
                        <strong>Default values:</strong><br>
                        <code>layout</code>: <em>list, grid, folder</em><br>
                        <code>exclude</code>: <em>"{term_id}"</em> (one or multiple comma separated libraries need to be hidden)<br>
                        <code>libraries/categories</code>: <em>"{term_id}"</em> (one or multiple comma separated libraries needs to be visibile)<br>
                        <code>search</code>: <em>true, false</em><br>
                        <code>limit</code>: <em>9</em> (controls the number of documents displayed)',
                        'learndash-document-library'
                    )); ?>
                </p>
            </div>
            <?php if(!empty($general_settings['enable_library_upload'])){?>
            <div class="ld-doc-shrt-code-box">
                <h3> <?php esc_html_e('Learndash Libraries Upload:', 'learndash-document-library'); ?></h3>
                <p><?php esc_html_e('Shortcode will allow you to create categories from frontend.', 'learndash-document-library'); ?></p>
                <div class="ld-doc-shrtcde-panel">
                    <code>[ldl_libraries_upload]</code>
                    <button class="ld-doc-shrtcde-panel-button" type="button">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
                <p>
                    <?php echo wp_kses_post(__(
                        'You need to allow <b>Enable Libraries Upload</b> from the general settings tab before you use this shortcode on frontend.',
                        'learndash-document-library'
                    )); ?>
                </p>
            </div>
            <?php } ?>
        </div>
    </form>
</div>