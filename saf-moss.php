<?php

/*
Plugin Name: WordPress VAT MOSS Standard Audit File Generator
Plugin URI: http://www.wproute.com/downloads/vat-moss-saf/
Description: Management and creation of standard audit files for MOSS.
Version: 1.0.13
Tested up to: 4.6.1
Author: Lyquidity Solutions
Author URI: http://www.wproute.com/
Contributors: Bill Seddon
Copyright: Lyquidity Solutions Limited
License: GNU Version 2 or Any Later Version
Updateable: true
Text Domain: vat_moss_saf
Domain Path: /languages
*/

namespace lyquidity\vat_moss_saf;

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

		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'post-types.php';
		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'class-vat-moss-saf-roles.php';
	}

	/**
	 * Setup any actions
	 */
	function actions()
	{
		global $moss_options;
		$moss_options = get_option( 'moss_settings' );

		// The supported ajax calls. 'action' parameter should be 'vat_moss_saf_action'
		add_action( 'vat_moss_saf_check_definition_license',	array( $this, 'check_definition_license' ) );
//		add_action( 'vat_moss_saf_generate_report',				array( $this, 'generate_report' ) );
		add_action( 'vat_moss_saf_download_report',				array( $this, 'download_report' ) );

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

		if (function_exists('vat_moss_saf_definitions_settings'))
		{
			// These three lines allow for the plugin folder name to be something other than vat-moss
			$plugin = plugin_basename(__FILE__);
			$basename = strtolower( dirname($plugin) );
			add_filter( 'sl_updater_' . $basename, array(&$this, 'sl_updater_vat_moss_saf'), 10, 2);

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
	function check_definition_license($data)
	{
		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'admin/submit_definition.php';

		$response = array(
			'version' => VAT_MOSS_SAF_VERSION,
			'status' => 'error',
			'message' => array( 'An unexpected error occurred' )
		);
		
		if (!isset($data['definition_key']) || empty($data['definition_key']))
		{
			$response['message'][] = "No definition key supplied";
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
					'edd_action'		=> 'moss_saf_check_definition_license',
					'definition_key'	=> $data['definition_key'],
					'url'				=> $data['url']
				),
				'cookies' => array()
			);
// error_log(print_r($args,true));
			$response = remote_get_handler( wp_remote_post( VAT_MOSS_SAF_STORE_API_URL, $args ) );
		}

		echo $response;

		exit();
	}
	
	function download_report( $data )
	{
		ob_clean();

		if (!current_user_can('send_definitions'))
		{
			echo "<div class='error'><p>" . __('You do not have rights to download a Standard Audit File', 'vat_moss_saf' ) . "</p></div>";
			exit;
		}

		try
		{
			if (!isset($data['definition_id']))
			{
				echo __( 'There is no definition id', 'vat_moss_saf' );
				exit;
			}

			$id = $data['definition_id'];
			if ( get_post_status( $id ) !== STATE_GENERATED)
			{
				echo __( 'The Standard Audit File has not been generated.', 'vat_moss_saf' );
				exit;
			}

			$report = get_post_meta($id, 'report', true);
			if (!$report)
			{
				echo __( 'There is no report to download', 'vat_moss_saf' );
				exit;
			}

			$title = get_the_title( $id );
			$definition_period = get_post_meta( $id, 'definition_period', true );
			$definition_year = get_post_meta( $id, 'definition_year', true );

			// Redirect output to a client’s web browser (Excel2007)
			header('Content-Type: application/xml');
			header('Content-Disposition: attachment;filename="' . "SAF $title Q$definition_period-$definition_year.xml");
			header('Cache-Control: max-age=0');
			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');

			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0

			echo $report;

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
			setup_vat_moss_saf_post_types();

			// Clear the permalinks
			flush_rewrite_rules();

			$roles = new MOSS_SAF_Roles;
			$roles->add_caps();
			$roles->add_roles();
		}
		catch(Exception $e)
		{
			set_transient(VAT_MOSS_SAF_ACTIVATION_ERROR_NOTICE, __("An error occurred during plugin activation: ", 'vat_moss_saf') . $e->getMessage(), 10);
		}
	}

	/**
	 * Take an action when the plugin is activated
	 */
	function plugin_deactivation()
	{
		try
		{
			$roles = new MOSS_SAF_Roles;
			$roles->remove_roles();
			$roles->remove_caps();
		}
		catch(Exception $e)
		{
			set_transient(VAT_MOSS_SAF_DEACTIVATION_ERROR_NOTICE, __("An error occurred during plugin deactivation: ", 'vat_moss_saf') . $e->getMessage(), 10);
		}
	}

	/**
	* Defines constants used by the plugin.
	*
	* @since 1.0
	*/
	function constants()
	{
		if ( ! defined( 'VAT_MOSS_SAF_PLUGIN_DIR' ) ) {
			define( 'VAT_MOSS_SAF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'VAT_MOSS_SAF_INCLUDES_DIR' ) ) {
			define( 'VAT_MOSS_SAF_INCLUDES_DIR', VAT_MOSS_SAF_PLUGIN_DIR . "includes/" );
		}

		if ( ! defined( 'VAT_MOSS_SAF_TEMPLATES_DIR' ) ) {
			define( 'VAT_MOSS_SAF_TEMPLATES_DIR', VAT_MOSS_SAF_PLUGIN_DIR . "templates/" );
		}

		if ( ! defined( 'VAT_MOSS_SAF_PLUGIN_URL' ) ) {
			define( 'VAT_MOSS_SAF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		if ( ! defined( 'VAT_MOSS_SAF_PLUGIN_FILE' ) ) {
			define( 'VAT_MOSS_SAF_PLUGIN_FILE', __FILE__ );
		}

		if ( ! defined( 'VAT_MOSS_SAF_VERSION' ) )
			define( 'VAT_MOSS_SAF_VERSION', '1.0.13' );

		if ( ! defined( 'VAT_MOSS_SAF_WORDPRESS_COMPATIBILITY' ) )
			define( 'VAT_MOSS_SAF_WORDPRESS_COMPATIBILITY', '4.6.1' );

		if ( ! defined( 'VAT_MOSS_SAF_STORE_API_URL' ) )
			define( 'VAT_MOSS_SAF_STORE_API_URL', 'https://www.wproute.com/' );

		if ( ! defined( 'VAT_MOSS_SAF_PRODUCT_NAME' ) )
			define( 'VAT_MOSS_SAF_PRODUCT_NAME', 'WP VAT MOSS Management' );

		if (!defined('VAT_MOSS_SAF_ACTIVATION_ERROR_NOTICE'))
			define('VAT_MOSS_SAF_ACTIVATION_ERROR_NOTICE', 'VAT_MOSS_SAF_ACTIVATION_ERROR_NOTICE');

		if (!defined('VAT_MOSS_SAF_ACTIVATION_UPDATE_NOTICE'))
			define('VAT_MOSS_SAF_ACTIVATION_UPDATE_NOTICE', 'VAT_MOSS_SAF_ACTIVATION_UPDATE_NOTICE');

		if (!defined('VAT_MOSS_SAF_DEACTIVATION_ERROR_NOTICE'))
			define('VAT_MOSS_SAF_DEACTIVATION_ERROR_NOTICE', 'VAT_MOSS_SAF_DEACTIVATION_ERROR_NOTICE');

		if (!defined('VAT_MOSS_SAF_DEACTIVATION_UPDATE_NOTICE'))
			define('VAT_MOSS_SAF_DEACTIVATION_UPDATE_NOTICE', 'VAT_MOSS_SAF_DEACTIVATION_UPDATE_NOTICE');

		if (!defined('VAT_MOSS_SAF_REASON_TOOSHORT'))
			define('VAT_MOSS_SAF_REASON_TOOSHORT',			 __('The VAT number supplied is too short', 'vat_moss_saf'));

		if (!defined('VAT_MOSS_SAF_REASON_INVALID_FORMAT'))
			define('VAT_MOSS_SAF_REASON_INVALID_FORMAT',		 __('The VAT number supplied does not have a valid format', 'vat_moss_saf'));

		if (!defined('VAT_MOSS_SAF_REASON_SIMPLE_CHECK_FAILS'))
			define('VAT_MOSS_SAF_REASON_SIMPLE_CHECK_FAILS',	 __('Simple check failed', 'vat_moss_saf'));

		if (!defined('VAT_MOSS_SAF_ERROR_VALIDATING_VAT_ID'))
			define('VAT_MOSS_SAF_ERROR_VALIDATING_VAT_ID',	 __('An error occurred validating the VAT number supplied', 'vat_moss_saf'));

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
		load_plugin_textdomain( 'vat_moss_saf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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

		global $wp_version;

		if (!isset($_REQUEST['vat_moss_saf_action']) && !is_admin() && php_sapi_name() !== "cli") return;

		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'admin-notices.php';

		// The SL plugin will not be available while at the network level
		// unless the SL is active in blog #1.
		if (is_network_admin()) return;

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		if ( ! class_exists( 'WP_Screen' ) )
		{
			// class-wp-screen.php was separated from screen.php in 4.4
			if ( version_compare( $wp_version, "4.3.99" ) > 0 )
			{
				require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
			}
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'class-menu.php';
		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'settings.php';
		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'definitions.php';
		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'class-settings.php';
		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'class-integrations.php';
		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'settings.php';
		require_once(VAT_MOSS_SAF_INCLUDES_DIR . 'class-html-elements.php');
		require_once(VAT_MOSS_SAF_INCLUDES_DIR . 'meta-box.php');
		require_once VAT_MOSS_SAF_INCLUDES_DIR . 'vatidvalidator.php';

		$this->settings = new MOSS_SAF_WP_Settings;
		$this->integrations = new MOSS_SAF_WP_Integrations;
		$this->html = new MOSS_SAF_HTML_Elements;
	}

	/**
	 * Enqueue scripts and styles
	 */

	function enqueue_scripts()
	{
		wp_enqueue_style("vat_moss_saf_style",  VAT_MOSS_SAF_PLUGIN_URL . "assets/css/vat_moss_saf.css", null, null, "screen");

		wp_enqueue_script ("vat_moss_saf_script", VAT_MOSS_SAF_PLUGIN_URL . "assets/js/vat_moss_saf.js", array( 'jquery' ));
		wp_localize_script("vat_moss_saf_script", 'vat_moss_saf_vars', array(
			'ajaxurl'            			=> $this->get_ajax_url(),
			'lyquidity_server_url'			=> VAT_MOSS_SAF_STORE_API_URL
		));

		wp_enqueue_script('jquery-ui-dialog', false, array('jquery-ui-core','jquery-ui-button', 'jquery') );

	} // end vat_enqueue_scripts

	function admin_enqueue_scripts()
	{
		$suffix = '';

		wp_enqueue_style  ("vat_moss_saf_admin_style",  VAT_MOSS_SAF_PLUGIN_URL . "assets/css/vat_moss_saf_admin.css", null, null, "screen");

//		wp_enqueue_script ("vat_moss_saf_admin_validation", VAT_MOSS_SAF_PLUGIN_URL . "js/vatid_validation.js");
		wp_enqueue_script ("vat_moss_saf_admin_script", VAT_MOSS_SAF_PLUGIN_URL . "assets/js/vat_moss_saf_admin.js", array( 'jquery' ), VAT_MOSS_SAF_VERSION);

		wp_localize_script("vat_moss_saf_admin_script", 'vat_moss_saf_vars', array(
			'ajaxurl'            			=> $this->get_ajax_url(),
			'url'							=> home_url( '/' ),
			'lyquidity_server_url'			=> VAT_MOSS_SAF_STORE_API_URL,
			'ReasonNoLicenseKey'			=> __( 'There is no license key to check', 'vat_moss_saf' ),
			'ReasonSimpleCheckFails'		=> VAT_MOSS_SAF_REASON_SIMPLE_CHECK_FAILS,
			'ErrorCheckingLicense'			=> 'An error occurred checking the license',
			'LicenseChecked'				=> 'The license check is complete. There are {credits} remaining credits with this definition license key.',
			'UnexpectedErrorLicense'		=> 'An unexpected error occurred validating the license.  If this error persists, contact the administrator.'
		));

		wp_enqueue_script('jquery-ui-dialog', false, array('jquery-ui-core','jquery-ui-button', 'jquery') );
		wp_enqueue_script('jquery-tiptip', VAT_MOSS_SAF_PLUGIN_URL . 'assets/js/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), VAT_MOSS_SAF_VERSION);
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
		if ( isset( $_GET['vat_moss_saf_action'] ) ) {
			error_log("get - do_action( 'vat_moss_saf_{$_GET['vat_moss_saf_action']}'");
			do_action( 'vat_moss_saf_' . $_GET['vat_moss_saf_action'], $_GET );
		}

		if ( isset( $_POST['vat_moss_saf_action'] ) ) {
			error_log("post - do_action( 'vat_moss_saf_{$_POST['vat_moss_saf_action']}'");
			do_action( 'vat_moss_saf_' . $_POST['vat_moss_saf_action'], $_POST );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Add compatibility information the get_version response.
	|--------------------------------------------------------------------------
	*/
	function sl_license_response($response)
	{
		$response['tested'] = VAT_MOSS_SAF_WORDPRESS_COMPATIBILITY;
		$response['compatibility'] = serialize( array( VAT_MOSS_SAF_WORDPRESS_COMPATIBILITY => array( VAT_MOSS_SAF_VERSION => array("100%", "5", "5") ) ) );
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
	function sl_updater_vat_moss_saf($data, $required_fields)
	{
		// Can't rely on the global $edd_options (if your license is stored as an EDD option)
		$license_key = get_option('vat_moss_saf_license_key');

		$data['license']	= $license_key;				// license key (used get_option above to retrieve from DB)
		$data['item_name']	= VAT_MOSS_SAF_PRODUCT_NAME;	// name of this plugin
		$data['api_url']	= VAT_MOSS_SAF_STORE_API_URL;
		$data['version']	= VAT_MOSS_SAF_VERSION;			// current version number
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
		
		$page_url = home_url( is_front_page() ? '/' : $_SERVER["REQUEST_URI"], $scheme = 'relative' );

		return apply_filters( 'vat_moss_saf_get_current_page_url', esc_url( $page_url ) );
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
function vat_moss_saf() {
	return WordPressPlugin::instance();
}

// Get EDD SL Change Expiry Date Running
vat_moss_saf();

?>
