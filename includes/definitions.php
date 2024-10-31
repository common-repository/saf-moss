<?php
/**
 * MOSS SAF Main controller for definitions
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

if ( ! defined( 'STATE_UNKNOWN' ) )
	define('STATE_UNKNOWN', 'unknown');

if ( ! defined( 'STATE_NOT_GENERATED' ) )
	define('STATE_NOT_GENERATED', 'not_generated');

if ( ! defined( 'STATE_FAILED' ) )
	define('STATE_FAILED', 'failed');

if ( ! defined( 'STATE_GENERATED' ) )
	define('STATE_GENERATED', 'generated');

include VAT_MOSS_SAF_INCLUDES_DIR . 'lists/class-definitions.php';
include VAT_MOSS_SAF_INCLUDES_DIR . 'admin/new_definition.php';
include VAT_MOSS_SAF_INCLUDES_DIR . 'admin/edit_definition.php';
include VAT_MOSS_SAF_INCLUDES_DIR . 'admin/delete_definition.php';
include VAT_MOSS_SAF_INCLUDES_DIR . 'admin/save_definition.php';
include VAT_MOSS_SAF_INCLUDES_DIR . 'admin/view_definition.php';
include VAT_MOSS_SAF_INCLUDES_DIR . 'admin/submit_definition.php';

$locale = ( isset($_COOKIE['locale']) )
	? $_COOKIE['locale']
	: (isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] )
		? $_SERVER['HTTP_ACCEPT_LANGUAGE']
		: 'en_GB'
	  );
setlocale(LC_ALL, $locale);

function moss_saf_definitions()
{
	global $moss_saf_options;
	
	 add_thickbox();

	if ( isset( $_REQUEST['action'] ) && 'submit_definition' == $_REQUEST['action'] ) {
	
		if (!isset($_REQUEST['id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the definition to submit.', 'vat_moss_saf' ) . "</p></div>";

			show_definitions();
			return;
		}

		submit_definition($_REQUEST['id']);

	} else if ( isset( $_REQUEST['action'] ) && 'view_definition' == $_REQUEST['action'] ) {

		if (!isset($_REQUEST['id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the definition details to view.', 'vat_moss_saf' ) . "</p></div>";

			show_definitions();
			return;
		}

		view_definition($_REQUEST['id']);

	} else if( isset( $_REQUEST['action'] ) && 'new_saf_definition' == $_REQUEST['action'] )  {

		if ( isset( $_REQUEST['save_definition']))
			save_definition();
		else
			new_definition();

	} else if( isset( $_REQUEST['action'] ) && 'edit_definition' == $_REQUEST['action'] ) {

		if (!isset($_REQUEST['id']) && !isset($_REQUEST['definition_id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the definition to edit.', 'vat_moss_saf' ) . "</p></div>";

			show_definitions();
			return;
		}

		if ( isset( $_REQUEST['save_definition']))
			save_definition();
		else
			edit_definition(isset($_REQUEST['id']) ? $_REQUEST['id'] : $_REQUEST['definition_id']);

	} else if( isset( $_REQUEST['action'] ) && 'delete_definition' == $_REQUEST['action'] ) {

		if (!isset($_REQUEST['id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the definition to delete.', 'vat_moss_saf' ) . "</p></div>";

			show_definitions();
			return;
		}

		delete_definition($_REQUEST['id']);
		show_definitions();

	} else if( (isset( $_REQUEST['action'] ) && 'save_definition' == $_REQUEST['action'] ) ) {

		save_definition();

	} else {

		show_definitions();

	}
}

function show_definitions()
{
		advert( 'standard-audit-file-saf-moss', VAT_MOSS_SAF_VERSION, function() {

		$definitions_list = new MOSS_SAF_Definitions();
		$definitions_list->prepare_items();
?>
		<div class="wrap">
			<a href='?page=moss-saf-definitions' class='button secondary' style='float: right; margin-top: 10px; margin-right: 10px;'><?php _e('Refresh', 'vat_moss_saf'); ?></a>
			<h2><?php _e( 'Definitions', 'vat_moss_saf' ); ?>
				<a href="?page=moss-saf-definitions&action=new_saf_definition" class="add-new-h2"><?php _e( 'Add New', 'vat_moss_saf' ); ?></a>
			</h2>

			<p>To find information to help you use this plug-in <a href="http://www.wproute.com/wordpress-vat-moss-saf/">visit the plug-in page on our site</a>.</p>
			<p>Please note that to ensure we are able to process any audit files you create, to verify any completed audit file created requests or to be able to answer questions about any request you make that fail, details of your audit file will be held on our site.</p>
<?php
			if(function_exists('wincache_ucache_get') && ini_get('wincache.ucenabled'))
			{
?>
				<p>WinCache is active on this server and user caching is enabled.  This configuration may cause in invalid query results.</p>
<?php 		}

			do_action( 'moss_saf_definitions_page_top' ); ?>
			<form id="moss_saf-filter" method="get" action="<?php echo admin_url( 'admin.php?page=moss-saf-definitions' ); ?>">

				<?php // $definitions_list->search_box( __( 'Search', 'vat_moss_saf' ), 'moss-saf-definitions' ); ?>

				<input type="hidden" name="page" value="moss-saf-definitions" />

				<?php
					$definitions_list->views();
					$definitions_list->display();
				?>

			</form>
			<?php
				do_action( 'moss_saf_definitions_page_bottom' );
			?>

		</div>
<?php
	} );
}

/**
 * Creates error messages
 *
 * @since 1.0
 *
 * @array|string errors
 *
 */
function report_errors($errors)
{
	if (!is_array($errors)) $errors = array($errors);

	foreach($errors as $source_error)
	{
		if (!is_array($source_error)) $source_error = array($source_error);
		foreach($source_error as $error)
			echo "<div class='error'><p>$error</p></div>";
	}
}

/**
 * Register an error to be displayed to the user
 */
function add_definition_error( $message ) {

	set_transient(VAT_MOSS_SAF_ACTIVATION_ERROR_NOTICE, $message, 10);

}

/**
 * Register information to be displayed to the user
 */
function add_definition_info( $message ) {

	set_transient(VAT_MOSS_SAF_ACTIVATION_UPDATE_NOTICE, $message, 10);

}

function moss_saf_sales_count($mosssales)
{
	$result = 0;
	
	if (is_array($mosssales))
	{
		foreach($mosssales as $key => $integration)
		{
			if (!is_array($integration)) continue;
			$result += count( $integration );
		}
	}

	return $result;
}

?>