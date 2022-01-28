<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    WCUnleashedOrders
 */

/**
 * Class to handle data Customer contacts.
 *
 * @package    WCUnleashedCustomer
 * @author     Frank Luzón <fluzon@jaibasoft.com>
 */
class WCUnlshContact_Admin {

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
	 * Add contact to database
	 *
	 * @since    1.0.0
	 * @param    object    $WCUnlshContact       Contact Model.
	 */
	public function add_contact($WCUnlshContact) {

		global $wpdb;

		$contact = $WCUnlshContact;
		$data_params = array('customer_code'=> $contact->getCustomerCode(), 'contact_email'=> $contact->getEmail(), 'customer_guid'=> $contact->getGUID());

		$result = $wpdb->insert('contacts_data', $data_params);

		if ($result === false)
		{
			error_log("Error adding email contact: " . $contact->getEmail() . " into database");
		}
	}


}
