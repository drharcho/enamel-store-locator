/**
 * Enamel Store Locator Admin JavaScript
 * Enhanced admin interface functionality
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize admin interface
        initTabs();
        initColorPickers();
        initSliders();
        initPreview();
    });

    /**
     * Initialize tab navigation
     */
    function initTabs() {
        $('.enamel-admin-tab').on('click', function() {
            var targetTab = $(this).data('tab');
            
            // Update active tab
            $('.enamel-admin-tab').removeClass('active');
            $(this).addClass('active');
            
            // Show target tab content
            $('.enamel-tab-content').removeClass('active');
            $('#' + targetTab).addClass('active');
        });
    }

    /**
     * Initialize color pickers
     */
    function initColorPickers() {
        $('.enamel-color-picker').wpColorPicker({
            change: function(event, ui) {
                var color = ui.color.toString();
                $(this).siblings('.enamel-color-preview').css('background-color', color);
                updatePreview();
            },
            clear: function() {
                $(this).siblings('.enamel-color-preview').css('background-color', '#ffffff');
                updatePreview();
            }
        });
    }

    /**
     * Initialize sliders with live value updates
     */
    function initSliders() {
        $('.enamel-slider').on('input', function() {
            var value = $(this).val();
            var unit = $(this).data('unit') || '';
            $(this).siblings('.slider-value').text(value + unit);
            updatePreview();
        });
    }

    /**
     * Initialize live preview functionality
     */
    function initPreview() {
        // Update preview on any form change
        $('.enamel-tab-content input, .enamel-tab-content textarea, .enamel-tab-content select').on('change input', function() {
            updatePreview();
        });
    }

    /**
     * Update live preview
     */
    function updatePreview() {
        var preview = $('.enamel-preview-demo');
        if (preview.length === 0) return;

        // Get current color values
        var primaryColor = $('input[name="enamel_sl_primary_color"]').val() || '#7D55C7';
        var accentColor = $('input[name="enamel_sl_accent_color"]').val() || '#E56B10';
        var backgroundColor = $('input[name="enamel_sl_background_color"]').val() || '#FFFFFF';
        var cardBackground = $('input[name="enamel_sl_card_background"]').val() || '#F8F9FA';
        var primaryText = $('input[name="enamel_sl_primary_text"]').val() || '#231942';
        var secondaryText = $('input[name="enamel_sl_secondary_text"]').val() || '#6B7280';

        // Get text values
        var headerTitle = $('input[name="enamel_sl_header_main_title"]').val() || 'Find a Location';
        var searchPlaceholder = $('input[name="enamel_sl_search_input_placeholder"]').val() || 'Enter your location';
        var searchButtonText = $('input[name="enamel_sl_search_button_text"]').val() || 'Search';

        // Create preview HTML
        var previewHTML = '<div style="background: ' + backgroundColor + '; padding: 20px; border-radius: 8px; width: 100%; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">' +
            '<h2 style="color: ' + primaryColor + '; margin: 0 0 15px 0; font-size: 24px; font-weight: 600;">' + headerTitle + '</h2>' +
            '<div style="background: ' + cardBackground + '; padding: 20px; border-radius: 6px; margin-bottom: 15px;">' +
                '<input type="text" placeholder="' + searchPlaceholder + '" style="width: calc(100% - 120px); padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-right: 10px; color: ' + primaryText + ';">' +
                '<button style="background: ' + accentColor + '; color: white; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 500; cursor: pointer;">' + searchButtonText + '</button>' +
            '</div>' +
            '<div style="background: ' + cardBackground + '; padding: 15px; border-radius: 6px; border-left: 4px solid ' + primaryColor + ';">' +
                '<h4 style="margin: 0 0 8px 0; color: ' + primaryText + '; font-size: 16px;">Sample Location</h4>' +
                '<p style="margin: 0; color: ' + secondaryText + '; font-size: 14px;">123 Main Street, City, State 12345</p>' +
            '</div>' +
        '</div>';

        preview.html(previewHTML);
    }

    /**
     * Form validation and submission
     */
    $('.enamel-admin-form').on('submit', function(e) {
        var isValid = true;
        var errors = [];

        // Validate Google Maps API key
        var apiKey = $('input[name="enamel_sl_google_maps_api_key"]').val();
        if (!apiKey.trim()) {
            errors.push('Google Maps API Key is required');
            isValid = false;
        }

        // Validate coordinates
        var lat = parseFloat($('input[name="enamel_sl_default_lat"]').val());
        var lng = parseFloat($('input[name="enamel_sl_default_lng"]').val());
        
        if (isNaN(lat) || lat < -90 || lat > 90) {
            errors.push('Latitude must be between -90 and 90');
            isValid = false;
        }
        
        if (isNaN(lng) || lng < -180 || lng > 180) {
            errors.push('Longitude must be between -180 and 180');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n• ' + errors.join('\n• '));
        }
    });

})(jQuery);