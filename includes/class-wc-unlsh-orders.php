<?php

/**
 * The class responsible for defining all actions and filters for Checkout page.
 */
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-unlsh-orders-checkout.php';

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    WCUnleashedOrders
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WCUnleashedOrders
 * @author     Frank Luzón <fluzon@jaibasoft.com>
 */
class WCUnlshOrder {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WCUnlshOrder_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Loads all methos to handle Unleashed API
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WCUnlshOrder_API_Handler    $unleashed  Unleashed API handler class
	 */
	protected $unleashed;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WC_UNLSH_ORDERS_VERSION' ) ) {
			$this->version = WC_UNLSH_ORDERS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wc-unlsh-orders';

		$this->load_dependencies();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
	 * - Plugin_Name_i18n. Defines internationalization functionality.
	 * - Plugin_Name_Admin. Defines all hooks for the admin area.
	 * - Plugin_Name_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-unlsh-orders-loader.php';

		/**
		* The class responsible for definig all interaction with Unleashed API.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-unlsh-api_handler.php';

		$this->unleashed = new WCUnlshOrder_API_Handler();

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-unlsh-orders-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area but for customers.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wc-unlsh-customers-admin.php';

		$this->loader = new WCUnlshOrder_Loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new WCUnlshOrder_Admin( $this->get_plugin_name(), $this->get_version() , $this->unleashed);
		$plugin_customer_admin = new WCUnlshCustomer_Admin( $this->get_plugin_name(), $this->get_version() , $this->unleashed);
		$checkout_page = new WCUnlshCheckout();

		$this->loader->add_action( 'woocommerce_order_status_completed', $plugin_admin, 'register_order_in_unleashed' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'settings_init' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_unleashed_options_page' );
		$this->loader->add_filter( 'woocommerce_login_redirect', $plugin_customer_admin, 'add_unlsh_customer_code', 10, 2);
		$this->loader->add_filter( 'woocommerce_get_price_html', $plugin_customer_admin, 'get_price_html', 10, 3);

		$this->loader->add_filter( 'woocommerce_gateway_description', $checkout_page, 'gateway_bacs_custom_fields', 20, 2);
		$this->loader->add_action( 'woocommerce_checkout_process', $checkout_page, 'purchase_order_number_checkout_field_validation');
		$this->loader->add_action( 'woocommerce_checkout_create_order', $checkout_page, 'save_purchase_order_number', 10, 2);

		$this->loader->add_filter( 'woocommerce_after_checkout_billing_form', $checkout_page, 'delivery_method_field', 20, 1);
		$this->loader->add_action( 'woocommerce_checkout_process', $checkout_page, 'delivery_method_field_validation');
		$this->loader->add_action( 'woocommerce_checkout_create_order', $checkout_page, 'save_delivery_method', 10, 2);

	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WCUnlshOrder_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
