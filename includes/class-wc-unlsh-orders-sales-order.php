<?php

/**
 * This is used to define a Sales Order Modeling Data.
 *
 * @since      1.0.0
 * @package    WCUnleashedOrders
 * @author     Frank Luzón <fluzon@jaibasoft.com>
 */
class WCUnlshSalesOrder {

	/**
	 * @access   protected
	 * @var      array    $wc_order_data    WC Order data.
	 */
	protected $wc_order_data;

	/**
	 * @access   protected
	 * @var      array    $wc_countries    WooCommerce Countries Class Object.
	 */
	protected $wc_countries;

	/**
	 * @access   protected
	 * @var      string    $guid   Sales Order Global Unique Identifier
	 */
	protected $guid;

	/**
	 * Constructor
	 *
	 * @since    1.0.0
	 * @param $wc_order_data Array with WooCommerce Order Data
	 * @param $wc_countries Object WooCommerce Countries Class Object
	 */
	public function __construct($wc_order_data, $wc_countries)
	{
		$this->wc_order_data = $wc_order_data;
		$this->guid = $this->create_guid();
		$this->wc_countries = $wc_countries;
	}

	/**
	 * Returns Delivery Post Code
	 */
	public function getDeliveryPostCode(){
		return !empty($this->wc_order_data['shipping']['postcode']) ? $this->wc_order_data['shipping']['postcode'] : $this->wc_order_data['billing']['postcode'];
	}

	/**
	 * Returns Delivery Country Name
	 */
	public function getDeliveryCountry(){
		$country_id = !empty($this->wc_order_data['shipping']['country']) ? $this->wc_order_data['shipping']['country'] : $this->wc_order_data['billing']['country'];

		error_log(print_r($this->wc_countries->countries,true));
		return $this->wc_countries->countries[ $country_id ];
	}

	/**
	 * Returns Delivery Region
	 */
	public function getDeliveryRegion(){
		$country_id = !empty($this->wc_order_data['shipping']['country']) ? $this->wc_order_data['shipping']['country'] : $this->wc_order_data['billing']['country'];
		$state_id = !empty($this->wc_order_data['shipping']['state']) ? $this->wc_order_data['shipping']['state'] : $this->wc_order_data['billing']['state'];
		$states = $this->wc_countries->get_states( $country_id );
		$state_name  = ! empty( $states[ $state_id ] ) ? $states[ $state_id ] : '';

		return $state_name;
	}

	/**
	 * Returns Delivery Street Address
	 */
	public function getDeliveryStreetAddress(){
		return !empty($this->wc_order_data['shipping']['address_1']) ? $this->wc_order_data['shipping']['address_1'] : $this->wc_order_data['billing']['address_1'];
	}

	/**
	 * Returns Delivery City
	 */
	public function getDeliveryCity(){
		return '';
	}

	/**
	 * Returns Delivery Suburb
	 */
	public function getDeliverySuburb(){
		return !empty($this->wc_order_data['shipping']['city']) ? $this->wc_order_data['shipping']['city'] : $this->wc_order_data['billing']['city'];
	}

	/**
	 * Returns Sub Total
	 */
	public function getSubTotal(){
		return ($this->wc_order_data['total'] - $this->wc_order_data['total_tax']);
	}

	/**
	 * Returns Tax Total
	 */
	public function getTaxTotal(){
		return $this->wc_order_data['total_tax'];
	}

	/**
	 * Returns Sales Order Customer data
	 */
	public function getCustomer($guid_customer){
		return array('Guid' => $guid_customer);
	}

	/**
	 * Returns Sales Order Tax data
	 */
	public function getTax($tax_code){
		return array('TaxCode' => $tax_code);
	}

	/**
	 * Returns Sales Order Initial status
	 */
	public function getInitStatus(){
		return 'Placed';
	}

	/**
	 * Returns Sales Order GUID
	 */
	public function getGUID(){
		return $this->guid;
	}

	/**
	 * Returns Sales Order Total
	 */
	public function getTotal(){
		return $this->wc_order_data['total'];
	}

	/**
	 * Create sales order lines array for Unleashed API.
	 *
	 * @since    1.0.0
	 * @return		array
	 */
	public function getLines() {

		$lines_array = array();
		$line_number = 1;

		foreach ($this->wc_order_data['line_items'] as $key => $order_line_item) {


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
				'Guid' => $this->create_guid()
			);

			$lines_array[] = $line;
			$line_number++;

		}

		return $lines_array;
	}

	/**
	 * Returns Customer Reference
	 */
	public function getCustomerRef(){
		$purchase_order_number = get_post_meta($this->wc_order_data['id'],'purchase_order_number',true);

		return !empty($purchase_order_number) ? 'PO-' . $purchase_order_number : '';
	}


	/**
	 * Create a Global Unique Identifier
	 *
	 * @since    1.0.0
	 */
	private function create_guid() {

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

}
