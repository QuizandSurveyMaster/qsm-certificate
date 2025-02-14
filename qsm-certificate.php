<?php
/**
 * Plugin Name: QSM - Certificate
 * Plugin URI: http://quizandsurveymaster.com
 * Description: Adds the ability to give certificates to quiz/survey takers
 * Author: QSM Team
 * Author URI: http://quizandsurveymaster.com
 * Version: 1.3.2
 *
 * @author QSM Team
 * @version 1.3.2
 * @package QSM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * This class is the main class of the plugin
 *
 * When loaded, it loads the included plugin files and add functions to hooks or filters. The class also handles the admin menu
 *
 * @since 0.1.0
 */
class QSM_Certificate {

	/**
	 * Version Number
	 *
	 * @var string
	 * @since 0.1.0
	 */
	public $version = '1.3.2';

	/**
	 * Main Construct Function
	 *
	 * Call functions within class
	 *
	 * @since 0.1.0
	 * @uses QSM_Certificate::load_dependencies() Loads required filed
	 * @uses QSM_Certificate::add_hooks() Adds actions to hooks and filters
	 * @return void
	 */
	function __construct() {
		$this->load_dependencies();
		$this->add_hooks();
		$this->check_license();
		define( 'QSM_CERTIFICATE_VERSION', $this->version );
		define( 'QSM_CERTIFICATE_URL', plugin_dir_url( __FILE__ ) );
		define( 'QSM_CERTIFICATE_JS_URL', QSM_CERTIFICATE_URL . 'js' );
		define( 'QSM_CERTIFICATE_CSS_URL', QSM_CERTIFICATE_URL . 'css' );
	}

	/**
	 * Load File Dependencies
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function load_dependencies() {
		include 'php/addon-settings-tab-content.php';
		include 'php/generate-certificate.php';
		include 'php/results-details-tab-content.php';
		include 'php/quiz-settings-tab-content.php';
		include 'php/variables.php';
	}

	/**
	 * Add Hooks
	 *
	 * Adds functions to relavent hooks and filters
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'admin_init', 'qsm_addon_certificate_register_quiz_settings_tabs' );
		add_action( 'admin_init', 'qsm_addon_certificate_register_results_details_tabs' );
		add_action( 'admin_init', 'qsm_addon_certificate_register_addon_settings_tabs' );
		add_action( 'admin_init', 'qsm_addon_qsm_certificate_textdomain' );
		add_action( 'admin_init', 'qsm_addon_create_upload_dir' );
		add_filter( 'mlw_qmn_template_variable_results_page', 'qsm_addon_certificate_variable', 10, 2 );
		add_filter( 'qmn_email_template_variable_results', 'qsm_addon_certificate_variable', 10, 2 );
		add_action( 'wp_ajax_qsm_addon_certificate_expiry_check', 'qsm_addon_certificate_expiry_check' );
		add_action('wp_ajax_nopriv_qsm_addon_certificate_expiry_check', 'qsm_addon_certificate_expiry_check');
		add_filter( 'mlw_qmn_template_variable_results_page', 'qsm_addon_certificate_variable', 10, 2 );
		add_filter( 'qmn_email_template_variable_results', 'qsm_addon_certificate_variable', 10, 2 );


		// Needed until the new variable system is finished.
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_point_score', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_average_point', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_amount_correct', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_total_questions', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_correct_score', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_quiz_name', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_user_name', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_user_business', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_user_phone', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_user_email', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_date', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'mlw_qmn_variable_date_taken', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'qsm_certificate_variable_expiry_date', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', 'qsm_certificate_id_variable', 10, 2 );
		add_filter( 'qsm_addon_certificate_content_filter', array( $this, 'qsm_certificate_user_full_name' ), 10, 2 );
		add_filter( 'upload_mimes', array( $this, 'add_ttf_upload_mimes' ) );
	}

	/**
	 * Display full name of user using %FULL_NAME%.
	 *
	 * @since 1.0.8
	 * @param string $content Certificate content.
	 * @param array  $mlw_quiz_array Quiz Array.
	 * @return string
	 */
	public function qsm_certificate_user_full_name( $content, $mlw_quiz_array ) {
		if ( false !== strpos( $content, '%FULL_NAME%' ) ) {
			$full_name = '';
			$user_id = isset( $mlw_quiz_array['user_id'] ) ? $mlw_quiz_array['user_id'] : 0;
			$current_user_id = get_current_user_id();

			if ( is_admin() && $user_id != $current_user_id ) {
				$current_user_id = $user_id;
			}

			$user = get_user_by( 'ID', $current_user_id );

			if ( $user ) {
				$firstname = get_user_meta( $user->ID, 'first_name', true );
				$lastname = get_user_meta( $user->ID, 'last_name', true );

				if ( ! empty( $firstname ) && ! empty( $lastname ) ) {
					$full_name = $firstname . ' ' . $lastname;
				} else {
					$full_name = $user->display_name;
				}
			}

			$content = str_replace( '%FULL_NAME%', $full_name, $content );
		}
		return $content;
	}

