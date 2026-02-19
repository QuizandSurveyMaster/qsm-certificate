<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Dompdf\Dompdf;
use Dompdf\Options;
// reference the Dompdf namespace

function qsm_get_dpi_tier_config() {
	return array(
		'tiers' => array(
			array(
				'dpi'          => 100,
				'memory'       => '128M',
				'time_limit'   => 30,
				'description'  => 'Screen quality',
				'safe'         => true,
			),
			array(
				'dpi'          => 200,
				'memory'       => '256M',
				'time_limit'   => 60,
				'description'  => 'Standard print quality',
				'safe'         => true,
			),
			array(
				'dpi'          => 300,
				'memory'       => '384M',
				'time_limit'   => 90,
				'description'  => 'High print quality',
				'safe'         => true,
			),
			array(
				'dpi'          => 400,
				'memory'       => '512M',
				'time_limit'   => 120,
				'description'  => 'Very high quality (risky)',
				'safe'         => false,
			),
			array(
				'dpi'          => 600,
				'memory'       => '768M',
				'time_limit'   => 180,
				'description'  => 'Maximum quality (extremely risky)',
				'safe'         => false,
			),
			array(
				'dpi'          => 720,
				'memory'       => '1024M',
				'time_limit'   => 240,
				'description'  => 'Extreme quality (not recommended)',
				'safe'         => false,
			),
		),
	);
}

function qsm_check_dpi_feasibility( $requested_dpi ) {
	$requested_dpi = (int) $requested_dpi;
	$config        = qsm_get_dpi_tier_config();

	$requested_tier = null;
	foreach ( $config['tiers'] as $tier ) {
		if ( $tier['dpi'] === $requested_dpi ) {
			$requested_tier = $tier;
			break;
		}
		if ( $tier['dpi'] > $requested_dpi ) {
			$requested_tier = $tier;
			break;
		}
	}

	if ( null === $requested_tier ) {
		$requested_tier = end( $config['tiers'] );
	}

	$result = array(
		'requested_dpi'     => $requested_dpi,
		'approved_dpi'      => $requested_tier['dpi'],
		'memory_required'   => $requested_tier['memory'],
		'time_limit'        => $requested_tier['time_limit'],
		'tier_description'  => $requested_tier['description'],
		'is_safe'           => $requested_tier['safe'],
		'warnings'          => array(),
		'errors'            => array(),
		'fallback_applied'  => false,
	);

	$current_memory = (int) wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) );
	$required_memory = (int) wp_convert_hr_to_bytes( $requested_tier['memory'] );

	if ( -1 !== $current_memory && $current_memory < $required_memory ) {
		$result['warnings'][] = sprintf(
			'Low memory: Current %s, need %s for DPI %d',
			ini_get( 'memory_limit' ),
			$requested_tier['memory'],
			$requested_tier['dpi']
		);

		foreach ( array_reverse( $config['tiers'] ) as $tier ) {
			$tier_memory = (int) wp_convert_hr_to_bytes( $tier['memory'] );
			if ( $tier_memory <= $current_memory || -1 === $current_memory ) {
				$result['approved_dpi']     = $tier['dpi'];
				$result['memory_required']  = $tier['memory'];
				$result['time_limit']       = $tier['time_limit'];
				$result['tier_description'] = $tier['description'];
				$result['is_safe']          = $tier['safe'];
				$result['fallback_applied'] = true;
				$result['warnings'][] = sprintf(
					'DPI reduced from %d to %d due to memory constraints',
					$requested_dpi,
					$tier['dpi']
				);
				break;
			}
		}
	}

	if ( ! $requested_tier['safe'] ) {
		$result['warnings'][] = sprintf(
			'DPI %d is in high-risk category. Generation might be slow or fail.',
			$requested_tier['dpi']
		);
	}

	$max_execution = (int) ini_get( 'max_execution_time' );
	if ( $max_execution > 0 && $max_execution < $requested_tier['time_limit'] ) {
		$result['warnings'][] = sprintf(
			'PHP max_execution_time (%ds) might be insufficient for DPI %d (needs ~%ds)',
			$max_execution,
			$requested_tier['dpi'],
			$requested_tier['time_limit']
		);
	}

 	return $result;
}

