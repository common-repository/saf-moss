<?php

/**
 * MOSS SAF Submit transactions to create SAF
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

/**
 * Sends a definition to HMRC and handles any errors
 *
 * @int id The id of the definition being sent
 */
function submit_definition($id)
{
	error_log("submit_definition");
	if (!current_user_can('send_definitions'))
	{
		echo "<div class='error'><p>" . __('You do not have rights to submit an EC MOSS Audit file', 'vat_moss_saf' ) . "</p></div>";
		show_definitions();
		return;
	}

	$post = get_post($id);
	if ($post->post_status === STATE_GENERATED)
	{
		echo "<div class='updated'><p>" . __('An audit file for this definition has already been generated', 'vat_moss_saf' ) . "</p></div>";
		show_definitions();
		return;		
	}

	$definition_key	= get_post_meta( $id, 'definition_key',		true );
	$test_mode			= empty($definition_key)
		? true
		: get_post_meta( $id, 'test_mode',			true );

	if (empty($definition_key) && !$test_mode)
	{
		echo "<div class='error'><p>" . __('No credit license key is available for this definition', 'vat_moss_saf' ) . "</p></div>";
		show_definitions();
		return;		
	}

	$transaction_from_month = get_post_meta( $id, 'transaction_from_month', true );
	if (empty($transaction_from_month)) $transaction_from_month = date('m');

	$transaction_from_year = get_post_meta( $id, 'transaction_from_year', true );
	if (empty($transaction_from_year)) $transaction_from_year = date('Y');

	$transaction_to_month = get_post_meta( $id, 'transaction_to_month', true );
	if (empty($transaction_to_month)) $transaction_to_month = date('m');

	$transaction_to_year = get_post_meta( $id, 'transaction_to_year', true );
	if (empty($transaction_to_year)) $transaction_to_year = date('Y');

	$from_timestamp = strtotime( "$transaction_from_year-$transaction_from_month-1" );
	$lastday = date( "t", mktime( 0, 0, 0, $transaction_to_month ) );
	$to_timestamp = strtotime( "$transaction_to_year-$transaction_to_month-$lastday 23:59:59" );

	$target_currency = \lyquidity\vat_moss_saf\get_default_currency();
	$vat_records	= vat_moss_saf()->integrations->get_vat_information($from_timestamp, $to_timestamp, $target_currency);

	if ( !$vat_records || !is_array($vat_records) )
	{
		report_errors( array( __('There was an error creating the information to generate a definition request.', 'vat_moss_saf' ) ) );
		if (is_array( vat_moss_saf()->integrations->issues ) && count( vat_moss_saf()->integrations->issues ) )
		{
			report_errors( vat_moss_saf()->integrations->issues );
		}
	}
	else if (!isset($vat_records['transactions']) || count($vat_records['transactions']) == 0 ) 
	{
		report_errors( array( __('There are no transactions associated with this definition.', 'vat_moss_saf' ) ) );
	}
	else
	{

		$establishment_country	=  \lyquidity\vat_moss_saf\get_establishment_country();
		$vat_number				= \lyquidity\vat_moss_saf\get_vat_number();
		$company_name			= \lyquidity\vat_moss_saf\get_company_name();
		$submitter				= \lyquidity\vat_moss_saf\get_submitter();
		$email					= \lyquidity\vat_moss_saf\get_submitter_email();

		$vat_records = \lyquidity\vat_moss_saf\vat_moss_saf()->integrations->get_vat_information($from_timestamp, $to_timestamp, $target_currency);
		if ($vat_records === false)
		{
			// There may be issues to display_errors
			$issues = vat_moss_saf()->integrations->issues;
			error_log(print_r($issues,true));
			if (isset( $issues ) && is_array( $issues ) && count( $issues ) > 0 )
			{
				foreach( $issues as $issue)
				{
					echo "<div class='error'><p>$issue</p></div>";		
				}
			}
			show_definitions();
		}

		$saf_data =  base64_encode( gzdeflate( serialize( $vat_records ), 9 ) );

		$vat_number				= get_post_meta( $id, 'vat_number',			true );
		$submitter				= get_post_meta( $id, 'submitter',			true );
		$email					= get_post_meta( $id, 'email',				true );
		$definition_period		= get_post_meta( $id, 'definition_period',	true );
		$definition_year		= get_post_meta( $id, 'definition_year',	true );
		$company_name			= get_company_name();
		$currency				= get_default_currency();
		$establishment_country	= get_establishment_country();

		$data = array(
			'edd_action'			=> 'moss_saf_generate_audit_file',
			'definition_key'		=> $definition_key,
			'url'					=> site_url(),
			'test_mode'				=> $test_mode,
			'vrn'					=> $vat_number,
			'company_name'			=> $company_name,
			'submitter'				=> $submitter,
			'email'					=> $email,
			'definition_period'		=> $definition_period,
			'definition_year'		=> $definition_year,
			'start_date'			=> date( 'Y-m-d 00:00:00', $from_timestamp ),
			'end_date'				=> date( 'Y-m-d 23:59:59', $to_timestamp ),
			'currency'				=> $currency,
			'establishment_country' => $establishment_country,
			'saf_data'				=> $saf_data
		);

		$args = array(
			'method'				=> 'POST',
			'timeout'				=> 45,
			'redirection'			=> 5,
			'httpversion'			=> '1.0',
			'blocking'				=> true,
			'headers'				=> array(),
			'body'					=> $data,
			'cookies'				=> array()
		);

		process_response( $id, $args );
	}
	
	show_definitions( );
	return;
}

