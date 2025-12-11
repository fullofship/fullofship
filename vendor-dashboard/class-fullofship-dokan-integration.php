<?php
/**
 * Dokan Integration
 *
 * @package FullOfShip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FullOfShip_Dokan_Integration {

    /**
     * Constructor
     */
    public function __construct() {
        // Add menu item to Dokan dashboard
        add_filter( 'dokan_get_dashboard_nav', array( $this, 'add_shipping_boxes_menu' ), 20 );

        // Load shipping boxes template
        add_action( 'dokan_load_custom_template', array( $this, 'load_shipping_boxes_template' ) );

        // Add box selector to product edit page
        add_action( 'dokan_product_edit_after_main', array( $this, 'render_product_box_selector' ), 10, 2 );

        // Save product box assignments
        add_action( 'dokan_product_updated', array( $this, 'save_product_boxes' ), 10, 2 );
        add_action( 'dokan_new_product_added', array( $this, 'save_product_boxes' ), 10, 2 );

        // Validate vendor has boxes before publishing
        add_filter( 'dokan_can_post', array( $this, 'validate_vendor_boxes' ), 10, 2 );

        // Show notice if vendor hasn't configured boxes
        add_action( 'dokan_dashboard_content_before', array( $this, 'show_setup_notice' ) );

        // AJAX handlers
        add_action( 'wp_ajax_fullofship_create_box', array( $this, 'ajax_create_box' ) );
        add_action( 'wp_ajax_fullofship_update_box', array( $this, 'ajax_update_box' ) );
        add_action( 'wp_ajax_fullofship_delete_box', array( $this, 'ajax_delete_box' ) );
        add_action( 'wp_ajax_fullofship_get_boxes', array( $this, 'ajax_get_boxes' ) );

        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_vendor_scripts' ) );
    }

    /**
     * Add shipping boxes menu to Dokan dashboard
     *
     * @param array $urls Dashboard URLs
     * @return array Modified URLs
     */
    public function add_shipping_boxes_menu( $urls ) {
        $urls['shipping-boxes'] = array(
            'title' => __( 'Shipping Boxes', 'fullofship' ),
            'icon'  => '<i class="fas fa-box"></i>',
            'url'   => dokan_get_navigation_url( 'shipping-boxes' ),
            'pos'   => 51,
        );

        return $urls;
    }

    /**
     * Load shipping boxes template
     *
     * @param array $query_vars Query vars
     */
    public function load_shipping_boxes_template( $query_vars ) {
        if ( isset( $query_vars['shipping-boxes'] ) ) {
            if ( ! current_user_can( 'manage_shipping_boxes' ) ) {
                wp_die( esc_html__( 'You do not have permission to access this page.', 'fullofship' ) );
            }

            require_once FULLOFSHIP_PLUGIN_DIR . 'vendor-dashboard/views/box-list.php';
        }
    }

    /**
     * Render product box selector on product edit page
     *
     * @param WP_Post $post Post object
     * @param int     $post_id Post ID
     */
    public function render_product_box_selector( $post, $post_id ) {
        // Verify this is a vendor's product
        if ( ! dokan_is_user_seller( get_current_user_id() ) ) {
            return;
        }

        $vendor_id = dokan_get_vendor_by_product( $post_id );
        if ( ! $vendor_id ) {
            $vendor_id = dokan_get_current_user_id();
        }

        // Get vendor's boxes
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';
        $vendor_boxes = FullOfShip_Vendor_Box_Manager::get_vendor_boxes( $vendor_id );

        if ( empty( $vendor_boxes ) ) {
            ?>
            <div class="dokan-form-group">
                <div class="notice notice-warning">
                    <p>
                        <?php
                        printf(
                            /* translators: %s: link to shipping boxes page */
                            esc_html__( 'Please configure your %s before adding products.', 'fullofship' ),
                            '<a href="' . esc_url( dokan_get_navigation_url( 'shipping-boxes' ) ) . '">' . esc_html__( 'shipping boxes', 'fullofship' ) . '</a>'
                        );
                        ?>
                    </p>
                </div>
            </div>
            <?php
            return;
        }

        // Get assigned boxes for this product
        $assigned_boxes = FullOfShip_Vendor_Box_Manager::get_product_boxes( $post_id );

        require FULLOFSHIP_PLUGIN_DIR . 'vendor-dashboard/views/product-box-selector.php';
    }

    /**
     * Save product box assignments
     *
     * @param int $product_id Product ID
     * @param array $data Product data (optional)
     */
    public function save_product_boxes( $product_id, $data = array() ) {
        // Verify nonce
        if ( ! isset( $_POST['fullofship_product_boxes_nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fullofship_product_boxes_nonce'] ) ), 'fullofship_save_product_boxes' ) ) {
            return;
        }

        // Get selected boxes
        $box_ids = array();
        if ( isset( $_POST['fullofship_product_boxes'] ) && is_array( $_POST['fullofship_product_boxes'] ) ) {
            $box_ids = array_map( 'absint', $_POST['fullofship_product_boxes'] );
        }

        // Save assignments
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';
        FullOfShip_Vendor_Box_Manager::assign_product_to_boxes( $product_id, $box_ids );
    }

    /**
     * Validate vendor has boxes before publishing products
     *
     * @param bool|WP_Error $can_post Whether vendor can post
     * @param int           $post_id Post ID
     * @return bool|WP_Error
     */
    public function validate_vendor_boxes( $can_post, $post_id ) {
        // Only check if requirement is enabled
        if ( get_option( 'fullofship_require_boxes' ) !== 'yes' ) {
            return $can_post;
        }

        // Only check for products
        if ( get_post_type( $post_id ) !== 'product' ) {
            return $can_post;
        }

        $vendor_id = dokan_get_current_user_id();

        // Check if vendor has boxes configured
        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';
        if ( ! FullOfShip_Vendor_Box_Manager::vendor_has_boxes( $vendor_id ) ) {
            return new WP_Error(
                'no_shipping_boxes',
                sprintf(
                    /* translators: %s: link to shipping boxes page */
                    __( 'Please configure at least one %s before adding products.', 'fullofship' ),
                    '<a href="' . esc_url( dokan_get_navigation_url( 'shipping-boxes' ) ) . '" target="_blank">' . __( 'shipping box', 'fullofship' ) . '</a>'
                )
            );
        }

        // Check if product has box assignments
        $assigned_boxes = FullOfShip_Vendor_Box_Manager::get_product_boxes( $post_id );
        if ( empty( $assigned_boxes ) ) {
            return new WP_Error(
                'no_box_assigned',
                __( 'Please assign this product to at least one shipping box.', 'fullofship' )
            );
        }

        return $can_post;
    }

    /**
     * Show setup notice if vendor hasn't configured boxes
     */
    public function show_setup_notice() {
        if ( ! dokan_is_user_seller( get_current_user_id() ) ) {
            return;
        }

        // Don't show on shipping boxes page
        global $wp;
        if ( isset( $wp->query_vars['shipping-boxes'] ) ) {
            return;
        }

        $vendor_id = dokan_get_current_user_id();

        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';
        if ( ! FullOfShip_Vendor_Box_Manager::vendor_has_boxes( $vendor_id ) ) {
            ?>
            <div class="dokan-alert dokan-alert-warning">
                <strong><?php esc_html_e( 'Shipping Setup Required', 'fullofship' ); ?>:</strong>
                <?php
                printf(
                    /* translators: %s: link to shipping boxes page */
                    esc_html__( 'Please configure your %s to enable shipping for your products.', 'fullofship' ),
                    '<a href="' . esc_url( dokan_get_navigation_url( 'shipping-boxes' ) ) . '">' . esc_html__( 'shipping boxes', 'fullofship' ) . '</a>'
                );
                ?>
            </div>
            <?php
        }
    }

    /**
     * AJAX: Create new box
     */
    public function ajax_create_box() {
        check_ajax_referer( 'fullofship_box_actions', 'nonce' );

        if ( ! current_user_can( 'manage_shipping_boxes' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fullofship' ) ) );
        }

        $vendor_id = dokan_get_current_user_id();

        $box_data = array(
            'box_name'       => isset( $_POST['box_name'] ) ? sanitize_text_field( wp_unslash( $_POST['box_name'] ) ) : '',
            'length'         => isset( $_POST['length'] ) ? floatval( $_POST['length'] ) : 0,
            'width'          => isset( $_POST['width'] ) ? floatval( $_POST['width'] ) : 0,
            'height'         => isset( $_POST['height'] ) ? floatval( $_POST['height'] ) : 0,
            'max_weight'     => isset( $_POST['max_weight'] ) ? floatval( $_POST['max_weight'] ) : 0,
            'dimension_unit' => isset( $_POST['dimension_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['dimension_unit'] ) ) : '',
            'weight_unit'    => isset( $_POST['weight_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['weight_unit'] ) ) : '',
        );

        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';
        $box_id = FullOfShip_Vendor_Box_Manager::create_box( $vendor_id, $box_data );

        if ( $box_id ) {
            wp_send_json_success( array(
                'message' => __( 'Box created successfully.', 'fullofship' ),
                'box_id'  => $box_id,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to create box.', 'fullofship' ) ) );
        }
    }

    /**
     * AJAX: Update box
     */
    public function ajax_update_box() {
        check_ajax_referer( 'fullofship_box_actions', 'nonce' );

        if ( ! current_user_can( 'manage_shipping_boxes' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fullofship' ) ) );
        }

        $box_id = isset( $_POST['box_id'] ) ? absint( $_POST['box_id'] ) : 0;
        $vendor_id = dokan_get_current_user_id();

        $box_data = array(
            'box_name'       => isset( $_POST['box_name'] ) ? sanitize_text_field( wp_unslash( $_POST['box_name'] ) ) : '',
            'length'         => isset( $_POST['length'] ) ? floatval( $_POST['length'] ) : 0,
            'width'          => isset( $_POST['width'] ) ? floatval( $_POST['width'] ) : 0,
            'height'         => isset( $_POST['height'] ) ? floatval( $_POST['height'] ) : 0,
            'max_weight'     => isset( $_POST['max_weight'] ) ? floatval( $_POST['max_weight'] ) : 0,
            'dimension_unit' => isset( $_POST['dimension_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['dimension_unit'] ) ) : '',
            'weight_unit'    => isset( $_POST['weight_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['weight_unit'] ) ) : '',
            'is_active'      => isset( $_POST['is_active'] ) ? absint( $_POST['is_active'] ) : 1,
        );

        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';
        $result = FullOfShip_Vendor_Box_Manager::update_box( $box_id, $vendor_id, $box_data );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Box updated successfully.', 'fullofship' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to update box.', 'fullofship' ) ) );
        }
    }

    /**
     * AJAX: Delete box
     */
    public function ajax_delete_box() {
        check_ajax_referer( 'fullofship_box_actions', 'nonce' );

        if ( ! current_user_can( 'manage_shipping_boxes' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fullofship' ) ) );
        }

        $box_id = isset( $_POST['box_id'] ) ? absint( $_POST['box_id'] ) : 0;
        $vendor_id = dokan_get_current_user_id();

        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';

        // Check if box has products
        if ( FullOfShip_Vendor_Box_Manager::box_has_products( $box_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Cannot delete box: it is assigned to products.', 'fullofship' ) ) );
        }

        $result = FullOfShip_Vendor_Box_Manager::delete_box( $box_id, $vendor_id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Box deleted successfully.', 'fullofship' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete box.', 'fullofship' ) ) );
        }
    }

    /**
     * AJAX: Get vendor's boxes
     */
    public function ajax_get_boxes() {
        check_ajax_referer( 'fullofship_box_actions', 'nonce' );

        if ( ! current_user_can( 'manage_shipping_boxes' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'fullofship' ) ) );
        }

        $vendor_id = dokan_get_current_user_id();

        require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';
        $boxes = FullOfShip_Vendor_Box_Manager::get_vendor_boxes( $vendor_id, false );

        wp_send_json_success( array( 'boxes' => $boxes ) );
    }

    /**
     * Enqueue vendor dashboard scripts and styles
     */
    public function enqueue_vendor_scripts() {
        global $wp;

        // Only load on shipping boxes page
        if ( ! isset( $wp->query_vars['shipping-boxes'] ) && ! dokan_is_product_edit_page() ) {
            return;
        }

        // Enqueue JavaScript
        wp_enqueue_script(
            'fullofship-vendor-dashboard',
            FULLOFSHIP_PLUGIN_URL . 'vendor-dashboard/js/vendor-dashboard.js',
            array( 'jquery' ),
            FULLOFSHIP_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'fullofship-vendor-dashboard',
            'fullofshipVendor',
            array(
                'ajaxurl'          => admin_url( 'admin-ajax.php' ),
                'nonce'            => wp_create_nonce( 'fullofship_box_actions' ),
                'dimension_unit'   => get_option( 'fullofship_dimension_unit', 'in' ),
                'weight_unit'      => get_option( 'fullofship_weight_unit', 'lbs' ),
                'i18n'             => array(
                    'confirm_delete' => __( 'Are you sure you want to delete this box?', 'fullofship' ),
                    'error'          => __( 'An error occurred. Please try again.', 'fullofship' ),
                ),
            )
        );

        // Enqueue CSS
        wp_enqueue_style(
            'fullofship-vendor-dashboard',
            FULLOFSHIP_PLUGIN_URL . 'vendor-dashboard/css/vendor-dashboard.css',
            array(),
            FULLOFSHIP_VERSION
        );
    }
}