function qsm_certificate_prepare_rendering_resources( $dpi ) {
	global $mlwQuizMasterNext;
	$dpi = (int) $dpi;

	$dpi_check = qsm_check_dpi_feasibility( $dpi );

	$final_dpi = (int) $dpi_check['approved_dpi'];

	$result = array(
		'success'               => true,
		'requested_dpi'         => $dpi,
		'final_dpi'             => $final_dpi,
		'fallback_applied'      => $dpi_check['fallback_applied'],
		'warnings'              => $dpi_check['warnings'],
		'tier_description'      => $dpi_check['tier_description'],
	);

	if ( $dpi_check['fallback_applied'] || ! empty( $dpi_check['warnings'] ) ) {
		$log_title = 'QSM Certificate DPI Check';
		$log_message = sprintf(
			'Requested DPI: %1$d | Using: %2$d | Details: %3$s',
			$dpi,
			$final_dpi,
			empty( $dpi_check['warnings'] ) ? 'None' : implode( ' | ', $dpi_check['warnings'] )
		);

		$mlwQuizMasterNext->log_manager->add( $log_title, $log_message, 0, 'warning' );
	}

	return $result;
}

/**
 * Generates the certificate
 *
 * @since 0.1.0
 * @param array $quiz_results The array built while scoring the quiz.
 * @param bool $return_file Whether the function should return the filepath or a boolean.
 * @return bool|string Returns false if file fails to generate. If $return_file is false, then the function will return true if pdf generation is success. If $return_file is set to true, the function will return the file's path
 */
