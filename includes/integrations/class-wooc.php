<?php

/**
 * ECSL WOO Commerce integration
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

class ECSL_Integration_WOOC extends ECSL_Integration_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->source = 'wooc';
		$this->name = 'Woo Commerce';
		$this->post_type = 'product';

		$instance = $this;
		add_action( 'ecsl_integration_instance', function( $instance_array ) use($instance)
		{
			if (function_exists('WC'))
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
			'key'		=> '_completed_date',
			'value'		=> array($startDate, $endDate),
			'compare'	=> 'BETWEEN',
			'type'		=> 'DATE'
		);
		$meta_query[] = array(
			'key'		=> '_order_tax',
			'value'		=> 0,
			'compare'	=> '='
		);
		
/*
		$meta_query[] = array(
			'key'		=> 'VAT Number',
			'compare'	=> 'EXISTS'
		);
		$meta_query[] = array(
			'key'		=> 'Valid EU VAT Number',
			'value'		=> 'true',
			'compare'	=> '='
		);
 */
		
		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'relation' => 'AND',
				array(
					'key'		=> 'VAT Number',
					'compare'	=> 'EXISTS'
				),
				array(
					'key'		=> 'Valid EU VAT Number',
					'value'		=> 'true',
					'compare'	=> '='
				)
			),
			array(
				'relation' => 'AND',
				array(
					'key'		=> 'vat_number',
					'compare'	=> 'EXISTS'
				),
				array(
					'key'		=> 'vat_number',
					'value'		=> ' ',
					'compare'	=> '!='
				)
			)
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
			'post_type' 		=> 'shop_order',
			'posts_per_page' 	=> -1,
			'fields'			=> 'ids',
			'post_status'		=> array( 'wc-completed' ),
			'orderby'			=> array( 'meta_value_num' => 'ASC' ),
			'meta_query'		=> $meta_query
		);

		$payments = new \WP_Query( $args );
		$vat_payments = array();

		if( $payments->posts )
		{
			$eu_states = array_flip( WordPressPlugin::$eu_states );

			foreach( $payments->posts as $payment_id ) {

				$purchase_key		= get_post_meta( $payment_id, '_order_key',				true );
				$vrn				= get_post_meta( $payment_id, 'VAT Number',				true );
				if (empty($vrn))
					$vrn			= get_post_meta( $payment_id, 'vat_number',				true );
				$date				= get_post_meta( $payment_id, '_completed_date',		true );
				$submission_id		= get_post_meta( $payment_id, 'ecsl_submission_id', 	true );
				$billing_first_name	= get_post_meta( $payment_id, '_billing_first_name',	true );
				$billing_last_name	= get_post_meta( $payment_id, '_billing_last_name', 	true );
				$order_total		= get_post_meta( $payment_id, '_order_total',			true );
				$currency_code		= get_post_meta( $payment_id, '_order_currency',		true );
				if (!$currency_code)
					$currency_code = "GBP";

				// Should exclude VAT numbers starting with GB
				$country_code = substr($vrn, 0, 2);
				if ($country_code === 'GB') continue;
				if (!isset($eu_states[$country_code])) continue;

				$vat_payment = array();

				$vat_payment['id']				= $payment_id;
				$vat_payment['vrn']				= preg_replace('/\s+/', '', $vrn);
				$vat_payment['purchase_key']	= $purchase_key;
				$vat_payment['date']			= $date;
				$vat_payment['submission_id']	= $submission_id;
				$vat_payment['buyer']			= sprintf("%1s %2s", $billing_first_name, $billing_last_name);
				$vat_payment['currency_code']	= $currency_code;

				$order = wc_get_order( $payment_id );
				$line_items = $order->get_items( 'line_item' );

				$values							= array();

				foreach ( $line_items as $item_id => $item ) {

					$product  = $order->get_product_from_item( $item );
					$indicator = \lyquidity\vat_ecsl\vat_indicator_to_use($product->id);
					$item_meta = $order->get_item_meta( $item_id );
					/*
						Each of these is an array
						_qty
						_tax_class
						_product_id
						_variation_id
						_line_subtotal
						_line_total
						_line_subtotal_tax
						_line_tax
						_line_tax_data
					 */

					$values[$indicator]		= ( isset($values[$indicator]) ? $values[$indicator] : 0 ) + apply_filters( 'ecsl_get_transaction_amount', array_sum($item_meta['_line_subtotal']), $order_total, $payment_id);
				}

				$vat_payment['values']			= $values;
				$vat_payments[]					= $vat_payment;
			}
		}
		
// error_log(print_r($vat_payments,true));
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
		
			$vrn				= get_post_meta( $id, 'VAT Number',				true );
			$order_tax			= get_post_meta( $id, '_order_tax',			 	true );
			
			$purchase_key		= get_post_meta( $id, '_order_key',				true );
			$date				= get_post_meta( $id, '_completed_date',		true );
			$order_total		= get_post_meta( $id, '_order_total',			true );
			$vat_paid			= get_post_meta( $id, 'vat_compliance_vat_paid',true );
			$currency_code		= get_post_meta( $id, '_order_currency',		true );
			if (!$currency_code)
				$currency_code = "GBP";
			
			$order = wc_get_order( $id );
			$line_items = $order->get_items( 'line_item' );

			$vat_payment = array();

			$vat_payment['id']				= $id;
			$vat_payment['vrn']				= $vrn;
			$vat_payment['purchase_key']	= $purchase_key;
//			$vat_payment['indicator']		= '3';
			$vat_payment['value']			= apply_filters( 'ecsl_get_transaction_amount', $order_total, $id  );
			$vat_payment['date']			= $date;
//			$vat_payment['values']			= array( '3' => apply_filters( 'ecsl_get_transaction_amount', $order_total, $id  ) );
			$vat_payment['currency_code']	= $currency_code;

			$values = array();

			foreach ( $line_items as $item_id => $item ) {

				$_product  = $order->get_product_from_item( $item );
				$indicator = \lyquidity\vat_ecsl\vat_indicator_to_use($_product->id);

				$item_meta = $order->get_item_meta( $item_id );
				/*
					Each of these is an array
					_qty
					_tax_class
					_product_id
					_variation_id
					_line_subtotal
					_line_total
					_line_subtotal_tax
					_line_tax
					_line_tax_data
				 */

				$values[$indicator]		= ( isset($values[$indicator]) ? $values[$indicator] : 0 ) + apply_filters( 'ecsl_get_transaction_amount', array_sum($item_meta['_line_subtotal']), $order_total, $id);
			}

			$vat_payment['values']			= $values;

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
new ECSL_Integration_WOOC;
