<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generates the certificate template variable for Quiz and Survey Master.
 *
 * @since 0.1.0
 * @param string $content The string from various templates including email and results pages.
 * @param array  $quiz_array An array of the results from the quiz/survey that was completed.
 * @return string The modified string with certificate link for email, results page, or social sharing.
 */
function qsm_addon_certificate_variable( $content, $quiz_array ) {
    global $mlwQuizMasterNext, $wpdb;

    // Load the settings.
    $certificate_settings = $mlwQuizMasterNext->pluginHelper->get_quiz_setting( 'certificate_settings' );

    // Fallback for older quiz options.
    if ( ! is_array( $certificate_settings ) ) {
        $quiz_options = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE quiz_id = %d LIMIT 1",
                $quiz_array['quiz_id']
            )
        );

        // Load certificate options if available.
        if ( isset( $quiz_options->certificate_template ) ) {
            $certificate_template = maybe_unserialize( $quiz_options->certificate_template );
            if ( is_array( $certificate_template ) ) {
                $certificate_settings = array(
                    'enabled'    => $certificate_template[4],
                    'title'      => $certificate_template[0],
                    'content'    => $certificate_template[1],
                    'logo'       => $certificate_template[2],
                    'background' => $certificate_template[3],
                );
            }
        }
    }

    // Define default certificate settings.
    $certificate_defaults = array(
        'enabled'    => 1,
        'title'      => __( 'Enter your title', 'qsm-certificate' ),
        'content'    => __( 'Enter your content', 'qsm-certificate' ),
        'logo'       => '',
        'background' => '',
    );
    $certificate_settings = wp_parse_args( $certificate_settings, $certificate_defaults );

    // Allow extensions to modify content before generating PDF.
    do_action( 'qsm_certificate_before_generate_pdf', $content, $quiz_array );

    // Process certificate if enabled.
    if ( 0 === (int) $certificate_settings['enabled'] && false !== strpos( $content, '%CERTIFICATE_LINK%' ) ) {
        // Generate certificate.
        $certificate_file = qsm_addon_certificate_generate_certificate( $quiz_array, true );

        // Check if the file was created.
        if ( ! empty( $certificate_file ) && false !== $certificate_file ) {
            $upload           = wp_upload_dir();
            $certificate_url  = esc_url( $upload['baseurl'] . '/qsm-certificates/' . $certificate_file );
            $certificate_link = sprintf(
                '<a target="_blank" href="%s" class="qmn_certificate_link">%s</a>',
                $certificate_url,
                __( 'Download Certificate', 'qsm-certificate' )
            );

            // Replace variable with link.
            $content = str_replace( '%CERTIFICATE_LINK%', $certificate_link, $content );
        } else {
            // Replace variable with empty string if file was not created.
            $content = str_replace( '%CERTIFICATE_LINK%', '', $content );
        }
    }

    return $content;
}