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

// Include the Document Class
if (file_exists(LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-document.php')) {
	require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-document.php';
}

/**
 * The core plugin class.
 *
 * This is used to define Metabox for LearnDash Document Library.
 *
 * @since      1.0.0
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/includes
 * @author     Wooninjas <info@wooninjas.com>
 */
class LearnDash_Document_Library_Metabox
{

	public function register()
	{
		// Enqueue Select2
        add_action('admin_enqueue_scripts', array($this, 'enqueue_select2_assets'));
        // add_action('admin_enqueue_scripts', array($this, 'ldl_document_library_enqueue_scripts'));

		add_action('add_meta_boxes', array($this, 'register_metabox'), 1);
		add_action('save_post_ldl-document', array($this, 'save'));

		// NEW: Hooks for ldl_library taxonomy
        add_action('ldl_library_add_form_fields', array($this, 'render_taxonomy_add_fields'));
        add_action('ldl_library_edit_form_fields', array($this, 'render_taxonomy_edit_fields'));
        add_action('category_add_form_fields', array($this, 'render_category_add_fields'));
        add_action('category_edit_form_fields', array($this, 'render_category_edit_fields'));
        add_action('created_ldl_library', array($this, 'save_taxonomy_meta'));
        add_action('edited_ldl_library', array($this, 'save_taxonomy_meta'));
        add_action('created_category', array($this, 'save_category_meta'));
        add_action('edited_category', array($this, 'save_category_meta'));
		add_filter('manage_edit-ldl_library_columns', array($this, 'add_custom_term_columns'));
		add_filter('manage_ldl_library_custom_column', array($this, 'render_custom_term_columns'), 10, 3);
		add_filter('manage_edit-ldl_library_sortable_columns', array($this, 'make_custom_library_column_sortable'));
		add_filter('manage_edit-category_columns', array($this, 'add_category_term_columns'));
		add_filter('manage_category_custom_column', array($this, 'render_category_term_columns'), 10, 3);
		add_filter('manage_edit-category_sortable_columns', array($this, 'make_custom_category_column_sortable'));
		// AJAX handlers for Select2
		add_action( 'wp_ajax_ldl_get_documents_select2', [$this, 'ldl_get_documents_callback'] );
		add_action( 'wp_ajax_nopriv_ldl_get_documents_select2', [$this, 'ldl_get_documents_callback'] );
		add_action( 'wp_ajax_ldl_get_documents_select2_prefill', [$this, 'ldl_get_documents_prefill_callback'] );
		add_action( 'wp_ajax_nopriv_ldl_get_documents_select2_prefill', [$this, 'ldl_get_documents_prefill_callback'] );

		// AJAX handler for logged-in and logged-out users
		add_action('wp_ajax_ldl_get_terms', 'get_libraries');
		add_action('wp_ajax_nopriv_ldl_get_terms', 'get_libraries');

		/**
		 * Register REST route for Select2 taxonomy terms.
		 */
		add_action( 'rest_api_init', function() {
			register_rest_route(
				'ldl/v1', // your namespace
				'/terms', // route: /wp-json/ldl/v1/terms
				[
					'methods'             => 'GET',
					'callback'            => [$this, 'ldl_get_terms_rest_callback'],
					'permission_callback' => '__return_true', // adjust if needed
					'args'                => [
						'taxonomy' => [
							'required' => true,
							'sanitize_callback' => 'sanitize_text_field',
						],
						'q' => [
							'required' => false,
							'sanitize_callback' => 'sanitize_text_field',
						],
						'page' => [
							'required' => false,
							'sanitize_callback' => 'absint',
							'default' => 1,
						],
						'per_page' => [
							'required' => false,
							'sanitize_callback' => 'absint',
							'default' => 20,
						],
					],
				]
			);
		});
	}

	/**
     * Enqueue Select2 scripts and styles for taxonomy edit/add screens
     */
    public function enqueue_select2_assets($hook)
    {
        // Only load on the ldl_library taxonomy pages
        if (isset($_GET['taxonomy']) && ($_GET['taxonomy'] === 'ldl_library' || $_GET['taxonomy'] === 'category')) {
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', '4.1.0', 'all');
			// wp_enqueue_style('select2-css', LEARNDASH_DOCUMENT_LIBRARY_URL . 'admin/css/learndash-document-library-admin-select2.css', array(), $this->version, 'all');
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', null, true);
			wp_localize_script('select2', 'ldl_ajax_obj', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce'    => wp_create_nonce('ldl_ajax_nonce'),
			));
            wp_add_inline_script('select2', "
                jQuery(document).ready(function($){
					function initSelect2WithAjax(selector, ajaxAction, placeholderText) {
						const \$el = $(selector);
						\$el.select2({
							placeholder: placeholderText,
							allowClear: true,
							ajax: {
								url: ldl_ajax_obj.ajax_url,
								dataType: 'json',
								delay: 250,
								data: function(params) {
									return {
										action: ajaxAction,
										search: params.term || '',
										page: params.page || 1,
										nonce: ldl_ajax_obj.nonce
									};
								},
								processResults: function(data, params) {
									params.page = params.page || 1;
									return {
										results: data.results || [],
										pagination: {
											more: data.pagination && data.pagination.more
										}
									};
								},
								cache: true
							},
							minimumInputLength: 0
						});
						// Preload existing selected items (useful when editing)
						const existingIds = \$el.data('selected'); // expects array of IDs or comma-separated IDs
						if (existingIds && existingIds.length) {
							let idsArray = Array.isArray(existingIds) ? existingIds : existingIds.toString().split(',');
							$.ajax({
								url: ldl_ajax_obj.ajax_url,
								dataType: 'json',
								data: {
									action: ajaxAction + '_prefill',
									ids: idsArray,
									nonce: ldl_ajax_obj.nonce
								},
								success: function(response) {
									if (response && response.results) {
										response.results.forEach(function(item) {
											// Create new option
											let option = new Option(item.text, item.id, true, true);
											\$el.append(option).trigger('change');
										});
									}
								}
							});
						}
					}
					// Initialize for documents
					initSelect2WithAjax(
						'#ldl_library_documents',
						'ldl_get_documents_select2',
						'" . esc_js(__('Select documents', 'learndash-document-library')) . "'
					);
                    $('#library_user_roles').select2({
						placeholder: '" . esc_js(__('Select user roles', 'learndash-document-library')) . "',
						allowClear: true,
					});
                });
            ");
        }
    }

	/**
	 * Enqueue scripts to restrict empty or blank document creation
	 */
	public function ldl_document_library_enqueue_scripts($hook){
		// Check for post type editing or creation screen
		global $post_type;

		// Adjust to your specific post type
		if ( $post_type === 'ldl-document' && in_array($hook, ['post-new.php', 'post.php']) ) {
			// Ensure jQuery is loaded
			wp_enqueue_script('jquery');

			// Enqueue your custom script
			wp_enqueue_script(
				'ldl_admin_script',
				LEARNDASH_DOCUMENT_LIBRARY_URL . 'js/ldl-admin-script.js', // Change to your script path
				['jquery'],
				LEARNDASH_DOCUMENT_LIBRARY_VERSION,
				true
			);
		}
	}

	/**
	 * Register the metabox
	 */
	public function register_metabox()
	{
		add_meta_box(
			'ld_document_upload',
			__('Document Upload', 'learndash-document-library'),
			array($this, 'render'),
			'ldl-document',
			'side',
			'high'
		);
		add_meta_box(
			'ld_document_pinned',
			__('Featured Document', 'learndash-document-library'),
			array($this, 'pinned'),
			'ldl-document',
			'side',
			'high'
		);
	}

	public function pinned($post){
		// Retrieve the current pinned status. 'true' checks for boolean existence.
		$pinned = get_post_meta($post->ID, '_ldl_featured_document', true);
		?>
		<label for="ldl_featured_document">
			<input
				type="checkbox"
				id="ldl_featured_document"
				class="ldl_featured_document"
				name="_ldl_featured_document"
				value="1"
				<?php checked( '1', $pinned ); ?>
			/>
			<?php esc_html_e( 'Feature / Pin this document to the top of relevant libraries.', 'your-text-domain' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the metabox.
	 *
	 * @param WP_Post $post
	 */
	public function render($post)
	{
		$document = new LearnDash_Document_Library_Document($post->ID);
		$button_text         = $document->get_file_id() ? __('Replace File', 'learndash-document-library') : __('Add File', 'learndash-document-library');
		$file_attached_class = $document->get_file_id() ? ' active' : '';
		$file_details_class  = $document->get_link_type() === 'library' ? 'active' : '';
		$access_type = get_post_meta($post->ID, '_ldl_access_type', true) ?: 'preview_download';
		?>
		<label for="<?php esc_attr('ld_document_upload'); ?>" class="howto"><?php esc_html_e('Upload a file or select from the media library:', 'learndash-document-library'); ?></label>
		<!-- option selector -->
		<select name="_ldl_document_upload_type" id="ldl_document_upload_type" class="postbox">
			<option value="library" <?php selected($document->get_link_type(), 'library'); ?>><?php esc_html_e('Select from Library', 'learndash-document-library'); ?></option>
			<option value="url" <?php selected($document->get_link_type(), 'url'); ?>><?php esc_html_e('URL', 'learndash-document-library'); ?></option>
			<option value="file" <?php selected($document->get_link_type(), 'file'); ?>><?php esc_html_e('File Upload', 'learndash-document-library'); ?></option>
		</select>
		<?php /*
		<!-- Access Type -->
		<div class="<?php echo ($document->get_link_type() === 'library') ? 'active' : ''; ?>">
			<label for="_ldl_access_type"><strong><?php esc_html_e('Access Type', 'learndash-document-library'); ?></strong></label><br>
			<select name="_ldl_access_type" id="_ldl_access_type" style="width:100%;">
				<option value="preview_download" <?php selected($access_type, 'preview_download'); ?>><?php esc_html_e('Preview and Download', 'learndash-document-library'); ?></option>
				<option value="preview" <?php selected($access_type, 'preview'); ?>><?php esc_html_e('Preview Only', 'learndash-document-library'); ?></option>
			</select>
			<p class="description"><?php esc_html_e('Choose whether this document allows only preview or both preview and download.', 'learndash-document-library'); ?></p>
		</div>
		*/ ?>
		<!-- Library Upload -->
		<div id="ldl_library_attachment_details" class="<?php echo ($document->get_link_type() === 'library') ? 'active' : ''; ?>">
			<div class="ldl_file_attached <?php echo esc_attr($file_attached_class); ?>">
				<?php /* <button type="button" id="ldl_remove_file_button">
					<span class="remove-file-icon" aria-hidden="true"> X </span>
					<span class="screen-reader-text"><?php echo esc_html(sprintf(__('Remove file: %s', 'learndash-document-library'), $document->get_file_name())); ?></span>
				</button> */ ?>
				<span class="ldl_file_name_text"><?php echo esc_html($document->get_file_name()); ?></span>
				<input id="ldl_file_name_input" type="hidden" name="_ldl_attached_file_name" value="<?php echo esc_attr($document->get_file_name()); ?>" />
			</div><br>
			<button id="ldl_add_file_button" class="button button-large"><?php echo esc_html($button_text); ?></button>
			<input id="ldl_file_id" type="number" name="_ldl_attached_file_id" value="<?php echo esc_attr($document->get_file_id()); ?>" style="height:0 !important;min-height:1px !important;border:0 !important;width:1px !important;padding:0 !important;margin:0 !important;outline:0 !important;box-shadow:none !important;transform-origin:center;transform:translate(-37px, 20px);" />
		</div><br/>

		<!-- URL Input -->
		<div id="ldl_url_input_details" class="<?php echo ($document->get_link_type() === 'url') ? 'active' : ''; ?>">
			<label for="ldl_url_input"><?php esc_html_e('Enter Document URL:', 'learndash-document-library'); ?></label>
			<input type="url" name="_ldl_document_url" id="ldl_url_input" value="<?php echo esc_attr($document->get_document_url()); ?>" class="widefat" />
		</div><br/>

		<!-- Direct File Upload -->
		<div id="ldl_direct_file_upload_details" class="<?php echo ($document->get_link_type() === 'file') ? 'active' : ''; ?>">
			<?php if($document->get_link_type() !== '' && $document->get_link_type() === 'file') {
				$file_url = $document->get_uploaded_file_url();
				$file_name = $document->get_uploaded_file_name();
				$file_type = wp_check_filetype( $file_url );
				if ( strpos( $file_type['type'], 'image/' ) === 0 ) : ?>
					<img src="<?php echo esc_url( $file_url ); ?>"
						alt="<?php echo esc_attr( $file_name ); ?>"
						class="ldl-file-preview"
						width="60"
						height="60" />
				<?php else : ?>
					<?php echo '<span class="ldl-file-preview">' . esc_html( $file_name ) . '</span>'; ?>
				<?php endif; ?>
				</br>
			<?php } ?>
			<label for="ldl_file_upload_input"><?php esc_html_e('Upload a file:', 'learndash-document-library'); ?></label>
			<input type="file" name="_ldl_uploaded_file" id="ldl_file_upload_input" />
		</div><br/>
		<?php
	}

	/**
	 * Save the metabox values
	 *
	 * @param mixed $post_id
	 */
	public function save($post_id)
	{
		if (get_post_type($post_id) === 'ldl-document'){}

		if (!isset($_POST['_ldl_document_upload_type'])) {
			return;
		}

		// Check for autosave or AJAX
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
		if (defined('DOING_AJAX') && DOING_AJAX) return;

		$has_title = isset($_POST['post_title']) && !empty($_POST['post_title']);
		$has_file = isset($_POST['_ldl_attached_file_id']) && !empty($_POST['_ldl_attached_file_id']) || isset($_FILES['_ldl_uploaded_file']['name']) && !empty($_FILES['_ldl_uploaded_file']['name']) || isset($_POST['_ldl_document_url']) && !empty($_POST['_ldl_document_url']);

		if (!$has_title || !$has_file) {
			return;
		}

		$type = filter_input(INPUT_POST, '_ldl_document_upload_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$data = [];

		switch ($type) {
			case 'library':
				$data['file_id']   = filter_input( INPUT_POST, '_ldl_attached_file_id', FILTER_SANITIZE_NUMBER_INT );
				$data['file_name'] = filter_input( INPUT_POST, '_ldl_attached_file_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				break;
			case 'file':
				if ( ! empty( $_FILES['_ldl_uploaded_file']['name'] ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/media.php';
					require_once ABSPATH . 'wp-admin/includes/image.php';

					$attachment_id = media_handle_upload( '_ldl_uploaded_file', $post_id );

					if ( is_wp_error( $attachment_id ) ) {
						// Handle error if needed
						return;
					}

					$data['file_id']   = $attachment_id;
					$data['file_name'] = get_the_title( $attachment_id );
				}
				break;
			case 'url':
				$data['url'] = filter_input( INPUT_POST, '_ldl_document_url', FILTER_SANITIZE_URL );
				break;
		}

		// Save Access Type
		if (isset($_POST['_ldl_access_type'])) {
			update_post_meta($post_id, '_ldl_access_type', sanitize_text_field($_POST['_ldl_access_type']));
		}

		// Save Feature / Pin this document to libraries.
		if ( isset($_POST['_ldl_featured_document']) ) {
			update_post_meta($post_id, '_ldl_featured_document', sanitize_text_field($_POST['_ldl_featured_document']));
		} else {
			delete_post_meta( $post_id, '_ldl_featured_document' );
		}

		try {
			$document = new LearnDash_Document_Library_Document($post_id);
			$document->set_document_link($type, $data);
		} catch (\Exception $exception) {
			// nothing
		}
	}

	/**
     * Render fields when adding a new term
     */
    public function render_taxonomy_add_fields()
    {
        $settings = get_option('ldl_general_settings');
        $enable_libraries_restriction = !empty($settings['enable_libraries_restriction']);
        // $documents = $this->get_ldl_documents();
        ?>
        <div class="form-field term-ldl_library_documents-wrap">
            <label for="ldl_library_documents"><?php esc_html_e('Attach Documents', 'learndash-document-library'); ?></label>
            <select name="ldl_library_documents[]" id="ldl_library_documents" multiple style="width: 95%;">
                <?php /* foreach ($documents as $doc_id => $doc_title) : ?>
                    <option value="<?php echo esc_attr($doc_id); ?>"><?php echo esc_html($doc_title); ?></option>
                <?php endforeach; */ ?>
            </select>
            <p class="description"><?php esc_html_e('Select documents to associate with this library.', 'learndash-document-library'); ?></p>
        </div>
        <?php if ($enable_libraries_restriction): ?>
        <div class="form-field term-library-password-wrap" style="position:relative;max-width:95%;">
            <label for="library_password"><?php esc_html_e('Library Password', 'learndash-document-library'); ?></label>
            <input type="password" name="library_password" id="library_password" value="" autocomplete="new-password" style="padding-right:36px;width:100%;box-sizing:border-box;" />
            <button type="button" id="toggle_library_password" style="position:absolute;top:28px;right:8px;background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;" tabindex="-1" aria-label="Show/Hide Password">
                <svg id="eye_icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                <svg id="eye_slash_icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            </button>
            <script>
            (function(){
                var btn = document.getElementById('toggle_library_password');
                if(btn){
                    btn.addEventListener('click', function(e){
                        e.preventDefault();
                        var pw = document.getElementById('library_password');
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
            <p class="description"><?php esc_html_e('Set a password to restrict access to this library from the frontend.', 'learndash-document-library'); ?></p>
        </div>
        <div class="form-field term-library-user-roles-wrap">
            <label for="library_user_roles"><?php esc_html_e('Allowed User Roles', 'learndash-document-library'); ?></label>
            <select name="library_user_roles[]" id="library_user_roles" multiple style="width: 95%;">
                <?php
                global $wp_roles;
                if ( ! isset( $wp_roles ) ) {
                    $wp_roles = new WP_Roles();
                }
                foreach ( $wp_roles->roles as $role_key => $role ) : ?>
                    <option value="<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Select user roles allowed to access this library from the frontend.', 'learndash-document-library'); ?></p>
        </div>
        <?php endif; ?>
        <script>
			jQuery(document).ready(function($) {
				const target = document.getElementById('ajax-response');
				if (target) {
					const observer = new MutationObserver(function(mutationsList) {
						for (let mutation of mutationsList) {
							if (mutation.type === 'childList') {
								const text = $(target).text().trim();
								if (text && text.toLowerCase().includes('added')) {
									// Term successfully added – reset the field
									$('#ldl_library_documents').val(null).trigger('change');
									$('#library_user_roles').val(null).trigger('change');
								}
							}
						}
					});
					observer.observe(target, {
						childList: true,
						subtree: true
					});
				}
			});
		</script>
        <?php
    }

    /**
     * Render fields when editing a term
     */
    public function render_taxonomy_edit_fields($term)
    {
        $settings = get_option('ldl_general_settings');
        $enable_libraries_restriction = !empty($settings['enable_libraries_restriction']);
        $selected_documents = get_posts(array(
            'post_type'      => 'ldl-document',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            'tax_query'      => array(
                array(
                    'taxonomy'          => 'ldl_library',
                    'field'            => 'term_id',
                    'terms'            => [$term->term_id],
                    'operator'         => 'IN',
                    'include_children' => false,
                ),
            ),
        ));
		$selected_roles = (array) get_term_meta($term->term_id, 'library_user_roles', true);
        // $documents = $this->get_ldl_documents();
        ?>
        <tr class="form-field term-ldl_library_documents-wrap">
            <th scope="row"><label for="ldl_library_documents"><?php esc_html_e('Attach Documents', 'learndash-document-library'); ?></label></th>
            <td style="position:relative;max-width:95%;padding-right:5%;">
                <select name="ldl_library_documents[]" id="ldl_library_documents" multiple data-selected="<?php echo json_encode($selected_documents); ?>" style="width: 100%;">
                    <?php /* foreach ($documents as $doc_id => $doc_title) : ?>
                        <option value="<?php echo esc_attr($doc_id); ?>">
                            <?php echo esc_html($doc_title); ?>
                        </option>
                    <?php endforeach; */ ?>
                </select>
                <p class="description"><?php esc_html_e('Select documents to associate with this library.', 'learndash-document-library'); ?></p>
            </td>
        </tr>
        <?php if ($enable_libraries_restriction): ?>
        <tr class="form-field term-library-password-wrap">
            <th scope="row"><label for="library_password"><?php esc_html_e('Library Password', 'learndash-document-library'); ?></label></th>
            <td style="position:relative;max-width:95%;padding-right:5%;">
                <input type="password" name="library_password" id="library_password" value="<?php echo esc_attr(get_term_meta($term->term_id, 'library_password', true)); ?>" autocomplete="new-password" style="padding-right:36px;width:100%;box-sizing:border-box;" />
                <button type="button" id="toggle_library_password" style="position:absolute;top:23%;right:8%;background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;" tabindex="-1" aria-label="Show/Hide Password">
                    <svg id="eye_icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                    <svg id="eye_slash_icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                </button>
                <script>
                (function(){
                    var btn = document.getElementById('toggle_library_password');
                    if(btn){
                        btn.addEventListener('click', function(e){
                            e.preventDefault();
                            var pw = document.getElementById('library_password');
                            var eye = btn.querySelector('#eye_icon');
                            var slash = btn.querySelector('#eye_slash_icon');
                            if(pw.type === 'password'){
                                pw.type = 'text';
                                eye.style.display = 'none';
                                slash.style.display = 'block';
                            } else {
                                pw.type = 'password';
                                eye.style.display = 'block';
                                slash.style.display = 'none';
                            }
                        });
                    }
                })();
                </script>
                <p class="description"><?php esc_html_e('Set a password to restrict access to this library from the frontend.', 'learndash-document-library'); ?></p>
            </td>
        </tr>
        <tr class="form-field term-library-user-roles-wrap">
            <th scope="row"><label for="library_user_roles"><?php esc_html_e('Allowed User Roles', 'learndash-document-library'); ?></label></th>
            <td style="position:relative;max-width:95%;padding-right:5%;">
                <select name="library_user_roles[]" id="library_user_roles" multiple style="width: 100%;">
                    <?php
                    global $wp_roles;
                    if ( ! isset( $wp_roles ) ) {
                        $wp_roles = new WP_Roles();
                    }
                    foreach ( $wp_roles->roles as $role_key => $role ) : ?>
                        <option value="<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Select user roles allowed to access this library from the frontend.', 'learndash-document-library'); ?></p>
            </td>
        </tr>
        <?php endif; ?>
        <script>
			jQuery(document).ready(function($) {
				// const preSelectedDocs = <?php echo json_encode($selected_documents); ?>;
				const preSelectedRoles = <?php echo json_encode($selected_roles); ?>;
				// $('#ldl_library_documents').val(preSelectedDocs).trigger('change');
				$('#library_user_roles').val(preSelectedRoles).trigger('change');
			});
		</script>
        <?php
    }

	/**
     * Render fields when adding a new term
     */
    public function render_category_add_fields()
    {
		$settings = get_option('ldl_general_settings');
		$enable_categories_filter = !empty($settings['enable_categories_filter']);
		$enable_categories_restriction = !empty($settings['enable_categories_restriction']);
		wp_nonce_field( 'ldl_save_related_terms', 'ldl_related_terms_nonce' );
		if($enable_categories_filter){
        ?>
        <div class="form-field term-ldl_libraries-wrap">
            <label for="ldl_libraries"><?php esc_html_e('Select Libraries', 'learndash-document-library'); ?></label>
            <select name="ldl_libraries[]" id="ldl_libraries" multiple style="width: 95%;">
                <option value=""><?php esc_html_e('Select Libraries.', 'learndash-document-library'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Select libraries to associate with this Category.', 'learndash-document-library'); ?></p>
        </div>
		<?php if ($enable_categories_restriction){ ?>
		<div class="form-field term-library-password-wrap" style="position:relative;max-width:95%;">
            <label for="library_password"><?php esc_html_e('Category Password', 'learndash-document-library'); ?></label>
            <input type="password" name="library_password" id="library_password" value="" autocomplete="new-password" style="padding-right:36px;width:100%;box-sizing:border-box;" />
            <button type="button" id="toggle_library_password" style="position:absolute;top:28px;right:8px;background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;" tabindex="-1" aria-label="Show/Hide Password">
                <svg id="eye_icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                <svg id="eye_slash_icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            </button>
            <script>
            (function(){
                var btn = document.getElementById('toggle_library_password');
                if(btn){
                    btn.addEventListener('click', function(e){
                        e.preventDefault();
                        var pw = document.getElementById('library_password');
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
            <p class="description"><?php esc_html_e('Set a password to restrict access to this category from the frontend.', 'learndash-document-library'); ?></p>
        </div>
        <div class="form-field term-library-user-roles-wrap">
            <label for="library_user_roles"><?php esc_html_e('Allowed User Roles', 'learndash-document-library'); ?></label>
            <select name="library_user_roles[]" id="library_user_roles" multiple style="width: 95%;">
                <?php
                global $wp_roles;
                if ( ! isset( $wp_roles ) ) {
                    $wp_roles = new WP_Roles();
                }
                foreach ( $wp_roles->roles as $role_key => $role ) : ?>
                    <option value="<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Select user roles allowed to access this category from the frontend.', 'learndash-document-library'); ?></p>
        </div>
		<?php } ?>
		<script>
			jQuery(document).ready(function($) {
				const target = document.getElementById('ajax-response');
				if (target) {
					const observer = new MutationObserver(function(mutationsList) {
						for (let mutation of mutationsList) {
							if (mutation.type === 'childList') {
								const text = $(target).text().trim();
								if (text && text.toLowerCase().includes('added')) {
									// Term successfully added – reset the field
									// $('#ldl_library_documents').val(null).trigger('change');
									$('#library_user_roles').val(null).trigger('change');
								}
							}
						}
					});
					observer.observe(target, {
						childList: true,
						subtree: true
					});
				}
				$('#ldl_libraries').select2({
					ajax: {
					url: '/wp-json/ldl/v1/terms', // REST route
					dataType: 'json',
					delay: 250, // debounce search
					data: function (params) {
						return {
						taxonomy: 'ldl_library', // your taxonomy slug
						q: params.term || '', // search query
						page: params.page || 1,
						per_page: 20
						};
					},
					processResults: function (data, params) {
						params.page = params.page || 1;
						return {
						results: data.results,
						pagination: data.pagination
						};
					},
					cache: true
					},
					placeholder: 'Select a library...',
					minimumInputLength: 0,
					width: '95%',
					allowClear: true
				});
			});
		</script>
        <?php }
    }

    /**
     * Render fields when editing a term
     */
    public function render_category_edit_fields($term)
    {
		$settings = get_option('ldl_general_settings');
		$enable_categories_filter = !empty($settings['enable_categories_filter']);
		$enable_categories_restriction = !empty($settings['enable_categories_restriction']);
		if($enable_categories_filter){
		$related_terms = [];
		if ( isset( $term->term_id ) ) {
			$related_terms = get_term_meta( $term->term_id, 'ldl_related_terms', true );
			if ( ! is_array( $related_terms ) ) {
				$related_terms = [];
			}
		}
		wp_nonce_field( 'ldl_save_related_terms', 'ldl_related_terms_nonce' );
		$selected_roles = (array) get_term_meta($term->term_id, 'library_user_roles', true);
        ?>
        <tr class="form-field term-ldl_libraries-wrap">
            <th scope="row"><label for="ldl_libraries"><?php esc_html_e('Select libraries', 'learndash-document-library'); ?></label></th>
            <td style="position:relative;max-width:95%;padding-right:5%;">
				<select name="ldl_libraries[]" id="ldl_libraries" multiple style="width: 100%;">
					<option value=""><?php esc_html_e('Select Libraries.', 'learndash-document-library'); ?></option>
                    <?php if ( ! empty( $related_terms ) ) {
						foreach ( $related_terms as $related_id ) {
							$term_obj = get_term( $related_id, 'ldl_library' );
							if ( ! is_wp_error( $term_obj ) && $term_obj ) {
								echo '<option value="' . esc_attr( $term_obj->term_id ) . '" selected>' . esc_html( $term_obj->name ) . '</option>';
							}
						}
					} ?>
                </select>
                <p class="description"><?php esc_html_e('Select libraries to associate with this category.', 'learndash-document-library'); ?></p>
            </td>
        </tr>
		<?php if ($enable_categories_restriction): ?>
		<tr class="form-field term-library-password-wrap">
            <th scope="row"><label for="library_password"><?php esc_html_e('Library Password', 'learndash-document-library'); ?></label></th>
            <td style="position:relative;max-width:95%;padding-right:5%;">
                <input type="password" name="library_password" id="library_password" value="<?php echo esc_attr(get_term_meta($term->term_id, 'library_password', true)); ?>" autocomplete="new-password" style="padding-right:36px;width:100%;box-sizing:border-box;" />
                <button type="button" id="toggle_library_password" style="position:absolute;top:23%;right:8%;background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;" tabindex="-1" aria-label="Show/Hide Password">
                    <svg id="eye_icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                    <svg id="eye_slash_icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12C1 12 5 5 12 5s11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                </button>
                <script>
                (function(){
                    var btn = document.getElementById('toggle_library_password');
                    if(btn){
                        btn.addEventListener('click', function(e){
                            e.preventDefault();
                            var pw = document.getElementById('library_password');
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
                <p class="description"><?php esc_html_e('Set a password to restrict access to this library from the frontend.', 'learndash-document-library'); ?></p>
            </td>
        </tr>
        <tr class="form-field term-library-user-roles-wrap">
            <th scope="row"><label for="library_user_roles"><?php esc_html_e('Allowed User Roles', 'learndash-document-library'); ?></label></th>
            <td style="position:relative;max-width:95%;padding-right:5%;">
                <select name="library_user_roles[]" id="library_user_roles" multiple style="width: 100%;">
                    <?php
                    global $wp_roles;
                    if ( ! isset( $wp_roles ) ) {
                        $wp_roles = new WP_Roles();
                    }
                    foreach ( $wp_roles->roles as $role_key => $role ) : ?>
                        <option value="<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Select user roles allowed to access this library from the frontend.', 'learndash-document-library'); ?></p>
            </td>
        </tr>
		<?php endif; ?>
		<script>
			jQuery(document).ready(function($) {
				const preSelectedRoles = <?php echo json_encode($selected_roles); ?>;
				$('#library_user_roles').val(preSelectedRoles).trigger('change');
				$('#ldl_libraries').select2({
					ajax: {
					url: '/wp-json/ldl/v1/terms', // REST route
					dataType: 'json',
					delay: 250, // debounce search
					data: function (params) {
						return {
						taxonomy: 'ldl_library', // your taxonomy slug
						q: params.term || '', // search query
						page: params.page || 1,
						per_page: 20
						};
					},
					processResults: function (data, params) {
						params.page = params.page || 1;
						return {
						results: data.results,
						pagination: data.pagination
						};
					},
					cache: true
					},
					placeholder: 'Select a library...',
					minimumInputLength: 0,
					width: '100%',
					allowClear: true
				});
			});
		</script>
        <?php }
    }

    /**
     * Save the custom taxonomy meta
     */
    public function save_taxonomy_meta($term_id)
    {
		if ( isset($_POST['ldl_library_documents']) && !empty($_POST['ldl_library_documents']) ) {
			$document_ids = array_map('intval', (array) $_POST['ldl_library_documents']);
			$term_id = (int) $term_id;
			// Get all post IDs currently associated with this term
			$currently_tagged_posts = get_objects_in_term($term_id, 'ldl_library');
			// Remove the term from posts that are no longer selected
			$posts_to_remove = array_diff($currently_tagged_posts, $document_ids);
			foreach ($posts_to_remove as $post_id) {
				wp_remove_object_terms($post_id, $term_id, 'ldl_library');
			}
			// Safely reassign the term to selected documents
			foreach ($document_ids as $post_id) {
				wp_set_object_terms($post_id, $term_id, 'ldl_library', true); // Append to existing
			}
		} else {
			// No documents selected — remove the term from all currently tagged posts
			$term_id = (int) $term_id;
			$currently_tagged_posts = get_objects_in_term($term_id, 'ldl_library');
			foreach ($currently_tagged_posts as $post_id) {
				wp_remove_object_terms($post_id, $term_id, 'ldl_library');
			}
		}
        // if (isset($_POST['ldl_library_documents'])) {
        //     $document_ids = array_map('intval', (array) $_POST['ldl_library_documents']);
        //     // Save selected document IDs in term meta
        //     // update_term_meta($term_id, 'ldl_library_documents', $document_ids);
		// 	// The taxonomy term you want to assign
		// 	$term_id = (int) $term_id;
		// 	// Get ALL post IDs currently associated with this term
		// 	$currently_tagged_posts = get_objects_in_term($term_id, 'ldl_library');
		// 	// Remove the term from posts that are not in the selected list
		// 	$posts_to_remove = array_diff($currently_tagged_posts, $document_ids);
		// 	foreach ($posts_to_remove as $post_id) {
		// 		wp_remove_object_terms($post_id, $term_id, 'ldl_library');
		// 	}
		// 	// Add the term to selected posts (safely, in case not already added)
		// 	foreach ($document_ids as $post_id) {
		// 		wp_set_object_terms($post_id, $term_id, 'ldl_library', true); // true = append to existing
		// 	}
        // }
        // Save password and user roles if restriction enabled
		// Save password
		if (isset($_POST['library_password'])) {
			update_term_meta($term_id, 'library_password', sanitize_text_field($_POST['library_password']));
		} else {
			delete_term_meta($term_id, 'library_password');
		}
		// Save user roles
		if (isset($_POST['library_user_roles']) && is_array($_POST['library_user_roles'])) {
			$roles = array_map('sanitize_text_field', $_POST['library_user_roles']);
			update_term_meta($term_id, 'library_user_roles', $roles);
		} else {
			delete_term_meta($term_id, 'library_user_roles');
		}
    }

	/**
     * Save the custom category meta
     */
    public function save_category_meta($term_id)
    {
		if ( ! isset( $_POST['ldl_related_terms_nonce'] ) || 
			! wp_verify_nonce( $_POST['ldl_related_terms_nonce'], 'ldl_save_related_terms' ) ) {
			return;
		}
		if ( isset( $_POST['ldl_libraries'] ) && is_array( $_POST['ldl_libraries'] ) ) {
			$related_terms = array_map( 'intval', $_POST['ldl_libraries'] );
			update_term_meta( $term_id, 'ldl_related_terms', $related_terms );
			$term_id = (int) $term_id;
			$currently_tagged_posts = get_objects_in_term($term_id, 'category');
			foreach ($currently_tagged_posts as $post_id) {
				$post_type = get_post_type($post_id);
    			if ($post_type === 'ldl-document') {
					wp_remove_object_terms($post_id, $term_id, 'category');
				}
			}
		} else {
			delete_term_meta( $term_id, 'ldl_related_terms' );
			$term_id = (int) $term_id;
			$currently_tagged_posts = get_objects_in_term($term_id, 'category');
			foreach ($currently_tagged_posts as $post_id) {
				$post_type = get_post_type($post_id);
    			if ($post_type === 'ldl-document') {
					wp_remove_object_terms($post_id, $term_id, 'category');
				}
			}
		}
		// if ( isset($_POST['ldl_libraries']) && !empty($_POST['ldl_libraries']) ) {
		// 	$document_ids = array_map('intval', (array) $_POST['ldl_libraries']);
		// 	$term_id = (int) $term_id;
		// 	// Get all post IDs currently associated with this term
		// 	$currently_tagged_posts = get_objects_in_term($term_id, 'category');
		// 	// Remove the term from posts that are no longer selected
		// 	$posts_to_remove = array_diff($currently_tagged_posts, $document_ids);
		// 	foreach ($posts_to_remove as $post_id) {
		// 		wp_remove_object_terms($post_id, $term_id, 'category');
		// 	}
		// 	// Safely reassign the term to selected documents
		// 	foreach ($document_ids as $post_id) {
		// 		wp_set_object_terms($post_id, $term_id, 'category', true); // Append to existing
		// 	}
		// } else {
		// 	// No documents selected — remove the term from all currently tagged posts
		// 	$term_id = (int) $term_id;
		// 	$currently_tagged_posts = get_objects_in_term($term_id, 'category');
		// 	foreach ($currently_tagged_posts as $post_id) {
		// 		wp_remove_object_terms($post_id, $term_id, 'category');
		// 	}
		// }
		// Save password and user roles if restriction enabled
		// Save password
		if (isset($_POST['library_password'])) {
			update_term_meta($term_id, 'library_password', sanitize_text_field($_POST['library_password']));
		} else {
			delete_term_meta($term_id, 'library_password');
		}
		// Save user roles
		if (isset($_POST['library_user_roles']) && is_array($_POST['library_user_roles'])) {
			$roles = array_map('sanitize_text_field', $_POST['library_user_roles']);
			update_term_meta($term_id, 'library_user_roles', $roles);
		} else {
			delete_term_meta($term_id, 'library_user_roles');
		}
    }

    /**
     * Get all ldl-document posts
     */
    private function get_ldl_documents()
    {
        $posts = get_posts(array(
            'post_type'      => 'ldl-document',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ));

        $documents = array();
        foreach ($posts as $post_id) {
            $documents[$post_id] = get_the_title($post_id);
        }

        return $documents;
    }

	/**
     * Get all ldl-document posts
     */
    public function ldl_get_terms_rest_callback( WP_REST_Request $request ) {
		$taxonomy = $request->get_param( 'taxonomy' );
		$search   = $request->get_param( 'q' );
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = max( 1, (int) $request->get_param( 'per_page' ) );
		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => $per_page,
			'offset'     => ( $page - 1 ) * $per_page,
		];
		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}
		$term_query = new WP_Term_Query( $args );
		$terms      = $term_query->get_terms();
		$total      = (int) count($term_query->terms);
		$results = [];
		foreach ( $terms as $term ) {
			$results[] = [
				'id'   => (int) $term->term_id,
				'text' => esc_html( $term->name ),
			];
		}
		$more = ( $page * $per_page ) < $total;
		return rest_ensure_response( [
			'results'    => $results,
			'pagination' => [ 'more' => $more ],
		] );
	}

	/**
	 * Add a custom column to the ldl_library taxonomy list
	 */
	public function add_custom_term_columns($columns)
	{
		$settings = get_option('ldl_general_settings');
        $enable_libraries_restriction = !empty($settings['enable_libraries_restriction']);
		// Insert the new column after the 'name' column
		$new_columns = array();
		foreach ($columns as $key => $value) {
			$new_columns[$key] = $value;
			if ($key === 'name') {
				$new_columns['library_id'] = __('Library ID', 'learndash-document-library');
				if($enable_libraries_restriction) {
					$new_columns['password'] = __('Password', 'learndash-document-library');
				}
				$new_columns['shortcodes'] = __('Shortcodes', 'learndash-document-library');
			}
		}

		return $new_columns;
	}

	/**
	 * Render content for the custom column
	 */
	public function render_custom_term_columns($content, $column_name, $term_id)
	{
		if ($column_name === 'library_id') {
			$content = esc_attr($term_id);
		}

		if ($column_name === 'password') {
			$password = get_term_meta($term_id, 'library_password', true);
			if (!empty($password)) {
				$content = str_repeat('•', strlen($password)); // Masked password
			} else {
				$content = '';
			}
		}

		if ($column_name === 'shortcodes') {
			$content = '<code>[ldl_libraries libraries="' . esc_attr($term_id) . '"]</code>';
		}

		return $content;
	}

	/**
	 * Add a custom column to the category taxonomy list
	 */
	public function add_category_term_columns($columns)
	{
		$settings = get_option('ldl_general_settings');
        $enable_categories_filter = !empty($settings['enable_categories_filter']);
        $enable_categories_restriction = !empty($settings['enable_categories_restriction']);
		// Insert the new column after the 'name' column
		$new_columns = array();
		foreach ($columns as $key => $value) {
			$new_columns[$key] = $value;
			if ($enable_categories_filter && $key === 'name') {
				$new_columns['category_id'] = __('Category ID', 'learndash-document-library');
				if($enable_categories_restriction) {
					$new_columns['password'] = __('Password', 'learndash-document-library');
				}
				$new_columns['shortcodes'] = __('Shortcodes', 'learndash-document-library');
			}
		}
		return $new_columns;
	}

	/**
	 * Render content for the custom column
	 */
	public function render_category_term_columns($content, $column_name, $term_id)
	{
		if ($column_name === 'category_id') {
			$content = esc_attr($term_id);
		}

		if ($column_name === 'password') {
			$password = get_term_meta($term_id, 'library_password', true);
			if (!empty($password)) {
				$content = str_repeat('•', strlen($password)); // Masked password
			} else {
				$content = '';
			}
		}

		if ($column_name === 'shortcodes') {
			$content = '<code>[ldl_libraries categories="' . esc_attr($term_id) . '"]</code>';
		}

		return $content;
	}

	/**
	 * Make the custom column sortable
	 */
	public function make_custom_library_column_sortable($sortable_columns) {
		$sortable_columns['library_id'] = 'library_id';	
		return $sortable_columns;
	}

	/**
	 * Make the custom column sortable
	 */
	public function make_custom_category_column_sortable($sortable_columns) {
		$sortable_columns['category_id'] = 'category_id';
		return $sortable_columns;
	}

	public function ldl_get_documents_callback() {
		check_ajax_referer('ldl_ajax_nonce', 'nonce');
		$paged  = isset($_GET['page']) ? absint($_GET['page']) : 1;
		$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
		$per_page = 20;
		$args = [
			'post_type'      => 'ldl-document',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			's'              => $search,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
			'fields'         => 'ids',
		];
		$query = new WP_Query($args);
		$results = [];
		foreach ($query->posts as $post_id) {
			$results[] = [
				'id'   => $post_id,
				'text' => get_the_title($post_id),
			];
		}
		wp_send_json([
			'results' => $results,
			'pagination' => [
				'more' => $query->max_num_pages > $paged,
			],
		]);
	}

	public function ldl_get_documents_prefill_callback() {
		check_ajax_referer('ldl_ajax_nonce', 'nonce');
		$ids = isset($_GET['ids']) ? (array) $_GET['ids'] : [];
		$ids = array_map('absint', $ids);
		$results = [];
		foreach ($ids as $post_id) {
			if (get_post_type($post_id) === 'ldl-document') {
				$results[] = [
					'id'   => $post_id,
					'text' => get_the_title($post_id),
				];
			}
		}
		wp_send_json(['results' => $results]);
	}
}
