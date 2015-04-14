<?php
/**
 * ECSL Type Functions
 *
 * @package     vat-ecsl
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @License:	GNU Version 2 or Any Later Version
 * @since       1.0
 */

namespace lyquidity\vat_ecsl;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'STATE_UNKNOWN' ) )
	define('STATE_UNKNOWN', 'unknown');

if ( ! defined( 'STATE_NOT_SUBMITTED' ) )
	define('STATE_NOT_SUBMITTED', 'not_submitted');

if ( ! defined( 'STATE_ACKNOWLEDGED' ) )
	define('STATE_ACKNOWLEDGED', 'acknowledged');

if ( ! defined( 'STATE_FAILED' ) )
	define('STATE_FAILED', 'failed');

if ( ! defined( 'STATE_SUBMITTED' ) )
	define('STATE_SUBMITTED', 'submitted');

include VAT_ECSL_INCLUDES_DIR . 'lists/class-submissions.php';
include VAT_ECSL_INCLUDES_DIR . 'lists/class-logs.php';
include VAT_ECSL_INCLUDES_DIR . 'admin/new_submission.php';
include VAT_ECSL_INCLUDES_DIR . 'lists/class-sales.php';
include VAT_ECSL_INCLUDES_DIR . 'admin/edit_submission.php';
include VAT_ECSL_INCLUDES_DIR . 'admin/delete_submission.php';
include VAT_ECSL_INCLUDES_DIR . 'admin/save_submission.php';
include VAT_ECSL_INCLUDES_DIR . 'admin/view_submission.php';
include VAT_ECSL_INCLUDES_DIR . 'admin/submit_submission.php';

function ecsl_submissions()
{
	 add_thickbox();

	if ( isset( $_REQUEST['action'] ) && 'check_submission' == $_REQUEST['action'] ) {

		if (!isset($_REQUEST['id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the submission for which to show logs.', 'vat_ecsl' ) . "</p></div>";

			show_submissions();
			return;
		}
				
		check_submission($_REQUEST['id']);
		show_submissions();
	}

	else if ( isset( $_REQUEST['action'] ) && 'delete_submission_log' == $_REQUEST['action'] ) {

		if (!isset($_REQUEST['submission_id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the submission for which to show logs.', 'vat_ecsl' ) . "</p></div>";

			show_submissions();
			return;
		}
		
		if (!isset($_REQUEST['id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the submission log to delete.', 'vat_ecsl' ) . "</p></div>";

			show_submission_logs($_REQUEST['submission_id']);
			return;
		}
		
		delete_submission_log($_REQUEST['id']);
		show_submission_logs($_REQUEST['submission_id']);
		
	}

	else if ( isset( $_REQUEST['action'] ) && 'show_submission_logs' == $_REQUEST['action'] ) {

		if (!isset($_REQUEST['id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the submission for which to show logs.', 'vat_ecsl' ) . "</p></div>";

			show_submissions();
			return;
		}
		
		show_submission_logs($_REQUEST['id']);

	} else if ( isset( $_REQUEST['action'] ) && 'submit_submission' == $_REQUEST['action'] ) {
	
		if (!isset($_REQUEST['id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the submission to delete.', 'vat_ecsl' ) . "</p></div>";

			show_submissions();
			return;
		}

		submit_submission($_REQUEST['id']);

	} else if ( isset( $_REQUEST['action'] ) && 'view_submission' == $_REQUEST['action'] ) {

		if (!isset($_REQUEST['id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the submission details to view.', 'vat_ecsl' ) . "</p></div>";

			show_submissions();
			return;
		}

		view_submission($_REQUEST['id']);

	} else if( isset( $_REQUEST['action'] ) && 'new_submission' == $_REQUEST['action'] )  {

		if ( isset( $_REQUEST['save_submission']))
			save_submission();
		else
			new_submission();

	} else if( isset( $_REQUEST['action'] ) && 'edit_submission' == $_REQUEST['action'] ) {

		if (!isset($_REQUEST['id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the submission to delete.', 'vat_ecsl' ) . "</p></div>";

			show_submissions();
			return;
		}

		if ( isset( $_REQUEST['save_submission']))
			save_submission();
		else
			edit_submission($_REQUEST['id']);

	} else if( isset( $_REQUEST['action'] ) && 'delete_submission' == $_REQUEST['action'] ) {

		if (!isset($_REQUEST['id']))
		{
			echo "<div class='error'><p>" . __('There is no id of the submission to delete.', 'vat_ecsl' ) . "</p></div>";

			show_submissions();
			return;
		}

		delete_submission($_REQUEST['id']);
		show_submissions();

	} else if( (isset( $_REQUEST['action'] ) && 'save_submission' == $_REQUEST['action'] ) ) {

		save_submission();

	} else {

		show_submissions();

	}
}

function show_submission_logs($submission_id)
{
		$logs_list = new ECSL_logs($submission_id);
		$logs_list->prepare_items();
?>
		<div class="wrap">
			<a href='?page=ecsl-submissions' class='button secondary' style='float: right; margin-top: 10px; margin-right: 10px;'><?php _e('Submissions', 'vat_ecsl'); ?></a>
			<h2><?php _e( 'Submission Logs', 'vat_ecsl' ); ?></h2>
			<?php do_action( 'ecsl_overview_top' ); ?>

				<input type="hidden" name="page" value="ecsl-submission-logs" />

				<?php $logs_list->views() ?>
				<?php $logs_list->display() ?>

			<?php do_action( 'ecsl_submission_logs_page_bottom' ); ?>
		</div>
<?php
}

function show_submissions()
{
		$submissions_list = new ECSL_Submissions();
		$submissions_list->prepare_items();
?>
		<div class="wrap">
			<a href='?page=ecsl-submissions' class='button secondary' style='float: right; margin-top: 10px; margin-right: 10px;'><?php _e('Refresh', 'vat_ecsl'); ?></a>
			<h2><?php _e( 'Submissions', 'vat_ecsl' ); ?>
				<a href="?page=ecsl-submissions&action=new_submission" class="add-new-h2"><?php _e( 'Add New', 'vat_ecsl' ); ?></a>
			</h2>

			<p>To find information to help you use this plug-in <a href="http://www.wproute.com/2015/01/wp-vat-ec-sales-list-submissions/">visit the plug-in page on our site</a>.</p>
			<p>Please note that to ensure we are able to process any submissions you make, to verify any completed submissions or to be able to answer questions about any submissions you make that fail, details of your submission will be held on our site.</p>

			<?php do_action( 'ecsl_overview_top' ); ?>
			<form id="vat-ecsl-filter" method="get" action="<?php echo admin_url( 'admin.php?page=ecsl-submissions' ); ?>">
				<?php // $submissions_list->search_box( __( 'Search', 'vat_ecsl' ), 'ecsl-submissions' ); ?>

				<input type="hidden" name="page" value="ecsl-submissions" />

				<?php $submissions_list->views() ?>
				<?php $submissions_list->display() ?>

			</form>
			<?php do_action( 'ecsl_submissions_page_bottom' ); ?>
		</div>
<?php
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
function add_submission_error( $message ) {

	set_transient(VAT_ECSL_ACTIVATION_ERROR_NOTICE, $message, 10);

}

/**
 * Register information to be displayed to the user
 */
function add_submission_info( $message ) {

	set_transient(VAT_ECSL_ACTIVATION_UPDATE_NOTICE, $message, 10);

}
 
 ?>