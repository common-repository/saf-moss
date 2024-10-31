<?php

/*
 * Part of: MOSS 
 * @Description: Implements a metabox for product definition posts so the site owner is able to select the service_code type for the product.
 * @Version: 1.0.1
 * @Author: Bill Seddon
 * @Author URI: http://www.lyqidity.com
 * @Copyright: Lyquidity Solution Limited
 * @License:	GNU Version 2 or Any Later Version
 */

namespace lyquidity\vat_moss_saf;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Returns VAT service_code to use
 *
 */
function saf_service_code_to_use($postID)
{
	$service_code = get_post_meta( $postID, '_moss_saf_service_code', true );
	if (empty($service_code))
		$service_code = 'SB';
	return $service_code;
}
/**
 * Add select VAT rates class meta box
 *
 * @since 1.4.0
 */
function register_moss_saf_meta_box() {

	$post_types = vat_moss_saf()->integrations->get_post_types();
	if (!$post_types || !is_array($post_types) || count($post_types) == 0) return;

	foreach($post_types as $key => $post_type)
	{
		add_meta_box( 'vat_moss_saf_service_code_box', __( 'VAT MOSS SAF service_code', 'vat_moss_saf' ), '\lyquidity\vat_moss_saf\render_service_code_meta_box', $post_type, 'side', 'core' );
	}
}
add_action( 'add_meta_boxes', '\lyquidity\vat_moss_saf\register_moss_saf_meta_box', 90 );

/**
 * Callback for the VAT meta box
 *
 * @since 1.4.0
 */
function render_service_code_meta_box()
{
	global $post;

	// Use nonce for verification
	echo '<input type="hidden" name="vat_moss_saf_meta_box_nonce" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

	$service_code = saf_service_code_to_use( $post->ID);
	$service_codes = array(
		"BA" => __( 'Radio or television programmes transmitted or retransmitted over a radio or television network', 'vat_moss_saf' ),
		"BB" => __( 'Radio or television programmes distributed via the Internet or similar electronic network (IP streaming) if broadcast live or simultaneous to their being transmitted or retransmitted ove ar radio or television network', 'vat_moss_saf' ),
		"TA" => __( 'fixed and mobile telephone services for the transmission and switching of voice, data and video, including telephone services with an imaging component, otherwise known as videophone services', 'vat_moss_saf' ),
		"TB" => __( 'telephone services provided through the Internet, including voice over Internet Protocol (VoIP)', 'vat_moss_saf' ),
		"TC" => __( 'voice mail, call waiting, call forwarding, caller identification, three-way calling and other call management services', 'vat_moss_saf' ),
		"TD" => __( 'paging services', 'vat_moss_saf' ),
		"TE" => __( 'audiotext services', 'vat_moss_saf' ),
		"TF" => __( 'facsimile, telegraph and telex', 'vat_moss_saf' ),
		"TG" => __( 'telephone helpdesk services by which assistance is provided to users in case of problems with their radio or television network, Internet or similar electronic network', 'vat_moss_saf' ),
		"TH" => __( 'access to the Internet, including the World Wide Web', 'vat_moss_saf' ),
		"TI" => __( 'private network connections providing telecommunications links for the exclusive use of the client', 'vat_moss_saf' ),
		"TJ" => __( 'private network connections providing telecommunications links for the exclusive use of the client', 'vat_moss_saf' ),
		"TK" => __( 'the onward supply of the audio and audio-visual output of a media service provider via communications networks by someone other than the media service provider', 'vat_moss_saf' ),
		"SA" => __( 'website supply, web-hosting, distance maintenance of programmes and equipment', 'vat_moss_saf' ),
		"SB" => __( 'supply of software and updating thereof', 'vat_moss_saf' ),
		"SC" => __( 'supply of images, text and information and making available of databases', 'vat_moss_saf' ),
		"SD" => __( 'supply of music, films and games, including games of chance and gambling games, and of political, cultural, artistic, sporting, scientific and entertainment broadcasts and events', 'vat_moss_saf' ),
		"SE" => __( 'supply of distance teaching', 'vat_moss_saf' )
	);

	echo vat_moss_saf()->html->select( array(
		'options'          => $service_codes,
		'name'             => 'vat_moss_saf_service_code',
		'selected'         => $service_code,
		'show_option_all'  => false,
		'show_option_none' => false,
		'class'            => 'moss-select escl-vat-class',
		'select2' => true
	) );

	$msg = __('Select the VAT service_code to use for this product when purchases are reported in MOSS definition.', 'vat_moss_saf');
	echo "<p>$msg</p>";

}

/**
 * Save data from meta boxes
 *
 * @since 1.4.0
 */
function service_code_meta_box_save( $post_id ) {

	global $post;

	 if(!is_admin()) return;

	// verify nonce
	if ( !isset( $_POST['vat_moss_saf_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['vat_moss_saf_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return;
	}

	$post_types = vat_moss_saf()->integrations->get_post_types();

	if ( !isset( $_POST['post_type'] ) || !in_array($_POST['post_type'], $post_types ) ) {
		return $post_id;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	if ( !isset( $_POST['vat_moss_saf_service_code'] ) ) 
		return $post_id;

	update_post_meta( $post_id, '_moss_saf_service_code', $_POST['vat_moss_saf_service_code'] );
}
add_action( 'save_post', '\lyquidity\vat_moss_saf\service_code_meta_box_save' );

?>