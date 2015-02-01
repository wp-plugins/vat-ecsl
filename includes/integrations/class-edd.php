<?php

/**
 * ECSL Easy Digital Downloads Integration
 *
 * @package     vat-ecsl
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */
 
namespace lyquidity\vat_ecsl;

class ECSL_Integration_EDD extends ECSL_Integration_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->source = 'edd';
		$this->name = 'Easy Digital Downloads';
		$this->post_type = 'download';
		$instance = $this;
		add_action( 'ecsl_integration_instance', function( $instance_array ) use($instance)
		{
			if (function_exists('EDD'))
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
		$meta_query = array();
		$meta_query[] = array(
			'key'		=> '_edd_completed_date',
			'value'		=> array($startDate, $endDate),
			'compare'	=> 'BETWEEN',
			'type'		=> 'DATE'
		);

		if (!$includeSubmitted)
		{
			$meta_query[] = array(
				'key'     => 'ecsl_submission_id',
				'compare' => 'NOT EXISTS'
			);
		}

		else if ($includeSubmittedOnly)
		{
			$meta_query[] = array(
				'key'     => 'ecsl_submission_id',
				'compare' => 'EXISTS'
			);
		}

		$args = array(
			'post_type' 		=> 'edd_payment',
			'posts_per_page' 	=> -1,
			'fields'			=> 'ids',
			'post_status'		=> array( 'publish','edd_subscription' ),
			'orderby'			=> array( 'meta_value_num' => 'ASC' ),
			'meta_query'		=> $meta_query
		);

		$payments = new \WP_Query( $args );
		$vat_payments = array();

		if( $payments->posts )
		{
			$eu_states = array_flip( WordPressPlugin::$eu_states );

			foreach( $payments->posts as $payment_id ) {

				$payment_meta = edd_get_payment_meta( $payment_id );
				$user_info = maybe_unserialize( $payment_meta['user_info'] );

				// If there is no VAT number then the record does not apply
				if (empty($user_info['vat_number'])) continue;

				// Should exclude VAT numbers starting with GB
				$country_code = substr($user_info['vat_number'], 0, 2);
				if ($country_code === 'GB') continue;
				if (!isset($eu_states[$country_code])) continue;

				$vat_payment = array();

				$vat_payment['id']				= $payment_id;
				$vat_payment['vrn']				= $user_info['vat_number'];
				$vat_payment['purchase_key']	= get_post_meta( $payment_id, '_edd_payment_purchase_key', true);
				$vat_payment['date']			= get_post_meta( $payment_id, '_edd_completed_date', true);
				$vat_payment['submission_id']	= get_post_meta( $payment_id, 'ecsl_submission_id', true);
				$vat_payment['buyer']			= sprintf("%1s %2s", $user_info['first_name'], $user_info['last_name']);

				$values = array();

				foreach( $payment_meta['cart_details'] as $key => $item )
				{
					$indicator = \lyquidity\vat_ecsl\vat_indicator_to_use($item['id']);
					/*
						name
						id
						item_number (Array)	id, options (Array), quantity
						item_price
						quantity
						discount
						subtotal
						tax
						fees (Array)
						price
					 */

					$values[$indicator]		= ( isset($values[$indicator]) ? $values[$indicator] : 0 ) + apply_filters( 'ecsl_get_transaction_amount', $item['price'], $payment_id);
				}

				$vat_payment['values'] = $values;
				$vat_payments[] = $vat_payment;
			}
		}

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
			foreach($ids as $id => $value)
			{
				update_post_meta($id, 'ecsl_submission_id', $submission_id);

				if (!empty($correlation_id))
					update_post_meta($id, 'correlation_id', $submission_id);
			}
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

		$vat_payments = array();

		foreach( $source_ids as $key => $id ) {
		
			$payment_meta = edd_get_payment_meta( $id );
			$user_info = maybe_unserialize( $payment_meta['user_info'] );
			
			// If there is no VAT number then the record does not apply
			if (empty($user_info['vat_number'])) continue;

			// Should exclude VAT numbers starting with GB

			$vat_payment = array();

			$vat_payment['id']				= $id;
			$vat_payment['vrn']				= $user_info['vat_number'];
			$vat_payment['purchase_key']	= get_post_meta( $id, '_edd_payment_purchase_key', true);
			$vat_payment['submission_id']	= get_post_meta( $id, 'ecsl_submission_id', true);

			$values = array();

			foreach( $payment_meta['cart_details'] as $key => $item )
			{
				$indicator = \lyquidity\vat_ecsl\vat_indicator_to_use($item['id']);
				/*
					name
					id
					item_number (Array)	id, options (Array), quantity
					item_price
					quantity
					discount
					subtotal
					tax
					fees (Array)
					price
				 */

				$values[$indicator]		= ( isset($values[$indicator]) ? $values[$indicator] : 0 ) + apply_filters( 'ecsl_get_transaction_amount', $item['price'], $id);
			}

			$vat_payment['values'] = $values;
			$vat_payments[] = $vat_payment;
		}

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
			foreach($ids as $id => $value)
			{
				delete_post_meta($id, 'ecsl_submission_id');
				delete_post_meta($id, 'correlation_id');
			}
		}
		catch(Exception $ex)
		{
			return array(__('An error occurred deleting ECSL sales record meta data', 'vat_ecsl'), $ex->getMessage());
		}
		
	 }

}
new ECSL_Integration_EDD;
