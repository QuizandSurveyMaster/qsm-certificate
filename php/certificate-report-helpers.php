<?php
/**
 * Helpers shared by the Certificate Report sub-tabs and the result details tab.
 *
 * @package QSM_Certificate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Max rows the "Not generated" view loads in one request.
 */
if ( ! defined( 'QSM_CERTIFICATE_REPORT_ROW_LIMIT' ) ) {
	define( 'QSM_CERTIFICATE_REPORT_ROW_LIMIT', 2000 );
}

/**
 * Whether certificates are enabled for a quiz.
 *
 * Mirrors the check on the result details "Certificate Addon" tab: the
 * `enabled` flag is inverted (0 = Yes, 1 = No), with a fallback to the
 * legacy `certificate_template` column for quizzes saved by old versions.
 *
 * @param object $quiz_row Row from mlw_quizzes with quiz_settings and certificate_template.
 * @return bool
 */
function qsm_certificate_is_enabled( $quiz_row ) {
	$certificate_settings = null;

	if ( ! empty( $quiz_row->quiz_settings ) ) {
		$quiz_settings = maybe_unserialize( $quiz_row->quiz_settings );
		if ( is_array( $quiz_settings ) && isset( $quiz_settings['certificate_settings'] ) ) {
			$certificate_settings = maybe_unserialize( $quiz_settings['certificate_settings'] );
		}
	}

	if ( ! is_array( $certificate_settings ) && ! empty( $quiz_row->certificate_template ) ) {
		$certificate = maybe_unserialize( $quiz_row->certificate_template );
		if ( is_array( $certificate ) ) {
			$certificate_settings = array( 'enabled' => isset( $certificate[4] ) ? (int) $certificate[4] : 1 );
		}
	}

	return is_array( $certificate_settings ) && ! ( isset( $certificate_settings['enabled'] ) && 1 === (int) $certificate_settings['enabled'] );
}

/**
 * Quizzes (not deleted) that have certificates enabled.
 *
 * @return array quiz_id => quiz_name
 */
function qsm_certificate_get_enabled_quizzes() {
	global $wpdb;

	$quizzes = $wpdb->get_results( "SELECT quiz_id, quiz_name, quiz_settings, certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE deleted = 0 ORDER BY quiz_name ASC" );
	$enabled = array();
	foreach ( (array) $quizzes as $quiz ) {
		if ( qsm_certificate_is_enabled( $quiz ) ) {
			$enabled[ (int) $quiz->quiz_id ] = $quiz->quiz_name;
		}
	}
	return $enabled;
}

/**
 * The prefix every certificate file of a result starts with.
 *
 * Same pattern the result details tab globs for: {quiz_id}-{result_id}-{md5(time_taken)}-*.pdf
 *
 * @param int    $quiz_id    Quiz ID.
 * @param int    $result_id  Result ID.
 * @param string $time_taken mlw_results.time_taken.
 * @return string
 */
function qsm_certificate_result_key( $quiz_id, $result_id, $time_taken ) {
	return (int) $quiz_id . '-' . (int) $result_id . '-' . md5( $time_taken );
}

/**
 * Existing certificate file (basename) for a result, or '' when there is none.
 *
 * @param array $quiz_results Needs quiz_id, result_id and time_taken.
 * @return string
 */
function qsm_certificate_find_existing_file( $quiz_results ) {
	if ( empty( $quiz_results['quiz_id'] ) || empty( $quiz_results['result_id'] ) || ! isset( $quiz_results['time_taken'] ) ) {
		return '';
	}
	$upload_dir = wp_upload_dir();
	$pattern    = trailingslashit( $upload_dir['basedir'] ) . 'qsm-certificates/' . qsm_certificate_result_key( $quiz_results['quiz_id'], $quiz_results['result_id'], $quiz_results['time_taken'] ) . '-*.pdf';
	$files      = glob( $pattern );
	return empty( $files ) ? '' : basename( $files[0] );
}

/**
 * Certificate PDFs on disk, from a single directory read.
 *
 * @return array result key ({quiz}-{result}-{md5}) => basename, for files named the current way.
 */
