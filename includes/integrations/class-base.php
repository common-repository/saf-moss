<?php

/**
 * MOSS SAF Base class for integrations
 *
 * @package     vat-moss-saf
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

namespace lyquidity\vat_moss_saf;

abstract class MOSS_SAF_Integration_Base {

	/**
	 * The source for referrals. This refers to the integration that is being used.
	 *
	 * @access  public
	 * @since   1.0
	 */
	public $source;

	/**
	 * The name associated with the source for referrals.
	 *
	 * @access  public
	 * @since   1.0
	 */
	public $name;

	/**
	 * The post_type associated with the source one which the indicator metabox should appear.
	 *
	 * @access  public
	 * @since   1.0
	 */
	public $post_type = '';

	/**
	 * The default indicator type to assume for products associated with the source.
	 *
	 * @access  public
	 * @since   1.0
	 */
	public $default_indicator = 3; // Services

	/**
	 * Constructor
	 *
	 * @access  public
	 * @since   1.0
	 */
	public function __construct() {

		$this->init();
	}

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */
	public function init() {

	}

	/**
	 * Returns an associative array of the VAT type names indexed by type
	 */
	function get_vat_type_names()
	{
		return array(
			'standard'		=> 'Standard',
			'reduced'		=> 'Reduced',
			'superreduced'	=> 'Super reduced',
			'parking'		=> 'Parking',
			'increased'		=> 'Increased',
			'enhanced'		=> 'Enhanced'
		);
	}

	/**
	 * Returns an array of VAT information:
	 *	id				Database id for the sale
	 *	purchase_key	Unique purchase identifier
	 *	date			Datetime of the completed transaction
	 *	correlation_id	Existing correlation_id (if any)
	 *	buyer			The name of the buyer
	 *	values			An array of sale values before any taxes indexed by the indicator.  
	 *						0: Goods, 2: Triangulated sale, 3: Services (reverse charge)
	 *						Values with the same indicator will be accumulated
	 *
	 * If you have more than one sale to a client the value of those sales can be aggregated
	 * If the sale is across two service types (different indicators) then they need to appear as different entries
	 *
	 * @string	startDate				strtotime() compatible date of the earliest record to return
	 * @string	endDate					strtotime() compatible date of the latest record to return.
	 */
	public function get_vat_information( $startTimestamp, $endTimestamp, $include_customer_details = false )
	{}
	
	function GUID()
	{
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}

		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}

}

?>