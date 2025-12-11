<?php
/**
 * Plugin Activation Handler
 *
 * @package FullOfShip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FullOfShip_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        // Check dependencies
        if ( ! self::check_dependencies() ) {
            deactivate_plugins( plugin_basename( FULLOFSHIP_PLUGIN_FILE ) );
            wp_die(
                __( 'FullOfShip requires WooCommerce and Dokan to be installed and active.', 'fullofship' ),
                __( 'Plugin Activation Error', 'fullofship' ),
                array( 'back_link' => true )
            );
        }

        // Create database tables
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/database/class-fullofship-db-schema.php';
        FullOfShip_DB_Schema::create_tables();

        // Set default options
        self::set_default_options();

        // Create custom capabilities
        self::add_capabilities();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Store activation timestamp
        update_option( 'fullofship_activated', current_time( 'timestamp' ) );
    }

    /**
     * Check if required plugins are active
     */
    private static function check_dependencies() {
        // Check WooCommerce
        if ( ! class_exists( 'WooCommerce' ) ) {
            return false;
        }

        // Check Dokan
        if ( ! function_exists( 'dokan' ) && ! class_exists( 'WeDevs_Dokan' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            'fullofship_enable_cache'      => 'yes',
            'fullofship_cache_duration'    => '30',
            'fullofship_fallback_rate'     => '10.00',
            'fullofship_require_boxes'     => 'yes',
            'fullofship_debug_mode'        => 'no',
            'fullofship_dimension_unit'    => 'in',
            'fullofship_weight_unit'       => 'lbs',
            'fullofship_ups_enabled'       => 'no',
            'fullofship_fedex_enabled'     => 'no',
            'fullofship_usps_enabled'      => 'no',
            'fullofship_dhl_enabled'       => 'no',
        );

        foreach ( $defaults as $key => $value ) {
            if ( get_option( $key ) === false ) {
                update_option( $key, $value );
            }
        }
    }

    /**
     * Add custom capabilities for vendor access
     */
    private static function add_capabilities() {
        $seller_role = get_role( 'seller' );

        if ( $seller_role ) {
            $seller_role->add_cap( 'manage_shipping_boxes' );
        }

        // Also add to shop_manager and administrator
        $shop_manager = get_role( 'shop_manager' );
        if ( $shop_manager ) {
            $shop_manager->add_cap( 'manage_shipping_boxes' );
        }

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'manage_shipping_boxes' );
        }
    }
}
