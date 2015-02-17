<?php

/**
 * ECSL Base class for integrations
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

abstract class ECSL_Integration_Base {

	/**
	 * The source for referrals. This refers to the integration that is being used.
	 *
	 * @access  public
	 * @since   1.0
	 */
	public $source;

	/**
	 * The name associated with the source for referrals.
	 *
	 * @access  public
	 * @since   1.0
	 */
	public $name;

	/**
	 * The post_type associated with the source one which the indicator metabox should appear.
	 *
	 * @access  public
	 * @since   1.0
	 */
	public $post_type = '';

	/**
	 * The default indicator type to assume for products associated with the source.
	 *
	 * @access  public
	 * @since   1.0
	 */
	public $default_indicator = 3; // Services

	/**
	 * Constructor
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function __construct() {

		$this->init();
	}

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */
	public function init() {

	}
	
	/**
	 * Returns an array of VAT information:
	 *	id				Database id for the sale
	 *	purchase_key	Unique purchase identifier
	 *	date			Datetime of the completed transaction
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
	 * @string	endDate					strtotime() compatible date of the latest record to return.
	 * @boolean	includeSubmitted		True if the results should include previously submitted records (submission_id does not exist in meta-data)
	 * @boolean	includeSubmittedOnly	True if the results should only include selected items
	 */
	public function get_vat_information($startTimestamp, $endTimestamp, $includeSubmitted = false, $includeSubmittedOnly = false)
	{}

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
	{}

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
	 */
	function get_vat_record_information($source_ids)
	{}

	/**
	 * Called by the integration controller to remove ECSL submission references for a set of post ids
	 *
	 * @array ids An array of sales record ids
	 *
	 * @return An error message, an array of messages or FALSE if every thing is OK
	 */
	function delete_vat_information($ids)
	{}

}

?>