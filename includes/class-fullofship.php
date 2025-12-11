<?php
/**
 * Main Plugin Class
 *
 * @package FullOfShip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FullOfShip {

    /**
     * Plugin instance
     */
    protected static $instance = null;

    /**
     * Get plugin instance
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        if ( ! defined( 'FULLOFSHIP_PLUGIN_FILE' ) ) {
            define( 'FULLOFSHIP_PLUGIN_FILE', FULLOFSHIP_PLUGIN_DIR . 'fullofship.php' );
        }
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load Composer autoloader
        if ( file_exists( FULLOFSHIP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
            require_once FULLOFSHIP_PLUGIN_DIR . 'vendor/autoload.php';
        }

        // Database schema
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/database/class-fullofship-db-schema.php';

        // Check for database upgrades (only in admin)
        if ( is_admin() && FullOfShip_DB_Schema::needs_upgrade() ) {
            FullOfShip_DB_Schema::create_tables();
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Plugin initialization
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 20 );

        // Register admin settings
        add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );

        // Register shipping method
        add_filter( 'woocommerce_shipping_methods', array( $this, 'register_shipping_method' ) );

        // Split packages by vendor
        add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'split_packages_by_vendor' ) );

        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ) );

        // Clean expired cache daily
        if ( ! wp_next_scheduled( 'fullofship_clean_cache' ) ) {
            wp_schedule_event( time(), 'daily', 'fullofship_clean_cache' );
        }
        add_action( 'fullofship_clean_cache', array( $this, 'clean_cache' ) );
    }

    /**
     * Actions on plugins_loaded
     */
    public function on_plugins_loaded() {
        // Check dependencies
        if ( ! $this->check_dependencies() ) {
            add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
            return;
        }

        // Load text domain
        load_plugin_textdomain( 'fullofship', false, dirname( plugin_basename( FULLOFSHIP_PLUGIN_FILE ) ) . '/languages' );

        // Initialize components
        $this->init_components();
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Load Dokan integration
        if ( function_exists( 'dokan' ) || class_exists( 'WeDevs_Dokan' ) ) {
            require_once FULLOFSHIP_PLUGIN_DIR . 'vendor-dashboard/class-fullofship-dokan-integration.php';
            new FullOfShip_Dokan_Integration();
        }

        // Admin settings and shipping method are registered via filters
    }

    /**
     * Add FullOfShip settings page to WooCommerce
     */
    public function add_settings_page( $settings ) {
        if ( ! class_exists( 'WC_Settings_Page' ) ) {
            return $settings;
        }

        require_once FULLOFSHIP_PLUGIN_DIR . 'admin/class-fullofship-admin-settings.php';
        $settings[] = new FullOfShip_Settings_Page();
        return $settings;
    }

    /**
     * Check plugin dependencies
     */
    private function check_dependencies() {
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
     * Show dependency notice
     */
    public function dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e( 'FullOfShip', 'fullofship' ); ?>:</strong>
                <?php esc_html_e( 'This plugin requires WooCommerce and Dokan to be installed and active.', 'fullofship' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Register FullOfShip shipping method
     */
    public function register_shipping_method( $methods ) {
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/shipping/class-fullofship-shipping-method.php';
        $methods['fullofship'] = 'FullOfShip_Shipping_Method';
        return $methods;
    }

    /**
     * Split packages by vendor
     *
     * @param array $packages WooCommerce packages
     * @return array Split packages
     */
    public function split_packages_by_vendor( $packages ) {
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/shipping/class-fullofship-package-splitter.php';
        $splitter = new FullOfShip_Package_Splitter();
        return $splitter->split_packages_by_vendor( $packages );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts( $hook ) {
        // Admin CSS and JS will be loaded in later steps
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_enqueue_scripts() {
        // Frontend CSS and JS will be loaded in later steps
    }

    /**
     * Clean expired cache entries
     */
    public function clean_cache() {
        FullOfShip_DB_Schema::clean_expired_cache();
    }

    /**
     * Get logger instance
     */
    public static function log( $message, $level = 'info' ) {
        if ( get_option( 'fullofship_debug_mode' ) === 'yes' && function_exists( 'wc_get_logger' ) ) {
            $logger = wc_get_logger();
            $logger->log( $level, $message, array( 'source' => 'fullofship' ) );
        }
    }
}
