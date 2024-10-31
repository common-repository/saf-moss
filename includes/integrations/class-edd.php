<?php

/**
 * MOSS SAF Easy Digital Downloads integration
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

class MOSS_SAF_Integration_EDD extends MOSS_SAF_Integration_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->source = 'edd';
		$this->name = 'Easy Digital Downloads';
		$this->post_type = 'download';
		$instance = $this;
		add_action( 'moss_saf_integration_instance', function( $instance_array ) use($instance)
		{
			if (function_exists('EDD'))
				$instance_array[$instance->source] = $instance;

			return $instance_array;
		}, 10 );

	}

	/**
	 * Returns an associative array of the VAT type names indexed by type
	 */
	function get_vat_type_names()
	{
		return \lyquidity\edd_vat\get_vat_rate_types();		
	}

	/**
	 * Returns an array of VAT information:
	 *
	 *	{
	 *		customers
	 *		{
	 *			id
	 *			city
	 *			country_code	
	 *		}
	 *		transactions
	 *		{
	 *			source				The 'context' identifier for the source
	 *			id					Database id for the sale
	 *			purchase_key		Unique purchase identifier (the id if separate unique id does not exist)
	 *			date				Datetime of the completed transaction
	 *			customer_id			The name of the buyer
	 * 			country_code		Country code of the consumer (billing address)
	 *			currency_code		Currency of the sale
	 *			type				Transaction types: TR – Transaction, IN – Invoice, DN- Debit Note, CN - Credit Note
	 *			evidence			Evidence used for the sale (enumeration)
	 *			items
	 *			{
	 *				[0] 
	 *				{
	 *					code		service code
     *					name		description
	 *					quantity	
	 *					measure		Denomination of the quantity
	 *					indicator	0: Goods, 2: Triangulated sale, 3: Services (reverse charge)
	 *					price		Unit price
	 *					debit		e.g. Credit notes
	 *					credit		e.g. Invoice amount and Debit notes
	 *					Tax
	 *					{
	 *						region
	 *						vat_type
	 *						rate
	 *					}
	 *					amount
	 *				}
	 *			}
	 *			total
	 *			{
	 *				tax
	 *				amount
	 *				gross
	 *				payment
	 *				{
	 *					type
	 *					date
	 *					amount
	 *					mechanism
	 *				}
	 *			}
	 *		}
	 *	}
	 *
	 * If the sale is across two service types (different indicators) then they need to appear as different entries
	 *
	 * @string	startDate					strtotime() compatible date of the earliest record to return
	 * @string	endDate						strtotime() compatible date of the latest record to return
	 * @bool	include_customer_details	True if the customer information should include identifiable information
	 */
	public function get_vat_information($startDate, $endDate, $include_customer_details = false )
	{
		$establishment_country = \lyquidity\vat_moss_saf\get_establishment_country(); 

		$meta_query = array();
		$meta_query[] = array(
			'key'		=> '_edd_completed_date',
			'value'		=> array($startDate, $endDate),
			'compare'	=> 'BETWEEN',
			'type'		=> 'DATE'
		);

		$args = array(
			'post_type' 		=> 'edd_payment',
			'posts_per_page' 	=> -1,
			'fields'			=> 'ids',
			'post_status'		=> array( 'publish','edd_subscription' ),
			'orderby'			=> array( 'meta_value_num' => 'ASC' ),
			'meta_query'		=> $meta_query
		);

		$payments = new \WP_Query( $args );

		$customers = array();
		$transactions = array();

		if( $payments->posts )
		{
			$eu_states = array_flip( WordPressPlugin::$eu_states );

			foreach( $payments->posts as $payment_id ) {

				$post = get_post( $payment_id );
				if (!$post)
				{
					// Should never happen
					error_log("class_edd->get_vat_information error: unable to find a post for $payment_id");
					continue;
				}

				// Record the customer id
				$customer_id	= $post->post_author;
				$customer_guid	= $this->GUID();

				$payment_meta	= edd_get_payment_meta( $payment_id );
				$user_info		= maybe_unserialize( $payment_meta['user_info'] );
				$country_code	= $user_info['address']['country'];
				
				// Don't need to report sales into the establishment_country as 
				// these are reported on the VAT return so not part of MOSS
				if ($country_code == $establishment_country)
					continue;

				// Has the customer information already been collected
				if (!isset( $customers[$customer_id] ) )
				{
					/* This filter will return an array like:
						Array
						(
							[line1] => Rue de Oostende 6
							[line2] => 
							[city] => Zaventem
							[state] => 
							[zip] => 1930
							[country] => BE
						)
		
						email		= x@y.com
						first_name	= Administrator
						last_name	= Administrator
						address = array
						{
							line1		= PO Box 504
							line2		= 
							city		= New Malden
							state		= SURREY
							country		= GB
							zip			= KT3 5EE
						}
						company	= "Lyquidity Solutions Limited";
					 */

					$address = apply_filters( 'get_customer_address', array(), new \WP_User( $customer_id ) );
					$customers[$customer_id] = array 
					(
						'guid'				=> $customer_guid,
						'company'			=> isset($user_info['company']) ? $user_info['company'] : '',
						'name'				=> 'unknown',
						'billing_address'	=> array
											   (
													'city' => $user_info['address']['city']
											   ),
						'country_code'		=> $user_info['address']['country']
					);
	
					if ($include_customer_details)
					{
						// Include the name, email address and first line of the physical address
						$customers[$customer_id]['email']				= $user_info['email'];
						$customers[$customer_id]['name'] = array
						(
							'first_name'	=> $user_info['first_name'],
							'last_name'		=> $user_info['last_name']
						);

						$address = $user_info['address'];
						$customers[$customer_id]['billing_address'] = 
							(isset($address['line1'])	&& !empty($address['line1'])	? ($address['line1']	. "\n")	: "") .
							(isset($address['line2'])	&& !empty($address['line2'])	? ($address['line2']	. "\n")	: "") .
							(isset($address['city'])	&& !empty($address['city'])		? ($address['city']		. "\n")	: "") .
							(isset($address['state'])	&& !empty($address['state'])	? ($address['state']	. "\n")	: "") .
							(isset($address['zip'])		&& !empty($address['zip'])		? ($address['zip']		. "\n")	: "");
					}
				}

				$currency_code	= $payment_meta['currency'];
				// This condition is needed to accommodate the change in the storage of total tax in EDD 2.3
				$cart_tax		= isset( $payment_meta['tax'] ) ? $payment_meta['tax'] : get_post_meta( $payment_id, '_edd_payment_tax', true );

				// If there is a VAT number then the record does not apply
				if ( isset($user_info['vat_number']) && !empty($user_info['vat_number']) ) continue;

				// Only report sales in the EU
				if (!isset($eu_states[$country_code])) continue;

				$buyer			= sprintf("%1s %2s", $user_info['first_name'], $user_info['last_name']);
				$purchase_key	= get_post_meta( $payment_id, '_edd_payment_purchase_key', true);
				$date			= get_post_meta( $payment_id, '_edd_completed_date', true);
				$payment_amount	= get_post_meta( $payment_id, '_edd_payment_total', true);
				$gateway		= get_post_meta( $payment_id, '_edd_payment_gateway', true);

				$payment_rate	= isset( $user_info['vat_rate'] ) ? $user_info['vat_rate'] : 0;
				$cart_details	= maybe_unserialize( $payment_meta['cart_details'] );

				$transaction = array();

				$transaction['source']			= $this->source;
				$transaction['id']				= $payment_id;
				$transaction['type']			= "IN";
				$transaction['purchase_key']	= $purchase_key;
				$transaction['date']			= $post->post_date; // Maybe this should be the post_date
				$transaction['country_code']	= $country_code;
				$transaction['currency_code']	= $currency_code;
				$transaction['customer_id']		= $customer_id;
				$transaction['evidence'][]		= "A";	// A – Billing Address of the Customer; 
														// B – IP address or geo-location; 
														// C – Bank Details; 
														// D – Mobile Country Codes or SIM card used by customer; 
														// E – Location of the fixed land-line used for service; 
														// F – Other Means;
				$transaction['evidence'][]		= isset($user_info['self_certified']) && $user_info['self_certified']
													? "F"
													: "B";
				$transaction['ipaddress']		= edd_get_payment_user_ip( $payment_id );

				$total_tax = 0;
				$total_net = 0;
				$total_gross = 0;

				$items = array();

				foreach( $cart_details as $key => $item )
				{
					if ($item['price'] == 0) continue;

					$class = \lyquidity\edd_vat\vat_class_to_use( $item['id'] );
					if ($class === VAT_EXEMPT_CLASS) continue;

					// Look up the correct set of class rates for this item
					$rate_type = VAT_GROUP_CLASS_REDUCED;
					if (VAT_STANDARD_CLASS !== $class)
					{
						$class_rates = \lyquidity\edd_vat\get_vat_rates($class);

						// Filter the rate for each country
						$country_rate = array_filter($class_rates, function($class_rate) use($country_code)
							{
								return $class_rate['country'] === $country_code;
							});

						// If one exists, take the first or create a default
						$country_rate = !is_array($country_rate) || count($country_rate) == 0
							? array('country' => $country_code, 'rate' => null, 'global' => true, 'state' => null, 'group' => VAT_GROUP_CLASS_REDUCED)
							: reset($country_rate);

						$rate_type = isset( $country_rate['group'] ) ? $country_rate['group'] : VAT_GROUP_CLASS_REDUCED;
					}
					
					$indicator = function_exists('\lyquidity\vat_ecsl\vat_indicator_to_use')
						? \lyquidity\vat_ecsl\vat_indicator_to_use($item['id'])
						: 3;  // Default to a service

					$service_code = saf_service_code_to_use($item['id']);

					$line_item = array();

					/*
						"name"	=> "Book"
						"id"	=> "376"
						"item_number" => {
							"id"		=> "376"
							"options"	=> {}
							"quantity"	=> 1
						}
						"item_price"	=> 7.5
						"quantity"		=> 1
						"discount"		=> 1.5
						"subtotal"		=> 7.5
						"tax"			=> 0.6
						"fees"			=> {}
						"price"			=> 6.6
						"vat_rate"		=> 0.1
					 */

					/*
						code		service code
						name		description
						quantity	
						measure		Denomination of the quantity
						price		Unit price
						debits		e.g. Credit notes
						credits		e.g. Invoice amount and Debit notes
						tax
						{
							region
							vat_type
							rate
						}
						amount
					 */

					$line_item['id']					= $item['id'];
					$line_item['code']					= $service_code;
					$line_item['name']					= $item['name'];
					$line_item['quantity']				= $item['quantity'];
					$line_item['measure']				= $currency_code;
					$line_item['price']					= $item['item_price'];
					$line_item['debits']				= array();
					$line_item['debits']['discount']	= $item['discount'];

					$line_item['credits']				= array();
					if ( isset($item['fees']) )
					{
						$total_fees = 0;
						foreach( $item['fees'] as $key => $fee )
						{
							// Doesn't count here if the discount is not for a specific product
							if ( !isset($fee['download_id']) || empty($fee['download_id']) )
								continue;

							$total_fees += $fee['amount'];
						}
						$line_item['credits']['fees']	= $total_fees;
					}

					$line_item['indicator']				= $indicator;
					$line_item['tax']					= array(
															'region'	=> '',
															'vat_type'	=> $rate_type,
															'vat_rate'	=> isset( $item['vat_rate'] ) ?  $item['vat_rate'] : $payment_rate,
															'amount'	=> $item['tax']
														);
					$net								= apply_filters( 'moss_saf_get_net_transaction_amount', $item['price'] - $item['tax'], $payment_id);
					$line_item['net']					= $net;
					$amount								= apply_filters( 'moss_saf_get_net_transaction_amount', $item['price'], $payment_id);
					$line_item['amount']				= $amount;
					$line_item['credits']['invoice']	= $line_item['amount'];

					$total_tax		+= $item['tax'];
					$total_net		+= $net;
					$total_gross	+= $amount;

					$items[]							= $line_item;
				}

				$transaction['items'] = $items;
				
				// Need to handle fees
				/*
					s:4:"fees";a:1:{
						s:12:"handling_fee";a:5:{
							s:6:"amount";s:5:"10.00";
							s:5:"label";s:12:"Handling Fee";
							s:6:"no_tax";b:1;
							s:4:"type";s:3:"fee";
							s:11:"download_id";i:0;
						}
					}
				 */
				
				$total_fees = 0;
				
				if ( isset($payment_meta['fees']) )
				{
					foreach( $payment_meta['fees'] as $key => $fee )
					{
						// Doesn't count here if the discount is for a specific product
						if ( isset($fee['download_id']) && !empty($fee['download_id']) )
							continue;

						$total_fees += $fee['amount'];
					}
				}
				
				$transaction['fees'] = $total_fees;

				/*
				 *	total
				 *	{
				 *		tax
				 *		amount
				 *		gross
				 *		payment
				 *		{
				 *			type
				 *			date
				 *			amount
				 *			mechanism
				 *		}
				 *	}
				 */
				
				$mechanism = 'CC';
				switch($gateway)
				{
					case 'bacs':
						$mechanism = 'BT';
						
					case 'check':
					case 'cheque':
					case 'manual':
						$mechanism = 'CH';
						
					case 'paypal':
						$mechanism = 'PP';
				}

				$transaction['total']			= array(
													'tax'		=> $total_tax,
													'amount'	=> $total_net + $total_fees,
													'gross'		=> $total_gross + $total_fees,
													'payment'	=> array(
																		'type'		=> 'TP',  		/* AP - Advanced Payment; PP – Partial Payment; TP – Total Payment; */
																		'date'		=> $date,		/* This should be the completed date */
																		'amount'	=> $payment_amount,
																		'mechanism'	=> $mechanism	/* CH – Cheque; DC – Debit card; CC – Credit card; BT – Bank transfer; PP - PayPal; OT - Other; MP - Mobile Phone. */
																   )
												);

				$transactions[] = $transaction;
			}
		}

		return array( 'customers' => $customers, 'transactions' => $transactions );
	}

}
new MOSS_SAF_Integration_EDD;
