<?php

/**
 * ECSL Dummy source integration
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

class ECSL_Integration_Dummy extends ECSL_Integration_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->source = 'dummy';
		$this->name = 'Dummy (Lyquidity Solutions)';
		$instance = $this;
		add_action( 'ecsl_integration_instance', function( $instance_array ) use($instance)
		{
			$instance_array[$instance->source] = $instance;
			return $instance_array;
		}, 10 );

	}

	/**
	 * Returns an array of VAT information:
	 *	id				Database id for the sale
	 *	purchase_key	Unique purchase identifier
	 *  vrn				The VAT number of the buyer
	 *	date			DateTime of the completed transaction
	 *	correlation_id	Existing correlation_id (if any)
	 *	buyer			The name of the buyer
	 *	values			An array of sale values before any taxes indexed by the indicator.  
	 *						0: Goods, 2: Triangulated sale, 3: Services (reverse charge)
	 *						Values with the same indicator will be accumulated
	 *
	 * If you have more than one sale to a client the value of those sales can be aggregated
	 * If the sale is across two service types (different indicators) then they need to appear as different entries
	 *
	 * @string	startDate				strtotime() compatible date of the earliest record to return
	 * @string	endDate					strtotime() compatible date of the latest record to return
	 * @boolean	includeSubmitted		True is the results should include previously submitted records (submission_id does not exist in meta-data)
	 * @boolean	includeSubmittedOnly	True if the results should only include selected items
	 */
	public function get_vat_information($startDate, $endDate, $includeSubmitted = false, $includeSubmittedOnly = false)
	{
		$vat_payments = json_decode(file_get_contents( VAT_ECSL_INCLUDES_DIR . 'integrations/test-data.json' ) );

		$vat_payments = array_filter($vat_payments, function($payment) use ($startDate, $endDate, $includeSubmitted, $includeSubmittedOnly )
		{
			return	( strtotime( $payment->date ) >= strtotime( $startDate ) ) &&
					( strtotime( $payment->date ) <= strtotime( $endDate ) ) &&
					( $includeSubmitted && $payment->submission_id !== 0 || ( !$includeSubmittedOnly && $payment->submission_id == 0 ) );
		});

		return array_map( function($payment)
		{
			return array(
				'id'				=> $payment->id,
				'purchase_key'		=> $payment->purchase_key,
				'vrn'				=> $payment->vrn,
				'date'				=> $payment->date,
				'submission_id'		=> isset($payment->submission_id) ? $payment->submission_id : 0,
				'currency_code'		=> isset($payment->currency_code) ? $payment->currency_code : 'GBP',
				'values'			=> array( $payment->indicator => apply_filters( 'ecsl_get_transaction_amount', $payment->value, $payment->id ) )
			);
		}, $vat_payments );

		return $vat_payments;
	}

	/**
	 * Called by the integration controller to allow the integration to update sales records with
	 *
	 * @int submission_id The id of the ECSL submission that references the sale record
	 * @string correlation_id The HMRC generated correlation_id of the submission in which the sales record is included
	 * @array ids An array of sales record ids
	 *
	 * @return An error message, an array of messages or FALSE if every thing is OK
	 */
	function update_vat_information($submission_id, $correlation_id, $ids)
	{
		if (!$submission_id || !is_numeric($submission_id))
		{
			return __('The submission id is not valid', 'vat_ecsl');
		}

		if (!$ids || !is_array($ids))
		{
			return __('The VAT sales records passed are not an array', 'vat_ecsl');
		}
		
		try
		{
			$vat_payments = json_decode(file_get_contents( VAT_ECSL_INCLUDES_DIR . 'integrations/test-data.json' ) );

			foreach($vat_payments as $key => $payment)
			{
				if (!isset($ids[$payment->id])) continue;

				$payment->submission_id = $submission_id;

				if (!empty($correlation_id))
					$payment->correlation_id = $correlation_id;
			}

			file_put_contents( VAT_ECSL_INCLUDES_DIR . 'integrations/test-data.json', json_encode($vat_payments) );
		}
		catch(Exception $ex)
		{
			return array(__('An error occurred updating ECSL sales record meta data', 'vat_ecsl'), $ex->getMessage());
		}

		return false;
	}
	
	/**
	 * Called to allow the integration to retrieve information from specific records
	 *
	 * @array source_ids An array of sources and record ids
	 *
	 * @return An error message, an array of messages or of payments if everything is OK
	 *
	 * array(
	 *	'status' => 'success',
	 *	'information' => array(
	 *		'id'			=> 0,
	 *		'vrn'			=> 'GB123456789',
	 *		'date'			=> '...',
	 *		'submission_id'	=> 0,
	 *		'purchase_key'	=> '...',
	 *		'values'		=> array(
	 *							  'indicator' (0|2|3) => sale amounts accumulated
	 *						   )
	 *	)
	 * )
	 *
	 * array(
	 *	'status' => 'error',
	 *	'messages' => array(
	 *		'',
	 *		''
	 *	)
	 * )
	 */
	function get_vat_record_information($source_ids)
	{
		if (!is_array($source_ids))
		{
			return array('status' => 'error', 'messages' => array( __( 'Invalid source', 'vat_ecsl' ) ) );
		}

		$vat_payments = json_decode(file_get_contents( VAT_ECSL_INCLUDES_DIR . 'integrations/test-data.json' ) );
		$vat_payments = array_filter( $vat_payments, function($payment) use($source_ids)
		{
			return isset($source_ids[$payment->id]);
		});

		$vat_payments = array_map( function($payment)
		{
			return array(
				'id'				=> $payment->id,
				'purchase_key'		=> $payment->purchase_key,
				'vrn'				=> $payment->vrn,
				'date'				=> $payment->date,
				'submission_id'		=> isset($payment->submission_id) ? $payment->submission_id : 0,
				'currency_code'		=> isset($payment->currency_code) ? $payment->currency_code : 'GBP',
				'values'			=> array( $payment->indicator => apply_filters( 'ecsl_get_transaction_amount', $payment->value, $payment->id ) )
			);
		}, $vat_payments);

		return array( 'status' => 'success', 'information' => $vat_payments );
	}

	/**
	 * Called by the integration controller to remove ECSL submission references for a set of post ids
	 *
	 * @array ids An array of sales record ids
	 *
	 * @return An error message, an array of messages or FALSE if every thing is OK
	 */
	 function delete_vat_information($ids)
	 {
		if (!$ids || !is_array($ids))
		{
			return __("The VAT sales records passed are not an array", 'vat_ecsl');
		}

		try
		{
			$vat_payments = json_decode(file_get_contents( VAT_ECSL_INCLUDES_DIR . 'integrations/test-data.json' ) );

			$vat_payments = array_map(function($payment) use($ids)
			{
				if (isset($ids[$payment->id]))
				{
					$payment->submission_id = 0;					
				}
				unset($payment->correlation_id);

				return $payment;
			}, $vat_payments);

			file_put_contents( VAT_ECSL_INCLUDES_DIR . 'integrations/test-data.json', json_encode($vat_payments) );
		}
		catch(Exception $ex)
		{
			return array(__('An error occurred deleting ECSL sales record meta data', 'vat_ecsl'), $ex->getMessage());
		}
		
	 }
}
new ECSL_Integration_Dummy;