function qsm_addon_certificate_generate_certificate( $quiz_results, $template_id = 0, $return_file = false ) {
    require_once(plugin_dir_path(__FILE__) . '../dompdf/vendor/autoload.php');
	global $wpdb;
	global $mlwQuizMasterNext;
    $certificate_settings = $mlwQuizMasterNext->pluginHelper->get_quiz_setting( "certificate_settings" );
    if ( ! is_array( $certificate_settings ) ) {
		$quiz_options = $wpdb->get_row( $wpdb->prepare( "SELECT certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE quiz_id=%d LIMIT 1", $quiz_results["quiz_id"] ) );
		// Loads the certificate options vVariables.
		if ( $quiz_options && is_serialized( $quiz_options->certificate_template ) && is_array( @unserialize( $quiz_options->certificate_template ) ) ) {
			$certificate = @unserialize( $quiz_options->certificate_template );
			$certificate_settings    = array(
				'enabled'    => $certificate[4],
				'title'      => $certificate[0],
				'content'    => $certificate[1],
				'logo'       => $certificate[2],
				'background' => $certificate[3],
			);
		}
	}
    $certificate_defaults = array(
        'enabled'          => 1,
        'certificate_size' => 'Landscape',
        'paper_size'       => 'A4',
        'certificate_font' => '',
        'title'            => __('Enter your title', 'qsm-certificate'),
        'content'          => __('Enter your content', 'qsm-certificate'),
        'logo'             => '',
        'logo_style'       => '',
        'background'       => '',
        'dpi'              => 100,
    );

    $certificate_settings = wp_parse_args( $certificate_settings, $certificate_defaults );

    if ( 0 == $certificate_settings["enabled"] ) {
        $query  = "SELECT * FROM {$wpdb->prefix}mlw_certificate_template";
        // Only load a specific template when template_id > 0.
        // When template_id == 0, we intentionally skip loading templates to allow certificate_settings-based generation.
        if ( (int) $template_id > 0 ) {
            $query  .= ' WHERE id = %d';
            $templates = $wpdb->get_results( $wpdb->prepare( $query, (int) $template_id ), ARRAY_A );
        } else {
            $templates = array();
        }
        if ( ! empty( $templates ) ) {
            $tpl      = $templates[0];
            $tpl_id   = (int) $tpl['id'];
            $tpl_data = maybe_unserialize( $tpl['certificate_data'] );
            if ( is_array( $tpl_data ) && ! empty( $tpl_data['content'] ) ) {
                $tpl_data['content'] = wp_unslash( $tpl_data['content'] );
                if ( isset( $tpl_data['font'] ) ) {
                    $tpl_data['certificate_font'] = wp_unslash( $tpl_data['font'] );
                }
                if ( isset( $tpl_data['size'] ) ) {
                    $tpl_data['certificate_size'] = $tpl_data['size'];
                }
                if ( isset( $tpl_data['logoStyle'] ) ) {
                    $tpl_data['logo_style'] = $tpl_data['logoStyle'];
                }
                $exp_date = '';
                if ( $certificate_settings['never_expiry'] == 1 ) {
                    $exp_date = "";
                } else {
                    $expire_time = $certificate_settings['expiry_days']
                    ? (new DateTime())->modify('+' . intval($certificate_settings['expiry_days']) . ' days')->format('d-m-Y')
                    : (new DateTime($certificate_settings['expiry_date']))->format('d-m-Y');
                    $exp_date = str_replace('-', '', $expire_time);
                }
                $encoded_time_taken = md5( $quiz_results['time_taken'] );
                $filename           = "{$quiz_results['quiz_id']}-{$quiz_results['result_id']}-$encoded_time_taken-{$quiz_results['total_points']}-{$quiz_results['total_score']}-{$exp_date}.pdf";
                $filename           = apply_filters( 'qsm_certificate_template_file_name', $filename, $quiz_results['quiz_id'], $quiz_results['result_id'], $encoded_time_taken, $quiz_results['total_score'], $quiz_results['total_points'], $exp_date );
                $wp_upload      = wp_upload_dir();
                $pdf_file_name = $filename;
                $pdf_folder    = trailingslashit( $wp_upload['basedir'] ) . 'qsm-certificates/';
                if ( ! file_exists( $pdf_folder . $filename ) ) {
                    if ( ! is_dir( $pdf_folder ) ) {
                        wp_mkdir_p( $pdf_folder );
                    }
                    $html          = qsm_pdf_html_post_process_certificate( '', $tpl_data, $quiz_results );
                    $tpl_dpi = isset( $tpl_data['dpi'] ) ? (int) $tpl_data['dpi'] : 100;
                    $resource_result = qsm_certificate_prepare_rendering_resources( $tpl_dpi );
                    $dpi_to_use = $resource_result['final_dpi'];
                    
                    // Log if fallback was applied.
                    if ( $resource_result['fallback_applied'] ) {
                        error_log(
                            sprintf('QSM Certificate Template: DPI reduced from %s to %s due to server constraints.', $tpl_dpi, $dpi_to_use)
                        );
                    }
                    
                    try {
                        $options = new Options();
                        $options->set('isHtml5ParserEnabled', true);
                        $options->set('isFontSubsettingEnabled', true);
                        $options->set('isRemoteEnabled', true);
                        $options->set('dpi', $dpi_to_use);
                        $dompdf = new Dompdf($options);
                        $size_key      = isset( $tpl_data['certificate_size'] ) ? $tpl_data['certificate_size'] : ( isset( $tpl_data['size'] ) ? $tpl_data['size'] : 'Landscape' );
                        $orientation   = strtolower( $size_key ) === 'portrait' ? 'Portrait' : 'Landscape';
                        $paper_size    = isset( $certificate_settings['paper_size'] ) ? $certificate_settings['paper_size'] : 'A4';
                        $dompdf->setPaper( $paper_size, $orientation );
                        $dompdf->loadHtml( $html );
                        $dompdf->render();
                        $pdf_output = $dompdf->output();
                        file_put_contents( $pdf_folder . $pdf_file_name, $pdf_output );
                    } catch (Exception $e) {
                        error_log('QSM Certificate: Dompdf rendering failed for template: ' . $e->getMessage());
                        return false;
                    }
                }
                return $return_file ? urlencode( $pdf_file_name ) : true;
            }
        }

        if ( $certificate_settings['never_expiry'] == 1 ) {
            $exp_date = "";
        } else {
            $expire_time = $certificate_settings['expiry_days']
            ? (new DateTime())->modify('+' . intval($certificate_settings['expiry_days']) . ' days')->format('d-m-Y')
            : (new DateTime($certificate_settings['expiry_date']))->format('d-m-Y');
            $exp_date = str_replace('-', '', $expire_time);
        }
        $encoded_time_taken = md5( $quiz_results['time_taken'] );
        $filename = "{$quiz_results['quiz_id']}-{$quiz_results['result_id']}-$encoded_time_taken-{$quiz_results['total_points']}-{$quiz_results['total_score']}-{$exp_date}.pdf";
        $filename = apply_filters('qsm_certificate_file_name', $filename, $quiz_results['quiz_id'], $quiz_results['result_id'], $encoded_time_taken, $quiz_results['total_score'], $quiz_results['total_points'], $exp_date);
        $isSVG = function ( $path ) {
        return pathinfo( $path, PATHINFO_EXTENSION ) === 'svg';
	};
	// If the certificate does not already exist
    $wp_upload = wp_upload_dir();
    $pdf_file_name = $filename;

	if ( ! file_exists( $wp_upload['basedir'] . "/qsm-certificates/$filename" ) ) {
        $pdf_folder = $wp_upload['basedir'] . '/qsm-certificates/';
        if ( ! is_dir( $pdf_folder ) ) {
            wp_mkdir_p( $pdf_folder );
        }
        $pdf_url = $wp_upload['baseurl']  . '/qsm-certificates/';

        //generate result page html
        $html = qsm_pdf_html_post_process_certificate( $html = "", $certificate_settings, $quiz_results );
        $cert_dpi = isset( $certificate_settings['dpi'] ) ? (int) $certificate_settings['dpi'] : 100;
        $resource_result = qsm_certificate_prepare_rendering_resources( $cert_dpi );
        $dpi_to_use = $resource_result['final_dpi'];
        
        // Log if fallback was applied.
        if ( $resource_result['fallback_applied'] ) {
            error_log(
                'QSM Certificate Default: DPI reduced from ' . $cert_dpi . ' to ' . $dpi_to_use .
                ' due to server constraints.'
            );
        }
        
        //initialize dompdf
        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isFontSubsettingEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('dpi', $dpi_to_use);
            $dompdf = new Dompdf($options);
            $certificate_size = "Landscape";
            if ( isset( $certificate_settings['certificate_size'] ) && 'Portrait' == $certificate_settings['certificate_size'] ) {
                $certificate_size = "Portrait";
            } else {
                $certificate_size = "Landscape";
            }
            $paper_size = isset( $certificate_settings['paper_size'] ) ? $certificate_settings['paper_size'] : 'A4';
            $dompdf->setPaper( $paper_size, $certificate_size );
            $dompdf->loadHtml( $html );
            $dompdf->render();
            $pdf_output = $dompdf->output();
            file_put_contents( $pdf_folder.$pdf_file_name, $pdf_output );
            $file_nonce = wp_create_nonce( 'pdf_file' );
            $response = array(
                'file' => $pdf_file_name,
            );
        } catch (Exception $e) {
            error_log('QSM Certificate: Dompdf rendering failed for default: ' . $e->getMessage());
            $response = array(
                'status' => false,
            );
        }
    } else {
        $response = array(
			'status' => false,
        );
    }
    return $return_file ? urlencode( $pdf_file_name ) : true;
  }
}