function qsm_certificate_existing_files() {
	$upload_dir = wp_upload_dir();
	$files      = glob( trailingslashit( $upload_dir['basedir'] ) . 'qsm-certificates/*.pdf' );
	$keys       = array();
	foreach ( (array) $files as $file ) {
		$name  = basename( $file );
		$parts = explode( '-', $name, 4 );
		if ( 4 === count( $parts ) ) {
			$key = $parts[0] . '-' . $parts[1] . '-' . $parts[2];
			if ( ! isset( $keys[ $key ] ) ) {
				$keys[ $key ] = $name;
			}
		}
	}
	return $keys;
}

/**
 * Validates a Y-m-d date from the report filter.
 *
 * @param string $date Raw value.
 * @return string Valid Y-m-d date or ''.
 */
function qsm_certificate_sanitize_filter_date( $date ) {
	$date = sanitize_text_field( $date );
	$dt   = DateTime::createFromFormat( '!Y-m-d', $date );
	return ( $dt && $dt->format( 'Y-m-d' ) === $date ) ? $date : '';
}

/**
 * Reads the report filters from the request.
 *
 * @param array $enabled_quizzes quiz_id => quiz_name.
 * @return array status, quiz_id, date_from, date_to
 */
function qsm_certificate_report_filters( $enabled_quizzes ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only GET filters.
	$status = isset( $_GET['cert_status'] ) ? sanitize_key( wp_unslash( $_GET['cert_status'] ) ) : '';
	if ( '' === $status && isset( $_GET['cert_view'] ) ) {
		// Links from the former Generated / Not generated sub-tabs.
		$status = sanitize_key( wp_unslash( $_GET['cert_view'] ) );
	}
	if ( ! in_array( $status, array( 'generated', 'not-generated' ), true ) ) {
		$status = 'all';
	}
	$quiz_id   = isset( $_GET['cert_quiz'] ) ? absint( $_GET['cert_quiz'] ) : 0;
	$date_from = isset( $_GET['cert_from'] ) ? qsm_certificate_sanitize_filter_date( wp_unslash( $_GET['cert_from'] ) ) : '';
	$date_to   = isset( $_GET['cert_to'] ) ? qsm_certificate_sanitize_filter_date( wp_unslash( $_GET['cert_to'] ) ) : '';

	// First visit (no date params at all): last 30 days, so large sites don't
	// load every result. Clearing the inputs and filtering shows all time.
	if ( ! isset( $_GET['cert_from'] ) && ! isset( $_GET['cert_to'] ) ) {
		$date_from = wp_date( 'Y-m-d', strtotime( '-30 days' ) );
	}
	// phpcs:enable

	if ( $quiz_id && ! isset( $enabled_quizzes[ $quiz_id ] ) ) {
		$quiz_id = 0;
	}
	if ( $date_from && $date_to && $date_from > $date_to ) {
		list( $date_from, $date_to ) = array( $date_to, $date_from );
	}

	return array(
		'status'    => $status,
		'quiz_id'   => $quiz_id,
		'date_from' => $date_from,
		'date_to'   => $date_to,
	);
}

/**
 * Expiry date encoded at the end of a certificate filename (…-ddmmYYYY.pdf).
 *
 * @param string $file_name Certificate basename.
 * @return DateTime|null Null when the certificate never expires.
 */
function qsm_certificate_file_expiry( $file_name ) {
	if ( strlen( $file_name ) < 53 ) {
		return null;
	}
	$last_part = substr( $file_name, -12, 10 );
	$date      = DateTime::createFromFormat( '!d-m-Y', substr( $last_part, 0, 2 ) . '-' . substr( $last_part, 2, 2 ) . '-' . substr( $last_part, 4, 4 ) );
	return $date ? $date : null;
}

/**
 * Certificate IDs (stored in quiz_results at submission) for the given results.
 *
 * @param int[] $result_ids Result IDs.
 * @return array result_id => certificate ID
 */
