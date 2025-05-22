<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers your tab in the addon settings page.
 *
 * @since 0.1.0
 * @return void
 */
function qsm_addon_certificate_register_addon_settings_tabs() {
	global $mlwQuizMasterNext;

	if (
		! is_null( $mlwQuizMasterNext )
		&& ! is_null( $mlwQuizMasterNext->pluginHelper )
		&& method_exists( $mlwQuizMasterNext->pluginHelper, 'register_quiz_settings_tabs' )
	) {
		$mlwQuizMasterNext->pluginHelper->register_addon_settings_tab( 'Certificate', 'qsm_addon_certificate_addon_settings_tabs_content' );
	}
}

/**
 * Generates the content for your addon settings tab.
 *
 * @since 0.1.0
 * @return void
 */
function qsm_addon_certificate_addon_settings_tabs_content() {
	global $mlwQuizMasterNext;

	if ( isset( $_POST['certificate_nonce'] ) && wp_verify_nonce( $_POST['certificate_nonce'], 'certificate' ) ) {
		// Load previous license key.
		$certificate_data = get_option( 'qsm_addon_certificate_settings', array() );
		$license          = isset( $certificate_data['license_key'] ) ? trim( $certificate_data['license_key'] ) : '';

		// Save settings.
		$saved_license   = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		$certificate_data = array(
			'license_key' => $saved_license,
		);
		update_option( 'qsm_addon_certificate_settings', $certificate_data );

		// Check if license key has changed.
		if ( $license !== $saved_license ) {
			// Activate new license.
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $saved_license,
				'item_name'  => urlencode( 'Certificate' ),
				'url'        => home_url(),
			);

			wp_remote_post(
				'https://quizandsurveymaster.com',
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			// Deactivate old license.
			if ( ! empty( $license ) ) {
				$api_params = array(
					'edd_action' => 'deactivate_license',
					'license'    => $license,
					'item_name'  => urlencode( 'Certificate' ),
					'url'        => home_url(),
				);

				wp_remote_post(
					'https://quizandsurveymaster.com',
					array(
						'timeout'   => 15,
						'sslverify' => true,
						'body'      => $api_params,
					)
				);
			}
		}

		$mlwQuizMasterNext->alertManager->newAlert(
			esc_html__( 'Your settings have been saved successfully! You can now configure certificates when editing your quiz using the Certificate tab.', 'qsm-certificate' ),
			'success'
		);
	}

	// Load settings.
	$certificate_data     = get_option( 'qsm_addon_certificate_settings', array() );
	$certificate_defaults = array(
		'license_key' => '',
	);
	$certificate_data = wp_parse_args( $certificate_data, $certificate_defaults );

	// Show any alerts.
	$mlwQuizMasterNext->alertManager->showAlerts();
	?>

	<form action="" method="post">
		<table class="form-table" style="width: 100%;">
			<tr valign="top">
				<th scope="row">
					<label for="license_key"><?php esc_html_e( 'Addon License Key', 'qsm-certificate' ); ?></label>
				</th>
				<td>
					<input type="text" name="license_key" id="license_key" value="<?php echo esc_attr( $certificate_data['license_key'] ); ?>" />
				</td>
			</tr>
		</table>
		<?php wp_nonce_field( 'certificate', 'certificate_nonce' ); ?>
		<?php submit_button( __( 'Save Changes', 'qsm-certificate' ) ); ?>
	</form>

	<?php
}
