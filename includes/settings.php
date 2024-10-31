<?php
/**
 * MOSS SAF Settings Functions
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

function moss_saf_settings()
{
	$active_tab = isset( $_GET[ 'tab' ] ) && array_key_exists( $_GET['tab'], moss_saf_get_settings_tabs() ) ? $_GET[ 'tab' ] : 'general';

	ob_start();
?>

	<div class="wrap">
		<?php
		advert( 'standard-audit-file-saf-moss', VAT_MOSS_SAF_VERSION, function() use( $active_tab ) {
		?>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach( moss_saf_get_settings_tabs() as $tab_id => $tab_name ) {

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
				settings_fields( 'moss_saf_settings' );
				do_settings_fields( 'moss_saf_settings_' . $active_tab, 'moss_saf_settings_' . $active_tab );
				?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div><!-- #tab_container-->
		<?php
		} );
		?>
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
function moss_saf_get_settings_tabs() {

	$tabs                 = array();
	$tabs['general']      = __( 'General', 'vat_moss_saf' );
	$tabs['integrations'] = __( 'Integrations', 'vat_moss_saf' );

	return apply_filters( 'moss_saf_settings_tabs', $tabs );
}

/**
 * Get Currencies
 *
 * @since 1.0
 * @return array $currencies A list of the available currencies
 */
function get_currencies() {
	$currencies = array(
		'EUR'  => __( 'Euros (&euro;)', 'vat_moss_saf' ),
		'GBP'  => __( 'Pounds Sterling (&pound;)', 'vat_moss_saf' ),
		'USD'  => __( 'US Dollars (&#36;)', 'vat_moss_saf' ),
		'AUD'  => __( 'Australian Dollars (&#36;)', 'vat_moss_saf' ),
		'BRL'  => __( 'Brazilian Real (R&#36;)', 'vat_moss_saf' ),
		'CAD'  => __( 'Canadian Dollars (&#36;)', 'vat_moss_saf' ),
		'CZK'  => __( 'Czech Koruna', 'vat_moss_saf' ),
		'DKK'  => __( 'Danish Krone', 'vat_moss_saf' ),
		'HKD'  => __( 'Hong Kong Dollar (&#36;)', 'vat_moss_saf' ),
		'HUF'  => __( 'Hungarian Forint', 'vat_moss_saf' ),
		'ILS'  => __( 'Israeli Shekel (&#8362;)', 'vat_moss_saf' ),
		'JPY'  => __( 'Japanese Yen (&yen;)', 'vat_moss_saf' ),
		'MYR'  => __( 'Malaysian Ringgits', 'vat_moss_saf' ),
		'MXN'  => __( 'Mexican Peso (&#36;)', 'vat_moss_saf' ),
		'NZD'  => __( 'New Zealand Dollar (&#36;)', 'vat_moss_saf' ),
		'NOK'  => __( 'Norwegian Krone', 'vat_moss_saf' ),
		'PHP'  => __( 'Philippine Pesos', 'vat_moss_saf' ),
		'PLN'  => __( 'Polish Zloty', 'vat_moss_saf' ),
		'SGD'  => __( 'Singapore Dollar (&#36;)', 'vat_moss_saf' ),
		'SEK'  => __( 'Swedish Krona', 'vat_moss_saf' ),
		'CHF'  => __( 'Swiss Franc', 'vat_moss_saf' ),
		'TWD'  => __( 'Taiwan New Dollars', 'vat_moss_saf' ),
		'THB'  => __( 'Thai Baht (&#3647;)', 'vat_moss_saf' ),
		'INR'  => __( 'Indian Rupee (&#8377;)', 'vat_moss_saf' ),
		'TRY'  => __( 'Turkish Lira (&#8378;)', 'vat_moss_saf' ),
		'RIAL' => __( 'Iranian Rial (&#65020;)', 'vat_moss_saf' ),
		'RUB'  => __( 'Russian Rubles', 'vat_moss_saf' )
	);

	return apply_filters( 'moss_saf_currencies', $currencies );
}

/**
 * Given a currency determine the symbol to use. If no currency given, site default is used.
 * If no symbol is determine, the currency string is returned.
 *
 * @since  1.0
 * @param  string $currency The currency string
 * @return string           The symbol to use for the currency
 */
function get_currency_symbol( $currency = '' ) {
	global $edd_options;

	if ( empty( $currency ) ) {
		$currency = get_default_currency();
	}

	switch ( $currency ) :
		case "GBP" :
			$symbol = '&pound;';
			break;
		case "BRL" :
			$symbol = 'R&#36;';
			break;
		case "EUR" :
			$symbol = '&euro;';
			break;
		case "USD" :
		case "AUD" :
		case "NZD" :
		case "CAD" :
		case "HKD" :
		case "MXN" :
		case "SGD" :
			$symbol = '&#36;';
			break;
		case "JPY" :
			$symbol = '&yen;';
			break;
		default :
			$symbol = $currency;
			break;
	endswitch;

	return apply_filters( 'edd_currency_symbol', $symbol, $currency );
}

function get_default_currency()
{
	return vat_moss_saf()->settings->get( 'currency', 'EUR' );
}

function get_establishment_country()
{
	return vat_moss_saf()->settings->get( 'country', 'GB' );
}

function get_company_name()
{
	return vat_moss_saf()->settings->get(
		'company_name', 
		function_exists('\get_blog_info') ? \get_blog_info( 'name' ) : '' 
	);
}

function include_customer_details()
{
	return vat_moss_saf()->settings->get( 'customer_details', false );
}

function get_vat_number()
{
	return vat_moss_saf()->settings->get( 'vat_number', false );
}

function get_submitter()
{
	return vat_moss_saf()->settings->get( 'submitter', '' );
}

function get_submitter_email()
{
	return vat_moss_saf()->settings->get( 'email', '' );
}


?>