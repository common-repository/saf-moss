<?php

/**
 * MOSS Integrations controller
 *
 * @package     vat-moss_saf
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

namespace lyquidity\vat_moss_saf;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class MOSS_SAF_WP_Integrations {

	public $issues = array();
	private	$last_updated_option_name = "moss_saf_euros_last_updated";
	private	$last_data_option_name = "moss_saf_euros_last_data";

	private	$euro_zone_states = array("AT","BE","CY","EE","FI","FR","DE","GR","IE","IT","LV","LT","LU","MT","NL","PT","SK","SI","ES");
	private $not_euro_zone_states = array(
		"BG" => "BGN",
		"HR" => "HRK",
		"CZ" => "CZK",
		"DK" => "DKK",
		"GB" => "GBP",
		"HU" => "HUF",
		"PL" => "PLN",
		"RO" => "RON",
		"SE" => "SEK"
	);
	public function __construct() {

		$this->load();

	}

	public static function get_integrations() {

		return apply_filters('moss_saf_integration_instance', array());
	}

	public static function get_integrations_list() {

		return array_reduce(
			MOSS_SAF_WP_Integrations::get_integrations(),
			function($carry, $instance)
			{
				$carry[$instance->source] = $instance->name;
				return $carry;
			}
		);
	}

	public static function get_enabled_integrations() {
		return vat_moss_saf()->settings->get( 'integrations', array() );
	}

	public function get_post_types()
	{
		$result = array();

		foreach(MOSS_SAF_WP_Integrations::get_integrations() as $integration)
		{
			if (!is_a($integration, 'lyquidity\vat_moss_saf\MOSS_SAF_Integration_Base') || !isset($this->enabled_integrations[$integration->source])) continue;
			if (!isset($integration->post_type) || empty($integration->post_type)) continue;

			$post_type = $integration->post_type;
			$result[$integration->post_type] = $integration->post_type;
		}
		
		return $result;
	}

	public function load() {

		$integrations_dir = VAT_MOSS_SAF_INCLUDES_DIR . 'integrations/';

		// Load each enabled integrations
		require_once $integrations_dir . 'class-base.php';

		if ($handle = opendir($integrations_dir))
		{
			try
			{
				while (false !== ($file = readdir($handle)))
				{
					if ($file === "." || $file === ".." || $file === "class-base.php" || strpos($file, 'class') !== 0) continue;
					
					$filename  = $integrations_dir . $file;

					if(!is_file($filename)) continue;

					require_once $filename;
				}
			}
			catch(Exception $ex)
			{}

			closedir($handle);
		} 

		$this->enabled_integrations = apply_filters( 'moss_saf_enabled_integrations', MOSS_SAF_WP_Integrations::get_enabled_integrations() );
	}

	/**
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
	 *			indicator			0: Goods, 2: Triangulated sale, 3: Services (reverse charge)
	 *			customer_id			The name of the buyer
	 * 			country_code		Country code of the consumer
	 *			currency_code		Currency of the sale
	 *			evidence			Evidence used for the sale (enumeration)
	 *			items
	 *			{
	 *				[0] 
	 *				{
	 *					code		service code
     *					name		description
	 *					quantity	
	 *					measure		Denomination of the quantity
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
	 * @string	startDate				strtotime() compatible date of the earliest record to return
	 * @string	endDate					strtotime() compatible date of the latest record to return.
	 * @string	target_currency			The currency in which the results should be expressed.
	 */
	public function get_vat_information($startDate, $endDate, $target_currency = "EUR")
	{
		require_once(VAT_MOSS_INCLUDES_DIR . 'vatidvalidator.php');

		$include_customer_details = include_customer_details();
		$vat_records = array();

		try
		{
			foreach(MOSS_SAF_WP_Integrations::get_integrations() as $key => $integration)
			{
				if (!is_a($integration, 'lyquidity\vat_moss_saf\MOSS_SAF_Integration_Base') || !isset($this->enabled_integrations[$integration->source])) continue;

				if ( ( !is_numeric($startDate) && !strtotime($startDate) ) || ( !is_numeric($startDate) && !strtotime($endDate) ) )
				{
					$this->issues[] = "The range dates are not valid";
					return false;
				}

				// Make sure the dates 
				$startDate = is_numeric($startDate)
					? date('Y-m-d 00:00:00', $startDate)
					: date('Y-m-d 00:00:00', strtotime($startDate));

				$endDate   = is_numeric($endDate)
					? date('Y-m-d 23:59:59', $endDate)
					: date('Y-m-d 23:59:59', strtotime($endDate));

				$vat_records	= $integration->get_vat_information($startDate, $endDate, $include_customer_details);
				$vat_type_names = $integration->get_vat_type_names();

				// For testing - select just the first record
				// $record = reset($vat_records['transactions']);
				// $vat_records['transactions'] = array();
				// $vat_records['transactions'][] = $record;

				$replacements = array();
				$total_debits = 0;
				$total_credits = 0;

				foreach($vat_records['transactions'] as $key => $vat_record)
				{
					$currency_code	= $vat_record['currency_code'];
					$payment_id		= $vat_record['id'];
					$date			= $vat_record['date'];
					
					$replacement_items = array();

					foreach( $vat_record['items'] as $key => $line_item )
					{
						if ($currency_code !== $target_currency)
						{
							$line_item['price']					= $this->translate_amount( $line_item['price'],				 $date, $currency_code, $target_currency, $payment_id );
							$line_item['debits']['discount']	= $this->translate_amount( $line_item['debits']['discount'], $date, $currency_code, $target_currency, $payment_id );
							$line_item['credits']['fees']		= $this->translate_amount( $line_item['credits']['fees'],	 $date, $currency_code, $target_currency, $payment_id );
							$line_item['credits']['invoice']	= $this->translate_amount( $line_item['credits']['invoice'], $date, $currency_code, $target_currency, $payment_id );
							$line_item['tax']['amount']			= $this->translate_amount( $line_item['tax']['amount'],		 $date, $currency_code, $target_currency, $payment_id );
							$line_item['net']					= $this->translate_amount( $line_item['tax']['amount'],		 $date, $currency_code, $target_currency, $payment_id );
							$line_item['amount']				= $this->translate_amount( $line_item['amount'],			 $date, $currency_code, $target_currency, $payment_id );
							$line_item['credits']['invoice']	= $this->translate_amount( $line_item['credits']['invoice'], $date, $currency_code, $target_currency, $payment_id );
						}

						$line_item['tax']['vat_type'] = $this->lookup_vat_rate_name( $vat_type_names, $line_item['tax']['vat_type'] );
						
						$replacement_items[] = $line_item;
						$total_credits += array_sum( $line_item['credits'] );
						$total_debits  += array_sum( $line_item['debits']  );
					}

					$vat_record['items'] = $replacement_items;

					if ($currency_code !== $target_currency)
					{
						$vat_record['fees']							= $this->translate_amount( $vat_record['fees'],							$vat_record['date'], $currency_code, $target_currency, $payment_id );
						$vat_record['total']['tax']					= $this->translate_amount( $vat_record['total']['tax'],					$vat_record['date'], $currency_code, $target_currency, $payment_id );
						$vat_record['total']['amount']				= $this->translate_amount( $vat_record['total']['amount'],				$vat_record['date'], $currency_code, $target_currency, $payment_id );
						$vat_record['total']['gross']				= $this->translate_amount( $vat_record['total']['gross'],				$vat_record['date'], $currency_code, $target_currency, $payment_id );
						$vat_record['total']['payment']['amount']	= $this->translate_amount( $vat_record['total']['payment']['amount'],	$vat_record['date'], $currency_code, $target_currency, $payment_id );
					}

					$replacements[] = $vat_record;
				}
				
				$vat_records['transactions']	= $replacements;
				$vat_records['total_credits']	= $total_credits;
				$vat_records['total_debits']	= $total_debits;
			}

			return $vat_records;
		}
		catch(\Exception $ex)
		{
			$this->issues[] = $ex->getMessage();
		}

		return false;
	}

	public function lookup_vat_rate_name($vat_type_names, $vat_type)
	{
		$result = !empty($vat_type)
			? (isset( $vat_type_names[$vat_type] )
				? $vat_type_names[$vat_type]
				: $vat_type_names['reduced']
			  )
			: $vat_type_names['reduced'];

		return ($result === 'Standard')
			? 'STD'
			: 'RED';
	}
	
	function translate_amount($amount, $date, $from_currency, $to_currency, $payment_id = 0 )
	{
		if (strcasecmp($from_currency, $to_currency) == 0)
			return $amount;

		$not_euro_zone = in_array( strtoupper( $to_currency ), $this->not_euro_zone_states );

		return $not_euro_zone
			? $this->translate_to_non_euro_currency( $amount, strtoupper( $from_currency ), strtoupper( $to_currency ), $date, $payment_id  )
			: $this->translate_to_euros( $amount, $from_currency, $date, $payment_id  );
			
		return $amount;
	}

	function translate_to_euros( $amount, $from_currency, $time, $payment_id  )
	{
		// Maybe the shop already knows the correct rate to use
		$shop_exchange_rate = apply_filters( 'moss_saf_get_shop_exchange_rate', false, $payment_id );
		
		// If the result is not a positive number then use the ECB rates
		if ($shop_exchange_rate === false || !is_numeric($shop_exchange_rate) || $shop_exchange_rate <= 0 )
		{
			$rates = $this->get_euro_rates_for_date( $time );
			if ($rates === false) return $amount;
			
			// OK, got some rates so convert
			if (!isset( $rates[$from_currency] ))
				return $amount;

			return round( $amount / $rates[$from_currency], 2);
		}
		else
		{
			return round( $amount * $shop_exchange_rate, 2);
		}
	}

	function translate_to_non_euro_currency( $amount, $from_currency, $to_currency, $time, $payment_id  )
	{
		// Maybe the shop already knows the correct rate to use
		$shop_exchange_rate = apply_filters( 'moss_saf_get_shop_exchange_rate', false, $payment_id );
		
		// If the result is not a positive number then use the ECB rates
		if ($shop_exchange_rate === false || !is_numeric($shop_exchange_rate) || $shop_exchange_rate <= 0 )
		{
			$rates = $this->get_euro_rates_for_date( $time );

			if ($rates === false) return $amount;

			// OK, got some rates so convert.  First to EUR then to base
			$euro_amount = $amount;
			if (strcasecmp( $from_currency, 'EUR' ) !== 0)
			{
				$from_currency = strtoupper( $from_currency );
				if (!isset( $rates[$from_currency] ) )
					return $amount;

				$euro_amount = $amount / $rates[$from_currency];
			}

			// Now from EUR to the base currency
			return round( $euro_amount * $rates[$to_currency], 2 );
		}
		else
		{
			return round( $amount * $shop_exchange_rate, 2 );
		}
	}

	/**
	 * Gets the rates array for the day represented in the $timestamp or the next earlier day
	 * @array rates An array of daily rate arrays
	 * @int timestamp A timestamp representing a date/time
	 * @return An array of rates for a date or false
	 */
	function get_rates_for_date( $rates, $timestamp )
	{
		if (!isset( $rates ) || !$rates )
			return false;

		$date = date( "Y-m-d", $timestamp );

		if ( isset( $this->euro_rates[$date] ) )
		{
			// This is the easy case
			return $this->euro_rates[$date];
		}

		// More difficult because the nearest older date is required
		foreach( $this->euro_rates as $key => $rates)
		{
			$date_stamp = strtotime( $key );

			if ($timestamp >= $date_stamp)
				return $rates;
		}

		return false;
	}

	/**
	 * Gets the euro rates for a time by calling init_euro_rates() then get_rates_for_date() if there are any rates available
	 * @string $time A time string in the form y-m-d H:i:s
	 * @return a rates array for the time or false
	 */
	function get_euro_rates_for_date( $time )
	{
		// Get the rates
		$rates_ok = $this->init_euro_rates();
		// If there are none and no rates could be retrieved then return false.
		if ($rates_ok === false) return false;

		// Get the best rate for the date
		return $this->get_rates_for_date( $this->euro_rates, strtotime( $time ) );
	}

	/**
	 * Report errors retrieving Euro rates 
	 */
	function handle_euro_error()
	{
		$this->issues[] = empty($this->euro_rates)
			? __( "An error occurred reading Euro exchange rates and no exchange rates have been read.", "vat_moss_saf" )
			: __( "An error occurred reading Euro exchange rates. The existing rates will be used but these may be inaccurate.", "vat_moss_saf" );

		return false;
	}

	/**
	 * Get and/or update the Euro rates
	 * @return True if there is no problem
	 */
	function init_euro_rates()
	{
		// Don't need to read all the rates every time a transaction is to be translated
		if (isset($this->euro_rates) && !empty($this->euro_rates))
			return true;

		$last_updated = get_site_option($this->last_updated_option_name);
		if (empty($last_updated)) $last_updated = 0;
		$this->euro_rates = get_site_option($this->last_data_option_name);

		if (!empty($this->euro_rates))
		{
			$this->euro_rates = $this->euro_rates = maybe_unserialize($this->euro_rates);
		}

		if (!empty($this->euro_rates) && ($last_updated + 60*60*12) > time()) {
			return true;
		}

		if (empty($this->euro_rates) && file_exists( dirname(__FILE__) . "/../assets/ecb-rates-q4-2014.xml" ) )
		{
			// There are no rates at all so begin by loading Q4-2014
			$new_data = simplexml_load_file(dirname(__FILE__) . "/../assets/ecb-rates-q4-2014.xml");
			$this->load_rates( $new_data );
		}

		$fetched = wp_remote_get("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-hist-90d.xml");
		if (is_wp_error($fetched) || 
			empty($fetched['response']) || 
			$fetched['response']['code'] >= 300 || 
			empty( $fetched['body'] ) ||
			strpos( $fetched['body'], '<!DOCTYPE HTML' ) !== false
		)
		{
			return $this->handle_euro_error();
		}

		$new_data = simplexml_load_string( $fetched['body'] );
		return $this->load_rates( $new_data );
	}
	
	function load_rates($new_data)
	{
		if ($new_data === false)
		{
			return $this->handle_euro_error();
		}

		$new_rates = array();
		foreach($new_data->Cube->Cube as $rates)
		{
			$day_rates = array();
			foreach($rates->Cube as $key => $rate)
			{
				$day_rates[(string) $rate['currency']] = floatval($rate['rate']);
			}
			$new_rates[(string)$rates['time']] = $day_rates;
		}

		if (count($new_rates) === 0) return true;

		$this->euro_rates = empty($this->euro_rates) ? $new_rates : array_merge($new_rates, $this->euro_rates );

		update_site_option($this->last_data_option_name, serialize( $this->euro_rates ));
		update_site_option($this->last_updated_option_name, time());

		return true;
	}

	/**
	 * Flattens an array generated by get_vat_information() or get_vat_record_information()
	 *
	 */
	function flatten_vat_information($hierarchical_vat_payments)
	{
		$vat_payments = array();

		if (is_array($hierarchical_vat_payments))
		{
			foreach($hierarchical_vat_payments as $key => $payment)
			{
				$new_payment = array();

				// Only the first instance of an expanded payment should be included
				// This flag is used to indicate this condition
				$first = true;

				foreach($payment['values'] as $indicator => $amount)
				{
					foreach($payment as $key => $value)
					{
						if ($key === 'values') continue;
						$new_payment[$key] = $value;
					}

					$new_payment['indicator'] = $indicator;
					$new_payment['value'] = $amount;
					$new_payment['first'] = $first;

					$vat_payments[] = $new_payment;

					$first = false;
				}
			}
		}

		return $vat_payments;
	}

}