	/**
	 * Checks license
	 *
	 * Checks to see if license is active and, if so, checks for updates
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function check_license() {

		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {

			// load our custom updater.
			include 'php/EDD_SL_Plugin_Updater.php';
		}

		// retrieve our license key from the DB.
		$certificate_data = get_option( 'qsm_addon_certificate_settings', '' );
		if ( isset( $certificate_data['license_key'] ) ) {
			$license_key = trim( $certificate_data['license_key'] );
		} else {
			$license_key = '';
		}

		// setup the updater.
		$edd_updater = new EDD_SL_Plugin_Updater(
			'http://quizandsurveymaster.com',
			__FILE__,
			array(
				'version'   => $this->version, // current version number.
				'license'   => $license_key,   // license key (used get_option above to retrieve from DB).
				'item_name' => 'Certificate',  // name of this plugin.
				'author'    => 'Frank Corso',  // author of this plugin.
			)
		);
	}

	public function add_ttf_upload_mimes( $existing_mimes ) {
		$existing_mimes['ttf'] = 'application/x-font-ttf';
		return $existing_mimes;
	}
}

/**
 * Loads the addon if QSM is installed and activated
 *
 * @since 0.1.0
 * @return void
 */
function qsm_addon_qsm_certificate_load() {
	// Make sure QSM is active.
	if ( class_exists( 'MLWQuizMasterNext' ) ) {
		$qsm_certificate = new QSM_Certificate();
	} else {
		add_action( 'admin_notices', 'qsm_addon_qsm_certificate_missing_qsm' );
	}
}
add_action( 'plugins_loaded', 'qsm_addon_qsm_certificate_load' );

/**
 * Display notice if Quiz And Survey Master isn't installed
 *
 * @since  0.1.0
 * @return void
 */
function qsm_addon_qsm_certificate_missing_qsm() {
	echo '<div class="error"><p>QSM - Certificate requires Quiz And Survey Master. Please install and activate the Quiz And Survey Master plugin.</p></div>';
}

/**
 * Load plugin text domain
 *
 * @since  0.1.0
 */
function qsm_addon_qsm_certificate_textdomain() {
	load_plugin_textdomain( 'qsm-certificate', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}

/**
 * Creates qsm-certificates directory under uploads folder
 */
function qsm_addon_create_upload_dir() {
	$upload_dir           = wp_upload_dir();
	$certificates_dirname = $upload_dir['basedir'] . '/qsm-certificates';
	if ( ! file_exists( $certificates_dirname ) ) {
		wp_mkdir_p( $certificates_dirname );
	}

	migrate_old_certificates( $certificates_dirname );
}

/**
 * Migration script to bring old certificates into new uploads folder
 *
 * @param string $certificates_dirname certificates dirname.
 */
function migrate_old_certificates( $certificates_dirname ) {
    $plugins_path = dirname( plugin_dir_path( __FILE__ ) );

    $plugins = scandir( $plugins_path );

    foreach ( $plugins as $plugin ) {
        if ( in_array($plugin, array( '.', '..' )) ) {
            continue;
        }
        if ( 0 === strpos( $plugin, 'qsm-certificate' ) && 'qsm-certificate' !== $plugin ) {
            $certificates_path = $plugins_path . '/' . $plugin . '/certificates';

            if ( is_dir($certificates_path) ) {
                $certificates = scandir( $certificates_path );

                foreach ( $certificates as $certificate ) {
                    if ( in_array($certificate, array( '.', '..' )) ) {
                        continue;
                    }
                    if ( strpos($certificate, 'pdf') !== false ) {
                        $source      = $certificates_path . '/' . $certificate;
                        $destination = $certificates_dirname . '/' . $certificate;

                        if ( file_exists($source) ) {
                            rename( $source, $destination );
                        }
                    }
                }
            }
        }
    }
}

function qsm_addon_certificate_expiry_check() {
    global $mlwQuizMasterNext;

    $certificate_id = isset($_POST['certificate_id']) ? sanitize_text_field($_POST['certificate_id']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

    $response = array();

    if ( ! empty($certificate_id) ) {
        $unique_key = $certificate_id;
        $resultant_string = substr($unique_key, 0, -13);
        $last_eight_characters = substr($resultant_string, -8);
		$last_characters = intval($last_eight_characters);

        $date = date('Y-m-d');
        $current = str_replace('-', '', $date);
		$current_date = intval($current);

		$expiry_date = DateTime::createFromFormat('Ymd', $last_characters);
		$expiry_date_formatted = $expiry_date->format('d F Y'); 

        if ( $current_date <= $last_characters ) {
            $response['message'] = '<span style="color: green;"><span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> ' . __('License Valid upto ', 'qsm-certificate') . $expiry_date_formatted;
            wp_send_json_success($response);
        } else {
            $response['message'] = '<span style="color: red;"><span class="dashicons dashicons-no" style="vertical-align: middle;"></span> ' . __('License Expired on ', 'qsm-certificate') . $expiry_date_formatted;
            wp_send_json_success($response);
        }
    } else {
        $response['message'] = '<span style="color: red;"><span class="dashicons dashicons-no" style="vertical-align: middle;"></span> ' . __('Certificate ID is missing', 'qsm-certificate');
        wp_send_json_error($response);
    }

    wp_die();
}
