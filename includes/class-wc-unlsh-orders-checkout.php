<?php

/**
 * This is used to add hook for Checkout Page
 *
 * @since      1.0.0
 * @package    WCUnleashedOrders
 * @author     Frank Luzón <fluzon@jaibasoft.com>
 */
class WCUnlshCheckout {

	/**
	 * Display Purchase Order Number in Checkout Page
	 *
	 */
	public function gateway_bacs_custom_fields( $description, $payment_id ){
	    //
	    if( 'bacs' === $payment_id ){
	        ob_start(); // Start buffering

	        echo '<div  class="bacs-fields" style="padding:10px 0;">';

	        woocommerce_form_field( 'purchase_order_number', array(
	            'type'          => 'text',
	            'label'         => __("Purchase Order Number", "woocommerce"),
	            'class'         => array('form-row-wide'),
	            'required'      => true,
	        ), '');

	        echo '<div>';

	        $description .= ob_get_clean(); // Append buffered content
	    }
	    return $description;
	}


	/**
	 * Validate Purchase Order Number in Checkout Page
	 *
	 */
	public function purchase_order_number_checkout_field_validation() {
	if ( $_POST['payment_method'] === 'bacs' && isset($_POST['purchase_order_number']) && empty($_POST['purchase_order_number']) )
	    wc_add_notice( __( 'Please enter the "Purchase Order Number".' ), 'error' );
	}

	/**
	 * Save Purchase Order Number in Order Meta data
	 *
	 */
	public function save_purchase_order_number( $order, $data ) {
	    if( $data['payment_method'] === 'bacs' && isset( $_POST['purchase_order_number'] ) ) {
	        $order->update_meta_data( 'purchase_order_number', sanitize_text_field( $_POST['purchase_order_number'] ) );
	    }
	}

}
