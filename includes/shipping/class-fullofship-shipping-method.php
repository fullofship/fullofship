<?php
/**
 * FullOfShip Shipping Method
 *
 * @package FullOfShip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FullOfShip_Shipping_Method extends WC_Shipping_Method {

    /**
     * Constructor
     *
     * @param int $instance_id Instance ID
     */
    public function __construct( $instance_id = 0 ) {
        $this->id                 = 'fullofship';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'FullOfShip Multi-Carrier', 'fullofship' );
        $this->method_description = __( 'Multi-vendor shipping with live carrier rates', 'fullofship' );
        $this->supports           = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );

        $this->init();
    }

    /**
     * Initialize shipping method
     */
    public function init() {
        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->enabled = $this->get_option( 'enabled' );
        $this->title   = $this->get_option( 'title' );

        // Save settings
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Initialize form fields
     */
    public function init_form_fields() {
        $this->instance_form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'fullofship' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable FullOfShip shipping', 'fullofship' ),
                'default' => 'yes',
            ),
            'title' => array(
                'title'       => __( 'Method Title', 'fullofship' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'fullofship' ),
                'default'     => __( 'Shipping', 'fullofship' ),
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Calculate shipping rates
     *
     * @param array $package Package data
     */
    public function calculate_shipping( $package = array() ) {
        if ( empty( $package ) || empty( $package['contents'] ) ) {
            return;
        }

        // Check if this is a vendor package
        if ( ! isset( $package['vendor_id'] ) ) {
            FullOfShip::log( 'Package missing vendor_id, skipping', 'warning' );
            return;
        }

        $vendor_id = absint( $package['vendor_id'] );

        // Validate vendor has boxes configured
        if ( ! $this->vendor_has_boxes( $vendor_id ) ) {
            $this->add_error_rate( __( 'Vendor has not configured shipping boxes', 'fullofship' ) );
            return;
        }

        // Pack items into boxes
        $packed_boxes = $this->pack_items( $package['contents'], $vendor_id );

        if ( is_wp_error( $packed_boxes ) ) {
            $this->add_error_rate( $packed_boxes->get_error_message() );
            return;
        }

        // Get vendor's shipping origin
        $origin = $this->get_vendor_origin( $vendor_id );
        $destination = $package['destination'];

        // Calculate rates (will be implemented in Step 5 with carrier integration)
        // For now, add a placeholder rate
        $this->add_placeholder_rate( $package, $packed_boxes );
    }

    /**
     * Check if vendor has boxes configured
     *
     * @param int $vendor_id Vendor ID
     * @return bool
     */
    private function vendor_has_boxes( $vendor_id ) {
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';
        return FullOfShip_Vendor_Box_Manager::vendor_has_boxes( $vendor_id );
    }

    /**
     * Pack items into boxes
     *
     * @param array $items Cart items
     * @param int   $vendor_id Vendor ID
     * @return array|WP_Error Packed boxes or error
     */
    private function pack_items( $items, $vendor_id ) {
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/shipping/class-fullofship-box-packer.php';
        $packer = new FullOfShip_Box_Packer();
        return $packer->pack_items( $items, $vendor_id );
    }

    /**
     * Get vendor's shipping origin
     *
     * @param int $vendor_id Vendor ID
     * @return array Origin address
     */
    private function get_vendor_origin( $vendor_id ) {
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/shipping/class-fullofship-package-splitter.php';
        $splitter = new FullOfShip_Package_Splitter();
        return $splitter->get_vendor_origin( $vendor_id );
    }

    /**
     * Add error rate with fallback
     *
     * @param string $error_message Error message
     */
    private function add_error_rate( $error_message ) {
        FullOfShip::log( 'Shipping calculation error: ' . $error_message, 'error' );

        // Check if fallback rate is configured
        $fallback_rate = get_option( 'fullofship_fallback_rate', '' );

        if ( ! empty( $fallback_rate ) && is_numeric( $fallback_rate ) ) {
            $this->add_rate( array(
                'id'    => $this->id . '_fallback',
                'label' => __( 'Shipping (Estimated)', 'fullofship' ),
                'cost'  => floatval( $fallback_rate ),
                'meta_data' => array(
                    'fallback' => true,
                    'error'    => $error_message,
                ),
            ) );
        } else {
            // No fallback, show error to customer
            $this->add_rate( array(
                'id'    => $this->id . '_error',
                'label' => __( 'Shipping Not Available', 'fullofship' ),
                'cost'  => 0,
                'meta_data' => array(
                    'error' => $error_message,
                ),
            ) );
        }
    }

    /**
     * Add placeholder rate (temporary until carrier integration)
     *
     * @param array $package Package data
     * @param array $packed_boxes Packed boxes
     */
    private function add_placeholder_rate( $package, $packed_boxes ) {
        // Calculate basic rate based on weight and distance
        $total_weight = 0;
        foreach ( $packed_boxes as $box ) {
            $total_weight += $box['weight'];
        }

        // Simple calculation: $1 per lb + $5 base
        $cost = 5 + ( $total_weight * 1 );

        $vendor_name = isset( $package['vendor_name'] ) ? $package['vendor_name'] : __( 'Vendor', 'fullofship' );

        $this->add_rate( array(
            'id'    => $this->id . '_standard',
            'label' => sprintf(
                /* translators: %s: vendor name */
                __( 'Shipping from %s (Standard)', 'fullofship' ),
                $vendor_name
            ),
            'cost'  => $cost,
            'meta_data' => array(
                'vendor_id'    => $package['vendor_id'],
                'boxes_count'  => count( $packed_boxes ),
                'total_weight' => $total_weight,
                'placeholder'  => true,
            ),
        ) );

        FullOfShip::log( sprintf(
            'Added placeholder rate: $%.2f for %d boxes, total weight: %.2f',
            $cost,
            count( $packed_boxes ),
            $total_weight
        ) );
    }

    /**
     * Check if this method is available
     *
     * @param array $package Package data
     * @return bool
     */
    public function is_available( $package ) {
        $is_available = parent::is_available( $package );

        if ( ! $is_available ) {
            return false;
        }

        // Check if any carriers are enabled
        $carriers_enabled = false;
        $carriers = array( 'ups', 'fedex', 'usps', 'dhl' );

        foreach ( $carriers as $carrier ) {
            if ( get_option( "fullofship_{$carrier}_enabled" ) === 'yes' ) {
                $carriers_enabled = true;
                break;
            }
        }

        if ( ! $carriers_enabled ) {
            FullOfShip::log( 'No carriers enabled in settings', 'warning' );
            return false;
        }

        return true;
    }
}
