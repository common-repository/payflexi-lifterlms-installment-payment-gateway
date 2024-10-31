<?php
/**
 * This is the main payflexi gateway plugin class
 *
 * This singleton acts as a bootstrap to load files, add initializing actions, etc...
 *
 * @package LifterLMS_Payflexi_Gateway/Classes
 *
 * @since 2021-02-18
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LifterLMS Gateway Payflexi
 */
final class LifterLMS_Payflexi_Gateway {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version = '1.0.2';

	/**
	 * Singleton instance of the class
	 *
	 * @var LifterLMS_Payflexi_Gateway
	 */
	private static $instance = null;

	/**
	 * Singleton Instance of the LifterLMS_Payflexi class
	 *
	 * @since 1.0.0
	 *
	 * @return LifterLMS_Payflexi_Gateway
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function __construct() {

		if ( ! defined( 'LLMS_PAYFLEXI_GATEWAY_VERSION' ) ) {
			define( 'LLMS_PAYFLEXI_GATEWAY_VERSION', $this->version );
		}

		add_action( 'init', array( $this, 'load_textdomain' ), 0 );

		// Load the plugin.
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Cleanup.
		register_deactivation_hook( LLMS_PAYFLEXI_GATEWAY_PLUGIN_FILE, array( $this, 'deactivate' ) );

	}

	/**
	 * Determines whether or not the plugin's dependencies are met
	 *
	 * This stub checks to see if the minimum required version of LifterLMS is installed
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function are_plugin_requirements_met() {

		return ( function_exists( 'llms' ) && version_compare( '4.0.0', llms()->version, '<=' ) );

	}

	/**
	 * Plugin deactivation.
	 *
	 * This method can be used to delete plugin data such as options or custom post types.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function deactivate() {}

	/**
	 * Retrieves an instance of the gateway itself
	 *
	 * This function isn't strictly necessary but is useful to quickly retrieve an instance of the gateway.
	 *
	 * @since 1.0.0
	 *
	 * @example llms_payflexi_gateway()->get_gateway()
	 *
	 * @return LLMS_Payment_Gateway_Payflexi
	 */
	public function get_gateway() {
		return llms()->payment_gateways()->get_gateway_by_id( 'payflexi' );
	}

	/**
	 * Include all required files
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function includes() {

		require_once LLMS_PAYFLEXI_GATEWAY_PLUGIN_DIR . 'includes/class-llms-payment-gateway-payflexi.php';

	}
	/**
	 * Include all required files and classes
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {

		// Only load the plugin if the plugin's requirements have been met.
		if ( $this->are_plugin_requirements_met() ) {

			// Register the payment gateway with LifterLMS.
			add_filter( 'lifterlms_payment_gateways', array( $this, 'register_gateway' ) );

			// Load all plugin files.
			$this->includes();

			// Load gateway request handler.
			add_action( 'wp', array( $this->get_gateway(), 'handle_gateway_requests' ) );

			add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );

		}

	}

	/**
	 * Load Localization files
	 *
	 * The first loaded file takes priority.
	 *
	 * Files can be found in the following order:
	 *      WP_LANG_DIR/lifterlms/lifterlms-payflexi-gateway-LOCALE.mo
	 *      WP_LANG_DIR/plugins/lifterlms-payflexi-gateway-LOCALE.mo
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_textdomain() {

		// Load locale.
		$locale = apply_filters( 'plugin_locale', get_locale(), 'lifterlms-payflexi-gateway' );

		// Load a lifterlms specific locale file if one exists.
		load_textdomain( 'lifterlms-payflexi-gateway', WP_LANG_DIR . '/lifterlms/lifterlms-payflexi-gateway-' . $locale . '.mo' );

		// Load localization files.
		load_plugin_textdomain( 'lifterlms-payflexi-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	}

	/**
	 * Register the gateway with LifterLMS
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $gateways Array of currently registered gateway class names.
	 * @return string[]
	 */
	public function register_gateway( $gateways ) {

		$gateways[] = 'LLMS_Payment_Gateway_Payflexi';
		return $gateways;

	}

	/**
	 * Require REST API classes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function rest_api_init() {
		
		require_once LLMS_PAYFLEXI_GATEWAY_PLUGIN_DIR . 'includes/class-llms-payflexi-events-controller.php';

		$events = new LLMS_PayFlexi_Events_Controller();
		$events->register_routes();
	}

}