function qsm_pdf_html_post_process_certificate( $html, $settings = array(), $quiz_results = array() ) {
	global $mlwQuizMasterNext;
    $upload_dir   = wp_upload_dir();
    $logo = "";
    if ( ! empty( $settings['logo'] ) ) {
        $logo_path = str_replace( $upload_dir['url'], $upload_dir['path'], $settings['logo'] );
        $logo_url     = base64_encode( file_get_contents( $logo_path ) );
        $extension    = pathinfo( $settings['logo'], PATHINFO_EXTENSION );
        $logo         = isset( $settings['logo'] ) ? "<img src='data:image/{$extension};base64,{$logo_url}'><br/>" : "";
    }
	if ( isset( $settings['content'] ) ) {
		$content = apply_filters( 'qsm_addon_certificate_content_filter', $settings["content"], $quiz_results );
        $content = htmlspecialchars_decode( $content, ENT_QUOTES ) ;
	}
    $certificate_title   = $settings["title"];
    $certificate_title   = $certificate_title;
    $certificate_title   = stripslashes( $certificate_title );
    $background = "";
    $background_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $settings['background'] );
    if ( ! empty($settings['background'] ) ) {
        if ( file_exists( $background_path ) ) {
        $background_url = base64_encode( file_get_contents( $background_path ) );
        $background_extension = pathinfo( $settings["background"], PATHINFO_EXTENSION );
        $background          = isset( $settings["background"] ) ? "data:image/{$background_extension};base64,{$background_url}" : "";
    } else {
        $background = $background_path;
    }
    }
    $logo_style = isset( $settings['logo_style'] ) ? $settings["logo_style"] : "";
	$html_top        = '<html style = "margin:0;padding:0;"><head><title>'.$certificate_title.'</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><style>';
    if ( empty( $settings['certificate_font'] ) || 'dejavusans' == $settings['certificate_font'] ) {
        $html_top   .= 'body{ font-family: "DejaVu Sans", sans-serif; text-align:left; font-size:12pt;}img{min-width:150pt !important;margin-top:22pt;}';
    } else {
        $html_top   .= trim( htmlspecialchars_decode( $settings["certificate_font"], ENT_QUOTES ) );
    }
	$html_top       .= '</style></head><body style="background-image: url('.$background.');background-size:100% 100%;background-repeat:no-repeat;background-position:center center;padding:15pt; ">';
	$html_bottom = '<div style="' . $logo_style . '"> ' . $logo
    . ( ! empty($certificate_title) ? '<h1 style="text-align:center;margin-top:60pt;font-weight:700;">' . $certificate_title . '</h1>' : '')
    . '<div style="text-align:center;vertical-align:middle;justify-content: center;">' . $content
    . '</div></body></html>';
    $html            = $html_top . $html . $html_bottom;
    return $html;
}