function qsm_certificate_get_certificate_ids( $result_ids ) {
	global $wpdb, $mlwQuizMasterNext;

	$ids = array();
	foreach ( array_chunk( array_map( 'intval', $result_ids ), 500 ) as $chunk ) {
		$rows = $wpdb->get_results( "SELECT result_id, quiz_results FROM {$wpdb->prefix}mlw_results WHERE result_id IN (" . implode( ',', $chunk ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- intval'd IDs.
		foreach ( (array) $rows as $row ) {
			if (
				empty( $row->quiz_results ) &&
				isset( $mlwQuizMasterNext->pluginHelper ) &&
				method_exists( $mlwQuizMasterNext->pluginHelper, 'get_formated_result_data' )
			) {
				$data = $mlwQuizMasterNext->pluginHelper->get_formated_result_data( $row->result_id );
			} else {
				$data = maybe_unserialize( $row->quiz_results );
			}
			if ( is_array( $data ) && ! empty( $data['certificate_id'] ) ) {
				$ids[ (int) $row->result_id ] = $data['certificate_id'];
			}
		}
	}
	return $ids;
}

/**
 * PDFs that belong to no current result of a certificate-enabled quiz: the old
 * filename format, a deleted result, or a quiz whose certificates were turned
 * off. They have no result to generate for but must stay listed so they can be
 * viewed and deleted.
 *
 * @param array $enabled_quizzes quiz_id => quiz_name.
 * @param array $existing        From qsm_certificate_existing_files().
 * @return string[] Basenames.
 */
function qsm_certificate_unmatched_files( $enabled_quizzes, $existing ) {
	global $wpdb;

	$upload_dir = wp_upload_dir();
	$all        = array_map( 'basename', (array) glob( trailingslashit( $upload_dir['basedir'] ) . 'qsm-certificates/*.pdf' ) );
	$matched    = array();

	$result_ids = array();
	foreach ( $existing as $key => $name ) {
		$parts = explode( '-', $key );
		if ( isset( $parts[1] ) && preg_match( '/^\d+$/', $parts[1] ) ) {
			$result_ids[] = (int) $parts[1];
		}
	}
	foreach ( array_chunk( array_unique( $result_ids ), 500 ) as $chunk ) {
		$rows = $wpdb->get_results( "SELECT result_id, quiz_id, time_taken FROM {$wpdb->prefix}mlw_results WHERE deleted = 0 AND result_id IN (" . implode( ',', $chunk ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- intval'd IDs.
		foreach ( (array) $rows as $row ) {
			$key = qsm_certificate_result_key( $row->quiz_id, $row->result_id, $row->time_taken );
			if ( isset( $existing[ $key ], $enabled_quizzes[ (int) $row->quiz_id ] ) ) {
				$matched[ $existing[ $key ] ] = true;
			}
		}
	}

	return array_values(
		array_filter(
			$all,
			function ( $name ) use ( $matched ) {
				return ! isset( $matched[ $name ] );
			}
		)
	);
}

/**
 * The report list: results of certificate-enabled quizzes with their
 * certificate status, plus unmatched PDFs (always "generated").
 *
 * A result counts as generated with the same check the result details tab
 * uses: a file named {quiz_id}-{result_id}-{md5(time_taken)}-*.pdf exists.
 *
 * @param array $enabled_quizzes quiz_id => quiz_name.
 * @param array $filters         From qsm_certificate_report_filters().
 * @return array { rows: object[], truncated: bool }
 */
function qsm_certificate_get_report_rows( $enabled_quizzes, $filters ) {
	global $wpdb;

	$existing  = qsm_certificate_existing_files();
	$upload    = wp_upload_dir();
	$cert_dir  = trailingslashit( $upload['basedir'] ) . 'qsm-certificates/';
	$list      = array();
	$truncated = false;

	$quiz_ids = $filters['quiz_id'] ? array( $filters['quiz_id'] ) : array_keys( $enabled_quizzes );
	if ( ! empty( $quiz_ids ) ) {
		$where  = 'deleted = 0 AND quiz_id IN (' . implode( ',', array_map( 'intval', $quiz_ids ) ) . ')';
		$params = array();
		if ( $filters['date_from'] ) {
			$where   .= ' AND time_taken_real >= %s';
			$params[] = $filters['date_from'] . ' 00:00:00';
		}
		if ( $filters['date_to'] ) {
			$where   .= ' AND time_taken_real <= %s';
			$params[] = $filters['date_to'] . ' 23:59:59';
		}

		$batch  = 500;
		$offset = 0;
		// Scan newest-first in batches until the list is full.
		do {
			$sql  = "SELECT result_id, quiz_id, quiz_name, name, email, point_score, correct_score, quiz_system, time_taken, time_taken_real FROM {$wpdb->prefix}mlw_results WHERE {$where} ORDER BY result_id DESC LIMIT %d OFFSET %d";
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $params, array( $batch, $offset ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $where holds only placeholders and intval'd IDs.
			foreach ( (array) $rows as $row ) {
				$key            = qsm_certificate_result_key( $row->quiz_id, $row->result_id, $row->time_taken );
				$row->cert_file = isset( $existing[ $key ] ) ? $existing[ $key ] : '';
				if ( ( 'generated' === $filters['status'] && '' === $row->cert_file ) || ( 'not-generated' === $filters['status'] && '' !== $row->cert_file ) ) {
					continue;
				}
				if ( count( $list ) >= QSM_CERTIFICATE_REPORT_ROW_LIMIT ) {
					$truncated = true;
					break 2;
				}
				$list[] = $row;
			}
			$offset += $batch;
		} while ( count( (array) $rows ) === $batch );
	}

	if ( 'not-generated' !== $filters['status'] && ! $truncated ) {
		foreach ( qsm_certificate_unmatched_files( $enabled_quizzes, $existing ) as $name ) {
			$parts = explode( '-', $name );
			if ( $filters['quiz_id'] && (int) $parts[0] !== $filters['quiz_id'] ) {
				continue;
			}
			$day = gmdate( 'Y-m-d', filemtime( $cert_dir . $name ) );
			if ( ( $filters['date_from'] && $day < $filters['date_from'] ) || ( $filters['date_to'] && $day > $filters['date_to'] ) ) {
				continue;
			}
			if ( count( $list ) >= QSM_CERTIFICATE_REPORT_ROW_LIMIT ) {
				$truncated = true;
				break;
			}
			$list[] = (object) array(
				'result_id' => 0,
				'quiz_id'   => (int) $parts[0],
				'cert_file' => $name,
			);
		}
	}

	return array(
		'rows'      => $list,
		'truncated' => $truncated,
	);
}

/**
 * Builds the $quiz_results array the generator expects from an mlw_results row.
 *
 * @param object $results_data Row from mlw_results.
 * @return array
 */
function qsm_certificate_build_quiz_results( $results_data ) {
	global $mlwQuizMasterNext;

	if (
		empty( $results_data->quiz_results ) &&
		isset( $mlwQuizMasterNext->pluginHelper ) &&
		method_exists( $mlwQuizMasterNext->pluginHelper, 'get_formated_result_data' )
	) {
		$results = $mlwQuizMasterNext->pluginHelper->get_formated_result_data( $results_data->result_id );
	} elseif ( empty( $results_data->quiz_results ) ) {
		$results = array(
			0,
			array(),
			'',
			'contact' => array(),
		);
	} else {
		$results = maybe_unserialize( $results_data->quiz_results );
	}
	if ( ! is_array( $results ) ) {
		$results = array(
			0,
			array(),
			'',
			'contact' => array(),
		);
	}

	return array(
		'quiz_id'                => $results_data->quiz_id,
		'quiz_name'              => $results_data->quiz_name ?? '',
		'quiz_system'            => $results_data->quiz_system ?? 0,
		'user_name'              => $results_data->name ?? '',
		'user_business'          => $results_data->business ?? '',
		'user_email'             => $results_data->email ?? '',
		'user_phone'             => $results_data->phone ?? '',
		'user_id'                => $results_data->user ?? 0,
		'timer'                  => $results[0] ?? 0,
		'time_taken'             => $results_data->time_taken ?? '',
		'total_points'           => $results_data->point_score ?? 0,
		'total_score'            => $results_data->correct_score ?? 0,
		'total_correct'          => $results_data->correct ?? 0,
		'total_questions'        => $results_data->total ?? 0,
		'comments'               => $results[2] ?? '',
		'question_answers_array' => $results[1] ?? array(),
		'result_id'              => (int) $results_data->result_id,
	);
}

/**
 * Generates the certificate for one result, unless it already exists.
 *
 * @param int $result_id Result ID.
 * @return array { status: generated|exists|error, url?: string, message?: string }
 */
function qsm_certificate_generate_for_result( $result_id ) {
	global $wpdb, $mlwQuizMasterNext;

	$results_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mlw_results WHERE result_id = %d AND deleted = 0", $result_id ) );
	if ( ! $results_data ) {
		return array(
			'status'  => 'error',
			'message' => __( 'Result not found.', 'qsm-certificate' ),
		);
	}

	$quiz_row = $wpdb->get_row( $wpdb->prepare( "SELECT quiz_settings, certificate_template FROM {$wpdb->prefix}mlw_quizzes WHERE quiz_id = %d LIMIT 1", $results_data->quiz_id ) );
	if ( ! $quiz_row || ! qsm_certificate_is_enabled( $quiz_row ) ) {
		return array(
			'status'  => 'error',
			'message' => __( 'Certificates are not enabled for this quiz.', 'qsm-certificate' ),
		);
	}

	$upload   = wp_upload_dir();
	$cert_dir = trailingslashit( $upload['basedir'] ) . 'qsm-certificates/';
	$pattern  = qsm_certificate_result_key( $results_data->quiz_id, $results_data->result_id, $results_data->time_taken ) . '-*.pdf';

	$existing = glob( $cert_dir . $pattern );
	if ( ! empty( $existing ) ) {
		return qsm_certificate_generated_payload( 'exists', basename( $existing[0] ) );
	}

	// Load this quiz's settings; the generator reads them via get_quiz_setting().
	$mlwQuizMasterNext->pluginHelper->prepare_quiz( (int) $results_data->quiz_id );

	try {
		$file = qsm_addon_certificate_generate_certificate( qsm_certificate_build_quiz_results( $results_data ), 0, true );
	} catch ( Throwable $e ) {
		$file = false;
		error_log( 'QSM Certificate: generation failed for result ' . (int) $result_id . ': ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	if ( empty( $file ) || ! file_exists( $cert_dir . urldecode( $file ) ) ) {
		return array(
			'status'  => 'error',
			'message' => __( 'Failed to generate certificate.', 'qsm-certificate' ),
		);
	}

	return qsm_certificate_generated_payload( 'generated', urldecode( $file ) );
}

/**
 * What the report needs to show a generated certificate in its row.
 *
 * @param string $status    generated|exists.
 * @param string $file_name Certificate basename.
 * @return array
 */
function qsm_certificate_generated_payload( $status, $file_name ) {
	$upload   = wp_upload_dir();
	$cert_dir = trailingslashit( $upload['basedir'] ) . 'qsm-certificates/';
	$expiry   = qsm_certificate_file_expiry( $file_name );
	return array(
		'status'    => $status,
		'file'      => $file_name,
		'url'       => trailingslashit( $upload['baseurl'] ) . 'qsm-certificates/' . rawurlencode( $file_name ),
		'generated' => gmdate( 'd-m-Y H:i:s', filemtime( $cert_dir . $file_name ) ),
		'expiry'    => $expiry ? $expiry->format( 'd-m-Y' ) : __( 'Never Expire', 'qsm-certificate' ),
	);
}

/**
 * AJAX: generate the certificate for ONE result.
 *
 * Bulk generation calls this once per result from a browser-side queue, one
 * request at a time, so the server never renders more than one PDF at once.
 */
function qsm_certificate_ajax_generate_single() {
	check_ajax_referer( 'qsm_certificate_generate', 'nonce' );
	if ( ! current_user_can( 'view_qsm_quiz_result' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to generate certificates.', 'qsm-certificate' ) ), 403 );
	}

	$result_id = isset( $_POST['result_id'] ) ? absint( $_POST['result_id'] ) : 0;
	if ( ! $result_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid result.', 'qsm-certificate' ) ), 400 );
	}

	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$outcome = qsm_certificate_generate_for_result( $result_id );
	if ( 'error' === $outcome['status'] ) {
		wp_send_json_error( $outcome );
	}
	wp_send_json_success( $outcome );
}
add_action( 'wp_ajax_qsm_certificate_generate_single', 'qsm_certificate_ajax_generate_single' );

/**
 * Base URL of the Certificate Report tab.
 *
 * @param array $args Extra query args.
 * @return string
 */
function qsm_certificate_report_url( $args = array() ) {
	return add_query_arg(
		array_merge(
			array(
				'page' => 'mlw_quiz_results',
				'tab'  => 'certificate-report',
			),
			$args
		),
		admin_url( 'admin.php' )
	);
}

/**
 * Renders the bulk actions and the status / quiz / date filters, laid out like
 * the core Quiz Results page (.tablenav.top: bulk actions left, filters right).
 *
 * @param array $enabled_quizzes quiz_id => quiz_name.
 * @param array $filters         From qsm_certificate_report_filters().
 */
function qsm_certificate_report_render_filters( $enabled_quizzes, $filters ) {
	$statuses = array(
		'all'           => __( 'All Statuses', 'qsm-certificate' ),
		'generated'     => __( 'Generated', 'qsm-certificate' ),
		'not-generated' => __( 'Not generated', 'qsm-certificate' ),
	);
	?>
	<div class="tablenav top qsm-certificate-report-filters">
		<div class="alignleft actions bulkactions">
			<select id="qsm-cert-bulk-action" class="postform">
				<option value=""><?php esc_html_e( 'Bulk Actions', 'qsm-certificate' ); ?></option>
				<option value="generate"><?php esc_html_e( 'Generate Certificates', 'qsm-certificate' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete Certificates', 'qsm-certificate' ); ?></option>
			</select>
			<button type="button" id="qsm-cert-bulk-apply" class="button action"><?php esc_html_e( 'Apply', 'qsm-certificate' ); ?></button>
		</div>
		<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
			<input type="hidden" name="page" value="mlw_quiz_results">
			<input type="hidden" name="tab" value="certificate-report">
			<p class="search-box" style="margin: 0;">
				<label for="qsm-cert-status"><?php esc_html_e( 'Status', 'qsm-certificate' ); ?></label>
				<select id="qsm-cert-status" name="cert_status">
					<?php foreach ( $statuses as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
				</select>
				<label for="qsm-cert-quiz"><?php esc_html_e( 'Quiz', 'qsm-certificate' ); ?></label>
				<select id="qsm-cert-quiz" name="cert_quiz">
					<option value="0"><?php esc_html_e( 'All Quizzes', 'qsm-certificate' ); ?></option>
					<?php foreach ( $enabled_quizzes as $quiz_id => $quiz_name ) { ?>
						<option value="<?php echo esc_attr( $quiz_id ); ?>" <?php selected( $filters['quiz_id'], $quiz_id ); ?>><?php echo esc_html( $quiz_name ); ?></option>
					<?php } ?>
				</select>
				<label for="qsm-cert-from"><?php esc_html_e( 'From', 'qsm-certificate' ); ?></label>
				<input type="date" id="qsm-cert-from" name="cert_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>" title="<?php esc_attr_e( 'Quiz submission date', 'qsm-certificate' ); ?>">
				<label for="qsm-cert-to"><?php esc_html_e( 'To', 'qsm-certificate' ); ?></label>
				<input type="date" id="qsm-cert-to" name="cert_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>" title="<?php esc_attr_e( 'Quiz submission date', 'qsm-certificate' ); ?>">
				<button class="button"><?php esc_html_e( 'Filter', 'qsm-certificate' ); ?></button>
				<a class="button" href="<?php echo esc_url( qsm_certificate_report_url() ); ?>"><?php esc_html_e( 'Reset', 'qsm-certificate' ); ?></a>
			</p>
		</form>
		<br class="clear">
	</div>
	<?php
}

/**
 * Action cell for a row: View + Delete when a certificate exists, Generate when not.
 *
 * @param object $row Report row.
 * @return string HTML.
 */
function qsm_certificate_report_action_html( $row ) {
	if ( '' !== $row->cert_file ) {
		$upload   = wp_upload_dir();
		$file_url = trailingslashit( $upload['baseurl'] ) . 'qsm-certificates/' . $row->cert_file;
		return '<div class="qsm-table-icons">
			<a href="' . esc_url( $file_url ) . '" target="_blank" class="qsm-view-file" title="' . esc_attr__( 'View', 'qsm-certificate' ) . '">
				<img class="qsm-common-svg-image-class" src="' . esc_url( QSM_CERTIFICATE_URL . 'assets/eye-line.png' ) . '" alt="' . esc_attr__( 'View Icon', 'qsm-certificate' ) . '">
			</a>
			<button type="button" class="qsm-cert-delete" data-filename="' . esc_attr( $row->cert_file ) . '" title="' . esc_attr__( 'Delete', 'qsm-certificate' ) . '">
				<img class="qsm-common-svg-image-class" src="' . esc_url( QSM_CERTIFICATE_URL . 'assets/trash.png' ) . '" alt="' . esc_attr__( 'Delete Icon', 'qsm-certificate' ) . '">
			</button>
		</div>';
	}
	return '<button type="button" class="button button-small qsm-cert-generate" data-result-id="' . esc_attr( $row->result_id ) . '">' . esc_html__( 'Generate', 'qsm-certificate' ) . '</button>';
}

/**
 * Renders the single certificate list with a status column.
 *
 * @param array $enabled_quizzes quiz_id => quiz_name.
 * @param array $filters         From qsm_certificate_report_filters().
 */
function qsm_certificate_report_render_list( $enabled_quizzes, $filters ) {
	if ( empty( $enabled_quizzes ) && 'not-generated' === $filters['status'] ) {
		echo '<div class="notice notice-info inline"><p>' . esc_html__( 'No quiz has certificates enabled.', 'qsm-certificate' ) . '</p></div>';
		return;
	}

	$report = qsm_certificate_get_report_rows( $enabled_quizzes, $filters );

	// Kept for the delete_certificate / bulk_delete_certificates AJAX handlers.
	wp_nonce_field( 'bulk_delete_certificates_action', 'bulk_delete_certificates_nonce' );

	if ( empty( $report['rows'] ) ) {
		echo '<div class="notice notice-info inline"><p>' . esc_html__( 'No results match these filters.', 'qsm-certificate' ) . '</p></div>';
		return;
	}

	if ( $report['truncated'] ) {
		/* translators: %d: number of rows shown */
		echo '<div class="notice notice-warning inline"><p>' . esc_html( sprintf( __( 'Showing the newest %d rows. Narrow the filters to see the rest.', 'qsm-certificate' ), QSM_CERTIFICATE_REPORT_ROW_LIMIT ) ) . '</p></div>';
	}

	$upload   = wp_upload_dir();
	$cert_dir = trailingslashit( $upload['basedir'] ) . 'qsm-certificates/';
	$today    = new DateTime( 'today' );

	$generated_ids = array();
	foreach ( $report['rows'] as $row ) {
		if ( $row->result_id && '' !== $row->cert_file ) {
			$generated_ids[] = (int) $row->result_id;
		}
	}
	$certificate_ids = $generated_ids ? qsm_certificate_get_certificate_ids( $generated_ids ) : array();
	?>
	<div id="qsm-cert-queue" class="notice notice-info inline" style="display:none;">
		<p>
			<span class="spinner is-active" style="float:none;margin:0 6px 0 0;"></span>
			<span class="qsm-cert-queue-text"></span>
			<button type="button" class="button button-small qsm-cert-queue-stop"><?php esc_html_e( 'Stop', 'qsm-certificate' ); ?></button>
			<button type="button" class="button button-small qsm-cert-queue-retry" style="display:none;"></button>
		</p>
	</div>
	<?php
	echo '<div class="qsm-certificate-table-container">';
	echo '<table id="qsm-certificate-report-table" class="wp-list-table widefat fixed striped">';
	echo '<thead><tr>
		<th class="qsm-manage-column qsm-check-column"><input type="checkbox" id="qsm-cert-select-all"></th>
		<th class="qsm-manage-column">' . esc_html__( 'Result ID', 'qsm-certificate' ) . '</th>
		<th class="qsm-manage-column">' . esc_html__( 'Quiz', 'qsm-certificate' ) . '</th>
		<th class="qsm-manage-column">' . esc_html__( 'User', 'qsm-certificate' ) . '</th>
		<th class="qsm-manage-column">' . esc_html__( 'Submitted', 'qsm-certificate' ) . '</th>
		<th class="qsm-manage-column">' . esc_html__( 'Status', 'qsm-certificate' ) . '</th>
		<th class="qsm-manage-column">' . esc_html__( 'Generated Date', 'qsm-certificate' ) . '</th>
		<th class="qsm-manage-column">' . esc_html__( 'Expiry Date', 'qsm-certificate' ) . '</th>
		<th class="qsm-manage-column">' . esc_html__( 'Certificate ID', 'qsm-certificate' ) . '</th>
		<th class="qsm-manage-column">' . esc_html__( 'Action', 'qsm-certificate' ) . '</th>
	</tr></thead><tbody>';

	foreach ( $report['rows'] as $row ) {
		$generated = '' !== $row->cert_file;
		$quiz_name = isset( $enabled_quizzes[ (int) $row->quiz_id ] ) ? $enabled_quizzes[ (int) $row->quiz_id ] : ( isset( $row->quiz_name ) ? $row->quiz_name : '#' . (int) $row->quiz_id );

		$attrs = ' data-status="' . ( $generated ? 'generated' : 'not-generated' ) . '"';
		if ( $row->result_id ) {
			$attrs .= ' data-result-id="' . esc_attr( $row->result_id ) . '"';
		}
		if ( $generated ) {
			$attrs .= ' data-filename="' . esc_attr( $row->cert_file ) . '"';
		}
		echo '<tr' . $attrs . ( $generated ? ' class="qsm-cert-done"' : '' ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		echo '<th scope="row" class="qsm-check-column"><input type="checkbox" class="qsm-cert-cb"></th>';

		if ( $row->result_id ) {
			$details_url = add_query_arg(
				array(
					'page'      => 'qsm_quiz_result_details',
					'result_id' => (int) $row->result_id,
				),
				admin_url( 'admin.php' )
			);
			if ( 1 === (int) $row->quiz_system ) {
				$score = $row->point_score . ' ' . __( 'points', 'qsm-certificate' );
			} elseif ( 0 === (int) $row->quiz_system ) {
				$score = $row->correct_score . '%';
			} else {
				$score = '';
			}
			echo '<td data-order="' . esc_attr( $row->result_id ) . '"><a href="' . esc_url( $details_url ) . '" target="_blank">' . esc_html( $row->result_id ) . '</a></td>';
			echo '<td>' . esc_html( $quiz_name ) . ( '' !== $score ? '<br><span class="description">' . esc_html( $score ) . '</span>' : '' ) . '</td>';
			echo '<td>' . esc_html( $row->name ) . '<br><span class="description">' . esc_html( $row->email ) . '</span></td>';
			echo '<td data-order="' . esc_attr( $row->time_taken_real ) . '">' . esc_html( $row->time_taken_real ) . '</td>';
		} else {
			echo '<td data-order="0">&mdash;</td>';
			echo '<td>' . esc_html( $quiz_name ) . '</td>';
			echo '<td><span class="description" title="' . esc_attr( $row->cert_file ) . '">' . esc_html__( 'Not linked to a current result', 'qsm-certificate' ) . '</span></td>';
			echo '<td data-order="">&mdash;</td>';
		}

		echo '<td class="qsm-cert-status">' . ( $generated ? esc_html__( 'Generated', 'qsm-certificate' ) : esc_html__( 'Not generated', 'qsm-certificate' ) ) . '</td>';

		if ( $generated ) {
			$mtime  = filemtime( $cert_dir . $row->cert_file );
			$expiry = qsm_certificate_file_expiry( $row->cert_file );
			echo '<td class="qsm-cert-generated" data-order="' . esc_attr( $mtime ) . '">' . esc_html( gmdate( 'd-m-Y H:i:s', $mtime ) ) . '</td>';
			if ( $expiry ) {
				echo '<td class="qsm-cert-expiry" data-order="' . esc_attr( $expiry->format( 'Ymd' ) ) . '"' . ( $today >= $expiry ? ' style="color: red;"' : '' ) . '>' . esc_html( $expiry->format( 'd-m-Y' ) ) . '</td>';
			} else {
				echo '<td class="qsm-cert-expiry" data-order="99999999">' . esc_html__( 'Never Expire', 'qsm-certificate' ) . '</td>';
			}
			echo '<td class="qsm-cert-id">' . ( isset( $certificate_ids[ (int) $row->result_id ] ) ? esc_html( $certificate_ids[ (int) $row->result_id ] ) : '&mdash;' ) . '</td>';
		} else {
			echo '<td class="qsm-cert-generated" data-order="0">&mdash;</td><td class="qsm-cert-expiry" data-order="0">&mdash;</td><td class="qsm-cert-id">&mdash;</td>';
		}

		echo '<td class="qsm-cert-action">' . qsm_certificate_report_action_html( $row ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
		echo '</tr>';
	}

	echo '</tbody></table></div>';
}
