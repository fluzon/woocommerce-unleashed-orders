<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    WCUnleashedOrders
 */

/**
 * Class to handle data between WooCommerce customer and Unleashed customer.
 *
 * @package    WCUnleashedCustomer
 * @author     Frank Luzón <fluzon@jaibasoft.com>
 */
class WCUnlshCustomer_Admin {

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
	 * Unleashed customer code.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $unlsh_customer_code    Unleashed customer code.
	 */
	private $unlsh_customer_code;



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

	}


	/**
	 * Add Unleashed Customer Code for a logged user after singed into WooCommerce.
	 * It will make an API Call only if there's no customer code for the user.
	 *
	 * @since    1.0.0
	 */
	public function add_unlsh_customer_code($redirect, $user) {
		//check if exist Unleashed customer code for this user

	  $unlsh_customer_code = get_user_meta($user->data->ID,'unlsh_customer_code',true);

	  if (empty($unlsh_customer_code))
	  {
			//go to unleashed to get the customer code
			$this->unlsh_customer_code = $this->get_unlsh_customer_code($user->data->user_email);

			if (!empty($this->unlsh_customer_code))
			{
				update_user_meta($user->data->ID, 'unlsh_customer_code',	$this->unlsh_customer_code);
			}

	  }

	  return $redirect;

	}

	/**
	 * Get Customer Code by Customer's Email address
	 *
	 * @since    	1.0.0
	 * @param     string    $user_email       The email address of the buyer in Woocommerce.
	 * @return 		string 		customer code or empty string if doesn't e
	 */
	protected function get_unlsh_customer_code($user_email) {

		$request = 'Customers?';
		$query_params = 'contactEmail=' . $user_email;

		$response = $this->unleashed->call_get_request($request, $query_params);
		$http_code = $this->unleashed->get_http_response_code( $response );
		$json_response = $this->unleashed->get_response_body( $response );

		if ($http_code == 200)
		{
			//if response is OK, than retrieve data
			return (empty($json_response->Items)) ? '' : $json_response->Items[0]->CustomerCode;
		}

		error_log('Unable to check if customer exists in Unleashed. Response code: ' . $http_code);
		error_log('Response Body ' . print_r(json_encode($json_response),true));

		return '';

	}


}
