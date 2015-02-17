<?php

/**
 * ECSL Edit submission (uses 'new' submission)
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

function edit_submission($id)
{
	global $selected;

	$selected	= maybe_unserialize(get_post_meta($id, 'ecslsales', true));

	if (isset($_REQUEST['change_periods']))
	{
		$from_year	= null;
		$from_month	= null;
		$to_year	= null;
		$to_month	= null;
	}
	else
	{
		$from_year	= get_post_meta( $id, 'from_year',	true );
		$from_month	= get_post_meta( $id, 'from_month',	true );
		$to_year	= get_post_meta( $id, 'to_year',	true );
		$to_month	= get_post_meta( $id, 'to_month',	true );
	}

	$state = get_post_status( $id );

	$read_only = ($state === STATE_SUBMITTED || $state === STATE_ACKNOWLEDGED);

	new_submission( $from_year, $from_month, $to_year, $to_month, $id, $read_only );
}
?>
