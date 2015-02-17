<?php

/**
 * ECSL Save submission definition
 *
 * @package     vat-ecsl
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

namespace lyquidity\vat_ecsl;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function save_submission()
{
	error_log("Save Submission");

	$submission_id = isset($_REQUEST['submission_id']) ? $_REQUEST['submission_id'] : 0;

	if ( !isset($_REQUEST['_wp_nonce']) ||
		 !wp_verify_nonce( $_REQUEST['_wp_nonce'], 'ecsl_submission' ) )
	{
		echo "<div class='error'><p>" . __('The attempt to save the submission is not valid.  The nonce does not exist or cannot be verified.', 'vat_ecsl' ) . "</p></div>";
		if ($submission_id)
			edit_submission($submission_id);
		else
			new_submission();
		return;
	}

 	if (!isset($_REQUEST['ecslsale']))
	{
		echo "<div class='error'><p>" . __('There are no selected sales from which to create a submission', 'vat_ecsl' ) . "</p></div>";
		if ($submission_id)
			edit_submission($submission_id);
		else
			new_submission();
		return;
	}

	global $selected;
	$selected = array();

	foreach(array_keys( ECSL_WP_Integrations::get_enabled_integrations() ) as $integration)
	{
		if (!isset($_REQUEST['ecslsale'][$integration])) continue;

		$sales = array();

		foreach($_REQUEST['ecslsale'][$integration] as $key => $value)
		{
			$sales[$value] = $value;
		}

	//	error_log(print_r($sales,true));
		
		$selected[$integration] = $sales;
	}

	// Grab the post information
	$test_mode	= isset($_REQUEST['test_mode'])	? $_REQUEST['test_mode'] : 0;
	$vrn		= isset($_REQUEST['ecsl_settings_vat_number'])	? $_REQUEST['ecsl_settings_vat_number']	: vat_ecsl()->settings->get('vat_number');
	$submitter	= isset($_REQUEST['ecsl_settings_submitter'])	? $_REQUEST['ecsl_settings_submitter']	: vat_ecsl()->settings->get('submitter');
	$email		= isset($_REQUEST['ecsl_settings_email'])		? $_REQUEST['ecsl_settings_email']		: vat_ecsl()->settings->get('email');
	$branch		= isset($_REQUEST['ecsl_settings_branch'])		? $_REQUEST['ecsl_settings_branch']		: vat_ecsl()->settings->get('branch');
	$postcode	= isset($_REQUEST['ecsl_settings_postcode'])	? $_REQUEST['ecsl_settings_postcode']	: vat_ecsl()->settings->get('postcode');
	$sender_id	= isset($_REQUEST['ecsl_settings_sender_id'])	? $_REQUEST['ecsl_settings_sender_id']	: vat_ecsl()->settings->get('sender_id');
	$password	= isset($_REQUEST['ecsl_settings_password'])	? $_REQUEST['ecsl_settings_password']	: vat_ecsl()->settings->get('password');
	$title		= isset($_REQUEST['ecsl_settings_title'])		? $_REQUEST['ecsl_settings_title']		: vat_ecsl()->settings->get('title');
	$totalvalue = 'n/a';
	$totallines = 'n/a';

	$from_year	= isset( $_REQUEST[ 'from_year'  ] )	? $_REQUEST[ 'from_year' ]	: date('Y');
	$from_month	= isset( $_REQUEST[ 'from_month' ] )	? $_REQUEST[ 'from_month' ]	: date('m');
	$to_year	= isset( $_REQUEST[ 'to_year'    ] )	? $_REQUEST[ 'to_year' ]	: date('Y');
	$to_month	= isset( $_REQUEST[ 'to_month'   ] )	? $_REQUEST[ 'to_month' ]	: date('m');

	$submission_period	= isset($_REQUEST['submission_period'])	? $_REQUEST['submission_period']	: floor((date('n') - 1) / 3) + 1;
	$submission_year	= isset($_REQUEST['submission_year'])	? $_REQUEST['submission_year']		: date('Y');
	$submission_key		= isset($_REQUEST['submission_key'])	? $_REQUEST['submission_key']		: '';

	if ($submission_id)
	{
		// Begin by deleting the records associated with the submission
		if (!delete_submission($submission_id, false))
		{
			edit_submission($submission_id);
			return;
		}

		wp_update_post(
			array(
				'ID'				=> $submission_id,
				'post_title'		=> $title,
				'post_modified'		=> date('Y-m-d H:i:s'),
				'post_modified_gmt'	=> gmdate('Y-m-d H:i:s')
			 )
		);
	}
	else
	{
		// Create a post 
		$submission_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_type'	   => 'ecsl_submission',
				'post_content' => '',
				'post_status'  => STATE_NOT_SUBMITTED
			 )
		);

		add_post_meta( $submission_id, 'totalvalue',	$totalvalue	);
		add_post_meta( $submission_id, 'totallines',	$totallines	);
	}

	if ($test_mode)
		update_post_meta( $submission_id, 'test_mode',		1					);
	else
		delete_post_meta( $submission_id, 'test_mode' 							);

	update_post_meta( $submission_id, 'vat_number',			$vrn				);
	update_post_meta( $submission_id, 'submitter', 			$submitter			);
	update_post_meta( $submission_id, 'email',				$email				);
	update_post_meta( $submission_id, 'branch',				$branch				);
	update_post_meta( $submission_id, 'postcode',			$postcode			);

	update_post_meta( $submission_id, 'sender_id',			$sender_id			);
	update_post_meta( $submission_id, 'password',			$password			);

	update_post_meta( $submission_id, 'from_year',			$from_year			);
	update_post_meta( $submission_id, 'from_month',			$from_month			);
	update_post_meta( $submission_id, 'to_year',			$to_year			);
	update_post_meta( $submission_id, 'to_month',			$to_month			);

	update_post_meta( $submission_id, 'submission_period',	$submission_period	);
	update_post_meta( $submission_id, 'submission_year',	$submission_year	);

	update_post_meta( $submission_id, 'ecslsales',		serialize($selected)	);

	// Update the sales records
	$errors = vat_ecsl()->integrations->update_vat_information($submission_id, '', $selected);
	if ($errors)
	{
		report_errors($errors);

		// If there were errors rollback the update
		error_log("delete_submission");
		delete_submission($submission_id);
		new_submission();
		return;
	}

	if ($submission_key)
		update_post_meta( $submission_id, 'submission_key',	$submission_key		);
	else
		delete_post_meta( $submission_id, 'submission_key'						);

	$message = __( "Submission details saved", 'vat_ecsl' );
	echo "<div class='updated'><p>$message</p></div>";

			if ($submission_id)
		edit_submission($submission_id);
	else
		show_submissions();
}

 ?>