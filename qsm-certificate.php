<?php
/**
 * Plugin Name: QSM - Certificate
 * Plugin URI: https://quizandsurveymaster.com
 * Description: Adds the ability to give certificates to quiz/survey takers
 * Author: QSM Team
 * Author URI: https://quizandsurveymaster.com
 * Version: 2.1.0
 *
 * @author QSM Team
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
	public $version = '2.1.0';

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
		define( 'QSM_CERTIFICATE_ITEM', 8241 );
		define( 'QSM_CERTIFICATE_VERSION', $this->version );
		define( 'QSM_CERTIFICATE_PATH', plugin_dir_path( __FILE__ ) );
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
		add_action( 'wp_ajax_qsm_certificate_validate_license', 'qsm_certificate_validate_license' );
		

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
		add_action('admin_footer', 'qsm_preview_popup_function');
		add_action('admin_footer', 'qsm_certificate_template_content');
		add_filter('qsm_addon_certificate_content_filter', 'qsm_certificate_attach_certificate_file', 10, 2);
        add_filter( 'qmn_email_template_variable_results', 'qsm_certificate_attach_certificate_file', 10, 2 );
		add_filter( 'qsm_text_variable_list_email', array( $this, 'qsm_certificate_show_variable' ), 10, 1 );
		add_filter( 'qsm_text_variable_list_result', array( $this, 'qsm_certificate_link_variable' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'qsm_certificate_template_create_table' ) );
		add_action( 'wp_ajax_qsm_addon_certificate_save_template', 'qsm_addon_certificate_save_template' );
		add_action( 'wp_ajax_nopriv_qsm_addon_certificate_save_template', 'qsm_addon_certificate_save_template' );
	}

	public function qsm_certificate_template_create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$certificate_template_table_name = $wpdb->prefix . 'mlw_certificate_template';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$certificate_template_table_name'" ) != $certificate_template_table_name ) {
			$sql = "CREATE TABLE $certificate_template_table_name (
  			id int(11) NOT NULL AUTO_INCREMENT,
  			quiz_id int(12) NOT NULL,
			template_name varchar(255) NOT NULL,
  			Creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  			certificate_data TEXT NOT NULL,
  			PRIMARY KEY  (id)
  		) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
     * Adds template variables for certificate.
     *
     * @param array $variable_list
     * @return array
     */
    public function qsm_certificate_show_variable( $variable_list ) {
        global $mlwQuizMasterNext;
        if ( ! empty( $_GET['tab'] ) && 'emails' === $_GET['tab'] ) {
            $template_array['%CERTIFICATE_ATTACHMENT_PDF%'] = __( 'Send the certificate as a PDF attachment via email.', 'qsm-certificate' );
            $template_array['%CERTIFICATE_ATTACHMENT_PDF_X%'] = __( 'This will allow to user to send the template on email via template id ', 'qsm-certificate' );
        }
        $analysis = array(
            'Certificate' => $template_array,
        );
        $variable_list = array_merge( $variable_list, $analysis );
        return $variable_list;
    }

    public function qsm_certificate_link_variable( $variable_list ) {
        global $mlwQuizMasterNext;
        if ( ! empty( $_GET['tab'] ) && 'results-pages' === $_GET['tab'] ) {
            $template_array['%CERTIFICATE_LINK%'] = __( 'This will create a button that allows users to download the certificate with a single click.', 'qsm-certificate' );
            $template_array['%CERTIFICATE_TEMPLATE_X%'] = __( 'This will allow to user to show template by using template id.', 'qsm-certificate' );
        }
        $analysis = array(
            'Certificate' => $template_array,
        );
        $variable_list = array_merge( $variable_list, $analysis );
        return $variable_list;
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
			'https://quizandsurveymaster.com',
			__FILE__,
			array(
				'version'   => $this->version, // current version number.
				'license'   => $license_key,   // license key (used get_option above to retrieve from DB).
				'item_name' => 'Certificate',  // name of this plugin.
				'author'    => 'QSM Team',  // author of this plugin.
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
	echo '<div class="error"><p>'.__( 'QSM - Certificate requires Quiz And Survey Master. Please install and activate the Quiz And Survey Master plugin.', 'qsm-certificate' ).'</p></div>';
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

/**
 * Checks certificate expiry and validity.
 *
 * @return void
 */
function qsm_addon_certificate_expiry_check() {
	global $mlwQuizMasterNext, $wpdb;

	if ( ! isset( $_POST['certificate_id'] ) ) {
		wp_send_json_error(
			array(
				'message' => '<div class="qsm-certificate-error"><span class="dashicons dashicons-no-alt"></span> ' . esc_html__( 'Missing certificate ID', 'qsm-certificate' ) . '</div>',
			)
		);
	}

	$certificate_id = isset( $_POST['certificate_id'] ) ? sanitize_text_field( wp_unslash( $_POST['certificate_id'] ) ) : '';

	$certificate_settings = get_option( 'certificate_settings', array() );
	$error_msgs           = wp_parse_args(
		$certificate_settings,
		array(
			'certificate_id_err_msg_blank_txt' => __( 'Certificate ID cannot be blank', 'qsm-certificate' ),
			'certificate_id_err_msg_wrong_txt' => __( 'Invalid certificate ID', 'qsm-certificate' ),
		)
	);

	if ( empty( $certificate_id ) ) {
		wp_send_json_error(
			array(
				'message' => '<div class="qsm-certificate-error"><span class="dashicons dashicons-no-alt"></span> ' . esc_html( $error_msgs['certificate_id_err_msg_blank_txt'] ) . '</div>',
			)
		);
	}

	$last_13 = substr( $certificate_id, -13 );

	$result_data = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}mlw_results WHERE unique_id = %s ORDER BY result_id DESC LIMIT 1",
			$last_13
		)
	);

	if ( empty( $result_data ) || empty( $result_data->unique_id ) || strlen( $last_13 ) !== 13 ) {
		wp_send_json_success(
			array(
				'status_icon'     => 'dashicons-no-alt',
				'status_color'    => 'red',
				'label_width'     => 'style="width:170px; position: relative;"',
				'status_text'     => esc_html( $error_msgs['certificate_id_err_msg_wrong_txt'] ),
				'quiz_name'       => esc_html__( 'NA', 'qsm-certificate' ),
				'name'            => esc_html__( 'NA', 'qsm-certificate' ),
				'issued_date'     => esc_html__( 'NA', 'qsm-certificate' ),
				'expiry_date'     => esc_html__( 'NA', 'qsm-certificate' ),
				'certificate_url' => '',
				'translations'    => array(
					'issued_by'         => esc_html__( 'Issued by', 'qsm-certificate' ),
					'name_label'        => esc_html__( 'Name', 'qsm-certificate' ),
					'issued_date_label' => esc_html__( 'Issued Date', 'qsm-certificate' ),
					'expires_label'     => esc_html__( 'Expires', 'qsm-certificate' ),
					'preview'           => esc_html__( 'Preview', 'qsm-certificate' ),
				),
			)
		);
	}

	$results = maybe_unserialize( $result_data->quiz_results );
	if ( ! is_array( $results ) ) {
		$results = array( 0, '', '' );
	}

	$mlwQuizMasterNext->quizCreator->set_id( $result_data->quiz_id );

	$quiz_results = array(
		'quiz_id'                => $result_data->quiz_id,
		'quiz_name'              => $result_data->quiz_name,
		'user_name'              => $result_data->name,
		'user_email'             => $result_data->email,
		'timer'                  => $results[0],
		'time_taken'             => $result_data->time_taken,
		'total_points'           => $result_data->point_score,
		'total_score'            => $result_data->correct_score,
		'total_correct'          => $result_data->correct,
		'total_questions'        => $result_data->total,
		'comments'               => $results[2],
		'question_answers_array' => $results[1],
	);

	$issued_date  = date_i18n( 'j F Y', strtotime( $result_data->time_taken_real ) );
	$expiry_int   = intval( substr( substr( $certificate_id, 0, -13 ), -8 ) );
	$current_int  = intval( date( 'Ymd' ) );
	$expiry_date  = ( $expiry_int > 0 ) ? DateTime::createFromFormat( 'Ymd', $expiry_int ) : false;
	$expiry_date  = $expiry_date ? $expiry_date->format( 'd F Y' ) : esc_html__( 'NA', 'qsm-certificate' );
	$exp_time     = $expiry_date ? date( 'd-m-Y', strtotime( $expiry_date ) ) : '';
	$exp_date     = str_replace( '-', '', $exp_time );
	$is_valid     = ( $current_int <= $expiry_int ) || ( 0 === $expiry_int );
	$status_color = $is_valid ? 'green' : 'red';
	$status_text  = $is_valid ? esc_html__( 'This Certificate is valid', 'qsm-certificate' ) : esc_html__( 'This Certificate is Invalid', 'qsm-certificate' );
	$status_icon  = $is_valid ? 'dashicons-yes' : 'dashicons-no-alt';

	$upload_dir        = wp_upload_dir();
	$certificate_dir   = trailingslashit( $upload_dir['basedir'] ) . 'qsm-certificates/';
	$encoded_time_taken = md5( $quiz_results['time_taken'] );
	$filename          = "{$quiz_results['quiz_id']}-{$quiz_results['timer']}-{$encoded_time_taken}-{$quiz_results['total_points']}-{$quiz_results['total_score']}-{$exp_date}.pdf";
	$certificate_url   = trailingslashit( $upload_dir['baseurl'] ) . 'qsm-certificates/' . $filename;

	if ( ! file_exists( $certificate_dir . $filename ) ) {
		$certificate_url = '';
	}

	wp_send_json_success(
		array(
			'status_icon'        => esc_attr( $status_icon ),
			'status_color'       => esc_attr( $status_color ),
			'status_text'        => esc_html( $status_text ),
			'quiz_name'          => esc_html( $result_data->quiz_name ),
			'name'               => esc_html( $result_data->name ),
			'issued_date'        => esc_html( $issued_date ),
			'expiry_date'        => esc_html( $expiry_date ),
			'expiry_date_status' => $expiry_int < $current_int ? 'qsm-logic-expired-date' : '',
			'certificate_url'    => esc_url( $certificate_url ),
			'translations'       => array(
				'issued_by'         => esc_html__( 'Issued by', 'qsm-certificate' ),
				'name_label'        => esc_html__( 'Name', 'qsm-certificate' ),
				'issued_date_label' => esc_html__( 'Issued Date', 'qsm-certificate' ),
				'expires_label'     => esc_html__( 'Expires', 'qsm-certificate' ),
				'preview'           => esc_html__( 'Preview', 'qsm-certificate' ),
			),
		)
	);
}

