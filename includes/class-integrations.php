<?php

/**
 * ECSL Type Functions
 *
 * @package     vat-ecsl
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @license     Lyquidity Commercial
 * @since       1.0
 */

namespace lyquidity\vat_ecsl;

class ECSL_WP_Integrations {

	public function __construct() {

		$this->load();

	}

	public static function get_integrations() {

		return apply_filters('ecsl_integration_instance', array());
	}

	public function get_integrations_list() {
		
		return array_reduce(
			ECSL_WP_Integrations::get_integrations(),
			function($carry, $instance)
			{
				$carry[$instance->source] = $instance->name;
				return $carry;
			}
		);
	}

	public static function get_enabled_integrations() {
		return vat_ecsl()->settings->get( 'integrations', array() );
	}

	public function get_post_types()
	{
		$result = array();

		foreach(ECSL_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_ecsl\ECSL_Integration_Base') || !isset($this->enabled_integrations[$integration->source])) continue;
			if (!isset($integration->post_type) || empty($integration->post_type)) continue;

			$post_type = $integration->post_type;
			$result[$integration->post_type] = $integration->post_type;
		}
		
		return $result;
	}

	public function load() {

		$integrations_dir = VAT_ECSL_INCLUDES_DIR . 'integrations/';

		// Load each enabled integrations
		require_once $integrations_dir . 'class-base.php';

		if ($handle = opendir($integrations_dir))
		{
			try
			{
				while (false !== ($file = readdir($handle)))
				{
					if ($file === "." || $file === ".." || $file === "class-base.php" || strpos($file, 'class') !== 0) continue;
					
					$filename  = $integrations_dir . $file;

					if(!is_file($filename)) continue;

					require_once $filename;
				}
			}
			catch(Exception $ex)
			{}

			closedir($handle);
		} 

		$this->enabled_integrations = apply_filters( 'ecsl_enabled_integrations', ECSL_WP_Integrations::get_enabled_integrations() );
	}

	/**
	 *	source			The 'context' identifier for the source
	 *	id				Database id for the sale
	 *	purchase_key	Unique purchase identifier (the id if separate unique id does not exist)
	 *	date			Datetime of the completed transaction
	 *	correlation_id	Existing correlation_id (if any)
	 *	indicator		0: Goods, 2: Triangulated sale, 3: Services (reverse charge)
	 *	buyer			The name of the buyer
	 *	value			Amount of sale before any taxes
	 *
	 * If you have more than one sale to a client the value of those sales can be aggregated
	 * If the sale is across two service types (different indicators) then they need to appear as different entries
	 *
	 * @string	startDate			strtotime() compatible date of the earliest record to return
	 * @string	endDate				strtotime() compatible date of the latest record to return.
	 * @boolean	includeSubmitted	True is the results should include previously submitted records (submission_id does not exist in meta-data)
	 * @boolean	includeSubmittedOnly	True if the results should only include selected items
	 */
	public function get_vat_information($startDate, $endDate, $includeSubmitted = false, $includeSubmittedOnly = false)
	{
		require_once(VAT_ECSL_INCLUDES_DIR . 'vatidvalidator.php');

		$vat_info = array();

		foreach(ECSL_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_ecsl\ECSL_Integration_Base') || !isset($this->enabled_integrations[$integration->source])) continue;
			
			if (!strtotime($startDate) || !strtotime($endDate))
				return;

			// Make sure the dates 
			$startDate = date('Y-m-d 00:00:00', strtotime($startDate));
			$endDate   = date('Y-m-d 23:59:59', strtotime($endDate));
			
			$vat_records = $integration->get_vat_information($startDate, $endDate, $includeSubmitted, $includeSubmittedOnly);
			
			foreach($vat_records as $key => $vat_record)
			{
				$out = new \StdClass;
				$vat_record['valid_vrn'] = perform_simple_check($vat_record['vrn'], $out, true);
				$vat_record['source'] = $integration->source;
				array_walk( $vat_record['values'], function(&$value, $key)
				{
					$value = floor( $value );
				});

				$vat_records[$key] = $vat_record;
			}

			$vat_info = array_merge($vat_info, $vat_records);
		}
		
		return $vat_info;
	}

