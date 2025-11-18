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
    $certificate_settings = $mlwQuizMasterNext->pluginHelper->get_quiz_setting( 'certificate_settings' );

    if ( ! is_array( $certificate_settings ) ) {
        $quiz_options = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE quiz_id = %d LIMIT 1",
                $results_data->quiz_id
            )
        );
        if ( $quiz_options && isset( $quiz_options->certificate_template ) ) {
            $certificate = maybe_unserialize( $quiz_options->certificate_template );
            if ( is_array( $certificate ) ) {
                $certificate_settings = array( 'enabled' => isset( $certificate[4] ) ? (int) $certificate[4] : 1 );
            }
        }
    }

    if ( ! is_array( $certificate_settings ) || ( isset( $certificate_settings['enabled'] ) && 1 === (int) $certificate_settings['enabled'] ) ) {
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

    $results = maybe_unserialize( $results_data->quiz_results );
    if ( ! is_array( $results ) ) {
        $results = array( 0, '', '' );
    }

    $quiz_results = array(
        'quiz_id'                => $results_data->quiz_id,
        'quiz_name'              => $results_data->quiz_name ?? '',
        'quiz_system'            => $results_data->quiz_system ?? 0,
        'user_name'              => $results_data->name ?? '',
        'user_business'          => $results_data->business ?? '',
        'user_email'             => $results_data->email ?? '',
        'user_phone'             => $results_data->phone ?? '',
        'user_id'                => $results_data->user ?? 0,
        'timer'                  => $results[0] ?? 0,
        'time_taken'             => $results_data->time_taken ?? '',
        'total_points'           => $results_data->point_score ?? 0,
        'total_score'            => $results_data->correct_score ?? 0,
        'total_correct'          => $results_data->correct ?? 0,
        'total_questions'        => $results_data->total ?? 0,
        'comments'               => $results[2] ?? '',
        'question_answers_array' => $results[1] ?? array(),
        'result_id'              => $result_id,
    );

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
    global $wp_filesystem, $wpdb;

    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    WP_Filesystem();

    wp_enqueue_script( 'certificate-datatable-js', QSM_CERTIFICATE_JS_URL . '/datatables.min.js', array( 'jquery' ), '2.1.8', true );
    wp_enqueue_script( 'qsm_certificate_admin_script', QSM_CERTIFICATE_JS_URL . '/qsm-certificate-admin.js', array( 'jquery' ), QSM_CERTIFICATE_VERSION, true );
    wp_enqueue_style( 'qsm_certificate_admin_style', QSM_CERTIFICATE_CSS_URL . '/qsm-certificate-admin.css', array(), QSM_CERTIFICATE_VERSION );
    wp_enqueue_style( 'certificate-datatable-css', QSM_CERTIFICATE_CSS_URL . '/datatables.min.css', array(), '2.1.8' );

    wp_localize_script( 'qsm_certificate_admin_script', 'qsm_certificate_obj', array(
        'delete_confirm'          => esc_html__( 'Are you sure you want to delete this file?', 'qsm-certificate' ),
        'bulk_delete_confirm'     => esc_html__( 'Are you sure you want to delete certificates?', 'qsm-certificate' ),
        'no_certificate_selected' => esc_html__( 'Please select the certificates.', 'qsm-certificate' ),
        'info'                    => esc_html__( 'Showing _START_ to _END_ of _TOTAL_ certificates', 'qsm-certificate' ),
        'search'                  => esc_html__( 'Search Certificates:', 'qsm-certificate' ),
        'lengthMenu'              => esc_html__( 'Show _MENU_ entries', 'qsm-certificate' ),
        'length_menu'             => esc_html__( 'All', 'qsm-certificate' ),
    ) );

    $upload_dir      = wp_upload_dir();
    $certificate_dir = trailingslashit( $upload_dir['basedir'] ) . 'qsm-certificates/';

    if ( ! $wp_filesystem->is_dir( $certificate_dir ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Certificate folder not found.', 'qsm-certificate' ) . '</p></div>';
        return;
    }

    $files = glob( $certificate_dir . '*.pdf' );

    if ( empty( $files ) ) {
        echo '<div class="notice notice-info"><p>' . esc_html__( 'No PDF certificates found.', 'qsm-certificate' ) . '</p></div>';
        return;
    }

    echo '<div class="qsm-certificate-table-container">';
    echo '<form method="post" id="qsm-certificate-form">';
    wp_nonce_field( 'bulk_delete_certificates_action', 'bulk_delete_certificates_nonce' );

    echo '<input type="submit" name="bulk_delete" value="' . esc_attr__( 'Bulk Delete', 'qsm-certificate' ) . '" class="button action" style="margin: 20px 0 0;">';

    echo '<table id="qsm-certificate-table" class="wp-list-table widefat fixed striped">';
    echo '<thead>
        <tr>
            <th class="qsm-manage-column qsm-check-column"><input type="checkbox" id="qsm-select-all-certificate"></th>
            <th class="qsm-manage-column">' . esc_html__( 'Certificate Name', 'qsm-certificate' ) . '</th>
            <th class="qsm-manage-column">' . esc_html__( 'Generated Date', 'qsm-certificate' ) . '</th>
            <th class="qsm-manage-column">' . esc_html__( 'Certificate ID', 'qsm-certificate' ) . '</th>
            <th class="qsm-manage-column">' . esc_html__( 'Expiry Date', 'qsm-certificate' ) . '</th>
            <th class="qsm-manage-column">' . esc_html__( 'Action', 'qsm-certificate' ) . '</th>
        </tr>
      </thead>';

    echo '<tbody id="qsm-certificate-list">';

    $current_date = new DateTime();

    foreach ( $files as $file ) {
        $file_name     = basename( $file );
        $file_url      = esc_url( trailingslashit( $upload_dir['baseurl'] ) . 'qsm-certificates/' . $file_name );
        $generated_date = gmdate( 'd-m-Y H:i:s', filemtime( $file ) );
        $expiration_date = null;

        $parts = explode('-', $file_name);
        $result_id = $parts[1]; 
            
        $latest_result = $wpdb->get_row( 
            $wpdb->prepare( "SELECT result_id, quiz_results FROM {$wpdb->prefix}mlw_results WHERE result_id = %d", $result_id 
            ), 
            ARRAY_A 
        );
        
        $certificate_id = '-';
        if ( $latest_result ) {
            $result_each = maybe_unserialize( $latest_result['quiz_results'] );
            if ( isset( $result_each['certificate_id'] ) ) {
                $certificate_id = $result_each['certificate_id'];
            }
        }

        if ( strlen( $file_name ) >= 53 ) {
            $last_part = substr( $file_name, -12, 10 );
            $day       = substr( $last_part, 0, 2 );
            $month     = substr( $last_part, 2, 2 );
            $year      = substr( $last_part, 4, 4 );

            $expiration_date = DateTime::createFromFormat( 'd-m-Y', $day . '-' . $month . '-' . $year );
        } 

        echo '<tr data-filename="' . esc_attr( $file_name ) . '">';
        echo '<th scope="row" class="qsm-check-column"><input type="checkbox" name="certificates[]" value="' . esc_attr( $file_name ) . '"></th>';
        echo '<td>' . esc_html( $file_name ) . '</td>';
        echo '<td>' . esc_html( $generated_date ) . '</td>';
        echo '<td>' . esc_html( $certificate_id ) . '</td>';

        if ( $expiration_date instanceof DateTime && $current_date >= $expiration_date ) {
            echo '<td style="color: red;">' . esc_html( $expiration_date->format( 'd-m-Y' ) ) . '</td>';
        } else {
            echo '<td>' . esc_html( $expiration_date instanceof DateTime ? $expiration_date->format( 'd-m-Y' ) : esc_html__( 'Never Expire', 'qsm-certificate' ) ) . '</td>';
        }

        echo '<td>
            <div class="qsm-table-icons">
                <a href="' . esc_url( $file_url ) . '" target="_blank" class="qsm-view-file">
                    <img src="' . esc_url( plugins_url( '../assets/eye-line.png', __FILE__ ) ) . '" alt="' . esc_attr__( 'View Icon', 'qsm-certificate' ) . '">
                </a> 
                <button type="button" class="qsm-delete-file" data-filename="' . esc_attr( $file_name ) . '">
                    <img src="' . esc_url( plugins_url( '../assets/trash.png', __FILE__ ) ) . '" alt="' . esc_attr__( 'Delete Icon', 'qsm-certificate' ) . '">
                </button>
            </div>
        </td>';

        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</form>';
    echo '</div>';
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