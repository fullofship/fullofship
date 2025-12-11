<?php
/**
 * Admin Settings Integration
 *
 * @package FullOfShip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FullOfShip_Admin_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
        add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_settings' ), 10, 3 );
    }

    /**
     * Sanitize sensitive settings
     */
    public function sanitize_settings( $value, $option, $raw_value ) {
        // Sanitize API credentials
        $sensitive_fields = array(
            'fullofship_ups_password',
            'fullofship_fedex_api_secret',
            'fullofship_dhl_api_secret',
        );

        if ( in_array( $option['id'], $sensitive_fields, true ) ) {
            return sanitize_text_field( $raw_value );
        }

        return $value;
    }

    /**
     * Add FullOfShip settings page to WooCommerce
     */
    public function add_settings_page( $settings ) {
        $settings[] = new FullOfShip_Settings_Page();
        return $settings;
    }
}

/**
 * FullOfShip Settings Page Class
 */
class FullOfShip_Settings_Page extends WC_Settings_Page {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id    = 'fullofship';
        $this->label = __( 'FullOfShip', 'fullofship' );

        // Add sanitization filter for sensitive settings
        add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_settings' ), 10, 3 );

        parent::__construct();
    }

    /**
     * Sanitize sensitive settings
     */
    public function sanitize_settings( $value, $option, $raw_value ) {
        // Sanitize API credentials
        $sensitive_fields = array(
            'fullofship_ups_password',
            'fullofship_fedex_api_secret',
            'fullofship_dhl_api_secret',
        );

        if ( in_array( $option['id'], $sensitive_fields, true ) ) {
            return sanitize_text_field( $raw_value );
        }

        return $value;
    }

    /**
     * Get sections
     */
    public function get_sections() {
        $sections = array(
            ''          => __( 'General', 'fullofship' ),
            'carriers'  => __( 'Carriers', 'fullofship' ),
            'advanced'  => __( 'Advanced', 'fullofship' ),
        );

        return apply_filters( 'fullofship_get_sections_' . $this->id, $sections );
    }

    /**
     * Get settings for each section
     */
    public function get_settings( $current_section = '' ) {
        $settings = array();

        if ( '' === $current_section ) {
            $settings = $this->get_general_settings();
        } elseif ( 'carriers' === $current_section ) {
            $settings = $this->get_carrier_settings();
        } elseif ( 'advanced' === $current_section ) {
            $settings = $this->get_advanced_settings();
        }

        return apply_filters( 'fullofship_get_settings_' . $this->id, $settings, $current_section );
    }

    /**
     * General settings
     */
    private function get_general_settings() {
        return array(
            array(
                'title' => __( 'General Settings', 'fullofship' ),
                'type'  => 'title',
                'desc'  => __( 'Configure general FullOfShip settings', 'fullofship' ),
                'id'    => 'fullofship_general_settings',
            ),

            array(
                'title'    => __( 'Require Box Configuration', 'fullofship' ),
                'desc'     => __( 'Prevent vendors from publishing products without box assignments', 'fullofship' ),
                'id'       => 'fullofship_require_boxes',
                'default'  => 'yes',
                'type'     => 'checkbox',
            ),

            array(
                'title'       => __( 'Fallback Shipping Rate', 'fullofship' ),
                'desc'        => __( 'Flat rate to charge if carrier APIs fail (leave blank to show error)', 'fullofship' ),
                'id'          => 'fullofship_fallback_rate',
                'type'        => 'price',
                'default'     => '10.00',
                'placeholder' => '10.00',
                'desc_tip'    => true,
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'fullofship_general_settings',
            ),
        );
    }

    /**
     * Carrier settings
     */
    private function get_carrier_settings() {
        return array(
            // UPS Settings
            array(
                'title' => __( 'UPS Settings', 'fullofship' ),
                'type'  => 'title',
                'id'    => 'fullofship_ups_settings',
            ),

            array(
                'title'   => __( 'Enable UPS', 'fullofship' ),
                'id'      => 'fullofship_ups_enabled',
                'default' => 'no',
                'type'    => 'checkbox',
            ),

            array(
                'title'       => __( 'UPS Access License Number', 'fullofship' ),
                'id'          => 'fullofship_ups_access_key',
                'type'        => 'text',
                'desc_tip'    => __( 'Obtain from UPS Developer Portal', 'fullofship' ),
                'placeholder' => 'Your UPS Access Key',
            ),

            array(
                'title'       => __( 'UPS User ID', 'fullofship' ),
                'id'          => 'fullofship_ups_user_id',
                'type'        => 'text',
                'placeholder' => 'Your UPS User ID',
            ),

            array(
                'title'       => __( 'UPS Password', 'fullofship' ),
                'id'          => 'fullofship_ups_password',
                'type'        => 'password',
                'placeholder' => 'Your UPS Password',
            ),

            array(
                'title'   => __( 'UPS Test Mode', 'fullofship' ),
                'desc'    => __( 'Use UPS test/sandbox environment', 'fullofship' ),
                'id'      => 'fullofship_ups_test_mode',
                'default' => 'no',
                'type'    => 'checkbox',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'fullofship_ups_settings',
            ),

            // FedEx Settings
            array(
                'title' => __( 'FedEx Settings', 'fullofship' ),
                'type'  => 'title',
                'id'    => 'fullofship_fedex_settings',
            ),

            array(
                'title'   => __( 'Enable FedEx', 'fullofship' ),
                'id'      => 'fullofship_fedex_enabled',
                'default' => 'no',
                'type'    => 'checkbox',
            ),

            array(
                'title'       => __( 'FedEx API Key', 'fullofship' ),
                'id'          => 'fullofship_fedex_api_key',
                'type'        => 'text',
                'desc_tip'    => __( 'Obtain from FedEx Developer Resource Center', 'fullofship' ),
                'placeholder' => 'Your FedEx API Key',
            ),

            array(
                'title'       => __( 'FedEx API Secret', 'fullofship' ),
                'id'          => 'fullofship_fedex_api_secret',
                'type'        => 'password',
                'placeholder' => 'Your FedEx API Secret',
            ),

            array(
                'title'       => __( 'FedEx Account Number', 'fullofship' ),
                'id'          => 'fullofship_fedex_account_number',
                'type'        => 'text',
                'placeholder' => 'Your FedEx Account Number',
            ),

            array(
                'title'       => __( 'FedEx Meter Number', 'fullofship' ),
                'id'          => 'fullofship_fedex_meter_number',
                'type'        => 'text',
                'placeholder' => 'Your FedEx Meter Number',
            ),

            array(
                'title'   => __( 'FedEx Test Mode', 'fullofship' ),
                'desc'    => __( 'Use FedEx test/sandbox environment', 'fullofship' ),
                'id'      => 'fullofship_fedex_test_mode',
                'default' => 'no',
                'type'    => 'checkbox',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'fullofship_fedex_settings',
            ),

            // USPS Settings
            array(
                'title' => __( 'USPS Settings', 'fullofship' ),
                'type'  => 'title',
                'id'    => 'fullofship_usps_settings',
            ),

            array(
                'title'   => __( 'Enable USPS', 'fullofship' ),
                'id'      => 'fullofship_usps_enabled',
                'default' => 'no',
                'type'    => 'checkbox',
            ),

            array(
                'title'       => __( 'USPS User ID', 'fullofship' ),
                'id'          => 'fullofship_usps_user_id',
                'type'        => 'text',
                'desc_tip'    => __( 'Obtain from USPS Web Tools', 'fullofship' ),
                'placeholder' => 'Your USPS User ID',
            ),

            array(
                'title'   => __( 'USPS Test Mode', 'fullofship' ),
                'desc'    => __( 'Use USPS test environment', 'fullofship' ),
                'id'      => 'fullofship_usps_test_mode',
                'default' => 'no',
                'type'    => 'checkbox',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'fullofship_usps_settings',
            ),

            // DHL Settings
            array(
                'title' => __( 'DHL Settings', 'fullofship' ),
                'type'  => 'title',
                'id'    => 'fullofship_dhl_settings',
            ),

            array(
                'title'   => __( 'Enable DHL', 'fullofship' ),
                'id'      => 'fullofship_dhl_enabled',
                'default' => 'no',
                'type'    => 'checkbox',
            ),

            array(
                'title'       => __( 'DHL API Key', 'fullofship' ),
                'id'          => 'fullofship_dhl_api_key',
                'type'        => 'text',
                'desc_tip'    => __( 'Obtain from DHL Developer Portal', 'fullofship' ),
                'placeholder' => 'Your DHL API Key',
            ),

            array(
                'title'       => __( 'DHL API Secret', 'fullofship' ),
                'id'          => 'fullofship_dhl_api_secret',
                'type'        => 'password',
                'placeholder' => 'Your DHL API Secret',
            ),

            array(
                'title'       => __( 'DHL Account Number', 'fullofship' ),
                'id'          => 'fullofship_dhl_account_number',
                'type'        => 'text',
                'placeholder' => 'Your DHL Account Number',
            ),

            array(
                'title'   => __( 'DHL Test Mode', 'fullofship' ),
                'desc'    => __( 'Use DHL test/sandbox environment', 'fullofship' ),
                'id'      => 'fullofship_dhl_test_mode',
                'default' => 'no',
                'type'    => 'checkbox',
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'fullofship_dhl_settings',
            ),
        );
    }

    /**
     * Advanced settings
     */
    private function get_advanced_settings() {
        return array(
            array(
                'title' => __( 'Advanced Settings', 'fullofship' ),
                'type'  => 'title',
                'desc'  => __( 'Advanced configuration options', 'fullofship' ),
                'id'    => 'fullofship_advanced_settings',
            ),

            array(
                'title'    => __( 'Enable Rate Caching', 'fullofship' ),
                'desc'     => __( 'Cache carrier rates to improve performance and reduce API calls', 'fullofship' ),
                'id'       => 'fullofship_enable_cache',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'             => __( 'Cache Duration (minutes)', 'fullofship' ),
                'desc'              => __( 'How long to cache shipping rates', 'fullofship' ),
                'id'                => 'fullofship_cache_duration',
                'type'              => 'number',
                'default'           => '30',
                'placeholder'       => '30',
                'custom_attributes' => array(
                    'min'  => '1',
                    'max'  => '1440',
                    'step' => '1',
                ),
                'desc_tip'          => true,
            ),

            array(
                'title'    => __( 'Debug Mode', 'fullofship' ),
                'desc'     => __( 'Log API requests and responses for troubleshooting', 'fullofship' ),
                'id'       => 'fullofship_debug_mode',
                'default'  => 'no',
                'type'     => 'checkbox',
                'desc_tip' => __( 'Logs will be written to WooCommerce > Status > Logs', 'fullofship' ),
            ),

            array(
                'title'   => __( 'Dimension Unit', 'fullofship' ),
                'desc'    => __( 'Default unit for box dimensions', 'fullofship' ),
                'id'      => 'fullofship_dimension_unit',
                'default' => 'in',
                'type'    => 'select',
                'options' => array(
                    'in' => __( 'Inches', 'fullofship' ),
                    'cm' => __( 'Centimeters', 'fullofship' ),
                ),
                'desc_tip' => true,
            ),

            array(
                'title'   => __( 'Weight Unit', 'fullofship' ),
                'desc'    => __( 'Default unit for package weight', 'fullofship' ),
                'id'      => 'fullofship_weight_unit',
                'default' => 'lbs',
                'type'    => 'select',
                'options' => array(
                    'lbs' => __( 'Pounds', 'fullofship' ),
                    'kg'  => __( 'Kilograms', 'fullofship' ),
                ),
                'desc_tip' => true,
            ),

            array(
                'type' => 'sectionend',
                'id'   => 'fullofship_advanced_settings',
            ),
        );
    }

    /**
     * Save settings
     */
    public function save() {
        $settings = $this->get_settings( $this->get_current_section() );
        WC_Admin_Settings::save_fields( $settings );
    }
}