// Check If URL is Exists or not
function qsm_does_url_exits( $url ) {
	$ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ( 200 == $code ) {
        $status = true;
    } else {
        $status = false;
    }
    curl_close($ch);
    return $status;
}

function qsm_certificate_variable_expiry_date( $content, $mlw_quiz_array ) {
    global $mlwQuizMasterNext;

    $quiz_options      = $mlwQuizMasterNext->quiz_settings->get_quiz_options();
    $qsm_quiz_settings = maybe_unserialize( $quiz_options->quiz_settings );
    $expiry_date = '';
    $certificate_settings = isset( $qsm_quiz_settings['certificate_settings'] )
        ? maybe_unserialize( $qsm_quiz_settings['certificate_settings'] )
        : [];

    $expiry_days_input = isset( $certificate_settings['expiry_days'] )
        ? $certificate_settings['expiry_days']
        : '';
    $expiry_date_input = isset( $certificate_settings['expiry_date'] )
        ? $certificate_settings['expiry_date']
        : '';
        $expire_time = "";
    if ( $certificate_settings['never_expiry'] != 1 ) {
    if ( is_numeric( $expiry_days_input ) ) {
        $expiry_date = (new DateTime())->modify('+' . intval( $expiry_days_input ) . ' days')->format('F j, Y');
    } else {
        $expiry_date = (new DateTime($expiry_date_input))->format('F j, Y');
    }
    }

    $content = str_replace( '%EXPIRY_DATE%', $expiry_date, $content );

    return $content;
}

/**
 * PHPMailer hook to add certificate attachments.
 */
function qsm_certificate_add_attachments_to_phpmailer( $phpmailer ) {
    global $qsm_certificate_mail_attachments;
    if ( empty( $qsm_certificate_mail_attachments ) || ! is_array( $qsm_certificate_mail_attachments ) ) {
        return;
    }

    $seen = array();
    foreach ( $qsm_certificate_mail_attachments as $att ) {
        if ( empty( $att['path'] ) || ! file_exists( $att['path'] ) || isset( $seen[ $att['path'] ] ) ) {
            continue;
        }
        $seen[ $att['path'] ] = true;
        $phpmailer->addAttachment( $att['path'], isset( $att['name'] ) ? $att['name'] : basename( $att['path'] ) );
    }

    $qsm_certificate_mail_attachments = array();
    remove_action( 'phpmailer_init', 'qsm_certificate_add_attachments_to_phpmailer', 10 );
}

