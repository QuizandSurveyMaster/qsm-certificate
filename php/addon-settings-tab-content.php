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
	wp_enqueue_script( 'qsm_certificate_admin_script', QSM_CERTIFICATE_JS_URL . '/qsm-certificate-admin.js', array( 'jquery' ), QSM_CERTIFICATE_VERSION, true );
	wp_enqueue_style( 'qsm_certificate_admin_style', QSM_CERTIFICATE_CSS_URL . '/qsm-certificate-admin.css', array(), QSM_CERTIFICATE_VERSION );
	?>
	<nav class="nav-tab-wrapper">
		<a class="nav-tab <?php echo ('certificate' === $_GET['tab']) ? esc_attr( 'nav-tab-active' ) : ''; ?>" href="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'page' => 'qmn_addons',
						'tab'  => 'certificate',
					),
					admin_url( 'admin.php' )
				)
			);
			?>
			"><?php esc_html_e( 'Certificate Settings', 'qsm-certificate' ); ?></a>
			<a class="nav-tab <?php echo ( isset($_GET['license']) ) ? esc_attr( 'nav-tab-active' ) : ''; ?>" href="
			<?php
			echo esc_url(
				add_query_arg(
					array(
						'page'    => 'qmn_addons',
						'tab'     => 'certificate',
						'license' => '1',
					),
					admin_url( 'admin.php' )
				)
			);
			?>
		"><?php esc_html_e( 'License', 'qsm-certificate' ); ?></a>
	</nav>
	<?php
	$date_now = date("d-m-Y");
	$settings = wp_parse_args(
		get_option( 'qsm_addon_certificate_settings', array() ),
		array( 'license_key' => '' )
	);
	$license        = isset( $settings['license_key'] ) ? trim( $settings['license_key'] ) : '';
	$license_status = isset( $settings['license_status'] ) ? trim( $settings['license_status'] ) : '';
	if ( ! isset($_GET['license']) ) {
		qsm_certificate_expiration_setting_display( $license, $license_status );
	} else {
		if ( '' !== $license && 'valid' === $license_status ) {
			$params      = array(
				'invalid' => __('<span class="dashicons dashicons-warning"></span><b>Incorrect License!</b> Please enter a valid license key.', 'qsm-certificate'),
				'hold'    => __('Please Wait! We are validating your license', 'qsm-certificate'),
				'empty'   => __('Please enter a license key.', 'qsm-certificate'),
			);
			wp_localize_script( 'qsm_certificate_admin_script', 'qsm_license_validate_obj', $params );
			?>
			<div class="qsm-license-form-parent">
				<div class="qsm-license-header">
					<p class="qsm-heading">
						<?php esc_html_e( 'Manage License', 'qsm-certificate' ); ?>
					</p>
				</div>
				<div class='qsm-license-content qsm-certificate-validate-parent'>
				<?php
					if ( (isset($settings['expiry_date']) && "" !== $settings['expiry_date']) && (isset($settings['last_validate']) && "" !== $settings['last_validate']) && strtotime($date_now) > strtotime($settings['expiry_date']) && strtotime($date_now) >= strtotime($settings['last_validate']) ) {
						$item_url = 'https://quizandsurveymaster.com/checkout/?edd_license_key='.$license.'&download_id='.QSM_CERTIFICATE_ITEM;
					?>
					<div class="qsm-license-info">
						<p class='qsm-valid-msg'>
							<img class="qsm-warning" src="<?php echo esc_attr( QSM_CERTIFICATE_URL . '/img/warning.png' ); ?>" alt="">
							<span class='qsm-msg qsm-addon-title'><b><?php esc_html_e( 'Certificate License', 'qsm-certificate' ); ?></b></span>
							<span class='qsm-msg qsm-addon-status'>
								<?php esc_html_e( 'Your license is expired.', 'qsm-certificate' ); ?>
								<a target="_blank" href="<?php echo esc_url( $item_url ); ?>"><?php esc_html_e( 'Renew', 'qsm-certificate' ); ?></a>
							</span>
						</p>
						<a data-toggle='0' class="qsm-certificate-licence-toggle" href=""><?php esc_attr_e( 'Change License', 'qsm-certificate' ); ?></a>
					</div>
				<?php } else { ?>
					<div class="qsm-license-info">
						<p class='qsm-valid-msg'>
							<span class="dashicons dashicons-yes-alt"></span>
							<span class='qsm-msg qsm-addon-title'><b><?php esc_html_e( 'Certificate License', 'qsm-certificate' ); ?></b></span>
							<span class='qsm-msg qsm-addon-status'><?php esc_html_e( 'Valid till ', 'qsm-certificate' ); ?><?php esc_html_e( date( 'd M, Y', strtotime($settings['expiry_date'])), 'qsm-certificate' ); ?></span>
						</p>
						<a data-toggle='0' class="qsm-certificate-licence-toggle" href=""><?php esc_attr_e( 'Change License', 'qsm-certificate' ); ?></a>
					</div>
				<?php } ?>
					<form id="qsm-certificate-license-entry" action="" method="post" style="display: none;">
						<input type="text" name="license_key" placeholder="<?php esc_html_e( 'Enter License key', 'qsm-certificate' ); ?>"/>
						<button id="qsm-certificate-validate-btn" class="button button-primary"><?php esc_html_e( 'Validate', 'qsm-certificate' ); ?></button>
						<a data-toggle='1' class="qsm-certificate-licence-toggle" href=""><?php esc_html_e( 'Cancel', 'qsm-certificate' ); ?></a>
						<p class="qsm-validate-msg"></p>
					</form>
				</div>
			</div>
		<?php
		} else {
			$params      = array(
				'please_wait' => __('Please Wait! We are validating your license', 'qsm-certificate'),
				'license_msg' => __('License Key Required.', 'qsm-certificate'),
				'invalid'     => __('<span class="dashicons dashicons-warning"></span><b>Incorrect License!</b> Please enter a valid license key.', 'qsm-certificate'),
			);
			wp_localize_script( 'qsm_certificate_admin_script', 'qsm_certificate_obj', $params );?>
			<div class="qsm-license-form-parent">
				<div class="qsm-license-header">
					<p class="qsm-heading">
						<img height='15px' src="<?php echo esc_attr( QSM_CERTIFICATE_URL . 'assets/warning.png' ); ?>" alt="">
						<?php esc_html_e( 'Manage License', 'qsm-certificate' ); ?>
					</p>
				</div>
				<div class="qsm-certificate-validate-parent">
					<p class="qsm-activation-msg"><?php esc_html_e( 'You must activate the Certificate addon with a license key to use it with QSM Plugin.', 'qsm-certificate' ); ?></p>
					<form class="qsm-validate-license" method="POST">
						<input type="text" placeholder="<?php echo esc_html("Enter your license key"); ?>" class="qsm-certificate-validate-license-input" name="qsm_validate_license_input" />
						<input type="submit" name="qsm_validate_license_button" class="qsm-certificate-validate-license-button button button-primary" value="Validate License" />
					</form>
				</div>
				<div id="qsm-certificate-license-message" style="display: none; padding: 0 0 0 22px;"><strong class="qsm-certificate-validate-license-message"></strong></div>
			</div>
		<?php
		}
	}
}
/**
 * Displays the certificate expiration settings form.
 *
 * @param string $license        The license key.
 * @param string $license_status The license status.
 * @since 0.1.0
 * @return void
 */
