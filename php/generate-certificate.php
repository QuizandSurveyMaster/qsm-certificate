<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates the certificate
 *
 * @since 0.1.0
 * @param array $quiz_results The array built while scoring the quiz.
 * @param bool $return_file Whether the function should return the filepath or a boolean.
 * @return bool|string Returns false if file fails to generate. If $return_file is false, then the function will return true if pdf generation is success. If $return_file is set to true, the function will return the file's path
 */
function qsm_addon_certificate_generate_certificate( $quiz_results, $return_file = false ) {

	global $wpdb;
	global $mlwQuizMasterNext;

	// Load the settings.
	$certificate_settings = $mlwQuizMasterNext->pluginHelper->get_quiz_setting( "certificate_settings" );
	if ( ! is_array( $certificate_settings ) ) {
		$quiz_options = $wpdb->get_row( $wpdb->prepare( "SELECT certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE quiz_id=%d LIMIT 1", $quiz_results["quiz_id"] ) );
		// Loads the certificate options vVariables.
		if ( is_serialized( $quiz_options->certificate_template ) && is_array( @unserialize( $quiz_options->certificate_template ) ) ) {
			$certificate = @unserialize( $quiz_options->certificate_template );
			$certificate_settings = array(
				'enabled'    => $certificate[4],
				'title'      => $certificate[0],
				'content'    => $certificate[1],
				'logo'       => $certificate[2],
				'background' => $certificate[3]
			);
		}
	}
  $certificate_defaults = array(
    'enabled' => 1,
    'title' => 'Enter your title',
    'content' => 'Enter your content',
    'logo' => '',
    'background' => ''
  );
  $certificate_settings = wp_parse_args( $certificate_settings, $certificate_defaults );

  // If certificate is enabled
  if ( 0 == $certificate_settings["enabled"] ) {

    $encoded_time_taken = md5( $quiz_results['time_taken'] ); 
    $filename =  "{$quiz_results['quiz_id']}-{$quiz_results['timer']}-$encoded_time_taken-{$quiz_results['total_points']}-{$quiz_results['total_score']}.pdf";    
    $filename = apply_filters('qsm_certificate_file_name', $filename, $quiz_results['quiz_id'], $quiz_results['timer'], $encoded_time_taken, $quiz_results['total_score'], $quiz_results['total_points']);
    // If the certificate does not already exist
    if ( ! file_exists( plugin_dir_path( __FILE__ ) . "../certificates/$filename" ) ) {

      // Include Write HTML class
		if ( ! class_exists( 'TCPDF' ) ) {
			include( "TCPDF/tcpdf.php" );
		}

		if ( ! defined( 'K_TCPDF_THROW_EXCEPTION_ERROR' ) ) {
			define( 'K_TCPDF_THROW_EXCEPTION_ERROR', true );
		}

      // Try to create the PDF
      try {

        // Creates new PDF, set to Landscape
		$pdf = new TCPDF( 'L' );

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

		// set margins
		$pdf->SetMargins( 0, 0, 0, true );
		$pdf->SetHeaderMargin( 0 );
		$pdf->SetFooterMargin( 0 );
	
		// set auto page breaks
		$pdf->SetAutoPageBreak( false, 0 );
	
		// set image scale factor
		$pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );
	
		// set font
		$pdf->SetFont( 'dejavusans', '', 9 );

		// remove default header/footer
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
    
		$pdf->AddPage();

        // Add background
        if ( ! empty( $certificate_settings["background"] ) ) {
          $pdf->Image( $certificate_settings["background"], 0, 0, $pdf->GetPageWidth(), $pdf->GetPageHeight() );
        }
        $pdf->Ln( 20 );

        // Add title
		$pdf->SetFont( 'dejavusans', 'B', 24);
		$pdf->writeHTML( "<h1>{$certificate_settings["title"]}</h1>", true, false, true, false, 'C' );
        $pdf->Ln( 15 );

        // Add content
        $pdf->SetFont( 'dejavusans', '', 16);
        $content = apply_filters( 'qsm_addon_certificate_content_filter', $certificate_settings["content"], $quiz_results );
        $content = nl2br( $content, false );
        $content = iconv('UTF-8', 'windows-1252', $content);
        $content = utf8_encode($content);
		$pdf->writeHTML( $content, true, false, true, false, 'C' );
		
		$pdf->Ln( 15 );

        // Add logo
        if ( ! empty( $certificate_settings['logo'] ) ) {
		//   $pdf->Image( $certificate_settings["logo"], 110, 130 );
			$pdf->writeHTML( '<div style="text-align:center"><img src="' . $certificate_settings['logo'] . '" /></div>' );
        }

        // Generate the pdf
        $pdf->Output( plugin_dir_path( __FILE__ ) . "../certificates/$filename", 'F' );

      } catch (Exception $e) {
        // If failed, log error and return false
        $mlwQuizMasterNext->log_manager->add( "TCPDF Error", $e->getMessage(), 0, 'error' );
        return false;
      }
    }

    // Returns filename or true based on $return_file parameter
    if ( $return_file ) {
      return urlencode( $filename );
    } else {
      return true;
    }
  }
}

?>
