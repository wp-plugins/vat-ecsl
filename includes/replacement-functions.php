<?php

/**
 * Replacement functions
 *
 * The WordPress list class expects the user will be a browser not an Ajax
 * request so has no problem expecting a WP_Screen instance to be available.
 * In an ajax call the function convert_to_screen does not exist so this
 * is provided to compensate.
 *
 * @package     vat-moss
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

if (!function_exists('convert_to_screen'))
{
	function convert_to_screen( $hook_name ) {
		if ( ! class_exists( 'WP_Screen' ) ) {
			return (object) array( 'id' => '_invalid', 'base' => '_are_belong_to_us' );
		}

		return WP_Screen::get( $hook_name );
	}
}

?>