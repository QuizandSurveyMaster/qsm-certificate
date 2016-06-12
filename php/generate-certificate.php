<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Generates the certificate
 *
 * @since 0.1.0
 * @param array $quiz_results The array built while scoring the quiz
 * @param bool $return_file Whether the function should return the filepath or a boolean
 * @return bool|string Returns false if file fails to generate. If $return_file is false, then the function will return true if pdf generation is success. If $return_file is set to true, the function will return the file's path
 */
function qsm_addon_certificate_generate_certificate( $quiz_results, $return_file = false ) {

  global $wpdb;
	global $mlwQuizMasterNext;

  // Load the settings
  $certificate_settings = $mlwQuizMasterNext->pluginHelper->get_quiz_setting( "certificate_settings" );
  $certificate_defaults = array(
    'enabled' => 1,
    'title' => 'Enter your title',
    'content' => 'Enter your content',
    'logo' => '',
    'background' => ''
  );
  $certificate_settings = wp_parse_args( $certificate_settings, $certificate_defaults );

  // If certificate is enabled
  if ( $certificate_settings["enabled"] ) {

    $filename = 'certificate.pdf';

    // If the certificate does not already exist
    if ( ! $file_exists( "../certificates/$filename" ) ) {

      // Include Write HTML class
      if ( ! class_exists( 'PDF_HTML' ) ) {
        include( "fpdf/WriteHTML.php" );
      }

      // Try to create the PDF
      try {

        // Creates new PDF, set to Landscape
        $pdf = new PDF_HTML();
        $pdf->AddPage( 'L' );

        // Add logo
        if ( ! empty( $certificate_settings["logo"] ) ) {
          $pdf->Image( $certificate_settings["logo"], 0, 0, $pdf->w, $pdf->h );
        }
        $pdf->Ln( 20 );

        // Add title
        $pdf->SetFont( 'Arial', 'B', 24);
        $pdf->MultiCell( 280, 20, $certificate_settings["title"], 0, 'C');
        $pdf->Ln( 15 );

        // Add content
        $pdf->SetFont( 'Arial', '', 16);
        $content = apply_filters( 'mlw_qmn_template_variable_results_page', $certificate_settings["content"], $quiz_results );
        $pdf->WriteHTML( "<p align='center'>$content</p>" );

        // Add background
        if ( ! empty( $certificate_settings["background"] ) ) {
          $pdf->Image( $certificate_settings["background"], 110, 130 );
        }

        // Generate the pdf
        $pdf->Output( 'F', "../certificates/$filename" );

      } catch (Exception $e) {
        // If failed, log error and return false
        $mlwQuizMasterNext->log_manager->add( "FPDF Error", $e->getMessage(), 0, 'error' );
        return false;
      }
    }

    // Returns filename or true based on $return_file parameter
    if ( $return_file ) {
      return $filename;
    } else {
      return true;
    }
  }
}
?>
