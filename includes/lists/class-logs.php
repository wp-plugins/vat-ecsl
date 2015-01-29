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
 * Presents a list of logs for a submission
 *
 * Renders the ECSL Submissions table
 *
 * @since 1.0
 */
class ECSL_Logs extends \WP_List_Table {

	/**
	 * A list of the payments to be reported
	 */
	private $logs;

	/**
	 * @var int ID of the submission to display the logs
	 */
	private $submission_id;

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
	public function __construct($submission_id) {

		$this->logs = array();

		// Set parent defaults
		parent::__construct( array(
			'singular'  => __('ECSL Submission Log', 'vat_ecsl'),	// Singular name of the listed records
			'plural'    => __('ECSL Submission Logs', 'vat_ecsl'),	// Plural name of the listed records
			'ajax'      => false									// Does this table support ajax?
		));
		$this->submission_id = $submission_id;
		$this->query();
	}

	/**
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
		return $item[ $column_name ];
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
			'id'				=> __( 'ID' ),
			'title'				=> __( 'Title' ),
			'date'				=> __( 'Date', 'vat_ecsl' ),
			'status'			=> __( 'Status', 'vat_ecsl' ),
			'message'			=> __( 'Message', 'vat_ecsl') ,
			'buttons'			=> ''
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
			'id'			=> array( 'id', true ),
			'date'			=> array( 'date', true )
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

	/** ==============================================================
	 *  BEGIN Query support function
	 *  --------------------------------------------------------------
	 * 
	 * The following 10 functions provide sorting for the vat payments
	 */
	function sortbydate_asc($a, $b)
	{
		return strcasecmp( $a['date'],  $b['date']);
	}

	function sortbydate_desc($a, $b)
	{
		return strcasecmp( $b['date'],  $a['date']);
	}

	function sortbyid_asc($a, $b)
	{
		return $a['id'] -  $b['id'];
	}

	function sortbyid_desc($a, $b)
	{
		return $b['id'] -  $a['id'];
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
		do_action( 'ecsl_report_view_actions' );
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
<?php
		if ($which === 'top') {
?>
		<div class="actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
<?php
		}

		$this->extra_tablenav( $which );
		$this->pagination( $which );
?>

		<br class="clear" />
<?php	
		if ($which === 'bottom') {
			foreach($this->logs as $log)
			{
				echo "<div id=\"response-{$log['id']}\" style=\"display: none;\">";
				echo $log['response'];
				echo "</div>";

				echo "<div id=\"request-{$log['id']}\" style=\"display: none;\">";
				echo $log['request'];
				echo "</div>";
			}
		}
		
?>
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

		global $wp_post_statuses;

		$args = array(
			'post_parent' => $this->submission_id,
			'post_type'   => 'ecsl_submission_log', 
			'posts_per_page' => -1,
			'post_status' => 'any' 
		);

		$submission_logs = get_children( $args );

		$this->logs = array();
		foreach ( $submission_logs as $log_id => $log ) {

			$request = get_post_meta( $log_id, 'xml_initial_request', true );
			$meta = maybe_unserialize( get_post_meta( $log_id, 'error_message', true ) );
			$message = is_array( $meta ) ? "<p>" . implode("</p><p>", $meta) . "</p>" : $meta;

			$this->logs[] = array(
				'id'		=> $log_id,
				'title' 	=> $log->post_title,
				'date'		=> $log->post_modified_gmt,
				'status'	=> isset($wp_post_statuses[$log->post_status]) ? $wp_post_statuses[$log->post_status]->label : $log->post_status,
				'buttons'	=>	"<a href=\"#TB_inline?width=800&height=550&inlineId=response-$log_id\" class=\"thickbox button button-secondary\">" . __( 'Response', 'vat_ecsl' ) . "</a>&nbsp;" .
								"<a href=\"#TB_inline?width=800&height=550&inlineId=request-$log_id\" class=\"thickbox button button-secondary\">" . __( 'Request', 'vat_ecsl' ) . "</a>&nbsp;" .
								"<a href='?page=ecsl-submissions&action=delete_submission_log&id=$log_id&submission_id=$this->submission_id' class='button button-primary'>" . __( 'Delete', 'vat_ecsl' ) . "</a>",
				'response'	=> "<pre>" . htmlentities($log->post_content) . "</pre>",
				'request'	=> "<pre>" . htmlentities($request) . "</pre>",
				'message'	=> $message
			);
		}

		$orderby	= isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'date';
		$order		= isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';

		// Sort them
		$order = strtolower($order);
		uasort($this->logs, array($this, "sortby{$orderby}_{$order}"));

		return $this->logs;
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

		return $this->logs;
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