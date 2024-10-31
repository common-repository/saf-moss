<?php

/**
 * MOSS SAF Main menu class
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

class MOSS_Admin_Menu {
	
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
	}

	public function register_menus() {
	
		// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
		add_menu_page( __( 'SAF-MOSS Definitions', 'vat_moss_saf' ), __( 'SAF-MOSS', 'vat_moss_saf' ), 'view_definitions', 'moss-saf-definitions', '\lyquidity\vat_moss_saf\moss_saf_definitions', 'dashicons-book' );
		// add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		add_submenu_page( 'moss-saf-definitions', __( 'Definitions', 'vat_moss_saf' ), __( 'Definitions', 'vat_moss_saf' ), 'view_definitions', 'moss-saf-definitions', '\lyquidity\vat_moss_saf\moss_saf_definitions' );
		add_submenu_page( 'moss-saf-definitions', __( 'Settings', 'vat_moss_saf' ), __( 'Settings', 'vat_moss_saf' ), 'view_definitions', 'moss-saf-settings', '\lyquidity\vat_moss_saf\moss_saf_settings' );
	}
}
$moss_menu = new MOSS_Admin_Menu;