function qsm_addon_certificate_save_template() {
    global $mlwQuizMasterNext, $wpdb;

    $cert_data        = isset( $_POST['cert_data'] ) ? wp_unslash( $_POST['cert_data'] ) : array();
    $quiz_id          = isset( $_POST['quiz_id'] ) ? (int) $_POST['quiz_id'] : 0;
    $template_name    = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';
    $certificate_data = maybe_serialize( $cert_data );
    $template_action  = isset( $_POST['template_action'] ) ? sanitize_text_field( wp_unslash( $_POST['template_action'] ) ) : 'new';
    $template_id      = isset( $_POST['template_id'] ) ? (int) $_POST['template_id'] : 0;
    $table            = $wpdb->prefix . 'mlw_certificate_template';
    $creation_date    = current_time( 'mysql' );

    if ( 'update' === $template_action && $template_id > 0 ) {
        $ok = $wpdb->update(
            $table,
            array(
                'certificate_data' => $certificate_data,
                'Creation_date'    => $creation_date,
            ),
            array( 'id' => $template_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
        if ( false === $ok ) {
            error_log( 'QSM Certificate: Failed to update certificate template. Last error: ' . $wpdb->last_error );
            wp_send_json_error( 'Failed to update certificate template' );
        }
        wp_send_json_success( array(
            'message'       => 'Certificate template updated',
            'template_id'   => $template_id,
            'template_name' => $template_name,
            'creation_date' => $creation_date,
            'cert_data'     => $cert_data,
        ) );
    }

    $ok = $wpdb->insert(
        $table,
        array(
            'quiz_id'          => $quiz_id,
            'template_name'    => $template_name,
            'Creation_date'    => $creation_date,
            'certificate_data' => $certificate_data,
        ),
        array( '%d', '%s', '%s', '%s' )
    );
    if ( false === $ok ) {
        error_log( 'QSM Certificate: Failed to insert certificate template. Last error: ' . $wpdb->last_error );
        wp_send_json_error( 'Failed to insert certificate template' );
    }
    wp_send_json_success( array(
        'message'       => 'Certificate template saved',
        'template_id'   => $wpdb->insert_id,
        'template_name' => $template_name,
        'creation_date' => $creation_date,
        'cert_data'     => $cert_data,
    ) );
}

/**
 * AJAX handler to delete a certificate template.
 */
function qsm_addon_certificate_delete_template() {
    global $wpdb;
    $table = $wpdb->prefix . 'mlw_certificate_template';
    $template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;
    if ( 0 >= $template_id ) {
        wp_send_json_error( 'Invalid template ID.' );
    }

    $ok = $wpdb->delete(
        $table,
        array( 'id' => $template_id ),
        array( '%d' )
    );
    if ( false === $ok ) {
        error_log( 'QSM Certificate: Failed to delete certificate template. Last error: ' . $wpdb->last_error );
        wp_send_json_error( 'Failed to delete certificate template' );
    }
    wp_send_json_success( array( 'message' => 'Certificate template deleted' ) );
}
add_action( 'wp_ajax_qsm_addon_certificate_delete_template', 'qsm_addon_certificate_delete_template' );

function qsm_certificate_validate_license() {
	global $mlwQuizMasterNext;
	$post_license   = isset( $_POST['input'] ) ? sanitize_text_field( wp_unslash( $_POST['input'] ) ) : '';
	$update_status  = isset( $_POST['update_status'] ) ? sanitize_text_field( wp_unslash( $_POST['update_status'] ) ) : '';
	if ( "" != $post_license ) {

		$settings    = get_option( 'qsm_addon_certificate_settings', array() );
		$license     = isset( $settings['license_key'] ) ? trim( $settings['license_key'] ) : '';

		if ( class_exists ( 'QSM_Check_License' ) ) {
			$activation = $mlwQuizMasterNext->check_license->activate( $post_license, 'Certificate' );
		}else {
			$activation = QSM_license::activate( $post_license, 'Certificate' );
		}

		if ( ! empty($activation) && ( ( __( 'Attempting to activate a bundle\'s parent license', 'qsm-certificate' ) == $activation['message'] || __( 'License key revoked', 'qsm-certificate' ) == $activation['message'] || __( 'No activations left', 'qsm-certificate' ) == $activation['message'] || __( 'License has expired', 'qsm-certificate' ) == $activation['message'] ) || 'success' == $activation['status'] ) ) {
			$item_url = 'https://quizandsurveymaster.com/checkout/?edd_license_key='.$license.'&download_id='.QSM_CERTIFICATE_ITEM;
			$settings['license_key'] = $post_license;
			$settings['license_status'] = 'valid';
			$settings['last_validate'] = gmdate("d-m-Y", time());
			$settings['expiry_date'] = isset($activation['expiry_date']) ? $activation['expiry_date'] : "";
			if ( 'success' == $activation['status'] ) {
				$validate = 1;
				$message = __( 'License validated Successfully', 'qsm-certificate' );
			}elseif ( __( 'License has expired', 'qsm-certificate' ) == $activation['message'] ) {
				$validate = 2;
				$message = __('License Key Expired. ', 'qsm-certificate');
			}else {
				$validate = 0;
				$message = $activation['message'];
			}
		} else {
			$validate = 0;
			$settings['license_key'] = '';
			$settings['license_status'] = '';
			$settings['last_validate'] = 'invalid';
			$settings['expiry_date'] = '';
			$message = $activation['message'];
		}
		// If same license key was entered.
		if ( $license != $post_license ) {
			$deactivation = QSM_license::deactivate( $license, 'Certificate' );
		}
		if ( ! empty( $update_status ) && 'yes' === $update_status ) {
			$keys_to_exclude    = [ 'license_key', 'license_status', 'last_validate', 'expiry_date' ];
			$prev_settings      = array_diff_key($settings, array_flip($keys_to_exclude));
			$settings           = array_merge( $settings, $prev_settings );
		}
		update_option( 'qsm_addon_certificate_settings', $settings );
		echo wp_json_encode(array(
			'validate' => $validate,
			'message'  => $message,
		));
		wp_die();
	}
}