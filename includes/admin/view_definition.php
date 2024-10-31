<?php

/**
 * MOSS SAF View a definition (uses 'new' definition)
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

function view_definition($id)
{
	$from_year	= get_post_meta( $id, 'from_year',	true );
	$from_month	= get_post_meta( $id, 'from_month',	true );
	$to_year	= get_post_meta( $id, 'to_year',	true );
	$to_month	= get_post_meta( $id, 'to_month',	true );

	new_definition( $from_year, $from_month, $to_year, $to_month, $id, true );
}

?>
