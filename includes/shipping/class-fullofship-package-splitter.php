<?php
/**
 * Package Splitter - Split Multi-Vendor Orders
 *
 * @package FullOfShip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FullOfShip_Package_Splitter {

    /**
     * Split packages by vendor
     *
     * @param array $packages WooCommerce packages
     * @return array Split packages
     */
    public function split_packages_by_vendor( $packages ) {
        $split_packages = array();

        foreach ( $packages as $package_key => $package ) {
            $vendor_groups = $this->group_items_by_vendor( $package );

            if ( empty( $vendor_groups ) ) {
                // No vendor items, keep original package
                $split_packages[] = $package;
                continue;
            }

            // Create separate package for each vendor
            foreach ( $vendor_groups as $vendor_id => $vendor_data ) {
                $new_package = $package;
                $new_package['contents'] = $vendor_data['contents'];
                $new_package['contents_cost'] = $vendor_data['contents_cost'];
                $new_package['vendor_id'] = $vendor_id;

                // Add vendor info for package identification
                if ( function_exists( 'dokan_get_store_info' ) ) {
                    $store_info = dokan_get_store_info( $vendor_id );
                    $new_package['vendor_name'] = ! empty( $store_info['store_name'] ) ? $store_info['store_name'] : get_userdata( $vendor_id )->display_name;
                } else {
                    $new_package['vendor_name'] = get_userdata( $vendor_id )->display_name;
                }

                $split_packages[] = $new_package;
            }
        }

        FullOfShip::log( 'Split ' . count( $packages ) . ' packages into ' . count( $split_packages ) . ' vendor-specific packages' );

        return $split_packages;
    }

    /**
     * Group cart items by vendor
     *
     * @param array $package Package data
     * @return array Vendor groups
     */
    private function group_items_by_vendor( $package ) {
        $vendor_groups = array();

        if ( empty( $package['contents'] ) ) {
            return $vendor_groups;
        }

        foreach ( $package['contents'] as $item_key => $item ) {
            $product_id = $item['product_id'];
            $vendor_id = $this->get_product_vendor( $product_id );

            if ( ! $vendor_id ) {
                FullOfShip::log( "No vendor found for product {$product_id}, skipping", 'warning' );
                continue;
            }

            if ( ! isset( $vendor_groups[ $vendor_id ] ) ) {
                $vendor_groups[ $vendor_id ] = array(
                    'contents'      => array(),
                    'contents_cost' => 0,
                );
            }

            $vendor_groups[ $vendor_id ]['contents'][ $item_key ] = $item;
            $vendor_groups[ $vendor_id ]['contents_cost'] += $item['line_total'];
        }

        return $vendor_groups;
    }

    /**
     * Get vendor for a product
     *
     * @param int $product_id Product ID
     * @return int|false Vendor ID or false
     */
    private function get_product_vendor( $product_id ) {
        // Try Dokan first
        if ( function_exists( 'dokan_get_vendor_by_product' ) ) {
            $vendor_id = dokan_get_vendor_by_product( $product_id );
            if ( $vendor_id ) {
                return absint( $vendor_id );
            }
        }

        // Fallback: check post author (standard WordPress)
        $product = wc_get_product( $product_id );
        if ( $product ) {
            $author_id = $product->get_meta( '_vendor_id' );
            if ( $author_id ) {
                return absint( $author_id );
            }

            // Last resort: post author
            $post = get_post( $product_id );
            if ( $post ) {
                return absint( $post->post_author );
            }
        }

        return false;
    }

    /**
     * Get vendor's shipping origin address
     *
     * @param int $vendor_id Vendor ID
     * @return array Origin address
     */
    public function get_vendor_origin( $vendor_id ) {
        $vendor_id = absint( $vendor_id );

        $origin = array(
            'address' => '',
            'city'    => '',
            'state'   => '',
            'postcode' => '',
            'country' => '',
        );

        // Try to get from Dokan store settings
        if ( function_exists( 'dokan_get_store_info' ) ) {
            $store_info = dokan_get_store_info( $vendor_id );

            if ( ! empty( $store_info['address'] ) ) {
                $origin['address'] = $store_info['address']['street_1'];
                $origin['city']    = $store_info['address']['city'];
                $origin['state']   = $store_info['address']['state'];
                $origin['postcode'] = $store_info['address']['zip'];
                $origin['country'] = $store_info['address']['country'];
            }
        }

        // Fallback to site default if vendor address is incomplete
        if ( empty( $origin['postcode'] ) || empty( $origin['country'] ) ) {
            $origin = array(
                'address'  => WC()->countries->get_base_address(),
                'city'     => WC()->countries->get_base_city(),
                'state'    => WC()->countries->get_base_state(),
                'postcode' => WC()->countries->get_base_postcode(),
                'country'  => WC()->countries->get_base_country(),
            );

            FullOfShip::log( "Using site default origin for vendor {$vendor_id}", 'warning' );
        }

        return $origin;
    }
}
