<?php
/**
 * Plugin Name: FullOfShip
 * Plugin URI: https://github.com/yourusername/fullofship
 * Description: A WooCommerce shipping plugin
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://github.com/yourusername
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: fullofship
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'FULLOFSHIP_VERSION', '1.0.0' );
define( 'FULLOFSHIP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FULLOFSHIP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if WooCommerce is active
 */
if ( ! function_exists( 'is_plugin_active' ) ) {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && ! function_exists( 'WC' ) ) {
    add_action( 'admin_notices', 'fullofship_woocommerce_missing_notice' );
    return;
}

function fullofship_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e( 'FullOfShip requires WooCommerce to be installed and active.', 'fullofship' ); ?></p>
    </div>
    <?php
}

/**
 * Initialize the plugin
 */
function fullofship_init() {
    // Plugin initialization code here
}
add_action( 'plugins_loaded', 'fullofship_init' );
