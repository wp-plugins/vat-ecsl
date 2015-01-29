<?php

/*
Part of: EU VAT for EDD
Description: Create a report to display the list of submissions.
Author: Bill Seddon
Author URI: http://www.lyquidity.com
Copyright: Lyquidity Solutions Limited 2013 and later
License: Lyquidity Commercial
*/

namespace lyquidity\vat_ecsl;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Presents a list of existing submissions
 *
 * Renders the ECSL Submissions table
 *
 * @since 1.0
 */
class ECSL_Submissions extends \WP_List_Table {

	/**
	 * A list of the payments to be reported
	 */
	private $submissions;
	
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

		$this->submissions = array();

		// Set parent defaults
		parent::__construct( array(
			'singular'  => __('ECSL Submission', 'vat_ecsl'),	// Singular name of the listed records
			'plural'    => __('ECSL Submissions', 'vat_ecsl'),	// Plural name of the listed records
			'ajax'      => false								// Does this table support ajax?
		));
		
		add_action( 'ecsl_report_view_actions', array( $this, 'period_filter' ) );

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
			
				$args = array(
					'post_parent' => $item['ID'],
					'post_type'   => 'ecsl_submission_log', 
					'posts_per_page' => -1,
					'post_status' => 'any' 
				);
				
				$delete_text	= __( 'Delete', 'vat_ecsl' );
				$edit_text		= __( 'Edit', 'vat_ecsl' );
				$view_text		= __( 'View', 'vat_ecsl' );
				$log_text		= __( 'Logs', 'vat_ecsl' );
				$submit_text	= __( 'Submit', 'vat_ecsl' );
				$check_text		= __( 'Check', 'vat_ecsl' );
				
				$delete_title	= __( 'Delete this submission', 'vat_ecsl' );
				$edit_title		= __( 'Edit this submission', 'vat_ecsl' );
				$view_title		= __( 'View the transaction in this submission', 'vat_ecsl' );
				$log_title		= __( 'View the submission logs', 'vat_ecsl' );
				$submit_title	= __( 'Submit this submission', 'vat_ecsl' );
				$check_title	= __( 'Check the status of this submission', 'vat_ecsl' );

				$children = get_children( $args );
				$log_button		= count($children)
					? "<a href='?page=ecsl-submissions&action=show_submission_logs&id={$item['ID']}' class='button button-secondary' title='$log_title'>$log_text</a>"
					: "";
				$delete_button	= "<a href='?page=ecsl-submissions&action=delete_submission&id={$item['ID']}' class='button button-secondary' title='$delete_title'>$delete_text</a>";
				$edit_button	= "<a href='?page=ecsl-submissions&action=edit_submission&id={$item['ID']}' class='button button-secondary' title='$edit_title'>$edit_text</a>";
				$view_button	= "<a href='?page=ecsl-submissions&action=view_submission&id={$item['ID']}' class='button button-secondary' title='$view_title'>$view_text</a>";
				$submit_button	= "<a href='?page=ecsl-submissions&action=submit_submission&id={$item['ID']}' class='button button-primary' title='$submit_title'>$submit_text</a>";
				$check_button	= "<a href='?page=ecsl-submissions&action=check_submission&id={$item['ID']}' class='button button-primary' title='$check_title'>$check_text</a>";

				if ($item[ 'state' ] === STATE_NOT_SUBMITTED || $item[ 'state' ] === STATE_FAILED || $item[ 'state' ] === STATE_UNKNOWN)
				{
					return "$log_button&nbsp;$delete_button&nbsp;$edit_button&nbsp;$submit_button";
				}
				else if ($item[ 'state' ] === STATE_ACKNOWLEDGED)
				{
					return "$log_button&nbsp;$view_button&nbsp;$check_button";
				}
				else if ($item[ 'state' ] === STATE_SUBMITTED)
				{
					return "$log_button&nbsp;$view_button&nbsp;$delete_button";
				}

				return $result;

