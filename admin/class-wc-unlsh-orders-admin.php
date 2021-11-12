<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    WCUnleashedOrders
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WCUnleashedOrders
 * @author     Frank Luzón <fluzon@jaibasoft.com>
 */
class WCUnlshOrder_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The Unleashed API Handler class.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $unleashed
	 */
	private $unleashed;

	/**
	 * The Global Unique Identifier for Woocommerce customer order .
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $guid_customer    The ID of this plugin.
	 */
	private $guid_customer;

	/**
	 * The Woocommerce	object to work on it
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      object    $wc_order   Woocommerce Order object
	 */
	private $wc_order;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $unleashed ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->unleashed = $unleashed;
		$this->guid_customer = '';
		$this->wc_order = null;


	}


	/**
	 * Register Woocommerce completed order in Unleashed
	 *
	 * @since    1.0.0
	 */
	public function register_order_in_unleashed($wc_order_id) {

		//get wc order and wc customer data
		$this->wc_order = wc_get_order( $wc_order_id );
	  $order_data = $this->wc_order->get_data();

		if (!$this->order_is_registered_in_unleashed())
		{
			$user_email = $order_data['billing']['email'];

			//check if customer is already registered in Unleashed
			$customer_exist = $this->unleashed_customer_exists($user_email);

			if (is_bool($customer_exist))
			{
				if (!$customer_exist)
				{
					//if customer is not registered, then create it in Unleashed
					$this->create_unleashed_customer($order_data);
				}

				//customer guid must be set at this point, execution must continue only when customer GUID was found
				if ($this->guid_customer != '')
				{
					$this->create_unleashed_sales_order($order_data);
				}
			}

		}
	}

	/**
	 * Valid if Woocommerce Order is already registered in Unleashed
	 *
	 * @since    1.0.0
	 * @param 	object $wc_order  order object
	 * @return boolean
	 */
	public function order_is_registered_in_unleashed() {

		if (empty(get_post_meta($this->wc_order->get_id(),'unleashed_order_number',true)))
		{
			return false;
		}
		else {
			return true;
		}

	}

	/**
	 * Save Woocommerce Order metadata according registering process results
	 *
	 * @since    1.0.0
	 * @param 	object $wc_order  order object
	 */
	public function update_metadata($successful_registration,$value) {

		if ($successful_registration)
		{
			update_post_meta($this->wc_order->get_id(),'unleashed_order_number',$value);
			delete_post_meta($this->wc_order->get_id(),'unleashed_registration_error_message');
		}
		else
		{
			delete_post_meta($this->wc_order->get_id(),'unleashed_order_number');
			update_post_meta($this->wc_order->get_id(),'unleashed_registration_error_message',$value);
		}
	}

	/**
	 * Check if customer exists using Unleashed API. if customer exists, initialize private variable
	 *
	 * @since    1.0.0
	 * @param      string    $user_email       The email address of the buyer in Woocommerce.
	 * @return 	mixed true is customer exists, false is customer not exist, 'error' if there an error
	 */
	public function unleashed_customer_exists($user_email) {

		global $wpdb;
		$results = $wpdb->get_results( "SELECT customer_code, customer_guid FROM `contacts_data` WHERE contact_email = '" . $user_email . "' ", OBJECT );

		if (!empty($results[0]))
		{
			$this->guid_customer = $results[0]->customer_guid;
			return true;
		}

		//customer doesn't exists
		error_log("Email: $user_email was not found as contact when trying to complete order.");
		return false;

	}

	/**
	 * Create customer using Unleashed API.
	 *
	 * @since    1.0.0
	 * @param      array    $order_data       Woocomerce order data.
	 */
	public function create_unleashed_customer($order_data) {

		$guid = $this->unleashed->create_guid();
		$customer_code = 'WC-' . $order_data['customer_id'];
		$email = $order_data['billing']['email'];

		$first_name = $order_data['billing']['first_name'];
		$last_name = $order_data['billing']['last_name'];
		$customer_name = $first_name . ' ' . $last_name;

		$request = 'Customers/' . $guid;

		$body = array(
			'Guid' => $guid,
			'CustomerCode' => $customer_code,
			'CustomerName' => $customer_name,
			'Email' => $email,
			'Notes' => null,
			'ContactFirstName' => $first_name,
			'ContactLastName' => $last_name
		);

		$response = $this->unleashed->post_request($request,'', json_encode($body));
		$http_code = $this->unleashed->get_http_response_code( $response );
		$json_response = $this->unleashed->get_response_body($response);

		if ($http_code == 201) {
			//if response is OK, than retrieve data
			$this->guid_customer = $guid;
		}
		else
		{
			$this->update_metadata(false,'Unable to create customer in Unleashed. Response code: ' . $http_code);
			error_log('Unable to create customer in Unleashed. Response code: ' . $http_code);
			error_log('Response Body ' . print_r(json_encode($json_response),true));
		}
	}

	/**
	 * Create Woocommerce order in Unleashed.
	 *
	 * @since    1.0.0
	 * @param      string    $user_email       The email address of the buyer in Woocommerce.
	 */
	public function create_unleashed_sales_order($order_data) {

		$sales_order_lines_array = $this->create_sales_order_lines_array($order_data);
		$tax_array = $this->create_tax_array();

		//order array
		$guid = $this->unleashed->create_guid();

		$request = 'SalesOrders/' . $guid . '?';
		$query_params = 'taxInclusive=true';
		$order_array = array(
			'SalesOrderLines' => $sales_order_lines_array,
			'OrderStatus' => 'Placed',
			'Customer' => array('Guid' => $this->guid_customer),
			'Tax' => $tax_array,
			//'TaxRate' => 0.000000,
			//'XeroTaxCode' => 'NONE',
			'SubTotal' => ($order_data['total'] - $order_data['total_tax']),
			'TaxTotal' => $order_data['total_tax'],
			'Total' => $order_data['total'],
			'Guid' => $guid
		);

		$response = $this->unleashed->post_request($request, $query_params, json_encode($order_array));
		$http_code = $this->unleashed->get_http_response_code( $response );
		$json_response = $this->unleashed->get_response_body($response);

		if ($http_code == 201) {
			//if response is OK, than retrieve data
			//and save it in meta order data
			$this->update_metadata(true,$json_response->OrderNumber);

		}
		else
		{
			$this->update_metadata(false,'Unable to create order in Unleashed. Response code: ' . $http_code);
			error_log('Unable to create order in Unleashed. Response code: ' . $http_code);
			error_log('Response Body ' . print_r(json_encode($json_response),true));
			error_log('Woocommerce Order Data: ' . print_r($order_data,true));
			error_log('Order POST Request: ' . print_r(json_encode($order_array),true));
		}

	}

	/**
	 * Create sales order lines array for Unleashed API.
	 *
	 * @since    1.0.0
	 * @param     array    $order_data    Woocommerce order data
	 * @return		array
	 */
	public function create_sales_order_lines_array($order_data) {

		$lines_array = array();
		$line_number = 1;

		foreach ($order_data['line_items'] as $key => $order_line_item) {


			//get product GUID
			$product = $order_line_item->get_product();
			$product_guid = $product->get_meta('_guid');
			$line = array(
				'LineNumber' => $line_number,
				'LineType' => null,
				'Product' => array('Guid' => $product_guid),
				'OrderQuantity' => $order_line_item->get_quantity(),
				'UnitPrice' => $product->get_price(),
				'DiscountRate' => 0.0000,
				'LineTotal' => round($product->get_price() * $order_line_item->get_quantity(),2),
				'LineTax' => $order_line_item->get_subtotal_tax(),
				'LineTaxCode' => null,
				'Guid' => $this->unleashed->create_guid()
			);

			$lines_array[] = $line;
			$line_number++;

		}



		return $lines_array;
	}

	/**
	* Create sales order lines array for Unleashed API.
	 *
	 * @since    1.0.0
	 * @param     array    $order_data    Woocommerce order data
	 * @return		array
	 */
	public function create_tax_array() {

		$tax_array = array(
			'TaxCode' => $this->unleashed->get_tax_code()
		);

		return $tax_array;
	}


	/**
	* Add the sub level menu page.
	*/
	public function add_unleashed_options_page() {
		add_submenu_page(
		    'options-general.php',
		    'Unleashed Integration Settings',
		    'Unleashed',
		    'manage_options',
		    'wc_unlsh_orders',
		    'WCUnlshOrder_Admin::wc_unlsh_orders_options_page_html'
		);
	}


	public function settings_init() {
	  // Register a new setting for "wc_unlsh_orders" page.
	  register_setting( 'wc_unlsh_orders', 'wc_unlsh_orders_options' );

	  // Register a new section in the "Unleashed Integration Settings" page.
	  add_settings_section(
	      'unlsh_api_credentials_section',
	      __( 'Unleashed API credentials', 'wc_unlsh_orders' ),
				'WCUnlshOrder_Admin::unlsh_api_credentials_section_description',
	      'wc_unlsh_orders'
	  );

	  // Register fields for "API Credentials" section,
	  add_settings_field(
	      'wc_unlsh_orders_unlsh_api_id', // As of WP 4.6 this value is used only internally.
	          __( 'API Id', 'wc_unlsh_orders' ),
	      'WCUnlshOrder_Admin::wc_unlsh_orders_unlsh_api_id',
	      'wc_unlsh_orders',
	      'unlsh_api_credentials_section',
	      array(
	          'label_for'         => 'wc_unlsh_orders_field_unlsh_api_id',
	          'class'             => 'wc_unlsh_orders_row',
	          'wc_unlsh_orders_custom_data' => 'custom',
	      )
	  );

		add_settings_field(
				'wc_unlsh_orders_unlsh_api_key',
						__( 'API Key', 'wc_unlsh_orders' ),
				'WCUnlshOrder_Admin::wc_unlsh_orders_unlsh_api_key',
				'wc_unlsh_orders',
				'unlsh_api_credentials_section',
				array(
						'label_for'         => 'wc_unlsh_orders_field_unlsh_api_key',
						'class'             => 'wc_unlsh_orders_row'
				)
		);

		add_settings_field(
				'wc_unlsh_orders_unlsh_tax_code',
						__( 'Tax Code', 'wc_unlsh_orders' ),
				'WCUnlshOrder_Admin::wc_unlsh_orders_unlsh_tax_code',
				'wc_unlsh_orders',
				'unlsh_api_credentials_section',
				array(
						'label_for'         => 'wc_unlsh_orders_field_unlsh_tax_code',
						'class'             => 'wc_unlsh_orders_row'
				)
		);

	}

	static function unlsh_api_credentials_section_description(){
		echo "Please enter the Unleashed API credentials";
	}

	/**
	* Sub level menu callback function
	*/
	static function wc_unlsh_orders_options_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
				return;
		}

		// show error/update messages
		settings_errors( 'wc_unlsh_orders_messages' );
		?>
		<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form action="options.php" method="post">
						<?php
						// output security fields for the registered setting "wc_unlsh_orders"
						settings_fields( 'wc_unlsh_orders' );
						// output setting sections and their fields
						// (sections are registered for "wporg", each field is registered to a specific section)
						do_settings_sections( 'wc_unlsh_orders' );
						// output save settings button
						submit_button( 'Save Settings' );
						?>
				</form>
		</div>
		<?php
	}


	/**
	* Test API Id render function.
	*
	* WordPress has magic interaction with the following keys: label_for, class.
	* - the "label_for" key value is used for the "for" attribute of the <label>.
	* - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	* Note: you can add custom key value pairs to be used inside your callbacks.
	*
	* @param array $args
	*/
	static function wc_unlsh_orders_unlsh_api_id( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$options = get_option( 'wc_unlsh_orders_options' );

		echo '<input id="wc_unlsh_orders_unlsh_api_id" name="wc_unlsh_orders_options[unlsh_api_id]" size="51" type="text" value="' . $options['unlsh_api_id'] . '"/><br/>';
		echo '<p class="description">' . esc_html_e( 'Paste here the API Id', 'wc_unlsh_orders' ) . '</p>';
	}

	/**
	* Test API Key render function.
	*
	* @param array $args
	*/
	static function wc_unlsh_orders_unlsh_api_key( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$options = get_option( 'wc_unlsh_orders_options' );

		echo '<input id="wc_unlsh_orders_unlsh_api_key" name="wc_unlsh_orders_options[unlsh_api_key]" size="51" type="password" value="' . $options['unlsh_api_key'] . '"/><br/>';
		echo '<p class="description">' . esc_html_e( 'Paste here the API Key', 'wc_unlsh_orders' ) . '</p>';
	}

	/**
	* Tax Code Field render function.
	*
	* @param array $args
	*/
	static function wc_unlsh_orders_unlsh_tax_code( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$options = get_option( 'wc_unlsh_orders_options' );

		echo '<input id="wc_unlsh_orders_unlsh_tax_code" name="wc_unlsh_orders_options[unlsh_tax_code]" size="10" type="text" value="' . $options['unlsh_tax_code'] . '"/><br/>';
		echo '<p class="description">' . esc_html_e( 'Unleashed Tax Code for Sales Orders', 'wc_unlsh_orders' ) . '</p>';
	}



}
