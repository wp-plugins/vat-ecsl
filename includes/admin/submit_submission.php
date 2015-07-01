<?php

/**
 * ECSL Functions to perform submissions
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

/**
 * Sends a submission to HMRC and handles any errors
 *
 * @int id The id of the submission being sent
 */
function submit_submission($id)
{
	if (!current_user_can('send_submissions'))
	{
		echo "<div class='error'><p>" . __('You do not have rights to submit an EC Sales List (VAT101)', 'vat_ecsl' ) . "</p></div>";
		show_submissions();
		return;
	}

	/**
		array(
			'SubmittersReference' =>  '001',
			'CountryCode' =>  'ES',
			'CustomerVATRegistrationNumber' =>  'A44014884',
			'TotalValueOfSupplies' =>  '2000',
			'TransactionIndicator' => '2'
		)
	 */

	$post = get_post($id);
	if ($post->post_status === STATE_SUBMITTED)
	{
		echo "<div class='updated'><p>" . __('This information has already been submitted', 'vat_ecsl' ) . "</p></div>";
		show_submissions();
		return;		
	}
	$selected		= maybe_unserialize(get_post_meta($id, 'ecslsales', true));
	$vat_records	= vat_ecsl()->integrations->get_vat_record_information($selected);

	if (!$vat_records || !is_array($vat_records) || !isset($vat_records['status']))
	{
		report_errors( array( __('There was an error creating the information to generate a submission request.', 'vat_ecsl' ) ) );
	}
	else if ($vat_records['status'] === 'error')
	{
		report_errors( $vat_records['messages'] );
	}
	else
	{
		$ecsl_lines = array();

		$vat_payments = vat_ecsl()->integrations->flatten_vat_information($vat_records['information']);

		foreach($vat_payments as $key => $payment)
		{
			$ecsl_lines[] = array(
				'SubmittersReference' =>  "{$payment['source']}-{$payment['id']}",
				'CountryCode' =>  substr( $payment['vrn'], 0, 2 ),
				'CustomerVATRegistrationNumber' =>  substr( $payment['vrn'], 2 ),
				'TotalValueOfSupplies' => floor( $payment['value'] ),
				'TransactionIndicator' => $payment['indicator']
			);
		}
		
//		error_log(print_r($ecsl_lines,true));

		$sender_id			= get_post_meta( $id, 'sender_id',			true );
		$password			= get_post_meta( $id, 'password',			true );

		$vrn				= get_post_meta( $id, 'vat_number',			true );
		$submitter			= get_post_meta( $id, 'submitter',			true );
		$email				= get_post_meta( $id, 'email',				true );
		$branch				= get_post_meta( $id, 'branch',				true );
		$postcode			= get_post_meta( $id, 'postcode',			true );
		$submission_period	= get_post_meta( $id, 'submission_period',	true );
		$submission_year	= get_post_meta( $id, 'submission_year',	true );
		$period				= vat_ecsl()->settings->get('period') === 'monthly' ? 'month' : 'quarter' ;
		$submission_key		= get_post_meta( $id, 'submission_key',		true );
		$test_mode			= empty($submission_key)
			? true
			: get_post_meta( $id, 'test_mode',			true );

		$server_type = apply_filters( 'ecsl_server_type', 'live' );

		$data = array(
			'senderid' => $sender_id,
			'password' => $password,
			'test_mode' => $test_mode,
			'edd_action' => 'submit_ecsl',
			'vrn' => apply_filters( 'ecsl_vrn', $vrn, $server_type ),
			'branch' => $branch,
			'postcode' => apply_filters( 'ecsl_postcode', $postcode, $server_type ),
			'submitter' => $submitter,
			'email' => $email,
			'vendorid' => VAT_ECSL_VENDOR_ID,
			'productname' => VAT_ECSL_PRODUCT_NAME,
			'productversion' => VAT_ECSL_VERSION,
			'strict' => true,
			'taxperiod' => $period,
			'submission_period' => $submission_period,
			'submission_year' => $submission_year,
			'europeansales' => $ecsl_lines
		);
		
		if ($submission_key) $data['submission_key'] = $submission_key;
		
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' => $data,
			'cookies' => array()
		);

		process_response( $id, $args );
	}
	
	show_submissions();
	return;
}

