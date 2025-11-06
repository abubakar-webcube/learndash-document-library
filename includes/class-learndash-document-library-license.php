<?php

/**
 * License Class.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LearnDash_Document_Library_License {
    private $license_key_field = null;

    /**
     * @var LearnDash_Document_Library_License_Handler
     */
    private $license_handler = null;

    public function __construct() {

        $this->license_key_field = 'wn_ldl_license_key';

        add_action( 'init', [ $this, 'plugin_init' ] );
        add_action( 'admin_notices', [ $this, 'show_license_expire_or_invalid' ], 20 );

        /**
         * Enable these for local testing
         */
         # add_filter( 'ldl_sl_api_request_verify_ssl', '__return_false', 10, 2 );
         # add_filter( 'https_ssl_verify', '__return_false' );
         # add_filter( 'http_request_host_is_external', '__return_true', 10, 3 );
    }

    public function plugin_init() {
        if ( ! current_user_can( 'manage_options' ) || ! is_admin() )
            return;

        if( !function_exists('get_plugin_data') ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        
        $plugin_data = get_plugin_data( LEARNDASH_DOCUMENT_LIBRARY_FILE );
        $this->license_handler = new LearnDash_Document_Library_License_Handler( LEARNDASH_DOCUMENT_LIBRARY_FILE, $plugin_data['Name'], $plugin_data['Version'], $plugin_data['AuthorName'], $this->license_key_field );
    }

    public function show_license_expire_or_invalid() {
        if ( ! isset( $this->license_handler ) )
            return;

        $license_setting_url = add_query_arg( array( 'page' => 'ld-document-library' ), admin_url( 'admin.php' ) );
        $error_msg = '';
        $success_msg = '';
        $submission_activate = isset( $_POST['ldl_activate_license'] ) || false;
        $submission_deactivate = isset( $_POST['ldl_deactivate_license'] ) || false;
        $license_key = isset( $_POST['wn_ldl_license_key'] ) ? sanitize_text_field( $_POST['wn_ldl_license_key'] ) : '';
        $invalid_license_err = __( 'Please enter a valid license key for <strong> LearnDash Document Library</strong> to receive latest updates. <a href="' . esc_attr( $license_setting_url ) . '">License Settings</a>', 'learndash-document-library' );
        $expired_license_err = __( 'Your License for <strong> LearnDash Document Library</strong> has been expired. You will not receive any future updates for this addon. Please purchase the addon from our site, <a href="https://wooninjas.com/downloads/learndash-document-library/">here</a> to receive a valid license key.', 'learndash-document-library' );
        
        if( $submission_activate ) {
            if( $this->license_handler->is_active() ) {
                $success_msg = __( 'License Activated!', 'learndash-document-library' );
            } else if( $this->license_handler->is_expired() ) {
                $error_msg = $expired_license_err;
            } else if( $this->license_handler->last_err() ) {
                $error_msg = $invalid_license_err;
            } else if( !$this->license_handler->is_active() ) {
                $error_msg = __( 'Invalid License Key!', 'learndash-document-library' );
            }
        } else if( $submission_deactivate ) {
            if( !$this->license_handler->is_active() ) {
                $success_msg = __( 'License Deactivated!', 'learndash-document-library' );
            }
        } else {
            if ( $this->license_handler->is_expired() ) {
                $error_msg = $expired_license_err;
            } else if( !$this->license_handler->is_active() ) {
                $error_msg = $invalid_license_err;
            }
        }

        if( $success_msg ) { ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo $success_msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
            </div>
            <?php
        } else if( $error_msg ) { ?>
            <div class="error notice is-dismissible">
                <p><?php echo $error_msg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
            </div>
            <?php
        }
    }

	/**
	 * @return LearnDash_Document_Library_License_Handler
	 */
    public function get_license_handler() {
        return $this->license_handler;
    }

	/**
	 * @return string
	 */
	public function get_license_key_field() {
		return $this->license_key_field;
	}
}