	/**
	 * Called to allow the integration to update sales records with a submission_id and correlation_id
	 *
	 * @int submission_id The id of the ECSL submission that references the sale record
	 * @string correlation_id The HMRC generated correlation_id of the submission in which the sales record is included
	 * @array source_ids An array of sources and record ids
	 *
	 * @return An error message, an array of messages or FALSE if every thing is OK
	 */
	function update_vat_information($submission_id, $correlation_id, $source_ids)
	{
		$errors = FALSE;

		foreach(ECSL_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_ecsl\ECSL_Integration_Base') ||
				!isset($this->enabled_integrations[$integration->source])) continue;

			if (!isset($source_ids[$integration->source])) continue;

			$result = $integration->update_vat_information($submission_id, $correlation_id, $source_ids[$integration->source]);
			if (!$result) continue;
			
			if (!is_array($errors)) $errors = array();
			if (!is_array($result)) $result = array($result);

			array_unshift($result, sprintf( __("Errors occurred update VAT information for records in source '%s'", 'vat_ecsl'), $integration->source));
			$errors[$integration->source] = $result;			
		}
		
		return $errors;
	}

	/**
	 * Called to allow the integration to retrieve information from specific records
	 *
	 * @array source_ids An array of sources and record ids
	 *
	 * @return An error message, an array of messages or FALSE if everything is OK
	 *
	 * array(
	 *	'status' => 'success',
	 *	'information' => array(
	 *		'sourcexxx' = array(
	 *			'0' => array(
	 *				'id'			=> 0,
	 *				'vrn'			=> 'GB123456789',
	 *				'purchase_key'	=> '...',
	 *				'indicator'		=> 1|2|3,
	 *				'value'			=> ???
	 *			)
	 *		),
	 *		'sourceyyy' = array(
	 *			'0' => array(
	 *				'id'			=> 0,
	 *				'vrn'			=> 'GB123456789',
	 *				'purchase_key'	=> '...',
	 *				'indicator'		=> 1|2|3,
	 *				'value'			=> ???
	 *			)
	 *		)
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
	 *
	 *
	 */
	function get_vat_record_information($source_ids)
	{
		$information = array();

		foreach(ECSL_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_ecsl\ECSL_Integration_Base') ||
				!isset($this->enabled_integrations[$integration->source])) continue;

			if (!isset($source_ids[$integration->source])) continue;

			$result = $integration->get_vat_record_information($source_ids[$integration->source]);
			if (!$result || !is_array($result) )
			{
				return array( 'status' => 'error', '' => array(sprintf( __( "Failed to access record information for source '%s'", 'vat_ecsl'), $integration->source ) ) );
			}

			if ($result['status'] === 'error')
				return $result;

			$information = $information + array_map( function($item) use($integration)
			{
				$item['source']  = $integration->source;
				return $item;
			}, $result['information'] );

//			$information[$integration->source] = $result['information'];
		}
		
		return array( 'status' => 'success', 'information' => $information );
	}

	/**
	 * Called by the integration controller to remove ECSL submission references for a set of post ids
	 *
	 * @array source_ids An array of sales record ids
	 *
	 * @return An error message, an array of messages or FALSE if every thing is OK
	 */
	function delete_vat_information($source_ids)
	{
		$errors = FALSE;

		foreach(ECSL_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_ecsl\ECSL_Integration_Base') ||
				!isset($this->enabled_integrations[$integration->source])) continue;

			if (!isset($source_ids[$integration->source])) continue;

			$result = $integration->delete_vat_information($source_ids[$integration->source]);
			if (!$result) continue;

			if (!is_array($errors)) $errors = array();
			if (!is_array($result)) $result = array($result);

			array_unshift($result, sprintf( __( "Errors occurred removing VAT information from records in source '%1s'", 'vat_ecsl'), $integration->source));
			$errors[$integration->source] = $result;
		}

		return $errors;
	}
	
	/**
	 * Flattens an array generated by get_vat_information() or get_vat_record_information()
	 *
	 */
	function flatten_vat_information($hierarchical_vat_payments)
	{
		$vat_payments = array();

		if (is_array($hierarchical_vat_payments))
		{
			foreach($hierarchical_vat_payments as $key => $payment)
			{
				$new_payment = array();

				// Only the first instance of an expanded payment should be included
				// This flag is used to indicate this condition
				$first = true;

				foreach($payment['values'] as $indicator => $amount)
				{
					foreach($payment as $key => $value)
					{
						if ($key === 'values') continue;
						$new_payment[$key] = $value;
					}

					$new_payment['indicator'] = $indicator;
					$new_payment['value'] = $amount;
					$new_payment['first'] = $first;

					$vat_payments[] = $new_payment;

					$first = false;
				}
			}
		}

		return $vat_payments;
	}

}