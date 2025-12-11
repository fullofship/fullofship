<?php
/**
 * Plugin Name: FullOfShip
 * Plugin URI: https://github.com/fullofship/fullofship
 * Description: Multi-vendor WooCommerce shipping plugin with live carrier rates and Dokan integration
 * Version: 1.0.0
 * Author: FullOfShip
 * Author URI: https://github.com/fullofship
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
 * Plugin activation hook
 */
function fullofship_activate() {
    require_once FULLOFSHIP_PLUGIN_DIR . 'includes/class-fullofship-activator.php';
    FullOfShip_Activator::activate();
}
register_activation_hook( __FILE__, 'fullofship_activate' );

/**
 * Plugin deactivation hook
 */
function fullofship_deactivate() {
    // Unscheduled events
    $timestamp = wp_next_scheduled( 'fullofship_clean_cache' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'fullofship_clean_cache' );
    }

    // Note: We don't drop tables on deactivation for data preservation
    // Tables will only be dropped if plugin is deleted via WordPress admin
}
register_deactivation_hook( __FILE__, 'fullofship_deactivate' );

/**
 * Initialize the plugin
 */
function fullofship_init() {
    require_once FULLOFSHIP_PLUGIN_DIR . 'includes/class-fullofship.php';
    return FullOfShip::instance();
}

// Start the plugin
fullofship_init();
