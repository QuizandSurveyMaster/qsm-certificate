<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the tab on the quiz details page
 *
 * @return void
 * @since 0.1.0
 */
function qsm_addon_certificate_register_results_details_tabs() {
    global $mlwQuizMasterNext;
    $mlwQuizMasterNext->pluginHelper->register_results_settings_tab( esc_html__( 'Certificate Addon', 'qsm-certificate' ), 'qsm_addon_certificate_results_details_tabs_content' );
    $mlwQuizMasterNext->pluginHelper->register_admin_results_tab( esc_html__( 'Certificate Report', 'qsm-certificate' ), 'qsm_addon_certificate_details_tabs_content', 13 );
}

/**
 * Creates the certificate in the certificate tab.
 *
 * @since 0.1.0
 */
function qsm_addon_certificate_results_details_tabs_content() {
    global $wpdb, $mlwQuizMasterNext;
    if ( ! empty( $_GET['tab'] ) && 'certificate-addon' === $_GET['tab'] ) {
        wp_enqueue_style( 'qsm_certificate_admin_style', QSM_CERTIFICATE_CSS_URL . '/qsm-certificate-admin.css', array(), QSM_CERTIFICATE_VERSION );
    }

    $result_id = isset( $_GET['result_id'] ) ? absint( $_GET['result_id'] ) : 0;

    $results_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mlw_results WHERE result_id = %d", $result_id ) );

    $mlwQuizMasterNext->quizCreator->set_id( $results_data->quiz_id );
    $quiz_row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT quiz_settings, certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE quiz_id = %d LIMIT 1",
            $results_data->quiz_id
        )
    );

    // Same check the Certificate Report "Not generated" view uses.
    if ( ! $quiz_row || ! qsm_certificate_is_enabled( $quiz_row ) ) {
        ?>
        <div id="qsm-certificate-message-update" class="qsm-certificate-message">
            <p><?php esc_html_e( 'Enable setting to generate certificate', 'qsm-certificate' ); ?> 
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=mlw_quiz_options&quiz_id=' . $results_data->quiz_id . '&tab=certificate' ) ); ?>" target="_blank">
                <?php esc_html_e( 'Enable Settings', 'qsm-certificate' ); ?>
            </a></p>
        </div>
        <?php
        return;
    }

    $quiz_results = qsm_certificate_build_quiz_results( $results_data );

    $encoded_time_taken = md5( $quiz_results['time_taken'] );
    $upload = wp_upload_dir();
    $certificate_dir = trailingslashit( $upload['basedir'] ) . 'qsm-certificates/';
    $pattern = "{$quiz_results['quiz_id']}-{$quiz_results['result_id']}-$encoded_time_taken-*.pdf";
    $existing_files = glob( $certificate_dir . $pattern );
    $certificate_exists = ! empty( $existing_files );
    
    $form_submitted = isset( $_POST['certificate_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['certificate_nonce'] ) ), 'certificate' );
    
    if ( $certificate_exists ) {
        $existing_filename = basename( $existing_files[0] );
        $certificate_url = esc_url( $upload['baseurl'] . '/qsm-certificates/' . $existing_filename );
        ?>
        <div id="qsm-certificate-already-generated" class="qsm-certificate-message">
            <p>
                <strong><?php esc_html_e( 'Certificate Already Generated!', 'qsm-certificate' ); ?> </strong>
                <a target="_blank" href="<?php echo esc_url( $certificate_url ); ?>" style="color: blue;">
                    <?php esc_html_e( 'View Your Certificate', 'qsm-certificate' ); ?>
                </a>
            </p>
        </div>
        <?php
    } elseif ( ! $form_submitted ) {
        ?>
        <div id="qsm-certificate-not-found" class="qsm-certificate-message">
            <p><?php esc_html_e( 'No certificate found. Click the button below to generate one.', 'qsm-certificate' ); ?></p>
            <?php if ( ! qsm_certificate_result_is_eligible( $results_data ) ) { ?>
                <p><em><?php esc_html_e( 'Note: no results page or email this result received includes the certificate, so none was expected for it.', 'qsm-certificate' ); ?></em></p>
            <?php } ?>
        </div>
        <?php
    }

    $show_form = true;
    $button_text = $certificate_exists ? __('Regenerate Certificate', 'qsm-certificate') : __('Generate Certificate', 'qsm-certificate');

    if ( $form_submitted ) {
        if ( $certificate_exists && ! empty( $existing_files ) ) {
            foreach ( $existing_files as $file ) {
                if ( is_file( $file ) ) {
                    unlink( $file );
                }
            }
        }

        $certificate_file = qsm_addon_certificate_generate_certificate( $quiz_results, 0, true );

        if ( ! empty( $certificate_file ) && false !== $certificate_file ) {
            $certificate_url = esc_url( $upload['baseurl'] . '/qsm-certificates/' . $certificate_file );
            ?>
            <div id="qsm-certificate-created" class="qsm-certificate-message">
                <p>
                    <strong><?php esc_html_e( 'Success!', 'qsm-certificate' ); ?> </strong>
                    <?php esc_html_e( 'Your certificate has been created.', 'qsm-certificate' ); ?> 
                    <a target="_blank" href="<?php echo esc_url( $certificate_url ); ?>" style="color: blue;">
                        <?php esc_html_e( 'Download Certificate', 'qsm-certificate' ); ?>
                    </a>
                </p>
            </div>
            <?php
            $show_form   = true;
            $button_text = __( 'Regenerate Certificate', 'qsm-certificate' );
        } else {
            ?>
            <div id="qsm-certificate-failed" class="qsm-certificate-message">
                <p><?php esc_html_e( 'Failed to generate certificate. Please try again.', 'qsm-certificate' ); ?></p>
            </div>
            <?php
        }
    }

    if ( $show_form ) {
        ?>
        <form style="padding: 50px 0;" action="" method="post">
            <?php wp_nonce_field( 'certificate', 'certificate_nonce' ); ?>
            <button class="button-primary"><?php echo esc_html( $button_text ); ?></button>
        </form>
        <?php
    }
}

/**
 * Displays the certificate report tab content.
 *
 * @since 0.1.0
 */
function qsm_addon_certificate_details_tabs_content() {
    wp_enqueue_script( 'certificate-datatable-js', QSM_CERTIFICATE_JS_URL . '/datatables.min.js', array( 'jquery' ), '2.1.8', true );
    wp_enqueue_script( 'qsm_certificate_admin_script', QSM_CERTIFICATE_JS_URL . '/qsm-certificate-admin.js', array( 'jquery' ), QSM_CERTIFICATE_VERSION, true );
    wp_enqueue_style( 'qsm_certificate_admin_style', QSM_CERTIFICATE_CSS_URL . '/qsm-certificate-admin.css', array(), QSM_CERTIFICATE_VERSION );
    wp_enqueue_style( 'certificate-datatable-css', QSM_CERTIFICATE_CSS_URL . '/datatables.min.css', array(), '2.1.8' );

    wp_localize_script( 'qsm_certificate_admin_script', 'qsm_certificate_obj', array(
        'delete_confirm'          => esc_html__( 'Are you sure you want to delete this file?', 'qsm-certificate' ),
        'bulk_delete_confirm'     => esc_html__( 'Are you sure you want to delete certificates?', 'qsm-certificate' ),
        'no_certificate_selected' => esc_html__( 'Please select the certificates.', 'qsm-certificate' ),
        'info'                    => esc_html__( 'Showing _START_ to _END_ of _TOTAL_ entries', 'qsm-certificate' ),
        'search'                  => esc_html__( 'Search:', 'qsm-certificate' ),
        'lengthMenu'              => esc_html__( 'Show _MENU_ entries', 'qsm-certificate' ),
        'length_menu'             => esc_html__( 'All', 'qsm-certificate' ),
        'generate_nonce'          => wp_create_nonce( 'qsm_certificate_generate' ),
        'gen_none_selected'       => esc_html__( 'Please select results with the status "Not generated".', 'qsm-certificate' ),
        /* translators: %d: number of selected results that are not eligible */
        'gen_skip_ineligible'     => esc_html__( '%d selected result(s) are not eligible and will be skipped. Use "Generate anyway" on a row to override.', 'qsm-certificate' ),
        'gen_anyway_confirm'      => esc_html__( 'No results page or email this result received includes the certificate, so none was expected. Generate it anyway?', 'qsm-certificate' ),
        'not_eligible'            => esc_html__( 'Not eligible', 'qsm-certificate' ),
        'generate_anyway'         => esc_html__( 'Generate anyway', 'qsm-certificate' ),
        'no_action_selected'      => esc_html__( 'Please choose a bulk action.', 'qsm-certificate' ),
        'not_generated'           => esc_html__( 'Not generated', 'qsm-certificate' ),
        'generate'                => esc_html__( 'Generate', 'qsm-certificate' ),
        'delete_error'            => esc_html__( 'An error occurred during deletion.', 'qsm-certificate' ),
        'bulk_delete_error'       => esc_html__( 'An error occurred during bulk deletion.', 'qsm-certificate' ),
        'view_icon'               => esc_url( QSM_CERTIFICATE_URL . 'assets/eye-line.png' ),
        'delete_icon'             => esc_url( QSM_CERTIFICATE_URL . 'assets/trash.png' ),
        /* translators: %d: number of certificates */
        'gen_confirm'             => esc_html__( 'Generate %d certificate(s)? They are created one at a time; keep this page open until it finishes.', 'qsm-certificate' ),
        /* translators: 1: current item, 2: total */
        'gen_progress'            => esc_html__( 'Generating certificate %1$d of %2$d...', 'qsm-certificate' ),
        /* translators: 1: generated count, 2: failed count */
        'gen_done'                => esc_html__( 'Done: %1$d generated, %2$d failed.', 'qsm-certificate' ),
        'gen_stopped'             => esc_html__( 'Stopped.', 'qsm-certificate' ),
        'gen_queued'              => esc_html__( 'Queued', 'qsm-certificate' ),
        'gen_running'             => esc_html__( 'Generating...', 'qsm-certificate' ),
        'gen_generated'           => esc_html__( 'Generated', 'qsm-certificate' ),
        'gen_failed'              => esc_html__( 'Failed', 'qsm-certificate' ),
        'gen_view'                => esc_html__( 'View', 'qsm-certificate' ),
        'gen_retry'               => esc_html__( 'Retry', 'qsm-certificate' ),
        /* translators: %d: number of failed certificates */
        'gen_retry_all'           => esc_html__( 'Retry all failed (%d)', 'qsm-certificate' ),
        'gen_leave'               => esc_html__( 'Certificates are still being generated. Leave anyway?', 'qsm-certificate' ),
    ) );

    $enabled_quizzes = qsm_certificate_get_enabled_quizzes();
    $filters         = qsm_certificate_report_filters( $enabled_quizzes );

    qsm_certificate_report_render_filters( $enabled_quizzes, $filters );
    qsm_certificate_report_render_list( $enabled_quizzes, $filters );
}

add_action( 'wp_ajax_delete_certificate', 'qsm_delete_certificate' );
function qsm_delete_certificate() {
    global $wp_filesystem;

    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    WP_Filesystem();

    check_ajax_referer( 'bulk_delete_certificates_action', 'security', false );

    $upload_dir      = wp_upload_dir();
    $certificate_dir = trailingslashit( $upload_dir['basedir'] ) . 'qsm-certificates/';

    $file_name = isset( $_POST['file_name'] ) ? sanitize_file_name( wp_unslash( $_POST['file_name'] ) ) : '';

    if ( ! empty( $file_name ) ) {
        $file_to_delete = $certificate_dir . $file_name;

        if ( $wp_filesystem->exists( $file_to_delete ) ) {
            $wp_filesystem->delete( $file_to_delete );
            wp_send_json_success( esc_html__( 'File deleted successfully.', 'qsm-certificate' ) );
        } else {
            wp_send_json_error( esc_html__( 'File not found.', 'qsm-certificate' ) );
        }
    } else {
        wp_send_json_error( esc_html__( 'Invalid file name.', 'qsm-certificate' ) );
    }
}

add_action( 'wp_ajax_bulk_delete_certificates', 'qsm_bulk_delete_certificates' );
function qsm_bulk_delete_certificates() {
    global $wp_filesystem;

    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    WP_Filesystem();

    if ( ! isset( $_POST['bulk_delete_certificates_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bulk_delete_certificates_nonce'] ) ), 'bulk_delete_certificates_action' ) ) {
        wp_send_json_error( esc_html__( 'Nonce verification failed.', 'qsm-certificate' ) );
    }

    $upload_dir      = wp_upload_dir();
    $certificate_dir = trailingslashit( $upload_dir['basedir'] ) . 'qsm-certificates/';

    if ( isset( $_POST['certificates'] ) && is_array( wp_unslash( $_POST['certificates'] ) ) ) {
        foreach ( wp_unslash( $_POST['certificates'] ) as $certificate_name ) {
            $file_name      = sanitize_file_name( $certificate_name );
            $file_to_delete = $certificate_dir . $file_name;

            if ( $wp_filesystem->exists( $file_to_delete ) ) {
                $wp_filesystem->delete( $file_to_delete );
            }
        }
        wp_send_json_success( esc_html__( 'Selected certificates deleted successfully.', 'qsm-certificate' ) );
    } else {
        wp_send_json_error( esc_html__( 'No certificates selected for deletion.', 'qsm-certificate' ) );
    }
}

