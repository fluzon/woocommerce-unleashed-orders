<?php
/**
 * Plugin Name:       WooCommerce Unleashed Orders
 * Description:       Add WooCommerce orders to a Unleashed account via Unleashed API.
 * Version:           1.0
 * Author:            Frank Luzón
 * Author URI:        https://frankluzon.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woocommerce-unleashed
 */

 // If this file is called directly, abort.
 if ( ! defined( 'WPINC' ) ) {
 	die;
 }

 /**
  * Currently plugin version.
  * Start at version 1.0.0 and use SemVer - https://semver.org
  * Rename this for your plugin and update it as you release new versions.
  */
 define( 'WC_UNLSH_ORDERS_VERSION', '1.0.0' );

register_activation_hook( __FILE__, 'WCUnleashedOrders_activate' );
register_deactivation_hook( __FILE__, 'WCUnleashedOrders_deactivate' );


/**
* Activate the plugin.
*/
function WCUnleashedOrders_activate() {
    error_log('Plugin Activado');
}


/**
 * Deactivation hook.
 */
function WCUnleashedOrders_deactivate() {
  error_log('Plugin Desactivado');
}


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wc-unlsh-orders.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wc_unlsh_orders() {

	$plugin = new WCUnlshOrder(true,'G.S.T.');
	$plugin->run();

}
run_wc_unlsh_orders();
