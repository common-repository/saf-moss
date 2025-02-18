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

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if (!function_exists('convert_to_screen'))
{
	function convert_to_screen( $hook_name ) {

		global $hook_suffix;
		if ( ! isset( $hook_suffix ) ) $hook_suffix = '';

		$screen = WP_Screen::get( $hook_name );
		$screen->set_current_screen();
		return $screen;
	}
}

?>