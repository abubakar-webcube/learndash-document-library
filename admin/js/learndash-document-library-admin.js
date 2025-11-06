jQuery( function( $ ) {
	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write $ code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	/**
	 * Document Upload Metabox JS
	 */
	const ldlDocumentUpload = function() {
		$( '#ldl_add_file_button' ).on( 'click', this.handleAddFile );
		$( '#ldl_remove_file_button' ).on( 'click', this.handleRemoveFile );
		$( '#ldl_document_upload_type' ).on( 'change', this.handleSelectBox.bind( this ) );

		// Handle select box on load
		this.handleSelectBox();
	};

	ldlDocumentUpload.wpMedia = null;

	/**
	 * Render second option
	 */
	ldlDocumentUpload.prototype.handleSelectBox = function( event ) {
		// const $this = $( this );
		const value = $( '#ldl_document_upload_type' ).val();
		/*
		const $file_details = $( '#ldl_file_attachment_details' );
		const $url_details = $( '#ldl_link_url_details' );
		const $file_size_input = $( '#ldl_file_size_input' );

		switch ( value ) {
			case 'file':
				$url_details.removeClass( 'active' );
				$file_details.addClass( 'active' );
				$file_size_input.prop( 'disabled', true );
				break;
			case 'url':
				$url_details.addClass( 'active' );
				$file_details.removeClass( 'active' );
				$file_size_input.removeAttr( 'disabled' );
				break;
			case 'none':
				$url_details.removeClass( 'active' );
				$file_details.removeClass( 'active' );
				$file_size_input.removeAttr( 'disabled' );
				break;
			default:
				$url_details.removeClass( 'active' );
				$file_details.removeClass( 'active' );
				$file_size_input.removeAttr( 'disabled' );
				break;
		}
		*/

		const $library = $( '#ldl_library_attachment_details' );
		const $url     = $( '#ldl_url_input_details' );
		const $file    = $( '#ldl_direct_file_upload_details' );

		// Reset all
		$library.removeClass( 'active' );
		$url.removeClass( 'active' );
		$file.removeClass( 'active' );

		// Activate based on selection
		switch ( value ) {
			case 'library':
				$library.addClass( 'active' );
				break;
			case 'url':
				$url.addClass( 'active' );
				break;
			case 'file':
				$file.addClass( 'active' );
				break;
		}
	};

	/**
	 * Handle Add File (WP Media)
	 */
	ldlDocumentUpload.prototype.handleAddFile = function( event ) {
		event.preventDefault();

		const $this = $( this );
		const $file_name = $( '#ldl_file_name' );
		const $file_name_text = $( '.ldl_file_name_text' );
		const $file_id = $( '#ldl_file_id' );
		const $file_attached_area = $( '.ldl_file_attached' );


		if ( ldlDocumentUpload.wpMedia !== null ) {
			ldlDocumentUpload.wpMedia.open();
			return;
		}

		ldlDocumentUpload.wpMedia = wp.media({
			title: ldlAdminObject.i18n.select_file,
			button: {
				text: ldlAdminObject.i18n.add_file
			}
		});

		ldlDocumentUpload.wpMedia.on( 'select', function () {
			const selection = ldlDocumentUpload.wpMedia.state().get('selection');

			selection.map( function (attachment) {
				attachment = attachment.toJSON();

				$file_name.val( attachment.filename ).trigger('change');
				$file_name_text.text( attachment.filename );
				$file_id.val( attachment.id ).trigger('change');
				$file_attached_area.addClass( 'active' );
				$this.text( ldlAdminObject.i18n.replace_file );
			});
		});

		ldlDocumentUpload.wpMedia.open();
	};

	/**
	 * Handle Remove File
	 */
	ldlDocumentUpload.prototype.handleRemoveFile = function( event ) {
		event.preventDefault();

		const $file_name = $( '#ldl_file_name' );
		const $file_name_text = $( '.ldl_file_name_text' );
		const $file_id = $( '#ldl_file_id' );
		const $file_attached_area = $( '.ldl_file_attached' );
		const $add_file_button = $( '#ldl_add_file_button' );

		$file_name_text.text('');
		$file_attached_area.removeClass( 'active' );
		$file_name.val('').trigger('change');
		$file_id.val('').trigger('change');
		$add_file_button.text( ldlAdminObject.i18n.add_file );
	};

	/**
	 * Init ldlDocumentUpload.
	 */
	new ldlDocumentUpload();

	jQuery(document).ready(function($) {
		jQuery('.ld-doc-shrtcde-panel-button').click(function() {
			console.log('xyz');
			var label = jQuery(this).prev('code').text();
			var temp = jQuery('<input>');
			jQuery('body').append(temp);
			temp.val(label).select();
			document.execCommand('copy');
			temp.remove();
		});
		
		$('#ldl_file_upload_input').on('change', function(event) {
			const file = event.target.files[0];
			if (!file) return;

			$(this).closest('form').attr('enctype', 'multipart/form-data');

			// const tempUrl = URL.createObjectURL(file);
			// console.log(tempUrl); // e.g. blob:http://localhost/xyz

			// // Remove existing hidden input if exists
			// $('#ldl_file_temp_url').remove();

			// // Append hidden input after the file input
			// $('<input>', {
			// 	type: 'hidden',
			// 	id: 'ldl_file_temp_url',
			// 	name: '_ldl_temp_file_url',
			// 	value: tempUrl
			// }).insertAfter($(this));
		});
		
		$('[name="post_title"]').attr('required', true);
		function updateRequiredFields() {
			var selectedType = $('#ldl_document_upload_type').val();

			// Remove 'required' from all fields first
			$('#ldl_file_id, #ldl_file_upload_input, #ldl_url_input').prop('required', false).closest('.form-field').show();

			// Add 'required' only to the selected one
			if (selectedType === 'library') {
				$('#ldl_file_id').prop('required', true);
			} else if (selectedType === 'file' && $('#ldl_direct_file_upload_details .ldl-file-preview').length === 0) {
				$('#ldl_file_upload_input').prop('required', true);
			} else if (selectedType === 'url') {
				$('#ldl_url_input').prop('required', true);
			}
		}

		// Initial check
		updateRequiredFields();

		// Update on change
		$('#ldl_document_upload_type').on('change', function () {
			updateRequiredFields();
			validateForm();
		});

		function validateForm() {
			let isValid = true;

			const selectedType = $('#ldl_document_upload_type').val();
			const title = $('input[name="post_title"]').val();

			if (!title) {
				isValid = false;
			}

			if (selectedType === 'library') {
				if ($('#ldl_file_id').val().trim() === '') {
					isValid = false;
				}
			} else if (selectedType === 'file') {
				if ($('#ldl_file_upload_input').val().trim() === '' && $('#ldl_direct_file_upload_details .ldl-file-preview').length === 0) {
					isValid = false;
				}
			} else if (selectedType === 'url') {
				if ($('#ldl_url_input').val().trim() === '') {
					isValid = false;
				}
			}

			$('#publish').prop('disabled', !isValid);
			$('#save-post').prop('disabled', !isValid);
		}

		// Bind validation check to input changes
		$('input, select, textarea').on('input change', validateForm);

		// Run on page load
		validateForm();

    });

} );
