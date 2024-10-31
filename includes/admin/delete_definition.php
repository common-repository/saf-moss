<?php

/**
 * MOSS SAF Delete a definition
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

function delete_definition($id, $delete_post = true)
{
	if (!current_user_can('delete_definitions'))
	{
		echo "<div class='error'><p>" . __('You do not have rights to delete a definition.', 'vat_moss_saf' ) . "</p></div>";
		return;
	}
	
	if ($delete_post)
	{
		wp_delete_post( $id, $delete_post );

		delete_post_meta( $id, 'vat_number' );
		delete_post_meta( $id, 'submitter' );
		delete_post_meta( $id, 'email' );
		delete_post_meta( $id, 'from_year' );
		delete_post_meta( $id, 'from_month' );
		delete_post_meta( $id, 'to_year' );
		delete_post_meta( $id, 'to_month' );
		delete_post_meta( $id, 'report' );
		delete_post_meta( $id, 'transaction_from_year' );
		delete_post_meta( $id, 'transaction_to_year' );
		delete_post_meta( $id, 'transaction_from_month' );
		delete_post_meta( $id, 'transaction_to_month' );
	}

	return true;
}

?>