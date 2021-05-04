<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
* Registers the tab on the quiz details page
*
* @return void
* @since 0.1.0
*/
function qsm_addon_certificate_register_results_details_tabs() {
	global $mlwQuizMasterNext;
	$mlwQuizMasterNext->pluginHelper->register_results_settings_tab( __('Certificate Addon', 'qsm-certificate'), "qsm_addon_certificate_results_details_tabs_content" );
}

/**
* Creates the certificate in the certificate tab.
*
* @since 0.1.0
*/
function qsm_addon_certificate_results_details_tabs_content() {

  global $wpdb;
	global $mlwQuizMasterNext;

  // If nonce is set and correct, save certificate settings
  if ( isset( $_POST["certificate_nonce"] ) && wp_verify_nonce( $_POST['certificate_nonce'], 'certificate') ) {

    // Retrieve results
    $result_id = intval( $_GET["result_id"] );
		$results_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mlw_results WHERE result_id=%d", $result_id ) );

    // Prepare result data
    if ( is_serialized( $results_data->quiz_results ) && is_array( @unserialize( $results_data->quiz_results ) ) ) {
			$results = unserialize($results_data->quiz_results);
		} else {
			$results = array( 0, '', '' );
		}

    // Prepare result array
    $quiz_results = array(
			'quiz_id' => $results_data->quiz_id,
			'quiz_name' => $results_data->quiz_name,
			'quiz_system' => $results_data->quiz_system,
			'user_name' => $results_data->name,
			'user_business' => $results_data->business,
			'user_email' => $results_data->email,
			'user_phone' => $results_data->phone,
			'user_id' => $results_data->user,
			'timer' => $results[0],
			'time_taken' => $results_data->time_taken,
			'total_points' => $results_data->point_score,
			'total_score' => $results_data->correct_score,
			'total_correct' => $results_data->correct,
			'total_questions' => $results_data->total,
			'comments' => $results[2],
			'question_answers_array' => $results[1]
		);

		$mlwQuizMasterNext->quizCreator->set_id( $results_data->quiz_id );

    // Generate certificate
    $certificate_file = qsm_addon_certificate_generate_certificate( $quiz_results, true );

		// Display link to certificate
    if ( ! empty( $certificate_file ) && false !== $certificate_file ) {
		$upload = wp_upload_dir();
      	$certificate_url = $upload['baseurl']."/qsm-certificates/$certificate_file";
			?>
                        <div id="message" class="updated below-h2" style="margin-top: 20px;">
				<p>
					<strong><?php _e('Success!', 'qsm-certificate'); ?> </strong>
					<?php _e('Your certificate has been created.', 'qsm-certificate'); ?> <a target='_blank' href='<?php echo $certificate_url; ?>' style='color: blue;'><?php _e('Download Certificate', 'qsm-certificate'); ?></a>
				</p>
			</div>
			<?php
    }
  }
	?>
<form style="padding: 50px 0;" action="" method="post">
		<?php wp_nonce_field('certificate','certificate_nonce'); ?>
		<button class="button-primary"><?php _e('Generate Certificate', 'qsm-certificate'); ?></button>
	</form>
	<?php
}
?>
