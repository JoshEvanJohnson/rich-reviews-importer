<?php
/**
 * Import Functions
 *
 * @package    Rich_Reviews_Importer
 * @subpackage Functions
 * @copyright  Copyright (c) 2018, joshuaevanjohnson.com
 * @link       https://joshuaevanjohnson.com/plugins/rich-reviews-importer/
 * @license    GPLv2 or later
 * @since      0.1
 */

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upload import file
 *
 * @since 0.3
 */
function rri_upload_import_file() {

	// Check nonce for security since form was posted.
	// check_admin_referer prints fail page and dies.
	if ( ! empty( $_POST ) && ! empty( $_FILES['rri_import_file'] ) && check_admin_referer( 'rri_import', 'rri_import_nonce' ) ) {

		// Workaround for upload bug in WordPress 4.7.1.
		// This will only be applied for WordPress 4.7.1. Other versions are not affected.
		add_filter( 'wp_check_filetype_and_ext', 'rri_disable_real_mime_check', 10, 4 );

		// Uploaded file.
		$uploaded_file = $_FILES['rri_import_file'];

		// Check file type.
		// This will also fire if no file uploaded.
		$wp_filetype = wp_check_filetype_and_ext( $uploaded_file['tmp_name'], $uploaded_file['name'], false );
		if ( 'csv' !== $wp_filetype['ext'] && ! wp_match_mime_types( 'csv', $wp_filetype['type'] ) ) {

			wp_die(
				wp_kses(
					__( 'You must upload a <b>.csv</b> file formatted for this plugin.', 'rich-reviews-importer' ),
					array(
						'b' => array(),
					)
				),
				'',
				array(
					'back_link' => true,
				)
			);

		}

		// Check and move file to uploads dir, get file data
		// Will show die with WP errors if necessary (file too large, quota exceeded, etc.).
		$file_data = wp_handle_upload( $uploaded_file, array(
			'test_form' => false,
		) );

		if ( isset( $file_data['error'] ) ) {
			wp_die(
				esc_html( $file_data['error'] ),
				'',
				array(
					'back_link' => true,
				)
			);
		}

		// Process import file.
		rri_process_import_file( $file_data['file'] );

	}

}

add_action( 'load-tools_page_rich-reviews-importer', 'rri_upload_import_file' );

/**
 * Process import file
 *
 * This parses a file and triggers importation of the reviews.
 *
 * @since 0.3
 * @param string $file Path to .rri file uploaded.
 * @global string $rri_import_results
 */
function rri_process_import_file( $file ) {

	global $rri_import_results;

	// File exists?
	if ( ! file_exists( $file ) ) {

		wp_die(
			esc_html__( 'Import file could not be found. Please try again.', 'rich-reviews-importer' ),
			'',
			array(
				'back_link' => true,
			)
		);

	}

	//$data = implode( '', file( $file ) );


	$columns = array('user_first_name','user_last_name','service_name','review_timestamp','rating','review_comment','review_recommend','user_city','user_zip');

  $import_array = array();
	$row = 1;
	if (($handle = fopen($file, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
			$num = count($data);
      if(
        $data[0] != 'qryReviewEngineExport' &&
        $data[0] != 'First Name' &&
        $data[0] != '' &&
        $data[1] != ''
      ){
  			for ($c=0; $c < $num; $c++) {
  				$import_array[$row][$columns[$c]] = $data[$c];
  			}
      }
			$row++;
		}
		fclose($handle);
	}

	// Delete import file.
	unlink( $file );

	// Import the reviews data
	// Make results available for display on import/export page.
	$rri_import_results = rri_import_data( $import_array );

}

/**
 * Import reviews array data
 *
 * @since 0.1
 * @global array $wp_registered_sidebars
 * @param  array $data array review data from .csv file.
 * @return array Results array
 */
function rri_import_data( $data ) {
  global $wpdb;

	// Have valid data?
	// If no data or could not decode.
	if ( empty( $data ) || ! is_array( $data ) ) {

		wp_die(
			esc_html__( 'Import data could not be read. Please try a different file.', 'rich-reviews-importer' ),
			'',
			array(
				'back_link' => true,
			)
		);

	}

	// Hook before import.
	do_action( 'rri_before_import' );
	$data = apply_filters( 'rri_import_data', $data );

	// Begin results.
	$results = array();

	// Loop import data's reviews
	foreach ( $data as $review_index => $review ) {

		$fail = false;

    /*
     *
     * TODO make some sort of checks in case there are duplicates or something to worry about
     *
     */

		// No failure.
		if ( ! $fail ) {

      $review['name'] = $review['user_first_name'] . ' ' . $review['user_last_name'];
      $review['reviewer_email'] = 'imported.user@giroudtree.com';
      $review['title'] = $review['user_city'] . ', PA';
      $review['review_text'] = $review['review_comment'];
  	  $review['review_timestamp'] = date('Y-m-d 00:00:00', strtotime($review['review_timestamp']));
  	  $review['reviewer_ip'] = '999.999.999.999';
  	  $review['review_category'] = 'Tree Service';
  	  if (isset($review['rating'])) {
  			$post_rating_1 = array();
  			$post_rating_1['rating_value'] = $review['rating'];
  			$post_rating_1['rating_tag'] = 'Overall Rating';
  	  }

      // actually do the importing

			$newdata = array(
					'date_time'       => $review['review_timestamp'],
					'reviewer_name'   => $review['name'],
					'reviewer_email'  => $review['reviewer_email'],
					'review_title'    => $review['title'],
					'review_rating'   => intval($review['rating']),
					'review_text'     => $review['review_text'],
					'review_status'   => 1, // for now always approved
					'reviewer_ip'     => $review['reviewer_ip'],
					'post_id'		      => 0, // we are not reviewing posts
					'review_category' => $review['review_category']
			);
      $dbresult = $wpdb->insert($wpdb->prefix.'richreviews', $newdata);
      $dberror = '';
      if(!$dbresult){
        $dberror = $wpdb->last_error();
      }

			// Success message.
      // TODO anything to warn per review import and set as pending
			if ( true ) {
				$review_message_type = 'success';
				$review_message      = esc_html__( 'Review Number '.($review_index+1).' Imported and Approved', 'rich-reviews-importer' );
			} else {
				$review_message_type = 'warning';
				$review_message      = esc_html__( 'Review Number '.($review_index+1).' Imported and Pending', 'rich-reviews-importer' );
			}

		}

		// Result for review instance
		$results[ $review_index ]['name'] = $review['name']; // reviewer name
		$results[ $review_index ]['title']        = ! empty( $review['title'] ) ? $review['title'] : esc_html__( 'No City State', 'rich-reviews-importer' ); // Show "No City State" if review instance did not include it.
		$results[ $review_index ]['message_type'] = $review_message_type;
		$results[ $review_index ]['message']      = $review_message;
    $results[ $review_index ]['insert']       = $dbresult;
    $results[ $review_index ]['insert_error'] = $dberror;
    $results[ $review_index ]['insert_id']    = $wpdb->insert_id;
	}

  //print_r($results);

  //die();

	// Hook after import.
	do_action( 'rri_after_import' );

	// Return results.
	return apply_filters( 'rri_import_results', $results );

}
