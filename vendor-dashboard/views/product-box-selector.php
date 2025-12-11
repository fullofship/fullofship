<?php
/**
 * Product Box Selector - Dokan Product Edit Page
 *
 * @package FullOfShip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="dokan-form-group">
    <label class="form-label" for="fullofship_product_boxes">
        <?php esc_html_e( 'Shipping Boxes', 'fullofship' ); ?>
        <span class="required"> *</span>
    </label>

    <?php wp_nonce_field( 'fullofship_save_product_boxes', 'fullofship_product_boxes_nonce' ); ?>

    <div class="fullofship-product-boxes">
        <?php if ( ! empty( $vendor_boxes ) ) : ?>
            <p class="help-block">
                <?php esc_html_e( 'Select which shipping boxes this product can fit in. The smallest box will be used for shipping calculations.', 'fullofship' ); ?>
            </p>

            <div class="fullofship-box-checkboxes">
                <?php foreach ( $vendor_boxes as $box ) : ?>
                    <label class="fullofship-box-option">
                        <input
                            type="checkbox"
                            name="fullofship_product_boxes[]"
                            value="<?php echo esc_attr( $box['id'] ); ?>"
                            <?php checked( in_array( $box['id'], $assigned_boxes, true ) ); ?>
                        >
                        <span class="fullofship-box-details">
                            <strong><?php echo esc_html( $box['box_name'] ); ?></strong><br>
                            <small>
                                <?php
                                printf(
                                    /* translators: 1: dimensions, 2: weight */
                                    esc_html__( 'Dimensions: %1$s | Max Weight: %2$s', 'fullofship' ),
                                    sprintf(
                                        '%s × %s × %s %s',
                                        number_format( $box['length'], 2 ),
                                        number_format( $box['width'], 2 ),
                                        number_format( $box['height'], 2 ),
                                        $box['dimension_unit']
                                    ),
                                    sprintf(
                                        '%s %s',
                                        number_format( $box['max_weight'], 2 ),
                                        $box['weight_unit']
                                    )
                                );
                                ?>
                            </small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php if ( get_option( 'fullofship_require_boxes' ) === 'yes' ) : ?>
                <p class="help-block fullofship-notice-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php esc_html_e( 'Note: You must assign at least one box before publishing this product.', 'fullofship' ); ?>
                </p>
            <?php endif; ?>
        <?php else : ?>
            <div class="fullofship-no-boxes-notice">
                <p>
                    <i class="fas fa-info-circle"></i>
                    <?php
                    printf(
                        /* translators: %s: link to shipping boxes page */
                        esc_html__( 'No shipping boxes configured yet. Please %s to enable shipping for your products.', 'fullofship' ),
                        '<a href="' . esc_url( dokan_get_navigation_url( 'shipping-boxes' ) ) . '" target="_blank">' . esc_html__( 'create shipping boxes', 'fullofship' ) . '</a>'
                    );
                    ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>
