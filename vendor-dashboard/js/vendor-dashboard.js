/**
 * FullOfShip Vendor Dashboard JavaScript
 *
 * @package FullOfShip
 */

(function($) {
    'use strict';

    const FullOfShipVendorDashboard = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Add new box
            $(document).on('click', '#fullofship-add-box-btn, .fullofship-add-box-trigger', this.openAddBoxModal.bind(this));

            // Edit box
            $(document).on('click', '.fullofship-edit-box', this.openEditBoxModal.bind(this));

            // Delete box
            $(document).on('click', '.fullofship-delete-box', this.deleteBox.bind(this));

            // Close modal
            $(document).on('click', '.fullofship-modal-close, .fullofship-modal-cancel', this.closeModal.bind(this));

            // Submit form
            $(document).on('submit', '#fullofship-box-form', this.submitBoxForm.bind(this));

            // Close modal on outside click
            $(document).on('click', '.fullofship-modal', function(e) {
                if (e.target === this) {
                    FullOfShipVendorDashboard.closeModal();
                }
            });
        },

        /**
         * Open add box modal
         */
        openAddBoxModal: function(e) {
            e.preventDefault();

            $('#fullofship-modal-title').text('Add Shipping Box');
            $('#fullofship-box-form')[0].reset();
            $('#box_id').val('');
            $('#fullofship-status-group').hide();

            // Set default units
            $('#dimension_unit').val(fullofshipVendor.dimension_unit);
            $('#weight_unit').val(fullofshipVendor.weight_unit);

            $('#fullofship-box-modal').fadeIn(300);
        },

        /**
         * Open edit box modal
         */
        openEditBoxModal: function(e) {
            e.preventDefault();

            const boxId = $(e.currentTarget).data('box-id');
            const $row = $('tr[data-box-id="' + boxId + '"]');

            if (!$row.length) {
                return;
            }

            this.showLoading();

            $.ajax({
                url: fullofshipVendor.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fullofship_get_boxes',
                    nonce: fullofshipVendor.nonce
                },
                success: function(response) {
                    this.hideLoading();

                    if (response.success && response.data.boxes) {
                        const box = response.data.boxes.find(b => parseInt(b.id) === parseInt(boxId));

                        if (box) {
                            $('#fullofship-modal-title').text('Edit Shipping Box');
                            $('#box_id').val(box.id);
                            $('#box_name').val(box.box_name);
                            $('#length').val(box.length);
                            $('#width').val(box.width);
                            $('#height').val(box.height);
                            $('#max_weight').val(box.max_weight);
                            $('#dimension_unit').val(box.dimension_unit);
                            $('#weight_unit').val(box.weight_unit);
                            $('#is_active').prop('checked', parseInt(box.is_active) === 1);
                            $('#fullofship-status-group').show();

                            $('#fullofship-box-modal').fadeIn(300);
                        }
                    } else {
                        this.showNotice(fullofshipVendor.i18n.error, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.hideLoading();
                    this.showNotice(fullofshipVendor.i18n.error, 'error');
                }.bind(this)
            });
        },

        /**
         * Submit box form (create or update)
         */
        submitBoxForm: function(e) {
            e.preventDefault();

            const formData = $('#fullofship-box-form').serializeArray();
            const boxId = $('#box_id').val();
            const action = boxId ? 'fullofship_update_box' : 'fullofship_create_box';

            const data = {
                action: action,
                nonce: fullofshipVendor.nonce
            };

            formData.forEach(function(field) {
                data[field.name] = field.value;
            });

            // Handle checkbox
            data.is_active = $('#is_active').is(':checked') ? 1 : 0;

            this.showLoading();

            $.ajax({
                url: fullofshipVendor.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    this.hideLoading();

                    if (response.success) {
                        this.showNotice(response.data.message, 'success');
                        this.closeModal();

                        // Reload page to show updated box list
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        this.showNotice(response.data.message || fullofshipVendor.i18n.error, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.hideLoading();
                    this.showNotice(fullofshipVendor.i18n.error, 'error');
                }.bind(this)
            });
        },

        /**
         * Delete box
         */
        deleteBox: function(e) {
            e.preventDefault();

            if (!confirm(fullofshipVendor.i18n.confirm_delete)) {
                return;
            }

            const boxId = $(e.currentTarget).data('box-id');

            this.showLoading();

            $.ajax({
                url: fullofshipVendor.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fullofship_delete_box',
                    nonce: fullofshipVendor.nonce,
                    box_id: boxId
                },
                success: function(response) {
                    this.hideLoading();

                    if (response.success) {
                        this.showNotice(response.data.message, 'success');

                        // Remove row from table
                        $('tr[data-box-id="' + boxId + '"]').fadeOut(300, function() {
                            $(this).remove();

                            // Check if table is now empty
                            if ($('.fullofship-boxes-list tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        this.showNotice(response.data.message || fullofshipVendor.i18n.error, 'error');
                    }
                }.bind(this),
                error: function() {
                    this.hideLoading();
                    this.showNotice(fullofshipVendor.i18n.error, 'error');
                }.bind(this)
            });
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('#fullofship-box-modal').fadeOut(300);
            $('#fullofship-box-form')[0].reset();
        },

        /**
         * Show loading overlay
         */
        showLoading: function() {
            $('#fullofship-loading').fadeIn(200);
        },

        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            $('#fullofship-loading').fadeOut(200);
        },

        /**
         * Show notice
         */
        showNotice: function(message, type) {
            const alertClass = type === 'success' ? 'dokan-alert-success' : 'dokan-alert-danger';

            const $notice = $('<div>')
                .addClass('dokan-alert ' + alertClass)
                .html('<p>' + message + '</p>')
                .css({
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    zIndex: 999999,
                    minWidth: '300px'
                });

            $('body').append($notice);

            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        FullOfShipVendorDashboard.init();
    });

})(jQuery);
