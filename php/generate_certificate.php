<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Generates the certificate
 *
 * @since 0.1.0
 * @param
 * @param
 * @return
 */
function qsm_addon_qsm_certificate_generate_certificate( $quiz_options, $quiz_results ) {

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

  if ( $certificate_settings["enabled"] ) {
    if ( ! class_exists( 'PDF_HTML' ) ) {
      include( "fpdf/WriteHTML.php" );
    }
    $pdf = new PDF_HTML():
    $pdf->AddPage( 'L' );
    if ( ! empty( $certificate_settings["logo"] ) ) {
      $pdf->Image( $certificate_settings["logo"], 0, 0, $pdf->w, $pdf->h );
    }
    $pdf->Ln( 20 );
    $pdf->SetFont( 'Arial', 'B', 24);
    $pdf->MultiCell( 280, 20, $certificate_settings["title"], 0, 'C');
    $pdf->Ln( 15 );
    $pdf->SetFont( 'Arial', '', 16);
    $content = apply_filters( 'mlw_qmn_template_variable_results_page', $certificate_settings["content"], $quiz_results );
    $pdf->WriteHTML( "<p align='center'>$content</p>" );
    if ( ! empty( $certificate_settings["background"] ) ) {
      $pdf->Image( $certificate_settings["background"], 110, 130 );
    }
    $pdf->Output('certificate.pdf','F');
  }
}
?>
