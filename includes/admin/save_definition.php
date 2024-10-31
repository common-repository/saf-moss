<?php

/**
 * MOSS SAF Save definition
 *
 * @package     vat-moss-saf
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

namespace lyquidity\vat_moss_saf;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function save_definition()
{
	error_log("Save Definition");

	$definition_id = isset($_REQUEST['definition_id']) ? $_REQUEST['definition_id'] : 0;

	if ( !isset($_REQUEST['_wp_nonce']) ||
		 !wp_verify_nonce( $_REQUEST['_wp_nonce'], 'moss_saf_definition' ) )
	{
		echo "<div class='error'><p>" . __('The attempt to save the definition is not valid.  The nonce does not exist or cannot be verified.', 'vat_moss_saf' ) . "</p></div>";
		if ($definition_id)
			edit_definition($definition_id);
		else
			new_definition();
		return;
	}

	if (!isset($_REQUEST['moss_saf_settings_title']) || !$_REQUEST['moss_saf_settings_title'])
	{
		echo "<div class='error'><p>" . __('The definition does not have a title', 'vat_moss_saf' ) . "</p></div>";
		if ($definition_id)
			edit_definition($definition_id);
		else
			new_definition();
		return;		
	}

	// Grab the post information
	$test_mode	= isset($_REQUEST['test_mode'])	? $_REQUEST['test_mode'] : 0;
	$vrn					= isset( $_REQUEST['moss_saf_settings_vat_number'])	? $_REQUEST['moss_saf_settings_vat_number']	: vat_moss_saf()->settings->get('vat_number');
	$submitter				= isset( $_REQUEST['moss_saf_settings_submitter'])	? $_REQUEST['moss_saf_settings_submitter']	: vat_moss_saf()->settings->get('submitter');
	$email					= isset( $_REQUEST['moss_saf_settings_email'])		? $_REQUEST['moss_saf_settings_email']		: vat_moss_saf()->settings->get('email');
	$title					= isset( $_REQUEST['moss_saf_settings_title'])		? $_REQUEST['moss_saf_settings_title']		: vat_moss_saf()->settings->get('title');

	$definition_key			= isset($_REQUEST['definition_key'])				? $_REQUEST['definition_key']				: '';

	$transaction_from_year	= isset($_REQUEST['transaction_from_year'])			? $_REQUEST['transaction_from_year']		: date('Y');
	$transaction_to_year	= isset($_REQUEST['transaction_to_year'])			? $_REQUEST['transaction_to_year']			: date('Y');
	$transaction_from_month	= isset($_REQUEST['transaction_from_month'])		? $_REQUEST['transaction_from_month']		: date('m');
	$transaction_to_month	= isset($_REQUEST['transaction_to_month'])			? $_REQUEST['transaction_to_month']			: date('m');

	$definition_period		= isset($_REQUEST['definition_period'])				? $_REQUEST['definition_period']			: floor((date('n') - 1) / 3) + 1;
	$definition_year		= isset($_REQUEST['definition_year'])				? $_REQUEST['definition_year']				: date('Y');

	$from_timestamp = strtotime( "$transaction_from_year-$transaction_from_month-1" );
	$lastday = date( "t", mktime( 0, 0, 0, $transaction_to_month ) );
	$to_timestamp = strtotime( "$transaction_to_year-$transaction_to_month-$lastday 23:59:59" );

	if ($from_timestamp > $to_timestamp)
	{
		echo "<div class='error'><p>" . __('The \'from\' date is later than the \'to\' date.', 'vat_moss_saf' ) . "</p></div>";
		if ($definition_id)
			edit_definition($definition_id);
		else
			new_definition();
		return;		
	}
	
	if ($definition_id)
	{
		// Begin by deleting the records associated with the definition
		if (!delete_definition($definition_id, false))
		{
			edit_definition($definition_id);
			return;
		}

		wp_update_post(
			array(
				'ID'				=> $definition_id,
				'post_title'		=> $title,
				'post_modified'		=> date('Y-m-d H:i:s'),
				'post_modified_gmt'	=> gmdate('Y-m-d H:i:s')
			 )
		);
	}
	else
	{
		// Create a post 
		$definition_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_type'	   => 'moss_saf_definition',
				'post_content' => '',
				'post_status'  => STATE_NOT_GENERATED
			 )
		);
	}

	if ($test_mode)
		update_post_meta( $definition_id, 'test_mode', 1 );
	else
		delete_post_meta( $definition_id, 'test_mode' );

	update_post_meta( $definition_id, 'vat_number',				$vrn					);
	update_post_meta( $definition_id, 'submitter', 				$submitter				);
	update_post_meta( $definition_id, 'email',					$email					);

	update_post_meta( $definition_id, 'definition_period',		$definition_period		);
	update_post_meta( $definition_id, 'definition_year',		$definition_year		);

	update_post_meta( $definition_id, 'transaction_from_year',	$transaction_from_year	);
	update_post_meta( $definition_id, 'transaction_to_year',	$transaction_to_year	);
	update_post_meta( $definition_id, 'transaction_from_month',	$transaction_from_month	);
	update_post_meta( $definition_id, 'transaction_to_month',	$transaction_to_month	);

	if ($definition_key)
		update_post_meta( $definition_id, 'definition_key',		$definition_key		);
	else
		delete_post_meta( $definition_id, 'definition_key'						);

	$message = __( "Definition details saved", 'vat_moss_saf' );
	echo "<div class='updated'><p>$message</p></div>";

	if ($definition_id)
		edit_definition($definition_id);
	else
		show_definitions();
}

?>