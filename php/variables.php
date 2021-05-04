<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Generates your template variable
 *
 * @since 0.1.0
 * @param string $content The string from various templates including email and results pages
 * @param array $quiz_array An array of the results from the quiz/survey that was completed
 * @return string The string to be used in email, results page, social sharing, etc..
 */
function qsm_addon_certificate_variable( $content, $quiz_array ) {

  global $mlwQuizMasterNext;

  // Load the settings
  $certificate_settings = $mlwQuizMasterNext->pluginHelper->get_quiz_setting( "certificate_settings" );
  if ( ! is_array( $certificate_settings ) ) {
    global $wpdb;
    $quiz_options = $wpdb->get_row( $wpdb->prepare( "SELECT certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE quiz_id=%d LIMIT 1", $quiz_array["quiz_id"] ) );
    //Load Certificate Options Variables
  	if ( is_serialized( $quiz_options->certificate_template ) && is_array( @unserialize( $quiz_options->certificate_template ) ) ) {
  		$certificate = @unserialize( $quiz_options->certificate_template );
      $certificate_settings = array(
        'enabled' => $certificate[4],
        'title' => $certificate[0],
        'content' => $certificate[1],
        'logo' => $certificate[2],
        'background' => $certificate[3]
      );
  	}
  }
  $certificate_defaults = array(
    'enabled' => 1,
    'title' => __('Enter your title', 'qsm-certificate'),
    'content' => __('Enter your content', 'qsm-certificate'),
    'logo' => '',
    'background' => ''
  );
  $certificate_settings = wp_parse_args( $certificate_settings, $certificate_defaults );

  // If certificate is enabled
  if ( 0 == $certificate_settings["enabled"] ) {

    // Checks if variable is in content
    if ( false !== strpos( $content, '%CERTIFICATE_LINK%' ) ) {

      // Generate certificate
      $certificate_file = qsm_addon_certificate_generate_certificate( $quiz_array, true );

  		// Checks if the file was created
      if ( ! empty( $certificate_file ) && false !== $certificate_file ) {
        $upload = wp_upload_dir();
        // Prepares url and link to certificate
        $certificate_url = $upload['baseurl']."/qsm-certificates/$certificate_file";
        $certificate_link = "<a target='_blank' href='$certificate_url' class='qmn_certificate_link'>". __('Download Certificate', 'qsm-certificate') ."</a>";

        // Replaces variable with link
        $content = str_replace( '%CERTIFICATE_LINK%', $certificate_link, $content );
      } else {
        // Replaces variable with empty string if file was not created
        $content = str_replace( '%CERTIFICATE_LINK%', '', $content );
      }
    }

  }

  // Returns the content
  return $content;
}

?>
