<?php
/**
 * Plugin Name: Enamel Store Locator
 * Plugin URI: https://enamel-dentistry.com/plugins/store-locator
 * Description: Intelligent store locator with Google Maps integration, customizable branding, and comprehensive location management for dental practices.
 * Version: 1.0.0
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
define('ENAMEL_SL_VERSION', '1.0.0');

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
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Create custom post type for locations immediately
        $this->create_location_post_type();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables and default settings
        $this->create_database_tables();
        $this->create_default_settings();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up (but keep data)
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
        // Include admin pages file
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/admin-pages.php';
        
        // Main menu page
        add_menu_page(
            __('Store Locator', 'enamel-store-locator'),
            __('Store Locator', 'enamel-store-locator'),
            'manage_options',
            'enamel-store-locator',
            array('EnamelStoreLocatorAdmin', 'dashboard_page'),
            'dashicons-location-alt',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'enamel-store-locator',
            __('Settings', 'enamel-store-locator'),
            __('Settings', 'enamel-store-locator'),
            'manage_options',
            'enamel-store-locator-settings',
            array('EnamelStoreLocatorAdmin', 'settings_page')
        );
        
        // Map Settings submenu
        add_submenu_page(
            'enamel-store-locator',
            __('Map Settings', 'enamel-store-locator'),
            __('Map Settings', 'enamel-store-locator'),
            'manage_options',
            'enamel-store-locator-map',
            array('EnamelStoreLocatorAdmin', 'map_settings_page')
        );
        
        // Locations submenu (points to custom post type)
        add_submenu_page(
            'enamel-store-locator',
            __('Manage Locations', 'enamel-store-locator'),
            __('Locations', 'enamel-store-locator'),
            'manage_options',
            'edit.php?post_type=clinic_location'
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        // Register all settings with proper sanitization for the new admin interface
        $this->register_all_settings();
        
        // Register settings sections and fields for legacy interface (if needed)
        $this->register_general_settings();
        $this->register_content_settings();
        $this->register_color_settings();
        $this->register_map_settings();
    }
    
    /**
     * Register all settings for the new admin interface
     */
    private function register_all_settings() {
        // All settings with their sanitization callbacks
        $all_settings = array(
            'google_maps_api_key' => 'sanitize_text_field',
            'default_lat' => array($this, 'sanitize_latitude'),
            'default_lng' => array($this, 'sanitize_longitude'),
            'default_zoom' => array($this, 'sanitize_zoom_level'),
            'default_radius' => 'absint',
            'header_main_title' => 'sanitize_text_field',
            'header_subtitle' => 'sanitize_textarea_field',
            'search_section_title' => 'sanitize_text_field',
            'search_input_placeholder' => 'sanitize_text_field',
            'search_button_text' => 'sanitize_text_field',
            'location_button_text' => 'sanitize_text_field',
            'directions_button_text' => 'sanitize_text_field',
            'call_button_text' => 'sanitize_text_field',
            'schedule_button_text' => 'sanitize_text_field',
            'schedule_link_url' => 'esc_url_raw',
            'footer_text' => 'sanitize_text_field',
            'primary_color' => 'sanitize_hex_color',
            'accent_color' => 'sanitize_hex_color',
            'background_color' => 'sanitize_hex_color',
            'card_background' => 'sanitize_hex_color',
            'primary_text' => 'sanitize_hex_color',
            'secondary_text' => 'sanitize_hex_color',
            'marker_color' => 'sanitize_hex_color',
            'map_type' => array($this, 'sanitize_map_type'),
            'enable_clustering' => array($this, 'sanitize_checkbox')
        );
        
        // Register each setting with the enamel_sl_settings group
        foreach ($all_settings as $setting => $sanitize_callback) {
            register_setting(
                'enamel_sl_settings',
                'enamel_sl_' . $setting,
                array(
                    'sanitize_callback' => $sanitize_callback,
                    'default' => $this->get_default_value($setting)
                )
            );
        }
    }
    
    /**
     * Get default value for a setting
     */
    private function get_default_value($setting) {
        $defaults = array(
            'google_maps_api_key' => '',
            'default_lat' => '30.3072',
            'default_lng' => '-97.7560',
            'default_zoom' => 10,
            'default_radius' => 25,
            'header_main_title' => 'Find Your Nearest Location',
            'header_subtitle' => 'Quality dental care across Texas with convenient locations',
            'search_section_title' => 'Find Nearest Location',
            'search_input_placeholder' => 'Enter address or zip code',
            'search_button_text' => 'Search',
            'location_button_text' => 'Use My Location',
            'directions_button_text' => 'Get Directions',
            'call_button_text' => 'Call',
            'schedule_button_text' => 'Schedule Online',
            'schedule_link_url' => '',
            'footer_text' => 'Established in 2016 • Quality dental care using the latest technology',
            'primary_color' => '#7D55C7',
            'accent_color' => '#E56B10',
            'background_color' => '#FFFFFF',
            'card_background' => '#F8F9FA',
            'primary_text' => '#231942',
            'secondary_text' => '#6B7280',
            'marker_color' => '#7D55C7',
            'map_type' => 'roadmap',
            'enable_clustering' => false
        );
        
        return isset($defaults[$setting]) ? $defaults[$setting] : '';
    }
    
    /**
     * Sanitize map type
     */
    public function sanitize_map_type($value) {
        $allowed = array('roadmap', 'satellite', 'hybrid', 'terrain');
        return in_array($value, $allowed) ? $value : 'roadmap';
    }
    
    /**
     * Register general settings
     */
    private function register_general_settings() {
        // General settings section
        add_settings_section(
            'enamel_sl_general',
            __('General Settings', 'enamel-store-locator'),
            array($this, 'general_section_callback'),
            'enamel-store-locator'
        );
        
        // Google Maps API Key
        add_settings_field(
            'google_maps_api_key',
            __('Google Maps API Key', 'enamel-store-locator'),
            array($this, 'api_key_field_callback'),
            'enamel-store-locator',
            'enamel_sl_general'
        );
        
        register_setting('enamel_sl_general', 'enamel_sl_google_maps_api_key', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        // Default Map Center
        add_settings_field(
            'default_center',
            __('Default Map Center', 'enamel-store-locator'),
            array($this, 'default_center_field_callback'),
            'enamel-store-locator',
            'enamel_sl_general'
        );
        
        register_setting('enamel_sl_general', 'enamel_sl_default_lat', array(
            'sanitize_callback' => array($this, 'sanitize_latitude')
        ));
        register_setting('enamel_sl_general', 'enamel_sl_default_lng', array(
            'sanitize_callback' => array($this, 'sanitize_longitude')
        ));
        register_setting('enamel_sl_general', 'enamel_sl_default_zoom', array(
            'sanitize_callback' => array($this, 'sanitize_zoom_level')
        ));
    }
    
    /**
     * Register content customization settings
     */
    private function register_content_settings() {
        // Content settings section
        add_settings_section(
            'enamel_sl_content',
            __('Content Customization', 'enamel-store-locator'),
            array($this, 'content_section_callback'),
            'enamel-store-locator-content'
        );
        
        // Header text settings
        $content_fields = array(
            'header_main_title' => __('Main Title', 'enamel-store-locator'),
            'header_subtitle' => __('Subtitle', 'enamel-store-locator'),
            'search_section_title' => __('Search Section Title', 'enamel-store-locator'),
            'search_input_placeholder' => __('Search Input Placeholder', 'enamel-store-locator'),
            'search_button_text' => __('Search Button Text', 'enamel-store-locator'),
            'location_button_text' => __('Use Location Button Text', 'enamel-store-locator'),
            'footer_text' => __('Footer Text', 'enamel-store-locator'),
            'directions_button_text' => __('Directions Button Text', 'enamel-store-locator'),
            'call_button_text' => __('Call Button Text', 'enamel-store-locator')
        );
        
        foreach ($content_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'text_field_callback'),
                'enamel-store-locator-content',
                'enamel_sl_content',
                array('field' => $field)
            );
            
            register_setting('enamel_sl_content', 'enamel_sl_' . $field, array(
                'sanitize_callback' => 'sanitize_text_field'
            ));
        }
    }
    
    /**
     * Register color scheme settings
     */
    private function register_color_settings() {
        // Color settings section
        add_settings_section(
            'enamel_sl_colors',
            __('Color Scheme', 'enamel-store-locator'),
            array($this, 'colors_section_callback'),
            'enamel-store-locator-colors'
        );
        
        // Color fields
        $color_fields = array(
            'primary_color' => __('Primary Color (Headers)', 'enamel-store-locator'),
            'accent_color' => __('Accent Color (Call Buttons)', 'enamel-store-locator'),
            'background_color' => __('Background Color', 'enamel-store-locator'),
            'card_background' => __('Card Background', 'enamel-store-locator'),
            'primary_text' => __('Primary Text Color', 'enamel-store-locator'),
            'secondary_text' => __('Secondary Text Color', 'enamel-store-locator')
        );
        
        foreach ($color_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'color_field_callback'),
                'enamel-store-locator-colors',
                'enamel_sl_colors',
                array('field' => $field)
            );
            
            register_setting('enamel_sl_colors', 'enamel_sl_' . $field, array(
                'sanitize_callback' => 'sanitize_hex_color'
            ));
        }
    }
    
    /**
     * Register map settings
     */
    private function register_map_settings() {
        // Map settings section
        add_settings_section(
            'enamel_sl_map',
            __('Map Configuration', 'enamel-store-locator'),
            array($this, 'map_section_callback'),
            'enamel-store-locator-map'
        );
        
        // Map type
        add_settings_field(
            'map_type',
            __('Default Map Type', 'enamel-store-locator'),
            array($this, 'map_type_field_callback'),
            'enamel-store-locator-map',
            'enamel_sl_map'
        );
        
        register_setting('enamel_sl_map', 'enamel_sl_map_type', array(
            'sanitize_callback' => array($this, 'sanitize_map_type')
        ));
        
        // Marker settings
        add_settings_field(
            'marker_color',
            __('Marker Color', 'enamel-store-locator'),
            array($this, 'color_field_callback'),
            'enamel-store-locator-map',
            'enamel_sl_map',
            array('field' => 'marker_color')
        );
        
        register_setting('enamel_sl_map', 'enamel_sl_marker_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        
        // Clustering
        add_settings_field(
            'enable_clustering',
            __('Enable Marker Clustering', 'enamel-store-locator'),
            array($this, 'checkbox_field_callback'),
            'enamel-store-locator-map',
            'enamel_sl_map',
            array('field' => 'enable_clustering')
        );
        
        register_setting('enamel_sl_map', 'enamel_sl_enable_clustering', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
    }
    
    /**
     * Shortcode handler - renders the React store locator
     */
    public function render_store_locator($atts) {
        // Parse and sanitize shortcode attributes
        $atts = shortcode_atts(array(
            'center_lat' => get_option('enamel_sl_default_lat', 30.3072),
            'center_lng' => get_option('enamel_sl_default_lng', -97.7560),
            'zoom' => get_option('enamel_sl_default_zoom', 10),
            'height' => '500px',
            'width' => '100%'
        ), $atts, 'enamel_store_locator');
        
        // Sanitize attributes
        $atts['center_lat'] = $this->sanitize_latitude($atts['center_lat']);
        $atts['center_lng'] = $this->sanitize_longitude($atts['center_lng']);
        $atts['zoom'] = $this->sanitize_zoom_level($atts['zoom']);
        $atts['height'] = sanitize_text_field($atts['height']);
        $atts['width'] = sanitize_text_field($atts['width']);
        
        // Enqueue React app assets
        $this->enqueue_react_app();
        
        // Pass settings to React app
        wp_localize_script('enamel-store-locator-app', 'enamelStoreLocatorSettings', array(
            'apiUrl' => rest_url('enamel-store-locator/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'settings' => $this->get_all_settings(),
            'defaultCenter' => array(
                'lat' => floatval($atts['center_lat']),
                'lng' => floatval($atts['center_lng'])
            ),
            'defaultZoom' => intval($atts['zoom']),
            'googleMapsApiKey' => get_option('enamel_sl_google_maps_api_key', '')
        ));
        
        // Generate unique container ID
        $container_id = 'enamel-store-locator-' . uniqid();
        
        return sprintf(
            '<div id="%s" class="enamel-store-locator-container" style="width: %s; height: %s;"></div>',
            esc_attr($container_id),
            esc_attr($atts['width']),
            esc_attr($atts['height'])
        );
    }
    
    /**
     * Enqueue React app assets
     */
    private function enqueue_react_app() {
        // Check if build files exist before enqueueing
        $js_file = ENAMEL_SL_PLUGIN_PATH . 'build/static/js/main.js';
        $css_file = ENAMEL_SL_PLUGIN_PATH . 'build/static/css/main.css';
        
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'enamel-store-locator-app',
                ENAMEL_SL_PLUGIN_URL . 'build/static/js/main.js',
                array('wp-element'),
                ENAMEL_SL_VERSION,
                true
            );
        }
        
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'enamel-store-locator-styles',
                ENAMEL_SL_PLUGIN_URL . 'build/static/css/main.css',
                array(),
                ENAMEL_SL_VERSION
            );
        }
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Locations endpoint
        register_rest_route('enamel-store-locator/v1', '/locations', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_locations'),
                'permission_callback' => array($this, 'check_read_permissions'),
                'args' => array()
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_location'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => array(
                    'name' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'address' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'city' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'state' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'zipCode' => array(
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'phone' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'lat' => array(
                        'required' => true,
                        'type' => 'number',
                        'sanitize_callback' => array($this, 'sanitize_latitude')
                    ),
                    'lng' => array(
                        'required' => true,
                        'type' => 'number',
                        'sanitize_callback' => array($this, 'sanitize_longitude')
                    )
                )
            )
        ));
        
        register_rest_route('enamel-store-locator/v1', '/locations/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_location'),
                'permission_callback' => array($this, 'check_read_permissions'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint'
                    )
                )
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_location'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint'
                    ),
                    'name' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'lat' => array(
                        'type' => 'number',
                        'sanitize_callback' => array($this, 'sanitize_latitude')
                    ),
                    'lng' => array(
                        'type' => 'number',
                        'sanitize_callback' => array($this, 'sanitize_longitude')
                    )
                )
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_location'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint'
                    )
                )
            )
        ));
        
        // Settings endpoint
        register_rest_route('enamel-store-locator/v1', '/settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => array($this, 'check_read_permissions')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'update_settings'),
                'permission_callback' => array($this, 'check_admin_permissions'),
                'args' => array(
                    'google_maps_api_key' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ),
                    'primary_color' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_hex_color'
                    ),
                    'accent_color' => array(
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_hex_color'
                    )
                )
            )
        ));
    }
    
    /**
     * Check admin permissions for REST API
     */
    public function check_admin_permissions($request = null) {
        // Check if user is logged in and has capability
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Check nonce for additional security when request is available
        if ($request) {
            $nonce = $request->get_header('X-WP-Nonce');
            if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
                return false;
            }
        }
        
        return current_user_can('manage_options');
    }
    
    /**
     * Check if user can read public data
     */
    public function check_read_permissions($request = null) {
        return true; // Public read access for frontend
    }
    
    /**
     * REST API Callbacks - Load from separate file
     */
    
    public function get_locations($request) {
        require_once ENAMEL_SL_PLUGIN_PATH . 'api/rest-endpoints.php';
        return EnamelStoreLocatorAPI::get_locations($request);
    }
    
    public function get_location($request) {
        require_once ENAMEL_SL_PLUGIN_PATH . 'api/rest-endpoints.php';
        return EnamelStoreLocatorAPI::get_location($request);
    }
    
    public function create_location($request) {
        require_once ENAMEL_SL_PLUGIN_PATH . 'api/rest-endpoints.php';
        return EnamelStoreLocatorAPI::create_location($request);
    }
    
    public function update_location($request) {
        require_once ENAMEL_SL_PLUGIN_PATH . 'api/rest-endpoints.php';
        return EnamelStoreLocatorAPI::update_location($request);
    }
    
    public function delete_location($request) {
        require_once ENAMEL_SL_PLUGIN_PATH . 'api/rest-endpoints.php';
        return EnamelStoreLocatorAPI::delete_location($request);
    }
    
    public function get_settings($request) {
        require_once ENAMEL_SL_PLUGIN_PATH . 'api/rest-endpoints.php';
        return EnamelStoreLocatorAPI::get_settings($request);
    }
    
    public function update_settings($request) {
        require_once ENAMEL_SL_PLUGIN_PATH . 'api/rest-endpoints.php';
        return EnamelStoreLocatorAPI::update_settings($request);
    }
    
    /**
     * Get all plugin settings
     */
    private function get_all_settings() {
        $default_settings = array(
            'header_main_title' => 'Find Your Nearest Location',
            'header_subtitle' => 'Quality dental care across Texas with convenient locations',
            'search_section_title' => 'Find Nearest Location',
            'search_input_placeholder' => 'Enter address or zip code',
            'search_button_text' => 'Search',
            'location_button_text' => 'Use My Location',
            'footer_text' => 'Established in 2016 • Quality dental care using the latest technology',
            'directions_button_text' => 'Get Directions',
            'call_button_text' => 'Call',
            'primary_color' => '#7D55C7',
            'accent_color' => '#E56B10',
            'background_color' => '#FFFFFF',
            'card_background' => '#F8F9FA',
            'primary_text' => '#231942',
            'secondary_text' => '#6B7280',
            'map_type' => 'roadmap',
            'marker_color' => '#7D55C7',
            'enable_clustering' => true
        );
        
        $settings = array();
        foreach ($default_settings as $key => $default_value) {
            $settings[$key] = get_option('enamel_sl_' . $key, $default_value);
        }
        
        return $settings;
    }
    
    /**
     * Create default settings on activation
     */
    private function create_default_settings() {
        $default_settings = array(
            'enamel_sl_header_main_title' => 'Find Your Nearest Location',
            'enamel_sl_header_subtitle' => 'Quality dental care across Texas with convenient locations',
            'enamel_sl_search_section_title' => 'Find Nearest Location',
            'enamel_sl_search_input_placeholder' => 'Enter address or zip code',
            'enamel_sl_search_button_text' => 'Search',
            'enamel_sl_location_button_text' => 'Use My Location',
            'enamel_sl_footer_text' => 'Established in 2016 • Quality dental care using the latest technology',
            'enamel_sl_directions_button_text' => 'Get Directions',
            'enamel_sl_call_button_text' => 'Call',
            'enamel_sl_primary_color' => '#7D55C7',
            'enamel_sl_accent_color' => '#E56B10',
            'enamel_sl_background_color' => '#FFFFFF',
            'enamel_sl_card_background' => '#F8F9FA',
            'enamel_sl_primary_text' => '#231942',
            'enamel_sl_secondary_text' => '#6B7280',
            'enamel_sl_default_lat' => 30.3072,
            'enamel_sl_default_lng' => -97.7560,
            'enamel_sl_default_zoom' => 10,
            'enamel_sl_map_type' => 'roadmap',
            'enamel_sl_marker_color' => '#7D55C7',
            'enamel_sl_enable_clustering' => true
        );
        
        foreach ($default_settings as $option_name => $default_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $default_value);
            }
        }
    }
    
    /**
     * Create database tables (placeholder - we'll use the Node.js API)
     */
    private function create_database_tables() {
        // Database creation will be handled by the Node.js backend
        // This is a placeholder for any WordPress-specific setup
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
        return !empty($value) ? 1 : 0;
    }
    
    // Admin page callbacks now handled directly by static methods in add_admin_menu
    
    /**
     * Settings field callbacks - Load from separate file
     */
    
    public function general_section_callback() {
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        EnamelStoreLocatorFields::general_section_callback();
    }
    
    public function content_section_callback() {
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        EnamelStoreLocatorFields::content_section_callback();
    }
    
    public function colors_section_callback() {
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        EnamelStoreLocatorFields::colors_section_callback();
    }
    
    public function map_section_callback() {
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        EnamelStoreLocatorFields::map_section_callback();
    }
    
    public function api_key_field_callback() {
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        EnamelStoreLocatorFields::api_key_field_callback();
    }
    
    public function default_center_field_callback() {
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        EnamelStoreLocatorFields::default_center_field_callback();
    }
    
    public function text_field_callback($args) {
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        EnamelStoreLocatorFields::text_field_callback($args);
    }
    
    public function color_field_callback($args) {
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        EnamelStoreLocatorFields::color_field_callback($args);
    }
    
    public function map_type_field_callback() {
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        EnamelStoreLocatorFields::map_type_field_callback();
    }
    
    public function checkbox_field_callback($args) {
        require_once ENAMEL_SL_PLUGIN_PATH . 'admin/field-callbacks.php';
        EnamelStoreLocatorFields::checkbox_field_callback($args);
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue if shortcode is present
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'enamel_store_locator')) {
            $this->enqueue_react_app();
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        // Only enqueue on our admin pages
        if (strpos($hook, 'enamel-store-locator') !== false) {
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_style('wp-color-picker');
            
            wp_enqueue_script(
                'enamel-store-locator-admin',
                ENAMEL_SL_PLUGIN_URL . 'assets/admin.js',
                array('jquery', 'wp-color-picker'),
                ENAMEL_SL_VERSION,
                true
            );
        }
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    EnamelStoreLocator::get_instance();
});

?>