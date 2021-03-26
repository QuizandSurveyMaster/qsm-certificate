<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers your tab in the quiz settings page
 *
 * @since 0.1.0
 * @return void
 */
function qsm_addon_certificate_register_quiz_settings_tabs() {
  global $mlwQuizMasterNext;
  if ( ! is_null( $mlwQuizMasterNext ) && ! is_null( $mlwQuizMasterNext->pluginHelper ) && method_exists( $mlwQuizMasterNext->pluginHelper, 'register_quiz_settings_tabs' ) ) {
    $mlwQuizMasterNext->pluginHelper->register_quiz_settings_tabs( "Certificate", 'qsm_addon_certificate_quiz_settings_tabs_content' );
  }
}

/**
 * Generates the content for your quiz settings tab
 *
 * @since 0.1.0
 * @return void
 */
function qsm_addon_certificate_quiz_settings_tabs_content() {

  // Enqueue your scripts and styles
  wp_enqueue_script( 'qsm_certificate_admin_script', plugins_url( '../js/qsm-certificate-admin.js' , __FILE__ ), array( 'jquery' ) );
  wp_enqueue_style( 'qsm_certificate_admin_style', plugins_url( '../css/qsm-certificate-admin.css' , __FILE__ ) );

  global $wpdb;
	global $mlwQuizMasterNext;

  // If nonce is set and correct, save certificate settings
  if ( isset( $_POST["certificate_nonce"] ) && wp_verify_nonce( $_POST['certificate_nonce'], 'certificate') ) {

    // Prepares certificate settings array
    $certificate_settings = array(
      'enabled' => intval( $_POST["enableCertificates"] ),
      'certificate_font' => sanitize_text_field( $_POST["certificate_font"] ),
      'title' => sanitize_text_field( $_POST["certificate_title"] ),
      //'content' => wp_kses_post( $_POST["certificate_template"] ),
      'content' =>  wp_kses($_POST["certificate_template"],
         array(
           'b' => array(),
           'i' => array(),
           'u' => array(),
           'br' => array(),
           'p' => array(
               'style' => array()
           ),
           'span' => array(
               'style' => array()
           )
         )
        ) ,
      'logo' => esc_url_raw( $_POST["certificate_logo"] ),
      'background' => esc_url_raw( $_POST["certificate_background"] )
    );

    // Saves array as QSM setting and alerts the user
    $mlwQuizMasterNext->pluginHelper->update_quiz_setting( "certificate_settings", $certificate_settings );
    $mlwQuizMasterNext->alertManager->newAlert( 'Your certificate settings has been saved successfully!', 'success' );
  }

  // Load the settings
  $certificate_settings = $mlwQuizMasterNext->pluginHelper->get_quiz_setting( "certificate_settings" );
  if ( ! is_array( $certificate_settings ) ) {
    $quiz_options = $wpdb->get_row( $wpdb->prepare( "SELECT certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE quiz_id=%d LIMIT 1", intval( $_GET["quiz_id"] ) ) );
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
    'certificate_font' => 'dejavusans',
    'title' => 'Enter your title',
    'content' => 'Enter your content',
    'logo' => '',
    'background' => ''
  );
  $certificate_settings = wp_parse_args( $certificate_settings, $certificate_defaults );

	?>
  <h2>Certificate</h2>
  <p><b><?php echo __('After enabling and configuring your certificate, you will have to add it to an email on the Emails tab or a results page on the Results Page tab using the %CERTIFICATE_LINK% variable.'); ?></b></p>
  <p><a href="https://quizandsurveymaster.com/docs/v7/adding-custom-font-to-certificate-addon/" target="_blank"><?php echo __('Click here') ?></a> <?php echo __('to learn about adding custom fonts.'); ?></p>
  <form action="" method="post">
    <button class="button-primary"><?php echo __('Save Settings'); ?></button>
		<table class="form-table">
			<tr valign="top">
				<td><label for="enableCertificates"><?php echo __('Enable certificates for this quiz/survey?'); ?></label></td>
				<td>
				    <input type="radio" id="radio30" name="enableCertificates" <?php checked( $certificate_settings["enabled"], '0' ); ?> value='0' /><label for="radio30">Yes</label><br>
				    <input type="radio" id="radio31" name="enableCertificates" <?php checked( $certificate_settings["enabled"], '1' ); ?> value='1' /><label for="radio31">No</label><br>
				</td>
			</tr>
      <tr valign="top">
				<td>
          <label for="enableCertificates"><strong><?php echo __('Certificates font family'); ?></strong></label>
          <p>
            <?php echo __('Please add font file (ttf) attachment ID or file path.') ?>
          </p>
        </td>
				<td>
				    <input type="text" id="certificate_font" name="certificate_font" value='<?php echo $certificate_settings["certificate_font"]; ?>' /><br>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('Title'); ?></strong>
				</td>
                                <td><textarea cols="80" rows="15" id="certificate_title" name="certificate_title"><?php echo stripslashes( $certificate_settings["title"] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong>Content</strong>
					<br />
					<p><?php _e('Allowed Variables:', 'quiz-master-next'); ?></p>
					<p style="margin: 2px 0">- %POINT_SCORE%</p>
					<p style="margin: 2px 0">- %AVERAGE_POINT%</p>
					<p style="margin: 2px 0">- %AMOUNT_CORRECT%</p>
					<p style="margin: 2px 0">- %TOTAL_QUESTIONS%</p>
					<p style="margin: 2px 0">- %CORRECT_SCORE%</p>
					<p style="margin: 2px 0">- %QUIZ_NAME%</p>
					<p style="margin: 2px 0">- %USER_NAME%</p>
					<p style="margin: 2px 0">- %FULL_NAME%</p>
					<p style="margin: 2px 0">- %USER_BUSINESS%</p>
					<p style="margin: 2px 0">- %USER_PHONE%</p>
					<p style="margin: 2px 0">- %USER_EMAIL%</p>
					<p style="margin: 2px 0">- %CURRENT_DATE%</p>
					<p style="margin: 2px 0">- %DATE_TAKEN%</p>
				</td>
				<td><label for="certificate_template"><?php echo __('Allowed tags'); ?>: &lt;b&gt; - bold, &lt;i&gt;-italics, &lt;u&gt;-underline, &lt;br&gt;-<?php echo __('New Line or start a new line by pressing enter') ?></label><textarea cols="80" rows="15" id="certificate_template" name="certificate_template"><?php echo $certificate_settings["content"]; ?></textarea>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('URL To Logo (Must be JPG, JPEG, PNG, GIF or SVG)'); ?></strong>
				</td>
				<td><textarea cols="80" rows="15" id="certificate_logo" name="certificate_logo"><?php echo $certificate_settings["logo"]; ?></textarea>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('URL To Background Img (Must be JPG, JPEG, PNG, GIF or SVG)'); ?></strong>
				</td>
				<td><textarea cols="80" rows="15" id="certificate_background" name="certificate_background"><?php echo $certificate_settings["background"]; ?></textarea>
				</td>
			</tr>
		</table>
    <?php wp_nonce_field('certificate','certificate_nonce'); ?>
		<button class="button-primary"><?php echo __('Save Settings'); ?></button>
	</form>
  <?php
}
?>
