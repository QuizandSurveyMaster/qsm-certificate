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
function qsm_addon_qsm_certificate_variable( $content, $quiz_array ) {

  // Cycle through content and replace the variable
  while( strpos( $content, '%CERTIFICATE_LINK%' ) !== false ) {
    $content = str_replace( '%CERTIFICATE_LINK%', '', $content );
  }

  // Returns the content
  return $content;
}

?>
