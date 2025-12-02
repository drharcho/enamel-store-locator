<?php
/**
 * Admin page callbacks for Enamel Store Locator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin page callbacks
 */
class EnamelStoreLocatorAdmin {
    
    /**
     * Dashboard page
     */
    public static function dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Enamel Store Locator', 'enamel-store-locator'); ?></h1>
            
            <div class="enamel-sl-dashboard">
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Quick Setup', 'enamel-store-locator'); ?></h2>
                    <div class="inside">
                        <?php
                        $api_key = get_option('enamel_sl_google_maps_api_key');
                        $has_locations = wp_count_posts('clinic_location')->publish > 0;
                        ?>
                        
                        <div class="enamel-sl-setup-checklist">
                            <h3><?php _e('Setup Checklist', 'enamel-store-locator'); ?></h3>
                            
                            <div class="setup-item <?php echo $api_key ? 'completed' : 'pending'; ?>">
                                <span class="dashicons <?php echo $api_key ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                                <?php _e('Google Maps API Key', 'enamel-store-locator'); ?>
                                <?php if (!$api_key): ?>
                                    <a href="<?php echo admin_url('admin.php?page=enamel-store-locator-settings'); ?>" class="button button-small">
                                        <?php _e('Add API Key', 'enamel-store-locator'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="setup-item <?php echo $has_locations ? 'completed' : 'pending'; ?>">
                                <span class="dashicons <?php echo $has_locations ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                                <?php _e('Add Clinic Locations', 'enamel-store-locator'); ?>
                                <?php if (!$has_locations): ?>
                                    <a href="<?php echo admin_url('post-new.php?post_type=clinic_location'); ?>" class="button button-small">
                                        <?php _e('Add Location', 'enamel-store-locator'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="enamel-sl-shortcode-info">
                            <h3><?php _e('Using the Store Locator', 'enamel-store-locator'); ?></h3>
                            <p><?php _e('Add the store locator to any page or post using this shortcode:', 'enamel-store-locator'); ?></p>
                            <code>[enamel_store_locator]</code>
                            
                            <h4><?php _e('Shortcode Options:', 'enamel-store-locator'); ?></h4>
                            <ul>
                                <li><code>[enamel_store_locator center_lat="30.3072" center_lng="-97.7560"]</code> - <?php _e('Custom map center', 'enamel-store-locator'); ?></li>
                                <li><code>[enamel_store_locator zoom="12"]</code> - <?php _e('Custom zoom level', 'enamel-store-locator'); ?></li>
                                <li><code>[enamel_store_locator height="600px"]</code> - <?php _e('Custom height', 'enamel-store-locator'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="enamel-sl-stats">
                            <h3><?php _e('Statistics', 'enamel-store-locator'); ?></h3>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo wp_count_posts('clinic_location')->publish; ?></span>
                                    <span class="stat-label"><?php _e('Active Locations', 'enamel-store-locator'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $api_key ? '✓' : '✗'; ?></span>
                                    <span class="stat-label"><?php _e('Maps API', 'enamel-store-locator'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .enamel-sl-setup-checklist {
            margin-bottom: 20px;
        }
        .setup-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .setup-item.completed {
            border-color: #00a32a;
            background-color: #f7fcf0;
        }
        .setup-item .dashicons {
            margin-right: 10px;
            width: 20px;
            height: 20px;
            font-size: 20px;
        }
        .setup-item.completed .dashicons {
            color: #00a32a;
        }
        .setup-item.pending .dashicons {
            color: #dba617;
        }
        .enamel-sl-shortcode-info {
            background: #f1f1f1;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .enamel-sl-shortcode-info code {
            background: #fff;
            padding: 3px 6px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .stat-number {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #7D55C7;
        }
        .stat-label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Settings page
     */
    public static function settings_page() {
        // Enqueue admin assets
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('enamel-admin-styles', ENAMEL_SL_PLUGIN_URL . 'admin/assets/admin-styles.css', array(), ENAMEL_SL_VERSION);
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('enamel-admin-scripts', ENAMEL_SL_PLUGIN_URL . 'admin/assets/admin-scripts.js', array('jquery', 'wp-color-picker'), ENAMEL_SL_VERSION, true);
        ?>
        <div class="wrap">
            <div class="enamel-admin-container">
                <div class="enamel-admin-header">
                    <h1><?php _e('Store Locator Settings', 'enamel-store-locator'); ?></h1>
                    <p><?php _e('Customize your store locator appearance, content, and functionality', 'enamel-store-locator'); ?></p>
                </div>
                
                <div class="enamel-admin-tabs">
                    <button type="button" class="enamel-admin-tab active" data-tab="content-tab">
                        <?php _e('Content & Text', 'enamel-store-locator'); ?>
                    </button>
                    <button type="button" class="enamel-admin-tab" data-tab="colors-tab">
                        <?php _e('Colors & Branding', 'enamel-store-locator'); ?>
                    </button>
                    <button type="button" class="enamel-admin-tab" data-tab="general-tab">
                        <?php _e('General Settings', 'enamel-store-locator'); ?>
                    </button>
                </div>
                
                <form method="post" action="options.php" class="enamel-admin-form">
                    <?php settings_fields('enamel_sl_settings'); ?>
                    
                    <!-- Content & Text Tab -->
                    <div id="content-tab" class="enamel-tab-content active">
                        <?php self::render_content_settings(); ?>
                    </div>
                    
                    <!-- Colors & Branding Tab -->
                    <div id="colors-tab" class="enamel-tab-content">
                        <?php self::render_color_settings(); ?>
                    </div>
                    
                    <!-- General Settings Tab -->
                    <div id="general-tab" class="enamel-tab-content">
                        <?php self::render_general_settings(); ?>
                    </div>
                    
                    <div class="enamel-actions">
                        <button type="button" class="enamel-secondary-button">
                            <?php _e('Reset to Defaults', 'enamel-store-locator'); ?>
                        </button>
                        <?php submit_button(__('Save All Settings', 'enamel-store-locator'), 'primary enamel-primary-button', 'submit', false); ?>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Map settings page
     */
    public static function map_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Map Settings', 'enamel-store-locator'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('enamel_sl_map');
                do_settings_sections('enamel-store-locator-map');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render content settings section
     */
    private static function render_content_settings() {
        // Include field callbacks
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        ?>
        <div class="enamel-preview-section">
            <h3 class="enamel-preview-title"><?php _e('Live Preview', 'enamel-store-locator'); ?></h3>
            <div class="enamel-preview-demo">
                <?php _e('Preview will appear here as you make changes', 'enamel-store-locator'); ?>
            </div>
        </div>
        
        <div class="enamel-form-section">
            <div class="enamel-section-header">
                <h3 class="enamel-section-title"><?php _e('Header Text', 'enamel-store-locator'); ?></h3>
                <p class="enamel-section-description"><?php _e('Customize the main header and subtitle', 'enamel-store-locator'); ?></p>
            </div>
            <div class="enamel-section-content">
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Main Title', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'header_main_title',
                            'default' => 'Find Your Nearest Location',
                            'placeholder' => 'Enter main title...'
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Subtitle', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'header_subtitle',
                            'type' => 'textarea',
                            'default' => 'Quality dental care across Texas with convenient locations',
                            'placeholder' => 'Enter subtitle or description...'
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="enamel-form-section">
            <div class="enamel-section-header">
                <h3 class="enamel-section-title"><?php _e('Search Section', 'enamel-store-locator'); ?></h3>
                <p class="enamel-section-description"><?php _e('Customize search functionality text', 'enamel-store-locator'); ?></p>
            </div>
            <div class="enamel-section-content">
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Search Section Title', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'search_section_title',
                            'default' => 'Find Nearest Location',
                            'placeholder' => 'Search section heading...'
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Search Placeholder', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'search_input_placeholder',
                            'default' => 'Enter address or zip code',
                            'placeholder' => 'Search input placeholder text...'
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Search Button', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'search_button_text',
                            'default' => 'Search',
                            'class' => 'regular-text'
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Location Button', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'location_button_text',
                            'default' => 'Use My Location',
                            'class' => 'regular-text'
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="enamel-form-section">
            <div class="enamel-section-header">
                <h3 class="enamel-section-title"><?php _e('Location Cards', 'enamel-store-locator'); ?></h3>
                <p class="enamel-section-description"><?php _e('Customize text for location card buttons', 'enamel-store-locator'); ?></p>
            </div>
            <div class="enamel-section-content">
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Directions Button', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'directions_button_text',
                            'default' => 'Get Directions',
                            'class' => 'regular-text'
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Call Button', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'call_button_text',
                            'default' => 'Call',
                            'class' => 'regular-text'
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Schedule Button', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'schedule_button_text',
                            'default' => 'Schedule Online',
                            'class' => 'regular-text',
                            'description' => 'Leave empty to hide the button'
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Schedule Link URL', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'schedule_link_url',
                            'default' => '',
                            'class' => 'regular-text',
                            'description' => 'URL to your online scheduling page or booking system',
                            'placeholder' => 'https://example.com/schedule'
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Footer Text', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'footer_text',
                            'default' => 'Established in 2016 • Quality dental care using the latest technology',
                            'placeholder' => 'Footer or branding text...'
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render color settings section
     */
    private static function render_color_settings() {
        // Include field callbacks
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        ?>
        <div class="enamel-form-section">
            <div class="enamel-section-header">
                <h3 class="enamel-section-title"><?php _e('Brand Colors', 'enamel-store-locator'); ?></h3>
                <p class="enamel-section-description"><?php _e('Set your primary brand colors for headers, buttons, and accents', 'enamel-store-locator'); ?></p>
            </div>
            <div class="enamel-section-content">
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Primary Color', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::color_field_callback(array(
                            'field' => 'primary_color',
                            'description' => __('Used for headers, titles, and primary buttons', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Accent Color', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::color_field_callback(array(
                            'field' => 'accent_color',
                            'description' => __('Used for call-to-action buttons and highlights', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Map Marker Color', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::color_field_callback(array(
                            'field' => 'marker_color',
                            'description' => __('Color for location markers on the map', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="enamel-form-section">
            <div class="enamel-section-header">
                <h3 class="enamel-section-title"><?php _e('Background Colors', 'enamel-store-locator'); ?></h3>
                <p class="enamel-section-description"><?php _e('Customize background and surface colors', 'enamel-store-locator'); ?></p>
            </div>
            <div class="enamel-section-content">
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Main Background', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::color_field_callback(array(
                            'field' => 'background_color',
                            'description' => __('Main background color for the entire locator', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Card Background', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::color_field_callback(array(
                            'field' => 'card_background',
                            'description' => __('Background color for location cards and panels', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="enamel-form-section">
            <div class="enamel-section-header">
                <h3 class="enamel-section-title"><?php _e('Text Colors', 'enamel-store-locator'); ?></h3>
                <p class="enamel-section-description"><?php _e('Set text colors for optimal readability', 'enamel-store-locator'); ?></p>
            </div>
            <div class="enamel-section-content">
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Primary Text', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::color_field_callback(array(
                            'field' => 'primary_text',
                            'description' => __('Main text color for headings and important information', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Secondary Text', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::color_field_callback(array(
                            'field' => 'secondary_text',
                            'description' => __('Secondary text for descriptions and less important information', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render general settings section
     */
    private static function render_general_settings() {
        // Include field callbacks
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        ?>
        <div class="enamel-form-section">
            <div class="enamel-section-header">
                <h3 class="enamel-section-title"><?php _e('Google Maps Integration', 'enamel-store-locator'); ?></h3>
                <p class="enamel-section-description"><?php _e('Configure your Google Maps API key and default settings', 'enamel-store-locator'); ?></p>
            </div>
            <div class="enamel-section-content">
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('API Key', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::text_field_callback(array(
                            'field' => 'google_maps_api_key',
                            'type' => 'password',
                            'placeholder' => 'AIzaSy...',
                            'description' => sprintf(
                                __('Get your API key from the <a href="%s" target="_blank">Google Cloud Console</a>', 'enamel-store-locator'),
                                'https://console.cloud.google.com/'
                            )
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Default Map Center', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php EnamelStoreLocatorFields::default_center_field_callback(); ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Default Zoom Level', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::slider_field_callback(array(
                            'field' => 'default_zoom',
                            'min' => 1,
                            'max' => 20,
                            'default' => 10,
                            'description' => __('Map zoom level (1 = world view, 20 = building level)', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="enamel-form-section">
            <div class="enamel-section-header">
                <h3 class="enamel-section-title"><?php _e('Map Appearance', 'enamel-store-locator'); ?></h3>
                <p class="enamel-section-description"><?php _e('Customize how your map looks and behaves', 'enamel-store-locator'); ?></p>
            </div>
            <div class="enamel-section-content">
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Map Type', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::select_field_callback(array(
                            'field' => 'map_type',
                            'default' => 'roadmap',
                            'options' => array(
                                'roadmap' => __('Roadmap (Standard)', 'enamel-store-locator'),
                                'satellite' => __('Satellite', 'enamel-store-locator'),
                                'hybrid' => __('Hybrid (Satellite + Labels)', 'enamel-store-locator'),
                                'terrain' => __('Terrain', 'enamel-store-locator')
                            ),
                            'description' => __('Default map display type', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Search Radius', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::slider_field_callback(array(
                            'field' => 'default_radius',
                            'min' => 5,
                            'max' => 100,
                            'default' => 25,
                            'unit' => ' miles',
                            'description' => __('Default search radius for finding nearby locations', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
                <div class="enamel-field-row">
                    <label class="enamel-field-label"><?php _e('Features', 'enamel-store-locator'); ?></label>
                    <div class="enamel-field-control">
                        <?php
                        EnamelStoreLocatorFields::checkbox_field_callback(array(
                            'field' => 'enable_clustering',
                            'label' => __('Enable marker clustering for better performance', 'enamel-store-locator'),
                            'description' => __('Groups nearby markers when zoomed out to improve map performance', 'enamel-store-locator')
                        ));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
?>