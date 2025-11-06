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
 * This is used to define Document Class for LearnDash Document Library.
 *
 * @since      1.0.0
 * @package    LearnDash_Document_Library
 * @subpackage LearnDash_Document_Library/includes
 * @author     Wooninjas <info@wooninjas.com>
 */

class LearnDash_Document_Library_Document {
    
    /**
     * Post ID
     *
     * @var int
     */
	protected $id = 0;

    /**
     * Post Object
     *
     * @var object
     */
	protected $post_object = null;

	/**
	 * Constructor
	 *
	 * @param integer 	$id
	 */
    public function __construct( $id = 0 ) {
		$this->fetch_document( $id );
	}

	/**
	 * Fetch and setup existing document
	 *
	 * @param int $id
	 */
	protected function fetch_document( $id ) {
		$this->post_object = get_post( $id, 'object' );

		if ( is_null( $this->post_object ) ) {
			throw new \Exception( esc_html__( 'Document does not exist', 'learndash-document-library' ) );
		}

		$this->id = $this->post_object->ID;
	}

	/**
	 * Retrieves meta data from the post
	 *
	 * @param 	string $key
	 * @return 	string
	 */
	public function get_meta_data( $key ) {
		return get_post_meta( $this->id, $key, true );
	}

	/**
	 * Sets the document link data
	 *
	 * @param 	string 	$type 'file' | 'none
	 * @param 	array 	$data Should contain 'file_id' for 'file'
	 */
    public function set_document_link( $type, $data = [] ) {
        update_post_meta( $this->id, '_ldl_document_upload_type', $type );

        switch ( $type ) {
            case 'none':
				if ( $this->get_file_id() && is_numeric( $this->get_file_id() ) ) {
					wp_set_object_terms( $this->get_file_id(), null, 'ldl_document_download' );
					delete_post_meta( $this->id, '_ldl_attached_file_id' );
				}
                break;
			case 'library':
				// Process file ID as normal
				if ( $this->get_file_id() && is_numeric( $this->get_file_id() ) ) {
					wp_set_object_terms( $this->get_file_id(), null, 'ldl_document_download' );
				}
				if ( filter_var( $data['file_id'], FILTER_VALIDATE_INT ) ) {
					$this->set_file_id( $data['file_id'] );
					wp_set_object_terms( $data['file_id'], 'ldl-document-download', 'ldl_document_download' );
				}
				break;
			case 'file':
				// Process file ID as normal
				if ( $this->get_uploaded_file_id() && is_numeric( $this->get_uploaded_file_id() ) ) {
					wp_set_object_terms( $this->get_uploaded_file_id(), null, '_ldl_uploaded_file' );
				}
				if ( filter_var( $data['file_id'], FILTER_VALIDATE_INT ) ) {
					$this->set_uploaded_file_id( $data['file_id'] );
					wp_set_object_terms( $data['file_id'], 'ldl-document-download', '_ldl_uploaded_file' );
				}
				break;
			case 'url':
				if(filter_var( $data['url'], FILTER_VALIDATE_URL ) ) {
					$this->set_meta_data( '_ldl_document_url', $data['url'] );
					delete_post_meta( $this->id, '_ldl_attached_file_id' );
					delete_post_meta( $this->id, '_ldl_uploaded_file' );
				} else {
					// delete_post_meta( $this->id, '_ldl_document_url' );
				}

				/*
			case 'file':
				if ( $this->get_file_id() && is_numeric( $this->get_file_id() ) ) {
					wp_set_object_terms( $this->get_file_id(), null, 'ldl_document_download' );
				}

                if ( filter_var( $data['file_id'], FILTER_VALIDATE_INT ) ) {
					$this->set_file_id( $data['file_id'] );
					wp_set_object_terms( $data['file_id'], 'ldl-document-download', 'ldl_document_download' );
				}
                break;
			*/
        }
    }

	/**
	 * Set the file id meta
	 *
	 * @param string $file_id
	 */
	public function set_file_id( $file_id ) {
        $this->set_meta_data( '_ldl_attached_file_id', $file_id );
		delete_post_meta( $this->id, '_ldl_uploaded_file' );
		delete_post_meta( $this->id, '_ldl_document_url' );
	}

	/**
	 * Set the file id meta
	 *
	 * @param string $file_id
	 */
	public function set_uploaded_file_id( $file_id ) {
        $this->set_meta_data( '_ldl_uploaded_file', $file_id );
		delete_post_meta( $this->id, '_ldl_attached_file_id' );
		delete_post_meta( $this->id, '_ldl_document_url' );
	}