function qsm_certificate_id_variable( $content, $mlw_quiz_array ) {
	global $mlwQuizMasterNext;
    global $wpdb;

    if ( isset( $mlw_quiz_array['quiz_id'] ) && ! empty( $mlw_quiz_array['quiz_id'] ) ) {
        $quiz_id = intval( $mlw_quiz_array['quiz_id'] );

        $unique_id = $wpdb->get_row( $wpdb->prepare(
            "SELECT unique_id FROM {$wpdb->prefix}mlw_results WHERE quiz_id = %d ORDER BY result_id DESC LIMIT 1",
            $quiz_id
        ) );

	$quiz_options        = $mlwQuizMasterNext->quiz_settings->get_quiz_options();
	$qsm_quiz_settings   = maybe_unserialize( $quiz_options->quiz_settings );

    $certificate_settings = isset($qsm_quiz_settings['certificate_settings']) ? maybe_unserialize($qsm_quiz_settings['certificate_settings'] ) : [];
	$certificate_id = isset($certificate_settings['certificate_id']) ? $certificate_settings['certificate_id'] . $unique_id->unique_id : "";
	$content = str_replace( '%CERTIFICATE_ID%', $certificate_id, $content );
    }
	return $content;
}

function qsm_certificate_localize_script_load() {
    global $wpdb;
    if ( isset($_GET['page']) && $_GET['page'] === 'mlw_quiz_options' && isset($_GET['tab']) && $_GET['tab'] === 'certificate' ) {
        $quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
        wp_enqueue_script( 
            'qsm_certificate_admin_script', 
            QSM_CERTIFICATE_JS_URL . '/qsm-certificate-admin.js', 
            array('jquery'), 
            QSM_CERTIFICATE_VERSION, 
            true
        );

        wp_localize_script(
            'qsm_certificate_admin_script',
            'qsm_certificate_pro_obj',
            array(
                'preview'         => esc_html__('Preview', 'qsm-certificate'),
                'import_template' => esc_html__('Import Template', 'qsm-certificate'),
                'save_template' => esc_html__('Save Template', 'qsm-certificate'),
                'quiz_id' => $quiz_id,
                'tmpl_confirm_msg' => esc_html__('Are you sure you want to delete this template?', 'qsm-certificate'),
                'failed_msg' => esc_html__('Failed to delete template.', 'qsm-certificate'),
                'server_error_msg' => esc_html__('Server error. Please try again.', 'qsm-certificate'),
            )
        );
    }
}

add_action( 'admin_enqueue_scripts', 'qsm_certificate_localize_script_load' );


function qsm_certificate_expiry_check_form( $settings, $cert_id ) {
    ob_start();
    wp_enqueue_style('qsm_certificate_front_style', QSM_CERTIFICATE_URL . 'css/qsm-certificate-front.css', array(), QSM_CERTIFICATE_VERSION ); 
    wp_enqueue_script( 'qsm_certificate_front_script', QSM_CERTIFICATE_JS_URL . '/qsm-certificate-front.js', array( 'jquery' ), QSM_CERTIFICATE_VERSION, true );
    $certificate_settings = get_option( 'certificate_settings', array() );
	$error_msgs           = wp_parse_args(
		$certificate_settings,
		array(
			'certificate_id_err_msg_blank_txt' => __( 'Please enter a certificate ID.', 'qsm-certificate' ),
		)
	);
    wp_localize_script(
        'qsm_certificate_front_script',
        'qsm_certificate_ajax_object',
        array(
            'site_url' => site_url(),
            'enter_certificate_id' => $error_msgs['certificate_id_err_msg_blank_txt'],
            'ajaxurl'  => admin_url( 'admin-ajax.php' ),
        )
    );
    ?>
    <form action="" method="post" id="qsm-certificate-expiry-check-form">
        <label for="qsm-certificate-id"><?php esc_html_e( 'Certificate ID*', 'qsm-certificate' ); ?></label>
        <?php wp_nonce_field('qsm_certificate_expiry_check', 'qsm_certificate_expiry_check_nonce'); ?>
        <input type="text" id="qsm-certificate-id" name="qsm_certificate_id">
        <button type="submit" class="qsm-certificate-expiry-check-button qmn_btn">
            <?php esc_html_e( 'Check Expiry', 'qsm-certificate' ); ?>
        </button>
    </form>
    <span id="qsm-certificate-validation-message"></span>
    <?php
    return ob_get_clean();
}

