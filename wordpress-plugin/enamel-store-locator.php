<?php
/**
 * Plugin Name: Enamel Store Locator
 * Plugin URI: https://enamel-dentistry.com/plugins/store-locator
 * Description: Intelligent store locator with Google Maps integration, customizable branding, and comprehensive location management for dental practices.
 * Version: 1.3.0
 * Author: Enamel Dentistry
 * License: GPL v2 or later
 * Text Domain: enamel-store-locator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ENAMEL_SL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ENAMEL_SL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ENAMEL_SL_VERSION', '1.3.0');

/**
 * Main Enamel Store Locator Class
 */
class EnamelStoreLocator {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('enamel-store-locator', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Register shortcodes
        add_shortcode('enamel_store_locator', array($this, 'render_store_locator'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_action('add_meta_boxes', array($this, 'add_location_meta_boxes'));
            add_action('save_post_clinic_location', array($this, 'save_location_meta'));
            add_filter('manage_clinic_location_posts_columns', array($this, 'location_columns'));
            add_action('manage_clinic_location_posts_custom_column', array($this, 'location_column_content'), 10, 2);
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // AJAX hooks for Google Places API
        add_action('wp_ajax_enamel_fetch_place_details', array($this, 'ajax_fetch_place_details'));

        // REST API
        add_action('rest_api_init', array($this, 'register_rest_api'));
        
        // WP Rocket compatibility - safelist our CSS from Remove Unused CSS
        add_filter('rocket_rucss_safelist', array($this, 'wp_rocket_safelist'));
        add_filter('rocket_exclude_css', array($this, 'wp_rocket_exclude_css'));
        
        // Create custom post type for locations
        $this->create_location_post_type();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create custom post type first
        $this->create_location_post_type();
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Create custom post type for clinic locations
     */
    public function create_location_post_type() {
        $args = array(
            'label' => __('Clinic Locations', 'enamel-store-locator'),
            'labels' => array(
                'name' => __('Clinic Locations', 'enamel-store-locator'),
                'singular_name' => __('Clinic Location', 'enamel-store-locator'),
                'add_new' => __('Add New Location', 'enamel-store-locator'),
                'add_new_item' => __('Add New Clinic Location', 'enamel-store-locator'),
                'edit_item' => __('Edit Clinic Location', 'enamel-store-locator'),
                'new_item' => __('New Clinic Location', 'enamel-store-locator'),
                'view_item' => __('View Clinic Location', 'enamel-store-locator'),
                'search_items' => __('Search Clinic Locations', 'enamel-store-locator'),
                'not_found' => __('No clinic locations found', 'enamel-store-locator'),
                'not_found_in_trash' => __('No clinic locations found in trash', 'enamel-store-locator'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'enamel-store-locator',
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title'),
            'show_in_rest' => true,
            'rest_base' => 'clinic-locations'
        );
        
        register_post_type('clinic_location', $args);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Store Locator', 'enamel-store-locator'),
            __('Store Locator', 'enamel-store-locator'),
            'manage_options',
            'enamel-store-locator',
            array($this, 'dashboard_page'),
            'dashicons-location-alt',
            30
        );
        
        // Dashboard submenu (same as main)
        add_submenu_page(
            'enamel-store-locator',
            __('Dashboard', 'enamel-store-locator'),
            __('Dashboard', 'enamel-store-locator'),
            'manage_options',
            'enamel-store-locator',
            array($this, 'dashboard_page')
        );
        
        // Locations submenu (points to custom post type)
        add_submenu_page(
            'enamel-store-locator',
            __('Manage Locations', 'enamel-store-locator'),
            __('Locations', 'enamel-store-locator'),
            'manage_options',
            'edit.php?post_type=clinic_location'
        );
        
        // Settings submenu
        add_submenu_page(
            'enamel-store-locator',
            __('Settings', 'enamel-store-locator'),
            __('Settings', 'enamel-store-locator'),
            'manage_options',
            'enamel-store-locator-settings',
            array($this, 'settings_page')
        );
        
        // Branding submenu
        add_submenu_page(
            'enamel-store-locator',
            __('Branding', 'enamel-store-locator'),
            __('Branding', 'enamel-store-locator'),
            'manage_options',
            'enamel-store-locator-branding',
            array($this, 'branding_page')
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        $this->register_settings();
    }
    
    /**
     * Register all plugin settings
     */
    private function register_settings() {
        // General settings (Settings page)
        $general_settings = array(
            'google_maps_api_key' => 'sanitize_text_field',
            'google_maps_map_id' => 'sanitize_text_field',
            'default_lat' => array($this, 'sanitize_latitude'),
            'default_lng' => array($this, 'sanitize_longitude'),
            'default_zoom' => array($this, 'sanitize_zoom_level'),
            // Map styling
            'map_style' => 'sanitize_text_field',
            'custom_map_style' => array($this, 'sanitize_json'),
            // Marker settings
            'marker_color' => 'sanitize_hex_color',
            'marker_style' => 'sanitize_text_field',
            'active_marker_color' => 'sanitize_hex_color',
            'custom_marker_image' => 'esc_url_raw',
            'custom_active_marker_image' => 'esc_url_raw',
            // Text labels
            'header_main_title' => 'sanitize_text_field',
            'header_subtitle' => 'sanitize_textarea_field',
            'search_input_placeholder' => 'sanitize_text_field',
            // Button visibility toggles (default ON)
            'enable_schedule_button' => array($this, 'sanitize_checkbox'),
            'enable_directions_button' => array($this, 'sanitize_checkbox'),
            'enable_call_button' => array($this, 'sanitize_checkbox'),
            // Button text labels
            'schedule_button_text' => 'sanitize_text_field',
            'directions_button_text' => 'sanitize_text_field',
            'call_button_text' => 'sanitize_text_field',
            // Performance settings
            'enable_lazy_load' => array($this, 'sanitize_checkbox'),
            'defer_scripts' => array($this, 'sanitize_checkbox'),
            'enable_preconnect' => array($this, 'sanitize_checkbox'),
        );
        
        foreach ($general_settings as $option_name => $sanitize_callback) {
            register_setting('enamel_sl_settings', 'enamel_sl_' . $option_name, array(
                'sanitize_callback' => $sanitize_callback
            ));
        }
        
        // Branding settings (Branding page) - separate group to avoid reset issue
        $branding_settings = array(
            // Colors
            'primary_color' => 'sanitize_hex_color',
            'secondary_color' => 'sanitize_hex_color',
            'accent_color' => 'sanitize_hex_color',
            'background_color' => 'sanitize_hex_color',
            'card_background' => 'sanitize_hex_color',
            'header_background' => 'sanitize_hex_color',
            'text_primary' => 'sanitize_hex_color',
            'text_secondary' => 'sanitize_hex_color',
            'button_text_color' => 'sanitize_hex_color',
            'header_text_color' => 'sanitize_hex_color',
            'card_text_color' => 'sanitize_hex_color',
            // Fonts
            'primary_font' => 'sanitize_text_field',
            'secondary_font' => 'sanitize_text_field',
            'font_size_base' => 'absint',
        );
        
        foreach ($branding_settings as $option_name => $sanitize_callback) {
            register_setting('enamel_sl_branding', 'enamel_sl_' . $option_name, array(
                'sanitize_callback' => $sanitize_callback
            ));
        }
    }
    
    /**
     * Add meta boxes for location post type
     */
    public function add_location_meta_boxes() {
        add_meta_box(
            'enamel_sl_google_place',
            __('Google Places Integration', 'enamel-store-locator'),
            array($this, 'google_place_meta_box'),
            'clinic_location',
            'normal',
            'high'
        );
        
        add_meta_box(
            'enamel_sl_location_details',
            __('Location Details', 'enamel-store-locator'),
            array($this, 'location_details_meta_box'),
            'clinic_location',
            'normal',
            'high'
        );
        
        add_meta_box(
            'enamel_sl_booking',
            __('Booking & Links', 'enamel-store-locator'),
            array($this, 'booking_meta_box'),
            'clinic_location',
            'side',
            'high'
        );
    }
    
    /**
     * Google Place meta box
     */
    public function google_place_meta_box($post) {
        wp_nonce_field('enamel_sl_location_nonce', 'enamel_sl_nonce');
        $place_id = get_post_meta($post->ID, '_enamel_sl_place_id', true);
        ?>
        <div class="enamel-sl-meta-box">
            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Enter a Google Place ID to automatically fetch business details. You can find Place IDs using the', 'enamel-store-locator'); ?>
                <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank"><?php _e('Google Place ID Finder', 'enamel-store-locator'); ?></a>.
            </p>
            
            <table class="form-table">
                <tr>
                    <th><label for="enamel_sl_place_id"><?php _e('Google Place ID', 'enamel-store-locator'); ?></label></th>
                    <td>
                        <input type="text" id="enamel_sl_place_id" name="enamel_sl_place_id" value="<?php echo esc_attr($place_id); ?>" class="large-text" placeholder="ChIJ..." />
                        <button type="button" id="enamel_sl_fetch_place" class="button button-secondary" style="margin-top: 5px;">
                            <?php _e('Fetch Details from Google', 'enamel-store-locator'); ?>
                        </button>
                        <span id="enamel_sl_fetch_status" style="margin-left: 10px;"></span>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Location details meta box
     */
    public function location_details_meta_box($post) {
        $address = get_post_meta($post->ID, '_enamel_sl_address', true);
        $city = get_post_meta($post->ID, '_enamel_sl_city', true);
        $state = get_post_meta($post->ID, '_enamel_sl_state', true);
        $zip = get_post_meta($post->ID, '_enamel_sl_zip', true);
        $phone = get_post_meta($post->ID, '_enamel_sl_phone', true);
        $lat = get_post_meta($post->ID, '_enamel_sl_lat', true);
        $lng = get_post_meta($post->ID, '_enamel_sl_lng', true);
        $hours = get_post_meta($post->ID, '_enamel_sl_hours', true);
        $rating = get_post_meta($post->ID, '_enamel_sl_rating', true);
        ?>
        <div class="enamel-sl-meta-box">
            <p class="description" style="margin-bottom: 15px;">
                <?php _e('These fields can be auto-filled from Google Places or entered manually.', 'enamel-store-locator'); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th><label for="enamel_sl_address"><?php _e('Street Address', 'enamel-store-locator'); ?></label></th>
                    <td><input type="text" id="enamel_sl_address" name="enamel_sl_address" value="<?php echo esc_attr($address); ?>" class="large-text" /></td>
                </tr>
                <tr>
                    <th><label for="enamel_sl_city"><?php _e('City', 'enamel-store-locator'); ?></label></th>
                    <td><input type="text" id="enamel_sl_city" name="enamel_sl_city" value="<?php echo esc_attr($city); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="enamel_sl_state"><?php _e('State', 'enamel-store-locator'); ?></label></th>
                    <td><input type="text" id="enamel_sl_state" name="enamel_sl_state" value="<?php echo esc_attr($state); ?>" class="small-text" /></td>
                </tr>
                <tr>
                    <th><label for="enamel_sl_zip"><?php _e('ZIP Code', 'enamel-store-locator'); ?></label></th>
                    <td><input type="text" id="enamel_sl_zip" name="enamel_sl_zip" value="<?php echo esc_attr($zip); ?>" class="small-text" /></td>
                </tr>
                <tr>
                    <th><label for="enamel_sl_phone"><?php _e('Phone Number', 'enamel-store-locator'); ?></label></th>
                    <td><input type="text" id="enamel_sl_phone" name="enamel_sl_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" placeholder="(512) 555-1234" /></td>
                </tr>
                <tr>
                    <th><label><?php _e('Coordinates', 'enamel-store-locator'); ?></label></th>
                    <td>
                        <label for="enamel_sl_lat"><?php _e('Lat:', 'enamel-store-locator'); ?></label>
                        <input type="text" id="enamel_sl_lat" name="enamel_sl_lat" value="<?php echo esc_attr($lat); ?>" class="small-text" />
                        <label for="enamel_sl_lng" style="margin-left: 10px;"><?php _e('Lng:', 'enamel-store-locator'); ?></label>
                        <input type="text" id="enamel_sl_lng" name="enamel_sl_lng" value="<?php echo esc_attr($lng); ?>" class="small-text" />
                    </td>
                </tr>
                <tr>
                    <th><label for="enamel_sl_hours"><?php _e('Business Hours', 'enamel-store-locator'); ?></label></th>
                    <td>
                        <textarea id="enamel_sl_hours" name="enamel_sl_hours" class="large-text" rows="4" placeholder="Mon-Fri: 8am-5pm&#10;Sat: 9am-2pm&#10;Sun: Closed"><?php echo esc_textarea($hours); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="enamel_sl_rating"><?php _e('Google Rating', 'enamel-store-locator'); ?></label></th>
                    <td><input type="text" id="enamel_sl_rating" name="enamel_sl_rating" value="<?php echo esc_attr($rating); ?>" class="small-text" readonly /> <span class="description"><?php _e('(Auto-fetched from Google)', 'enamel-store-locator'); ?></span></td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Booking meta box
     */
    public function booking_meta_box($post) {
        $booking_url = get_post_meta($post->ID, '_enamel_sl_booking_url', true);
        $gmb_url = get_post_meta($post->ID, '_enamel_sl_gmb_url', true);
        $active = get_post_meta($post->ID, '_enamel_sl_active', true);
        if ($active === '') $active = '1'; // Default to active
        ?>
        <div class="enamel-sl-meta-box">
            <p>
                <label for="enamel_sl_booking_url"><strong><?php _e('Booking URL', 'enamel-store-locator'); ?></strong></label><br>
                <input type="url" id="enamel_sl_booking_url" name="enamel_sl_booking_url" value="<?php echo esc_url($booking_url); ?>" class="large-text" placeholder="https://booking.example.com/location1" />
                <span class="description"><?php _e('Unique booking link for this location', 'enamel-store-locator'); ?></span>
            </p>
            
            <p>
                <label for="enamel_sl_gmb_url"><strong><?php _e('Google My Business URL', 'enamel-store-locator'); ?></strong></label><br>
                <input type="url" id="enamel_sl_gmb_url" name="enamel_sl_gmb_url" value="<?php echo esc_url($gmb_url); ?>" class="large-text" placeholder="https://g.page/your-business" />
                <span class="description"><?php _e('Link to Google My Business page', 'enamel-store-locator'); ?></span>
            </p>
            
            <p>
                <label>
                    <input type="checkbox" name="enamel_sl_active" value="1" <?php checked($active, '1'); ?> />
                    <strong><?php _e('Active Location', 'enamel-store-locator'); ?></strong>
                </label><br>
                <span class="description"><?php _e('Uncheck to hide this location from the map', 'enamel-store-locator'); ?></span>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save location meta data
     */
    public function save_location_meta($post_id) {
        // Verify nonce
        if (!isset($_POST['enamel_sl_nonce']) || !wp_verify_nonce($_POST['enamel_sl_nonce'], 'enamel_sl_location_nonce')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta fields
        $fields = array(
            'place_id' => 'sanitize_text_field',
            'address' => 'sanitize_text_field',
            'city' => 'sanitize_text_field',
            'state' => 'sanitize_text_field',
            'zip' => 'sanitize_text_field',
            'phone' => 'sanitize_text_field',
            'lat' => array($this, 'sanitize_latitude'),
            'lng' => array($this, 'sanitize_longitude'),
            'hours' => 'sanitize_textarea_field',
            'rating' => 'sanitize_text_field',
            'booking_url' => 'esc_url_raw',
            'gmb_url' => 'esc_url_raw',
        );
        
        foreach ($fields as $field => $sanitize_callback) {
            if (isset($_POST['enamel_sl_' . $field])) {
                $value = $_POST['enamel_sl_' . $field];
                if (is_callable($sanitize_callback)) {
                    $value = call_user_func($sanitize_callback, $value);
                }
                update_post_meta($post_id, '_enamel_sl_' . $field, $value);
            }
        }
        
        // Handle checkbox separately
        $active = isset($_POST['enamel_sl_active']) ? '1' : '0';
        update_post_meta($post_id, '_enamel_sl_active', $active);
    }
    
    /**
     * Custom columns for location list
     */
    public function location_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __('Location Name', 'enamel-store-locator');
        $new_columns['address'] = __('Address', 'enamel-store-locator');
        $new_columns['phone'] = __('Phone', 'enamel-store-locator');
        $new_columns['booking'] = __('Booking URL', 'enamel-store-locator');
        $new_columns['active'] = __('Status', 'enamel-store-locator');
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }
    
    /**
     * Custom column content
     */
    public function location_column_content($column, $post_id) {
        switch ($column) {
            case 'address':
                $address = get_post_meta($post_id, '_enamel_sl_address', true);
                $city = get_post_meta($post_id, '_enamel_sl_city', true);
                $state = get_post_meta($post_id, '_enamel_sl_state', true);
                echo esc_html($address);
                if ($city || $state) {
                    echo '<br><small>' . esc_html($city . ', ' . $state) . '</small>';
                }
                break;
            case 'phone':
                echo esc_html(get_post_meta($post_id, '_enamel_sl_phone', true));
                break;
            case 'booking':
                $url = get_post_meta($post_id, '_enamel_sl_booking_url', true);
                if ($url) {
                    echo '<a href="' . esc_url($url) . '" target="_blank" class="button button-small">' . __('View', 'enamel-store-locator') . '</a>';
                } else {
                    echo '<span style="color: #999;">' . __('Not set', 'enamel-store-locator') . '</span>';
                }
                break;
            case 'active':
                $active = get_post_meta($post_id, '_enamel_sl_active', true);
                if ($active === '' || $active === '1') {
                    echo '<span style="color: green;">●</span> ' . __('Active', 'enamel-store-locator');
                } else {
                    echo '<span style="color: #ccc;">●</span> ' . __('Inactive', 'enamel-store-locator');
                }
                break;
        }
    }
    
    /**
     * AJAX handler for fetching Google Place details
     */
    public function ajax_fetch_place_details() {
        check_ajax_referer('enamel_sl_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'enamel-store-locator'));
        }
        
        $place_id = sanitize_text_field($_POST['place_id']);
        $api_key = get_option('enamel_sl_google_maps_api_key');
        
        if (empty($api_key)) {
            wp_send_json_error(__('Google Maps API key not configured. Please add it in Settings.', 'enamel-store-locator'));
        }
        
        if (empty($place_id)) {
            wp_send_json_error(__('Please enter a Place ID', 'enamel-store-locator'));
        }
        
        // Call Google Places API
        $url = add_query_arg(array(
            'place_id' => $place_id,
            'fields' => 'name,formatted_address,formatted_phone_number,geometry,opening_hours,rating,url,address_components',
            'key' => $api_key
        ), 'https://maps.googleapis.com/maps/api/place/details/json');
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            wp_send_json_error(__('Failed to connect to Google API', 'enamel-store-locator'));
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($body['status'] !== 'OK') {
            wp_send_json_error(sprintf(__('Google API Error: %s', 'enamel-store-locator'), $body['status']));
        }
        
        $result = $body['result'];
        
        // Parse address components
        $city = '';
        $state = '';
        $zip = '';
        $street_number = '';
        $route = '';
        
        if (isset($result['address_components'])) {
            foreach ($result['address_components'] as $component) {
                if (in_array('locality', $component['types'])) {
                    $city = $component['long_name'];
                }
                if (in_array('administrative_area_level_1', $component['types'])) {
                    $state = $component['short_name'];
                }
                if (in_array('postal_code', $component['types'])) {
                    $zip = $component['long_name'];
                }
                if (in_array('street_number', $component['types'])) {
                    $street_number = $component['long_name'];
                }
                if (in_array('route', $component['types'])) {
                    $route = $component['long_name'];
                }
            }
        }
        
        $address = trim($street_number . ' ' . $route);
        
        // Format hours
        $hours = '';
        if (isset($result['opening_hours']['weekday_text'])) {
            $hours = implode("\n", $result['opening_hours']['weekday_text']);
        }
        
        // Helper to limit string length for safety
        $limit = function($str, $max = 255) {
            return mb_substr(sanitize_text_field($str), 0, $max);
        };
        
        // Validate lat/lng are finite numbers
        $lat = isset($result['geometry']['location']['lat']) ? floatval($result['geometry']['location']['lat']) : 0;
        $lng = isset($result['geometry']['location']['lng']) ? floatval($result['geometry']['location']['lng']) : 0;
        if (!is_finite($lat)) $lat = 0;
        if (!is_finite($lng)) $lng = 0;
        
        // Validate rating is between 0 and 5
        $rating = isset($result['rating']) ? floatval($result['rating']) : 0;
        $rating = max(0, min(5, $rating));
        
        // Sanitize all data with length limits
        $data = array(
            'name' => $limit($result['name'] ?? '', 200),
            'address' => $limit($address, 300),
            'city' => $limit($city, 100),
            'state' => $limit($state, 50),
            'zip' => $limit($zip, 20),
            'phone' => $limit($result['formatted_phone_number'] ?? '', 30),
            'lat' => $lat,
            'lng' => $lng,
            'hours' => mb_substr(sanitize_textarea_field($hours), 0, 1000),
            'rating' => $rating,
            'gmb_url' => esc_url_raw(mb_substr($result['url'] ?? '', 0, 500))
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $location_count = intval(wp_count_posts('clinic_location')->publish);
        $api_key = get_option('enamel_sl_google_maps_api_key');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Enamel Store Locator', 'enamel-store-locator'); ?></h1>
            
            <div class="enamel-sl-dashboard" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                
                <div class="postbox" style="margin: 0;">
                    <h2 class="hndle" style="padding: 12px;"><?php esc_html_e('Quick Stats', 'enamel-store-locator'); ?></h2>
                    <div class="inside">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: center;">
                            <div style="background: #f0f0f1; padding: 20px; border-radius: 8px;">
                                <div style="font-size: 36px; font-weight: bold; color: #7D55C7;"><?php echo esc_html($location_count); ?></div>
                                <div style="color: #666;"><?php esc_html_e('Locations', 'enamel-store-locator'); ?></div>
                            </div>
                            <div style="background: #f0f0f1; padding: 20px; border-radius: 8px;">
                                <div style="font-size: 36px; font-weight: bold; color: <?php echo $api_key ? '#00a32a' : '#d63638'; ?>;"><?php echo $api_key ? '&#10003;' : '&#10007;'; ?></div>
                                <div style="color: #666;"><?php esc_html_e('API Key', 'enamel-store-locator'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="postbox" style="margin: 0;">
                    <h2 class="hndle" style="padding: 12px;"><?php esc_html_e('Quick Setup', 'enamel-store-locator'); ?></h2>
                    <div class="inside">
                        <ol style="margin-left: 20px;">
                            <li style="margin-bottom: 10px;">
                                <?php if ($api_key): ?>
                                    <span style="color: #00a32a;">&#10003;</span>
                                <?php else: ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=enamel-store-locator-settings')); ?>"><?php esc_html_e('Add Google Maps API Key', 'enamel-store-locator'); ?></a>
                                <?php endif; ?>
                                <?php if ($api_key) esc_html_e('Google Maps API configured', 'enamel-store-locator'); ?>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=clinic_location')); ?>"><?php esc_html_e('Add Clinic Locations', 'enamel-store-locator'); ?></a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=enamel-store-locator-branding')); ?>"><?php esc_html_e('Customize Branding', 'enamel-store-locator'); ?></a>
                            </li>
                            <li><?php esc_html_e('Add shortcode to any page:', 'enamel-store-locator'); ?> <code>[enamel_store_locator]</code></li>
                        </ol>
                    </div>
                </div>
                
                <div class="postbox" style="margin: 0; grid-column: 1 / -1;">
                    <h2 class="hndle" style="padding: 12px;"><?php esc_html_e('Shortcode Usage', 'enamel-store-locator'); ?></h2>
                    <div class="inside">
                        <p><?php esc_html_e('Copy this shortcode to any page or Elementor Custom HTML widget:', 'enamel-store-locator'); ?></p>
                        <code style="display: block; padding: 15px; background: #f0f0f1; border-radius: 4px; font-size: 14px;">[enamel_store_locator]</code>
                        
                        <h4><?php esc_html_e('Shortcode Options:', 'enamel-store-locator'); ?></h4>
                        <ul style="margin-left: 20px;">
                            <li><code>[enamel_store_locator height="600px"]</code> - <?php esc_html_e('Custom height', 'enamel-store-locator'); ?></li>
                            <li><code>[enamel_store_locator zoom="12"]</code> - <?php esc_html_e('Custom zoom level', 'enamel-store-locator'); ?></li>
                        </ul>
                    </div>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Store Locator Settings', 'enamel-store-locator'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('enamel_sl_settings'); ?>
                
                <h2><?php _e('Google Maps API', 'enamel-store-locator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="enamel_sl_google_maps_api_key"><?php _e('API Key', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_google_maps_api_key" name="enamel_sl_google_maps_api_key" value="<?php echo esc_attr(get_option('enamel_sl_google_maps_api_key', '')); ?>" class="large-text" />
                            <p class="description"><?php _e('Required for Maps and Places API. Get one from', 'enamel-store-locator'); ?> <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_google_maps_map_id"><?php _e('Map ID', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_google_maps_map_id" name="enamel_sl_google_maps_map_id" value="<?php echo esc_attr(get_option('enamel_sl_google_maps_map_id', '')); ?>" class="regular-text" placeholder="e.g., 8e0a97af9386fef" />
                            <p class="description"><?php _e('Required for Advanced Markers. Create one in', 'enamel-store-locator'); ?> <a href="https://console.cloud.google.com/google/maps-apis/studio/maps" target="_blank">Google Cloud Console > Map Management</a><br><?php _e('Choose "JavaScript" platform when creating your Map ID.', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Map Defaults', 'enamel-store-locator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Default Center', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <label for="enamel_sl_default_lat"><?php _e('Latitude:', 'enamel-store-locator'); ?></label>
                            <input type="text" id="enamel_sl_default_lat" name="enamel_sl_default_lat" value="<?php echo esc_attr(get_option('enamel_sl_default_lat', '30.3072')); ?>" class="small-text" />
                            <label for="enamel_sl_default_lng" style="margin-left: 15px;"><?php _e('Longitude:', 'enamel-store-locator'); ?></label>
                            <input type="text" id="enamel_sl_default_lng" name="enamel_sl_default_lng" value="<?php echo esc_attr(get_option('enamel_sl_default_lng', '-97.7560')); ?>" class="small-text" />
                            <p class="description"><?php _e('Default map center (Austin, TX by default)', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_default_zoom"><?php _e('Default Zoom', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="number" id="enamel_sl_default_zoom" name="enamel_sl_default_zoom" value="<?php echo esc_attr(get_option('enamel_sl_default_zoom', '10')); ?>" min="1" max="20" class="small-text" />
                            <p class="description"><?php _e('1 (world) to 20 (building level)', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_map_style"><?php _e('Map Style', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <select id="enamel_sl_map_style" name="enamel_sl_map_style" class="regular-text">
                                <?php
                                $current_style = get_option('enamel_sl_map_style', 'standard');
                                $map_styles = array(
                                    'standard' => 'Standard (Default)',
                                    'silver' => 'Silver (Modern Gray)',
                                    'retro' => 'Retro (Muted Vintage)',
                                    'dark' => 'Dark (Night Mode)',
                                    'aubergine' => 'Aubergine (Purple Tones)',
                                    'custom' => 'Custom (Paste JSON Below)',
                                );
                                foreach ($map_styles as $value => $label) {
                                    printf('<option value="%s" %s>%s</option>', esc_attr($value), selected($current_style, $value, false), esc_html($label));
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Choose a preset map style or use custom JSON', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr id="custom_map_style_row" style="<?php echo $current_style === 'custom' ? '' : 'display:none;'; ?>">
                        <th><label for="enamel_sl_custom_map_style"><?php _e('Custom Map Style JSON', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <textarea id="enamel_sl_custom_map_style" name="enamel_sl_custom_map_style" class="large-text code" rows="8" placeholder='[{"featureType": "all", "stylers": [{"saturation": -80}]}]'><?php echo esc_textarea(get_option('enamel_sl_custom_map_style', '')); ?></textarea>
                            <p class="description"><?php _e('Paste JSON from', 'enamel-store-locator'); ?> <a href="https://snazzymaps.com/" target="_blank">Snazzy Maps</a> <?php _e('or', 'enamel-store-locator'); ?> <a href="https://mapstyle.withgoogle.com/" target="_blank">Google Maps Styling Wizard</a></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Map Markers', 'enamel-store-locator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="enamel_sl_marker_color"><?php _e('Marker Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_marker_color" name="enamel_sl_marker_color" value="<?php echo esc_attr(get_option('enamel_sl_marker_color', '#7D55C7')); ?>" class="enamel-color-picker" />
                            <p class="description"><?php _e('Color for map marker pins', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_marker_style"><?php _e('Marker Style', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <select id="enamel_sl_marker_style" name="enamel_sl_marker_style" class="regular-text">
                                <?php
                                $current_marker = get_option('enamel_sl_marker_style', 'pin');
                                $marker_styles = array(
                                    'pin' => 'Classic Pin',
                                    'circle' => 'Circle Dot',
                                    'tooth' => 'Dental Tooth',
                                    'custom' => 'Custom Image (Upload Below)',
                                );
                                foreach ($marker_styles as $value => $label) {
                                    printf('<option value="%s" %s>%s</option>', esc_attr($value), selected($current_marker, $value, false), esc_html($label));
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Choose a built-in style or upload your own custom marker image', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr id="custom_marker_row" style="<?php echo $current_marker === 'custom' ? '' : 'display:none;'; ?>">
                        <th><label for="enamel_sl_custom_marker_image"><?php _e('Custom Marker Image', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <?php $custom_marker_url = get_option('enamel_sl_custom_marker_image', ''); ?>
                            <div class="enamel-media-upload">
                                <input type="text" id="enamel_sl_custom_marker_image" name="enamel_sl_custom_marker_image" value="<?php echo esc_url($custom_marker_url); ?>" class="regular-text" placeholder="https://example.com/marker.png" />
                                <button type="button" class="button enamel-upload-btn" data-target="enamel_sl_custom_marker_image"><?php _e('Upload Image', 'enamel-store-locator'); ?></button>
                                <button type="button" class="button enamel-remove-btn" data-target="enamel_sl_custom_marker_image" <?php echo empty($custom_marker_url) ? 'style="display:none;"' : ''; ?>><?php _e('Remove', 'enamel-store-locator'); ?></button>
                            </div>
                            <?php if (!empty($custom_marker_url)): ?>
                            <div class="enamel-marker-preview" id="preview_enamel_sl_custom_marker_image" style="margin-top: 10px;">
                                <img src="<?php echo esc_url($custom_marker_url); ?>" alt="Custom marker preview" style="max-height: 50px; border: 1px solid #ddd; padding: 5px; background: #f9f9f9;" />
                            </div>
                            <?php else: ?>
                            <div class="enamel-marker-preview" id="preview_enamel_sl_custom_marker_image" style="margin-top: 10px; display:none;">
                                <img src="" alt="Custom marker preview" style="max-height: 50px; border: 1px solid #ddd; padding: 5px; background: #f9f9f9;" />
                            </div>
                            <?php endif; ?>
                            <p class="description"><?php _e('Recommended size: 32x32 to 64x64 pixels. PNG with transparency works best.', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr id="custom_active_marker_row" style="<?php echo $current_marker === 'custom' ? '' : 'display:none;'; ?>">
                        <th><label for="enamel_sl_custom_active_marker_image"><?php _e('Custom Active Marker', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <?php $custom_active_marker_url = get_option('enamel_sl_custom_active_marker_image', ''); ?>
                            <div class="enamel-media-upload">
                                <input type="text" id="enamel_sl_custom_active_marker_image" name="enamel_sl_custom_active_marker_image" value="<?php echo esc_url($custom_active_marker_url); ?>" class="regular-text" placeholder="https://example.com/marker-active.png" />
                                <button type="button" class="button enamel-upload-btn" data-target="enamel_sl_custom_active_marker_image"><?php _e('Upload Image', 'enamel-store-locator'); ?></button>
                                <button type="button" class="button enamel-remove-btn" data-target="enamel_sl_custom_active_marker_image" <?php echo empty($custom_active_marker_url) ? 'style="display:none;"' : ''; ?>><?php _e('Remove', 'enamel-store-locator'); ?></button>
                            </div>
                            <?php if (!empty($custom_active_marker_url)): ?>
                            <div class="enamel-marker-preview" id="preview_enamel_sl_custom_active_marker_image" style="margin-top: 10px;">
                                <img src="<?php echo esc_url($custom_active_marker_url); ?>" alt="Active marker preview" style="max-height: 50px; border: 1px solid #ddd; padding: 5px; background: #f9f9f9;" />
                            </div>
                            <?php else: ?>
                            <div class="enamel-marker-preview" id="preview_enamel_sl_custom_active_marker_image" style="margin-top: 10px; display:none;">
                                <img src="" alt="Active marker preview" style="max-height: 50px; border: 1px solid #ddd; padding: 5px; background: #f9f9f9;" />
                            </div>
                            <?php endif; ?>
                            <p class="description"><?php _e('Optional: Different marker for selected/active locations. Uses default custom marker if empty.', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_active_marker_color"><?php _e('Active Marker Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_active_marker_color" name="enamel_sl_active_marker_color" value="<?php echo esc_attr(get_option('enamel_sl_active_marker_color', '#E56B10')); ?>" class="enamel-color-picker" />
                            <p class="description"><?php _e('Color when a location is selected (applies to built-in marker styles only)', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <script>
                jQuery(document).ready(function($) {
                    $('.enamel-color-picker').wpColorPicker();
                    
                    // Map style toggle
                    $('#enamel_sl_map_style').on('change', function() {
                        if ($(this).val() === 'custom') {
                            $('#custom_map_style_row').show();
                        } else {
                            $('#custom_map_style_row').hide();
                        }
                    });
                    
                    // Marker style toggle - show/hide custom marker upload fields
                    $('#enamel_sl_marker_style').on('change', function() {
                        if ($(this).val() === 'custom') {
                            $('#custom_marker_row, #custom_active_marker_row').show();
                        } else {
                            $('#custom_marker_row, #custom_active_marker_row').hide();
                        }
                    });
                    
                    // Media uploader for custom markers
                    var mediaUploader;
                    $('.enamel-upload-btn').on('click', function(e) {
                        e.preventDefault();
                        var targetId = $(this).data('target');
                        var $input = $('#' + targetId);
                        var $removeBtn = $(this).siblings('.enamel-remove-btn');
                        var $preview = $('#preview_' + targetId);
                        
                        if (mediaUploader) {
                            mediaUploader.open();
                            return;
                        }
                        
                        mediaUploader = wp.media({
                            title: '<?php _e('Select Marker Image', 'enamel-store-locator'); ?>',
                            button: { text: '<?php _e('Use This Image', 'enamel-store-locator'); ?>' },
                            multiple: false,
                            library: { type: 'image' }
                        });
                        
                        mediaUploader.on('select', function() {
                            var attachment = mediaUploader.state().get('selection').first().toJSON();
                            $input.val(attachment.url);
                            $removeBtn.show();
                            $preview.show().find('img').attr('src', attachment.url);
                        });
                        
                        mediaUploader.open();
                    });
                    
                    // Remove marker image
                    $('.enamel-remove-btn').on('click', function(e) {
                        e.preventDefault();
                        var targetId = $(this).data('target');
                        $('#' + targetId).val('');
                        $(this).hide();
                        $('#preview_' + targetId).hide().find('img').attr('src', '');
                    });
                });
                </script>
                
                <h2><?php _e('Text & Labels', 'enamel-store-locator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="enamel_sl_header_main_title"><?php _e('Header Title', 'enamel-store-locator'); ?></label></th>
                        <td><input type="text" id="enamel_sl_header_main_title" name="enamel_sl_header_main_title" value="<?php echo esc_attr(get_option('enamel_sl_header_main_title', 'Find Your Nearest Location')); ?>" class="large-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_header_subtitle"><?php _e('Header Subtitle', 'enamel-store-locator'); ?></label></th>
                        <td><textarea id="enamel_sl_header_subtitle" name="enamel_sl_header_subtitle" class="large-text" rows="2"><?php echo esc_textarea(get_option('enamel_sl_header_subtitle', 'Quality dental care across Texas with convenient locations')); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_search_input_placeholder"><?php _e('Search Placeholder', 'enamel-store-locator'); ?></label></th>
                        <td><input type="text" id="enamel_sl_search_input_placeholder" name="enamel_sl_search_input_placeholder" value="<?php echo esc_attr(get_option('enamel_sl_search_input_placeholder', 'Enter address or zip code')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                
                <h2><?php _e('Button Visibility', 'enamel-store-locator'); ?></h2>
                <p class="description"><?php _e('Choose which buttons to display on location cards. All buttons are enabled by default.', 'enamel-store-locator'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><label for="enamel_sl_enable_schedule_button"><?php _e('Schedule Button', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <label>
                                <input type="hidden" name="enamel_sl_enable_schedule_button" value="0" />
                                <input type="checkbox" id="enamel_sl_enable_schedule_button" name="enamel_sl_enable_schedule_button" value="1" <?php checked(get_option('enamel_sl_enable_schedule_button', '1'), '1'); ?> />
                                <?php _e('Show "Schedule Online" button', 'enamel-store-locator'); ?>
                            </label>
                            <br />
                            <input type="text" id="enamel_sl_schedule_button_text" name="enamel_sl_schedule_button_text" value="<?php echo esc_attr(get_option('enamel_sl_schedule_button_text', 'Schedule Online')); ?>" class="regular-text" style="margin-top: 5px;" />
                            <p class="description"><?php _e('Button text label', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_enable_directions_button"><?php _e('Directions Button', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <label>
                                <input type="hidden" name="enamel_sl_enable_directions_button" value="0" />
                                <input type="checkbox" id="enamel_sl_enable_directions_button" name="enamel_sl_enable_directions_button" value="1" <?php checked(get_option('enamel_sl_enable_directions_button', '1'), '1'); ?> />
                                <?php _e('Show "Get Directions" button', 'enamel-store-locator'); ?>
                            </label>
                            <br />
                            <input type="text" id="enamel_sl_directions_button_text" name="enamel_sl_directions_button_text" value="<?php echo esc_attr(get_option('enamel_sl_directions_button_text', 'Get Directions')); ?>" class="regular-text" style="margin-top: 5px;" />
                            <p class="description"><?php _e('Button text label', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_enable_call_button"><?php _e('Call Button', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <label>
                                <input type="hidden" name="enamel_sl_enable_call_button" value="0" />
                                <input type="checkbox" id="enamel_sl_enable_call_button" name="enamel_sl_enable_call_button" value="1" <?php checked(get_option('enamel_sl_enable_call_button', '1'), '1'); ?> />
                                <?php _e('Show "Call Now" button', 'enamel-store-locator'); ?>
                            </label>
                            <br />
                            <input type="text" id="enamel_sl_call_button_text" name="enamel_sl_call_button_text" value="<?php echo esc_attr(get_option('enamel_sl_call_button_text', 'Call Now')); ?>" class="regular-text" style="margin-top: 5px;" />
                            <p class="description"><?php _e('Button text label', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Performance Settings', 'enamel-store-locator'); ?></h2>
                <p class="description"><?php _e('Optimize loading speed and performance. These settings are especially useful for pages with multiple store locators or heavy content.', 'enamel-store-locator'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><label for="enamel_sl_enable_lazy_load"><?php _e('Lazy Loading', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <label>
                                <input type="hidden" name="enamel_sl_enable_lazy_load" value="0" />
                                <input type="checkbox" id="enamel_sl_enable_lazy_load" name="enamel_sl_enable_lazy_load" value="1" <?php checked(get_option('enamel_sl_enable_lazy_load', '0'), '1'); ?> />
                                <?php _e('Enable lazy loading', 'enamel-store-locator'); ?>
                            </label>
                            <p class="description"><?php _e('Only load the Google Maps API when the store locator scrolls into view. Improves initial page load time.', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_defer_scripts"><?php _e('Defer Scripts', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <label>
                                <input type="hidden" name="enamel_sl_defer_scripts" value="0" />
                                <input type="checkbox" id="enamel_sl_defer_scripts" name="enamel_sl_defer_scripts" value="1" <?php checked(get_option('enamel_sl_defer_scripts', '0'), '1'); ?> />
                                <?php _e('Defer script loading', 'enamel-store-locator'); ?>
                            </label>
                            <p class="description"><?php _e('Load scripts with the defer attribute to prevent blocking page rendering.', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_enable_preconnect"><?php _e('Preconnect Hints', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <label>
                                <input type="hidden" name="enamel_sl_enable_preconnect" value="0" />
                                <input type="checkbox" id="enamel_sl_enable_preconnect" name="enamel_sl_enable_preconnect" value="1" <?php checked(get_option('enamel_sl_enable_preconnect', '0'), '1'); ?> />
                                <?php _e('Enable preconnect hints', 'enamel-store-locator'); ?>
                            </label>
                            <p class="description"><?php _e('Add preconnect hints for Google Maps domains to speed up initial connection.', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Branding page
     */
    public function branding_page() {
        // Available Google Fonts
        $google_fonts = array(
            'Montserrat' => 'Montserrat',
            'Rubik' => 'Rubik',
            'Open Sans' => 'Open Sans',
            'Roboto' => 'Roboto',
            'Lato' => 'Lato',
            'Poppins' => 'Poppins',
            'Inter' => 'Inter',
            'Nunito' => 'Nunito',
            'Raleway' => 'Raleway',
            'Work Sans' => 'Work Sans',
            'Playfair Display' => 'Playfair Display',
            'Merriweather' => 'Merriweather',
            'Source Sans Pro' => 'Source Sans Pro',
            'PT Sans' => 'PT Sans',
            'Oswald' => 'Oswald',
        );
        ?>
        <div class="wrap">
            <h1><?php _e('Branding & Appearance', 'enamel-store-locator'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('enamel_sl_branding'); ?>
                
                <h2><?php _e('Typography', 'enamel-store-locator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="enamel_sl_primary_font"><?php _e('Primary Font (Headings)', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <select id="enamel_sl_primary_font" name="enamel_sl_primary_font" class="regular-text">
                                <?php
                                $current_primary = get_option('enamel_sl_primary_font', 'Montserrat');
                                foreach ($google_fonts as $value => $label) {
                                    printf('<option value="%s" %s>%s</option>', esc_attr($value), selected($current_primary, $value, false), esc_html($label));
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_secondary_font"><?php _e('Secondary Font (Body Text)', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <select id="enamel_sl_secondary_font" name="enamel_sl_secondary_font" class="regular-text">
                                <?php
                                $current_secondary = get_option('enamel_sl_secondary_font', 'Rubik');
                                foreach ($google_fonts as $value => $label) {
                                    printf('<option value="%s" %s>%s</option>', esc_attr($value), selected($current_secondary, $value, false), esc_html($label));
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_font_size_base"><?php _e('Base Font Size (px)', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="number" id="enamel_sl_font_size_base" name="enamel_sl_font_size_base" value="<?php echo esc_attr(get_option('enamel_sl_font_size_base', '16')); ?>" min="12" max="24" class="small-text" />
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Colors', 'enamel-store-locator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="enamel_sl_primary_color"><?php _e('Primary Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_primary_color" name="enamel_sl_primary_color" value="<?php echo esc_attr(get_option('enamel_sl_primary_color', '#7D55C7')); ?>" class="enamel-color-picker" />
                            <p class="description"><?php _e('Main brand color (buttons, highlights)', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_secondary_color"><?php _e('Secondary Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_secondary_color" name="enamel_sl_secondary_color" value="<?php echo esc_attr(get_option('enamel_sl_secondary_color', '#5a3d96')); ?>" class="enamel-color-picker" />
                            <p class="description"><?php _e('Secondary brand color', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_accent_color"><?php _e('Accent Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_accent_color" name="enamel_sl_accent_color" value="<?php echo esc_attr(get_option('enamel_sl_accent_color', '#E56B10')); ?>" class="enamel-color-picker" />
                            <p class="description"><?php _e('Accent color for highlights and CTAs', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_background_color"><?php _e('Background Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_background_color" name="enamel_sl_background_color" value="<?php echo esc_attr(get_option('enamel_sl_background_color', '#FFFFFF')); ?>" class="enamel-color-picker" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_card_background"><?php _e('Card Background', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_card_background" name="enamel_sl_card_background" value="<?php echo esc_attr(get_option('enamel_sl_card_background', '#F8F9FA')); ?>" class="enamel-color-picker" />
                            <p class="description"><?php _e('Background color for location cards', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_header_background"><?php _e('Header Background', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_header_background" name="enamel_sl_header_background" value="<?php echo esc_attr(get_option('enamel_sl_header_background', '#7D55C7')); ?>" class="enamel-color-picker" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_text_primary"><?php _e('Primary Text Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_text_primary" name="enamel_sl_text_primary" value="<?php echo esc_attr(get_option('enamel_sl_text_primary', '#231942')); ?>" class="enamel-color-picker" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_text_secondary"><?php _e('Secondary Text Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_text_secondary" name="enamel_sl_text_secondary" value="<?php echo esc_attr(get_option('enamel_sl_text_secondary', '#6B7280')); ?>" class="enamel-color-picker" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_button_text_color"><?php _e('Button Text Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_button_text_color" name="enamel_sl_button_text_color" value="<?php echo esc_attr(get_option('enamel_sl_button_text_color', '#FFFFFF')); ?>" class="enamel-color-picker" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_header_text_color"><?php _e('Header Text Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_header_text_color" name="enamel_sl_header_text_color" value="<?php echo esc_attr(get_option('enamel_sl_header_text_color', '#FFFFFF')); ?>" class="enamel-color-picker" />
                            <p class="description"><?php _e('Text color for the header title and subtitle', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_card_text_color"><?php _e('Card Text Color', 'enamel-store-locator'); ?></label></th>
                        <td>
                            <input type="text" id="enamel_sl_card_text_color" name="enamel_sl_card_text_color" value="<?php echo esc_attr(get_option('enamel_sl_card_text_color', '#231942')); ?>" class="enamel-color-picker" />
                            <p class="description"><?php _e('Text color for location cards', 'enamel-store-locator'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.enamel-color-picker').wpColorPicker();
        });
        </script>
        <?php
    }
    
    /**
     * Shortcode handler - renders the store locator
     */
    public function render_store_locator($atts) {
        $atts = shortcode_atts(array(
            'center_lat' => get_option('enamel_sl_default_lat', 30.3072),
            'center_lng' => get_option('enamel_sl_default_lng', -97.7560),
            'zoom' => get_option('enamel_sl_default_zoom', 10),
            'height' => '500px',
            'width' => '100%'
        ), $atts, 'enamel_store_locator');
        
        // Get all active locations
        $locations = $this->get_all_locations();
        
        // Get settings
        $settings = $this->get_frontend_settings();
        
        // Generate unique container ID
        $container_id = 'enamel-store-locator-' . uniqid();
        
        // Build the HTML output
        ob_start();
        ?>
        <div id="<?php echo esc_attr($container_id); ?>" class="enamel-store-locator-container" style="width: <?php echo esc_attr($atts['width']); ?>; min-height: <?php echo esc_attr($atts['height']); ?>; --esl-primary: <?php echo esc_attr($settings['primary_color']); ?>; --esl-accent: <?php echo esc_attr($settings['accent_color']); ?>; --esl-bg: <?php echo esc_attr($settings['background_color']); ?>; --esl-card-bg: <?php echo esc_attr($settings['card_background']); ?>; --esl-header-bg: <?php echo esc_attr($settings['header_background']); ?>; --esl-text: <?php echo esc_attr($settings['text_primary']); ?>; --esl-text-secondary: <?php echo esc_attr($settings['text_secondary']); ?>; --esl-btn-text: <?php echo esc_attr($settings['button_text_color']); ?>; --esl-header-text: <?php echo esc_attr($settings['header_text_color']); ?>; --esl-card-text: <?php echo esc_attr($settings['card_text_color']); ?>; --esl-font-heading: <?php echo esc_attr($settings['primary_font']); ?>; --esl-font-body: <?php echo esc_attr($settings['secondary_font']); ?>; --esl-font-size: <?php echo esc_attr($settings['font_size_base']); ?>px;">
            
            <!-- SVG sprite: one definition, referenced everywhere via <use> -->
            <svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">
                <symbol id="esl-icon-search" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
                </symbol>
                <symbol id="esl-icon-crosshair" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="1"></circle>
                    <line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line>
                    <line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line>
                </symbol>
                <symbol id="esl-icon-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle>
                </symbol>
                <symbol id="esl-icon-phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </symbol>
                <symbol id="esl-icon-directions" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                </symbol>
            </svg>

            <div class="esl-header">
                <h2 class="esl-header-title"><?php echo esc_html($settings['header_main_title']); ?></h2>
                <p class="esl-header-subtitle"><?php echo esc_html($settings['header_subtitle']); ?></p>
            </div>
            
            <div class="esl-content">
                <div class="esl-sidebar">
                    <!-- Search Box -->
                    <div class="esl-search-box">
                        <h3 class="esl-search-title"><?php _e('Find Nearest Location', 'enamel-store-locator'); ?></h3>
                        <div class="esl-search-input-wrapper">
                            <svg class="esl-search-icon" aria-hidden="true"><use href="#esl-icon-search"></use></svg>
                            <input type="text" class="esl-search-input" id="<?php echo esc_attr($container_id); ?>-search" placeholder="<?php echo esc_attr($settings['search_input_placeholder']); ?>" />
                        </div>
                        <button type="button" class="esl-search-btn" id="<?php echo esc_attr($container_id); ?>-search-btn">
                            <svg width="16" height="16" aria-hidden="true"><use href="#esl-icon-search"></use></svg>
                            <?php _e('Search', 'enamel-store-locator'); ?>
                        </button>
                        <div class="esl-search-divider"><span><?php _e('or', 'enamel-store-locator'); ?></span></div>
                        <button type="button" class="esl-location-btn" id="<?php echo esc_attr($container_id); ?>-location-btn">
                            <svg width="16" height="16" aria-hidden="true"><use href="#esl-icon-crosshair"></use></svg>
                            <span class="esl-location-btn-text"><?php _e('Use My Location', 'enamel-store-locator'); ?></span>
                        </button>
                    </div>
                    
                    <!-- Location Cards -->
                    <div class="esl-locations-list" id="<?php echo esc_attr($container_id); ?>-locations">
                    <?php if (empty($locations)): ?>
                        <div class="esl-no-locations">
                            <p><?php _e('No locations found. Add locations in the WordPress admin.', 'enamel-store-locator'); ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($locations as $location): ?>
                            <div class="esl-location-card" data-lat="<?php echo esc_attr($location['lat']); ?>" data-lng="<?php echo esc_attr($location['lng']); ?>">
                                <div class="esl-location-name"><?php echo esc_html($location['name']); ?></div>
                                <div class="esl-location-info">
                                    <div class="esl-info-row">
                                        <svg class="esl-info-icon" aria-hidden="true"><use href="#esl-icon-pin"></use></svg>
                                        <span>
                                            <?php echo esc_html($location['address']); ?><br>
                                            <?php echo esc_html($location['city'] . ', ' . $location['state'] . ' ' . $location['zip']); ?>
                                        </span>
                                    </div>
                                    <?php if ($location['phone']): ?>
                                    <div class="esl-info-row">
                                        <svg class="esl-info-icon" aria-hidden="true"><use href="#esl-icon-phone"></use></svg>
                                        <span><?php echo esc_html($location['phone']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="esl-buttons">
                                    <?php if ($settings['enable_schedule_button'] && $location['booking_url']): ?>
                                        <a href="<?php echo esc_url($location['booking_url']); ?>" target="_blank" class="esl-btn esl-btn-accent">
                                            <?php echo esc_html($settings['schedule_button_text']); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($settings['enable_call_button'] && $location['phone']): ?>
                                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $location['phone'])); ?>" class="esl-btn esl-btn-outline">
                                            <?php echo esc_html($settings['call_button_text']); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($settings['enable_directions_button']): ?>
                                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr($location['lat']); ?>,<?php echo esc_attr($location['lng']); ?>" target="_blank" class="esl-btn esl-btn-primary" style="margin-left: auto;">
                                            <svg width="14" height="14" aria-hidden="true"><use href="#esl-icon-directions"></use></svg>
                                            <?php echo esc_html($settings['directions_button_text']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>
                
                <div class="esl-map" id="<?php echo esc_attr($container_id); ?>-map">
                    <!-- Google Map will be initialized here -->
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f0f0f0;">
                        <p style="color: #999; text-align: center;">
                            <?php if (!get_option('enamel_sl_google_maps_api_key')): ?>
                                <?php _e('Add your Google Maps API key in Settings to display the map', 'enamel-store-locator'); ?>
                            <?php else: ?>
                                <?php _e('Loading map...', 'enamel-store-locator'); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
        </div>
        
        <?php
        $api_key = get_option('enamel_sl_google_maps_api_key');
        if ($api_key):
            $map_id = get_option('enamel_sl_google_maps_map_id', '');
            $map_styles = array(
                'standard'  => '[{"featureType":"poi","stylers":[{"visibility":"off"}]}]',
                'silver'    => '[{"elementType":"geometry","stylers":[{"color":"#f5f5f5"}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#616161"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#f5f5f5"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry","stylers":[{"color":"#ffffff"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"color":"#dadada"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#c9c9c9"}]}]',
                'retro'     => '[{"elementType":"geometry","stylers":[{"color":"#ebe3cd"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#523735"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry","stylers":[{"color":"#f5f1e6"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"color":"#f8c967"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#b9d3c2"}]}]',
                'dark'      => '[{"elementType":"geometry","stylers":[{"color":"#212121"}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#757575"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#212121"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"color":"#2c2c2c"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"color":"#3c3c3c"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#000000"}]}]',
                'aubergine' => '[{"elementType":"geometry","stylers":[{"color":"#1d2c4d"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#8ec3b9"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#1a3646"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry","stylers":[{"color":"#304a7d"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"color":"#2c6675"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#171f29"}]}]',
            );
            $selected_style = $settings['map_style'];
            $style_json     = isset($map_styles[$selected_style]) ? $map_styles[$selected_style] : $map_styles['standard'];
            $style_data     = json_decode(($selected_style === 'custom' && !empty($settings['custom_map_style'])) ? $settings['custom_map_style'] : $style_json);
            $config = array(
                'locations'               => $locations,
                'defaultCenter'           => array('lat' => floatval($atts['center_lat']), 'lng' => floatval($atts['center_lng'])),
                'defaultZoom'             => intval($atts['zoom']),
                'primaryColor'            => sanitize_hex_color($settings['primary_color']),
                'accentColor'             => sanitize_hex_color($settings['accent_color']),
                'markerColor'             => sanitize_hex_color($settings['marker_color']),
                'activeMarkerColor'       => sanitize_hex_color($settings['active_marker_color']),
                'markerStyle'             => sanitize_text_field($settings['marker_style']),
                'customMarkerImage'       => esc_url_raw($settings['custom_marker_image']),
                'customActiveMarkerImage' => esc_url_raw($settings['custom_active_marker_image']),
                'showDirections'          => !empty($settings['enable_directions_button']),
                'showSchedule'            => !empty($settings['enable_schedule_button']),
                'showCall'                => !empty($settings['enable_call_button']),
                'mapId'                   => sanitize_text_field($map_id),
                'useAdvancedMarkers'      => !empty($map_id),
                'mapStyle'                => $style_data,
                'apiKey'                  => sanitize_text_field($api_key),
                'lazyLoad'                => get_option('enamel_sl_enable_lazy_load', '1') === '1',
            );
        ?>
        <script>
        window.enamelSLInstances = window.enamelSLInstances || {};
        window.enamelSLInstances[<?php echo wp_json_encode($container_id); ?>] = <?php echo wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        </script>
        <?php endif; // api_key check — frontend.js handles the rest ?>

        <?php
        return ob_get_clean();
    }

    /**
     * Register REST API endpoints
     */
    public function register_rest_api() {
        register_rest_route('enamel-sl/v1', '/locations', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'rest_get_locations'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * REST API: return all active locations
     */
    public function rest_get_locations() {
        return rest_ensure_response($this->get_all_locations());
    }

    /**
     * Get all active locations with sanitized data
     */
    private function get_all_locations() {
        $posts = get_posts(array(
            'post_type' => 'clinic_location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $locations = array();
        foreach ($posts as $post) {
            $active = get_post_meta($post->ID, '_enamel_sl_active', true);
            if ($active === '0') continue; // Skip inactive
            
            // Validate and sanitize coordinates
            $lat = floatval(get_post_meta($post->ID, '_enamel_sl_lat', true));
            $lng = floatval(get_post_meta($post->ID, '_enamel_sl_lng', true));
            if (!is_finite($lat) || $lat < -90 || $lat > 90) $lat = 0;
            if (!is_finite($lng) || $lng < -180 || $lng > 180) $lng = 0;
            
            // Validate rating
            $rating = floatval(get_post_meta($post->ID, '_enamel_sl_rating', true));
            if (!is_finite($rating) || $rating < 0 || $rating > 5) $rating = 0;
            
            // Sanitize all data for safe output with length limits
            $locations[] = array(
                'id' => intval($post->ID),
                'name' => mb_substr(sanitize_text_field($post->post_title), 0, 200),
                'address' => mb_substr(sanitize_text_field(get_post_meta($post->ID, '_enamel_sl_address', true)), 0, 300),
                'city' => mb_substr(sanitize_text_field(get_post_meta($post->ID, '_enamel_sl_city', true)), 0, 100),
                'state' => mb_substr(sanitize_text_field(get_post_meta($post->ID, '_enamel_sl_state', true)), 0, 50),
                'zip' => mb_substr(sanitize_text_field(get_post_meta($post->ID, '_enamel_sl_zip', true)), 0, 20),
                'phone' => mb_substr(sanitize_text_field(get_post_meta($post->ID, '_enamel_sl_phone', true)), 0, 30),
                'lat' => $lat,
                'lng' => $lng,
                'hours' => mb_substr(sanitize_textarea_field(get_post_meta($post->ID, '_enamel_sl_hours', true)), 0, 1000),
                'booking_url' => esc_url_raw(mb_substr(get_post_meta($post->ID, '_enamel_sl_booking_url', true), 0, 500)),
                'gmb_url' => esc_url_raw(mb_substr(get_post_meta($post->ID, '_enamel_sl_gmb_url', true), 0, 500)),
                'rating' => $rating,
            );
        }
        
        return $locations;
    }
    
    /**
     * Get frontend settings
     */
    private function get_frontend_settings() {
        return array(
            'header_main_title' => get_option('enamel_sl_header_main_title', 'Find Your Nearest Location'),
            'header_subtitle' => get_option('enamel_sl_header_subtitle', 'Quality dental care across Texas with convenient locations'),
            'search_input_placeholder' => get_option('enamel_sl_search_input_placeholder', 'Enter address or zip code') ?: 'Enter address or zip code',
            // Button visibility (default ON for all)
            'enable_schedule_button' => get_option('enamel_sl_enable_schedule_button', '1'),
            'enable_directions_button' => get_option('enamel_sl_enable_directions_button', '1'),
            'enable_call_button' => get_option('enamel_sl_enable_call_button', '1'),
            // Button text
            'schedule_button_text' => get_option('enamel_sl_schedule_button_text', 'Schedule Online'),
            'directions_button_text' => get_option('enamel_sl_directions_button_text', 'Get Directions'),
            'call_button_text' => get_option('enamel_sl_call_button_text', 'Call Now'),
            // Map styling
            'map_style' => get_option('enamel_sl_map_style', 'standard'),
            'custom_map_style' => get_option('enamel_sl_custom_map_style', ''),
            // Marker settings
            'marker_color' => get_option('enamel_sl_marker_color', '#7D55C7'),
            'marker_style' => get_option('enamel_sl_marker_style', 'pin'),
            'active_marker_color' => get_option('enamel_sl_active_marker_color', '#E56B10'),
            'custom_marker_image' => get_option('enamel_sl_custom_marker_image', ''),
            'custom_active_marker_image' => get_option('enamel_sl_custom_active_marker_image', ''),
            // Colors
            'primary_color' => get_option('enamel_sl_primary_color', '#7D55C7'),
            'secondary_color' => get_option('enamel_sl_secondary_color', '#5a3d96'),
            'accent_color' => get_option('enamel_sl_accent_color', '#E56B10'),
            'background_color' => get_option('enamel_sl_background_color', '#FFFFFF'),
            'card_background' => get_option('enamel_sl_card_background', '#F8F9FA'),
            'header_background' => get_option('enamel_sl_header_background', '#7D55C7'),
            'text_primary' => get_option('enamel_sl_text_primary', '#231942'),
            'text_secondary' => get_option('enamel_sl_text_secondary', '#6B7280'),
            'button_text_color' => get_option('enamel_sl_button_text_color', '#FFFFFF'),
            'header_text_color' => get_option('enamel_sl_header_text_color', '#FFFFFF'),
            'card_text_color' => get_option('enamel_sl_card_text_color', '#231942'),
            // Fonts
            'primary_font' => get_option('enamel_sl_primary_font', 'Montserrat'),
            'secondary_font' => get_option('enamel_sl_secondary_font', 'Rubik'),
            'font_size_base' => get_option('enamel_sl_font_size_base', '16'),
            // Legacy aliases
            'card_bg' => get_option('enamel_sl_card_background', '#F8F9FA'),
            'border_color' => '#E5E7EB',
        );
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'enamel_store_locator')) {
            // Load frontend CSS (external file for WP Rocket compatibility)
            wp_enqueue_style('enamel-sl-frontend', ENAMEL_SL_PLUGIN_URL . 'assets/frontend.css', array(), ENAMEL_SL_VERSION);
            wp_enqueue_script('enamel-sl-frontend', ENAMEL_SL_PLUGIN_URL . 'assets/frontend.js', array(), ENAMEL_SL_VERSION, true);

            // Google Fonts removed — theme already loads Montserrat + Rubik

            // Add preconnect hints for Google domains (if enabled - default ON)
            $enable_preconnect = get_option('enamel_sl_enable_preconnect', '1');
            if ($enable_preconnect === '1') {
                add_action('wp_head', array($this, 'add_preconnect_hints'), 1);
            }
        }
    }
    
    /**
     * Add preconnect hints for Google domains
     */
    public function add_preconnect_hints() {
        echo '<link rel="preconnect" href="https://maps.googleapis.com" crossorigin>' . "\n";
        echo '<link rel="preconnect" href="https://maps.gstatic.com" crossorigin>' . "\n";
        echo '<link rel="dns-prefetch" href="https://maps.googleapis.com">' . "\n";
    }
    
    /**
     * WP Rocket RUCSS Safelist - preserve our CSS classes
     */
    public function wp_rocket_safelist($safelist) {
        $safelist[] = '.enamel-store-locator-container';
        $safelist[] = '.esl-(.*)';
        $safelist[] = '/enamel-store-locator/assets/frontend.css';
        return $safelist;
    }
    
    /**
     * WP Rocket CSS exclusion - exclude our CSS from optimization
     */
    public function wp_rocket_exclude_css($excluded) {
        $excluded[] = '/wp-content/plugins/enamel-store-locator/assets/frontend.css';
        return $excluded;
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        // On our admin pages or location edit screen
        if (strpos($hook, 'enamel-store-locator') !== false || $post_type === 'clinic_location') {
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_media(); // For custom marker image upload
            
            // Add inline script for location meta box
            wp_add_inline_script('jquery', '
                jQuery(document).ready(function($) {
                    // Color pickers
                    $(".enamel-color-picker").wpColorPicker();
                    
                    // Fetch place details
                    $("#enamel_sl_fetch_place").on("click", function() {
                        var placeId = $("#enamel_sl_place_id").val();
                        var $status = $("#enamel_sl_fetch_status");
                        var $btn = $(this);
                        
                        if (!placeId) {
                            $status.html("<span style=\"color: red;\">Please enter a Place ID</span>");
                            return;
                        }
                        
                        $btn.prop("disabled", true);
                        $status.html("<span>Fetching...</span>");
                        
                        $.ajax({
                            url: ajaxurl,
                            type: "POST",
                            data: {
                                action: "enamel_fetch_place_details",
                                place_id: placeId,
                                nonce: "' . wp_create_nonce('enamel_sl_admin_nonce') . '"
                            },
                            success: function(response) {
                                $btn.prop("disabled", false);
                                if (response.success) {
                                    var data = response.data;
                                    
                                    // Update title if empty
                                    if (!$("#title").val() && data.name) {
                                        $("#title").val(data.name);
                                    }
                                    
                                    // Fill in fields
                                    if (data.address) $("#enamel_sl_address").val(data.address);
                                    if (data.city) $("#enamel_sl_city").val(data.city);
                                    if (data.state) $("#enamel_sl_state").val(data.state);
                                    if (data.zip) $("#enamel_sl_zip").val(data.zip);
                                    if (data.phone) $("#enamel_sl_phone").val(data.phone);
                                    if (data.lat) $("#enamel_sl_lat").val(data.lat);
                                    if (data.lng) $("#enamel_sl_lng").val(data.lng);
                                    if (data.hours) $("#enamel_sl_hours").val(data.hours);
                                    if (data.rating) $("#enamel_sl_rating").val(data.rating);
                                    if (data.gmb_url) $("#enamel_sl_gmb_url").val(data.gmb_url);
                                    
                                    $status.html("<span style=\"color: green;\">✓ Details fetched successfully!</span>");
                                } else {
                                    $status.html("<span style=\"color: red;\">" + response.data + "</span>");
                                }
                            },
                            error: function() {
                                $btn.prop("disabled", false);
                                $status.html("<span style=\"color: red;\">Request failed. Please try again.</span>");
                            }
                        });
                    });
                });
            ');
        }
    }
    
    /**
     * Sanitization callbacks
     */
    public function sanitize_latitude($value) {
        $lat = floatval($value);
        return max(-90, min(90, $lat));
    }
    
    public function sanitize_longitude($value) {
        $lng = floatval($value);
        return max(-180, min(180, $lng));
    }
    
    public function sanitize_zoom_level($value) {
        $zoom = intval($value);
        return max(1, min(20, $zoom));
    }
    
    public function sanitize_map_type($value) {
        $allowed_types = array('roadmap', 'satellite', 'hybrid', 'terrain');
        return in_array($value, $allowed_types) ? $value : 'roadmap';
    }
    
    public function sanitize_checkbox($value) {
        return ($value === '1' || $value === 1) ? '1' : '0';
    }
    
    public function sanitize_json($value) {
        if (empty($value)) {
            return '';
        }
        // Limit payload size to 32KB
        if (strlen($value) > 32768) {
            return '';
        }
        $decoded = json_decode($value, true);
        // Must be valid JSON and an array (map styles are always arrays)
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return '';
        }
        // Re-encode to ensure clean output
        return wp_json_encode($decoded);
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    EnamelStoreLocator::get_instance();
});

// ---------------------------------------------------------------------------
// Auto-updater — checks GitHub releases for new versions
// ---------------------------------------------------------------------------
add_filter( 'pre_set_site_transient_update_plugins', 'enamel_sl_check_for_update' );

function enamel_sl_check_for_update( $transient ) {
    if ( empty( $transient->checked ) ) {
        return $transient;
    }

    $plugin_slug = plugin_basename( __FILE__ );
    $api_url     = 'https://api.github.com/repos/drharcho/enamel-store-locator/releases/latest';

    $response = wp_remote_get( $api_url, array(
        'headers' => array(
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
        ),
        'timeout' => 10,
    ) );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return $transient;
    }

    $release = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( empty( $release['tag_name'] ) ) {
        return $transient;
    }

    $latest_version = ltrim( $release['tag_name'], 'v' );

    if ( version_compare( $latest_version, ENAMEL_SL_VERSION, '>' ) ) {
        // Use the uploaded zip asset if available, fall back to zipball
        $package = $release['zipball_url'];
        if ( ! empty( $release['assets'] ) ) {
            foreach ( $release['assets'] as $asset ) {
                if ( pathinfo( $asset['name'], PATHINFO_EXTENSION ) === 'zip' ) {
                    $package = $asset['browser_download_url'];
                    break;
                }
            }
        }

        $transient->response[ $plugin_slug ] = (object) array(
            'slug'        => dirname( $plugin_slug ),
            'plugin'      => $plugin_slug,
            'new_version' => $latest_version,
            'url'         => 'https://github.com/drharcho/enamel-store-locator',
            'package'     => $package,
        );
    }

    return $transient;
}

// ---------------------------------------------------------------------------
// Fix folder rename issue when WordPress installs updates from GitHub zips
// ---------------------------------------------------------------------------
add_filter( 'upgrader_source_selection', 'enamel_sl_fix_update_folder', 10, 4 );

function enamel_sl_fix_update_folder( $source, $remote_source, $upgrader, $extra ) {
    if ( ! isset( $extra['plugin'] ) || strpos( $extra['plugin'], 'enamel-store-locator' ) === false ) {
        return $source;
    }

    $correct = trailingslashit( $remote_source ) . 'enamel-store-locator/';

    if ( $source !== $correct ) {
        global $wp_filesystem;
        if ( $wp_filesystem->move( $source, $correct ) ) {
            return $correct;
        }
    }

    return $source;
}
