<?php
/**
 * Roles and Capabilities
 *
 * @package     vat-moss
 * @subpackage  Classes/Roles
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
*/

namespace lyquidity\vat_moss_saf;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * MOSS_SAF_Roles Class
 *
 * This class handles the role creation and assignment of capabilities for those roles.
 *
 * These roles let us have MOSS SAF Submitter, MOSS SAF Reviewer, etc, each of whom can do
 * certain things within the VAT MOSS SAF information
 *
 * @since 1.0
 */
class MOSS_SAF_Roles {

	/**
	 * Get things going
	 *
	 * @since 1.4.4
	 */
	public function __construct() {

	}

	/**
	 * Add new shop roles with default WP caps
	 *
	 * @access public
	 * @since 1.4.4
	 * @return void
	 */
	public function add_roles() {
		add_role( 'moss_saf_submitter', __( 'MOSS SAF Submitter', 'vat_moss_saf' ), array(
			'read'                   => true,
			'edit_posts'             => true,
			'delete_posts'           => true
		) );

		add_role( 'moss_saf_reviewer', __( 'MOSS SAF Reviewer', 'vat_moss_saf' ), array(
		    'read'                   => true,
		    'edit_posts'             => false,
		    'delete_posts'           => false
		) );

	}

	/**
	 * Add new shop roles with default WP caps
	 *
	 * @access public
	 * @since 1.4.4
	 * @return void
	 */
	public function remove_roles() {
		remove_role( 'moss_saf_submitter' );
		remove_role( 'moss_saf_reviewer' );
	}

	/**
	 * Add new shop-specific capabilities
	 *
	 * @access public
	 * @since  1.4.4
	 * @global WP_Roles $wp_roles
	 * @return void
	 */
	public function add_caps() {
		global $wp_roles;

		if ( class_exists('WP_Roles') ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			$wp_roles->add_cap( 'moss_saf_submitter', 'view_definitions' );
			$wp_roles->add_cap( 'moss_saf_submitter', 'send_definitions' );
			$wp_roles->add_cap( 'moss_saf_submitter', 'export_definitions' );
			$wp_roles->add_cap( 'moss_saf_submitter', 'edit_definitions' );

			$wp_roles->add_cap( 'administrator', 'view_definitions' );
			$wp_roles->add_cap( 'administrator', 'send_definitions' );
			$wp_roles->add_cap( 'administrator', 'export_definitions' );
			$wp_roles->add_cap( 'administrator', 'edit_definitions' );
			$wp_roles->add_cap( 'administrator', 'delete_definitions' );
			$wp_roles->add_cap( 'administrator', 'delete_definition_logs' );

			$wp_roles->add_cap( 'moss_saf_reviewer', 'view_definitions' );
			$wp_roles->add_cap( 'moss_saf_reviewer', 'export_definitions' );
			$wp_roles->add_cap( 'moss_saf_reviewer', 'edit_definitions' );

			// Add the main post type capabilities
			$capabilities = $this->get_core_caps();
			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'moss_saf_submitter', $cap );
					$wp_roles->add_cap( 'administrator', $cap );
					$wp_roles->add_cap( 'moss_saf_reviewer', $cap );
				}
			}
		}
	}

	/**
	 * Gets the core post type capabilities
	 *
	 * @access public
	 * @since  1.4.4
	 * @return array $capabilities Core post type capabilities
	 */
	public function get_core_caps() {
		$capabilities = array();

		$capability_types = array( 'definition' );

		foreach ( $capability_types as $capability_type ) {
			$capabilities[ $capability_type ] = array(
				// Post type
				"edit_{$capability_type}",
				"read_{$capability_type}",
				"delete_{$capability_type}",
				"send_{$capability_type}",
				"edit_{$capability_type}s",
				"delete_{$capability_type}s",
				"delete_{$capability_type}_logs",
				"delete_sent_{$capability_type}s",
				"edit_sent_{$capability_type}s",
			);
		}

		return $capabilities;
	}

	/**
	 * Remove core post type capabilities (called on uninstall)
	 *
	 * @access public
	 * @since 1.5.2
	 * @return void
	 */
	public function remove_caps() {
		
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}
		}

		if ( is_object( $wp_roles ) ) {
			/** Shop Manager Capabilities */
			$wp_roles->remove_cap( 'moss_saf_submitter', 'view_definitions' );
			$wp_roles->remove_cap( 'moss_saf_submitter', 'send_definitions' );
			$wp_roles->remove_cap( 'moss_saf_submitter', 'export_definitions' );
			$wp_roles->remove_cap( 'moss_saf_submitter', 'edit_definitions' );

			$wp_roles->remove_cap( 'administrator', 'view_definitions' );
			$wp_roles->remove_cap( 'administrator', 'send_definitions' );
			$wp_roles->remove_cap( 'administrator', 'export_definitions' );
			$wp_roles->remove_cap( 'administrator', 'edit_definitions' );
			$wp_roles->remove_cap( 'administrator', 'delete_definitions' );
			$wp_roles->remove_cap( 'administrator', 'delete_definition_logs' );

			$wp_roles->remove_cap( 'moss_saf_reviewer', 'view_definitions' );
			$wp_roles->remove_cap( 'moss_saf_reviewer', 'export_definitions' );
			$wp_roles->remove_cap( 'moss_saf_reviewer', 'edit_definitions' );

			/** Remove the Main Post Type Capabilities */
			$capabilities = $this->get_core_caps();

			foreach ( $capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->remove_cap( 'moss_saf_submitter', $cap );
					$wp_roles->remove_cap( 'administrator', $cap );
					$wp_roles->remove_cap( 'moss_saf_reviewer', $cap );
				}
			}
		}
	}
}
$vat_moss_saf_roles = new MOSS_SAF_Roles;
