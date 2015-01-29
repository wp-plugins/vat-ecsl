<?php
/**
 * ECSL Type Functions
 *
 * @package     vat-ecsl
 * @subpackage  Includes
 * @copyright   Copyright (c) 2014, Lyquidity Solutions Limited
 * @license     Lyquidity Commercial
 * @since       1.0
 */

namespace lyquidity\vat_ecsl;

function ecsl_submissions_settings()
{
	$active_tab = isset( $_GET[ 'tab' ] ) && array_key_exists( $_GET['tab'], ecsl_get_settings_tabs() ) ? $_GET[ 'tab' ] : 'general';

	ob_start();
?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php
			foreach( ecsl_get_settings_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab' => $tab_id
				) );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
					echo esc_html( $tab_name );
				echo '</a>';
			}
			?>
		</h2>
		<div id="tab_container">
			<form method="post" action="options.php">
				<table class="form-table">
				<?php
				settings_fields( 'ecsl_settings' );
				do_settings_fields( 'ecsl_settings_' . $active_tab, 'ecsl_settings_' . $active_tab );
				?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div><!-- #tab_container-->
	</div><!-- .wrap -->
	<?php
	echo ob_get_clean();
}


/**
 * Retrieve settings tabs
 *
 * @since 1.0
 * @return array $tabs
 */
function ecsl_get_settings_tabs() {

	$tabs                 = array();
	$tabs['general']      = __( 'General', 'vat_ecsl' );
	$tabs['integrations'] = __( 'Integrations', 'vat_ecsl' );

	return apply_filters( 'ecsl_settings_tabs', $tabs );
}

?>