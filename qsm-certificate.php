<?php
/**
 * Plugin Name: QSM - Certificate
 * Plugin URI: http://quizandsurveymaster.com
 * Description: Adds the ability to give certificates to quiz/survey takers
 * Author: Frank Corso
 * Author URI: http://quizandsurveymaster.com
 * Version: 0.1.0
 *
 * @author
 * @version 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


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
  	public $version = '0.1.0';

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
    }

    /**
  	  * Load File Dependencies
  	  *
  	  * @since 0.1.0
  	  * @return void
  	  */
    public function load_dependencies() {
      include( "php/generate-certificate.php" );
      include( "php/results-details-tab-content.php" );
      include( "php/quiz-settings-tab-content.php" );
      include( "php/variables.php" );
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
      add_action( 'admin_init', 'qsm_addon_qsm_certificate_register_quiz_settings_tabs' );
      add_action( 'admin_init', 'qsm_addon_qsm_certificate_register_results_details_tabs' );
      add_filter( 'mlw_qmn_template_variable_results_page', 'qsm_addon_qsm_certificate_variable', 10, 2 );
    }
}

/**
 * Loads the addon if QSM is installed and activated
 *
 * @since 0.1.0
 * @return void
 */
function qsm_addon_qsm_certificate_load() {
	// Make sure QSM is active
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
 * @since       0.1.0
 * @return      string The notice to display
 */
function qsm_addon_qsm_certificate_missing_qsm() {
  echo '<div class="error"><p>QSM - Certificate requires Quiz And Survey Master. Please install and activate the Quiz And Survey Master plugin.</p></div>';
}
?>