function process_response($id, $args)
{
	$json = remote_get_handler( wp_remote_post( VAT_ECSL_STORE_API_URL, $args ) );
	$error = "";
	$result = json_decode($json);

	// switch and check possible JSON errors
	switch (json_last_error()) {
		case JSON_ERROR_NONE:
			$error = ''; // JSON is valid
			break;
		case JSON_ERROR_DEPTH:
			$error = 'Maximum stack depth exceeded.';
			break;
		case JSON_ERROR_STATE_MISMATCH:
			$error = 'Underflow or the modes mismatch.';
			break;
		case JSON_ERROR_CTRL_CHAR:
			$error = 'Unexpected control character found.';
			break;
		case JSON_ERROR_SYNTAX:
			$error = 'Syntax error, malformed JSON.';
			break;
		// only PHP 5.3+
		case JSON_ERROR_UTF8:
			$error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
			break;
		default:
			$error = 'Unknown JSON error occured.';
			break;
	}

	if($error !== '') {
		report_severe_error( $id, $result, $error );
	}
	else if (!is_object( $result ))
	{
		report_severe_error( $id, $result, "The response from the request to process the submission is not an array and this should never happen." );
	}
	else if(!isset( $result->status ))
	{
		report_severe_error(  $id, $result, "The response from the request to process the submission is an array but it does not contain a 'status' element" );
	}
	else
	{
		// The sources of error are:
		//	the failure to complete the wp_remote_post ('status' === 'failed' + 'message')
		//	an error processing the post (e.g. missing request data) ('status' === 'error' + 'message')
		//	an error reported by the gateway ('status' === 'success' + 'error_message' in the submission log)
		error_log(print_r($result,true));
		
		if (true)
		{
			if ( $result->status === 'failed' )
			{
				report_severe_error( $id, $result, isset( $result->message ) ? $result->message : "An error posting the submission has occurred but the reason is unknown" );
			}
			else
			{
				if ($result->status === 'error'  )
				{
					report_severe_error( $id, $result, isset( $result->message ) ? $result->message : "An error has occurred validating the submission on the remote server but the reason is unknown" );
				}
				if ($result->status !== 'valid' && $result->status !== 'success'  ) // Licence issue
				{
					report_severe_error( $id, $result, isset( $result->message ) ? $result->message : "An error has occurred validating the license key" );
				}
				else
				{
					process_submission_status( isset( $result->state ) ? $result->state : STATE_FAILED);
// error_log("Process submission");
// error_log(print_r($result,true));
					// Copy the results of the 'submission' and 'submission_log' arrays to posts on this site
					$submission_log_id = wp_insert_post(
						array(
							'post_title'	=> isset( $result->submission_log->title ) ? $result->submission_log->title : "Submission error log ($id)",
							'post_type'		=> 'ecsl_submission_log',
							'post_status'	=> property_exists( $result, 'state' ) ? $result->state : STATE_FAILED,
							'post_parent'	=> $id,
							'post_content'	=> isset( $result->submission_log->content ) ? $result->submission_log->content : ""
						)
					);

					if ( property_exists( $result, 'submission_log' ) )
					{
						if (property_exists( $result->submission_log, 'error_information' ) )
							update_post_meta( $submission_log_id,	'error_information',		$result->submission_log->error_information );
						if (property_exists( $result->submission_log, 'error_message' ) )
							update_post_meta( $submission_log_id,	'error_message',			$result->submission_log->error_message);
						if (property_exists( $result->submission_log, 'xml_initial_request' ) )
							update_post_meta( $submission_log_id,	'xml_initial_request',		$result->submission_log->xml_initial_request );
						if (property_exists( $result->submission_log, 'xml_initial_response' ) )
							update_post_meta( $submission_log_id,	'xml_initial_response',		$result->submission_log->xml_initial_response );
						if (property_exists( $result->submission_log, 'xml_final_request' ) )
							update_post_meta( $submission_log_id,	'xml_final_request',		$result->submission_log->xml_final_request );
						if (property_exists( $result->submission_log, 'correlation_id' ) )
							update_post_meta( $submission_log_id,	'correlation_id',			$result->submission_log->correlation_id );
						if (property_exists( $result->submission_log, 'result' ) )
							update_post_meta( $submission_log_id,	'result',					$result->submission_log->result );
					}

					wp_update_post( array(
						'ID'				=> $id,
						'post_status'		=> property_exists( $result, 'state' ) ? $result->state : STATE_FAILED,
						'post_modified'		=> date('Y-m-d H:i:s'),
						'post_modified_gmt'	=> gmdate('Y-m-d H:i:s')
					));

					if ( property_exists( $result, 'submission' ) )
					{
						if (property_exists( $result->submission,	'endpoint' ) )
							update_post_meta( $id,					'endpoint',					$result->submission->endpoint );
						if (property_exists( $result->submission,	'submission_due_date' ) )
							update_post_meta( $id,					'submission_due_date',		$result->submission->submission_due_date );
						if (property_exists( $result->submission,	'totallines' ) )
							update_post_meta( $id,					'totallines',				$result->submission->totallines );
						if (property_exists( $result->submission,	'totalvalue' ) )
							update_post_meta( $id,					'totalvalue',				$result->submission->totalvalue );
						if (property_exists( $result->submission,	'endpoint' ) )
							update_post_meta( $id,					'endpoint',					$result->submission->endpoint );
						if (property_exists( $result->submission,	'correlation_id' ) )
							update_post_meta( $id,					'correlation_id',			$result->submission_log->correlation_id );
					}
				}
			}
		}
	}
}

