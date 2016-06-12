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

  // Checks if variable is in content
  if ( false !== strpos( $content, '%CERTIFICATE_LINK%' ) ) {

    // Generate certificate
    $certificate_file = qsm_addon_certificate_generate_certificate( $quiz_array, true );

		// Checks if the file was created
    if ( ! empty( $certificate_file ) && false !== $certificate_file ) {

      // Prepares url and link to certificate
      $certificate_url = plugin_dir_url( __FILE__ )."certificates/$certificate_file";
      $certificate_link = "<a href='$certificate_url' class='qmn_certificate_link'>Download Certificate</a>";

      // Replaces variable with link
      $content = str_replace( '%CERTIFICATE_LINK%', $certificate_link, $content );
    } else {
      // Replaces variable with empty string if file was not created
      $content = str_replace( '%CERTIFICATE_LINK%', '', $content );
    }
  }

  // Returns the content
  return $content;
}

?>
