<?php

/**
 * MOSS SAF WOO Commerce integration
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

class MOSS_SAF_Integration_WOOC extends MOSS_SAF_Integration_Base {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function init() {

		$this->source = 'wooc';
		$this->name = 'Woo Commerce';
		$this->post_type = 'product';

		$instance = $this;
		add_action( 'moss_saf_integration_instance', function( $instance_array ) use($instance)
		{
			if (function_exists('WC'))
				$instance_array[$instance->source] = $instance;

			return $instance_array;
		}, 10 );

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
	public function get_vat_information( $startDate, $endDate, $include_customer_details = false )
	{
		$establishment_country = \lyquidity\vat_moss_saf\get_establishment_country(); 

		$meta_query = array();
		$meta_query[] = array(
			'key'		=> '_completed_date',
			'value'		=> array($startDate, $endDate),
			'compare'	=> 'BETWEEN',
			'type'		=> 'DATE'
		);
		$meta_query[] = array(
			'key'		=> '_order_tax',
			'value'		=> 0,
			'compare'	=> '>',
			'type'		=> 'DECIMAL'
		);
		
/*		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'		=> 'VAT Number',
				'compare'	=> 'NOT EXISTS'
			),
			array(
				'key'		=> 'Valid EU VAT Number',
				'value'		=> 'false',
				'compare'	=> '='
			)
		);
 */
		$meta_query[] = array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'		=> 'VAT Number',
					'compare'	=> 'NOT EXISTS'
				),
				array(
					'key'		=> 'Valid EU VAT Number',
					'value'		=> 'false',
					'compare'	=> '='
				)
			),
			array(
				'relation' => 'OR',
				array(
					'key'		=> 'vat_number',
					'compare'	=> 'NOT EXISTS'
				),
				array(
					'key'		=> 'vat_number',
					'value'		=> '',
					'compare'	=> '='
				)
			)
		);
		
		$args = array(
			'post_type' 		=> 'shop_order',
			'posts_per_page' 	=> -1,
			'fields'			=> 'ids',
			'post_status'		=> array( 'wc-completed' ),
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
				$order = wc_get_order( $payment_id );

				if (!$order)
				{
					// Should never happen
					error_log("class_wooc->get_vat_information error: unable to find a post for $payment_id");
					continue;
				}

				$purchase_key 					= get_post_meta(  $payment_id, '_order_key', true );
				$order_currency					= get_post_meta(  $payment_id, '_order_currency', true );
				$prices_include_tax				= get_post_meta(  $payment_id, '_prices_include_tax', true );
				$customer_ip_address			= get_post_meta(  $payment_id, '_customer_ip_address', true );
				$customer_user					= get_post_meta(  $payment_id, '_customer_user', true );
				$billing_country				= get_post_meta(  $payment_id, '_billing_country', true );
				$billing_first_name				= get_post_meta(  $payment_id, '_billing_first_name', true );
				$billing_last_name				= get_post_meta(  $payment_id, '_billing_last_name', true );
				$billing_company				= get_post_meta(  $payment_id, '_billing_company', true );
				$billing_address_1				= get_post_meta(  $payment_id, '_billing_address_1', true );
				$billing_address_2				= get_post_meta(  $payment_id, '_billing_address_2', true );
				$billing_postcode				= get_post_meta(  $payment_id, '_billing_postcode', true );
				$billing_city					= get_post_meta(  $payment_id, '_billing_city', true );
				$billing_state					= get_post_meta(  $payment_id, '_billing_state', true );
				$billing_email					= get_post_meta(  $payment_id, '_billing_email', true );
				$billing_phone					= get_post_meta(  $payment_id, '_billing_phone', true );
				$shipping_country				= get_post_meta(  $payment_id, '_shipping_country', true );
				$shipping_first_name			= get_post_meta(  $payment_id, '_shipping_first_name', true );
				$shipping_last_name				= get_post_meta(  $payment_id, '_shipping_last_name', true );
				$shipping_company				= get_post_meta(  $payment_id, '_shipping_company', true );
				$shipping_address_1				= get_post_meta(  $payment_id, '_shipping_address_1', true );
				$shipping_address_2				= get_post_meta(  $payment_id, '_shipping_address_2', true );
				$shipping_postcode				= get_post_meta(  $payment_id, '_shipping_postcode', true );
				$shipping_city					= get_post_meta(  $payment_id, '_shipping_city', true );
				$shipping_state					= get_post_meta(  $payment_id, '_shipping_state', true );
				$payment_method					= get_post_meta(  $payment_id, '_payment_method', true );
				$payment_method_title			= get_post_meta(  $payment_id, '_payment_method_title', true );
				$order_shipping					= get_post_meta(  $payment_id, '_order_shipping', true );
				$order_discount					= get_post_meta(  $payment_id, '_order_discount', true );
				$cart_discount					= get_post_meta(  $payment_id, '_cart_discount', true );
				$order_tax						= get_post_meta(  $payment_id, '_order_tax', true );
				$order_shipping_tax				= get_post_meta(  $payment_id, '_order_shipping_tax', true );
				$order_total					= get_post_meta(  $payment_id, '_order_total', true );
				$vat_compliance_country_info	= get_post_meta(  $payment_id, 'vat_compliance_country_info', true );
				$country_info					= maybe_unserialize( $vat_compliance_country_info );
				$country_code					= $country_info['taxable_address'][0];
				$wceuvat_conversion_rates		= get_post_meta(  $payment_id, 'wceuvat_conversion_rates', true );
				$vat_compliance_vat_paid		= get_post_meta(  $payment_id, 'vat_compliance_vat_paid', true );
				$vat_paid						= maybe_unserialize( $vat_compliance_vat_paid );
				$currency_code					= $vat_paid['currency'];
				$recorded_sales					= get_post_meta(  $payment_id, '_recorded_sales', true );
				$recorded_coupon_usage_counts	= get_post_meta(  $payment_id, '_recorded_coupon_usage_counts', true );
				$download_permissions_granted	= get_post_meta(  $payment_id, '_download_permissions_granted', true );
				$date							= get_post_meta(  $payment_id, '_completed_date', true );

				/** vat_compliance_country_info
					a:4:{
						s:6:"source";s:29:"geoip_detect_get_info_from_ip";
						s:4:"data";s:2:"GB";
						s:4:"meta";a:2:{
							s:2:"ip";
							s:13:"77.103.111.36";
							s:4:"info";O:11:"geoiprecord":14:{
								s:12:"country_code";s:2:"GB";
								s:13:"country_code3";s:3:"GBR";
								s:12:"country_name";s:14:"United Kingdom";
								s:6:"region";s:3:"ENG";
								s:4:"city";s:6:"London";
								s:11:"postal_code";s:4:"SW19";
								s:8:"latitude";d:51.408799999999999;
								s:9:"longitude";d:-0.2011;
								s:9:"area_code";N;
								s:8:"dma_code";N;
								s:10:"metro_code";N;
								s:14:"continent_code";s:2:"EU";
								s:11:"region_name";s:7:"England";
								s:8:"timezone";s:13:"Europe/London";
							}
						}
						s:15:"taxable_address";a:4:{
							i:0;s:2:"DE";
							i:1;s:0:"";
							i:2;s:6:"KT35EE";
							i:3;s:10:"New Malden";
						}
					}
				 */

				 /** vat_compliance_vat_paid

					a:10:{
						"by_rates" = array(
							[5] => array(
								"items_total" => 2.736,
								"shipping_total" => 0,
								"rate" => "19.0000",
								"name" => "VAT (19%)"
							),
							[31] => array(
								"items_total" => 0.9,
								"shipping_total" => 0,
								"rate" => "10.0000",
								"name" => "Dummy VAT"
							)
						)
						s:8:"by_rates";a:1:{
							i:5;a:4:{
								s:11:"items_total";d:4.4459999999999997;
								s:14:"shipping_total";i:0;
								s:4:"rate";s:7:"19.0000";
								s:4:"name";s:9:"VAT (19%)";
							}
						}
						s:11:"items_total";d:4.4459999999999997;
						s:14:"shipping_total";i:0;
						s:5:"total";d:4.4459999999999997;
						s:8:"currency";s:3:"GBP";
						s:13:"base_currency";s:3:"GBP";
						s:25:"items_total_base_currency";d:4.4459999999999997;
						s:28:"shipping_total_base_currency";i:0;
						s:19:"total_base_currency";d:4.4459999999999997;
						s:33:"base_currency_totals_are_reliable";b:1;
					}
				 */

				// Don't need to report sales into the establishment_country as 
				// these are reported on the VAT return so not part of MOSS
				if ($country_code == $establishment_country)
					continue;

				// Record the customer id
				$customer_id	= $customer_user;
				$customer_guid	= $this->GUID();

				// Has the customer information already been collected
				if ( !isset( $customers[$customer_id] ) )
				{
					$customers[$customer_id] = array 
					(
						'guid'				=> $customer_guid,
						'company'			=> $billing_company,
						'name'				=> 'unknown',
						'billing_address'	=> array
											   (
													'city' => $billing_city
											   ),
						'country_code'		=> $billing_country
					);
	
					if ($include_customer_details)
					{
						// Include the name, email address and first line of the physical address
						$customers[$customer_id]['email'] = $billing_email;
						$customers[$customer_id]['name'] = array
						(
							'first_name'	=> $billing_first_name,
							'last_name'		=> $billing_last_name
						);

						$address = $user_info['address'];
						$customers[$customer_id]['billing_address'] = 
							(!empty($address['billing_address_1'])	? ($address['billing_address_1'] . "\n") : "") .
							(!empty($address['billing_address_2'])	? ($address['billing_address_2'] . "\n") : "") .
							(!empty($address['billing_city'])		? ($address['billing_city']		 . "\n") : "") .
							(!empty($address['billing_state'])		? ($address['billing_state']	 . "\n") : "") .
							(!empty($address['billing_postcode'])	? ($address['billing_postcode']	 . "\n") : "");
					}
				}

				$rates				= $vat_paid['by_rates'];

				
				$currency_code	= $order_currency;
				$cart_tax		= $order_tax;

				// Only report sales in the EU
				if (!isset($eu_states[$country_code])) continue;

				$payment_amount	= $order_total;
				$gateway		= $payment_method; // cheque paypal cod

				$transaction = array();

				$transaction['source']			= $this->source;
				$transaction['id']				= $payment_id;
				$transaction['type']			= "IN";
				$transaction['purchase_key']	= $purchase_key;
				$transaction['date']			= $order->order_date;
				$transaction['country_code']	= $country_code;
				$transaction['currency_code']	= $currency_code;
				$transaction['customer_id']		= $customer_id;
				$transaction['evidence'][]		= "A";	// A – Billing Address of the Customer; 
														// B – IP address or geo-location; 
														// C – Bank Details; 
														// D – Mobile Country Codes or SIM card used by customer; 
														// E – Location of the fixed land-line used for service; 
														// F – Other Means;
				$transaction['evidence'][]		= "B";
				$transaction['ipaddress']		= $customer_ip_address;

				$total_tax = 0;
				$total_net = 0;
				$total_gross = 0;

				$items = array();

				$line_items = $order->get_items( 'line_item' );
				$index			= 0;

// error_log("Order");
// error_log(print_r($order,true));

				foreach ( $line_items as $item_id => $item ) {

					/*
						Each of these is an array
						_qty
						_tax_class
						_product_id
						_variation_id
						_line_subtotal
						_line_total
						_line_subtotal_tax
						_line_tax
						_line_tax_data
					 */

// error_log("Payment: $payment_id-$item_id");
// echo "shipping_tax_amount: " . wc_get_order_item_meta( $item_id, 'shipping_tax_amount' ) . "\n";

					$item_meta	= $order->get_item_meta( $item_id );
					$tax		= array_sum($item_meta['_line_tax']);
					// if ( $tax == 0 ) continue;

					$tax_data = maybe_unserialize( $item_meta['_line_tax_data'][0] );

					/*
						Array
						(
							[total] => Array
								(
									[5] => 2.736
								)

							[subtotal] => Array
								(
									[5] => 3.8
								)
						)
					 */

					$rate_index = key($tax_data['total']);

					// What's the rate for this sale?
					$rate = round( isset($rates[$rate_index]) ? $rates[$rate_index]['rate'] : 0, 3 );
					$rate /= 100;

					$rate_type = isset( $item_meta['_tax_class'] ) && count( $item_meta['_tax_class'] ) > 0 && $item_meta['_tax_class'][0] ? $item_meta['_tax_class'][0] : 'reduced';
					$service_code = saf_service_code_to_use($item_id);

					$line_item = array();

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

					/** Discounts

						It seems that WooCommerce does not hold discounts by item. 
						Discount amount information is held in the database in the
						order_itemmeta table.  However, these are aggregates of the
						discounts by coupon type not discounts for each line item.
						So for now line level discounts will be computed by subtracting
						the sale value from the price (taking into account whether
						prices include tax or not.
					  
					 */

					$product							= wc_get_product( $item['product_id'] );
					$price								= $product->get_price_excluding_tax();
					$net								= array_sum($item_meta['_line_total']);
					$line_item['net']					= apply_filters( 'moss_saf_get_net_transaction_amount', $net, $net, $payment_id);
					$line_item['amount']				= apply_filters( 'moss_saf_get_net_transaction_amount', $line_item['net'] + $tax, $payment_id);;


					$line_item['id']					= $item_id;
					$line_item['code']					= $service_code;
					$line_item['name']					= $item['name'];
					$line_item['quantity']				= $item['qty'];
					$line_item['measure']				= $currency_code;
					$line_item['price']					= $price;
					$line_item['debits']				= array();
					$line_item['debits']['discount']	= $price - $line_item['net'];

					$line_item['credits']				= array();
					if ( isset($item['fees']) )
					{
						$total_fees = 0;
						$line_item['credits']['fees']	= $total_fees;
					}

					$line_item['tax']					= array(
															'region'	=> '',
															'vat_type'	=> $rate_type,
															'vat_rate'	=> $rate,
															'amount'	=> $tax
														);

					$line_item['credits']['invoice']	= $line_item['amount'];
					
					$total_tax		+= $tax;
					$total_net		+= $line_item['net'];
					$total_gross	+= $line_item['amount'];

					$items[]							= $line_item;
							
				}

				$transaction['items'] = $items;
				
				// Need to handle fees?
				$transaction['fees']['net']['shipping'] = $order_shipping;
				$transaction['fees']['tax']['shipping']	 = $order_shipping_tax;

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
													'tax'		=> $total_tax + array_sum( $transaction['fees']['tax'] ),
													'amount'	=> $total_net + array_sum( $transaction['fees']['net'] ),
													'gross'		=> $total_gross + array_sum( $transaction['fees']['net'] ) + array_sum( $transaction['fees']['tax'] ),
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
new MOSS_SAF_Integration_WOOC;
