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
        // Handle form submission
        if (isset($_POST['submit'])) {
            check_admin_referer('enamel_sl_settings');
            
            // Save settings
            $settings_fields = array(
                'google_maps_api_key',
                'default_lat',
                'default_lng', 
                'default_zoom',
                'header_main_title',
                'header_subtitle',
                'search_section_title',
                'search_input_placeholder',
                'search_button_text',
                'location_button_text',
                'footer_text',
                'directions_button_text',
                'call_button_text'
            );
            
            foreach ($settings_fields as $field) {
                if (isset($_POST['enamel_sl_' . $field])) {
                    update_option('enamel_sl_' . $field, sanitize_text_field($_POST['enamel_sl_' . $field]));
                }
            }
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'enamel-store-locator') . '</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Store Locator Settings', 'enamel-store-locator'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('enamel_sl_settings'); ?>
                <?php settings_fields('enamel_sl_general'); ?>
                
                <div class="enamel-sl-settings-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'enamel-store-locator'); ?></a>
                        <a href="#content" class="nav-tab"><?php _e('Content', 'enamel-store-locator'); ?></a>
                        <a href="#colors" class="nav-tab"><?php _e('Colors', 'enamel-store-locator'); ?></a>
                    </nav>
                    
                    <div id="general" class="tab-content active">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Google Maps API Key', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_google_maps_api_key" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_google_maps_api_key')); ?>" 
                                           class="regular-text" />
                                    <p class="description">
                                        <?php _e('Get your API key from the', 'enamel-store-locator'); ?> 
                                        <a href="https://console.cloud.google.com/" target="_blank"><?php _e('Google Cloud Console', 'enamel-store-locator'); ?></a>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Default Map Center', 'enamel-store-locator'); ?></th>
                                <td>
                                    <label><?php _e('Latitude:', 'enamel-store-locator'); ?>
                                        <input type="text" 
                                               name="enamel_sl_default_lat" 
                                               value="<?php echo esc_attr(get_option('enamel_sl_default_lat', '30.3072')); ?>" />
                                    </label><br><br>
                                    <label><?php _e('Longitude:', 'enamel-store-locator'); ?>
                                        <input type="text" 
                                               name="enamel_sl_default_lng" 
                                               value="<?php echo esc_attr(get_option('enamel_sl_default_lng', '-97.7560')); ?>" />
                                    </label>
                                    <p class="description"><?php _e('Default center point for the map (Austin, TX shown as example)', 'enamel-store-locator'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Default Zoom Level', 'enamel-store-locator'); ?></th>
                                <td>
                                    <select name="enamel_sl_default_zoom">
                                        <?php
                                        $current_zoom = get_option('enamel_sl_default_zoom', 10);
                                        for ($i = 1; $i <= 20; $i++) {
                                            echo '<option value="' . $i . '"' . selected($current_zoom, $i, false) . '>' . $i . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php _e('Map zoom level (1 = world view, 20 = building level)', 'enamel-store-locator'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div id="content" class="tab-content">
                        <h2><?php _e('Content Customization', 'enamel-store-locator'); ?></h2>
                        <p><?php _e('Customize all text that appears in the store locator:', 'enamel-store-locator'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Main Title', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_header_main_title" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_header_main_title', 'Find Your Nearest Location')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Subtitle', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_header_subtitle" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_header_subtitle', 'Quality dental care across Texas with convenient locations')); ?>" 
                                           class="large-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Search Section Title', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_search_section_title" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_search_section_title', 'Find Nearest Location')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Search Input Placeholder', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_search_input_placeholder" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_search_input_placeholder', 'Enter address or zip code')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Search Button Text', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_search_button_text" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_search_button_text', 'Search')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Use Location Button Text', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_location_button_text" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_location_button_text', 'Use My Location')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Directions Button Text', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_directions_button_text" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_directions_button_text', 'Get Directions')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Call Button Text', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_call_button_text" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_call_button_text', 'Call')); ?>" 
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Footer Text', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_footer_text" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_footer_text', 'Established in 2016 • Quality dental care using the latest technology')); ?>" 
                                           class="large-text" />
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div id="colors" class="tab-content">
                        <h2><?php _e('Color Scheme', 'enamel-store-locator'); ?></h2>
                        <p><?php _e('Customize the colors to match your brand:', 'enamel-store-locator'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Primary Color (Headers)', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_primary_color" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_primary_color', '#7D55C7')); ?>" 
                                           class="color-field" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Accent Color (Call Buttons)', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_accent_color" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_accent_color', '#E56B10')); ?>" 
                                           class="color-field" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Background Color', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_background_color" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_background_color', '#FFFFFF')); ?>" 
                                           class="color-field" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Card Background', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_card_background" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_card_background', '#F8F9FA')); ?>" 
                                           class="color-field" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Primary Text Color', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_primary_text" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_primary_text', '#231942')); ?>" 
                                           class="color-field" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Secondary Text Color', 'enamel-store-locator'); ?></th>
                                <td>
                                    <input type="text" 
                                           name="enamel_sl_secondary_text" 
                                           value="<?php echo esc_attr(get_option('enamel_sl_secondary_text', '#6B7280')); ?>" 
                                           class="color-field" />
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php submit_button(__('Save Settings', 'enamel-store-locator')); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize color pickers
            $('.color-field').wpColorPicker();
            
            // Tab switching
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').removeClass('active');
                $(target).addClass('active');
            });
        });
        </script>
        
        <style>
        .enamel-sl-settings-tabs .tab-content {
            display: none;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-top: none;
        }
        .enamel-sl-settings-tabs .tab-content.active {
            display: block;
        }
        .nav-tab-wrapper {
            margin-bottom: 0;
        }
        </style>
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
}
?>