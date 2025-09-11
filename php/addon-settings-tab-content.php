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
