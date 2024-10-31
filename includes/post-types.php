<?php
/**
 * MOSS Type Functions
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
 * Registers and sets up the Downloads custom post type
 *
 * @since 1.0
 * @return void
 */
function setup_vat_moss_saf_post_types() {

	$archives = false;
	$slug     = 'moss-saf-definitions';
	$rewrite  = array('slug' => $slug, 'with_front' => false);

	$vat_moss_saf_labels =  apply_filters( 'vat_moss_saf_labels', array(
		'name' 				=> '%2$s',
		'singular_name' 	=> '%1$s',
		'add_new' 			=> __( 'Add New', 'vat_moss_saf' ),
		'add_new_item' 		=> __( 'Add New %1$s', 'vat_moss_saf' ),
		'edit_item' 		=> __( 'Edit %1$s', 'vat_moss_saf' ),
		'new_item' 			=> __( 'New %1$s', 'vat_moss_saf' ),
		'all_items' 		=> __( 'All %2$s', 'vat_moss_saf' ),
		'view_item' 		=> __( 'View %1$s', 'vat_moss_saf' ),
		'search_items' 		=> __( 'Search %2$s', 'vat_moss_saf' ),
		'not_found' 		=> __( 'No %2$s found', 'vat_moss_saf' ),
		'not_found_in_trash'=> __( 'No %2$s found in Trash', 'vat_moss_saf' ),
		'parent_item_colon' => '',
		'menu_name' 		=> __( '%2$s', 'vat_moss_saf' )
	) );

	$post_type_labels = array();
	foreach ( $vat_moss_saf_labels as $key => $value ) {
	   $post_type_labels[ $key ] = sprintf( $value, vat_moss_saf_get_label_singular(), vat_moss_saf_get_label_plural() );
	}

	$vat_moss_saf_args = array(
		'labels' 			=> $post_type_labels,
		'public' 			=> false,
		'query_var' 		=> false,
		'rewrite' 			=> false,
		'capability_type' 	=> 'definition',
		'map_meta_cap'      => true,
		'supports' 			=> array( 'title', 'author' ),
		'can_export'		=> true,
		'menu_icon'			=> 'dashicons-book'
	);
	register_post_type( 'moss_saf_definition', apply_filters( 'vat_moss_saf_post_type_args', $vat_moss_saf_args ) );
}
add_action( 'init', '\lyquidity\vat_moss_saf\setup_vat_moss_saf_post_types', 1 );

/**
 * Get Default Labels
 *
 * @since 1.0
 * @return array $defaults Default labels
 */
function vat_moss_saf_get_default_definition_labels() {
	$defaults = array(
	   'singular' => __( 'MOSS SAF Definition', 'vat_moss_saf' ),
	   'plural' => __( 'MOSS SAF Definitions', 'vat_moss_saf')
	);
	return apply_filters( 'vat_moss_saf_default_definition_name', $defaults );
}

/**
 * Get Singular Label
 *
 * @since 1.0
 *
 * @param bool $lowercase
 * @return string $defaults['singular'] Singular label
 */
function vat_moss_saf_get_label_singular( $lowercase = false ) {
	$defaults = vat_moss_saf_get_default_definition_labels();
	return ($lowercase) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
}

/**
 * Get Plural Label
 *
 * @since 1.0
 * @return string $defaults['plural'] Plural label
 */
function vat_moss_saf_get_label_plural( $lowercase = false ) {
	$defaults = vat_moss_saf_get_default_definition_labels();
	return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
}

/**
 * Change default "Enter title here" input
 *
 * @since 1.4.0.2
 * @param string $title Default title placeholder text
 * @return string $title New placeholder text
 */
function vat_moss_saf_change_default_title( $title ) {
     // If a frontend plugin uses this filter (check extensions before changing this function)
     if ( !is_admin() ) {
     	$label = vat_moss_saf_get_label_singular();
        $title = sprintf( __( 'Enter %s title here', 'vat_moss_saf' ), $label );
     	return $title;
     }
     
     $screen = get_current_screen();

     if  ( 'definition' == $screen->post_type ) {
     	$label = vat_moss_saf_get_label_singular();
        $title = sprintf( __( 'Enter %s title here', 'vat_moss_saf' ), $label );
     }

     return $title;
}
add_filter( 'enter_title_here', '\lyquidity\vat_moss_saf\vat_moss_saf_change_default_title' );

/**
 * Registers Custom Post Statuses which are used by the definition
 * Codes
 *
 * @since 1.0
 * @return void
 */
function vat_moss_saf_register_post_type_statuses() {
	// Payment Statuses
	register_post_status( 'failed', array(
		'label'                     => _x( 'Failed', 'Failed generation status', 'vat_moss_saf' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'vat_moss_saf' )
	)  );
	register_post_status( 'generated', array(
		'label'                     => _x( 'Generated', 'Generated status', 'vat_moss_saf' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Generated <span class="count">(%s)</span>', 'Generated <span class="count">(%s)</span>', 'vat_moss_saf' )
	)  );
	register_post_status( 'not_generated', array(
		'label'                     => _x( 'Not generated', 'Not generated status', 'vat_moss_saf' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Unknown <span class="count">(%s)</span>', 'Unknown <span class="count">(%s)</span>', 'vat_moss_saf' )
	)  );
	register_post_status( 'unknown', array(
		'label'                     => _x( 'Unknown', 'Unknown status', 'vat_moss_saf' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Unknown <span class="count">(%s)</span>', 'Unknown <span class="count">(%s)</span>', 'vat_moss_saf' )
	)  );
}
add_action( 'init', '\lyquidity\vat_moss_saf\vat_moss_saf_register_post_type_statuses' );

/**
 * Updated Messages
 *
 * Returns an array of with all updated messages.
 *
 * @since 1.0
 * @param array $messages Post updated message
 * @return array $messages New post updated messages
 */
function vat_moss_saf_updated_messages( $messages ) {
	global $post, $post_ID;

	$url1 = '<a href="' . get_permalink( $post_ID ) . '">';
	$url2 = vat_moss_saf_get_label_singular();
	$url3 = '</a>';

	$messages['definition'] = array(
		1 => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'vat_moss_saf' ), $url1, $url2, $url3 ),
		4 => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'vat_moss_saf' ), $url1, $url2, $url3 ),
		6 => sprintf( __( '%2$s published. %1$sView %2$s%3$s.', 'vat_moss_saf' ), $url1, $url2, $url3 ),
		7 => sprintf( __( '%2$s saved. %1$sView %2$s%3$s.', 'vat_moss_saf' ), $url1, $url2, $url3 ),
		8 => sprintf( __( '%2$s submitted. %1$sView %2$s%3$s.', 'vat_moss_saf' ), $url1, $url2, $url3 )
	);

	return $messages;
}
add_filter( 'post_updated_messages', '\lyquidity\vat_moss_saf\vat_moss_saf_updated_messages' );
