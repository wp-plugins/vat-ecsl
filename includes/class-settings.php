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

class ECSL_WP_Settings {

	private $options;

	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @return void
	*/
	public function __construct() {

		$this->options = get_option( 'ecsl_settings', array() );

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Get the value of a specific setting
	 *
	 * @since 1.0
	 * @return mixed
	*/
	public function get( $key, $default = false ) {
		$value = ! empty( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
		return $value;
	}

	/**
	 * Get all settings
	 *
	 * @since 1.0
	 * @return array
	*/
	public function get_all() {
		return $this->options;
	}

	/**
	 * Add all settings sections and fields
	 *
	 * @since 1.0
	 * @return void
	*/
	function register_settings() {

		if ( false == get_option( 'ecsl_settings' ) ) {
			add_option( 'ecsl_settings' );
		}

		foreach( $this->get_registered_settings() as $tab => $settings ) {

			add_settings_section(
				'ecsl_settings_' . $tab,
				__return_null(),
				'__return_false',
				'ecsl_settings_' . $tab
			);

			foreach ( $settings as $key => $option ) {

				$name = isset( $option['name'] ) ? $option['name'] : '';

				add_settings_field(
					'ecsl_settings[' . $key . ']',
					$name,
					is_callable( array( $this, $option[ 'type' ] . '_callback' ) ) ? array( $this, $option[ 'type' ] . '_callback' ) : array( $this, 'missing_callback' ),
					'ecsl_settings_' . $tab,
					'ecsl_settings_' . $tab,
					array(
						'id'      => $key,
						'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
						'name'    => isset( $option['name'] ) ? $option['name'] : null,
						'section' => $tab,
						'size'    => isset( $option['size'] ) ? $option['size'] : null,
						'max'     => isset( $option['max'] ) ? $option['max'] : null,
						'min'     => isset( $option['min'] ) ? $option['min'] : null,
						'step'    => isset( $option['step'] ) ? $option['step'] : null,
						'options' => isset( $option['options'] ) ? $option['options'] : '',
						'std'     => isset( $option['std'] ) ? $option['std'] : ''
					)
				);
			}

		}

		// Creates our settings in the options table
		register_setting( 'ecsl_settings', 'ecsl_settings', array( $this, 'sanitize_settings' ) );

	}

	/**
	 * Retrieve the array of plugin settings
	 *
	 * @since 1.0
	 * @return array
	*/
	function sanitize_settings( $input = array() ) {

		if ( empty( $_POST['_wp_http_referer'] ) ) {
			return $input;
		}

		parse_str( $_POST['_wp_http_referer'], $referrer );

		$saved    = get_option( 'ecsl_settings', array() );
		if( ! is_array( $saved ) ) {
			$saved = array();
		}
		$settings = $this->get_registered_settings();
		$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';

		$input = $input ? $input : array();
		$input = apply_filters( 'ecsl_settings_' . $tab . '_sanitize', $input );

		// Ensure a value is always passed for every checkbox
		if( ! empty( $settings[ $tab ] ) ) {
			foreach ( $settings[ $tab ] as $key => $setting ) {

				// Single checkbox
				if ( isset( $settings[ $tab ][ $key ][ 'type' ] ) && 'checkbox' == $settings[ $tab ][ $key ][ 'type' ] ) {
					$input[ $key ] = ! empty( $input[ $key ] );
				}

				// Multicheck list
				if ( isset( $settings[ $tab ][ $key ][ 'type' ] ) && 'multicheck' == $settings[ $tab ][ $key ][ 'type' ] ) {
					if( empty( $input[ $key ] ) ) {
						$input[ $key ] = array();
					}
				}
			}
		}
		
		// Loop through each setting being saved and pass it through a sanitization filter
		foreach ( $input as $key => $value ) {

			// Get the setting type (checkbox, select, etc)
			$type = isset( $settings[ $tab ][ $key ][ 'type' ] ) ? $settings[ $tab ][ $key ][ 'type' ] : false;

			if ( $type ) {
				// Field type specific filter
				$input[$key] = apply_filters( 'ecsl_settings_sanitize_' . $type, $value, $key );
			}

			// General filter
			$input[ $key ] = apply_filters( 'ecsl_settings_sanitize', $value, $key );
		}

		add_settings_error( 'ecsl-notices', '', __( 'Settings updated.', 'vat_ecsl' ), 'updated' );

		return array_merge( $saved, $input );

	}

	/**
	 * Retrieve the array of plugin settings
	 *
	 * @since 1.0
	 * @return array
	*/
	function get_registered_settings() {

		$settings = array(
			/** General Settings */
			'general' => apply_filters( 'ecsl_settings_general',
				array(
					'submission_details' => array(
						'name' => '<strong>' . __( 'Submission Details', 'vat_ecsl' ) . '</strong>',
						'desc' => '',
						'type' => 'header'
					),

					'sender_id' => array(
						'name' => __( 'Your HMRC Account ID', 'vat_ecsl' ),
						'desc' => '<p class="description">' . __( 'Enter the account ID you have been provided by HMRC.', 'vat_ecsl' ) . '</p>',
						'type' => 'text',
						'std'  => ''
					),
					'password' => array(
						'name' => __( 'Your Account Password', 'vat_ecsl' ),
						'desc' => '<p class="description">' . __( 'Enter the password corresponding to the HMRC account ID.', 'vat_ecsl' ) . '</p>',
						'type' => 'password',
						'std'  => ''
					),
					'validate' => array(
						'name' => '',
						'desc' => '',
						'type' => 'validate'
					),
					'vat_number' => array(
						'name' => __( 'Your VAT Number', 'vat_ecsl' ),
						'desc' => '<p class="description">' . __( 'Enter your VAT number without country code.', 'vat_ecsl' ) . '</p>',
						'type' => 'text'
					),
					'submitter' => array(
						'name' => __( 'Submitters Name', 'vat_ecsl' ),
						'desc' => '<p class="description">' . __( 'The default name of the submitter.', 'vat_ecsl' ) . '</p>',
						'type' => 'text'
					),
					'email' => array(
						'name' => __( 'Submitters Email Address', 'vat_ecsl' ),
						'desc' => '<p class="description">' . __( 'Enter a default email address for the submission.', 'vat_ecsl' ) . '</p>',
						'type' => 'text'
					),
					'branch' => array(
						'name' => __( 'Branch number', 'vat_ecsl' ),
						'desc' => '<p class="description">' . __( 'Enter the default branch number for the submitting organisation.', 'vat_ecsl' ) . '</p>',
						'type' => 'text',
						'std' => '000'
					),
					'postcode' => array(
						'name' => __( 'Postcode', 'vat_ecsl' ),
						'desc' => '<p class="description">' . __( 'Enter the default postcode of the submitting organisation. Do not include a space.', 'vat_ecsl' ) . '</p>',
						'type' => 'text'
					),
					'period' => array(
						'name' => __( 'Period', 'vat_ecsl' ),
						'desc' => __( 'Select the period (monthly or quarterly) that you will be submitting ECSL returns', 'vat_ecsl' ),
						'type' => 'radio',
						'std' => 'quarterly',
						'options' => array(
							'quarterly' => __( 'The EC Sales List (VAT 101) is submitted quarterly (April, July, November, January', 'vat_ecsl' ),
							'monthly' 	=> __( 'The EC Sales List (VAT 101) is submitted monthly', 'vat_ecsl' )
						)
					)
				)
			),
			/** Integration Settings */
			'integrations' => apply_filters( 'ecsl_settings_integrations',
				array(
					'integrations' => array(
						'name' => __( 'Integrations', 'vat_ecsl' ),
						'desc' => __( 'Choose the integrations to enable.', 'vat_ecsl' ),
						'type' => 'multicheck',
						'options' => ECSL_WP_Integrations::get_integrations_list()
					)
				)
			)
		);

		return apply_filters( 'ecsl_settings', $settings );
	}

	/**
	 * Header Callback
	 *
	 * Renders the header.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	function header_callback( $args ) {
		echo '<hr/>';
	}

	/**
	 * Checkbox Callback
	 *
	 * Renders checkboxes.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function checkbox_callback( $args ) {

		$checked = isset($this->options[$args['id']]) ? checked(1, $this->options[$args['id']], false) : '';
		$html = '<input type="checkbox" id="ecsl_settings[' . $args['id'] . ']" name="ecsl_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
		$html .= '<label for="ecsl_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Multicheck Callback
	 *
	 * Renders multiple checkboxes.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function multicheck_callback( $args ) {

		if ( ! empty( $args['options'] ) ) {
			foreach( $args['options'] as $key => $option ) {
				if( isset( $this->options[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
				echo '<input name="ecsl_settings[' . $args['id'] . '][' . $key . ']" id="ecsl_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked($option, $enabled, false) . '/>&nbsp;';
				echo '<label for="ecsl_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
			}
			echo '<p class="description">' . $args['desc'] . '</p>';
		}
	}

	/**
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function radio_callback( $args ) {

		foreach ( $args['options'] as $key => $option ) :
			$checked = false;

			if ( isset( $this->options[ $args['id'] ] ) && $this->options[ $args['id'] ] == $key )
				$checked = true;
			elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( $this->options[ $args['id'] ] ) )
				$checked = true;

			echo '<input name="ecsl_settings[' . $args['id'] . ']"" id="ecsl_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>&nbsp;';
			echo '<label for="ecsl_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
		endforeach;

		echo '<p class="description">' . $args['desc'] . '</p>';
	}

	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function text_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="ecsl_settings[' . $args['id'] . ']" name="ecsl_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<label for="ecsl_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function number_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$max  = isset( $args['max'] ) ? $args['max'] : 999999;
		$min  = isset( $args['min'] ) ? $args['min'] : 0;
		$step = isset( $args['step'] ) ? $args['step'] : 1;

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="ecsl_settings[' . $args['id'] . ']" name="ecsl_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<label for="ecsl_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function textarea_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<textarea class="large-text" cols="50" rows="5" id="ecsl_settings[' . $args['id'] . ']" name="ecsl_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		$html .= '<label for="ecsl_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function password_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="password" class="' . $size . '-text" id="ecsl_settings[' . $args['id'] . ']" name="ecsl_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
		$html .= '<label for="ecsl_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';
		$html .= "<input type=\"hidden\" name=\"_wp_nonce\" value=\"" . wp_create_nonce( 'ecsl_settings' ) . "\" >";

		echo $html;
	}

	/**
	 * Missing Callback
	 *
	 * If a function is missing for settings callbacks alert the user.
	 *
	 * @since 1.3.1
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	function missing_callback($args) {
		printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 'vat_ecsl' ), $args['id'] );
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @return void
	 */
	function select_callback($args) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$html = '<select id="ecsl_settings[' . $args['id'] . ']" name="ecsl_settings[' . $args['id'] . ']"/>';

		foreach ( $args['options'] as $option => $name ) :
			$selected = selected( $option, $value, false );
			$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
		endforeach;

		$html .= '</select>';
		$html .= '<label for="ecsl_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Rich Editor Callback
	 *
	 * Renders rich editor fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $this->options Array of all the plugin Options
	 * @global $wp_version WordPress Version
	 */
	function rich_editor_callback( $args ) {

		if ( isset( $this->options[ $args['id'] ] ) )
			$value = $this->options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		ob_start();
		wp_editor( stripslashes( $value ), 'ecsl_settings[' . $args['id'] . ']', array( 'textarea_name' => 'ecsl_settings[' . $args['id'] . ']' ) );
		$html = ob_get_clean();

		$html .= '<br/><label for="ecsl_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	function validate_callback( $args ) {
?>
		<button id="validate_credentials" password_id="ecsl_settings\[password\]" sender_id="ecsl_settings\[sender_id\]" value="Validate Credentials" class="button button-primary" >Validate Credentials</button>
		<img src="<?php echo VAT_ECSL_PLUGIN_URL . "images/loading.gif" ?>" id="ecsl-loading" style="display:none; margin-left: 10px; margin-top: 8px;" />
		<input type="hidden" name="_validate_credentials_nonce" value="<?php echo wp_create_nonce( 'validate_credentials' ); ?>" >
<?php
	}
}
