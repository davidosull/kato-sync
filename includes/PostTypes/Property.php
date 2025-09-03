<?php

namespace KatoSync\PostTypes;

/**
 * Property custom post type
 */
class Property {

  /**
   * Register the custom post type
   */
  public static function register(): void {
    $labels = array(
      'name' => __('Properties', 'kato-sync'),
      'singular_name' => __('Property', 'kato-sync'),
      'menu_name' => __('Properties', 'kato-sync'),
      'add_new' => __('Add New', 'kato-sync'),
      'add_new_item' => __('Add New Property', 'kato-sync'),
      'edit_item' => __('Edit Property', 'kato-sync'),
      'new_item' => __('New Property', 'kato-sync'),
      'view_item' => __('View Property', 'kato-sync'),
      'search_items' => __('Search Properties', 'kato-sync'),
      'not_found' => __('No properties found', 'kato-sync'),
      'not_found_in_trash' => __('No properties found in trash', 'kato-sync'),
    );

    $args = array(
      'labels' => $labels,
      'public' => true,
      'publicly_queryable' => true,
      'show_ui' => true,
      'show_in_menu' => false, // We'll add it to our custom menu
      'show_in_nav_menus' => true,
      'show_in_rest' => true,
      'query_var' => true,
      'rewrite' => array(
        'slug' => 'property',
        'with_front' => false,
      ),
      'capability_type' => 'post',
      'has_archive' => true,
      'hierarchical' => false,
      'menu_position' => null,
      'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
      'show_in_admin_bar' => true,
      'taxonomies' => array('kato_property_type', 'kato_location', 'kato_availability'),
    );

    register_post_type('kato-property', $args);
  }

  /**
   * Generate property title from data
   */
  public static function generate_title(array $property_data): string {
    $parts = array();

    if (!empty($property_data['name'])) {
      $parts[] = $property_data['name'];
    }

    if (!empty($property_data['address1'])) {
      $parts[] = $property_data['address1'];
    }

    if (!empty($property_data['postcode'])) {
      $parts[] = $property_data['postcode'];
    }

    return implode(' ', $parts);
  }

  /**
   * Generate property slug from data
   */
  public static function generate_slug(array $property_data): string {
    $title = self::generate_title($property_data);
    return sanitize_title($title);
  }

  /**
   * Get property by external ID
   */
  public static function get_by_external_id(string $external_id): ?\WP_Post {
    $posts = get_posts(array(
      'post_type' => 'kato-property',
      'meta_key' => '_kato_sync_external_id',
      'meta_value' => $external_id,
      'numberposts' => 1,
      'post_status' => 'any',
    ));

    return !empty($posts) ? $posts[0] : null;
  }

  /**
   * Create or update property from data
   */
  public static function create_or_update(array $property_data): int {
    $external_id = $property_data['id'] ?? '';
    $existing_property = self::get_by_external_id($external_id);

    $post_data = array(
      'post_title' => self::generate_title($property_data),
      'post_name' => self::generate_slug($property_data),
      'post_type' => 'kato-property',
      'post_status' => 'publish',
      'post_content' => $property_data['description'] ?? '',
      'post_excerpt' => $property_data['specification_summary'] ?? '',
    );

    if ($existing_property) {
      $post_data['ID'] = $existing_property->ID;
      $post_id = wp_update_post($post_data);
    } else {
      $post_id = wp_insert_post($post_data);
    }

    if ($post_id && !is_wp_error($post_id)) {
      // Store property meta data
      self::update_property_meta($post_id, $property_data);
    }

    return $post_id;
  }