function report_severe_error($submission_id, $result, $message)
{
	if (is_array($message))
		$message = implode('<br/>', $message);

	report_errors( "Severe error. $message" );

	// Create a log post of the submission
	$submission_log_id = wp_insert_post(
		array(
			'post_title'	=> "Submission log ($submission_id)",
			'post_type'		=> 'ecsl_submission_log',
			'post_status'	=> STATE_FAILED,
			'post_parent'	=> $submission_id
		 )
	);

	update_post_meta( $submission_log_id, 'error_information', serialize($result) );
	update_post_meta( $submission_log_id, 'error_message', $message );

	wp_update_post( array(
		'ID'				=> $submission_id,
		'post_status'		=> STATE_FAILED,
		'post_modified'		=> date('Y-m-d H:i:s'),
		'post_modified_gmt'	=> gmdate('Y-m-d H:i:s')
	));
}

function process_submission_status($submission_state)
{
	switch($submission_state)
	{
		case STATE_ACKNOWLEDGED:
			echo "<div class='updated'><p>" . __('The attempt to submit the EC Sales List has been acknowledged but is not yet complete. Try again later to check the posting status.', 'vat_ecsl' ) . "</p></div>";
			break;
			
		case STATE_SUBMITTED:
			echo "<div class='updated'><p>" . __('The EC Sales List submission has been successful.', 'vat_ecsl' ) . "</p></div>";
			break;
			
		default:
			echo "<div class='error'><p>" . __('The attempt to submit the EC Sales List failed. See the log for more information.', 'vat_ecsl' ) . "</p></div>";
			break;
	}
}

function format_xml($xml)
{
	$domxml = new \DOMDocument('1.0');
	$domxml->preserveWhiteSpace = false;
	$domxml->formatOutput = true;
	$domxml->loadXML($xml);
	return $domxml->saveXML();
}

function remote_get_handler($response, $message = 'Error processing submission')
{
	if (is_a($response,'WP_Error'))
	{
		error_log(print_r($response,true));
		$error = array(
			'status' => 'failed',
			'message' => $response->get_error_message()
		);

		return json_encode($error);
	}
	else
	{
		$code = isset( $response['response']['code'] ) && isset( $response['response']['code'] )
			? $response['response']['code']
			: 'Unknown';

		if ( $code == 200 && isset( $response['body'] ))
		{
			return $response['body'];
		}
		else
		{
			$error = array(
				'status' => 'failed',
				'message' => "$message ($code)"
			);

			return json_encode($error);
		}
	}
}

function check_submission($id)
{
	error_log("check_submission");

	if (!current_user_can('send_submissions'))
	{
		echo "<div class='error'><p>" . __( 'You do not have rights to submit an EC Sales List (VAT101)', 'vat_ecsl' ) . "</p></div>";
		return;
	}

	// The submission must be acknowledged and there must be a correlation_id
	$post = get_post($id);
	if (!$post)
	{
		echo "<div class='error'><p>" . __( 'The submission id is invalid', 'vat_ecsl' ) . "</p></div>";
		return;
	}
	
	if (is_a($post, 'WP_Error'))
	{
		echo "<div class='error'><p>" . __( 'An error occurred retrieving the submission.  The error is: ', 'vat_ecsl' ) . $post->get_error_message() . "</p></div>";
		return;
	}

	$correlationid = get_post_meta($id, 'correlation_id', true);
	$status = $post->post_status;

	if (empty($correlationid))
	{
		echo "<div class='error'><p>" . __( 'A correlation id does not exist but one if required for this action.', 'vat_ecsl' ) . "</p></div>";
		return;
	}

	if ($status !== STATE_ACKNOWLEDGED)
	{
		echo "<div class='error'><p>" . __( 'The status of the submission must be \'Acknowledged\' to use this action.', 'vat_ecsl' ) . "</p></div>";
		return;
	}

	$sender_id		= get_post_meta( $id, 'sender_id',		true );
	$password		= get_post_meta( $id, 'password',		true );
	$end_point		= get_post_meta( $id, 'endpoint',		true );
	$submission_key	= get_post_meta( $id, 'submission_key',	true );
	$test_mode			= empty($submission_key)
		? true
		: get_post_meta( $id, 'test_mode',			true );

	$data = array(
		'senderid'			=> $sender_id,
		'password'			=> $password,
		'end_point'			=> $end_point,
		'test_mode'			=> $test_mode,
		'correlation_id'	=> $correlationid,
		'submission_key'	=> $submission_key,
		'edd_action'		=> 'check_ecsl'
	);
		
	if ($submission_key) $data['submission_key'] = $submission_key;
		
	$args = array(
		'method' => 'POST',
		'timeout' => 45,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking' => true,
		'headers' => array(),
		'body' => $data,
		'cookies' => array()
	);

	process_response( $id, $args );

	return;	
}

?>