function qsm_certificate_expiration_setting_display( $license, $license_status ) {
	global $mlwQuizMasterNext;

	if ( isset( $_POST['certificate_nonce'] ) && wp_verify_nonce( $_POST['certificate_nonce'], 'certificate' ) ) {
		$certificate_settings = get_option( 'certificate_settings', array() );

		$certificate_settings['certificate_id_err_msg_wrong_txt'] = isset( $_POST['certificate_id_err_msg_wrong_txt'] ) ? sanitize_text_field( $_POST['certificate_id_err_msg_wrong_txt'] ) : __( 'Certificate ID is not Valid!', 'qsm-certificate' );
		$certificate_settings['certificate_id_err_msg_blank_txt'] = isset( $_POST['certificate_id_err_msg_blank_txt'] ) ? sanitize_text_field( $_POST['certificate_id_err_msg_blank_txt'] ) : __( 'Please enter a valid Certificate ID.', 'qsm-certificate' );

		update_option( 'certificate_settings', $certificate_settings );

		$mlwQuizMasterNext->alertManager->newAlert( 'Your certificate settings have been saved successfully!', 'success' );
		$mlwQuizMasterNext->alertManager->showAlerts();
	}

	$certificate_settings = get_option( 'certificate_settings', array() );
	?>
	<form method="post">
		<table class="form-table">
			<tr>
				<td colspan="2">
					<h2><?php esc_html_e( 'Certificate Settings', 'qsm-certificate' ); ?></h2>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php esc_html_e( 'Add form with shortcode to check expiry', 'qsm-certificate' ); ?></strong>
				</td>
				<td>
					<div class="qsm-certificate-expiry-shortcode-notloop button-secondary">
						<span class="qsm-certificate-expiry-shortcode-print" style="cursor: pointer;">
							[quiz_expiry_check]
						</span>
						<span class="qsm-certificate-expiry-shortcode-info">
							<span class="certificate-copy-msg">
								<?php esc_html_e( 'Click to Copy', 'qsm-certificate' ); ?>
							</span>
							<span class="certificate-copy-success" style="display: none;">
								<?php esc_html_e( 'Copied!', 'qsm-certificate' ); ?>
							</span>
						</span>
					</div>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php esc_html_e( 'Error Message: Certificate ID is Blank', 'qsm-certificate' ); ?></strong>
				</td>
				<td>
					<input
						type="text"
						id="certificate_id_err_msg_blank_txt"
						name="certificate_id_err_msg_blank_txt"
						value="<?php echo isset( $certificate_settings['certificate_id_err_msg_blank_txt'] ) ? esc_attr( $certificate_settings['certificate_id_err_msg_blank_txt'] ) : esc_attr__( 'Please enter a valid Certificate ID.', 'qsm-certificate' ); ?>"
						class="regular-text"
					>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php esc_html_e( 'Error Message: Invalid Certificate ID', 'qsm-certificate' ); ?></strong>
				</td>
				<td>
					<input
						type="text"
						id="certificate_id_err_msg_wrong_txt"
						name="certificate_id_err_msg_wrong_txt"
						value="<?php echo isset( $certificate_settings['certificate_id_err_msg_wrong_txt'] ) ? esc_attr( $certificate_settings['certificate_id_err_msg_wrong_txt'] ) : esc_attr__( 'Certificate ID is not Valid', 'qsm-certificate' ); ?>"
						class="regular-text"
					>
				</td>
			</tr>
		</table>
		<?php wp_nonce_field( 'certificate', 'certificate_nonce' ); ?>
		<?php submit_button( __( 'Save Settings', 'qsm-certificate' ) ); ?>
	</form>
	<?php
}
