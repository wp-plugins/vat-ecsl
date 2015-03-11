<?php

/**
 * ECSL Create a new definition (also edit and view)
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

function get_setting($id, $key)
{
	$result = '';

	if ($id)
		$result = get_post_meta( $id, $key, true );
		
	if (!empty($result)) return $result;

	return isset($_REQUEST[$key]) ? $_REQUEST[$key] : vat_ecsl()->settings->get($key);
}

function new_submission($from_year = null, $from_month = null, $to_year = null, $to_month = null, $submission_id = 0, $read_only = false)
{
	global $selected;

	if (!current_user_can('edit_submissions'))
	{
		echo "<div class='error'><p>" . __('You do not have rights to perform this action.', 'vat_ecsl' ) . "</p></div>";
		show_submissions();
		return;
	}

	$state = get_post_status($submission_id);
	if (($state === STATE_SUBMITTED || $state === STATE_ACKNOWLEDGED) && !$read_only)
	{
		echo "<div class='error'><p>" . __('This action is not valid on a submission that is complete or acknowledged.', 'vat_ecsl' ) . "</p></div>";
		show_submissions();
		return;
	}

	$title = $submission_id
		? ($read_only
			? __( 'View Submission', 'vat_ecsl' )
			: __( 'Edit Submission', 'vat_ecsl' )
		  )
		: __( 'New Submission', 'vat_ecsl' );
	$title .= $submission_id ? " ($submission_id)" : "";

	$sales_list = new ECSL_Sales_List($from_year, $from_month, $to_year, $to_month, $submission_id, $read_only);
	$sales_list->prepare_items();

	$vrn			= get_setting( $submission_id, 'vat_number');
	$submitter		= get_setting( $submission_id, 'submitter');
	$email			= get_setting( $submission_id, 'email');
	$branch			= get_setting( $submission_id, 'branch');
	$postcode		= get_setting( $submission_id, 'postcode');

	$sender_id		= get_setting( $submission_id, 'sender_id');
	$password		= get_setting( $submission_id, 'password');
	$period			= get_setting( $submission_id, 'period');
	$submission_key	= get_setting( $submission_id, 'submission_key');
	
	$submission_period = ($submission_id)
		? $result = get_post_meta( $submission_id, 'submission_period', true )
		: floor((date('n') - 1) / 3) + 1;

	$submission_year = ($submission_id)
		? $result = get_post_meta( $submission_id, 'submission_year', true )
		: 0;

	$test_mode = get_post_meta( $submission_id, 'test_mode', true );

	$submission = $submission_id ? get_post($submission_id) : null;
	$post_title	= $submission_id ? $submission->post_title : '';
?>

	<style>
		.ecsl-submission-header-details td span {
			line-height: 29px;
		}
	</style>

	<div class="wrap">

<?php	do_action( 'ecsl_overview_top' ); ?>

		<form id="vat-ecsl-sales" method="post">

<?php		submit_button( __( 'Save', 'vat_ecsl' ), 'primary', 'save_submission', false, array( 'style' => 'float: right; margin-top: 10px;' ) ); ?>
			<a href='?page=ecsl-submissions' class='button secondary' style='float: right; margin-top: 10px; margin-right: 10px;'><?php _e('Submissions', 'vat_ecsl'); ?></a>
			<h2><?php echo $title; ?></h2>

			<input type="hidden" name="post_type" value="submission"/>
			<input type="hidden" name="page" value="ecsl-submissions"/>
			<input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>"/>
			<input type="hidden" name="_wp_nonce" value="<?php echo wp_create_nonce( 'ecsl_submission' ); ?>" />

			<div id="poststuff" >
				<div id="ecsl_submission_header" class="postbox ">
					<h3 class="hndle ui-sortable-handle"><span><?php _e('Details', 'vat_ecsl'); ?></span></h3>
					<div class="inside">
						<table width="100%" class="ecsl-submission-header-details">
							<colgroup>
								<col width="200px">
							</colgroup>
							<tbody>
								<tr>
									<td scope="row" style="200px"><b><?php _e( 'Submission Title', 'vat_ecsl' ); ?></b></td>
									<td style="200px">
<?php	if ($read_only) { ?>
										<span><?php echo $post_title; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="ecsl_settings_title" name="ecsl_settings_title" value="<?php echo $post_title; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Test mode', 'vat_ecsl' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $test_mode ? "Yes" : "No"; ?></span>
										<input type="hidden" id="ecsl_settings_test_mode" value="<?php echo $test_mode; ?>">
<?php	} else { ?>
										<input type="checkbox" class="checkbox" id="test_mode" name="test_mode" <?php echo $test_mode ? "checked='on'" : ""; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Submission license key', 'vat_ecsl' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $submission_key; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="submission_key" name="submission_key" value="<?php echo $submission_key; ?>">
<?php	} ?>
									</td>
								</tr>
<?php	if (!$read_only) { ?>
								<tr>
									<td></td>
									<td>
										<button id="check_license" submission_key_id="submission_key" value="Check License" class="button button-primary" >Check License</button>
										<img src="<?php echo VAT_ECSL_PLUGIN_URL . "images/loading.gif" ?>" id="license-checking" style="display:none; margin-left: 10px; margin-top: 8px;" />
									</td>
								</tr>
<?php	}
		if (!$read_only) { ?>
								<tr>
									<td scope="row"</td>
									<td><span><?php _e( 'If a license key is not supplied, any submission will always be in test mode.', 'vat_ecsl' ); ?></span></td>
								</tr>
<?php	}
		if ($submission_id) { ?>
								<tr>
									<td scope="row"><b><?php _e( 'Creation date', 'vat_ecsl' ); ?></b></td>
									<td>
										<span><?php echo $submission->post_date; ?></span>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Last modified date', 'vat_ecsl' ); ?></b></td>
									<td>
										<span><?php echo $submission->post_modified; ?></span>
									</td>
								</tr>
<?php	} ?>
								<tr>
									<td scope="row"><b><?php _e( 'Your HMRC Account ID', 'vat_ecsl' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $sender_id; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="ecsl_settings_sender_id" name="ecsl_settings_sender_id" value="<?php echo $sender_id; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Your HMRC Account Password', 'vat_ecsl' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $password; ?></span>
<?php	} else { ?>
										<input type="password" class="regular-text" id="ecsl_settings_password" name="ecsl_settings_password" value="<?php echo $password; ?>">
<?php	} ?>
									</td>
								</tr>
<?php	if (!$read_only) { ?>
								<tr>
									<td></td>
									<td>
										<button id="validate_credentials" password_id="ecsl_settings_password" sender_id="ecsl_settings_sender_id" test_mode_id="test_mode" value="Validate Credentials" class="button button-primary" >Validate Credentials</button>
										<img src="<?php echo VAT_ECSL_PLUGIN_URL . "images/loading.gif" ?>" id="ecsl-loading" style="display:none; margin-left: 10px; margin-top: 8px;" />
										<input type="hidden" name="_validate_credentials_nonce" value="<?php echo wp_create_nonce( 'validate_credentials' ); ?>" >
									</td>
								</tr>
<?php	} ?>
								<tr>
									<td scope="row"><b><?php _e( 'Your VAT Number', 'vat_ecsl' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $vrn; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="ecsl_settings_vat_number" name="ecsl_settings_vat_number" value="<?php echo $vrn; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Submitters Name', 'vat_ecsl' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $submitter; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="ecsl_settings_submitter" name="ecsl_settings_submitter" value="<?php echo $submitter; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Submitters Email Address', 'vat_ecsl' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $email; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="ecsl_settings_email" name="ecsl_settings_email" value="<?php echo $email; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Branch number', 'vat_ecsl' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $branch; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="ecsl_settings_branch" name="ecsl_settings_branch" value="<?php echo $branch; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Postcode', 'vat_ecsl' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo $postcode; ?></span>
<?php	} else { ?>
										<input type="text" class="regular-text" id="ecsl_settings_postcode" name="ecsl_settings_postcode" value="<?php echo $postcode; ?>">
<?php	} ?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Submission Period', 'vat_ecsl' ); ?></b></td>
									<td>
<?php	if ($read_only) { ?>
										<span><?php echo ($period === 'monthly' ? date ("M", mktime(0,0,0,$submission_period,1,0)) : "Q$submission_period") . " $submission_year"; ?></span>
<?php	} else { ?>
<?php
									if ($period === 'monthly') {
										echo vat_ecsl()->html->month_dropdown( 'submission_period', $submission_period );
									} else {
										echo vat_ecsl()->html->quarter_dropdown( 'submission_period', $submission_period );
									}
									echo vat_ecsl()->html->year_dropdown( 'submission_year', $submission_year );
		}
?>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Total lines selected', 'vat_ecsl' ); ?></b></td>
									<td>
										<span><?php echo $sales_list->total_lines; ?></span>
									</td>
								</tr>
								<tr>
									<td scope="row"><b><?php _e( 'Total value of selected lines', 'vat_ecsl' ); ?></b></td>
									<td>
										<span><?php echo number_format( $sales_list->total_value, 2 ); ?></span>
									</td>
								</tr>
<?php if ($submission_id) { ?>
								<tr>
									<td scope="row"><b><?php _e( '', 'vat_ecsl' ); ?></b></td>
									<td>
										<a id='export_records' href='?ecsl_action=export_records&submission_id=<?php echo $submission_id; ?>' target='_blank' class='export_records button button-secondary' title='Export to CSV'>Export to CSV</a>
									</td>
								</tr>
<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
<?php
			$sales_list->views();
			$sales_list->display();
			do_action( 'ecsl_submissions_page_bottom' );
			
			$selected = array();
?>

		</form>

	</div>
<?php
}
?>
