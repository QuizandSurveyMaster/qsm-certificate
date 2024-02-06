<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Dompdf\Dompdf;
use Dompdf\Options;
// reference the Dompdf namespace

/**
 * Generates the certificate
 *
 * @since 0.1.0
 * @param array $quiz_results The array built while scoring the quiz.
 * @param bool $return_file Whether the function should return the filepath or a boolean.
 * @return bool|string Returns false if file fails to generate. If $return_file is false, then the function will return true if pdf generation is success. If $return_file is set to true, the function will return the file's path
 */
function qsm_addon_certificate_generate_certificate( $quiz_results, $return_file = false ) {
    if( !class_exists( 'Dompdf\Autoloader' ) ) {
        require_once(plugin_dir_path(__FILE__) . '../dompdf/autoload.inc.php');
    }
	global $wpdb;
	global $mlwQuizMasterNext;
	// Load the settings.
	$certificate_settings = $mlwQuizMasterNext->pluginHelper->get_quiz_setting( "certificate_settings" );
	if ( ! is_array( $certificate_settings ) ) {
		$quiz_options = $wpdb->get_row( $wpdb->prepare( "SELECT certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE quiz_id=%d LIMIT 1", $quiz_results["quiz_id"] ) );
		// Loads the certificate options vVariables.
		if ( is_serialized( $quiz_options->certificate_template ) && is_array( @unserialize( $quiz_options->certificate_template ) ) ) {
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
        'certificate_font' => '',
        'title'            => __('Enter your title', 'qsm-certificate'),
        'content'          => __('Enter your content', 'qsm-certificate'),
        'logo'             => '',
        'logo_style'       => '',
        'background'       => '',
    );
 
    $certificate_settings = wp_parse_args( $certificate_settings, $certificate_defaults );

    // If certificate is enabled
    if ( 0 == $certificate_settings["enabled"] ) {
        $encoded_time_taken = md5( $quiz_results['time_taken'] ); 
        $filename = "{$quiz_results['quiz_id']}-{$quiz_results['timer']}-$encoded_time_taken-{$quiz_results['total_points']}-{$quiz_results['total_score']}.pdf";    
        $filename = apply_filters('qsm_certificate_file_name', $filename, $quiz_results['quiz_id'], $quiz_results['timer'], $encoded_time_taken, $quiz_results['total_score'], $quiz_results['total_points']);
        $isSVG = function ( $path ) {
        return pathinfo( $path, PATHINFO_EXTENSION ) === 'svg';
	};
	// If the certificate does not already exist
    $wp_upload = wp_upload_dir();
	if ( ! file_exists( $wp_upload['basedir'] . "/qsm-certificates/.$filename" ) ) {
        $pdf_folder = $wp_upload['basedir'] . '/qsm-certificates/';
        if ( ! is_dir($pdf_folder) ) {
            mkdir($pdf_folder, 0755);
        }
        $pdf_url = $wp_upload['baseurl']  . '/qsm-certificates/';
        $pdf_file_name = $filename;
        //generate result page html
        $html = qsm_pdf_html_post_process_certificate( $html = "", $certificate_settings, $quiz_results );
        //initialize dompdf
        $dompdf = new Dompdf();
        $certificate_size = "Landscape";
        if ( isset( $certificate_settings['certificate_size'] ) && 'Portrait' == $certificate_settings['certificate_size'] ) {
            $certificate_size = "Portrait";
        } else {
            $certificate_size = "Landscape";
        }
        $dompdf->setPaper( 'A4',$certificate_size );
        $dompdf->set_option( 'isHtml5ParserEnabled', true );
        $dompdf->set_option( 'isFontSubsettingEnabled', true );
        $dompdf->set_option( 'isRemoteEnabled', true );
        $dompdf->loadHtml( $html );
        $dompdf->render();
        $pdf_output = $dompdf->output();
        file_put_contents( $pdf_folder.$pdf_file_name, $pdf_output );
        $file_nonce = wp_create_nonce( 'pdf_file' );
        $response = array(
            'file' => $pdf_file_name,
        );
    } else {
        $response = array(
			'status' => false,
        );
    }
    if ( $pdf_file_name ) {
        return urlencode( $pdf_file_name );
    } else {
        return true;
    }
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
    $certificate_title   = nl2br( $certificate_title, false );
    $certificate_title   = stripslashes( $certificate_title );
    $background = "";
    $background_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $settings['background'] );
    if ( ! empty($settings['background'] ) ) {
        $background_url = base64_encode( file_get_contents( $background_path ) );
        $background_extension = pathinfo( $settings["background"], PATHINFO_EXTENSION );
        $background          = isset( $settings["background"] ) ? "data:image/{$background_extension};base64,{$background_url}" : "";
    }
    $logo_style = isset( $settings['logo_style'] ) ? $settings["logo_style"] : "";
	$html_top        = '<html style = "margin:0;padding:0;"><head><title>'.$certificate_title.'</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><style>';
    if ( empty( $settings['certificate_font'] ) || 'dejavusans' == $settings['certificate_font'] ) {
        $html_top   .= 'body{ font-family: "DejaVu Sans", sans-serif; text-align:left;}img{min-width:200px !important;margin-top:30px;}';
    } else {
        $html_top   .= trim( htmlspecialchars_decode( $settings["certificate_font"], ENT_QUOTES ) );
    }
	$html_top       .= '</style></head><body style="background-image: url('.$background.');background-size:100% 100%;background-repeat:no-repeat;background-position:center center;padding:20px; ">';
	$html_bottom     = '<div style='.$logo_style.'> '.$logo.'<h1 style="text-align:center;margin-top:80px;font-weight:700;">'.$certificate_title.'</h1><div style="text-align:center;vertical-align:middle;justify-content: center;font-size:16px;">'.nl2br($content).'</div></body></html>';
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



