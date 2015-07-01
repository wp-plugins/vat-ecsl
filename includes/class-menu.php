<?php

/**
 * ECSL Class menus
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

class VAT_ECSL_Admin_Menu {
	

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
	}

	public function register_menus() {
	
		// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page( __( 'ECSL Submissions', 'vat_ecsl' ), __( 'ECSL', 'vat_ecsl' ), 'view_submissions', 'ecsl-submissions', '\lyquidity\vat_ecsl\ecsl_submissions', 'dashicons-book' );
		// add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		add_submenu_page( 'ecsl-submissions', __( 'Submissions', 'vat_ecsl' ), __( 'Submissions', 'vat_ecsl' ), 'view_submissions', 'ecsl-submissions', '\lyquidity\vat_ecsl\ecsl_submissions' );
		add_submenu_page( 'ecsl-submissions', __( 'Settings', 'vat_ecsl' ), __( 'Settings', 'vat_ecsl' ), 'view_submissions', 'ecsl-submissions-settings', '\lyquidity\vat_ecsl\ecsl_submissions_settings' );
	}

}
$ecsl_menu = new VAT_ECSL_Admin_Menu;