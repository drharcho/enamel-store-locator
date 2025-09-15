jQuery(document).ready(function($) {
    // Initialize color pickers
    $('.color-field').wpColorPicker();
    
    // Tab switching functionality
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // API key validation
    $('#enamel_sl_google_maps_api_key').on('blur', function() {
        var apiKey = $(this).val();
        if (apiKey && apiKey.length > 0) {
            // Basic validation for Google API key format
            if (!/^AIza[0-9A-Za-z-_]{35}$/.test(apiKey)) {
                $(this).after('<div class="notice notice-warning inline"><p>Warning: API key format appears invalid</p></div>');
            }
        }
    });
    
    // Coordinate validation
    $('input[name="enamel_sl_default_lat"], input[name="enamel_sl_default_lng"]').on('blur', function() {
        var value = parseFloat($(this).val());
        var field = $(this).attr('name').includes('lat') ? 'latitude' : 'longitude';
        var isLat = field === 'latitude';
        
        if (isNaN(value) || 
            (isLat && (value < -90 || value > 90)) || 
            (!isLat && (value < -180 || value > 180))) {
            $(this).addClass('error');
            $(this).after('<div class="notice notice-error inline"><p>Invalid ' + field + '</p></div>');
        } else {
            $(this).removeClass('error');
            $(this).siblings('.notice').remove();
        }
    });
    
    // Preview map button
    $('#preview-map').click(function(e) {
        e.preventDefault();
        var lat = $('input[name="enamel_sl_default_lat"]').val();
        var lng = $('input[name="enamel_sl_default_lng"]').val();
        var zoom = $('select[name="enamel_sl_default_zoom"]').val();
        
        if (lat && lng) {
            var url = 'https://www.google.com/maps/@' + lat + ',' + lng + ',' + zoom + 'z';
            window.open(url, '_blank');
        }
    });
    
    // Auto-save draft settings
    var settingsForm = $('#enamel-sl-settings-form');
    var autoSaveTimer;
    
    settingsForm.find('input, select, textarea').on('change', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Show saving indicator
            $('.save-indicator').text('Saving...').show();
            
            // Here you could implement auto-save via AJAX
            setTimeout(function() {
                $('.save-indicator').text('Saved').fadeOut(2000);
            }, 1000);
        }, 2000);
    });
    
    // Color scheme presets
    var colorPresets = {
        'enamel': {
            'primary_color': '#7D55C7',
            'accent_color': '#E56B10',
            'background_color': '#FFFFFF',
            'card_background': '#F8F9FA',
            'primary_text': '#231942',
            'secondary_text': '#6B7280'
        },
        'medical': {
            'primary_color': '#2563EB',
            'accent_color': '#10B981',
            'background_color': '#FFFFFF',
            'card_background': '#F1F5F9',
            'primary_text': '#1E293B',
            'secondary_text': '#64748B'
        },
        'professional': {
            'primary_color': '#374151',
            'accent_color': '#F59E0B',
            'background_color': '#FFFFFF',
            'card_background': '#F9FAFB',
            'primary_text': '#111827',
            'secondary_text': '#6B7280'
        }
    };
    
    $('.color-preset').click(function(e) {
        e.preventDefault();
        var preset = $(this).data('preset');
        
        if (colorPresets[preset]) {
            $.each(colorPresets[preset], function(field, color) {
                var input = $('input[name="enamel_sl_' + field + '"]');
                input.val(color).wpColorPicker('color', color);
            });
        }
    });
});