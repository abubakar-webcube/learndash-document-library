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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$general_settings = get_option( 'ldl_general_settings' );

?>

<div class="ldl_general_options ld-documents-panel">
    <form method="post">
        <?php wp_nonce_field( 'ldl_general_settings', 'ldl_general_settings_field' ); ?>
        <h2><?php esc_html_e( 'General Settings', 'learndash-document-library' ); ?></h2>
		<div class="sfwd sfwd_options themes ldl-general-settings">
			<div id="ldl_settings_default_libraries_layout_field" class="sfwd_input sfwd_input_type_select sfwd_input_type_select--ldl_settings_default_libraries_layout">
				<span class="sfwd_option_label">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!" onclick="toggleVisibility('ldl_settings_default_libraries_layout_tip');">
						<img alt="" src="<?php echo esc_url(LEARNDASH_DOCUMENT_LIBRARY_URL . '/admin/images/question.png'); ?>" />
						<label for="ldl_libraries_layout" class="sfwd_label"><?php esc_html_e( 'Default Libraries Layout', 'learndash-document-library' ); ?></label>
					</a>
					<div id="ldl_settings_default_libraries_layout_tip" class="sfwd_help_text_div" style="display: none;">
						<label class="sfwd_help_text"><?php esc_html_e( 'Choose the default layout style for document libraries.', 'learndash-document-library' ); ?></label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<span class="ld-select ld-select2">
							<select name="ldl_libraries_layout" id="ldl_libraries_layout" class="learndash-section-field learndash-section-field-select select2-hidden-accessible" data-ld-select2="1">
								<option value="list" <?php selected( $general_settings['default_libraries_layout'] ?? '', 'list' ); ?>>
									<?php esc_html_e( 'List View', 'learndash-document-library' ); ?>
								</option>
								<option value="grid" <?php selected( $general_settings['default_libraries_layout'] ?? '', 'grid' ); ?>>
									<?php esc_html_e( 'Grid View', 'learndash-document-library' ); ?>
								</option>
								<option value="folder" <?php selected( $general_settings['default_libraries_layout'] ?? '', 'folder' ); ?>>
									<?php esc_html_e( 'Folder View', 'learndash-document-library' ); ?>
								</option>
							</select>
						</span>
					</div>
				</span>
				<p class="ld-clear"></p>
			</div>
			<div id="ldl_settings_visible_list_columns_field" class="sfwd_input sfwd_input_type_checkbox sfwd_input_type_checkbox--ldl_settings_visible_list_columns">
				<span class="sfwd_option_label">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!" onclick="toggleVisibility('ldl_settings_visible_list_columns_tip');">
						<img alt="" src="<?php echo esc_url(LEARNDASH_DOCUMENT_LIBRARY_URL . '/admin/images/question.png'); ?>" />
						<label for="ldl_visible_list_columns" class="sfwd_label"><?php esc_html_e( 'Visible Columns in Selected Layout', 'learndash-document-library' ); ?></label>
					</a>
					<div id="ldl_settings_visible_list_columns_tip" class="sfwd_help_text_div" style="display: none;">
						<label class="sfwd_help_text"><?php esc_html_e( 'Uncheck to hide specific columns from the selected layout. All disabled columns are not visible in grid view, regardless of whether they are checked or not.', 'learndash-document-library' ); ?></label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div" id="ldl_visible_list_columns">
						<?php
						$default_columns = [
							'image'     => __( 'Image', 'learndash-document-library' ),
							'reference' => __( 'Reference', 'learndash-document-library' ),
							'title'     => __( 'Title', 'learndash-document-library' ),
							'published' => __( 'Published', 'learndash-document-library' ),
							'modified'  => __( 'Last Modified', 'learndash-document-library' ),
							'author'    => __( 'Author', 'learndash-document-library' ),
							'favorites' => __( 'Favorites', 'learndash-document-library' ),
							'downloads' => __( 'Downloads', 'learndash-document-library' ),
							'download'  => __( 'Download', 'learndash-document-library' ),
						];

						$visible_columns = $general_settings['visible_list_columns'] ?? array_keys( $default_columns );

						foreach ( $default_columns as $key => $label ) : ?>
							<?php if( $general_settings['default_libraries_layout'] === 'grid' && in_array( $key, ['reference','published','modified','author'], true ) ) : ?>
							<label style="display: inline-block; margin-right: 15px;">
								<input type="checkbox" name="ldl_visible_list_columns[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $visible_columns, true ) ); ?> disabled="true" />
								<?php if ( in_array( $key, $visible_columns, true ) ) : ?>
								<input type="hidden" name="ldl_visible_list_columns[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $visible_columns, true ) ); ?> />
								<?php endif; ?>
								<?php echo esc_html( $label ); ?>
							</label><br />
							<?php else : ?>
							<label style="display: inline-block; margin-right: 15px;">
								<input type="checkbox" name="ldl_visible_list_columns[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $visible_columns, true ) ); ?> />
								<?php echo esc_html( $label ); ?>
							</label><br />
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</span>
				<p class="ld-clear"></p>
			</div>
			<div class="sfwd_input sfwd_input_type_checkbox sfwd_input_type_checkbox--ldl_settings_enable_categories_filter">
				<span class="sfwd_option_label">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!" onclick="toggleVisibility('ldl_settings_enable_categories_filter_tip');">
						<img alt="" src="<?php echo esc_url(LEARNDASH_DOCUMENT_LIBRARY_URL . '/admin/images/question.png'); ?>" />
						<label for="ldl_enable_categories_filter" class="sfwd_label"><?php esc_html_e( 'Enable Categories Filter', 'learndash-document-library' ); ?></label>
					</a>
					<div id="ldl_settings_enable_categories_filter_tip" class="sfwd_help_text_div" style="display: none;">
						<label class="sfwd_help_text"><?php esc_html_e( 'Check to enable the categories filter in the document library.', 'learndash-document-library' ); ?></label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<label style="display: inline-block; margin-right: 15px;">
							<input type="checkbox" name="ldl_enable_categories_filter" id="ldl_enable_categories_filter" value="1" <?php checked( !empty($general_settings['enable_categories_filter']) ); ?> />
							<?php // esc_html_e( 'Enable Categories Filter', 'learndash-document-library' ); ?>
						</label><br />
					</div>
				</span>
				<p class="ld-clear"></p>
			</div>
			<div class="sfwd_input sfwd_input_type_checkbox sfwd_input_type_checkbox--ldl_settings_enable_library_upload">
				<span class="sfwd_option_label">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!" onclick="toggleVisibility('ldl_settings_enable_library_upload_tip');">
						<img alt="" src="<?php echo esc_url(LEARNDASH_DOCUMENT_LIBRARY_URL . '/admin/images/question.png'); ?>" />
						<label for="ldl_enable_library_upload" class="sfwd_label"><?php esc_html_e( 'Enable Libraries Upload', 'learndash-document-library' ); ?></label>
					</a>
					<div id="ldl_settings_enable_library_upload_tip" class="sfwd_help_text_div" style="display: none;">
						<label class="sfwd_help_text"><?php esc_html_e( 'Check to enable libraries upload from frontend.', 'learndash-document-library' ); ?></label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<label style="display: inline-block; margin-right: 15px;">
							<input type="checkbox" name="ldl_enable_library_upload" id="ldl_enable_library_upload" value="1" <?php checked( !empty($general_settings['enable_library_upload']) ); ?> />
							<?php // esc_html_e( 'Enable Libraries Upload', 'learndash-document-library' ); ?>
						</label><br />
					</div>
				</span>
				<p class="ld-clear"></p>
			</div>
			<div class="sfwd_input sfwd_input_type_checkbox sfwd_input_type_checkbox--ldl_settings_enable_libraries_restriction">
				<span class="sfwd_option_label">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!" onclick="toggleVisibility('ldl_settings_enable_libraries_restriction_tip');">
						<img alt="" src="<?php echo esc_url(LEARNDASH_DOCUMENT_LIBRARY_URL . '/admin/images/question.png'); ?>" />
						<label for="ldl_enable_libraries_restriction" class="sfwd_label"><?php esc_html_e( 'Enable Libraries Restriction', 'learndash-document-library' ); ?></label>
					</a>
					<div id="ldl_settings_enable_libraries_restriction_tip" class="sfwd_help_text_div" style="display: none;">
						<label class="sfwd_help_text"><?php esc_html_e( 'Check to enable restriction on libraries.', 'learndash-document-library' ); ?></label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<label style="display: inline-block; margin-right: 15px;">
							<input type="checkbox" name="ldl_enable_libraries_restriction" id="ldl_enable_libraries_restriction" value="1" <?php checked( !empty($general_settings['enable_libraries_restriction']) ); ?> />
							<?php // esc_html_e( 'Enable Libraries Restriction', 'learndash-document-library' ); ?>
						</label><br />
					</div>
				</span>
				<p class="ld-clear"></p>
			</div>
			<div class="sfwd_input sfwd_input_type_text sfwd_input_type_text--ldl_settings_global_password">
				<span class="sfwd_option_label">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!" onclick="toggleVisibility('ldl_settings_global_password_tip');">
						<img alt="" src="<?php echo esc_url(LEARNDASH_DOCUMENT_LIBRARY_URL . '/admin/images/question.png'); ?>" />
						<label for="ldl_global_password" class="sfwd_label"><?php esc_html_e( 'Global Password', 'learndash-document-library' ); ?></label>
					</a>
					<div id="ldl_settings_global_password_tip" class="sfwd_help_text_div" style="display: none;">
						<label class="sfwd_help_text"><?php esc_html_e( 'Set a global password for document library access (optional).', 'learndash-document-library' ); ?></label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<span class="ld-input" style="max-width:450px;position:relative;">
							<input type="password" name="ldl_global_password" id="ldl_global_password" value="<?php echo isset($general_settings['global_password']) ? esc_attr($general_settings['global_password']) : ''; ?>" autocomplete="new-password" style="padding-right:36px;width:100%;max-width:450px;box-sizing:border-box;" />
							<button type="button" id="toggle_global_password" style="position:absolute;top:-1px;right:8px;background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;" tabindex="-1" aria-label="Show/Hide Password">
								<svg id="eye_icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
								<svg id="eye_slash_icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
							</button>
							<script>
							(function(){
								var btn = document.getElementById('toggle_global_password');
								if(btn){
									btn.addEventListener('click', function(e){
										e.preventDefault();
										var pw = document.getElementById('ldl_global_password');
										var eye = btn.querySelector('#eye_icon');
										var slash = btn.querySelector('#eye_slash_icon');
										if(pw.type === 'password'){
											pw.type = 'text';
											eye.style.display = 'none';
											slash.style.display = 'block';
										}else{
											pw.type = 'password';
											eye.style.display = 'block';
											slash.style.display = 'none';
										}
									});
								}
							})();
							</script>
						</span>
					</div>
				</span>
				<p class="ld-clear"></p>
			</div>
			<div class="sfwd_input sfwd_input_type_select sfwd_input_type_select--ldl_settings_global_user_roles">
				<span class="sfwd_option_label">
					<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!" onclick="toggleVisibility('ldl_settings_global_user_roles_tip');">
						<img alt="" src="<?php echo esc_url(LEARNDASH_DOCUMENT_LIBRARY_URL . '/admin/images/question.png'); ?>" />
						<label for="ldl_global_user_roles" class="sfwd_label"><?php esc_html_e( 'Global Allowed User Role', 'learndash-document-library' ); ?></label>
					</a>
					<div id="ldl_settings_global_user_roles_tip" class="sfwd_help_text_div" style="display: none;">
						<label class="sfwd_help_text"><?php esc_html_e( 'Select user role allowed to access the document library globally (optional).', 'learndash-document-library' ); ?></label>
					</div>
				</span>
				<span class="sfwd_option_input">
					<div class="sfwd_option_div">
						<span class="ld-select ld-select2" style="max-width:450px;">
							<select name="ldl_global_user_role[]" id="ldl_global_user_roles" multiple="multiple" style="width:100%;">
								<?php
								global $wp_roles;
								if ( ! isset( $wp_roles ) ) {
									$wp_roles = new WP_Roles();
								}
								$selected_roles = isset($general_settings['global_user_roles']) && is_array($general_settings['global_user_roles']) ? $general_settings['global_user_roles'] : array();
								foreach ( $wp_roles->roles as $role_key => $role ) : ?>
									<option value="<?php echo esc_attr($role_key); ?>" <?php selected(in_array($role_key, $selected_roles)); ?>><?php echo esc_html($role['name']); ?></option>
								<?php endforeach; ?>
							</select>
						</span>
					</div>
				</span>
				<p class="ld-clear"></p>
			</div>
		</div>
        <div class="sfwd_input">
			<span class="sfwd_option_label">&nbsp;</span>
			<span class="sfwd_option_input">
				<input type="submit" name="save_ldl_general_settings" class="button button-primary" value="<?php esc_attr_e( 'Update Settings', 'learndash-document-library' ); ?>">
			</span>
			<p class="ld-clear"></p>
		</div>
    </form>
