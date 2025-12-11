<?php
/**
 * Database Schema Manager
 *
 * @package FullOfShip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FullOfShip_DB_Schema {

    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';

    /**
     * Create all custom tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_prefix = $wpdb->prefix;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Suppress errors during table creation
        $wpdb->hide_errors();

        // Table 1: Vendor Boxes
        $vendor_boxes_table = $table_prefix . 'fullofship_vendor_boxes';
        $sql_boxes = "CREATE TABLE {$vendor_boxes_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            vendor_id BIGINT(20) UNSIGNED NOT NULL,
            box_name VARCHAR(255) NOT NULL,
            length DECIMAL(10,2) NOT NULL,
            width DECIMAL(10,2) NOT NULL,
            height DECIMAL(10,2) NOT NULL,
            max_weight DECIMAL(10,2) NOT NULL,
            dimension_unit VARCHAR(10) DEFAULT 'in',
            weight_unit VARCHAR(10) DEFAULT 'lbs',
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY vendor_id (vendor_id),
            KEY is_active (is_active)
        ) {$charset_collate};";

        // Table 2: Product-Box Relationships
        $product_boxes_table = $table_prefix . 'fullofship_product_boxes';
        $sql_product_boxes = "CREATE TABLE {$product_boxes_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            box_id BIGINT(20) UNSIGNED NOT NULL,
            priority INT(11) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY box_id (box_id),
            UNIQUE KEY product_box_unique (product_id, box_id)
        ) {$charset_collate};";

        // Table 3: Rate Cache
        $rate_cache_table = $table_prefix . 'fullofship_rate_cache';
        $sql_rate_cache = "CREATE TABLE {$rate_cache_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            cache_key VARCHAR(255) NOT NULL,
            carrier VARCHAR(50) NOT NULL,
            rate_data LONGTEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY cache_key (cache_key),
            KEY expires_at (expires_at),
            KEY carrier (carrier)
        ) {$charset_collate};";

        // Execute table creation
        $result_boxes = dbDelta( $sql_boxes );
        $result_product_boxes = dbDelta( $sql_product_boxes );
        $result_cache = dbDelta( $sql_rate_cache );

        // Log any errors
        if ( $wpdb->last_error ) {
            FullOfShip::log( 'Database table creation error: ' . $wpdb->last_error, 'error' );
        }

        // Store database version only if tables were created successfully
        if ( self::verify_tables_exist() ) {
            update_option( 'fullofship_db_version', self::DB_VERSION );
        }

        // Show errors again
        $wpdb->show_errors();
    }

    /**
     * Verify tables exist
     */
    private static function verify_tables_exist() {
        global $wpdb;

        $table_prefix = $wpdb->prefix;
        $tables = array(
            $table_prefix . 'fullofship_vendor_boxes',
            $table_prefix . 'fullofship_product_boxes',
            $table_prefix . 'fullofship_rate_cache',
        );

        foreach ( $tables as $table ) {
            $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table );
            if ( $wpdb->get_var( $query ) !== $table ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Drop all custom tables (used during deactivation if needed)
     */
    public static function drop_tables() {
        global $wpdb;

        $table_prefix = $wpdb->prefix;

        $wpdb->query( "DROP TABLE IF EXISTS {$table_prefix}fullofship_product_boxes" );
        $wpdb->query( "DROP TABLE IF EXISTS {$table_prefix}fullofship_vendor_boxes" );
        $wpdb->query( "DROP TABLE IF EXISTS {$table_prefix}fullofship_rate_cache" );

        delete_option( 'fullofship_db_version' );
    }

    /**
     * Clean expired cache entries
     */
    public static function clean_expired_cache() {
        global $wpdb;

        $table = $wpdb->prefix . 'fullofship_rate_cache';
        $wpdb->query( "DELETE FROM {$table} WHERE expires_at < NOW()" );
    }

    /**
     * Check if database needs upgrade
     */
    public static function needs_upgrade() {
        $current_version = get_option( 'fullofship_db_version', '0.0.0' );
        return version_compare( $current_version, self::DB_VERSION, '<' );
    }
}
