<?php
/**
 * REST API endpoints for Enamel Store Locator
 */

if (!defined('ABSPATH')) {
    exit;
}

class EnamelStoreLocatorAPI {
    
    /**
     * Get all locations
     */
    public static function get_locations($request) {
        $posts = get_posts(array(
            'post_type' => 'clinic_location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_enamel_sl_active',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        $locations = array();
        foreach ($posts as $post) {
            $meta = get_post_meta($post->ID);
            
            $location = array(
                'id' => $post->ID,
                'name' => $post->post_title,
                'address' => $meta['_enamel_sl_address'][0] ?? '',
                'city' => $meta['_enamel_sl_city'][0] ?? '',
                'state' => $meta['_enamel_sl_state'][0] ?? '',
                'zipCode' => $meta['_enamel_sl_zip_code'][0] ?? '',
                'phone' => $meta['_enamel_sl_phone'][0] ?? '',
                'lat' => floatval($meta['_enamel_sl_lat'][0] ?? 0),
                'lng' => floatval($meta['_enamel_sl_lng'][0] ?? 0),
                'hours' => $meta['_enamel_sl_hours'][0] ?? '',
                'services' => json_decode($meta['_enamel_sl_services'][0] ?? '[]', true),
                'businessHours' => json_decode($meta['_enamel_sl_business_hours'][0] ?? '{}', true),
                'metadata' => json_decode($meta['_enamel_sl_metadata'][0] ?? '{}', true)
            );
            
            $locations[] = $location;
        }
        
        return rest_ensure_response($locations);
    }
    
    /**
     * Get single location
     */
    public static function get_location($request) {
        $id = $request['id'];
        $post = get_post($id);
        
        if (!$post || $post->post_type !== 'clinic_location') {
            return new WP_Error('not_found', __('Location not found', 'enamel-store-locator'), array('status' => 404));
        }
        
        $meta = get_post_meta($id);
        
        $location = array(
            'id' => $id,
            'name' => $post->post_title,
            'address' => $meta['_enamel_sl_address'][0] ?? '',
            'city' => $meta['_enamel_sl_city'][0] ?? '',
            'state' => $meta['_enamel_sl_state'][0] ?? '',
            'zipCode' => $meta['_enamel_sl_zip_code'][0] ?? '',
            'phone' => $meta['_enamel_sl_phone'][0] ?? '',
            'lat' => floatval($meta['_enamel_sl_lat'][0] ?? 0),
            'lng' => floatval($meta['_enamel_sl_lng'][0] ?? 0),
            'hours' => $meta['_enamel_sl_hours'][0] ?? '',
            'services' => json_decode($meta['_enamel_sl_services'][0] ?? '[]', true),
            'businessHours' => json_decode($meta['_enamel_sl_business_hours'][0] ?? '{}', true),
            'metadata' => json_decode($meta['_enamel_sl_metadata'][0] ?? '{}', true)
        );
        
        return rest_ensure_response($location);
    }
    
    /**
     * Create location
     */
    public static function create_location($request) {
        // Additional security check
        if (!current_user_can('manage_options')) {
            return new WP_Error('forbidden', __('Insufficient permissions', 'enamel-store-locator'), array('status' => 403));
        }
        
        $params = $request->get_params();
        
        // Validate required fields
        $required_fields = array('name', 'address', 'city', 'state', 'zipCode', 'lat', 'lng');
        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new WP_Error('missing_field', sprintf(__('Missing required field: %s', 'enamel-store-locator'), $field), array('status' => 400));
            }
        }
        
        // Validate coordinates
        if (!is_numeric($params['lat']) || !is_numeric($params['lng'])) {
            return new WP_Error('invalid_coordinates', __('Invalid latitude or longitude', 'enamel-store-locator'), array('status' => 400));
        }
        
        // Create post
        $post_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($params['name']),
            'post_type' => 'clinic_location',
            'post_status' => 'publish'
        ));
        
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        
        // Save meta fields
        update_post_meta($post_id, '_enamel_sl_address', sanitize_text_field($params['address']));
        update_post_meta($post_id, '_enamel_sl_city', sanitize_text_field($params['city']));
        update_post_meta($post_id, '_enamel_sl_state', sanitize_text_field($params['state']));
        update_post_meta($post_id, '_enamel_sl_zip_code', sanitize_text_field($params['zipCode']));
        update_post_meta($post_id, '_enamel_sl_lat', floatval($params['lat']));
        update_post_meta($post_id, '_enamel_sl_lng', floatval($params['lng']));
        
        if (!empty($params['phone'])) {
            update_post_meta($post_id, '_enamel_sl_phone', sanitize_text_field($params['phone']));
        }
        
        if (!empty($params['hours'])) {
            update_post_meta($post_id, '_enamel_sl_hours', sanitize_text_field($params['hours']));
        }
        
        // Save JSON fields with validation
        if (isset($params['services']) && is_array($params['services'])) {
            $services = array_map('sanitize_text_field', $params['services']);
            update_post_meta($post_id, '_enamel_sl_services', wp_json_encode($services));
        }
        
        if (isset($params['businessHours']) && is_array($params['businessHours'])) {
            // Validate business hours structure
            $valid_hours = array();
            foreach ($params['businessHours'] as $day => $hours) {
                if (is_array($hours)) {
                    $valid_hours[sanitize_text_field($day)] = array(
                        'open' => isset($hours['open']) ? sanitize_text_field($hours['open']) : '',
                        'close' => isset($hours['close']) ? sanitize_text_field($hours['close']) : '',
                        'closed' => isset($hours['closed']) ? (bool)$hours['closed'] : false
                    );
                }
            }
            update_post_meta($post_id, '_enamel_sl_business_hours', wp_json_encode($valid_hours));
        }
        
        update_post_meta($post_id, '_enamel_sl_active', '1');
        
        return rest_ensure_response(array(
            'id' => $post_id,
            'message' => __('Location created successfully', 'enamel-store-locator')
        ));
    }
    
    /**
     * Update location
     */
    public static function update_location($request) {
        // Security check
        if (!current_user_can('manage_options')) {
            return new WP_Error('forbidden', __('Insufficient permissions', 'enamel-store-locator'), array('status' => 403));
        }
        
        $id = $request['id'];
        $params = $request->get_params();
        
        $post = get_post($id);
        if (!$post || $post->post_type !== 'clinic_location') {
            return new WP_Error('not_found', __('Location not found', 'enamel-store-locator'), array('status' => 404));
        }
        
        // Update post title if provided
        if (isset($params['name'])) {
            wp_update_post(array(
                'ID' => $id,
                'post_title' => sanitize_text_field($params['name'])
            ));
        }
        
        // Update meta fields with proper data types
        if (isset($params['address'])) {
            update_post_meta($id, '_enamel_sl_address', sanitize_text_field($params['address']));
        }
        if (isset($params['city'])) {
            update_post_meta($id, '_enamel_sl_city', sanitize_text_field($params['city']));
        }
        if (isset($params['state'])) {
            update_post_meta($id, '_enamel_sl_state', sanitize_text_field($params['state']));
        }
        if (isset($params['zipCode'])) {
            update_post_meta($id, '_enamel_sl_zip_code', sanitize_text_field($params['zipCode']));
        }
        if (isset($params['phone'])) {
            update_post_meta($id, '_enamel_sl_phone', sanitize_text_field($params['phone']));
        }
        if (isset($params['lat'])) {
            if (!is_numeric($params['lat'])) {
                return new WP_Error('invalid_lat', __('Invalid latitude', 'enamel-store-locator'), array('status' => 400));
            }
            update_post_meta($id, '_enamel_sl_lat', floatval($params['lat']));
        }
        if (isset($params['lng'])) {
            if (!is_numeric($params['lng'])) {
                return new WP_Error('invalid_lng', __('Invalid longitude', 'enamel-store-locator'), array('status' => 400));
            }
            update_post_meta($id, '_enamel_sl_lng', floatval($params['lng']));
        }
        if (isset($params['hours'])) {
            update_post_meta($id, '_enamel_sl_hours', sanitize_text_field($params['hours']));
        }
        
        // Update JSON fields with validation
        if (isset($params['services']) && is_array($params['services'])) {
            $services = array_map('sanitize_text_field', $params['services']);
            update_post_meta($id, '_enamel_sl_services', wp_json_encode($services));
        }
        
        if (isset($params['businessHours']) && is_array($params['businessHours'])) {
            // Validate business hours structure
            $valid_hours = array();
            foreach ($params['businessHours'] as $day => $hours) {
                if (is_array($hours)) {
                    $valid_hours[sanitize_text_field($day)] = array(
                        'open' => isset($hours['open']) ? sanitize_text_field($hours['open']) : '',
                        'close' => isset($hours['close']) ? sanitize_text_field($hours['close']) : '',
                        'closed' => isset($hours['closed']) ? (bool)$hours['closed'] : false
                    );
                }
            }
            update_post_meta($id, '_enamel_sl_business_hours', wp_json_encode($valid_hours));
        }
        
        if (isset($params['metadata']) && is_array($params['metadata'])) {
            // Sanitize metadata
            $clean_metadata = array();
            foreach ($params['metadata'] as $key => $value) {
                $clean_metadata[sanitize_text_field($key)] = sanitize_text_field($value);
            }
            update_post_meta($id, '_enamel_sl_metadata', wp_json_encode($clean_metadata));
        }
        
        return rest_ensure_response(array(
            'id' => $id,
            'message' => __('Location updated successfully', 'enamel-store-locator')
        ));
    }
    
    /**
     * Delete location
     */
    public static function delete_location($request) {
        // Security check
        if (!current_user_can('manage_options')) {
            return new WP_Error('forbidden', __('Insufficient permissions', 'enamel-store-locator'), array('status' => 403));
        }
        
        $id = absint($request['id']);
        
        if (!$id) {
            return new WP_Error('invalid_id', __('Invalid location ID', 'enamel-store-locator'), array('status' => 400));
        }
        
        $post = get_post($id);
        if (!$post || $post->post_type !== 'clinic_location') {
            return new WP_Error('not_found', __('Location not found', 'enamel-store-locator'), array('status' => 404));
        }
        
        // Soft delete by setting inactive
        update_post_meta($id, '_enamel_sl_active', '0');
        wp_update_post(array(
            'ID' => $id,
            'post_status' => 'draft'
        ));
        
        return rest_ensure_response(array(
            'message' => __('Location deleted successfully', 'enamel-store-locator')
        ));
    }
    
    /**
     * Get settings
     */
    public static function get_settings($request) {
        $plugin = EnamelStoreLocator::get_instance();
        $settings = $plugin->get_all_settings();
        
        return rest_ensure_response($settings);
    }
    
    /**
     * Update settings
     */
    public static function update_settings($request) {
        // Security check
        if (!current_user_can('manage_options')) {
            return new WP_Error('forbidden', __('Insufficient permissions', 'enamel-store-locator'), array('status' => 403));
        }
        
        $params = $request->get_params();
        
        // Define allowed settings with their sanitization functions
        $allowed_settings = array(
            'google_maps_api_key' => 'sanitize_text_field',
            'default_lat' => 'floatval',
            'default_lng' => 'floatval', 
            'default_zoom' => 'intval',
            'primary_color' => 'sanitize_hex_color',
            'accent_color' => 'sanitize_hex_color',
            'background_color' => 'sanitize_hex_color',
            'card_background' => 'sanitize_hex_color',
            'primary_text' => 'sanitize_hex_color',
            'secondary_text' => 'sanitize_hex_color',
            'header_main_title' => 'sanitize_text_field',
            'header_subtitle' => 'sanitize_text_field',
            'search_section_title' => 'sanitize_text_field',
            'search_input_placeholder' => 'sanitize_text_field',
            'search_button_text' => 'sanitize_text_field',
            'location_button_text' => 'sanitize_text_field',
            'footer_text' => 'sanitize_text_field',
            'directions_button_text' => 'sanitize_text_field',
            'call_button_text' => 'sanitize_text_field',
            'map_type' => function($value) {
                $allowed = array('roadmap', 'satellite', 'hybrid', 'terrain');
                return in_array($value, $allowed) ? $value : 'roadmap';
            },
            'marker_color' => 'sanitize_hex_color',
            'enable_clustering' => function($value) {
                return !empty($value) ? 1 : 0;
            }
        );
        
        foreach ($params as $key => $value) {
            if (isset($allowed_settings[$key])) {
                $sanitizer = $allowed_settings[$key];
                if (is_callable($sanitizer)) {
                    $sanitized_value = call_user_func($sanitizer, $value);
                    update_option('enamel_sl_' . $key, $sanitized_value);
                }
            }
        }
        
        return rest_ensure_response(array(
            'message' => __('Settings updated successfully', 'enamel-store-locator')
        ));
    }
}
?>