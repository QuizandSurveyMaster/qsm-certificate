<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers your tab in the addon  settings page
 *
 * @since 0.1.0
 * @return void
 */
function qsm_addon_certificate_register_addon_settings_tabs() {
  global $mlwQuizMasterNext;
  if ( ! is_null( $mlwQuizMasterNext ) && ! is_null( $mlwQuizMasterNext->pluginHelper ) && method_exists( $mlwQuizMasterNext->pluginHelper, 'register_quiz_settings_tabs' ) ) {
    $mlwQuizMasterNext->pluginHelper->register_addon_settings_tab( "Certificate", 'qsm_addon_certificate_addon_settings_tabs_content' );
  }
}

/**
 * Generates the content for your addon settings tab
 *
 * @since 0.1.0
 * @return void
 */
function qsm_addon_certificate_addon_settings_tabs_content() {
  global $mlwQuizMasterNext;

  //If nonce is correct, update settings from passed input
  if ( isset( $_POST["certificate_nonce"] ) && wp_verify_nonce( $_POST['certificate_nonce'], 'certificate') ) {

    // Load previous license key
    $certificate_data = get_option( 'qsm_addon_certificate_settings', '' );
    if ( isset( $certificate_data["license_key"] ) ) {
      $license = trim( $certificate_data["license_key"] );
    } else {
      $license = '';
    }

    // Save settings
    $saved_license = sanitize_text_field( $_POST["license_key"] );
    $certificate_data = array(
      'license_key' => $saved_license
    );
    update_option( 'qsm_addon_certificate_settings', $certificate_data );

    // Checks to see if the license key has changed
    if ( $license != $saved_license ) {

      // Prepares data to activate the license
      $api_params = array(
        'edd_action'=> 'activate_license',
        'license' 	=> $saved_license,
        'item_name' => urlencode( 'Certificate' ), // the name of our product in EDD
        'url'       => home_url()
      );

      // Call the custom API.
      $response = wp_remote_post( 'http://quizandsurveymaster.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      // If previous license key was entered
      if ( ! empty( $license ) ) {

        // Prepares data to deactivate changed license
        $api_params = array(
          'edd_action'=> 'deactivate_license',
          'license' 	=> $license,
          'item_name' => urlencode( 'Certificate' ), // the name of our product in EDD
          'url'       => home_url()
        );

        // Call the custom API.
        $response = wp_remote_post( 'http://quizandsurveymaster.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
      }
    }
    $mlwQuizMasterNext->alertManager->newAlert( 'Your settings has been saved successfully! You can now configure certificates when editing your quiz using the Certificate tab.', 'success' );
  }

  // Load settings
  $certificate_data = get_option( 'qsm_addon_certificate_settings', '' );
  $certificate_defaults = array(
    'license_key' => ''
  );
  $certificate_data = wp_parse_args( $certificate_data, $certificate_defaults );

  // Show any alerts from saving
  $mlwQuizMasterNext->alertManager->showAlerts();

  ?>
  <form action="" method="post">
    <table class="form-table" style="width: 100%;">
      <tr valign="top">
        <th scope="row"><label for="license_key"><?php _e('Addon License Key', 'qsm-certificate'); ?></label></th>
        <td><input type="text" name="license_key" id="license_key" value="<?php echo $certificate_data["license_key"]; ?>"></td>
      </tr>
    </table>
    <?php wp_nonce_field('certificate','certificate_nonce'); ?>
    <button class="button-primary">Save Changes</button>
  </form>
  <?php
}
?>