function process_response($id, $args)
{
	$json = remote_get_handler( wp_remote_post( VAT_MOSS_SAF_STORE_API_URL, $args ) );
	$error = "";
	$result = json_decode($json);

	// switch and check possible JSON errors
	switch (json_last_error()) {
		case JSON_ERROR_NONE:
			$error = ''; // JSON is valid
			break;
		case JSON_ERROR_DEPTH:
			$error = 'Maximum stack depth exceeded.';
			break;
		case JSON_ERROR_STATE_MISMATCH:
			$error = 'Underflow or the modes mismatch.';
			break;
		case JSON_ERROR_CTRL_CHAR:
			$error = 'Unexpected control character found.';
			break;
		case JSON_ERROR_SYNTAX:
			$error = 'Syntax error, malformed JSON.';
			break;
		// only PHP 5.3+
		case JSON_ERROR_UTF8:
			$error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
			break;
		default:
			$error = 'Unknown JSON error occured.';
			break;
	}

	if($error !== '') {
		report_severe_error( $id, $result, $error );
	}
	else if (!is_object( $result ))
	{
		report_severe_error( $id, $result, "The response from the request to process the definition is not an array and this should never happen." );
	}
	else if(!isset( $result->status ))
	{
		report_severe_error(  $id, $result, "The response from the request to process the definition is an array but it does not contain a 'status' element" );
	}
	else
	{
		// The sources of error are:
		//	the failure to complete the wp_remote_post ('status' === 'failed' + 'message')
		//	an error processing the post (e.g. missing request data) ('status' === 'error' + 'message')
		//	an error reported by the gateway ('status' === 'success' + 'error_message' in the definition log)

		if (true)
		{
			if ( $result->status === 'failed' )
			{
				report_severe_error( $id, $result, isset( $result->message ) ? $result->message : "An error posting the definition has occurred but the reason is unknown" );
			}
			else
			{

				if ($result->status === 'error'  )
				{
					report_severe_error( $id, $result, isset( $result->message ) ? $result->message : "An error has occurred validating the definition on the remote server but the reason is unknown" );
				}
				else
				if ($result->status !== 'valid' && $result->status !== 'success'  ) // Licence issue
				{
					report_severe_error( $id, $result, isset( $result->message ) ? $result->message : "An error has occurred validating the license key" );
				}
				else
				{
					process_definition_status( isset( $result->state ) ? $result->state : STATE_FAILED);

					// Copy the results of the 'definition' arrays to posts on this site
					wp_update_post( array(
						'ID'				=> $id,
						'post_status'		=> property_exists( $result, 'state' ) ? $result->state : STATE_FAILED,
						'post_modified'		=> date('Y-m-d H:i:s'),
						'post_modified_gmt'	=> gmdate('Y-m-d H:i:s')
					));

					if ( property_exists( $result, 'body' ) )
					{
						$xml = gzinflate( base64_decode( $result->body ) );
						update_post_meta( $id, 'report', $xml );
					}
				}
			}
		}
	}
}

function report_severe_error($id, $result, $message)
{
	if (is_array($message))
		$message = implode('<br/>', $message);

	report_errors( "Severe error. $message" );

	update_post_meta( $id, 'error_information', serialize($result) );
	update_post_meta( $id, 'error_message', $message );

	wp_update_post( array(
		'ID'				=> $id,
		'post_status'		=> STATE_FAILED,
		'post_modified'		=> date('Y-m-d H:i:s'),
		'post_modified_gmt'	=> gmdate('Y-m-d H:i:s')
	));
}

function process_definition_status($definition_state)
{
	switch($definition_state)
	{
		case STATE_GENERATED:
			echo "<div class='updated'><p>" . __('The Standard Audit File generation has been successful.', 'vat_moss_saf' ) . "</p></div>";
			break;
			
		default:
			echo "<div class='error'><p>" . __('The attempt to generate a Standard Audit File failed. See the log for more information.', 'vat_moss_saf' ) . "</p></div>";
			break;
	}
}

function format_xml($xml)
{
	$domxml = new \DOMDocument('1.0');
	$domxml->preserveWhiteSpace = false;
	$domxml->formatOutput = true;
	$domxml->loadXML($xml);
	return $domxml->saveXML();
}

function remote_get_handler($response, $message = 'Error processing definition')
{
	if (is_a($response,'WP_Error'))
	{
		error_log(print_r($response,true));
		$error = array(
			'status' => 'failed',
			'message' => $response->get_error_message()
		);

		return json_encode($error);
	}
	else
	{
		$code = isset( $response['response']['code'] ) && isset( $response['response']['code'] )
			? $response['response']['code']
			: 'Unknown';

		if ( $code == 200 && isset( $response['body'] ))
		{
			return $response['body'];
		}
		else
		{
			$error = array(
				'status' => 'failed',
				'message' => "$message ($code)"
			);

			return json_encode($error);
		}
	}
}

?>