  /**
   * Update property meta data with comprehensive mapping
   */
  public static function update_property_meta(int $post_id, array $property_data): void {
    $external_id = $property_data['id'] ?? '';

    // Store external ID for future reference
    if (!empty($property_data['id'])) {
      update_post_meta($post_id, '_kato_sync_external_id', $property_data['id']);
    }

    // Store last modified timestamp
    if (!empty($property_data['last_updated'])) {
      update_post_meta($post_id, '_kato_sync_lastmod', $property_data['last_updated']);
    }

    // Store import timestamp
    update_post_meta($post_id, '_kato_sync_imported_at', current_time('mysql'));

    // Handle types - store all types properly
    if (!empty($property_data['types'])) {
      $type_values = array();

      if (is_array($property_data['types'])) {
        // Check if types contains a 'type' key with multiple type objects
        if (isset($property_data['types']['type']) && is_array($property_data['types']['type'])) {
          // Handle multiple type objects under types/type structure
          foreach ($property_data['types']['type'] as $type) {
            if (is_array($type) && isset($type['name'])) {
              // Type object with name/id structure
              $type_values[] = $type['name'];
            } elseif (is_string($type) && !empty($type)) {
              // Simple string type
              $type_values[] = $type;
            }
          }
        } else {
          // Handle array of types directly
          foreach ($property_data['types'] as $type) {
            if (is_array($type) && isset($type['name'])) {
              // Type object with name/id structure
              $type_values[] = $type['name'];
            } elseif (is_string($type) && !empty($type)) {
              // Simple string type
              $type_values[] = $type;
            }
          }
        }
      } else {
        // Handle string type
        $type_values = array($property_data['types']);
      }

      // Clean and deduplicate types
      $type_values = array_unique(array_filter($type_values, function ($item) {
        return is_string($item) && !empty(trim($item));
      }));

      // Store all types as JSON for the types field
      if (!empty($type_values)) {
        $property_data['types'] = $type_values;
        // Also store the first type as property_type for backward compatibility
        $property_data['property_type'] = $type_values[0];

        // Debug logging removed to prevent excessive log output
      }
    }

    // Comprehensive field mapping
    $field_mapping = self::get_field_mapping();

    // Debug logging for agent data
    if (isset($property_data['agent'])) {
    }

    foreach ($field_mapping as $xml_field => $meta_key) {
      if (isset($property_data[$xml_field])) {
        $value = $property_data[$xml_field];

        // Handle different data types
        if (is_array($value)) {
          // Store arrays as JSON for easy access
          $value = json_encode($value);
        } elseif (is_string($value) && !empty($value)) {
          // Clean string values
          $value = sanitize_text_field($value);
        }

        if (!empty($value)) {
          update_post_meta($post_id, $meta_key, $value);
          // Debug logging for contacts specifically
          if ($xml_field === 'contacts') {
          }
        }
      }
    }

    // Store raw data for debugging
    update_post_meta($post_id, '_kato_sync_raw_data', json_encode($property_data));

    // Debug logging removed to prevent excessive log output
  }

