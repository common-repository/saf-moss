<?php

/**
 * MOSS SAF Create a new definition (also used by 'edit' and 'view')
 *
 * @package     vat-moss-saf
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

namespace lyquidity\vat_moss_saf;

function get_setting($id, $key)
{
	$result = '';

	if ($id)
		$result = get_post_meta( $id, $key, true );
		
	if (!empty($result)) return $result;

	return isset($_REQUEST[$key]) ? $_REQUEST[$key] : vat_moss_saf()->settings->get($key);
}

function new_definition($from_year = null, $from_month = null, $to_year = null, $to_month = null, $definition_id = 0, $read_only = false)
{
	$locale = localeconv();

	if (!current_user_can('edit_definitions'))
	{
		echo "<div class='error'><p>" . __('You do not have rights to perform this action.', 'vat_moss_saf' ) . "</p></div>";
		show_definitions();
		return;
	}

	$state = get_post_status($definition_id);
	if (($state === STATE_GENERATED ) && !$read_only)
	{
		echo "<div class='error'><p>" . __('This action is not valid on a definition that is complete or acknowledged.', 'vat_moss_saf' ) . "</p></div>";
		show_definitions();
		return;
	}

	advert( 'standard-audit-file-saf-moss', VAT_MOSS_SAF_VERSION, function() use( $from_year, $from_month, $to_year, $to_month, $definition_id, $read_only ) {

		global $selected;

		$title = $definition_id
			? ($read_only
				? __( 'View Definition', 'vat_moss_saf' )
				: __( 'Edit Definition', 'vat_moss_saf' )
			  )
			: __( 'New Definition', 'vat_moss_saf' );

		$title .= $definition_id ? " ($definition_id)" : "";
		$vrn			= get_setting( $definition_id, 'vat_number');
		$submitter		= get_setting( $definition_id, 'submitter');
		$email			= get_setting( $definition_id, 'email');

		$definition_key	= get_setting( $definition_id, 'definition_key');
		
		$definition_period = ($definition_id)
			? $result = get_post_meta( $definition_id, 'definition_period', true )
			: floor((date('n') - 1) / 3) + 1;

		$definition_year = ($definition_id)
			? $result = get_post_meta( $definition_id, 'definition_year', true )
			: 0;

		$totalnetvalue = get_post_meta( $definition_id, 'totalnetvalue', true );
		$totaltaxvalue = get_post_meta( $definition_id, 'totaltaxvalue', true );

		$transaction_from_month = get_post_meta( $definition_id, 'transaction_from_month', true );
		if (empty($transaction_from_month)) $transaction_from_month = date('m');

		$transaction_from_year = get_post_meta( $definition_id, 'transaction_from_year', true );
		if (empty($transaction_from_year)) $transaction_from_year = date('Y');

		$transaction_to_month = get_post_meta( $definition_id, 'transaction_to_month', true );
		if (empty($transaction_to_month)) $transaction_to_month = date('m');

		$transaction_to_year = get_post_meta( $definition_id, 'transaction_to_year', true );
		if (empty($transaction_to_year)) $transaction_to_year = date('Y');

		$test_mode = get_post_meta( $definition_id, 'test_mode', true );

		$definition = $definition_id ? get_post($definition_id) : null;
		$post_title	= $definition_id ? $definition->post_title : '';
?>
		<style>
			.moss-saf-definition-header-details td span {
				line-height: 29px;
			}
		</style>

		<div class="wrap">

<?php		do_action( 'moss_saf_overview_top' ); ?>

			<form id="vat-moss-saf-sales" method="post">

<?php			submit_button( __( 'Save', 'vat_moss_saf' ), 'primary', 'save_definition', false, array( 'style' => 'float: right; margin-top: 10px;' ) ); ?>
				<a href='?page=moss-saf-definitions' class='button secondary' style='float: right; margin-top: 10px; margin-right: 10px;'><?php _e('Definitions', 'vat_moss_saf'); ?></a>
				<h2><?php echo $title; if ($definition_id) { ?>
					<a href="?page=moss-saf-definitions&action=new_definition" class="add-new-h2"><?php _e( 'Add New', 'vat_moss_saf' ); ?></a>
				<?php } ?>
				</h2>

				<input type="hidden" name="post_type" value="definition"/>
				<input type="hidden" name="page" value="moss-saf-definitions"/>
				<input type="hidden" name="definition_id" value="<?php echo $definition_id; ?>"/>
				<input type="hidden" name="_wp_nonce" value="<?php echo wp_create_nonce( 'moss_saf_definition' ); ?>" />

				<div id="poststuff" >
					<div id="moss_saf_definition_header" class="postbox ">
						<h3 class="hndle ui-sortable-handle"><span>Details</span></h3>
						<div class="inside">
							<table width="100%" class="moss-saf-definition-header-details">
								<colgroup>
									<col width="200px">
								</colgroup>
								<tbody>
									<tr>
										<td scope="row" style="200px"><b><?php _e( 'Definition Title', 'vat_moss_saf' ); ?></b></td>
										<td style="200px">
<?php	if ($read_only) { ?>
											<span><?php echo $post_title; ?></span>
<?php	} else { ?>
											<input type="text" class="regular-text" id="moss_saf_settings_title" name="moss_saf_settings_title" value="<?php echo $post_title; ?>">
<?php	} ?>
										</td>
									</tr>
									<tr>
										<td style="vertical-align: top;" scope="row"><span><b><?php _e( 'Test mode', 'vat_moss_saf' ); ?></b></span></td>
										<td>
<?php	if ($read_only) { ?>
											<span><?php echo $test_mode ? "Yes" : "No"; ?></span>&nbsp;-&nbsp;
											<input type="hidden" id="ecsl_settings_test_mode" value="<?php echo $test_mode; ?>">
<?php	} else { ?>
											<input type="checkbox" class="checkbox" id="test_mode" name="test_mode" <?php echo $test_mode ? "checked='on'" : ""; ?>">
<?php	} ?>
											<span><?php echo __( "Use the test mode to check the structure of the file before you purchase and use a credit.", 'vat_moss_saf' ); ?></span>
											<p style="margin-top: 0px; margin-bottom: 0px;"><?php echo __( "In test mode a license key is not required and an audit file will be generated but the sales values in the generated file will be zero.", 'vat_moss_saf' ); ?></p>
										</td>
									</tr>
									<tr>
										<td scope="row"><b><?php _e( 'Definition license key', 'vat_moss_saf' ); ?></b></td>
										<td>
<?php	if ($read_only) { ?>
											<span><?php echo $definition_key; ?></span>
<?php	} else { ?>
											<input type="text" class="regular-text" id="definition_key" name="definition_key" value="<?php echo $definition_key; ?>">
<?php	} ?>
										</td>
									</tr>
<?php	if (!$read_only) { ?>
									<tr>
										<td></td>
										<td>
											<button id="check_moss_saf_license" definition_key_id="definition_key" value="Check License" class="button button-primary" >Check License</button>
											<img src="<?php echo VAT_MOSS_PLUGIN_URL . "images/loading.gif" ?>" id="license-checking" style="display:none; margin-left: 10px; margin-top: 8px;" />
										</td>
									</tr>
<?php	}
		if ($definition_id) { ?>
									<tr>
										<td scope="row"><b><?php _e( 'Creation date', 'vat_moss_saf' ); ?></b></td>
										<td>
											<span><?php echo $definition->post_date; ?></span>
										</td>
									</tr>
									<tr>
										<td scope="row"><b><?php _e( 'Last modified date', 'vat_moss_saf' ); ?></b></td>
										<td>
											<span><?php echo $definition->post_modified; ?></span>
										</td>
									</tr>
<?php	} ?>
									<tr>
										<td scope="row"><b><?php _e( 'Your MS ID', 'vat_moss_saf' ); ?></b></td>
										<td>
<?php	if ($read_only) { ?>
											<span><?php echo $vrn; ?></span>
<?php	} else { ?>
											<input type="text" class="regular-text" id="moss_saf_settings_vat_number" name="moss_saf_settings_vat_number" value="<?php echo $vrn; ?>">
<?php	} ?>
										</td>
									</tr>
									<tr>
										<td></td>
										<td>The MS ID is the id issued by your member state tax authority and may be the same as your VAT/TVA number.</td>
									</tr>
									<tr>
										<td scope="row"><b><?php _e( 'Submitters Name', 'vat_moss_saf' ); ?></b></td>
										<td>
<?php	if ($read_only) { ?>
											<span><?php echo $submitter; ?></span>
<?php	} else { ?>
											<input type="text" class="regular-text" id="moss_saf_settings_submitter" name="moss_saf_settings_submitter" value="<?php echo $submitter; ?>">
<?php	} ?>
										</td>
									</tr>
									<tr>
										<td scope="row"><b><?php _e( 'Submitters Email Address', 'vat_moss_saf' ); ?></b></td>
										<td>
<?php	if ($read_only) { ?>
											<span><?php echo $email; ?></span>
<?php	} else { ?>
											<input type="text" class="regular-text" id="moss_saf_settings_email" name="moss_saf_settings_email" value="<?php echo $email; ?>">
<?php	} ?>
										</td>
									</tr>
									<tr>
										<td scope="row"><b><?php _e( 'Audit Report Period', 'vat_moss_saf' ); ?></b></td>
										<td>
<?php	if ($read_only) { ?>
											<span><?php echo "Q$definition_period $definition_year"; ?></span>
<?php	} else { ?>
<?php
											echo vat_moss_saf()->html->quarter_dropdown( 'definition_period', $definition_period );
											echo vat_moss_saf()->html->year_dropdown( 'definition_year', $definition_year );
		}
?>
										</td>
									</tr>
									<tr>
										<td scope="row"><b><?php _e( 'Transactions From', 'vat_moss_saf' ); ?></b></td>
										<td>
<?php	if ($read_only) { ?>
											<span><?php echo "$transaction_from_month/$transaction_from_year"; ?></span>
<?php	} else { ?>
<?php
											echo vat_moss_saf()->html->year_dropdown ( 'transaction_from_year',  $transaction_from_year );
											echo vat_moss_saf()->html->month_dropdown( 'transaction_from_month', $transaction_from_month );
		}
?>
										</td>
									</tr>
									<tr>
										<td scope="row"><b><?php _e( 'Transactions To', 'vat_moss_saf' ); ?></b></td>
										<td>
<?php	if ($read_only) { ?>
											<span><?php echo "$transaction_to_month/$transaction_to_year"; ?></span>
<?php	} else { ?>
<?php
											echo vat_moss_saf()->html->year_dropdown ( 'transaction_to_year',  $transaction_to_year );
											echo vat_moss_saf()->html->month_dropdown( 'transaction_to_month', $transaction_to_month );
		}
?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
<?php
				do_action( 'moss_saf_definitions_page_bottom' );
?>
			</form>
		</div>
<?php
	} );
}
?>
