<?php
/**
 * Vendor Dashboard - Shipping Boxes List
 *
 * @package FullOfShip
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once FULLOFSHIP_PLUGIN_DIR . 'includes/vendor/class-fullofship-vendor-box-manager.php';

$vendor_id = dokan_get_current_user_id();
$boxes = FullOfShip_Vendor_Box_Manager::get_vendor_boxes( $vendor_id, false );
$dimension_unit = get_option( 'fullofship_dimension_unit', 'in' );
$weight_unit = get_option( 'fullofship_weight_unit', 'lbs' );
?>

<div class="fullofship-boxes-wrapper">
    <header class="fullofship-boxes-header">
        <h1><?php esc_html_e( 'Shipping Boxes', 'fullofship' ); ?></h1>
        <button type="button" class="dokan-btn dokan-btn-theme" id="fullofship-add-box-btn">
            <i class="fas fa-plus"></i> <?php esc_html_e( 'Add New Box', 'fullofship' ); ?>
        </button>
    </header>

    <?php if ( empty( $boxes ) ) : ?>
        <div class="fullofship-empty-state">
            <div class="fullofship-empty-icon">
                <i class="fas fa-box-open fa-3x"></i>
            </div>
            <h2><?php esc_html_e( 'No Shipping Boxes Yet', 'fullofship' ); ?></h2>
            <p><?php esc_html_e( 'Create shipping boxes to define how your products will be packaged and shipped.', 'fullofship' ); ?></p>
            <button type="button" class="dokan-btn dokan-btn-theme dokan-btn-lg fullofship-add-box-trigger">
                <i class="fas fa-plus"></i> <?php esc_html_e( 'Add Your First Box', 'fullofship' ); ?>
            </button>
        </div>
    <?php else : ?>
        <div class="fullofship-boxes-list">
            <table class="dokan-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Box Name', 'fullofship' ); ?></th>
                        <th><?php esc_html_e( 'Dimensions', 'fullofship' ); ?></th>
                        <th><?php esc_html_e( 'Max Weight', 'fullofship' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'fullofship' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'fullofship' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $boxes as $box ) : ?>
                        <tr data-box-id="<?php echo esc_attr( $box['id'] ); ?>">
                            <td>
                                <strong><?php echo esc_html( $box['box_name'] ); ?></strong>
                            </td>
                            <td>
                                <?php
                                printf(
                                    /* translators: 1: length, 2: width, 3: height, 4: unit */
                                    esc_html__( '%1$s × %2$s × %3$s %4$s', 'fullofship' ),
                                    esc_html( number_format( $box['length'], 2 ) ),
                                    esc_html( number_format( $box['width'], 2 ) ),
                                    esc_html( number_format( $box['height'], 2 ) ),
                                    esc_html( $box['dimension_unit'] )
                                );
                                ?>
                            </td>
                            <td>
                                <?php
                                printf(
                                    /* translators: 1: weight, 2: unit */
                                    esc_html__( '%1$s %2$s', 'fullofship' ),
                                    esc_html( number_format( $box['max_weight'], 2 ) ),
                                    esc_html( $box['weight_unit'] )
                                );
                                ?>
                            </td>
                            <td>
                                <?php if ( $box['is_active'] ) : ?>
                                    <span class="fullofship-status fullofship-status-active">
                                        <?php esc_html_e( 'Active', 'fullofship' ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="fullofship-status fullofship-status-inactive">
                                        <?php esc_html_e( 'Inactive', 'fullofship' ); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="fullofship-actions">
                                <button type="button" class="dokan-btn dokan-btn-sm fullofship-edit-box" data-box-id="<?php echo esc_attr( $box['id'] ); ?>">
                                    <i class="fas fa-edit"></i> <?php esc_html_e( 'Edit', 'fullofship' ); ?>
                                </button>
                                <button type="button" class="dokan-btn dokan-btn-sm dokan-btn-danger fullofship-delete-box" data-box-id="<?php echo esc_attr( $box['id'] ); ?>">
                                    <i class="fas fa-trash"></i> <?php esc_html_e( 'Delete', 'fullofship' ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Box Form Modal -->
    <div id="fullofship-box-modal" class="fullofship-modal" style="display: none;">
        <div class="fullofship-modal-content">
            <span class="fullofship-modal-close">&times;</span>
            <h2 id="fullofship-modal-title"><?php esc_html_e( 'Add Shipping Box', 'fullofship' ); ?></h2>

            <form id="fullofship-box-form">
                <input type="hidden" id="box_id" name="box_id" value="">

                <div class="dokan-form-group">
                    <label for="box_name"><?php esc_html_e( 'Box Name', 'fullofship' ); ?> <span class="required">*</span></label>
                    <input type="text" id="box_name" name="box_name" class="dokan-form-control" required placeholder="<?php esc_attr_e( 'e.g., Small Box, Shoe Box', 'fullofship' ); ?>">
                </div>

                <div class="dokan-form-group">
                    <label><?php esc_html_e( 'Dimensions', 'fullofship' ); ?> <span class="required">*</span></label>
                    <div class="fullofship-dimensions-row">
                        <input type="number" id="length" name="length" class="dokan-form-control" step="0.01" min="0.01" required placeholder="<?php esc_attr_e( 'Length', 'fullofship' ); ?>">
                        <span class="fullofship-separator">×</span>
                        <input type="number" id="width" name="width" class="dokan-form-control" step="0.01" min="0.01" required placeholder="<?php esc_attr_e( 'Width', 'fullofship' ); ?>">
                        <span class="fullofship-separator">×</span>
                        <input type="number" id="height" name="height" class="dokan-form-control" step="0.01" min="0.01" required placeholder="<?php esc_attr_e( 'Height', 'fullofship' ); ?>">
                        <select id="dimension_unit" name="dimension_unit" class="dokan-form-control">
                            <option value="in" <?php selected( $dimension_unit, 'in' ); ?>><?php esc_html_e( 'in', 'fullofship' ); ?></option>
                            <option value="cm" <?php selected( $dimension_unit, 'cm' ); ?>><?php esc_html_e( 'cm', 'fullofship' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="dokan-form-group">
                    <label for="max_weight"><?php esc_html_e( 'Maximum Weight', 'fullofship' ); ?> <span class="required">*</span></label>
                    <div class="fullofship-weight-row">
                        <input type="number" id="max_weight" name="max_weight" class="dokan-form-control" step="0.01" min="0.01" required>
                        <select id="weight_unit" name="weight_unit" class="dokan-form-control">
                            <option value="lbs" <?php selected( $weight_unit, 'lbs' ); ?>><?php esc_html_e( 'lbs', 'fullofship' ); ?></option>
                            <option value="kg" <?php selected( $weight_unit, 'kg' ); ?>><?php esc_html_e( 'kg', 'fullofship' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="dokan-form-group" id="fullofship-status-group" style="display: none;">
                    <label>
                        <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                        <?php esc_html_e( 'Active', 'fullofship' ); ?>
                    </label>
                </div>

                <div class="fullofship-modal-footer">
                    <button type="button" class="dokan-btn fullofship-modal-cancel"><?php esc_html_e( 'Cancel', 'fullofship' ); ?></button>
                    <button type="submit" class="dokan-btn dokan-btn-theme"><?php esc_html_e( 'Save Box', 'fullofship' ); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="fullofship-loading" class="fullofship-loading" style="display: none;">
        <div class="fullofship-spinner"></div>
    </div>
</div>
