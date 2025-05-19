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
	$mlwQuizMasterNext->pluginHelper->register_quiz_settings_tabs( __("Certificate", 'qsm-certificate'), 'qsm_addon_certificate_quiz_settings_tabs_content' );
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

	$enable_expiry = isset($_POST['enable_expiry']) ? intval($_POST['enable_expiry']) : 0;
	$prefix = isset($_POST['prefix']) ? str_replace(' ', '', $_POST['prefix']) : '';
	$certificate_id = '';

	if ( $enable_expiry == 2 ) {
		$certificate_id = $prefix;
	} elseif ( $enable_expiry == 0 ) {
		$expiry_days = isset($_POST['expiry_days']) ? intval($_POST['expiry_days']) : 0;
		$future_date = (new DateTime())->modify('+' . $expiry_days . ' days')->format('Y-m-d');
		$future_date_without_hyphens = str_replace('-', '', $future_date);
		$certificate_id = $prefix . $future_date_without_hyphens;
	} else {
		$expiry_date = isset($_POST['expiry_date']) ? str_replace('-', '', $_POST['expiry_date']) : '';
		$certificate_id = $prefix . $expiry_date;
	}

    // Prepares certificate settings array
    $certificate_settings = array(
		'enabled'                          => intval( $_POST["enableCertificates"] ),
		'certificate_size'                 => isset($_POST["certificateSize"]) ? $_POST['certificateSize'] : "Landscape",
		'certificate_font'                 => htmlspecialchars( preg_replace( '#<script(.*?)>(.*?)</script>#is', '', sanitize_textarea_field( wp_unslash( $_POST['certificate_font'] ) ) ), ENT_QUOTES ),
		'title'                            => sanitize_text_field( $_POST["certificate_title"] ),
		//'content' => wp_kses_post( $_POST["certificate_template"] ),
		'content'                          => wp_kses_post($_POST["certificate_template"],
		array(
			'b'    => array(),
			'i'    => array(),
			'u'    => array(),
			'br'   => array(),
			'p'    => array(
				'style' => array(),
			),
			'span' => array(
				'style' => array(),
			),
		)
		),
		'logo'                             => isset($_POST["certificate_logo"]) ? $_POST["certificate_logo"] : "",
		'logo_style'                       => isset($_POST['certificate_logo_style']) ? $_POST['certificate_logo_style'] : "",
		'background'                       => isset($_POST["certificate_background"]) ? $_POST["certificate_background"] : plugins_url( '../assets/default-certificate-background.png', __FILE__ ),
		'dpi'                              => isset( $_POST["certificate_dpi"] ) ? $_POST["certificate_dpi"] : 100,
		'expiry_date'                      => (isset($_POST["expiry_date"]) && isset($_POST["enable_expiry"]) == 1) ? $_POST["expiry_date"] : "",
		'expiry_days'                      => (isset($_POST["expiry_days"]) && isset($_POST["enable_expiry"]) && $_POST["enable_expiry"] == 0) ? intval($_POST["expiry_days"]) : "",
		'prefix'                           => isset($_POST["prefix"]) ? $_POST["prefix"] : "",
		'certificate_id'                   => $certificate_id,
		'enable_expiry'                    => isset($_POST["enable_expiry"]) ? $_POST["enable_expiry"] : "",
		'never_expiry'                     => (isset($_POST["enable_expiry"]) && $_POST["enable_expiry"] == 2) ? true : false,
		'certificate_id_err_msg_wrong_txt' => (isset($_POST["certificate_id_err_msg_wrong_txt"]) && ! empty($_POST["certificate_id_err_msg_wrong_txt"])) ? $_POST["certificate_id_err_msg_wrong_txt"] : __('Certificate ID is not Valid!', 'qsm-certificate'),
		'certificate_id_err_msg_blank_txt' => (isset($_POST["certificate_id_err_msg_blank_txt"]) && ! empty($_POST["certificate_id_err_msg_blank_txt"])) ? $_POST["certificate_id_err_msg_blank_txt"] : __('Please enter a valid Certificate ID.', 'qsm-certificate'),
	);
    // Saves array as QSM setting and alerts the user
	$mlwQuizMasterNext->pluginHelper->update_quiz_setting( "certificate_settings", $certificate_settings );
	$mlwQuizMasterNext->alertManager->newAlert( 'Your certificate settings has been saved successfully!', 'success' );
	$mlwQuizMasterNext->alertManager->showAlerts();
	}
	// Load the settings
	$certificate_settings = $mlwQuizMasterNext->pluginHelper->get_quiz_setting( "certificate_settings" );

	if ( ! is_array( $certificate_settings ) ) {
		$quiz_options = $wpdb->get_row( $wpdb->prepare( "SELECT certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE quiz_id=%d LIMIT 1", intval( $_GET["quiz_id"] ) ) );
		//Load Certificate Options Variables
		if ( is_serialized( $quiz_options->certificate_template ) && is_array( @unserialize( $quiz_options->certificate_template ) ) ) {
			$certificate = @unserialize( $quiz_options->certificate_template );
			$certificate_settings  = array(
				'enabled'    => $certificate[4],
				'title'      => $certificate[0],
				'content'    => $certificate[1],
				'logo'       => $certificate[2],
				'background' => $certificate[3],
			);
		}
	}
	$font_family   = 'body{ font-family: "DejaVu Sans", sans-serif; text-align:left;}';
	if ( empty( $certificate_settings['certificate_font'] ) || 'dejavusans' == $certificate_settings['certificate_font'] ) {
        $font_family   = 'body{ font-family: "DejaVu Sans", sans-serif; text-align:left;}';
    }
	$certificate_defaults = array(
		'certificate_size' => 'Landscape',
		'enabled'          => 1,
		'certificate_font' => $font_family,
		'title'            => 'Enter your title',
		'content'          => 'Enter your content',
		'logo'             => '',
		'logo_style'       => 'text-align:center;',
		'background'       => '',
		'dpi'              => 100,
		'enable_expiry'    => 2,
	);
	$certificate_settings = wp_parse_args( $certificate_settings, $certificate_defaults );
	update_option( 'certificate_settings', $certificate_settings ,true );
	?>
	<h2><?php echo __('Certificate', 'qsm-certificate'); ?></h2>
	<p><b><?php echo __('After enabling and configuring. your certificate, you will have to add it to an email on the Emails tab or a results page on the Results Page tab using the %CERTIFICATE_LINK% variable.', 'qsm-certificate'); ?></b></p>

	<form action="" method="post">
	<button class="button-primary"><?php echo __('Save Settings', 'qsm-certificate'); ?></button>
		<table class="form-table">
			<tr valign="top">
				<td>
					<strong><?php echo __('Enable certificates for this quiz/survey?', 'qsm-certificate'); ?></strong>
				</td>
				<td>
				    <input type="radio" id="radio30" name="enableCertificates" <?php checked( $certificate_settings["enabled"], '0' ); ?> value='0' /><label for="radio30"><?php _e('Yes', 'qsm-certificate'); ?></label><br>
				    <input type="radio" id="radio31" name="enableCertificates" <?php checked( $certificate_settings["enabled"], '1' ); ?> value='1' /><label for="radio31"><?php _e('No', 'qsm-certificate'); ?></label><br>
				</td>
			</tr>
			<tr valign="top">
				<td>
					<strong><?php echo __('Certificate Orientation', 'qsm-certificate'); ?></strong>
				</td>
				<td>
				    <input type="radio" id="radio32" name="certificateSize" <?php checked( $certificate_settings["certificate_size"], 'Portrait' ); ?> value='Portrait' /><label for="radio32"><?php _e('Portrait', 'qsm-certificate'); ?></label><br>
				    <input type="radio" id="radio33" name="certificateSize" <?php checked( $certificate_settings["certificate_size"], 'Landscape','Landscape' ); ?> value='Landscape' /><label for="radio33"><?php _e('Landscape', 'qsm-certificate'); ?></label><br>
				</td>
			</tr>
			<tr valign="top">
				<td>
					<strong><?php echo __('Custom Style', 'qsm-certificate'); ?></strong>
				</td>
				<td>
					<textarea cols="50" rows="8" id="certificate_font" name="certificate_font"><?php echo trim( htmlspecialchars_decode( $certificate_settings["certificate_font"], ENT_QUOTES ) ); ?></textarea>
					<p><a href="https://quizandsurveymaster.com/docs/add-ons/certificate/#adding-google-fonts" target="_blank"><?php echo __('Click here', 'qsm-certificate') ?></a> <?php echo __('to learn about adding custom fonts.', 'qsm-certificate'); ?></p>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('Title', 'qsm-certificate'); ?></strong>
				</td>
				<td>
				<textarea cols="80" rows="3" id="certificate_title" name="certificate_title"><?php echo stripslashes( $certificate_settings["title"] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('Select PDF Resolution', 'qsm-certificate'); ?></strong>
				</td>
				<td>
					<select id="certificate_dpi" name="certificate_dpi">
						<option value="100" <?php selected($certificate_settings["dpi"], 100); ?>><?php esc_html_e('100 DPI (Low Resolution)', 'qsm-certificate'); ?></option>
						<option value="200" <?php selected($certificate_settings["dpi"], 200); ?>><?php esc_html_e('200 DPI (Standard Resolution)', 'qsm-certificate'); ?></option>
						<option value="300" <?php selected($certificate_settings["dpi"], 300); ?>><?php esc_html_e('300 DPI (Normal Resolution)', 'qsm-certificate'); ?></option>
						<option value="400" <?php selected($certificate_settings["dpi"], 400); ?>><?php esc_html_e('400 DPI (Enhanced Resolution)', 'qsm-certificate'); ?></option>
						<option value="600" <?php selected($certificate_settings["dpi"], 600); ?>><?php esc_html_e('600 DPI (High Resolution)', 'qsm-certificate'); ?></option>
						<option value="720" <?php selected($certificate_settings["dpi"], 720); ?>><?php esc_html_e('720 DPI (Ultra High Resolution)', 'qsm-certificate'); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php _e('Content', 'qsm-certificate'); ?></strong>
					<br />
					<p><?php _e('Allowed Variables:', 'qsm-certificate'); ?></p>
					<p style="margin: 2px 0" >- %POINT_SCORE%</p>
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
					<p style="margin: 2px 0">- %EXPIRY_DATE%</p>
					<p style="margin: 2px 0">- %CERTIFICATE_ID%</p>
          	<?php do_action('qsm_certificate_after_variable'); ?>
				</td>
				<td>
				<?php
				wp_editor( htmlspecialchars_decode( $certificate_settings["content"], ENT_QUOTES ), 'certificate_template', array(
					'editor_height' => 250,
					'textarea_rows' => 10,
				) ); ?>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('URL To Logo (Must be JPG, JPEG, PNG, GIF or SVG)', 'qsm-certificate'); ?></strong>
				</td>
				<td><textarea cols="80" rows="3" id="certificate_logo" name="certificate_logo"><?php echo $certificate_settings["logo"]; ?></textarea>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('Logo Img style(CSS properties)', 'qsm-certificate'); ?></strong>
				</td>
				<td><textarea cols="80" rows="3" id="certificate_logo_style" name="certificate_logo_style"><?php echo $certificate_settings["logo_style"]; ?></textarea>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('URL To Background Img (Must be JPG, JPEG, PNG, GIF or SVG)', 'qsm-certificate'); ?></strong>
					<p style="font-style: italic; color: #666; margin-top: 5px;">
    				<?php echo __('If no background image is provided, the default certificate background will be used automatically.', 'qsm-certificate'); ?>
					</p>
				</td>
				<td>
					<textarea cols="80" rows="3" class="qsm-certificate-background" id="qsm_certificate_background" name="certificate_background"><?php echo empty($certificate_settings["background"]) ? plugins_url( '../assets/default-certificate-background.png', __FILE__ ) : $certificate_settings["background"]; ?></textarea>
				</td>
				<td width="30%">
					<img src="<?php echo ! empty($certificate_settings["background"]) ? $certificate_settings["background"] : plugins_url( '../assets/default-certificate-background.png', __FILE__ ); ?>" id="qsm-certificate-image" style="width: 100px; height: 100px;">
				</td>
			</tr>
			<tr>
				<td width="30%">
    				<strong><?php echo __('Activate Expiration Settings', 'qsm-certificate'); ?></strong>
				</td>
				<td>
					<input id="never_expiry" type="radio" name="enable_expiry" value="2"
					<?php if ( isset( $certificate_settings["enable_expiry"] ) ) { checked( $certificate_settings["enable_expiry"], '2' );
					} ?>>
					<label for="never_expiry"><?php echo __('Never Expire', 'qsm-certificate'); ?></label>
				<br>
					<input id="enable_expiry_date" type="radio" name="enable_expiry" value="1" <?php if ( isset( $certificate_settings["enable_expiry"] ) ) { checked( $certificate_settings["enable_expiry"], '1' );
					} ?>>
					<label for="enable_expiry_date"><?php echo __('Expiry Date', 'qsm-certificate'); ?></label>
				<br>
					<input id="enable_expiry_days" type="radio" name="enable_expiry" value="0" <?php if ( isset( $certificate_settings["enable_expiry"] ) ) { checked( $certificate_settings["enable_expiry"], '0' );
					} ?>>
					<label for="enable_expiry_days"><?php echo __('Expiry Days', 'qsm-certificate'); ?></label>
				<br>
					<p style="font-style: italic; color: #666; margin-top: 5px;">
    				<?php echo __('Select a radio button to activate expiration settings. Choosing "Expiry Days" will calculate the expiration based on the number of days, while selecting "Expiry Date" allows you to manually set a specific date.', 'qsm-certificate'); ?>
					</p>
				</td>
			</tr>
			<tr class = "qsm-certificate-expiry-date">
				<td width="30%">
					<strong><?php echo __('Set expiry date', 'qsm-certificate'); ?></strong>
				</td>
				<td><input type="date" id="expiry_date" name="expiry_date" value="<?php echo isset($certificate_settings["expiry_date"]) ? esc_attr($certificate_settings["expiry_date"]) : ""; ?>">
				</td>
			</tr>
			<tr class = "qsm-certificate-expiry-days">
				<td width="30%">
					<strong><?php echo __('Set expiry date in X days', 'qsm-certificate'); ?></strong>
				</td>
				<td><input type="number" id="expiry_days" name="expiry_days" value="<?php echo isset($certificate_settings["expiry_days"]) ? esc_attr($certificate_settings["expiry_days"]) : ""; ?>">
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('Add Certificate Id Prefix', 'qsm-certificate'); ?></strong>
				</td>
				<td><input type="text" id="prefix" name="prefix" value="<?php echo isset($certificate_settings["prefix"]) ? esc_attr($certificate_settings["prefix"]) : ""; ?>">
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('Add form with shortcode to check expiry', 'qsm-certificate'); ?></strong>
				</td>
				<td><p><?php echo __('[quiz_expiry_check]', 'qsm-certificate'); ?></p>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('Error Message: Certificate ID is Blank', 'qsm-certificate'); ?></strong>
				</td>
				<td><input type="text" id="certificate_id_err_msg_blank_txt" name="certificate_id_err_msg_blank_txt" value="<?php echo isset($certificate_settings["certificate_id_err_msg_blank_txt"]) ? esc_attr($certificate_settings["certificate_id_err_msg_blank_txt"]) : __("Please enter a valid Certificate ID.", 'qsm-certificate'); ?>">
				</td>
			</tr>
			<tr>
				<td width="30%">
					<strong><?php echo __('Error Message: Invalid Certificate ID', 'qsm-certificate'); ?></strong>
				</td>
				<td><input type="text" id="certificate_id_err_msg_wrong_txt" name="certificate_id_err_msg_wrong_txt" value="<?php echo isset($certificate_settings["certificate_id_err_msg_wrong_txt"]) ? esc_attr($certificate_settings["certificate_id_err_msg_wrong_txt"]) : __("Certificate ID is not Valid", 'qsm-certificate'); ?>">
				</td>
			</tr>
		</table>
	<?php wp_nonce_field('certificate','certificate_nonce'); ?>
		<button class="button-primary"><?php echo __('Save Settings', 'qsm-certificate'); ?></button>
	</form>
<?php
/**
 * Displays certificate template popups for different template types.
 *
 * @param array  $certificate_template_from_script Array of certificate templates.
 * @param array  $my_templates                     User's saved templates.
 * @param string $type                             Type of template (certificate/result).
 */
function qsm_certificate_popups_for_templates( $certificate_template_from_script, $my_templates, $type ) {
    $valid_types = array( 'certificate', 'result' );

    if ( ! in_array( $type, $valid_types, true ) ) {
        return;
    }

    ?>
    <div class="qsm-popup qsm-popup-slide" id="qsm-<?php echo esc_attr( $type ); ?>-page-templates" aria-hidden="false" style="display:none;">
        <div class="qsm-popup__overlay" tabindex="-1" data-micromodal-close>
            <div class="qsm-popup__container" role="dialog" aria-modal="true" aria-labelledby="qsm-<?php echo esc_attr( $type ); ?>-page-templates-title">
                <header class="qsm-popup__header">
                    <div class="qsm-<?php echo esc_attr( $type ); ?>-page-template-header-left">
                        <img class="qsm-<?php echo esc_attr( $type ); ?>-page-template-header-image"
                             src="<?php echo esc_url( QSM_CERTIFICATE_URL . 'assets/icon-200x200.png' ); ?>"
                             alt="<?php echo esc_attr__( 'Certificate Icon', 'qsm-certificate' ); ?>" />
                        <h2 class="qsm-popup__title" id="qsm-<?php echo esc_attr( $type ); ?>-page-templates-title">
                            <?php esc_html_e( 'Certificate Templates', 'qsm-certificate' ); ?>
                        </h2>
                    </div>
                    <div class="qsm-<?php echo esc_attr( $type ); ?>-page-template-header-right">
                        <div class="qsm-<?php echo esc_attr( $type ); ?>-page-template-header"></div>
                        <a class="qsm-popup__close" aria-label="<?php echo esc_attr__( 'Close modal', 'qsm-certificate' ); ?>" data-micromodal-close></a>
                    </div>
                </header>
                <main class="qsm-popup__content" id="qsm-<?php echo esc_attr( $type ); ?>-page-templates-content"
                      data-type="<?php echo esc_attr( $type ); ?>" data-<?php echo esc_attr( $type ); ?>-page="">
                    <div class="qsm-<?php echo esc_attr( $type ); ?>-page-template-container qsm-<?php echo esc_attr( $type ); ?>-page-template-common">
                        <?php foreach ( $certificate_template_from_script as $key => $single_template ) : ?>
                            <?php if ( $type === $single_template['template_type'] ) : ?>
                                <?php
                                $image_url = QSM_CERTIFICATE_URL . 'assets/screenshot-default-theme.png';
                                if ( ! empty( $single_template['template_preview'] ) ) {
                                    $image_url = QSM_CERTIFICATE_URL . 'assets/' . $single_template['template_preview'];
                                }
                                ?>
                                <div class="qsm-<?php echo esc_attr( $type ); ?>-page-template-card">
                                    <div data-url="<?php echo esc_url( $image_url ); ?>" class="qsm-<?php echo esc_attr( $type ); ?>-page-template-card-content">
                                        <img class="qsm-<?php echo esc_attr( $type ); ?>-page-template-card-image"
                                             src="<?php echo esc_url( $image_url ); ?>"
                                             alt="<?php echo esc_attr( $single_template['template_name'] ); ?>">
                                        <div class="qsm-<?php echo esc_attr( $type ); ?>-page-template-card-buttons">
                                            <button class="qsm-<?php echo esc_attr( $type ); ?>-page-template-preview-button button"
                                                    data-indexid="<?php echo esc_attr( $key ); ?>">
                                                <img class="qsm-common-svg-image-class"
                                                     src="<?php echo esc_url( QSM_CERTIFICATE_URL . 'assets/eye-line-blue.png' ); ?>"
                                                     alt="<?php echo esc_attr__( 'Preview', 'qsm-certificate' ); ?>" />
                                                <?php esc_html_e( 'Preview', 'qsm-certificate' ); ?>
                                            </button>
                                            <button class="qsm-<?php echo esc_attr( $type ); ?>-page-template-use-button button"
                                                    data-structure="default"
                                                    data-indexid="<?php echo esc_attr( $key ); ?>">
                                                <?php esc_html_e( 'Use Template', 'qsm-certificate' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="qsm-<?php echo esc_attr( $type ); ?>-page-template-template-name">
                                        <?php echo esc_html( $single_template['template_name'] ); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <div class="qsm-popup qsm-popup-slide" id="qsm-preview-<?php echo esc_attr( $type ); ?>-page-templates" style="display:none;">
        <div class="qsm-popup__overlay" tabindex="-1" data-micromodal-close>
            <div class="qsm-popup__container" role="dialog" aria-modal="true" aria-labelledby="qsm-preview-<?php echo esc_attr( $type ); ?>-page-templates-title">
                <header class="qsm-popup__header">
                    <h2 class="qsm-popup__title" id="qsm-preview-<?php echo esc_attr( $type ); ?>-page-templates-title">
                        <?php esc_html_e( 'Template Preview', 'qsm-certificate' ); ?>
                    </h2>
                    <a class="qsm-popup__close" aria-label="<?php echo esc_attr__( 'Close modal', 'qsm-certificate' ); ?>" data-micromodal-close></a>
                </header>
                <main class="qsm-popup__content" id="qsm-preview-<?php echo esc_attr( $type ); ?>-page-templates-content">
                    <div class="qsm-preview-<?php echo esc_attr( $type ); ?>-page-template-container">
                        <div class="qsm-preview-template-image-wrapper"></div>
                    </div>
                </main>
            </div>
        </div>
    </div>
    <?php
}
}
?>
