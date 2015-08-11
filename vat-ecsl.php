<?php

/*
Plugin Name: WordPress VAT EC Sales List
Plugin URI: http://www.wproute.com/downloads/vat-ecsl/
Description: Management and submission of VAT sales with VAT numbers.
Version: 1.0.13
Tested up to: 4.3
Author: Lyquidity Solutions
Author URI: http://www.wproute.com/
Contributors: Bill Seddon
Copyright: Lyquidity Solutions Limited
License: GNU Version 2 or Any Later Version
Updateable: true
Text Domain: vat-ecsl
Domain Path: /languages
*/

namespace lyquidity\vat_ecsl;

/* -----------------------------------------------------------------
 *
 * -----------------------------------------------------------------
 */

// Uncomment this line to test
//set_site_transient( 'update_plugins', null );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/* -----------------------------------------------------------------
 * Plugin class
 * -----------------------------------------------------------------
 */
class WordPressPlugin {

	/**
	 * @var WordPressPlugin The one true WordPressPlugin
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * Main WordPressPlugin instance
	 *
	 * Insures that only one instance of WordPressPlugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0
	 * @static
	 * @staticvar array $instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WordPressPlugin ) ) {
			self::$instance = new WordPressPlugin;
			self::$instance->actions();
		}
		return self::$instance;
	}

	/**
	 * @var Array or EU states
	 * @since 1.0
	 */
	public static $eu_states		= array("AT","BE","BG","HR","CY","CZ","DK","EE","FI","FR","DE","GB","GR","HU","IE","IT","LV","LT","LU","MT","NL","PL","PT","RO","SK","SI","ES","SE");

	/**
	 * Public settings object
	 */
	public $settings;

	/**
	 * Public integrations object
	 */
	public $integrations;

	/**
	 * Public html object
	 */
	public $html;

	/**
	* PHP5 constructor method.
	*
	* @since 1.0
	*/
	public function __construct() {

		/* Internationalize the text strings used. */
		$this->i18n();

		/* Set the constants needed by the plugin. */
		$this->constants();

		require_once VAT_ECSL_INCLUDES_DIR . 'post-types.php';
		require_once VAT_ECSL_INCLUDES_DIR . 'class-ecsl-roles.php';
	}

	/**
	 * Setup any actions
	 */
	function actions()
	{
		add_action( 'ecsl_verify_ecsl_credentials',		array( $this, 'verify_ecsl_credentials' ) );
		add_action( 'ecsl_check_submission_license',	array( $this, 'check_submission_license' ) );
		add_action( 'ecsl_export_records',				array( $this, 'export_records' ) );

		// Allow the get_version request to obtain a response
		add_action( 'edd_sl_license_response', array(&$this, 'sl_license_response'));

		/* Load the functions files. */
		add_action( 'plugins_loaded', array( &$this, 'includes' ), 3 );

		/* Perform actions on admin initialization. */
		add_action( 'admin_init', array( &$this, 'admin_init') );
		add_action( 'init', array( &$this, 'init' ), 3 );

//		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

		register_activation_hook( __FILE__, array($this, 'plugin_activation' ) );
		register_deactivation_hook( __FILE__, array($this, 'plugin_deactivation' ) );

		if (function_exists('ecsl_submissions_settings'))
		{
			// These three lines allow for the plugin folder name to be something other than vat-ecsl
			$plugin = plugin_basename(__FILE__);
			$basename = strtolower( dirname($plugin) );
			add_filter( 'sl_updater_' . $basename, array(&$this, 'sl_updater_vat_ecsl'), 10, 2);

			// These two lines ensure the must-use update is able to access the credentials
			require_once 'edd_mu_updater.php';
			$this->updater = init_lsl_mu_updater2(__FILE__,$this);
		}
	}

	/**
	 * Called by the client pressing the check license button. This request is passed onto the Lyquidity server.
	 * 
	 * @since 1.0
	 */
	function check_submission_license($data)
	{
		require_once VAT_ECSL_INCLUDES_DIR . 'admin/submit_submission.php';

		$response = array(
			'version' => VAT_ECSL_VERSION,
			'status' => 'error',
			'message' => array( 'An unexpected error occurred' )
		);
		
		if (!isset($data['submission_key']) || empty($data['submission_key']))
		{
			$response['message'][] = "No submission key supplied";
			$response = json_encode( $response );
		}
		else if (!isset($data['url']) || empty($data['url']))
		{
			$response['message'][] = "No url supplied";	
			$response = json_encode( $response );
		}
		else
		{
			$args = array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => array(
					'edd_action'		=> 'check_submission_license',
					'submission_key'	=> $data['submission_key'],
					'url'				=> $data['url']
				),
				'cookies' => array()
			);

			$response = remote_get_handler( wp_remote_post( VAT_ECSL_STORE_API_URL, $args ) );
		}

