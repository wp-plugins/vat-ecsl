<?php

/*
 * Part of: ECSL 
 * @Description:	Implements a metabox for product definition posts so the site owner is able to select the indicator type for the product.
 * @Version:		1.0.1
 * @Author:			Bill Seddon
 * @Author URI:		http://www.lyqidity.com
 * @Copyright:		Lyquidity Solution Limited
 * @License:		GNU Version 2 or Any Later Version
 */

namespace lyquidity\vat_ecsl;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Returns VAT indicator to use
 *
 */
function vat_indicator_to_use($postID)
{
	$indicator = get_post_meta( $postID, '_ecsl_indicator', true );
	if (empty($indicator) && $indicator !== '0')
		$indicator = '3';
	return $indicator;
}
/**
 * Add select VAT rates class meta box
 *
 * @since 1.4.0
 */
function register_vat_meta_box() {

	$post_types = vat_ecsl()->integrations->get_post_types();
	if (!$post_types || !is_array($post_types) || count($post_types) == 0) return;

	foreach($post_types as $key => $post_type)
	{
		add_meta_box( 'vat_ecsl_indicator_box', __( 'VAT EC Sales List type indicator', 'vat_ecsl' ), '\lyquidity\vat_ecsl\render_indicator_meta_box', $post_type, 'side', 'core' );
	}
}
add_action( 'add_meta_boxes', '\lyquidity\vat_ecsl\register_vat_meta_box', 90 );

/**
 * Callback for the VAT meta box
 *
 * @since 1.4.0
 */
function render_indicator_meta_box()
{
	global $post;

	// Use nonce for verification
	echo '<input type="hidden" name="vat_ecsl_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

	$indicator = vat_indicator_to_use( $post->ID);

	echo vat_ecsl()->html->select( array(
		'options'          => array( '0' => __( 'Goods', 'vat_ecsl' ), '2' => 'Triangulation', '3' => 'Service' ),
		'name'             => 'vat_ecsl_indicator',
		'selected'         => $indicator,
		'show_option_all'  => false,
		'show_option_none' => false,
		'class'            => 'ecsl-select escl-vat-class',
		'select2' => true
	) );

	$msg = __('Select the VAT indicator to use for this product when purchases are reported in EC Sales List (VAT101) submission.', 'vat_ecsl');
	echo "<p>$msg</p>";

}

/**
 * Save data from meta boxes
 *
 * @since 1.4.0
 */
function indicator_meta_box_save( $post_id ) {

	global $post;

	 if(!is_admin()) return;

	// verify nonce
	if ( !isset( $_POST['vat_ecsl_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['vat_ecsl_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return;
	}

	$post_types = vat_ecsl()->integrations->get_post_types();

	if ( !isset( $_POST['post_type'] ) || !in_array($_POST['post_type'], $post_types ) ) {
		return $post_id;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	if ( !isset( $_POST['vat_ecsl_indicator'] ) ) 
		return $post_id;

	update_post_meta( $post_id, '_ecsl_indicator', $_POST['vat_ecsl_indicator'] );
}
add_action( 'save_post', '\lyquidity\vat_ecsl\indicator_meta_box_save' );

?>