				break;
			case 'totalvalue' :
				if (!is_numeric( $item[ $column_name ] )) return $item[ $column_name ];
				return number_format( $item[ $column_name ], 2, '.', '' );
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
			'date'			=> __( 'Date', 'vat_ecsl' ),
			'vrn'    		=> __( 'VAT Number', 'vat_ecsl' ),
			'title'			=> __( 'Title', 'vat_ecsl' ),
			'correlationid'	=> __( 'Correlation Id', 'vat_ecsl' ),
			'post_author'	=> __( 'Author', 'vat_ecsl' ),
			'submitter'		=> __( 'Submitter', 'vat_ecsl' ),
			'state'			=> __( 'State', 'vat_ecsl' ),
			'totalvalue'	=> __( 'Total Value', 'vat_ecsl' ),
			'totallines'	=> __( 'Total Lines', 'vat_ecsl' ),
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
			'title'			=> array( 'title', false ),
			'correlationid'	=> array( 'post_title', false )
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
		<span style="float: left; margin-top: 5px;"><?php echo __('From', 'vat_ecsl'); ?>:</span>
<?php
		echo vat_ecsl()->html->year_dropdown( 'from_year', $from_year );
		echo vat_ecsl()->html->month_dropdown( 'from_month', $from_month );
?>
		<span style="float: left; margin-top: 5px;"><?php echo __('To', 'vat_ecsl'); ?>:</span>
<?php
		echo vat_ecsl()->html->year_dropdown ( 'to_year',  $to_year );
		echo vat_ecsl()->html->month_dropdown( 'to_month', $to_month );
?>
<?php
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
			return;

		$text = __( 'Search', 'vat_ecsl' );
		$input_id = 'ecsl-submissions' . '-search-input';

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
	public function bulk_actions() {
		// These aren't really bulk actions but this outputs the markup in the right place
?>
		<form id="ecsl-reports-filter" method="get">

			<?php do_action( 'ecsl_report_view_actions' ); ?>

			<input type="hidden" name="post_type" value="submission"/>
			<input type="hidden" name="page" value="ecsl-submissions"/>

			<?php submit_button( __( 'Show', 'edd' ), 'secondary', 'submit', false ); ?>
		</form>
<?php
		do_action( 'ecsl_report_view_actions_after' );
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
			'post_type' 		=> 'ecsl_submission',
			'posts_per_page' 	=> -1,
			'fields'			=> 'ids',
			'post_status'		=> array('publish',STATE_UNKNOWN,STATE_NOT_SUBMITTED,STATE_ACKNOWLEDGED,STATE_FAILED,STATE_SUBMITTED ),
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

		$submissions = new \WP_Query( $args );

		$this->submissions = $submissions->posts;
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

		foreach ( $this->submissions as $submission_id ) {

			$post			= get_post($submission_id);
			$vrn			= get_post_meta($submission_id, 'vat_number', true);
			$totalvalue		= get_post_meta($submission_id, 'totalvalue', true);
			$totallines		= get_post_meta($submission_id, 'totallines', true);
			$submitter		= get_post_meta($submission_id, 'submitter', true);
			$email			= get_post_meta($submission_id, 'email', true);
			$branch			= get_post_meta($submission_id, 'branch', true);
			$postcode		= get_post_meta($submission_id, 'postcode', true);
			$correlationid	= get_post_meta($submission_id, 'correlation_id', true);

			$reports_data[] = array(
				'ID'			=> $submission_id,
				'date'			=> $post->post_modified_gmt,
				'vrn'    		=> $vrn,
				'post_author'	=> get_the_author_meta( 'display_name', $post->post_author),
				'submitter'		=> $submitter,
				'state'			=> $post->post_status,
				'totalvalue'	=> $totalvalue,
				'totallines'	=> $totallines,
				'title'			=> $post->post_title,
				'correlationid'	=> $correlationid
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
		$hidden                = array(); // No hidden columns
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->reports_data();

	}
}

?>