<?php

/**
 * This is used to define a Contact Modeling Data.
 *
 * @since      1.0.0
 * @package    WCUnleashedOrder
 * @author     Frank Luzón <fluzon@jaibasoft.com>
 */
class WCUnlshContact {

	/**
	 * @access   protected
	 * @var      string    $customer_code    Unleashed Customer Code
	 */
	protected $customer_code;

	/**
	 * @access   protected
	 * @var      string    $email    Contact Email Address
	 */
	protected $email;

	/**
	 * @access   protected
	 * @var      string    $guid   Contact Global Unique Identifier
	 */
	protected $guid;

	/**
	 * Constructor
	 *
	 * @since    1.0.0
	 * @param $wc_order_data Array with WooCommerce Order Data
	 * @param $customer_id Unleashed customer ID
	 * @param $contact_email 
	 */
	public function __construct($wc_order_data, $customer_guid, $contact_email)
	{
		$this->customer_code = 'WC-' . $wc_order_data['customer_id'];
		$this->guid = $customer_guid;
		$this->email = $contact_email;
	}

	/**
	 * Returns Customer Code
	 */
	public function getCustomerCode(){
		return $this->customer_code;
	}

	/**
	 * Returns Contact GUID
	 */
	public function getGUID(){
		return $this->guid;
	}

	/**
	 * Returns Contact Email
	 */
	public function getEmail(){
		return $this->email;
	}

}
