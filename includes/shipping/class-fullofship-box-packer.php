<?php
/**
 * Box Packer - Simple Weight-Based Algorithm
 *
 * @package FullOfShip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FullOfShip_Box_Packer {

    /**
     * Pack items into boxes
     *
     * @param array $items Cart items
     * @param int   $vendor_id Vendor ID
     * @return array|WP_Error Packed boxes or error
     */
    public function pack_items( $items, $vendor_id ) {
        $vendor_id = absint( $vendor_id );

        if ( empty( $items ) ) {
            return new WP_Error( 'no_items', __( 'No items to pack', 'fullofship' ) );
        }

        // Load vendor box manager
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';

        // Get vendor's boxes
        $vendor_boxes = FullOfShip_Vendor_Box_Manager::get_vendor_boxes( $vendor_id );

        if ( empty( $vendor_boxes ) ) {
            return new WP_Error( 'no_boxes', __( 'Vendor has no shipping boxes configured', 'fullofship' ) );
        }

        $packed_boxes = array();

        foreach ( $items as $item ) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];

            // Get product weight
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                FullOfShip::log( "Product {$product_id} not found", 'error' );
                continue;
            }

            $product_weight = $this->get_product_weight( $product );

            if ( $product_weight <= 0 ) {
                FullOfShip::log( "Product {$product_id} has no weight defined", 'warning' );
                // Assign minimal weight to prevent division by zero
                $product_weight = 0.1;
            }

            // Get boxes assigned to this product
            $assigned_box_ids = FullOfShip_Vendor_Box_Manager::get_product_boxes( $product_id );

            if ( empty( $assigned_box_ids ) ) {
                return new WP_Error(
                    'no_box_assigned',
                    sprintf(
                        /* translators: %s: product name */
                        __( 'Product "%s" is not assigned to any shipping box', 'fullofship' ),
                        $product->get_name()
                    )
                );
            }

            // Get box details (use first assigned box - smallest by priority)
            $box = $this->get_box_by_id( $assigned_box_ids[0], $vendor_boxes );

            if ( ! $box ) {
                return new WP_Error( 'box_not_found', __( 'Assigned box not found', 'fullofship' ) );
            }

            // Calculate how many items fit in one box based on weight
            $max_weight = floatval( $box['max_weight'] );
            $items_per_box = floor( $max_weight / $product_weight );

            if ( $items_per_box <= 0 ) {
                return new WP_Error(
                    'item_too_heavy',
                    sprintf(
                        /* translators: 1: product name, 2: box name */
                        __( 'Product "%1$s" is too heavy for box "%2$s"', 'fullofship' ),
                        $product->get_name(),
                        $box['box_name']
                    )
                );
            }

            // Calculate number of boxes needed
            $boxes_needed = ceil( $quantity / $items_per_box );

            // Pack into boxes
            $remaining_quantity = $quantity;

            for ( $i = 0; $i < $boxes_needed; $i++ ) {
                $items_in_this_box = min( $items_per_box, $remaining_quantity );
                $box_weight = $items_in_this_box * $product_weight;

                $packed_boxes[] = array(
                    'box_id'     => $box['id'],
                    'box_name'   => $box['box_name'],
                    'length'     => floatval( $box['length'] ),
                    'width'      => floatval( $box['width'] ),
                    'height'     => floatval( $box['height'] ),
                    'weight'     => $box_weight,
                    'dimension_unit' => $box['dimension_unit'],
                    'weight_unit'    => $box['weight_unit'],
                    'items'      => array(
                        $product_id => $items_in_this_box,
                    ),
                );

                $remaining_quantity -= $items_in_this_box;
            }
        }

        if ( empty( $packed_boxes ) ) {
            return new WP_Error( 'packing_failed', __( 'Failed to pack items into boxes', 'fullofship' ) );
        }

        FullOfShip::log( sprintf(
            'Packed %d items for vendor %d into %d boxes',
            count( $items ),
            $vendor_id,
            count( $packed_boxes )
        ) );

        return $packed_boxes;
    }

    /**
     * Get product weight in the configured unit
     *
     * @param WC_Product $product Product object
     * @return float Weight
     */
    private function get_product_weight( $product ) {
        $weight = floatval( $product->get_weight() );

        if ( $weight <= 0 ) {
            return 0;
        }

        // WooCommerce weight unit
        $wc_weight_unit = get_option( 'woocommerce_weight_unit' );
        $target_unit = get_option( 'fullofship_weight_unit', 'lbs' );

        // Convert if necessary
        if ( $wc_weight_unit !== $target_unit ) {
            $weight = $this->convert_weight( $weight, $wc_weight_unit, $target_unit );
        }

        return $weight;
    }

    /**
     * Convert weight between units
     *
     * @param float  $weight Weight value
     * @param string $from Source unit
     * @param string $to Target unit
     * @return float Converted weight
     */
    private function convert_weight( $weight, $from, $to ) {
        $weight = floatval( $weight );

        // Convert to kg first (base unit)
        $kg = $weight;
        switch ( $from ) {
            case 'lbs':
                $kg = $weight * 0.453592;
                break;
            case 'oz':
                $kg = $weight * 0.0283495;
                break;
            case 'g':
                $kg = $weight / 1000;
                break;
        }

        // Convert from kg to target unit
        $result = $kg;
        switch ( $to ) {
            case 'lbs':
                $result = $kg / 0.453592;
                break;
            case 'oz':
                $result = $kg / 0.0283495;
                break;
            case 'g':
                $result = $kg * 1000;
                break;
        }

        return $result;
    }

    /**
     * Get box by ID from vendor boxes array
     *
     * @param int   $box_id Box ID
     * @param array $vendor_boxes Vendor boxes
     * @return array|null Box data or null
     */
    private function get_box_by_id( $box_id, $vendor_boxes ) {
        foreach ( $vendor_boxes as $box ) {
            if ( absint( $box['id'] ) === absint( $box_id ) ) {
                return $box;
            }
        }

        return null;
    }

    /**
     * Consolidate boxes (combine multiple boxes if possible)
     * Future enhancement for optimization
     *
     * @param array $packed_boxes Packed boxes
     * @return array Consolidated boxes
     */
    public function consolidate_boxes( $packed_boxes ) {
        // Phase 2: Implement 3D bin packing optimization
        // For now, return as-is (simple packing)
        return $packed_boxes;
    }
}