add_filter( 'qsm_results_array', 'qsm_addon_certificate_get_certificate_ids', 10, 2 );
function qsm_addon_certificate_get_certificate_ids( $results_array, $qmn_array_for_variables ) {
    global $wpdb, $mlwQuizMasterNext;
    
    if ( isset( $qmn_array_for_variables['quiz_id'] ) && ! empty( $qmn_array_for_variables['quiz_id'] ) ) {
        $quiz_id = intval( $qmn_array_for_variables['quiz_id'] );
        
        $mlwQuizMasterNext->pluginHelper->prepare_quiz( $quiz_id );
        
        $quiz_options        = $mlwQuizMasterNext->quiz_settings->get_quiz_options();
        $qsm_quiz_settings   = maybe_unserialize( $quiz_options->quiz_settings );
        
        $certificate_settings = isset( $qsm_quiz_settings['certificate_settings'] ) ? 
            maybe_unserialize( $qsm_quiz_settings['certificate_settings'] ) : array();
        
        $unique_id = $wpdb->get_row( $wpdb->prepare(
            "SELECT unique_id FROM {$wpdb->prefix}mlw_results WHERE quiz_id = %d ORDER BY result_id DESC LIMIT 1",
            $quiz_id
        ) );

        $certificate_prefix = isset( $certificate_settings['certificate_id'] ) ? 
            $certificate_settings['certificate_id'] : '-';
            
        $certificate_id = $certificate_prefix . $unique_id->unique_id;
        
        $results_array['certificate_id'] = $certificate_id;
    }
    
    return $results_array;
}