		echo $response;

		exit();
	}
	
	/**
	 * Called by the client pressing the validate credentials button. This request is passed onto the Lyquidity server.
	 *
	 * @since 1.0
	 */
	function verify_ecsl_credentials($data)
	{
		require_once VAT_ECSL_INCLUDES_DIR . 'admin/submit_submission.php';

		$response = array(
			'version' => VAT_ECSL_VERSION,
			'status' => 'error',
			'message' => array( 'An unexpected error occurred' )
		);
		
		if (!isset($data['senderid']) || empty($data['senderid']))
		{
			$response['message'][] = "No sender id supplied";
			$response = json_encode( $response );
		}
		else if (!isset($data['password']) || empty($data['password']))
		{
			$response['message'][] = "No password supplied";	
			$response = json_encode( $response );
		}
		else
		{
			$args = array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'body' => array(
					'edd_action' =>	'verify_ecsl_credentials',
					'senderid' => $data['senderid'],
					'password' => $data['password']
				),
				'cookies' => array()
			);

			$response = remote_get_handler( wp_remote_post( VAT_ECSL_STORE_API_URL, $args ) );
		}

		echo $response;

		exit();
	}
	
	/**
	 * Called by the client to download a CSV version of the selected submission
	 *
	 * @since 1.0.4
	 */
	function export_records($data)
	{
		require_once VAT_ECSL_INCLUDES_DIR . "replacement-functions.php";

		if (!current_user_can('send_submissions'))
		{
			echo "<div class='error'><p>" . __('You do not have rights to submit an EC Sales List (VAT101)', 'vat_ecsl' ) . "</p></div>";
			exit();
		}

		if (!isset( $_REQUEST['submission_id'] ) || empty( $_REQUEST['submission_id'] )  )
		{
			echo "<div class='error'><p>" . __('The id of the submission to export has not been provided.', 'vat_ecsl' ) . "</p></div>";
			exit();			
		}

		try
		{
			$id = $_REQUEST['submission_id'];

			$post = get_post($id);

			$selected		= maybe_unserialize(get_post_meta($id, 'ecslsales', true));
			$vat_records	= vat_ecsl()->integrations->get_vat_record_information($selected);

			if (!$vat_records || !is_array($vat_records) || !isset($vat_records['status']))
			{
				echo __('There was an error creating the information to generate the CSV file.', 'vat_ecsl' );
				exit();
			}

			if ($vat_records['status'] === 'error')
			{
				foreach($vat_records['messages'] as $key => $message)
				{
					echo "$message<br/>";
				}
				exit();
			}

			$ecsl_lines = array();

			$vat_payments = vat_ecsl()->integrations->flatten_vat_information($vat_records['information']);

			foreach($vat_payments as $key => $payment)
			{
				$ecsl_lines[] = array(
					'SubmittersReference' =>  "{$payment['source']}-{$payment['id']}",
					'CountryCode' =>  substr( $payment['vrn'], 0, 2 ),
					'CustomerVATRegistrationNumber' =>  substr( $payment['vrn'], 2 ),
					'TotalValueOfSupplies' => floor( $payment['value'] ),
					'TransactionIndicator' => $payment['indicator']
				);
			}
			
	//		error_log(print_r($ecsl_lines,true));

			$vrn				= get_post_meta( $id, 'vat_number',			true );
			$submitter			= get_post_meta( $id, 'submitter',			true );
			$email				= get_post_meta( $id, 'email',				true );
			$branch				= get_post_meta( $id, 'branch',				true );
			$postcode			= get_post_meta( $id, 'postcode',			true );
			$submission_period	= get_post_meta( $id, 'submission_period',	true );
			$submission_year	= get_post_meta( $id, 'submission_year',	true );
			$period				= vat_ecsl()->settings->get('period') === 'monthly' ? 'month' : 'quarter' ;
			$title				= get_the_title( $id );

			// Redirect output to a client’s web browser (Excel2007)
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment;filename="' . "$title Q$submission_period-$submission_year.csv");
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');

			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0

			echo "vat_number:\t\t$vrn\n";
			echo "submitter:		$submitter\n";
			echo "email_address:	$email\n";
			echo "branch:			$branch\n";
			echo "postcode:		$postcode\n";
			echo "period:			$submission_period (" . (vat_ecsl()->settings->get('period') === 'monthly' ? 'month' : 'quarter') . ")\n";
			echo "year:			$submission_year\n";

			echo "SubmittersReference,CountryCode,CustomerVATRegistrationNumber,TotalValueOfSupplies,TransactionIndicator\n";
				
			foreach($ecsl_lines as $key => $line)
			{
				echo implode(",", $line) . "\n";
			}

		}
		catch(\Exception $ex)
		{
			error_log($ex->getMessage());
			echo "Download failed: " . $ex->getMessage();
		}

		exit();

	}

	/**
	 * Take an action when the plugin is activated
	 */
	function plugin_activation()
	{
		try
		{
			setup_vat_ecsl_post_types();

			// Clear the permalinks
			flush_rewrite_rules();

			$roles = new ECSL_Roles;
			$roles->add_caps();
			$roles->add_roles();
		}
		catch(Exception $e)
		{
			set_transient(VAT_ECSL_ACTIVATION_ERROR_NOTICE, __("An error occurred during plugin activation: ", 'vat_ecsl') . $e->getMessage(), 10);
		}
	}

	/**
	 * Take an action when the plugin is activated
	 */
	function plugin_deactivation()
	{
		try
		{
			$roles = new ECSL_Roles;
			$roles->remove_roles();
			$roles->remove_caps();
		}
		catch(Exception $e)
		{
			set_transient(VAT_ECSL_DEACTIVATION_ERROR_NOTICE, __("An error occurred during plugin deactivation: ", 'vat_ecsl') . $e->getMessage(), 10);
		}
	}

	/**
	* Defines constants used by the plugin.
	*
	* @since 1.0
	*/
	function constants()
	{
		if ( ! defined( 'VAT_ECSL_PLUGIN_DIR' ) )
			define( 'VAT_ECSL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

		if ( ! defined( 'VAT_ECSL_INCLUDES_DIR' ) )
			define( 'VAT_ECSL_INCLUDES_DIR', VAT_ECSL_PLUGIN_DIR . "includes/" );

		if ( ! defined( 'VAT_ECSL_TEMPLATES_DIR' ) )
			define( 'VAT_ECSL_TEMPLATES_DIR', VAT_ECSL_PLUGIN_DIR . "templates/" );

		if ( ! defined( 'VAT_ECSL_PLUGIN_URL' ) )
			define( 'VAT_ECSL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

		if ( ! defined( 'VAT_ECSL_PLUGIN_FILE' ) )
			define( 'VAT_ECSL_PLUGIN_FILE', __FILE__ );

		if ( ! defined( 'VAT_ECSL_VERSION' ) )
			define( 'VAT_ECSL_VERSION', '1.0.13' );

		if ( ! defined( 'VAT_ECSL_WORDPRESS_COMPATIBILITY' ) )
			define( 'VAT_ECSL_WORDPRESS_COMPATIBILITY', '4.1' );

		if ( ! defined( 'VAT_ECSL_STORE_API_URL' ) )
			define( 'VAT_ECSL_STORE_API_URL', 'https://www.wproute.com/' );

		if ( ! defined( 'VAT_ECSL_PRODUCT_NAME' ) )
			define( 'VAT_ECSL_PRODUCT_NAME', 'EC Sales List Submissions Credit' );

		if (!defined('VAT_ECSL_ACTIVATION_ERROR_NOTICE'))
			define('VAT_ECSL_ACTIVATION_ERROR_NOTICE', 'VAT_ECSL_ACTIVATION_ERROR_NOTICE');

		if (!defined('VAT_ECSL_ACTIVATION_UPDATE_NOTICE'))
			define('VAT_ECSL_ACTIVATION_UPDATE_NOTICE', 'VAT_ECSL_ACTIVATION_UPDATE_NOTICE');

		if (!defined('VAT_ECSL_DEACTIVATION_ERROR_NOTICE'))
			define('VAT_ECSL_DEACTIVATION_ERROR_NOTICE', 'VAT_ECSL_DEACTIVATION_ERROR_NOTICE');

		if (!defined('VAT_ECSL_DEACTIVATION_UPDATE_NOTICE'))
			define('VAT_ECSL_DEACTIVATION_UPDATE_NOTICE', 'VAT_ECSL_DEACTIVATION_UPDATE_NOTICE');

		if (!defined('VAT_ECSL_REASON_TOOSHORT'))
			define('VAT_ECSL_REASON_TOOSHORT', __('The VAT number supplied is too short', 'vat_ecsl'));

		if (!defined('VAT_ECSL_REASON_INVALID_FORMAT'))
			define('VAT_ECSL_REASON_INVALID_FORMAT', __('The VAT number supplied does not have a valid format', 'vat_ecsl'));

		if (!defined('VAT_ECSL_REASON_SIMPLE_CHECK_FAILS'))
			define('VAT_ECSL_REASON_SIMPLE_CHECK_FAILS', __('Simple check failed', 'vat_ecsl'));

		if (!defined('VAT_ECSL_ERROR_VALIDATING_VAT_ID'))
			define('VAT_ECSL_ERROR_VALIDATING_VAT_ID', __('An error occurred validating the VAT number supplied', 'vat_ecsl'));

	}

	/*
	|--------------------------------------------------------------------------
	| INTERNATIONALIZATION
	|--------------------------------------------------------------------------
	*/

	/**
	* Load the translation of the plugin.
	*
	* @since 1.0
	*/
	public function i18n() {

		/* Load the translation of the plugin. */
		load_plugin_textdomain( 'vat_ecsl', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/*
	|--------------------------------------------------------------------------
	| INCLUDES
	|--------------------------------------------------------------------------
	*/

	/**
	* Loads the initial files needed by the plugin.
	*
	* @since 1.0
	*/
	public function includes() {

		if( !is_admin() && php_sapi_name() !== "cli") return;

		require_once VAT_ECSL_INCLUDES_DIR . 'admin-notices.php';

		// The SL plugin will not be available while at the network level
		// unless the SL is active in blog #1.
		if (is_network_admin()) return;

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

		require_once VAT_ECSL_INCLUDES_DIR . 'class-menu.php';
		require_once VAT_ECSL_INCLUDES_DIR . 'settings.php';
		require_once VAT_ECSL_INCLUDES_DIR . 'submissions.php';
		require_once VAT_ECSL_INCLUDES_DIR . 'class-settings.php';
		require_once VAT_ECSL_INCLUDES_DIR . 'class-integrations.php';
		require_once VAT_ECSL_INCLUDES_DIR . 'settings.php';
		require_once(VAT_ECSL_INCLUDES_DIR . 'vatidvalidator.php');
		require_once(VAT_ECSL_INCLUDES_DIR . 'class-html-elements.php');
		require_once(VAT_ECSL_INCLUDES_DIR . 'meta-box.php');

		$this->settings = new ECSL_WP_Settings;
		$this->integrations = new ECSL_WP_Integrations;
		$this->html = new ECSL_HTML_Elements;
	}

	/**
	 * Enqueue scripts and styles
	 */

	function enqueue_scripts()
	{
		wp_enqueue_style("vat_ecsl_style",  VAT_ECSL_PLUGIN_URL . "assets/css/ecsl.css", null, null, "screen");

		wp_enqueue_script ("ecsl_script", VAT_ECSL_PLUGIN_URL . "assets/js/ecsl.js", array( 'jquery' ));
		wp_localize_script("ecsl_script", 'ecsl_vars', array(
			'ajaxurl'            			=> $this->get_ajax_url(),
			'lyquidity_server_url'			=> VAT_ECSL_STORE_API_URL
		));

		wp_enqueue_script('jquery-ui-dialog', false, array('jquery-ui-core','jquery-ui-button', 'jquery') );

	} // end vat_enqueue_scripts

	function admin_enqueue_scripts()
	{
		$suffix = '';

		wp_enqueue_style  ("ecsl_admin_style",  VAT_ECSL_PLUGIN_URL . "assets/css/ecsl-admin.css", null, null, "screen");

//		wp_enqueue_script ("ecsl_admin_validation", VAT_ECSL_PLUGIN_URL . "js/vatid_validation.js");
		wp_enqueue_script ("ecsl_admin_script", VAT_ECSL_PLUGIN_URL . "assets/js/ecsl_admin.js", array( 'jquery' ), VAT_ECSL_VERSION);

		wp_localize_script("ecsl_admin_script", 'ecsl_vars', array(
			'ajaxurl'            			=> $this->get_ajax_url(),
			'url'							=> home_url( '/' ),
			'lyquidity_server_url'			=> VAT_ECSL_STORE_API_URL,
			'ReasonNoLicenseKey'			=> __( 'There is no license key to check', 'vat_ecsl' ),
			'ReasonNoSenderId'				=> __( 'There is no sender id to test', 'vat_ecsl' ),
			'ReasonNoPassword'				=> __( 'There is no password', 'vat_ecsl' ),
			'ReasonSimpleCheckFails'		=> VAT_ECSL_REASON_SIMPLE_CHECK_FAILS,
			'ErrorValidatingCredentials'	=> 'An error occurred validating the credentials',
			'ErrorCheckingLicense'			=> 'An error occurred checking the license',
			'CredentialsValidated'			=> 'Credentials are valid',
			'LicenseChecked'				=> 'The license check is complete. There are {credits} remaining credits with this submission license key.'
		));

		wp_enqueue_script('jquery-ui-dialog', false, array('jquery-ui-core','jquery-ui-button', 'jquery') );
		wp_enqueue_script('jquery-tiptip', VAT_ECSL_PLUGIN_URL . 'assets/js/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), VAT_ECSL_VERSION);
	}

	/*
	|--------------------------------------------------------------------------
	| Perform actions on frontend initialization.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Hooks EDD actions, when present in the $_POST superglobal. Every edd_action
	 * present in $_POST is called using WordPress's do_action function. These
	 * functions are called on init.
	 *
	 * @since 1.0
	 * @return void
	*/
	function init()
	{
		if ( isset( $_GET['ecsl_action'] ) ) {
			do_action( 'ecsl_' . $_GET['ecsl_action'], $_GET );
		}

		if ( isset( $_POST['ecsl_action'] ) ) {
			error_log("do_action( 'ecsl_{$_POST['ecsl_action']}'");
			do_action( 'ecsl_' . $_POST['ecsl_action'], $_POST );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Add compatibility information the get_version response.
	|--------------------------------------------------------------------------
	*/
	function sl_license_response($response)
	{
		$response['tested'] = VAT_ECSL_WORDPRESS_COMPATIBILITY;
		$response['compatibility'] = serialize( array( VAT_ECSL_WORDPRESS_COMPATIBILITY => array( VAT_ECSL_VERSION => array("100%", "5", "5") ) ) );
		return $response;
	}

	/*
	|--------------------------------------------------------------------------
	| Perform actions on admin initialization.
	|--------------------------------------------------------------------------
	*/
	function admin_init()
	{
	}

	/**
	 * Callback to return plugin values to the updater
	 *
	 */
	function sl_updater_vat_ecsl($data, $required_fields)
	{
		// Can't rely on the global $edd_options (if your license is stored as an EDD option)
		$license_key = get_option('vat_ecsl_license_key');

		$data['license']	= $license_key;				// license key (used get_option above to retrieve from DB)
		$data['item_name']	= VAT_ECSL_PRODUCT_NAME;	// name of this plugin
		$data['api_url']	= VAT_ECSL_STORE_API_URL;
		$data['version']	= VAT_ECSL_VERSION;			// current version number
		$data['author']		= 'Lyquidity Solutions';	// author of this plugin

		return $data;
	}

	/**
	 * Get the current page URL
	 *
	 * @since 1.0.1
	 * @object $post
	 * @return string $page_url Current page URL
	 */
	function get_current_page_url() {
		global $post;

		if ( is_front_page() ) :
			$page_url = home_url( '/' );
		else :
			$page_url = 'http';

		if ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on" )
			$page_url .= "s";

		$page_url .= "://";

		if ( isset( $_SERVER["SERVER_PORT"] ) && $_SERVER["SERVER_PORT"] != "80" )
			$page_url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		else
			$page_url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		endif;

		return apply_filters( 'ecsl_get_current_page_url', esc_url( $page_url ) );
	}

	/**
	 * Get AJAX URL
	 *
	 * @since 1.0.1
	 * @return string
	*/
	function get_ajax_url() {
		$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';

		$current_url = $this->get_current_page_url();
		$ajax_url    = admin_url( 'admin-ajax.php', $scheme );

		if ( preg_match( '/^https/', $current_url ) && ! preg_match( '/^https/', $ajax_url ) ) {
			$ajax_url = preg_replace( '/^http/', 'https', $ajax_url );
		}

		return apply_filters( 'edd_ajax_url', $ajax_url );
	}
}

/**
 * The main function responsible for returning the one true example plug-in
 * instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: &lt;?php $plugin = initialize(); ?&gt;
 *
 * @since 1.0
 * @return object The one true WordPressPlugin Instance
 */
function vat_ecsl() {
	return WordPressPlugin::instance();
}

// Get EDD SL Change Expiry Date Running
vat_ecsl();

?>