add_shortcode( 'quiz_expiry_check', 'qsm_certificate_expiry_check_form' );

/**
 * Handles certificate template content.
 */
function qsm_certificate_template_content() {
    global $mlwQuizMasterNext, $wp_filesystem;

    if ( ! function_exists('WP_Filesystem') ) {
        require_once ABSPATH . '/wp-admin/includes/file.php';
    }
    WP_Filesystem();

    if ( ! empty($_GET['tab']) && 'certificate' !== $_GET['tab']) return;

    $quiz_id = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;
    $readme_file = QSM_CERTIFICATE_PATH . '/data/certificate-templates.json';
    $local_templates = file_exists($readme_file) ? json_decode($wp_filesystem->get_contents($readme_file), true) : array();

    $remote_response = wp_remote_get(QSM_CERTIFICATE_URL . 'data/certificate-templates.json', array(
		'sslverify' => true,
		'timeout'   => 15,
	));
    $certificate_template_from_script = ! is_wp_error($remote_response) ? json_decode(wp_remote_retrieve_body($remote_response), true) : $local_templates;
    $certificate_template_from_script = empty($certificate_template_from_script) ? $local_templates : $certificate_template_from_script;

    wp_enqueue_script('qsm_certificate_admin_script', QSM_CERTIFICATE_JS_URL . '/qsm-certificate-admin.js', array( 'jquery' ), QSM_CERTIFICATE_VERSION, true);

    $js_data = array(
        'quizID'          => $quiz_id,
        'script_tmpl'     => $certificate_template_from_script,
        'qsm_tmpl_bg_url' => QSM_CERTIFICATE_URL . 'assets/'
    );
    wp_localize_script('qsm_certificate_admin_script', 'qsmCertificateObject', $js_data);

    if ( function_exists('qsm_certificate_popups_for_templates') ) {
        qsm_certificate_popups_for_templates( $certificate_template_from_script, 'certificate' );
    }
}

/**
 * Generates preview popup for certificates.
 */
function qsm_preview_popup_function() {
    $html  = '<div class="qsm-popup qsm-popup-slide" id="qsm-certificate-show-popup" aria-hidden="false">';
    $html .= '<div class="qsm-popup__overlay" tabindex="-1" data-micromodal-close>';
    $html .= '<div class="qsm-popup__container qsm-certificate-popup" role="dialog" aria-modal="true" aria-labelledby="modal-3-title">';
    $html .= '<header class="qsm-popup__header">';
    $html .= '<div class="qsm-certificate-preview-page-template-header-left">';
    $html .= '<img class="qsm-certificate-preview-page-template-header-image" src="' . esc_url(QSM_CERTIFICATE_URL . 'assets/icon-200x200.png') . '" alt="icon-200x200.png"/>';
    $html .= '<h2 class="qsm-popup__title" id="qsm-certificate-preview-page-templates-title">';
    $html .= esc_html__('Certificate Preview', 'qsm-certificate');
    $html .= '</h2>';
    $html .= '</div>';
    $html .= '<div class="qsm-certificate-preview-page-template-header-right">';
    $html .= '<div class="qsm-certificate-preview-page-template-header">';
    $html .= '</div>';
    $html .= '<a class="qsm-popup__close" aria-label="Close modal" data-micromodal-close></a>';
    $html .= '</div>';
    $html .= '<div class="qsm-title-overlay"></div>';
    $html .= '</header>';
    $html .= '<main id="qsm-certificate-show-changes">';
    $html .= '</main>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    echo $html;
}
function qsm_certificate_preview_allow_br_tags($init) {
    if ( isset($_GET['tab']) && $_GET['tab'] === 'certificate' ) {
        $init['wpautop'] = false;
    }
    return $init;
}
add_filter( 'wp_editor_settings', 'qsm_certificate_preview_allow_br_tags' );

