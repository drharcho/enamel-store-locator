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
        $description = $args['description'] ?? '';
        $placeholder = $args['placeholder'] ?? '';
        $type = $args['type'] ?? 'text';
        $class = $args['class'] ?? 'large-text';
        $value = get_option('enamel_sl_' . $field, $args['default'] ?? '');
        ?>
        <div class="enamel-text-field">
            <?php if ($type === 'textarea'): ?>
                <textarea name="enamel_sl_<?php echo esc_attr($field); ?>" 
                         class="<?php echo esc_attr($class); ?>"
                         placeholder="<?php echo esc_attr($placeholder); ?>"
                         rows="3"><?php echo esc_textarea($value); ?></textarea>
            <?php else: ?>
                <input type="<?php echo esc_attr($type); ?>" 
                       name="enamel_sl_<?php echo esc_attr($field); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       class="<?php echo esc_attr($class); ?>"
                       placeholder="<?php echo esc_attr($placeholder); ?>" />
            <?php endif; ?>
            <?php if ($description): ?>
                <p class="description"><?php echo wp_kses_post($description); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Color field callback
     */
    public static function color_field_callback($args) {
        $field = $args['field'];
        $description = $args['description'] ?? '';
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
        <div class="enamel-color-field">
            <input type="text" 
                   name="enamel_sl_<?php echo esc_attr($field); ?>" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="enamel-color-picker" />
            <div class="enamel-color-preview" style="background-color: <?php echo esc_attr($value); ?>"></div>
            <?php if ($description): ?>
                <p class="description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </div>
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
        $label = $args['label'] ?? '';
        $description = $args['description'] ?? '';
        $value = get_option('enamel_sl_' . $field, false);
        ?>
        <div class="enamel-checkbox-field">
            <label>
                <input type="checkbox" 
                       name="enamel_sl_<?php echo esc_attr($field); ?>" 
                       value="1" 
                       <?php checked(1, $value); ?> />
                <?php if ($label): ?>
                    <span class="checkbox-label"><?php echo esc_html($label); ?></span>
                <?php endif; ?>
            </label>
            <?php if ($description): ?>
                <p class="description"><?php echo wp_kses_post($description); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Slider field callback
     */
    public static function slider_field_callback($args) {
        $field = $args['field'];
        $min = $args['min'] ?? 1;
        $max = $args['max'] ?? 100;
        $step = $args['step'] ?? 1;
        $unit = $args['unit'] ?? '';
        $description = $args['description'] ?? '';
        $value = get_option('enamel_sl_' . $field, $args['default'] ?? $min);
        ?>
        <div class="enamel-slider-field">
            <div class="slider-container">
                <input type="range" 
                       name="enamel_sl_<?php echo esc_attr($field); ?>" 
                       value="<?php echo esc_attr($value); ?>"
                       min="<?php echo esc_attr($min); ?>"
                       max="<?php echo esc_attr($max); ?>"
                       step="<?php echo esc_attr($step); ?>"
                       class="enamel-slider"
                       data-unit="<?php echo esc_attr($unit); ?>" />
                <span class="slider-value"><?php echo esc_html($value . $unit); ?></span>
            </div>
            <?php if ($description): ?>
                <p class="description"><?php echo wp_kses_post($description); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Select field callback
     */
    public static function select_field_callback($args) {
        $field = $args['field'];
        $options = $args['options'] ?? array();
        $description = $args['description'] ?? '';
        $value = get_option('enamel_sl_' . $field, $args['default'] ?? '');
        ?>
        <div class="enamel-select-field">
            <select name="enamel_sl_<?php echo esc_attr($field); ?>" class="regular-text">
                <?php foreach ($options as $option_value => $option_label): ?>
                    <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                        <?php echo esc_html($option_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($description): ?>
                <p class="description"><?php echo wp_kses_post($description); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
?>