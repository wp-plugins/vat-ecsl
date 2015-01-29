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

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin Notices
 *
 * Outputs admin notices
 *
 * @package VAT ECSL
 * @since 1.0
*/
function admin_notices() {

	if (!isset($_REQUEST['page']) || $_REQUEST['page'] !== 'ecsl-submissions-settings') return;

	$vat_number = vat_ecsl()->settings->get( 'vat_number', array() );

	$out = new \StdClass();
	if (!perform_simple_check("GB$vat_number", $out))
	{
		echo "<div class='error'><p>$out->message</p></div>";
	}
	
	$names = array(VAT_ECSL_ACTIVATION_ERROR_NOTICE, VAT_ECSL_ACTIVATION_UPDATE_NOTICE, VAT_ECSL_DEACTIVATION_ERROR_NOTICE, VAT_ECSL_DEACTIVATION_UPDATE_NOTICE);
	array_walk($names, function($name) {

		$message = get_transient($name);
		delete_transient($name);

		if (empty($message)) return;
		$class = strpos($name,"UPDATE") === FALSE ? "error" : "updated";
		echo "<div class='$class'><p>$message</p></div>";

	});

}
add_action('admin_notices', '\lyquidity\vat_ecsl\admin_notices');