  /**
   * Get comprehensive field mapping
   */
  private static function get_field_mapping(): array {
    return array(
      // Basic information
      'id' => '_kato_sync_id',
      'object_id' => '_kato_sync_object_id',
      'name' => '_kato_sync_name',
      'property_type' => '_kato_sync_property_type',
      'status' => '_kato_sync_status',
      'description' => '_kato_sync_description',
      'features' => '_kato_sync_features',
      'created_at' => '_kato_sync_created_at',
      'last_updated' => '_kato_sync_last_updated',
      'featured' => '_kato_sync_featured',
      'fitted' => '_kato_sync_fitted',
      'fitted_comment' => '_kato_sync_fitted_comment',
      'specification_summary' => '_kato_sync_specification_summary',
      'specification_promo' => '_kato_sync_specification_promo',
      'specification_description' => '_kato_sync_specification_description',
      'url' => '_kato_sync_url',
      'particulars_url' => '_kato_sync_particulars_url',
      'togc' => '_kato_sync_togc',
      'sale_type' => '_kato_sync_sale_type',
      'tenancy_passing_giy' => '_kato_sync_tenancy_passing_giy',
      'tenancy_passing_niy' => '_kato_sync_tenancy_passing_niy',
      'turnover_pa' => '_kato_sync_turnover_pa',
      'tenancy_status' => '_kato_sync_tenancy_status',
      'class_of_use' => '_kato_sync_class_of_use',
      'legal_fees_applicable' => '_kato_sync_legal_fees_applicable',
      'lease_length' => '_kato_sync_lease_length',
      'protected_act' => '_kato_sync_protected_act',
      'insurance_type' => '_kato_sync_insurance_type',
      'availability_reasons' => '_kato_sync_availability_reasons',
      'shop_frontage_ft' => '_kato_sync_shop_frontage_ft',
      'shop_frontage_m' => '_kato_sync_shop_frontage_m',
      'shop_frontage_inches' => '_kato_sync_shop_frontage_inches',
      'travel_times' => '_kato_sync_travel_times',
      'tags' => '_kato_sync_tags',

      // Marketing information
      'marketing_title_1' => '_kato_sync_marketing_title_1',
      'marketing_title_2' => '_kato_sync_marketing_title_2',
      'marketing_title_3' => '_kato_sync_marketing_title_3',
      'marketing_title_4' => '_kato_sync_marketing_title_4',
      'marketing_title_5' => '_kato_sync_marketing_title_5',
      'marketing_text_1' => '_kato_sync_marketing_text_1',
      'marketing_text_2' => '_kato_sync_marketing_text_2',
      'marketing_text_3' => '_kato_sync_marketing_text_3',
      'marketing_text_4' => '_kato_sync_marketing_text_4',
      'marketing_text_5' => '_kato_sync_marketing_text_5',
      'marketing_title_transport' => '_kato_sync_marketing_title_transport',
      'marketing_text_transport' => '_kato_sync_marketing_text_transport',

      // Location information
      'address1' => '_kato_sync_address1',
      'address2' => '_kato_sync_address2',
      'town' => '_kato_sync_town',
      'city' => '_kato_sync_city',
      'county' => '_kato_sync_county',
      'postcode' => '_kato_sync_postcode',
      'lat' => '_kato_sync_lat',
      'lon' => '_kato_sync_lon',
      'latitude' => '_kato_sync_latitude',
      'longitude' => '_kato_sync_longitude',
      'location' => '_kato_sync_location',
      'street_view_data' => '_kato_sync_street_view_data',
      'submarkets' => '_kato_sync_submarkets',

      // Size information
      'size_sqft' => '_kato_sync_size_sqft',
      'size_from' => '_kato_sync_size_from',
      'size_to' => '_kato_sync_size_to',
      'total_property_size' => '_kato_sync_total_property_size',
      'total_property_size_metric' => '_kato_sync_total_property_size_metric',
      'area_size_unit' => '_kato_sync_area_size_unit',
      'area_size_type' => '_kato_sync_area_size_type',
      'size_from_sqft' => '_kato_sync_size_from_sqft',
      'size_to_sqft' => '_kato_sync_size_to_sqft',
      'size_measure' => '_kato_sync_size_measure',
      'land_size_from' => '_kato_sync_land_size_from',
      'land_size_to' => '_kato_sync_land_size_to',
      'land_size_metric' => '_kato_sync_land_size_metric',

      // Pricing information
      'price' => '_kato_sync_price',
      'rent' => '_kato_sync_rent',
      'price_per_sqft' => '_kato_sync_price_per_sqft',
      'price_per_sqft_min' => '_kato_sync_price_per_sqft_min',
      'price_per_sqft_max' => '_kato_sync_price_per_sqft_max',
      'total_price' => '_kato_sync_total_price',
      'total_monthly_min' => '_kato_sync_total_monthly_min',
      'total_monthly_max' => '_kato_sync_total_monthly_max',
      'total_yearly_min' => '_kato_sync_total_yearly_min',
      'total_yearly_max' => '_kato_sync_total_yearly_max',
      'turnover' => '_kato_sync_turnover',
      'profit_gross' => '_kato_sync_profit_gross',
      'profit_net' => '_kato_sync_profit_net',
      'initial_yield' => '_kato_sync_initial_yield',
      'premium' => '_kato_sync_premium',
      'premium_nil' => '_kato_sync_premium_nil',
      'parking_ratio' => '_kato_sync_parking_ratio',
      'rent_components' => '_kato_sync_rent_components',
      'service_charge' => '_kato_sync_service_charge',

      // Calculated size and price ranges (from floor units and main data)
      'size_min' => '_kato_sync_size_min',
      'size_max' => '_kato_sync_size_max',
      'price_min' => '_kato_sync_price_min',
      'price_max' => '_kato_sync_price_max',
      'price_type' => '_kato_sync_price_type',

      // Agent information
      'agent_name' => '_kato_sync_agent_name',
      'agent_email' => '_kato_sync_agent_email',
      'agent_phone' => '_kato_sync_agent_phone',
      'agent_company' => '_kato_sync_agent_company',

      // Complex data (arrays and objects)
      'types' => '_kato_sync_types',
      'availabilities' => '_kato_sync_availabilities',
      'key_selling_points' => '_kato_sync_key_selling_points',
      'amenities_specifications' => '_kato_sync_amenities_specifications',
      'contacts' => '_kato_sync_contacts',
      'joint_agents' => '_kato_sync_joint_agents',
      'images' => '_kato_sync_images',
      'original_images' => '_kato_sync_original_images',
      'files' => '_kato_sync_files',
      'epcs' => '_kato_sync_epcs',
      'videos' => '_kato_sync_videos',
      'videos_detail' => '_kato_sync_videos_detail',
      'floor_units' => '_kato_sync_floor_units',
      'current_energy_ratings' => '_kato_sync_current_energy_ratings',
    );
  }

  /**
   * Get property meta value with fallback
   */
  public static function get_meta_value(int $post_id, string $meta_key, $default = 'No data') {
    $value = get_post_meta($post_id, $meta_key, true);

    if (empty($value)) {
      return $default;
    }

    // Try to decode JSON if it looks like JSON
    if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
      $decoded = json_decode($value, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
      }
    }

    return $value;
  }

  /**
   * Get all property meta data
   */
  public static function get_all_meta_data(int $post_id): array {
    $field_mapping = self::get_field_mapping();
    $data = array();

    foreach ($field_mapping as $xml_field => $meta_key) {
      $data[$xml_field] = self::get_meta_value($post_id, $meta_key);
    }

    return $data;
  }

  /**
   * Get property by ID
   */
  public static function get_by_id(int $post_id): ?\WP_Post {
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'kato-property') {
      return null;
    }

    return $post;
  }
}