	/**
	 * Sets the document URL
	 * 
	 * @param string $url
	 */
	public function set_document_url( $url ) {

	}

	/**
	 * Sets meta data
	 *
	 * @param string $key
	 * @param string $value
	 */
    protected function set_meta_data( $key, $value ) {
        update_post_meta( $this->id, $key, $value );
	}

    /**
     * Returns the document ID
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
	}

	/**
	 * Retrieves the attached file id
	 *
	 * @return string
	 */
	public function get_file_id() {
		return $this->get_meta_data( '_ldl_attached_file_id' );
	}

	/**
	 * Retrieves the uploaded file id
	 *
	 * @return string
	 */
	public function get_uploaded_file_id() {
		return $this->get_meta_data( '_ldl_uploaded_file' );
	}

	/**
	 * Retrieves the attached file url
	 * 
	 * @return string
	 */
	public function get_uploaded_file_url() {
		$file = false;

		if ( $this->get_uploaded_file_id() ) {
			$file = wp_get_attachment_url( $this->get_uploaded_file_id() );
		}

		if ( ! $file ) {
			return false;
		}

		return $file;
	}

	/**
	 * Retrieves the attached file name
	 *
	 * @return string
	 */
	public function get_file_name() {
		$file = false;

		if ( $this->get_file_id() ) {
			$file = get_attached_file( $this->get_file_id() );
		}

		if ( ! $file ) {
			return false;
		}

		return wp_basename( $file );
	}

	/**
	 * Retrieves the attached file name
	 *
	 * @return string
	 */
	public function get_uploaded_file_name() {
		$file = false;

		if ( $this->get_uploaded_file_id() ) {
			$file = get_attached_file( $this->get_uploaded_file_id() );
		}

		if ( ! $file ) {
			return false;
		}

		return wp_basename( $file );
	}

	/**
	 * Gets the link type
	 *
	 * @return string
	 */
	public function get_link_type() {
		$saved_meta = $this->get_meta_data( '_ldl_document_upload_type' );

		return $saved_meta ? $saved_meta : 'none';
	}

	/**
	 * Retrieves the custom document URL (if upload type is 'url')
	 *
	 * @return string|false
	 */
	public function get_document_url() {
		if ( $this->get_link_type() === 'url' ) {
			return $this->get_meta_data( '_ldl_document_url' );
		}
		return false;
	}

	/**
	 * Gets the download URL
	 *
	 * @return string
	 */
	public function get_download_url() {
		switch ( $this->get_link_type() ) {
			case 'file':
				$url = wp_get_attachment_url( $this->get_file_id() );
			case 'url':
				$url = $this->get_document_url();
			default:
				$url = false;
		}

		return $url;
	}

	/**
     * Generate the Download button HTML markup
     *
     * @param string $text
     * @return string
     */
    public function get_download_button( $link_text ) {
		if ( ! $this->get_download_url() ) {
			return '';
		}

		$link_text = $this->ensure_download_button_link_text( $link_text );
		$button_class =  apply_filters( 'ldl_document_library_button_column_button_class', 'document-library-button button btn' );
		$download_attribute = $this->get_download_button_attributes();

		$anchor_open = sprintf(
			'<a href="%1$s" class="%2$s" %3$s>',
			esc_url( $this->get_download_url() ),
			esc_attr( $button_class ),
			$download_attribute
		);

		$anchor_close = '</a>';

		return $anchor_open . $link_text . $anchor_close;
    }

	/**
     * Retrieves the 'download' attribute
	 *
	 * @return string
     */
    private function get_download_button_attributes() {

		if (  $this->get_link_type() !== 'file' ) {
			return '';
		}

        $mime_type = get_post_mime_type( $this->get_file_id() );

        return sprintf(' download="%1$s" type="%2$s"', basename( get_attached_file( $this->get_file_id() ) ), $mime_type );
    }

	/**
     * Retrieves the download button text
     *
     * @return string
     */
    private function ensure_download_button_link_text( $link_text ) {
        $link_text = $link_text ? $link_text : get_the_title( $this->get_id() );

        return apply_filters( 'ldl_document_library_button_column_button_text', $link_text );
    }

	/**
	 * Sets the post meta
	 * 
	 * @param string $key
	 * @param array $value
	 */
	public function set_meta( $key, $value ) {
		if ( ! is_array( $value ) ) {
			$value = array();
		}
		update_post_meta( $this->id, $key, $value );
	}

	/**
	 * Retrieves the post meta
	 * 
	 * @return array
	 */
	public function get_meta( $key ) {
		$value = get_post_meta( $this->id, $key, true );
		if ( ! is_array( $value ) ) {
			return array();
		}
		return $value;
	}
}