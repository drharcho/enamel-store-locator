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
        
        // Create custom post type for locations
        $this->create_location_post_type();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
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
        
        // Settings submenu
        add_submenu_page(
            'enamel-store-locator',
            __('Settings', 'enamel-store-locator'),
            __('Settings', 'enamel-store-locator'),
            'manage_options',
            'enamel-store-locator-settings',
            array($this, 'settings_page')
        );
        
        // Map Settings submenu
        add_submenu_page(
            'enamel-store-locator',
            __('Map Settings', 'enamel-store-locator'),
            __('Map Settings', 'enamel-store-locator'),
            'manage_options',
            'enamel-store-locator-map',
            array($this, 'map_settings_page')
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
        // Register all settings with sanitization
        $this->register_settings();
    }
    
    /**
     * Register all plugin settings
     */
    private function register_settings() {
        $settings = array(
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
            'primary_color' => 'sanitize_hex_color',
            'accent_color' => 'sanitize_hex_color',
            'background_color' => 'sanitize_hex_color',
            'card_background' => 'sanitize_hex_color',
            'primary_text' => 'sanitize_hex_color',
            'secondary_text' => 'sanitize_hex_color',
            'map_type' => array($this, 'sanitize_map_type'),
            'marker_color' => 'sanitize_hex_color',
            'enable_clustering' => array($this, 'sanitize_checkbox'),
        );
        
        foreach ($settings as $option_name => $sanitize_callback) {
            register_setting('enamel_sl_settings', 'enamel_sl_' . $option_name, array(
                'sanitize_callback' => $sanitize_callback
            ));
        }
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Enamel Store Locator', 'enamel-store-locator'); ?></h1>
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('Quick Setup', 'enamel-store-locator'); ?></h2>
                <div class="inside">
                    <h3><?php _e('How to Use:', 'enamel-store-locator'); ?></h3>
                    <ol>
                        <li><?php _e('Add your Google Maps API key in Settings', 'enamel-store-locator'); ?></li>
                        <li><?php _e('Add clinic locations in Locations', 'enamel-store-locator'); ?></li>
                        <li><?php _e('Copy this shortcode to any page:', 'enamel-store-locator'); ?> <code>[enamel_store_locator]</code></li>
                    </ol>
                    
                    <h3><?php _e('Statistics', 'enamel-store-locator'); ?></h3>
                    <p><?php printf(__('Active Locations: %d', 'enamel-store-locator'), wp_count_posts('clinic_location')->publish); ?></p>
                    <p><?php echo get_option('enamel_sl_google_maps_api_key') ? __('Google Maps API Key: Configured ✓', 'enamel-store-locator') : __('Google Maps API Key: Not configured', 'enamel-store-locator'); ?></p>
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
                
                <h2><?php _e('Content & Text', 'enamel-store-locator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="enamel_sl_header_main_title"><?php _e('Header Title', 'enamel-store-locator'); ?></label></th>
                        <td><input type="text" id="enamel_sl_header_main_title" name="enamel_sl_header_main_title" value="<?php echo esc_attr(get_option('enamel_sl_header_main_title', 'Find Your Nearest Location')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_header_subtitle"><?php _e('Header Subtitle', 'enamel-store-locator'); ?></label></th>
                        <td><textarea id="enamel_sl_header_subtitle" name="enamel_sl_header_subtitle" class="regular-text" rows="3"><?php echo esc_textarea(get_option('enamel_sl_header_subtitle', 'Quality dental care across Texas with convenient locations')); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_search_input_placeholder"><?php _e('Search Placeholder', 'enamel-store-locator'); ?></label></th>
                        <td><input type="text" id="enamel_sl_search_input_placeholder" name="enamel_sl_search_input_placeholder" value="<?php echo esc_attr(get_option('enamel_sl_search_input_placeholder', 'Enter address or zip code')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_schedule_button_text"><?php _e('Schedule Button Text', 'enamel-store-locator'); ?></label></th>
                        <td><input type="text" id="enamel_sl_schedule_button_text" name="enamel_sl_schedule_button_text" value="<?php echo esc_attr(get_option('enamel_sl_schedule_button_text', 'Schedule Online')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_schedule_link_url"><?php _e('Schedule Link URL', 'enamel-store-locator'); ?></label></th>
                        <td><input type="url" id="enamel_sl_schedule_link_url" name="enamel_sl_schedule_link_url" value="<?php echo esc_attr(get_option('enamel_sl_schedule_link_url', '')); ?>" class="regular-text" placeholder="https://your-booking-system.com" /></td>
                    </tr>
                </table>
                
                <h2><?php _e('Colors & Branding', 'enamel-store-locator'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="enamel_sl_primary_color"><?php _e('Primary Color', 'enamel-store-locator'); ?></label></th>
                        <td><input type="text" id="enamel_sl_primary_color" name="enamel_sl_primary_color" value="<?php echo esc_attr(get_option('enamel_sl_primary_color', '#7D55C7')); ?>" class="color-picker" data-alpha="true" /></td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_accent_color"><?php _e('Accent Color', 'enamel-store-locator'); ?></label></th>
                        <td><input type="text" id="enamel_sl_accent_color" name="enamel_sl_accent_color" value="<?php echo esc_attr(get_option('enamel_sl_accent_color', '#E56B10')); ?>" class="color-picker" data-alpha="true" /></td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Map settings page
     */
    public function map_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Map Settings', 'enamel-store-locator'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('enamel_sl_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="enamel_sl_google_maps_api_key"><?php _e('Google Maps API Key', 'enamel-store-locator'); ?></label></th>
                        <td><input type="text" id="enamel_sl_google_maps_api_key" name="enamel_sl_google_maps_api_key" value="<?php echo esc_attr(get_option('enamel_sl_google_maps_api_key', '')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_default_lat"><?php _e('Default Latitude', 'enamel-store-locator'); ?></label></th>
                        <td><input type="number" id="enamel_sl_default_lat" name="enamel_sl_default_lat" value="<?php echo esc_attr(get_option('enamel_sl_default_lat', '30.3072')); ?>" step="0.0001" class="small-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_default_lng"><?php _e('Default Longitude', 'enamel-store-locator'); ?></label></th>
                        <td><input type="number" id="enamel_sl_default_lng" name="enamel_sl_default_lng" value="<?php echo esc_attr(get_option('enamel_sl_default_lng', '-97.7560')); ?>" step="0.0001" class="small-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="enamel_sl_default_zoom"><?php _e('Default Zoom Level', 'enamel-store-locator'); ?></label></th>
                        <td><input type="number" id="enamel_sl_default_zoom" name="enamel_sl_default_zoom" value="<?php echo esc_attr(get_option('enamel_sl_default_zoom', '10')); ?>" min="1" max="20" class="small-text" /></td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Shortcode handler - renders the store locator
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
        
        // Generate unique container ID
        $container_id = 'enamel-store-locator-' . uniqid();
        
        // Return placeholder - the React app would load here
        return sprintf(
            '<div id="%s" class="enamel-store-locator-container" style="width: %s; height: %s; background: #f5f5f5; border-radius: 8px; display: flex; align-items: center; justify-content: center;"><p style="color: #999; text-align: center;">Store Locator Loading...<br><small>Add your Google Maps API key in Store Locator > Map Settings</small></p></div>',
            esc_attr($container_id),
            esc_attr($atts['width']),
            esc_attr($atts['height'])
        );
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue if shortcode is present
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'enamel_store_locator')) {
            // Scripts would be enqueued here
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_enqueue_scripts($hook) {
        // Only enqueue on our admin pages
        if (strpos($hook, 'enamel-store-locator') !== false || strpos($hook, 'clinic_location') !== false) {
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_style('wp-color-picker');
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
        return !empty($value) ? 1 : 0;
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    EnamelStoreLocator::get_instance();
});

?>
