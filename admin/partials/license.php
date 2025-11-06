<?php
/**
 * License Options
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div id="ldl_license_options" class="ld-documents-panel">
    <form method="post">
        <h2><?php esc_html_e( 'License Configuration', 'learndash-document-library' ); ?></h2>
        <h3><?php esc_html_e( 'Please enter the license key for this product to get automatic updates. You were emailed the license key when you purchased this item', 'learndash-document-library' ); ?></h3>
        <table class="form-table">
            <tr>
                <th style="width:100px;"><label for="<?php echo esc_attr($this->license_class->get_license_key_field()); ?>"><?php esc_html_e( 'License Key', 'learndash-document-library' ); ?></label>
                </th>
            </tr>
            <tr>
                <td>
                    <input class="regular-text" type="text" id="<?php echo esc_attr($this->license_class->get_license_key_field()); ?>"
                           placeholder="Enter license key provided with plugin"
                           name="<?php echo esc_attr($this->license_class->get_license_key_field()); ?>"
                           value="<?php echo esc_attr(get_option( 'wn_ldl_license_key' )); ?>"
                        <?php echo ( $this->license_class->get_license_handler()->is_active() ) ? 'readonly' : ''; ?>>
                </td>
            </tr>
        </table>
        <p class="submit">
            <?php if( ! $this->license_class->get_license_handler()->is_active() ) : ?>
                <input type="submit" name="ldl_activate_license" value="<?php esc_html_e( 'Activate', 'learndash-document-library' ); ?>"
                       />
            <?php endif; ?>

            <?php if( $this->license_class->get_license_handler()->is_active() ) : ?>
                <input type="submit" name="ldl_deactivate_license" value="<?php esc_html_e( 'Deactivate', 'learndash-document-library' ); ?>"
                       />
            <?php endif; ?>
        </p>
    </form>
</div>