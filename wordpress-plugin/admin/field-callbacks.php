<?php
/**
 * Field callback functions for settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class EnamelStoreLocatorFields {
    
    /**
     * General section callback
     */
    public static function general_section_callback() {
        echo '<p>' . __('Configure your Google Maps integration and default map settings.', 'enamel-store-locator') . '</p>';
    }
    
    /**
     * Content section callback
     */
    public static function content_section_callback() {
        echo '<p>' . __('Customize all text that appears in your store locator.', 'enamel-store-locator') . '</p>';
    }
    
    /**
     * Colors section callback
     */
    public static function colors_section_callback() {
        echo '<p>' . __('Customize colors to match your brand.', 'enamel-store-locator') . '</p>';
    }
    
    /**
     * Map section callback
     */
    public static function map_section_callback() {
        echo '<p>' . __('Configure map appearance and behavior.', 'enamel-store-locator') . '</p>';
    }
    
    /**
     * API key field callback
     */
    public static function api_key_field_callback() {
        $value = get_option('enamel_sl_google_maps_api_key', '');
        ?>
        <input type="text" 
               name="enamel_sl_google_maps_api_key" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description">
            <?php _e('Get your API key from the', 'enamel-store-locator'); ?>
            <a href="https://console.cloud.google.com/" target="_blank"><?php _e('Google Cloud Console', 'enamel-store-locator'); ?></a>
        </p>
        <?php
    }
    
    /**
     * Default center field callback
     */
    public static function default_center_field_callback() {
        $lat = get_option('enamel_sl_default_lat', '30.3072');
        $lng = get_option('enamel_sl_default_lng', '-97.7560');
        ?>
        <div>
            <label><?php _e('Latitude:', 'enamel-store-locator'); ?>
                <input type="text" name="enamel_sl_default_lat" value="<?php echo esc_attr($lat); ?>" />
            </label>
            <br><br>
            <label><?php _e('Longitude:', 'enamel-store-locator'); ?>
                <input type="text" name="enamel_sl_default_lng" value="<?php echo esc_attr($lng); ?>" />
            </label>
        </div>
        <p class="description"><?php _e('Default center point for the map (Austin, TX coordinates shown)', 'enamel-store-locator'); ?></p>
        <?php
    }
    
    /**
     * Text field callback
     */
    public static function text_field_callback($args) {
        $field = $args['field'];
        $value = get_option('enamel_sl_' . $field, '');
        ?>
        <input type="text" 
               name="enamel_sl_<?php echo esc_attr($field); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               class="large-text" />
        <?php
    }
    
    /**
     * Color field callback
     */
    public static function color_field_callback($args) {
        $field = $args['field'];
        $default_colors = array(
            'primary_color' => '#7D55C7',
            'accent_color' => '#E56B10',
            'background_color' => '#FFFFFF',
            'card_background' => '#F8F9FA',
            'primary_text' => '#231942',
            'secondary_text' => '#6B7280',
            'marker_color' => '#7D55C7'
        );
        
        $value = get_option('enamel_sl_' . $field, $default_colors[$field] ?? '#000000');
        ?>
        <input type="text" 
               name="enamel_sl_<?php echo esc_attr($field); ?>" 
               value="<?php echo esc_attr($value); ?>" 
               class="color-field" />
        <?php
    }
    
    /**
     * Map type field callback
     */
    public static function map_type_field_callback() {
        $value = get_option('enamel_sl_map_type', 'roadmap');
        $types = array(
            'roadmap' => __('Roadmap (Standard)', 'enamel-store-locator'),
            'satellite' => __('Satellite', 'enamel-store-locator'),
            'hybrid' => __('Hybrid (Satellite + Labels)', 'enamel-store-locator'),
            'terrain' => __('Terrain', 'enamel-store-locator')
        );
        ?>
        <select name="enamel_sl_map_type">
            <?php foreach ($types as $type => $label): ?>
                <option value="<?php echo esc_attr($type); ?>" <?php selected($value, $type); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Checkbox field callback
     */
    public static function checkbox_field_callback($args) {
        $field = $args['field'];
        $value = get_option('enamel_sl_' . $field, false);
        ?>
        <input type="checkbox" 
               name="enamel_sl_<?php echo esc_attr($field); ?>" 
               value="1" 
               <?php checked(1, $value); ?> />
        <?php
    }
}
?>