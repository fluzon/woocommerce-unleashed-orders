<?php

/**
 * Register all actions and filters for the plugin
 *
 * @since      1.0.0
 *
 * @package    Unleashed
 */

/**
 * Handle all calls to Unleashed API
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    WCUnleashedOrders
 * @author     Frank Luzón <fluzon@jaibasoft.com>
 */
class WCUnlshOrder_API_Handler {

	/**
	 * The variable to set calls to Unleashed Sandbox environment.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      boolean    $sandbox    True if using sandbox account, false otherwise
	 */
	protected $sandbox;

	/**
	 * Save the base url for Unleashed API
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $api_id    api id value
	 */
	protected $base_url;

	/**
	 * The variable set the API ID token
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $api_id    api id value
	 */
	protected $api_id;

	/**
	 * The variable set the API secret token
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $api_secret    api secret token
	 */
	protected $api_secret;

	/**
	 * Tax code to use when create Sales Orders
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $tax_code   Unleashed sales orders tax code
	 */
	protected $tax_code;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    1.0.0
	 */
	public function __construct($enable_sandbox = false, $tax_code) {

		$this->enable_sandbox($enable_sandbox);
		$this->base_url = 'https://api.unleashedsoftware.com/';
		$this->tax_code = $tax_code;
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @since    1.0.0
	 * @param    string               $hook             The name of the WordPress action that is being registered.
	 * @param    object               $component        A reference to the instance of the object on which the action is defined.
	 * @param    string               $callback         The name of the function definition on the $component.
	 * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
	 * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Return the API signature using in every API call
	 *
	 * @since    1.0.0
	 * @param      string    $query_params   query parameter portion of the API call
	 */
	private function get_signature($query_params = '') {
			$query = rawurldecode($query_params);
			return base64_encode(hash_hmac('sha256', $query, $this->api_secret, true));
	}

	/**
	 * Return the remote get response
	 *
	 * @since    1.0.0
	 * @param			string   $end_point method of the API
	 * @param     string   $query_params parameter portion of the API call
	 * @return   array
	 */
	public function call_get_request($end_point, $query_params = '') {
		$request_args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'api-auth-id' => $this->api_id,
						'api-auth-signature' => $this->get_signature($query_params),
						'client-type' => 'jaibasoft',
						'Accept' => 'application/json'

					));

		$request = $this->base_url . $end_point . $query_params;

		return wp_remote_get($request, $request_args);

	}

	/**
	 * Return the remote post response
	 *
	 * @since    1.0.0
	 * @param			string   $end_point method of the API
	 * @param     string   $query_params parameter portion of the API call
	 * @return   array
	 */
	public function post_request($end_point, $body) {
		$request_args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
						'api-auth-id' => $this->api_id,
						'api-auth-signature' => $this->get_signature(''),
						'client-type' => 'jaibasoft',
						'Accept' => 'application/json'

					),
					'body' => $body);

		$request = $this->base_url . $end_point;

		return wp_remote_post($request, $request_args);

	}

	/**
	 * Enable Sandbox execution mode
	 *
	 * @since    1.0.0
	 * @param      string    $query_params   query parameter portion of the API call
	 */
	public function enable_sandbox($sandbox = false) {

			$this->sandbox = $sandbox;
			$options = get_option( 'wc_unlsh_orders_options' );

			if ($sandbox)
			{
				$this->api_id = $options['test_api_id'];
				$this->api_secret = $options['test_api_key'];
			}
			else
			{
				$this->api_id = $options['prod_api_id'];
				$this->api_secret = $options['prod_api_key'];
			}
	}


	/**
	 * Return the HTTP response code
	 *
	 * @since    1.0.0
	 * @param      array    $response   array with API response
	 */
	public function get_http_response_code($response) {
			return wp_remote_retrieve_response_code($response);
	}


	/**
	 * Create GUI
	 *
	 * @since    1.0.0
	 * @param      string    $query_params   query parameter portion of the API call
	 */
	public function create_guid() {

		if (function_exists('com_create_guid')){
        return com_create_guid();
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
    }
	}

	/**
	 * Return body response of the request in JSON
	 *
	 * @since    1.0.0
	 * @param   array    $response   array with API response
	 * @return	object
	 */
	public function get_response_body($response) {

		$body = wp_remote_retrieve_body( $response );
		return json_decode($body);
	}


	/**
	 * Get Unleashed tax code
	 *
	 * @since    1.0.0
	 * @return      string    $tax_code   tax code set in Unleashed
	 */
	public function get_tax_code() {

			return $this->tax_code;

	}

}