function qsm_certificate_attach_certificate_file( $content, $quiz_array ) {
    global $mlwQuizMasterNext;

    $quiz_options    = $mlwQuizMasterNext->quiz_settings->get_quiz_options();
    $qsm_quiz_settings = maybe_unserialize($quiz_options->quiz_settings);
    $settings       = isset($qsm_quiz_settings['certificate_settings']) ? maybe_unserialize($qsm_quiz_settings['certificate_settings']) : [];

    if ( isset($settings['enabled']) && 0 === $settings['enabled'] ) {
        $content = qsm_handle_certificate_attachment($content, $quiz_array);
    }

    return $content;
}
function qsm_certificate_contact_x_variable($content, $results_array){
	preg_match_all( '~%CONTACT_(.*?)%~i', $content, $matches );
	for ( $i = 0; $i < count( $matches[0] ); $i++ ) {
		$contact_key = $matches[1][ $i ];
		if ( is_numeric( $contact_key ) && intval( $contact_key ) > 0 ) {
			$contact_index = intval( $contact_key ) - 1;

			if ( isset( $results_array['contact'][ $contact_index ]['value'] ) ) {
				$content = str_replace( '%CONTACT_' . $contact_key . '%', $results_array['contact'][ $contact_index ]['value'], $content );
			} else {
				$content = str_replace( '%CONTACT_' . $contact_key . '%', '', $content );
			}
		} else {
			$content = str_replace( '%CONTACT_' . $contact_key . '%', '', $content );
		}
	}
	return $content;
}

function qsm_handle_certificate_attachment( $content, $quiz_array ) {
    $generic_placeholder = '%CERTIFICATE_ATTACHMENT_PDF%';
    $message_text        = __( 'Your certificate is attached to this email.', 'qsm-certificate' );
    $attachments         = array();
    $upload              = wp_upload_dir();

    if ( preg_match_all( '/%CERTIFICATE_ATTACHMENT_PDF_(\d+)%/i', $content, $matches ) ) {
        $ids = array_unique( array_map( 'intval', $matches[1] ) );
        foreach ( $ids as $tpl_id ) {
            if ( $tpl_id <= 0 ) {
                continue;
            }
            $file = qsm_addon_certificate_generate_certificate( $quiz_array, $tpl_id, true );
            if ( ! empty( $file ) && false !== $file ) {
                $path = trailingslashit( $upload['basedir'] ) . 'qsm-certificates/' . $file;
                if ( file_exists( $path ) && is_readable( $path ) ) {
                    $attachments[] = array( 'path' => $path, 'name' => 'certificate-' . $tpl_id . '.pdf' );
                    $content       = str_replace( '%CERTIFICATE_ATTACHMENT_PDF_' . $tpl_id . '%', $message_text, $content );
                } else {
                    $content = str_replace( '%CERTIFICATE_ATTACHMENT_PDF_' . $tpl_id . '%', '', $content );
                }
            } else {
                $content = str_replace( '%CERTIFICATE_ATTACHMENT_PDF_' . $tpl_id . '%', '', $content );
            }
        }
    }

    if ( false !== strpos( $content, $generic_placeholder ) ) {
        $file = qsm_addon_certificate_generate_certificate( $quiz_array, 0, true );
        if ( ! empty( $file ) && false !== $file ) {
            $path = trailingslashit( $upload['basedir'] ) . 'qsm-certificates/' . $file;
            if ( file_exists( $path ) && is_readable( $path ) ) {
                $attachments[] = array( 'path' => $path, 'name' => 'certificate.pdf' );
                $content       = str_replace( $generic_placeholder, $message_text, $content );
            } else {
                $content = str_replace( $generic_placeholder, '', $content );
            }
        } else {
            $content = str_replace( $generic_placeholder, '', $content );
        }
    }

    if ( ! empty( $attachments ) ) {
        global $qsm_certificate_mail_attachments;
        if ( ! is_array( $qsm_certificate_mail_attachments ) ) {
            $qsm_certificate_mail_attachments = array();
        }
        $qsm_certificate_mail_attachments = array_merge( $qsm_certificate_mail_attachments, $attachments );

        if ( ! has_action( 'phpmailer_init', 'qsm_certificate_add_attachments_to_phpmailer' ) ) {
            add_action( 'phpmailer_init', 'qsm_certificate_add_attachments_to_phpmailer', 10, 1 );
        }
    }

    return $content;
}