</div>

<script>
	(function($){
		function toggleCategoriesRestrictionField() {
			const isChecked = $('#ldl_enable_categories_filter').is(':checked');
			const $restrictionField = $('.sfwd_input_type_checkbox--ldl_settings_enable_categories_restriction');
			if(isChecked) {
				$restrictionField.show();
				$('.sfwd_input_type_checkbox--ldl_settings_enable_libraries_restriction').hide();
				$('.sfwd_input_type_checkbox--ldl_settings_enable_libraries_restriction input').prop('checked', false);
				$('.sfwd_input_type_checkbox--ldl_settings_enable_libraries_restriction input').trigger('change');
				// console.log('show');
			} else {
				$('.sfwd_input_type_checkbox--ldl_settings_enable_libraries_restriction').show();
				$restrictionField.hide();
				$restrictionField.find('input[type="checkbox"]').prop('checked', false);
				$restrictionField.find('input[type="checkbox"]').trigger('change');
				// console.log('hide');
			}
		}
		function toggleRestrictionFields() {
			const libChecked = $('#ldl_enable_libraries_restriction').is(':checked');
			// const catChecked = $('#ldl_enable_categories_restriction').is(':checked');
			// if (libChecked || catChecked) {
			if (libChecked) {
				$('.sfwd_input_type_text--ldl_settings_global_password, .sfwd_input_type_select--ldl_settings_global_user_roles').show();
			} else {
				$('.sfwd_input_type_text--ldl_settings_global_password, .sfwd_input_type_select--ldl_settings_global_user_roles').hide();
				$('.sfwd_input_type_text--ldl_settings_global_password input').val('');
				$('.sfwd_input_type_select--ldl_settings_global_user_roles select').val('').trigger('change');
			}
		}
		$(document).ready(function(){
			// toggleCategoriesRestrictionField();
			toggleRestrictionFields();
			// $('#ldl_enable_categories_filter').on('change', toggleCategoriesRestrictionField);
			$('input').on('change', toggleRestrictionFields)
		});
	})(jQuery);
</script>