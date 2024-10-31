<?php

/*
 * Part of: VAT-MOSS-SAF
 * @Description: Create a standard audit file definition.
 * @Author: Bill Seddon
 * @Author URI: http://www.lyquidity.com
 * @Copyright: Lyquidity Solutions Limited 2013 and later
 * @License:	GNU Version 2 or Any Later Version
 */

namespace lyquidity\vat_moss_saf;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Presents a list of existing definitions
 *
 * Renders the MOSS SAF Definitions table
 *
 * @since 1.0
 */
class MOSS_SAF_Definitions extends \WP_List_Table {

	/**
	 * A list of the payments to be reported
	 */
	private $definitions;
	
	/**
	 * @var int Number of items per page
	 * @since 1.0
	 */
	public $per_page = 30;


	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		$this->definitions = array();

		// Set parent defaults
		parent::__construct( array(
			'singular'  => __('MOSS SAF Definition', 'vat_moss_saf'),	// Singular name of the listed records
			'plural'    => __('MOSS SAF Definitions', 'vat_moss_saf'),	// Plural name of the listed records
			'ajax'      => false								// Does this table support ajax?
		));
		
		add_action( 'moss_saf_report_view_actions', array( $this, 'period_filter' ) );

		$this->query();
	}

	/** ==============================================================
	 *  BEGIN Utility functions to read the period filter settings
	 *  --------------------------------------------------------------
	 * 
	 * From year
	 */
	function get_from_year()
	{
		return isset( $_REQUEST[ 'from_year' ] ) ? $_REQUEST[ 'from_year' ]	: date('Y');
	}

	/**
	 * From month
	 */
	function get_from_month()
	{
		return isset( $_REQUEST[ 'from_month' ] ) ? $_REQUEST[ 'from_month' ] : date('m');
	}

	/**
	 * To year
	 */
	function get_to_year()
	{
		return isset( $_REQUEST[ 'to_year' ] ) ? $_REQUEST[ 'to_year' ]	: date('Y');
	}

	/**
	 * To month
	 */
	function get_to_month()
	{
		return isset( $_REQUEST[ 'to_month' ] )	? $_REQUEST[ 'to_month' ] : date('m');
	}

	/** --------------------------------------------------------------
	 *  END Utility functions to read the period filter settings
	 *  ==============================================================
	 * 
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since 1.0
	 *
	 * @param array $item Contains all the data of the downloads
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {
		switch( $column_name ){
			case 'buttons':
				
				$delete_text	= __( 'Delete', 'vat_moss_saf' );
				$edit_text		= __( 'Edit', 'vat_moss_saf' );
				$view_text		= __( 'View', 'vat_moss_saf' );
				$submit_text	= __( 'Generate', 'vat_moss_saf' );
				$download_text	= __( 'Download', 'vat_moss_saf' );
				
				$delete_title	= __( 'Delete this definition', 'vat_moss_saf' );
				$edit_title		= __( 'Edit this definition', 'vat_moss_saf' );
				$view_title		= __( 'View the transaction in this definition', 'vat_moss_saf' );
				$submit_title	= __( 'Generate a definition report', 'vat_moss_saf' );
				$download_title	= __( 'Download the report file', 'vat_moss_saf' );

				$delete_button	= "<a href='?page=moss-saf-definitions&action=delete_definition&id={$item['ID']}' class='delete_definition button button-secondary' title='$delete_title'>$delete_text</a>";
				$edit_button	= "<a href='?page=moss-saf-definitions&action=edit_definition&id={$item['ID']}' class='edit_definition button button-secondary' title='$edit_title'>$edit_text</a>";
				$view_button	= "<a href='?page=moss-saf-definitions&action=view_definition&id={$item['ID']}' class='view_definition button button-secondary' title='$view_title'>$view_text</a>";
				$submit_button	= "<a href='?page=moss-saf-definitions&action=submit_definition&id={$item['ID']}' class='submit button button-primary' title='$submit_title'>$submit_text</a>";
				$download_button= "<a id='download_report' href='?vat_moss_saf_action=download_report&definition_id={$item['ID']}' target='_blank' class='download_report button button-primary' title='$download_title'>$download_text</a>";

				if ($item[ 'state' ] === STATE_NOT_GENERATED || $item[ 'state' ] === STATE_FAILED || $item[ 'state' ] === STATE_UNKNOWN)
				{
					return "$edit_button&nbsp;$delete_button&nbsp;$submit_button";
				}
				else if ($item[ 'state' ] === STATE_GENERATED)
				{
					return "$view_button&nbsp;$delete_button&nbsp;$download_button";
				}

				return $result;

				break;
			case 'state':
				global $wp_post_statuses;
				return isset($wp_post_statuses[$item['state']]) ? $wp_post_statuses[$item['state']]->label : $item['state'];
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Retrieve the table columns
	 *
	 * @access public
	 * @since 1.0
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		$columns = array(
			'date'			=> __( 'Date',			'vat_moss_saf' ),
			'title'			=> __( 'Title',			'vat_moss_saf' ),
			'period'		=> __( 'Period',		'vat_moss_saf' ),
			'transactions'	=> __( 'Transactions',	'vat_moss_saf' ),
			'submitter'		=> __( 'Submitter',		'vat_moss_saf' ),
			'state'			=> __( 'State',			'vat_moss_saf' ),
			'buttons'		=> ''
		);

		return $columns;
	}

	/**
	 * Retrieve the table's sortable columns
	 *
	 * @access public
	 * @since 1.4
	 * @return array Array of all the sortable columns
	 */
	public function get_sortable_columns() {
		
		return array(
			'date'			=> array( 'post_modified_gmt', true ),
			'submitter' 	=> array( 'submitter', false ),
			'title'			=> array( 'title', false )
		);
	}

	/**
	 * Retrieve the current page number
	 *
	 * @access public
	 * @since 1.0
	 * @return int Current page number
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}

	/**
	 * Renders the year/period from/to drop downs
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	function period_filter()
	{
		$from_year	= $this->get_from_year();
		$to_year	= $this->get_to_year();
		$from_month	= $this->get_from_month();
		$to_month	= $this->get_to_month();
?>
		<span style="float: left; margin-top: 5px;"><?php echo __('From', 'vat_moss_saf'); ?>:</span>
<?php
		echo vat_moss_saf()->html->year_dropdown( 'from_year', $from_year );
		echo vat_moss_saf()->html->month_dropdown( 'from_month', $from_month );
?>
		<span style="float: left; margin-top: 5px;"><?php echo __('To', 'vat_moss_saf'); ?>:</span>
<?php
		echo vat_moss_saf()->html->year_dropdown ( 'to_year',  $to_year );
		echo vat_moss_saf()->html->month_dropdown( 'to_month', $to_month );
?>
<?php
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
			return;

		$text = __( 'Search', 'vat_moss_saf' );
		$input_id = 'moss-saf-definitions' . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) )
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		if ( ! empty( $_REQUEST['order'] ) )
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		if ( ! empty( $_REQUEST['post_mime_type'] ) )
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		if ( ! empty( $_REQUEST['detached'] ) )
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';

?>
		<div style="float: right;">
			<label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
			<?php submit_button( $text, 'button', false, false, array('id' => 'search-submit') ); ?>
		</div>
<?php
	}

	/**
	 * Outputs the reporting views
	 *
	 * @access public
	 * @since 1.5
	 * @return void
	 */
	public function bulk_actions( $which = "" ) {
		// These aren't really bulk actions but this outputs the markup in the right place
?>
		<form id="moss-saf-reports-filter" method="get">

			<?php do_action( 'moss_saf_report_view_actions' ); ?>

			<input type="hidden" name="post_type" value="definition"/>
			<input type="hidden" name="page" value="moss-saf-definitions"/>

			<?php submit_button( __( 'Show', 'vat_moss_saf' ), 'secondary', 'submit', false ); ?>
		</form>
<?php
		do_action( 'moss_saf_report_view_actions_after' );
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	protected function display_tablenav( $which ) {
?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<div class="actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
<?php
		$this->extra_tablenav( $which );
		$this->pagination( $which );
?>

		<br class="clear" />
	</div>
<?php
	}

	/**
	 * Performs the products query
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function query() {

		$orderby	= isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'date';
		$order		= isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$endDay		= date("t", strtotime(sprintf("%1u-%02u-01", $this->get_to_year(), $this->get_to_month())));

		$args = array(
			'post_type' 		=> 'moss_saf_definition',
			'posts_per_page' 	=> -1,
			'fields'			=> 'ids',
			'post_status'		=> array('publish',STATE_UNKNOWN,STATE_NOT_GENERATED,STATE_FAILED,STATE_GENERATED ),
			'orderby'			=> array( "$orderby" => "$order" ),
			'date_query' => array(
				array(
					'after'    => array(
						'year'  => $this->get_from_year(),
						'month' => $this->get_from_month(),
						'day'   => 1,
					),
					'before'    => array(
						'year'  => $this->get_to_year(),
						'month' => $this->get_to_month(),
						'day'   => $endDay,
					),
					'inclusive' => true,
				),
			)
		);

		$definitions = new \WP_Query( $args );
;
		$this->definitions = $definitions->posts;
	}

	/** --------------------------------------------------------------
	 *  END Query support function
	 *  ==============================================================
	 * 
	 * Build all the reports data
	 *
	 * @access public
	 * @since 1.0
	 * @return array $reports_data All the data for customer reports
	 */
	public function reports_data() {

		$reports_data = array();

		foreach ( $this->definitions as $definition_id ) {

			$post					= get_post($definition_id);
			$submitter				= get_post_meta( $definition_id, 'submitter', true);
			$email					= get_post_meta( $definition_id, 'email', true);
			$transaction_from_month	= get_post_meta( $definition_id, 'transaction_from_month', true );
			$transaction_from_year	= get_post_meta( $definition_id, 'transaction_from_year', true );
			$transaction_to_month	= get_post_meta( $definition_id, 'transaction_to_month', true );
			$transaction_to_year	= get_post_meta( $definition_id, 'transaction_to_year', true );
			$definition_period		= get_post_meta( $definition_id, 'definition_period', true );
			$definition_year		= get_post_meta( $definition_id, 'definition_year', true );

			$reports_data[] = array(
				'ID'			=> $definition_id,
				'date'			=> $post->post_modified_gmt,
				'post_author'	=> get_the_author_meta( 'display_name', $post->post_author),
				'submitter'		=> $submitter,
				'state'			=> $post->post_status,
				'title'			=> $post->post_title,
				'period'		=> "Q$definition_period.$definition_year",
				'transactions'	=> "$transaction_from_year/$transaction_from_month-$transaction_to_year/$transaction_to_month"
			);
		}

		return $reports_data;
	}

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since 1.0
	 * @uses Sales_Report_Table::get_columns()
	 * @uses Sales_Report_Table::get_sortable_columns()
	 * @uses Sales_Report_Table::reports_data()
	 * @return void
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array( 'post_author'	=> __( 'Author',	'vat_moss_saf' ) ); // No hidden columns
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->reports_data();

	}
}

?>