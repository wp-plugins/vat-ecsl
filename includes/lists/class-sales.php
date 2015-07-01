<?php

/*
 * Part of: ECSL
 * @Description:	Create a report to display the list of submissions.
 * @Author:			Bill Seddon
 * @Author URI:		http://www.lyquidity.com
 * @Copyright:		Lyquidity Solutions Limited 2013 and later
 * @License:		GNU Version 2 or Any Later Version
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
class ECSL_Sales_List extends \WP_List_Table {

	/**
	 * A list of the payments to be reported
	 */
	private $vat_payments;
	
	private $from_year = null;
	private $from_month = null;
	private $to_year = null;
	private $to_month = null;
	private $edit = false;
	private $read_only = false;

	public $total_lines = 0;
	public $total_value = 0;
	public $integrations = array();

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
	public function __construct($from_year = null, $from_month = null, $to_year = null, $to_month = null, $edit = false, $read_only = false) {
	
		$this->vat_payments = array();

		// Set parent defaults
		parent::__construct( array(
			'singular'  => __('ECSL Sale', 'vat_ecsl'),		// Singular name of the listed records
			'plural'    => __('ECSL Sales', 'vat_ecsl'),	// Plural name of the listed records
			'ajax'      => false							// Does this table support ajax?
		));

		$this->from_year	= $from_year;
		$this->from_month	= $from_month;
		$this->to_year		= $to_year;
		$this->to_month		= $to_month;
		$this->edit			= $edit;
		$this->read_only	= $read_only;
		$this->integrations = vat_ecsl()->integrations->get_integrations();

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
		return $this->from_year === null 
			? ( isset( $_REQUEST[ 'from_year' ] ) ? $_REQUEST[ 'from_year' ] : date('Y') )
			: $this->from_year ;
	}

	/**
	 * From month
	 */
	function get_from_month()
	{
		return $this->from_month === null
			? ( isset( $_REQUEST[ 'from_month' ] ) ? $_REQUEST[ 'from_month' ] : date('m') ) 
			: $this->from_month;
	}

	/**
	 * To year
	 */
	function get_to_year()
	{
		return $this->to_year === null
			? ( isset( $_REQUEST[ 'to_year' ] ) ? $_REQUEST[ 'to_year' ]	: date('Y') )
			: $this->to_year;
	}

	/**
	 * To month
	 */
	function get_to_month()
	{
		return $this->to_month === null
			? ( isset( $_REQUEST[ 'to_month' ] ) ? $_REQUEST[ 'to_month' ] : date('m') )
			: $this->to_month;
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
			case 'indicator':
				$indicator_name = $item['indicator'] == '3'
					? __( 'Services', 'vat_ecsl' )
					: ($item['indicator'] == '2'
						? __( 'Triangulation', 'vat_ecsl' )
						: __( 'Goods', 'vat_ecsl' )
					  );
				return "<span class='tips' data-tip='$indicator_name'>{$item['indicator']}</span>";
			case 'value' :
				return number_format( $item[ $column_name ], 2, '.', '' );
			case 'message':
				return $item['valid_vrn'] ? '' : "<span style='color: red;'>The VAT number is invalid</span>";
			case 'source':
				
				return isset($this->integrations[$item['source']]) ? $this->integrations[$item['source']]->name : $item['source'];
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
		$columns = array();
		
		if (!$this->read_only)
			$columns['cb'] = 'All';

		$columns = $columns + array(
			'id'				=> __( 'ID', 'vat_ecsl' ),
			'purchase_key'		=> __( 'Purchase ID', 'vat_ecsl' ),
			'vrn'				=> __( 'VAT Number', 'vat_ecsl' ),
			'date'				=> __( 'Date', 'vat_ecsl' ),
			'source'    		=> __( 'Source', 'vat_ecsl' ),
			'message'			=> __( 'Message', 'vat_ecsl' ),
			'indicator'			=> __( 'Indicator', 'vat_ecsl' ),
			'value'				=> __( 'Value', 'vat_ecsl' )
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
			'source'			=> array( 'source', true ),
			'date'				=> array( 'date', true ),
			'value' 			=> array( 'value', false ),
			'vrn'				=> array( 'vrn', false )
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
		<span style="float: left; margin-top: 5px;"><?php echo __('From', 'vat_ecsl'); ?>:&nbsp;</span>
<?php
	if ($this->read_only) {
?>
		&nbsp;<span style="float: left; margin-top: 5px;">
<?php	echo date ("M", mktime(0,0,0,$from_month,1,0)) . " $from_year&nbsp;"; ?>
		</span>
<?php
	} else
	{
		echo vat_ecsl()->html->year_dropdown( 'from_year', $from_year );
		echo vat_ecsl()->html->month_dropdown( 'from_month', $from_month );
	}
?>
		<span style="float: left; margin-top: 5px;"><?php echo __('To', 'vat_ecsl'); ?>:&nbsp;</span>
<?php
	if ($this->read_only) {
?>
		<span style="float: left; margin-top: 5px;">
<?php	echo date ("M", mktime(0,0,0,$to_month,1,0)) . " $to_year&nbsp;"; ?>
		</span>
<?php
	} else
	{
		echo vat_ecsl()->html->year_dropdown ( 'to_year',  $to_year );
		echo vat_ecsl()->html->month_dropdown( 'to_month', $to_month );
	}
?>
<?php
		if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
			return;

		$text = __( 'Search', 'vat_ecsl' );
		$input_id = 'ecsl-vat-payments' . '-search-input';

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
     * Output the checkbox column
     *
     * @access      private
     * @since       1.0
     * @return      void
     */

    function column_cb( $item ) {

		if ($this->read_only) return;

		global $selected;
		
		$checked = $item['first'] && is_array($selected) && isset($selected[$item['source']]) && isset($selected[$item['source']][$item['id']]);

        return sprintf(
            '<input type="checkbox" name="%1$s[%3$s][]" value="%2$s" %4$s %5$s />',
            esc_attr( $this->_args['singular'] ),
            esc_attr( $item['id'] ),
            esc_attr( $item['source']), 
			$checked ? "checked" : "",
			!$item['valid_vrn'] || !$item['first']  ? "disabled" : ""
        );
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


	function sortbysource_asc($a, $b)
	{
		return strcasecmp( $a['source'],  $b['source']);
	}

	function sortbysource_desc($a, $b)
	{
		return strcasecmp( $b['source'],  $a['source']);
	}

	function sortbyvalue_asc($a, $b)
	{
		return $a['value'] -  $b['value'];
	}

	function sortbyvalue_desc($a, $b)
	{
		return $b['value'] -  $a['value'];
	}

	function sortbyvrn_asc($a, $b)
	{
		return $a['vrn'] -  $b['vrn'];
	}

	function sortbyvrn_desc($a, $b)
	{
		return $b['vrn'] -  $a['vrn'];
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
		if (!$this->read_only)
			submit_button( __( 'Show', 'vat_ecsl' ), 'secondary', 'change_periods', false );
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

		global $selected;

		$orderby	= isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : 'date';
		$order		= isset( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'DESC';
		$endDay		= date("t", strtotime(sprintf("%1u-%2u-01", $this->get_to_year(), $this->get_to_month())));

		$startYear = $this->get_from_year();
		$startMonth = $this->get_from_month();
		$startDate = "$startYear-$startMonth-01";
		$endYear = $this->get_to_year();
		$endMonth = $this->get_to_month();
		$endDate = date("Y-m-t", strtotime("$endYear-$endMonth-01"));

		// Get the payments to display
		$this->vat_payments = vat_ecsl()->integrations->get_vat_information($startDate, $endDate, $this->edit, $this->read_only);
		
		// If there are any, filter them to exclude previously selected payments if needed
		if ($this->vat_payments !== null)
		{
			$this->vat_payments = array_filter($this->vat_payments, function($payment) use($selected)
			{
				if (!isset($payment['submission_id']) || $payment['submission_id'] == 0) return true;
				if (!is_array($selected)) return false;
				return isset($selected[$payment['source']][$payment['id']]);
			});

			// Sort them
			$order = strtolower($order);
			uasort($this->vat_payments, array($this, "sortby{$orderby}_{$order}"));
		}

		return $this->vat_payments;
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

		global $selected;

		$this->total_lines = 0;
		$this->total_value = 0;

		$vat_payments		= vat_ecsl()->integrations->flatten_vat_information($this->vat_payments);

		if (is_array($selected))
		{
			foreach($vat_payments as $key => $payment)
			{
				if( isset($selected[$payment['source']]) && isset($selected[$payment['source']][$payment['id']]))
					$this->total_lines++;

				if (is_array($selected) && isset($selected[$payment['source']]) && isset($selected[$payment['source']][$payment['id']]))
					$this->total_value += $payment['value'];
			}
		}

		return $vat_payments;
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